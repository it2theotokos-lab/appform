<?php
namespace App\Middleware;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;

class MaintenanceMiddleware {
    public function handle(array $args = []) {
        // 1. Check local status file fallback (allows offline maintenance checks during migrations)
        $isMaint = false;
        $statusFile = dirname(__DIR__) . '/../storage/update_status.json';
        if (file_exists($statusFile)) {
            $statusData = json_decode(file_get_contents($statusFile), true);
            if (!empty($statusData['maintenance_mode'])) {
                $isMaint = true;
            }
        }

        // 2. Database backup check
        if (!$isMaint) {
            try {
                $db = Database::getInstance();
                $stmt = $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'maintenance_mode'");
                $isMaint = (int)$stmt->fetchColumn() === 1;
            } catch (\Exception $e) {
                // system_settings not installed yet — skip database maintenance check
            }
        }

        if ($isMaint) {
            // Exclude administrators from maintenance lockouts
            if (Auth::check() && Auth::role() === 'administrator') {
                return;
            }

            // Render maintenance view page layout
            http_response_code(503);
            View::render('maintenance', [
                'title' => 'Συντήρηση Συστήματος'
            ]);
            exit;
        }
    }
}
