<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class OrgUnit {
    public static function all(): array {
        $db = Database::getInstance();
        $stmt = $db->query("
            SELECT u.*, p.name as parent_name, p.type as parent_type 
            FROM org_units u 
            LEFT JOIN org_units p ON u.parent_id = p.id 
            ORDER BY u.type ASC, u.name ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(int $id): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM org_units WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function getDepartments(): array {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM org_units WHERE type = 'department' ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create(string $name, string $type, ?int $parentId): array {
        // Validate unit type
        if (!in_array($type, ['department', 'subdepartment', 'team'], true)) {
            return ['success' => false, 'message' => __('Invalid unit type.')];
        }

        // Validate parent rules
        if ($type === 'department') {
            if ($parentId !== null) {
                return ['success' => false, 'message' => __('A Department cannot have a parent unit.')];
            }
        } else {
            // subdepartment or team
            if (!$parentId) {
                return ['success' => false, 'message' => __('Sub-departments and Teams must belong to a Department.')];
            }
            $parent = self::find($parentId);
            if (!$parent) {
                return ['success' => false, 'message' => __('Selected parent department does not exist.')];
            }
            if ($parent['type'] !== 'department') {
                return ['success' => false, 'message' => __('Sub-departments and Teams can only be created directly under a Department.')];
            }
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("INSERT INTO org_units (name, type, parent_id) VALUES (?, ?, ?)");
        $stmt->execute([trim($name), $type, $parentId]);

        return [
            'success' => true,
            'id' => (int)$db->lastInsertId(),
            'message' => __('Organizational unit created successfully.')
        ];
    }

    public static function update(int $id, string $name, ?int $parentId): array {
        $unit = self::find($id);
        if (!$unit) {
            return ['success' => false, 'message' => __('Organizational unit not found.')];
        }

        $type = $unit['type'];

        if ($type === 'department') {
            $parentId = null;
        } else {
            if (!$parentId) {
                return ['success' => false, 'message' => __('Sub-departments and Teams must belong to a Department.')];
            }
            $parent = self::find($parentId);
            if (!$parent || $parent['type'] !== 'department') {
                return ['success' => false, 'message' => __('Selected parent unit must be a valid Department.')];
            }
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE org_units SET name = ?, parent_id = ? WHERE id = ?");
        $stmt->execute([trim($name), $parentId, $id]);

        return ['success' => true, 'message' => __('Organizational unit updated successfully.')];
    }

    public static function delete(int $id): array {
        $unit = self::find($id);
        if (!$unit) {
            return ['success' => false, 'message' => __('Organizational unit not found.')];
        }

        $db = Database::getInstance();

        // Safety check 1: Check for child units
        $childStmt = $db->prepare("SELECT COUNT(*) FROM org_units WHERE parent_id = ?");
        $childStmt->execute([$id]);
        if ((int)$childStmt->fetchColumn() > 0) {
            return [
                'success' => false,
                'message' => __('Cannot delete unit: It contains sub-departments or teams. Move or delete child units first.')
            ];
        }

        // Safety check 2: Check for assigned users
        $userStmt = $db->prepare("SELECT COUNT(*) FROM users WHERE org_unit_id = ?");
        $userStmt->execute([$id]);
        if ((int)$userStmt->fetchColumn() > 0) {
            return [
                'success' => false,
                'message' => __('Cannot delete unit: It contains assigned users. Reassign or remove users first.')
            ];
        }

        $deleteStmt = $db->prepare("DELETE FROM org_units WHERE id = ?");
        $deleteStmt->execute([$id]);

        return ['success' => true, 'message' => __('Organizational unit deleted successfully.')];
    }

    public static function getTree(): array {
        $db = Database::getInstance();
        
        // Fetch all departments
        $deptStmt = $db->query("SELECT * FROM org_units WHERE type = 'department' ORDER BY name ASC");
        $departments = $deptStmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch all subdepartments and teams with assigned user counts & members
        $tree = [];
        foreach ($departments as $dept) {
            $deptId = (int)$dept['id'];

            // Fetch users directly in department
            $deptUsersStmt = $db->prepare("SELECT id, username, full_name, email FROM users WHERE org_unit_id = ? ORDER BY full_name ASC");
            $deptUsersStmt->execute([$deptId]);
            $dept['users'] = $deptUsersStmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch child units (subdepartments & teams)
            $childStmt = $db->prepare("SELECT * FROM org_units WHERE parent_id = ? ORDER BY type ASC, name ASC");
            $childStmt->execute([$deptId]);
            $children = $childStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($children as &$child) {
                $childId = (int)$child['id'];
                $childUsersStmt = $db->prepare("SELECT id, username, full_name, email FROM users WHERE org_unit_id = ? ORDER BY full_name ASC");
                $childUsersStmt->execute([$childId]);
                $child['users'] = $childUsersStmt->fetchAll(PDO::FETCH_ASSOC);
            }
            unset($child);

            $dept['children'] = $children;
            $tree[] = $dept;
        }

        return $tree;
    }
}
