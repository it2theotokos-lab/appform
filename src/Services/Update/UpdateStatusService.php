<?php
namespace App\Services\Update;

use App\Core\Database;
use App\Core\Auth;
use PDO;

class UpdateStatusService {
    
    public static function createUpdateRecord(array $data): int {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO application_updates (
                release_version, build_number, release_channel, previous_version, previous_build,
                status, provider, started_by, started_at, heartbeat_at
            ) VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $data['release_version'],
            $data['build_number'],
            $data['release_channel'],
            $data['previous_version'],
            $data['previous_build'],
            $data['provider'],
            $data['started_by'] ?? Auth::id()
        ]);
        return (int)$db->lastInsertId();
    }

    public static function updateState(int $updateId, string $newState, ?string $stepKey = null) {
        $db = Database::getInstance();
        
        // Fetch current status to validate transition
        $stmtCurr = $db->prepare("SELECT status FROM application_updates WHERE id = ?");
        $stmtCurr->execute([$updateId]);
        $currentStatus = $stmtCurr->fetchColumn();

        if ($currentStatus) {
            UpdateStateMachine::validateTransition($currentStatus, $newState);
        }

        $stmt = $db->prepare("
            UPDATE application_updates 
            SET status = ?, current_step = ?, heartbeat_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$newState, $stepKey, $updateId]);
        self::appendLog($updateId, $stepKey ?? 'transition', 'info', "State transition: {$currentStatus} -> {$newState}");
    }

    public static function updateProgress(int $updateId, int $progressPercent) {
        if ($progressPercent < 0 || $progressPercent > 100) {
            throw new \InvalidArgumentException("Progress percent must be between 0 and 100.");
        }
        $db = Database::getInstance();
        $stmt = $db->prepare("
            UPDATE application_updates 
            SET progress_percent = ?, heartbeat_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$progressPercent, $updateId]);
    }

    public static function heartbeat(int $updateId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            UPDATE application_updates 
            SET heartbeat_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$updateId]);
    }

    public static function appendLog(int $updateId, string $stepKey, string $level, string $message, ?array $context = null) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO application_update_logs (update_id, step_key, level, message, context_json)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $updateId,
            $stepKey,
            $level,
            $message,
            $context ? json_encode($context, JSON_UNESCAPED_UNICODE) : null
        ]);
    }

    public static function appendWarning(int $updateId, string $stepKey, string $message, ?array $context = null) {
        self::appendLog($updateId, $stepKey, 'warning', $message, $context);
    }

    public static function markFailure(int $updateId, string $errorCode, string $errorMessage) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            UPDATE application_updates 
            SET status = 'failed', failed_at = NOW(), error_code = ?, error_message = ?, heartbeat_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$errorCode, $errorMessage, $updateId]);
        self::appendLog($updateId, 'failure', 'error', "Update failed: [{$errorCode}] {$errorMessage}");
    }

    public static function beginRollback(int $updateId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            UPDATE application_updates 
            SET rollback_status = 'rolling_back', rollback_started_at = NOW(), heartbeat_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$updateId]);
        self::appendLog($updateId, 'rollback', 'info', "Starting rollback execution.");
    }

    public static function markRollbackResult(int $updateId, bool $success, ?string $errorMessage = null) {
        $db = Database::getInstance();
        $status = $success ? 'rolled_back' : 'rollback_failed';
        
        $stmt = $db->prepare("
            UPDATE application_updates 
            SET rollback_status = ?, rollback_completed_at = NOW(), error_message = ?, heartbeat_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$status, $errorMessage, $updateId]);
        self::appendLog($updateId, 'rollback', $success ? 'info' : 'error', "Rollback completed with status: {$status}");
    }

    public static function getActiveUpdate(): ?array {
        $db = Database::getInstance();
        // Only return records that represent a truly in-progress update.
        // Terminal states (completed, failed, rolled_back, rollback_failed) must
        // never be returned here — they cause stale progress panels on page load.
        $stmt = $db->prepare("
            SELECT * FROM application_updates
            WHERE status IN (
                'pending',
                'waiting_for_lock',
                'maintenance_enabled',
                'backing_up_files',
                'backing_up_database',
                'verifying_package',
                'extracting',
                'running_migrations',
                'validating_application'
            )
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute();
        $active = $stmt->fetch(PDO::FETCH_ASSOC);
        return $active ?: null;
    }

    public static function getUpdateHistory(int $limit = 20): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM application_updates ORDER BY id DESC LIMIT ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getLogs(int $updateId): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM application_update_logs WHERE update_id = ? ORDER BY id ASC");
        $stmt->execute([$updateId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
