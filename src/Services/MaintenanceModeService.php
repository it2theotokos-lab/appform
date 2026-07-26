<?php
namespace App\Services;

use App\Core\Database;

class MaintenanceModeService {
    public static function isEnabled(): bool {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'maintenance_mode'");
        return (int)$stmt->fetchColumn() === 1;
    }

    public static function enable() {
        $db = Database::getInstance();
        $db->prepare("UPDATE system_settings SET setting_value = '1' WHERE setting_key = 'maintenance_mode'")->execute();
    }

    public static function disable() {
        $db = Database::getInstance();
        $db->prepare("UPDATE system_settings SET setting_value = '0' WHERE setting_key = 'maintenance_mode'")->execute();
    }
}
