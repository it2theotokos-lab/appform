<?php
namespace App\Services\Update;

use App\Core\Database;
use App\Core\Auth;
use PDO;

class UpdateLockService {
    private const STALE_TIMEOUT_SECONDS = 300; // 5 minutes

    /**
     * Checks if there is an active exclusive update lock.
     * Returns the active update row array if locked, null otherwise.
     */
    public static function getActiveLock(): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT * FROM application_updates 
            WHERE status NOT IN ('completed', 'rolled_back')
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute();
        $active = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$active) {
            return null;
        }

        // Check if the lock is stale
        $heartbeat = strtotime($active['heartbeat_at'] ?? $active['created_at']);
        if ((time() - $heartbeat) > self::STALE_TIMEOUT_SECONDS) {
            // Lock is stale
            return null;
        }

        return $active;
    }

    /**
     * Attempts to acquire the exclusive update lock.
     * Throws exception if locked.
     */
    public static function acquireLock(int $updateId): bool {
        $activeLock = self::getActiveLock();
        if ($activeLock && (int)$activeLock['id'] !== $updateId) {
            throw new \Exception("Υπάρχει ήδη άλλη ενεργή διαδικασία αναβάθμισης (ID: {$activeLock['id']}) σε εξέλιξη.");
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("
            UPDATE application_updates 
            SET heartbeat_at = NOW(), updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$updateId]);
        return true;
    }

    /**
     * Safe manual recovery / release of a stale or active lock only by administrators.
     */
    public static function forceRelease(int $updateId, int $adminUserId): bool {
        $db = Database::getInstance();
        
        // Audit log mapping
        $stmtAudit = $db->prepare("
            INSERT INTO audit_logs (user_id, action, entity_type, entity_id, metadata_json)
            VALUES (?, 'update.lock.force_released', 'application_updates', ?, ?)
        ");
        $stmtAudit->execute([
            $adminUserId,
            $updateId,
            json_encode(['action' => 'force_release_lock'], JSON_UNESCAPED_UNICODE)
        ]);

        $stmt = $db->prepare("
            UPDATE application_updates 
            SET status = 'failed', failed_at = NOW(), error_message = 'Η διαδικασία τερματίστηκε βίαια από τον διαχειριστή.'
            WHERE id = ?
        ");
        return $stmt->execute([$updateId]);
    }
}
