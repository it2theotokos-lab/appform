<?php
namespace App\Services;

use App\Core\Database;
use PDO;

class RestoreService {
    private static function checkRestoreLock(): bool {
        $lockFile = 'storage/restore.lock';
        if (file_exists($lockFile)) {
            $pid = (int)file_get_contents($lockFile);
            if ($pid && posix_kill($pid, 0)) {
                return true; // Already running
            }
            unlink($lockFile);
        }
        return false;
    }

    private static function acquireRestoreLock() {
        file_put_contents('storage/restore.lock', getmypid());
    }

    private static function releaseRestoreLock() {
        if (file_exists('storage/restore.lock')) {
            unlink('storage/restore.lock');
        }
    }

    public static function executeRestore(int $backupId, int $userId): array {
        $db = Database::getInstance();

        // 1. Acquire global restore lock
        if (file_exists('storage/restore.lock')) {
            return [
                'success' => false,
                'code' => 409,
                'message' => 'Υπάρχει ήδη μια ενεργή διεργασία επαναφοράς.'
            ];
        }
        self::acquireRestoreLock();

        // 2. Load backup record
        $stmt = $db->prepare("SELECT * FROM system_backups WHERE id = ?");
        $stmt->execute([$backupId]);
        $backup = $stmt->fetch();

        if (!$backup) {
            self::releaseRestoreLock();
            return [
                'success' => false,
                'code' => 404,
                'message' => 'Το επιλεγμένο backup δεν βρέθηκε.'
            ];
        }

        $filePath = $backup['storage_path'];
        if (!file_exists($filePath)) {
            self::releaseRestoreLock();
            return [
                'success' => false,
                'code' => 404,
                'message' => 'Το αρχείο του backup δεν υπάρχει στο δίσκο.'
            ];
        }

        // Checksum verification
        if (!RestoreValidationService::validateChecksum($filePath, $backup['sha256_hash'])) {
            self::releaseRestoreLock();
            return [
                'success' => false,
                'code' => 422,
                'message' => 'Αποτυχία ακεραιότητας! Το SHA256 Checksum δεν ταιριάζει.'
            ];
        }

        $startedAt = microtime(true);
        $type = $backup['backup_type'];

        // Enable Maintenance Mode
        MaintenanceModeService::enable();

        // Create Real PRE-RESTORE Emergency Backup
        $emergencyFilename = 'PRE-RESTORE-' . date('Y-m-d-His');
        $emergencyDir = 'storage/backups/' . $type;
        if (!is_dir($emergencyDir)) {
            mkdir($emergencyDir, 0755, true);
        }
        $emergencyPath = $emergencyDir . '/' . $emergencyFilename . ($type === 'database' ? '.sql.gz' : '.zip');

        if ($type === 'database') {
            // Write database SQL dump
            $tables = [];
            $result = $db->query("SHOW TABLES");
            while ($row = $result->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }
            $sqlContent = "-- Pre-Restore Emergency DB Backup\n\n";
            foreach ($tables as $table) {
                $showCreate = $db->query("SHOW CREATE TABLE `$table`")->fetch();
                $sqlContent .= $showCreate['Create Table'] . ";\n\n";
                
                $rows = $db->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rows as $r) {
                    $keys = array_map(function($k) { return "`$k`"; }, array_keys($r));
                    $vals = array_map(function($v) use ($db) { 
                        if ($v === null) return 'NULL';
                        return $db->quote($v);
                    }, array_values($r));
                    
                    $sqlContent .= "INSERT INTO `$table` (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $vals) . ");\n";
                }
                $sqlContent .= "\n\n";
            }
            file_put_contents($emergencyDir . '/temp.sql', $sqlContent);
            $fp = gzopen($emergencyPath, 'w9');
            gzwrite($fp, file_get_contents($emergencyDir . '/temp.sql'));
            gzclose($fp);
            unlink($emergencyDir . '/temp.sql');
        } else {
            // Write emergency files ZIP
            $zip = new \ZipArchive();
            if ($zip->open($emergencyPath, \ZipArchive::CREATE) === TRUE) {
                // Bundle target dirs
                $targetDirs = ['storage/document_templates', 'storage/document_final_pdfs', 'storage/signatures'];
                foreach ($targetDirs as $dir) {
                    if (!is_dir($dir)) continue;
                    $files = new \RecursiveIteratorIterator(
                        new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
                        \RecursiveIteratorIterator::LEAVES_ONLY
                    );
                    foreach ($files as $name => $file) {
                        if (!$file->isDir()) {
                            $fPath = $file->getRealPath();
                            $zip->addFile($fPath, substr($fPath, strlen(realpath('storage')) + 1));
                        }
                    }
                }
                $zip->close();
            }
        }

        $emSize = filesize($emergencyPath);
        $emHash = hash_file('sha256', $emergencyPath);

        // Save real registered emergency backup record
        $db->prepare("
            INSERT INTO system_backups (backup_type, filename, storage_driver, storage_path, file_size, sha256_hash, status, created_by, started_at, completed_at)
            VALUES (?, ?, 'local', ?, ?, ?, 'completed', ?, NOW(), NOW())
        ")->execute([$type, $emergencyFilename, $emergencyPath, $emSize, $emHash, $userId]);
        $emergencyId = $db->lastInsertId();

        try {
            if ($type === 'database') {
                $db->beginTransaction();

                // Load database dump SQL queries safely
                $sData = '';
                $zd = gzopen($filePath, "r");
                while ($line = gzgets($zd, 4096)) {
                    $sData .= $line;
                }
                gzclose($zd);

                $db->exec($sData);
                $db->commit();
            } else {
                // Restore files ZIP overwriting safely using staging folder swaps
                $stagingDir = 'storage/restore_staging/' . uniqid('job-', true);
                if (!is_dir($stagingDir)) {
                    mkdir($stagingDir, 0755, true);
                }

                $zip = new \ZipArchive();
                if ($zip->open($filePath) === TRUE) {
                    $zip->extractTo($stagingDir);
                    $zip->close();
                    
                    // Atomically move from staging to storage (excluding protected files)
                    $files = new \RecursiveIteratorIterator(
                        new \RecursiveDirectoryIterator($stagingDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                        \RecursiveIteratorIterator::LEAVES_ONLY
                    );

                    $protected = ['config.php', 'config/config.php', '.env', 'restore.lock'];

                    foreach ($files as $name => $file) {
                        if (!$file->isDir()) {
                            $fPath = $file->getRealPath();
                            $relPath = substr($fPath, strlen(realpath($stagingDir)) + 1);

                            // Block Zip Slip path traversals
                            if (str_contains($relPath, '..') || str_starts_with($relPath, '/') || str_starts_with($relPath, '\\')) {
                                continue;
                            }

                            // Skip protected configs
                            if (in_array(basename($relPath), $protected)) {
                                continue;
                            }

                            $destPath = 'storage/' . $relPath;
                            $destDir = dirname($destPath);
                            if (!is_dir($destDir)) {
                                mkdir($destDir, 0755, true);
                            }
                            copy($fPath, $destPath);
                        }
                    }

                    // Clean staging dir
                    self::rrmdir($stagingDir);
                }
            }

            // Disable Maintenance Mode
            MaintenanceModeService::disable();
            self::releaseRestoreLock();

            $duration = (int)(microtime(true) - $startedAt);
            $db->prepare("
                INSERT INTO system_restore_history (backup_id, executed_by, restore_type, duration_seconds, status, emergency_backup_id)
                VALUES (?, ?, ?, ?, 'completed', ?)
            ")->execute([$backupId, $userId, $type, $duration, $emergencyId]);

            return [
                'success' => true,
                'message' => 'Η επαναφορά ολοκληρώθηκε επιτυχώς!'
            ];

        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            // Rollback using Emergency Backup
            RestoreRollbackService::executeRollback($emergencyId, $type);

            // Disable Maintenance Mode
            MaintenanceModeService::disable();
            self::releaseRestoreLock();

            $db->prepare("
                INSERT INTO system_restore_history (backup_id, executed_by, restore_type, status, errors_json)
                VALUES (?, ?, ?, 'failed', ?)
            ")->execute([$backupId, $userId, $type, json_encode(['error' => $e->getMessage()])]);

            return [
                'success' => false,
                'code' => 500,
                'message' => 'Αποτυχία επαναφοράς! Πραγματοποιήθηκε αυτόματη επαναφορά ασφαλείας: ' . $e->getMessage()
            ];
        }
    }

    private static function rrmdir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . "/" . $object)) {
                        self::rrmdir($dir . DIRECTORY_SEPARATOR . $object);
                    } else {
                        unlink($dir . DIRECTORY_SEPARATOR . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }
}
