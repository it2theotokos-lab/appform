<?php
namespace App\Models;

use App\Core\Model;

class Permission extends Model {
    public static function getAll(): array {
        return self::fetchAll("SELECT * FROM permissions ORDER BY slug ASC");
    }
}
