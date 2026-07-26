<?php
namespace App\Models;

use App\Core\Model;

class Role extends Model {
    public static function getAll(): array {
        return self::fetchAll("SELECT * FROM roles ORDER BY id ASC");
    }
}
