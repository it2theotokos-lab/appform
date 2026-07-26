<?php
namespace App\Services;

use App\Core\Database;
use PDO;

class RestoreRollbackService {
    public static function executeRollback(int $emergencyBackupId, string $type) {
        $db = Database::getInstance();
        
        $stmt = $db->prepare("SELECT * FROM system_backups WHERE id = ?");
        $stmt->execute([$emergencyBackupId]);
        $backup = $stmt->fetch();

        if (!$backup || !file_exists($backup['storage_path'])) {
            return false;
        }

        $filePath = $backup['storage_path'];

        if ($type === 'database') {
            // Restore Emergency DB backup
            $sData = '';
            $zd = gzopen($filePath, "r");
            while ($line = gzgets($zd, 4096)) {
                $sData .= $line;
            }
            gzclose($zd);

            $db->exec($sData);
        } else {
            // Restore Emergency files backup
            $zip = new \ZipArchive();
            if ($zip->open($filePath) === TRUE) {
                $zip->extractTo('storage');
                $zip->close();
            }
        }
        
        return true;
    }
}
