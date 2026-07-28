<?php
namespace App\Services;

use App\Core\Database;
use PDO;

class CloudReplicationService {
    public static function queueReplication(int $backupId, string $provider, int $userId): array {
        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            // Row Lock system_backups for safety against concurrent requests
            $stmtLock = $db->prepare("SELECT id FROM system_backups WHERE id = ? FOR UPDATE");
            $stmtLock->execute([$backupId]);
            $backupExists = $stmtLock->fetch();
            if (!$backupExists) {
                $db->rollBack();
                return [
                    'success' => false,
                    'code' => 404,
                    'message' => 'Το backup δεν βρέθηκε.'
                ];
            }

            // Retrieve timeout settings
            $cloudTimeout = \App\Core\App::$config['queue']['cloud_upload_timeout'] ?? 1800;
            $heartbeatTimeout = \App\Core\App::$config['queue']['worker_heartbeat_timeout'] ?? 300;

            // Heartbeat check for active queue worker
            $stmtHB = $db->prepare("SELECT last_heartbeat FROM worker_heartbeats WHERE worker_name = 'queue_worker'");
            $stmtHB->execute();
            $lastHeartbeat = $stmtHB->fetchColumn();

            $isWorkerOffline = true;
            if ($lastHeartbeat) {
                $isWorkerOffline = (time() - strtotime($lastHeartbeat)) > $heartbeatTimeout;
            }

            // Stale detection check and clean up individually
            $staleLimit = date('Y-m-d H:i:s', time() - $cloudTimeout);
            $stmtActive = $db->prepare("
                SELECT r.*, j.status as ent_status 
                FROM cloud_replication_jobs r
                LEFT JOIN jobs j ON r.enterprise_job_id = j.id
                WHERE r.backup_id = ? AND r.provider = ? 
                  AND r.status NOT IN ('completed', 'failed', 'cancelled')
            ");
            $stmtActive->execute([$backupId, $provider]);
            $activeJobs = $stmtActive->fetchAll(PDO::FETCH_ASSOC);

            foreach ($activeJobs as $act) {
                $rId = (int)$act['id'];
                $isStale = false;
                $reason = '';

                if (empty($act['enterprise_job_id'])) {
                    $isStale = true;
                    $reason = 'Orphaned replication job: no enterprise job assigned.';
                } elseif (in_array($act['ent_status'], ['completed', 'failed', 'cancelled'])) {
                    $isStale = true;
                    $reason = "Enterprise queue job is in terminal state ({$act['ent_status']}).";
                } elseif ($act['ent_status'] === 'running' || $act['ent_status'] === 'processing') {
                    $inactiveDuration = time() - strtotime($act['updated_at']);
                    if ($inactiveDuration > $cloudTimeout && $isWorkerOffline) {
                        $isStale = true;
                        $reason = 'Worker offline and job execution timed out.';
                    }
                } elseif ($act['created_at'] && (time() - strtotime($act['created_at']) > $cloudTimeout)) {
                    $isStale = true;
                    $reason = 'Replication job stuck in queue longer than timeout.';
                }

                if ($isStale) {
                    $db->prepare("
                        UPDATE cloud_replication_jobs 
                        SET status = 'failed', last_error = ?, completed_at = NOW() 
                        WHERE id = ?
                    ")->execute([$reason, $rId]);
                    
                    $db->prepare("
                        UPDATE system_backups 
                        SET cloud_status = 'failed', cloud_error = ? 
                        WHERE id = ?
                    ")->execute([$reason, $backupId]);
                }
            }

            // Check if there is still a valid active replication job
            $stmtCheck = $db->prepare("
                SELECT id FROM cloud_replication_jobs 
                WHERE backup_id = ? AND provider = ? AND status NOT IN ('completed', 'failed', 'cancelled')
            ");
            $stmtCheck->execute([$backupId, $provider]);
            if ($stmtCheck->fetch()) {
                $db->rollBack();
                return [
                    'success' => false,
                    'code' => 409,
                    'message' => 'Υπάρχει ήδη μια ενεργή διεργασία συγχρονισμού για αυτό το backup στον ίδιο Provider.'
                ];
            }

            // Create cloud_replication_job
            $stmt = $db->prepare("
                INSERT INTO cloud_replication_jobs (backup_id, provider, status, created_by)
                VALUES (?, ?, 'pending', ?)
            ");
            $stmt->execute([$backupId, $provider, $userId]);
            $replicationJobId = $db->lastInsertId();

            // Create Enterprise Queue Job
            $enterpriseJobId = \App\Services\JobQueueService::dispatch(\App\Jobs\CloudUploadJob::class, [
                'backup_id' => $backupId,
                'provider' => $provider,
                'replication_job_id' => $replicationJobId
            ], 'default');

            // Store correlation link
            $db->prepare("
                UPDATE cloud_replication_jobs 
                SET enterprise_job_id = ? 
                WHERE id = ?
            ")->execute([$enterpriseJobId, $replicationJobId]);

            $db->commit();

            return [
                'success' => true,
                'job_id' => $replicationJobId,
                'message' => 'Η εργασία συγχρονισμού προστέθηκε στην ουρά (Job ID: ' . $replicationJobId . ').'
            ];

        } catch (\Exception $e) {
            $db->rollBack();
            return [
                'success' => false,
                'code' => 500,
                'message' => 'Σφάλμα κατά την προσθήκη στην ουρά: ' . $e->getMessage()
            ];
        }
    }

    public static function processQueue(): int {
        // Log deprecation warning
        error_log("WARNING: CloudReplicationService::processQueue() is deprecated. Background tasks should run via queue:work.");
        return 0;
    }

    public static function processSpecificJob(int $replicationJobId): bool {
        $db = Database::getInstance();

        // 1. Fetch specific job
        $stmt = $db->prepare("SELECT * FROM cloud_replication_jobs WHERE id = ?");
        $stmt->execute([$replicationJobId]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$job) {
            throw new \Exception("Replication job ID {$replicationJobId} not found.");
        }

        $backupId = (int)$job['backup_id'];
        $provider = $job['provider'];

        // Update heartbeat
        $db->prepare("INSERT INTO worker_heartbeats (worker_name, last_heartbeat) VALUES ('queue_worker', NOW()) ON DUPLICATE KEY UPDATE last_heartbeat = NOW()")->execute();

        // Increment attempts
        $attempts = (int)$job['attempts'] + 1;
        $db->prepare("UPDATE cloud_replication_jobs SET attempts = ? WHERE id = ?")->execute([$attempts, $replicationJobId]);

        // Load backup details
        $stmtBackup = $db->prepare("SELECT * FROM system_backups WHERE id = ?");
        $stmtBackup->execute([$backupId]);
        $backup = $stmtBackup->fetch(PDO::FETCH_ASSOC);

        if (!$backup) {
            throw new \Exception("Backup record not found.");
        }

        $filePath = $backup['storage_path'];
        if (!file_exists($filePath)) {
            $filePath = 'public/' . $backup['storage_path'];
        }

        if (!file_exists($filePath)) {
            throw new \Exception("Backup file not found on disk: " . $backup['storage_path']);
        }
        $totalBytes = filesize($filePath);

        // Update total bytes
        $db->prepare("UPDATE cloud_replication_jobs SET total_bytes = ? WHERE id = ?")->execute([$totalBytes, $replicationJobId]);

        // Select Storage Provider
        $providerInstance = ($provider === 'googledrive') ? new GoogleDriveProvider() : new OneDriveProvider();

        // Optional Encryption
        $encryptJob = true; 
        if ($encryptJob) {
            $db->prepare("UPDATE cloud_replication_jobs SET status = 'encrypting' WHERE id = ?")->execute([$replicationJobId]);
            $encPath = $filePath . '.appform.enc';
            CloudEncryptionService::encryptFile($filePath, $encPath);
            $filePath = $encPath;
            $totalBytes = filesize($filePath);
        }

        // Start Resumable Upload Session
        $db->prepare("UPDATE cloud_replication_jobs SET status = 'uploading' WHERE id = ?")->execute([$replicationJobId]);
        
        $session = $providerInstance->startUpload($filePath, basename($filePath));
        $sessionUrl = $session['session_url'];

        // Read file in chunks and upload them
        $chunkSize = 8 * 1024 * 1024; // 8MB chunks
        $fp = fopen($filePath, 'rb');
        if (!$fp) {
            throw new \Exception("Failed to open local backup file for upload.");
        }

        $uploadedBytes = 0;
        $remoteFileId = null;

        // If resuming, we can query Google for the current offset
        if ($job['bytes_uploaded'] > 0 && !empty($job['upload_session_id'])) {
            try {
                $resumeOffset = $providerInstance->resumeUpload($job['upload_session_id'], $totalBytes);
                if ($resumeOffset > 0) {
                    fseek($fp, $resumeOffset);
                    $uploadedBytes = $resumeOffset;
                    $sessionUrl = $job['upload_session_id'];
                }
            } catch (\Throwable $e) {
                // If resume fails, start a new session
                fseek($fp, 0);
            }
        }

        // Save session URL in DB
        $db->prepare("UPDATE cloud_replication_jobs SET upload_session_id = ? WHERE id = ?")->execute([$sessionUrl, $replicationJobId]);

        $speedStartTime = microtime(true);

        while (!feof($fp)) {
            $chunkData = fread($fp, $chunkSize);
            if ($chunkData === false || strlen($chunkData) === 0) {
                break;
            }

            $rangeStart = $uploadedBytes;
            $rangeEnd = $uploadedBytes + strlen($chunkData) - 1;

            $chunkResult = $providerInstance->uploadChunk($sessionUrl, $chunkData, $rangeStart, $rangeEnd, $totalBytes);

            if (!$chunkResult['success']) {
                throw new \Exception("Chunk upload failed at byte range {$rangeStart}-{$rangeEnd}");
            }

            $uploadedBytes += strlen($chunkData);

            // Update bytes_uploaded in database
            $db->prepare("UPDATE cloud_replication_jobs SET bytes_uploaded = ? WHERE id = ?")
               ->execute([$uploadedBytes, $replicationJobId]);

            if (!empty($chunkResult['completed'])) {
                $remoteFileId = $chunkResult['remote_file_id'];
                break;
            }
        }
        fclose($fp);

        $speedEndTime = microtime(true);
        $duration = $speedEndTime - $speedStartTime;
        $speed = $duration > 0 ? intval(($totalBytes / 1024) / $duration) : 1000; // KB/s

        if (!$remoteFileId) {
            throw new \Exception("Upload completed but no remote file ID was returned.");
        }

        // Log attempt
        $db->prepare("
            INSERT INTO cloud_replication_attempts (job_id, attempt_number, status, bytes_transferred)
            VALUES (?, ?, ?, ?)
        ")->execute([$replicationJobId, $attempts, 'completed', $totalBytes]);

        // Complete upload & verification
        $db->prepare("UPDATE cloud_replication_jobs SET status = 'verifying' WHERE id = ?")->execute([$replicationJobId]);
        
        $verified = $providerInstance->verifyUpload($remoteFileId, $filePath);
        if (!$verified) {
            throw new \Exception("Remote hash integrity check failed.");
        }

        $checksum = hash_file('sha256', $filePath);

        // Success updates
        $db->prepare("
            UPDATE cloud_replication_jobs 
            SET status = 'completed', completed_at = NOW(), bytes_uploaded = ?, remote_file_id = ?
            WHERE id = ?
        ")->execute([$totalBytes, $remoteFileId, $replicationJobId]);

        // Update system_backups details
        $db->prepare("
            UPDATE system_backups 
            SET cloud_provider = ?, cloud_status = 'uploaded', cloud_checksum = ?, upload_speed_kbps = ?
            WHERE id = ?
        ")->execute([$provider, $checksum, $speed, $backupId]);

        // Clean up encrypted temporary file
        if ($encryptJob && file_exists($filePath)) {
            unlink($filePath);
        }

        return true;
    }
}
