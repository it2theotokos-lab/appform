<?php
namespace App\Services;

use App\Core\Database;
use PDO;

class JobQueueService {
    public static function dispatch(string $jobClass, array $payload = [], string $queue = 'default', string $priority = 'normal', int $maxAttempts = 3): int {
        $db = Database::getInstance();
        $uuid = bin2hex(random_bytes(16));
        
        // Redact or encrypt sensitive parameters in payload (e.g. passwords, client_secret)
        $redactedPayload = self::redactSensitiveData($payload);

        $stmt = $db->prepare("
            INSERT INTO jobs (uuid, type, queue, priority, status, payload, max_attempts)
            VALUES (?, ?, ?, ?, 'pending', ?, ?)
        ");
        $stmt->execute([
            $uuid,
            $jobClass,
            $queue,
            $priority,
            json_encode($redactedPayload, JSON_UNESCAPED_UNICODE),
            $maxAttempts
        ]);
        $jobId = $db->lastInsertId();

        self::logAudit('job.created', 'jobs', $jobId, ['uuid' => $uuid, 'type' => $jobClass]);

        // Synchronous inline execution for immediate delivery
        try {
            if (class_exists($jobClass)) {
                $job = new $jobClass($payload, $jobId);
                $db->prepare("UPDATE jobs SET status = 'processing', attempts = 1, started_at = NOW() WHERE id = ?")->execute([$jobId]);
                $success = $job->handle();
                if ($success) {
                    $db->prepare("UPDATE jobs SET status = 'completed', completed_at = NOW() WHERE id = ?")->execute([$jobId]);
                } else {
                    $db->prepare("UPDATE jobs SET status = 'failed', failed_at = NOW(), last_error = 'Job handle returned false' WHERE id = ?")->execute([$jobId]);
                }
            }
        } catch (\Throwable $e) {
            $db->prepare("UPDATE jobs SET status = 'failed', failed_at = NOW(), last_error = ? WHERE id = ?")->execute([$e->getMessage(), $jobId]);
        }

        return $jobId;
    }

    private static function redactSensitiveData(array $payload): array {
        $sensitiveKeys = ['password', 'client_secret', 'token', 'key', 'connection_string'];
        foreach ($payload as $key => &$value) {
            if (in_array(strtolower($key), $sensitiveKeys)) {
                $value = '********';
            } elseif (is_array($value)) {
                $value = self::redactSensitiveData($value);
            }
        }
        return $payload;
    }

    private static function logAudit(string $action, string $entityType, ?int $entityId, array $metadata) {
        try {
            $db = Database::getInstance();
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
