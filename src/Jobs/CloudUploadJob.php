<?php
namespace App\Jobs;

class CloudUploadJob extends AbstractJob {
    public function handle(): bool {
        $replicationJobId = (int)($this->payload['replication_job_id'] ?? 0);
        $backupId = (int)($this->payload['backup_id'] ?? 0);
        $provider = $this->payload['provider'] ?? 'googledrive';

        if ($replicationJobId <= 0) {
            throw new \Exception("Invalid or missing replication_job_id in payload.");
        }

        $db = \App\Core\Database::getInstance();

        // Log audit event: cloud.upload.started
        $this->logAudit('cloud.upload.started', 'cloud_replication_jobs', $replicationJobId, [
            'backup_id' => $backupId,
            'provider' => $provider
        ]);

        try {
            // Update cloud_replication_jobs status to preparing, started_at = NOW()
            $db->prepare("UPDATE cloud_replication_jobs SET status = 'preparing', started_at = NOW() WHERE id = ?")->execute([$replicationJobId]);
            $db->prepare("UPDATE system_backups SET cloud_status = 'uploading', cloud_provider = ? WHERE id = ?")->execute([$provider, $backupId]);

            // Execute specific replication job
            $success = \App\Services\CloudReplicationService::processSpecificJob($replicationJobId);

            if ($success) {
                // Log audit event: cloud.upload.completed
                $this->logAudit('cloud.upload.completed', 'cloud_replication_jobs', $replicationJobId, [
                    'backup_id' => $backupId,
                    'provider' => $provider
                ]);
                return true;
            } else {
                throw new \Exception("Cloud upload process failed.");
            }
        } catch (\Throwable $e) {
            // Log audit event: cloud.upload.failed
            $this->logAudit('cloud.upload.failed', 'cloud_replication_jobs', $replicationJobId, [
                'backup_id' => $backupId,
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);

            // Update statuses to failed
            $db->prepare("UPDATE cloud_replication_jobs SET status = 'failed', last_error = ?, completed_at = NOW() WHERE id = ?")
               ->execute([$e->getMessage(), $replicationJobId]);
            $db->prepare("UPDATE system_backups SET cloud_status = 'failed', cloud_error = ? WHERE id = ?")
               ->execute([$e->getMessage(), $backupId]);

            throw $e; // Propagate exception so JobWorker registers it as failed
        }
    }

    protected function logAudit(string $action, string $entityType, ?int $entityId, array $metadata) {
        try {
            $db = \App\Core\Database::getInstance();
            $stmt = $db->prepare("
                INSERT INTO audit_logs (user_id, action, entity_type, entity_id, metadata_json, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                \App\Core\Auth::id() ?: 1,
                $action,
                $entityType,
                $entityId,
                json_encode($metadata, JSON_UNESCAPED_UNICODE),
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                $_SERVER['HTTP_USER_AGENT'] ?? 'CLI/Worker'
            ]);
        } catch (\Exception $e) {}
    }
}
