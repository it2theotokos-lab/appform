<?php
namespace App\Middleware;

use App\Core\Auth;
use App\Core\View;

class RoleMiddleware {
    public function handle(array $args = []) {
        if (!Auth::check()) {
            header("Location: /login");
            exit;
        }

        $userRole = Auth::role();
        // Administrator bypasses role restrictions
        if ($userRole === 'administrator') {
            return;
        }

        if (empty($args) || !in_array($userRole, $args)) {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }
    }
}
