<?php
namespace App\Middleware;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;

class MaintenanceMiddleware {
    public function handle(array $args = []) {
        // Guard: system_settings table may not exist on first boot (before migrations)
        $isMaint = false;
        try {
            $db = Database::getInstance();
            $stmt = $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'maintenance_mode'");
            $isMaint = (int)$stmt->fetchColumn() === 1;
        } catch (\Exception $e) {
            // system_settings not installed yet — skip maintenance check
            return;
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
