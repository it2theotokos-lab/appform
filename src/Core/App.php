<?php
namespace App\Core;

class App {
    public static $config;
    public static $router;
    public static $db;

    public function __construct() {
        // Load configuration
        $configPath = __DIR__ . '/../../config/config.php';
        if (!file_exists($configPath)) {
            die("Configuration file config.php is missing. Copy config.example.php to config.php");
        }
        self::$config = require $configPath;

        // Initialize Session
        Session::init();

        // Setup Error Handling
        $this->setupErrorHandling();

        // Initialize Database Connection
        self::$db = Database::getInstance();

        // Load Router
        self::$router = new Router();
    }

    public function run() {
        try {
            $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
            $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            
            // Handle URL rewriting cleanups
            $parsedUrl = parse_url($requestUri);
            $path = $parsedUrl['path'] ?? '/';
            
            // Remove application base folder or trailing slashes if present
            if ($path !== '/' && str_ends_with($path, '/')) {
                $path = rtrim($path, '/');
            }

            self::$router->dispatch($path, $requestMethod);
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    private function setupErrorHandling() {
        if (self::$config['app']['debug']) {
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');
            error_reporting(E_ALL);
        } else {
            ini_set('display_errors', '0');
            ini_set('display_startup_errors', '0');
            error_reporting(0);
        }

        set_exception_handler([$this, 'handleException']);
        set_error_handler(function($severity, $message, $file, $line) {
            if (!(error_reporting() & $severity)) {
                return;
            }
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
    }

    public function handleException(\Throwable $e) {
        // Log Error
        Logger::log("Exception: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());

        // Send 500 error page
        if (!headers_sent()) {
            http_response_code(500);
        }

        if (self::$config['app']['debug']) {
            echo "<h1>500 Internal Server Error</h1>";
            echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
            echo "<p><strong>Line:</strong> " . htmlspecialchars($e->getLine()) . "</p>";
            echo "<h3>Stack Trace:</h3><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        } else {
            View::render('errors/500');
        }
        exit;
    }
}
