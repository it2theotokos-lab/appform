<?php
namespace App\Models;

use App\Core\Model;

class Notification extends Model {
    public static function findById(int $id) {
        return self::fetch("SELECT * FROM notifications WHERE id = ?", [$id]);
    }
}
