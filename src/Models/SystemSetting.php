<?php
namespace App\Models;

use App\Core\Model;

class SystemSetting extends Model {
    public static function getVal(string $key, $default = null) {
        $row = self::fetch("SELECT setting_value FROM system_settings WHERE setting_key = ?", [$key]);
        return $row ? $row['setting_value'] : $default;
    }

    public static function getAll(): array {
        return self::fetchAll("SELECT * FROM system_settings ORDER BY setting_key ASC");
    }
}
