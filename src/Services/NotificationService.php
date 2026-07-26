<?php
namespace App\Services;

use App\Core\Database;
use PDO;

class NotificationService {
    public static function notify(int $userId, string $type, string $title, string $message, ?string $linkUrl = null): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO notifications (user_id, type, title, message, link_url)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$userId, $type, $title, $message, $linkUrl]);
    }

    public static function getUnreadCount(int $userId): int {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return (int)($row['count'] ?? 0);
    }

    public static function getNotifications(int $userId): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
}
