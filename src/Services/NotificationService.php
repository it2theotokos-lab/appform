<?php
namespace App\Services;

use App\Core\Database;
use PDO;

class NotificationService {
    public static function notify(int $userId, string $type, string $title, string $message, ?string $linkUrl = null, ?int $senderUserId = null): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO notifications (user_id, sender_user_id, type, title, message, link_url)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$userId, $senderUserId, $type, $title, $message, $linkUrl]);
    }

    public static function getUnreadCount(int $userId): int {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return (int)($row['count'] ?? 0);
    }

    public static function getNotifications(int $userId, int $page = 1, int $perPage = 20): array {
        $db = Database::getInstance();
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;
        $stmt = $db->prepare("
            SELECT n.*, COALESCE(sender.full_name, sender.username) AS sender_name
            FROM notifications n
            LEFT JOIN users sender ON sender.id = n.sender_user_id
            WHERE n.user_id = :user_id
            ORDER BY n.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        // Use named parameters consistently. MySQL PDO rejects queries that mix
        // positional (`?`) and named placeholders, which would make the inbox 500.
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getNotificationCount(int $userId): int {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    public static function markAsRead(int $notificationId, int $userId): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        return $stmt->execute([$notificationId, $userId]);
    }

    public static function markAllAsRead(int $userId): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        return $stmt->execute([$userId]);
    }

    /** A user may delete only notifications delivered to that same user. */
    public static function deleteNotification(int $notificationId, int $userId): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
        return $stmt->execute([$notificationId, $userId]);
    }

    /** Clear a user's own notification inbox; never affects other users. */
    public static function deleteAll(int $userId): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM notifications WHERE user_id = ?");
        return $stmt->execute([$userId]);
    }
}
