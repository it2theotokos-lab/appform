<?php
namespace App\Services;

use App\Core\Database;
use PDO;

class JobWorker {
    private $hostname;
    private $pid;
    private $queues;

    public function __construct(array $queues = ['default']) {
        $this->hostname = gethostname() ?: 'localhost';
        $this->pid = getmypid();
        $this->queues = $queues;
    }

    public function work(): bool {
        $db = Database::getInstance();
        $this->registerWorker();

        // 1. Fetch next pending job matching worker queues and sorted by priority (critical, high, normal, low)
        $queuePlaceholders = implode(',', array_fill(0, count($this->queues), '?'));
        
        $sql = "
            SELECT * FROM jobs 
            WHERE status IN ('pending', 'retrying') 
              AND queue IN ($queuePlaceholders)
              AND (scheduled_at IS NULL OR scheduled_at <= NOW())
            ORDER BY 
              CASE priority 
                WHEN 'critical' THEN 1 
                WHEN 'high' THEN 2 
                WHEN 'normal' THEN 3 
                WHEN 'low' THEN 4 
                ELSE 5 
              END ASC, 
              id ASC 
            LIMIT 1
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute($this->queues);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$job) {
            $this->updateStatus('idle');
            return false;
        }

        $jobId = (int)$job['id'];
        $jobClass = $job['type'];
        
        // 2. Lock job via DB lock to avoid duplicate execution
        $db->beginTransaction();
        try {
            $stmtLock = $db->prepare("SELECT status FROM jobs WHERE id = ? FOR UPDATE");
            $stmtLock->execute([$jobId]);
            $currentStatus = $stmtLock->fetchColumn();

            if ($currentStatus !== 'pending' && $currentStatus !== 'retrying') {
                $db->rollBack();
                return false;
            }

            // Update status to running
            $db->prepare("UPDATE jobs SET status = 'running', started_at = NOW(), attempts = attempts + 1 WHERE id = ?")->execute([$jobId]);
            $db->commit();
        } catch (\Exception $e) {
            $db->rollBack();
            return false;
        }

        $this->updateStatus('working', $jobId);
        $this->logAudit('job.started', 'jobs', $jobId, ['class' => $jobClass]);

        // 3. Execute Job Class
        $success = false;
        $errorMsg = null;
        try {
            if (class_exists($jobClass)) {
                $payload = json_decode($job['payload'], true) ?: [];
                $jobInstance = new $jobClass($payload, $jobId);
                
                $success = $jobInstance->handle();
            } else {
                throw new \Exception("Job class {$jobClass} not found.");
            }
        } catch (\Throwable $e) {
            $success = false;
            $errorMsg = $e->getMessage();
        }

        // 4. Update Job Status after run completes
        if ($success) {
            $db->prepare("UPDATE jobs SET status = 'completed', completed_at = NOW(), progress = 100 WHERE id = ?")->execute([$jobId]);
            $this->logAudit('job.completed', 'jobs', $jobId, []);
        } else {
            $attempts = (int)$job['attempts'] + 1;
            $maxAttempts = (int)$job['max_attempts'];

            if ($attempts < $maxAttempts) {
                // Retry queue
                $db->prepare("UPDATE jobs SET status = 'retrying', last_error = ? WHERE id = ?")->execute([$errorMsg, $jobId]);
                $this->logAudit('job.retried', 'jobs', $jobId, ['attempt' => $attempts]);
            } else {
                $db->prepare("UPDATE jobs SET status = 'failed', failed_at = NOW(), last_error = ? WHERE id = ?")->execute([$errorMsg, $jobId]);
                $this->logAudit('job.failed', 'jobs', $jobId, ['error' => $errorMsg]);
            }
        }

        $this->updateStatus('idle');
        return true;
    }

    private function registerWorker() {
        $db = Database::getInstance();
        $qList = implode(',', $this->queues);
        
        $db->prepare("
            INSERT INTO job_workers (hostname, pid, queue_list, status, started_at)
            VALUES (?, ?, ?, 'idle', NOW())
            ON DUPLICATE KEY UPDATE status = 'idle', heartbeat = NOW()
        ")->execute([$this->hostname, $this->pid, $qList]);
    }

    private function updateStatus(string $status, ?int $jobId = null) {
        $db = Database::getInstance();
        $mem = memory_get_usage();
        
        $db->prepare("
            UPDATE job_workers 
            SET status = ?, current_job_id = ?, memory_usage = ?, heartbeat = NOW()
            WHERE hostname = ? AND pid = ?
        ")->execute([$status, $jobId, $mem, $this->hostname, $this->pid]);
    }

    private function logAudit(string $action, string $entityType, ?int $entityId, array $metadata) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                INSERT INTO audit_logs (user_id, action, entity_type, entity_id, metadata_json, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                1, // CLI System account
                $action,
                $entityType,
                $entityId,
                json_encode($metadata, JSON_UNESCAPED_UNICODE),
                '127.0.0.1',
                'CLI/Worker'
            ]);
        } catch (\Exception $e) {}
    }
}
