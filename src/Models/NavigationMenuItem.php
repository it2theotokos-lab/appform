<?php
namespace App\Models;

use App\Core\Model;

class NavigationMenuItem extends Model {
    public static function getItemsByMenuId(int $menuId): array {
        return self::getTreeByMenuId($menuId, false);
    }

    public static function getTreeByMenuId(int $menuId, bool $activeOnly = true): array {
        $sql = "SELECT * FROM navigation_menu_items WHERE menu_id = ?";
        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }
        $sql .= " ORDER BY sort_order ASC, id ASC";

        $allItems = self::fetchAll($sql, [$menuId]);

        $tree = [];
        self::buildTreeRecursive($allItems, null, 0, $tree);
        return $tree;
    }

    private static function buildTreeRecursive(array $items, ?int $parentId, int $depth, array &$tree) {
        foreach ($items as $item) {
            $itemParentId = $item['parent_id'] !== null ? (int)$item['parent_id'] : null;
            if ($itemParentId === $parentId) {
                $item['depth'] = $depth;
                $tree[] = $item;
                self::buildTreeRecursive($items, (int)$item['id'], $depth + 1, $tree);
            }
        }
    }
}
