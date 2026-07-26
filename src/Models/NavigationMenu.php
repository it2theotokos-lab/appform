<?php
namespace App\Models;

use App\Core\Model;

class NavigationMenu extends Model {
    public static function findById(int $id) {
        return self::fetch("SELECT * FROM navigation_menus WHERE id = ?", [$id]);
    }

    public static function findByRoleId(int $roleId) {
        return self::fetch("SELECT * FROM navigation_menus WHERE role_id = ?", [$roleId]);
    }

    public static function getAll(): array {
        return self::fetchAll("SELECT * FROM navigation_menus ORDER BY id ASC");
    }
}
