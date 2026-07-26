<?php
/**
 * AppForm Background Update Worker
 */

// Disable timeouts and output buffering
set_time_limit(0);
ini_set('memory_limit', '512M');

$root = dirname(__DIR__) . '/..';
$autoload = $root . '/vendor/autoload.php';

if (is_file($autoload)) {
    require_once $autoload;
}

spl_autoload_register(function ($class) use ($root) {
    $prefix = 'App\\';
    $base_dir = $root . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Bootstrap application instance to load configuration and database connection
$app = new App\Core\App();

// Retrieve Update ID from arguments
$updateId = null;
foreach ($argv as $index => $arg) {
    if ($arg === '--' && isset($argv[$index + 1])) {
        $updateId = (int)$argv[$index + 1];
        break;
    }
}

if (!$updateId) {
    // Check fallback argument
    $updateId = isset($argv[1]) ? (int)$argv[1] : null;
}

if (!$updateId) {
    fwrite(STDERR, "ERROR: Missing required Update ID argument.\n");
    exit(1);
}

try {
    $success = \App\Services\Update\UpdateEngineService::runUpdate($updateId);
    exit($success ? 0 : 1);
} catch (\Throwable $e) {
    fwrite(STDERR, "CRITICAL ERROR: " . $e->getMessage() . "\n");
    exit(1);
}
