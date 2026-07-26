<?php
namespace App\Middleware;

use App\Core\Auth;

class AuthMiddleware {
    public function handle(array $args = []) {
        // Detect path and allow guest access to public form endpoints
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        $parsedUrl = parse_url($path);
        $path = $parsedUrl['path'] ?? '/';
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        if (preg_match('#^/forms/(?P<slug>[a-zA-Z0-9_\-]+)(/(draft|submit))?$#', $path, $matches)) {
            $slug = $matches['slug'];
            $db = \App\Core\Database::getInstance();
            $stmt = $db->prepare("SELECT is_public FROM forms WHERE slug = ?");
            $stmt->execute([$slug]);
            $form = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($form && !empty($form['is_public'])) {
                return; // Bypass auth check for public form endpoints
            }
        }

        if (preg_match('#^/f/(?P<token>[a-f0-9]+)$#', $path, $matches)) {
            $token = $matches['token'];
            $db = \App\Core\Database::getInstance();
            $stmt = $db->prepare("SELECT is_public FROM forms WHERE public_token = ?");
            $stmt->execute([$token]);
            $form = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($form && !empty($form['is_public'])) {
                return; // Bypass auth check for public token route
            }
        }

        if ($path === '/f/submitted/success') {
            return; // Bypass auth check for public success landing page
        }

        if (!Auth::check()) {
            header("Location: /login");
            exit;
        }
    }
}
