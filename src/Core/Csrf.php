<?php
namespace App\Core;

class Csrf {
    public static function token(): string {
        if (!Session::has('csrf_token')) {
            Session::set('csrf_token', bin2hex(random_bytes(32)));
        }
        return Session::get('csrf_token');
    }

    public static function validate(): bool {
        // Skip for GET, HEAD, OPTIONS
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'])) {
            return true;
        }

        $token = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $stored = Session::get('csrf_token');

        if (!$stored || !hash_equals($stored, $token)) {
            return false;
        }
        return true;
    }

    public static function field(): string {
        return '<input type="hidden" name="_token" value="' . self::token() . '">';
    }
}
