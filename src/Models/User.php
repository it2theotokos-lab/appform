<?php
namespace App\Models;

use App\Core\Model;

class User extends Model {
    public static function findById(int $id) {
        return self::fetch("SELECT * FROM users WHERE id = ?", [$id]);
    }

    public static function findByUsername(string $username) {
        return self::fetch("SELECT * FROM users WHERE username = ?", [$username]);
    }

    public static function getAll(): array {
        return self::fetchAll("SELECT u.*, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id ORDER BY u.id ASC");
    }
}
