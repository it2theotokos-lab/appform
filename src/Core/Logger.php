<?php
namespace App\Core;

class Logger {
    public static function log(string $message, string $level = 'ERROR') {
        $logPath = App::$config['storage']['logs'] ?? __DIR__ . '/../../public/storage/logs/app.log';
        $logDir = dirname($logPath);

        // Auto-create directories if they do not exist
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $formatted = "[$timestamp] [$level] $message" . PHP_EOL;
        
        file_put_contents($logPath, $formatted, FILE_APPEND);
    }
}
