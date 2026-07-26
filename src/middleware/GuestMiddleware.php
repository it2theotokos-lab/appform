<?php
namespace App\Middleware;

use App\Core\Auth;

class GuestMiddleware {
    public function handle(array $args = []) {
        if (Auth::check()) {
            header("Location: /dashboard");
            exit;
        }
    }
}
