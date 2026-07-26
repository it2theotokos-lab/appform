<?php
namespace App\Core;

class Response {
    public static function json($data, int $status = 200) {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function status(int $code) {
        http_response_code($code);
    }
}
