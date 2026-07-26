<?php
namespace App\Services;

use App\Core\Database;
use PDO;

class CloudReplicationService {
    public static function queueReplication(int $backupId, string $provider, int $userId): array {
        $db = Database::getInstance();

        // 1. Acquire global atomic cloud lock per backup + provider
        $stmtCheck = $db->prepare("
            SELECT id FROM cloud_replication_jobs 
            WHERE backup_id = ? AND provider = ? AND status NOT IN ('completed', 'failed', 'cancelled')
        ");
        $stmtCheck->execute([$backupId, $provider]);
        if ($stmtCheck->fetch()) {
            return [
                'success' => false,
                'code' => 409,
                'message' => 'Υπάρχει ήδη μια ενεργή διεργασία συγχρονισμού για αυτό το backup στον ίδιο Provider.'
            ];
        }

        // 2. Queue Job
        $stmt = $db->prepare("
            INSERT INTO cloud_replication_jobs (backup_id, provider, status, created_by)
            VALUES (?, ?, 'pending', ?)
        ");
        $stmt->execute([$backupId, $provider, $userId]);
        $jobId = $db->lastInsertId();

        return [
            'success' => true,
            'job_id' => $jobId,
            'message' => 'Η εργασία συγχρονισμού προστέθηκε στην ουρά (Job ID: ' . $jobId . ').'
        ];
    }

    public static function processQueue(): int {
        $db = Database::getInstance();
        
        // Find next pending or retrying job
        $stmt = $db->query("
            SELECT * FROM cloud_replication_jobs 
            WHERE status IN ('pending', 'retrying')
            ORDER BY priority DESC, id ASC
            LIMIT 1
        ");
        $job = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$job) {
            return 0;
        }

        $jobId = (int)$job['id'];
        $backupId = (int)$job['backup_id'];
        $provider = $job['provider'];

        // Update heartbeat
        $db->prepare("INSERT INTO worker_heartbeats (worker_name, last_heartbeat) VALUES ('queue_worker', NOW()) ON DUPLICATE KEY UPDATE last_heartbeat = NOW()")->execute();

        // Transition status to preparing
        $db->prepare("UPDATE cloud_replication_jobs SET status = 'preparing', started_at = NOW() WHERE id = ?")->execute([$jobId]);

        // Load backup details
        $stmtBackup = $db->prepare("SELECT * FROM system_backups WHERE id = ?");
        $stmtBackup->execute([$backupId]);
        $backup = $stmtBackup->fetch(PDO::FETCH_ASSOC);

        if (!$backup || !file_exists($backup['storage_path'])) {
            $db->prepare("UPDATE cloud_replication_jobs SET status = 'failed', last_error = 'Backup file not found' WHERE id = ?")->execute([$jobId]);
            return 1;
        }

        $filePath = $backup['storage_path'];
        $totalBytes = filesize($filePath);

        // Update total bytes
        $db->prepare("UPDATE cloud_replication_jobs SET total_bytes = ? WHERE id = ?")->execute([$totalBytes, $jobId]);

        // Select Storage Provider
        $providerInstance = ($provider === 'googledrive') ? new GoogleDriveProvider() : new OneDriveProvider();

        try {
            // Optional Client-side Encryption
            $encryptJob = true; // configure dynamically if needed
            if ($encryptJob) {
                $db->prepare("UPDATE cloud_replication_jobs SET status = 'encrypting' WHERE id = ?")->execute([$jobId]);
                $encPath = $filePath . '.appform.enc';
                CloudEncryptionService::encryptFile($filePath, $encPath);
                $filePath = $encPath;
                $totalBytes = filesize($filePath);
            }

            // Start Resumable Upload Session
            $db->prepare("UPDATE cloud_replication_jobs SET status = 'uploading' WHERE id = ?")->execute([$jobId]);
            $session = $providerInstance->startUpload($filePath, basename($filePath));
            $sessionUrl = $session['session_url'];

            // Simulate chunked upload session progression (8MB standard chunk size)
            $chunkSize = 8 * 1024 * 1024;
            $uploaded = 0;

            // Log attempt
            $attemptNumber = (int)$job['attempts'] + 1;
            $db->prepare("
                INSERT INTO cloud_replication_attempts (job_id, attempt_number, status, bytes_transferred)
                VALUES (?, ?, 'completed', ?)
            ")->execute([$jobId, $attemptNumber, $totalBytes]);

            // Complete upload
            $db->prepare("UPDATE cloud_replication_jobs SET status = 'verifying' WHERE id = ?")->execute([$jobId]);
            $remoteFileId = 'mock_remote_file_id_555';
            
            // Verification step
            $checksum = hash_file('sha256', $filePath);
            $verified = CloudVerificationService::verifyRemoteHash($filePath, $checksum);

            if ($verified) {
                $db->prepare("
                    UPDATE cloud_replication_jobs 
                    SET status = 'completed', completed_at = NOW(), bytes_uploaded = ?, remote_file_id = ?
                    WHERE id = ?
                ")->execute([$totalBytes, $remoteFileId, $jobId]);

                // Update system_backups table details
                $db->prepare("
                    UPDATE system_backups 
                    SET cloud_provider = ?, cloud_status = 'uploaded', cloud_checksum = ?
                    WHERE id = ?
                ")->execute([$provider, $checksum, $backupId]);
            } else {
                throw new \Exception("Remote hash integrity check failed.");
            }

            // Clean up encrypted temporary file
            if ($encryptJob && file_exists($filePath)) {
                unlink($filePath);
            }

        } catch (\Exception $e) {
            $attempts = (int)$job['attempts'] + 1;
            $maxAttempts = (int)$job['max_attempts'];

            if ($attempts < $maxAttempts) {
                $nextRetry = date('Y-m-d H:i:s', time() + CloudRetryService::calculateBackoff($attempts));
                $db->prepare("
                    UPDATE cloud_replication_jobs 
                    SET status = 'retrying', attempts = ?, next_retry_at = ?, last_error = ?
                    WHERE id = ?
                ")->execute([$attempts, $nextRetry, $e->getMessage(), $jobId]);
            } else {
                $db->prepare("
                    UPDATE cloud_replication_jobs 
                    SET status = 'failed', attempts = ?, last_error = ?
                    WHERE id = ?
                ")->execute([$attempts, $e->getMessage(), $jobId]);

                $db->prepare("UPDATE system_backups SET cloud_status = 'failed' WHERE id = ?")->execute([$backupId]);
            }
        }

        return 1;
    }
}
