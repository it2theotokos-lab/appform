<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class OrgUnit {

    /**
     * Return all organizational units with parent details.
     */
    public static function all(): array {
        $db = Database::getInstance();
        $stmt = $db->query("
            SELECT u.*, p.name as parent_name, p.type as parent_type 
            FROM org_units u 
            LEFT JOIN org_units p ON u.parent_id = p.id 
            ORDER BY u.name ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find a single unit by ID.
     */
    public static function find(int $id): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM org_units WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Return flat list of all potential parent units.
     */
    public static function getDepartments(): array {
        return self::all();
    }

    /**
     * Return all descendants IDs of a unit (to prevent circular references).
     */
    public static function getDescendantIds(int $unitId): array {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT id, parent_id FROM org_units");
        $all = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $childrenMap = [];
        foreach ($all as $u) {
            $pId = $u['parent_id'] !== null ? (int)$u['parent_id'] : 0;
            $childrenMap[$pId][] = (int)$u['id'];
        }

        $descendants = [];
        $queue = [$unitId];
        while (!empty($queue)) {
            $curr = array_shift($queue);
            if (isset($childrenMap[$curr])) {
                foreach ($childrenMap[$curr] as $childId) {
                    $descendants[] = $childId;
                    $queue[] = $childId;
                }
            }
        }
        return $descendants;
    }

    /**
     * Create a new unit under any optional parent (unlimited depth tree).
     */
    public static function create(string $name, string $type, ?int $parentId): array {
        $name = trim($name);
        if ($name === '') {
            return ['success' => false, 'message' => __('Unit name is required.')];
        }

        if ($parentId !== null && $parentId <= 0) {
            $parentId = null;
        }

        if ($parentId !== null) {
            $parent = self::find($parentId);
            if (!$parent) {
                return ['success' => false, 'message' => __('Selected parent unit does not exist.')];
            }
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("INSERT INTO org_units (name, type, parent_id) VALUES (?, ?, ?)");
        $stmt->execute([$name, $type, $parentId]);

        return [
            'success' => true,
            'id' => (int)$db->lastInsertId(),
            'message' => __('Organizational unit created successfully.')
        ];
    }

    /**
     * Update unit name and parent_id (supports moving nodes without cycle loops).
     */
    public static function update(int $id, string $name, ?int $parentId): array {
        $unit = self::find($id);
        if (!$unit) {
            return ['success' => false, 'message' => __('Organizational unit not found.')];
        }

        $name = trim($name);
        if ($name === '') {
            return ['success' => false, 'message' => __('Unit name is required.')];
        }

        if ($parentId !== null && $parentId <= 0) {
            $parentId = null;
        }

        // Circular reference check
        if ($parentId !== null) {
            if ($parentId === $id) {
                return ['success' => false, 'message' => __('A unit cannot be its own parent.')];
            }
            $parent = self::find($parentId);
            if (!$parent) {
                return ['success' => false, 'message' => __('Selected parent unit does not exist.')];
            }
            $descendants = self::getDescendantIds($id);
            if (in_array($parentId, $descendants, true)) {
                return ['success' => false, 'message' => __('Cannot move a unit under one of its own sub-units.')];
            }
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE org_units SET name = ?, parent_id = ? WHERE id = ?");
        $stmt->execute([$name, $parentId, $id]);

        return ['success' => true, 'message' => __('Organizational unit updated successfully.')];
    }

    /**
     * Delete an organizational unit (guarded by child and user checks).
     */
    public static function delete(int $id): array {
        $unit = self::find($id);
        if (!$unit) {
            return ['success' => false, 'message' => __('Organizational unit not found.')];
        }

        $db = Database::getInstance();

        // Check for child units
        $childStmt = $db->prepare("SELECT COUNT(*) FROM org_units WHERE parent_id = ?");
        $childStmt->execute([$id]);
        if ((int)$childStmt->fetchColumn() > 0) {
            return [
                'success' => false,
                'message' => __('Cannot delete unit: It contains sub-departments or teams. Move or delete child units first.')
            ];
        }

        // Check for assigned users
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

    /**
     * Build and return full recursive tree with unlimited depth.
     */
    public static function getTree(): array {
        $db = Database::getInstance();
        
        // Fetch all org units
        $stmt = $db->query("SELECT * FROM org_units ORDER BY name ASC");
        $allUnits = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch users per org unit
        $usersStmt = $db->query("SELECT id, username, full_name, email, org_unit_id FROM users WHERE org_unit_id IS NOT NULL ORDER BY full_name ASC");
        $users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

        $usersByUnit = [];
        foreach ($users as $u) {
            $unitId = (int)$u['org_unit_id'];
            $usersByUnit[$unitId][] = $u;
        }

        // Build associative array indexed by unit ID
        $nodes = [];
        foreach ($allUnits as $unit) {
            $id = (int)$unit['id'];
            $unit['users'] = $usersByUnit[$id] ?? [];
            $unit['children'] = [];
            $nodes[$id] = $unit;
        }

        // Construct tree hierarchy
        $tree = [];
        foreach ($nodes as $id => &$node) {
            $parentId = $node['parent_id'] !== null ? (int)$node['parent_id'] : null;
            if ($parentId === null || !isset($nodes[$parentId])) {
                $tree[] = &$node;
            } else {
                $nodes[$parentId]['children'][] = &$node;
            }
        }
        unset($node);

        return $tree;
    }
}
