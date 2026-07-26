<?php
namespace App\Services;

use App\Core\Database;
use PDO;

class OrganizationalScopeService {
    /**
     * Determines all user IDs in the reporting hierarchy below a manager.
     */
    public static function getSubordinateIds(int $managerId): array {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT id, manager_id FROM users");
        $allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $subordinates = [];
        self::findSubordinatesRecursive($managerId, $allUsers, $subordinates);

        return array_unique($subordinates);
    }

    private static function findSubordinatesRecursive(int $parentId, array $users, array &$subordinates) {
        foreach ($users as $u) {
            if ($u['manager_id'] !== null && (int)$u['manager_id'] === $parentId) {
                if (!in_array((int)$u['id'], $subordinates)) {
                    $subordinates[] = (int)$u['id'];
                    self::findSubordinatesRecursive((int)$u['id'], $users, $subordinates);
                }
            }
        }
    }

    /**
     * Confirms if a user can report to another manager without loops.
     */
    public static function detectCircularHierarchy(int $userId, int $proposedManagerId): bool {
        if ($userId === $proposedManagerId) {
            return true;
        }

        $db = Database::getInstance();
        $stmt = $db->query("SELECT id, manager_id FROM users");
        $allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $currentManagerId = $proposedManagerId;
        $visited = [$userId];

        while ($currentManagerId !== null) {
            if (in_array($currentManagerId, $visited)) {
                return true; // Loop detected
            }
            $visited[] = $currentManagerId;

            // Find parent manager
            $parent = null;
            foreach ($allUsers as $u) {
                if ((int)$u['id'] === $currentManagerId) {
                    $parent = $u['manager_id'] !== null ? (int)$u['manager_id'] : null;
                    break;
                }
            }
            $currentManagerId = $parent;
        }

        return false;
    }
}
