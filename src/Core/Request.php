<?php
namespace App\Core;

class Request {
    public static function method(): string {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function all(): array {
        $data = [];
        
        // GET params
        foreach ($_GET as $key => $val) {
            $data[$key] = $_GET[$key];
        }

        // POST params
        foreach ($_POST as $key => $val) {
            $data[$key] = $_POST[$key];
        }

        // Handle JSON payloads
        $json = file_get_contents('php://input');
        if ($json) {
            $decoded = json_decode($json, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $data = array_merge($data, $decoded);
            }
        }

        return $data;
    }

    public static function files(): array {
        return $_FILES;
    }

    public static function post(?string $key = null, $default = null) {
        if ($key === null) {
            return $_POST;
        }
        return $_POST[$key] ?? $default;
    }
}
