<?php
namespace App\Core;

class Session {
    public static function init() {
        if (session_status() === PHP_SESSION_NONE) {
            $config = App::$config['session'] ?? [
                'lifetime' => 28800,
                'cookie_secure' => false,
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax'
            ];

            ini_set('session.cookie_lifetime', $config['lifetime']);
            ini_set('session.gc_maxlifetime', $config['lifetime']);
            
            session_set_cookie_params([
                'lifetime' => $config['lifetime'],
                'path' => '/',
                'domain' => '',
                'secure' => $config['cookie_secure'],
                'httponly' => $config['cookie_httponly'],
                'samesite' => $config['cookie_samesite']
            ]);

            session_start();
        }

        self::checkTimeout();
    }

    public static function set(string $key, $value) {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key) {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    public static function destroy() {
        $_SESSION = [];
        if (ini_get("session.use_cookies") && !headers_sent()) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }

    public static function regenerate() {
        session_regenerate_id(true);
    }

    public static function flash(string $key, $message = null) {
        if ($message !== null) {
            $_SESSION['flash'][$key] = $message;
        } else {
            $msg = $_SESSION['flash'][$key] ?? null;
            unset($_SESSION['flash'][$key]);
            return $msg;
        }
    }

    private static function checkTimeout() {
        if (self::has('user_id')) {
            $idleTimeout = App::$config['session']['idle_timeout'] ?? 1800;
            $lastActivity = self::get('last_activity');

            if ($lastActivity && (time() - $lastActivity > $idleTimeout)) {
                self::destroy();
                header("Location: /login?timeout=1");
                exit;
            }
            self::set('last_activity', time());
        }
    }
}
