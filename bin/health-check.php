<?php
// AppForm System Health Checker

require_once __DIR__ . '/../vendor/autoload.php';
new \App\Core\App();

echo "Running AppForm Diagnostics & Health Check...\n";

$failed = false;

// 1. Check Database connection
try {
    $db = \App\Core\Database::getInstance();
    $db->query("SELECT 1");
    echo "[+] Database Connection: OK\n";
} catch (\Exception $e) {
    echo "[-] Database Connection: FAIL (" . $e->getMessage() . ")\n";
    $failed = true;
}

// 2. Check writable paths
$paths = [
    dirname(__DIR__) . '/storage/logs',
    dirname(__DIR__) . '/storage/private_uploads',
    dirname(__DIR__) . '/storage/exports'
];

foreach ($paths as $path) {
    if (!is_dir($path)) {
        @mkdir($path, 0755, true);
    }
    if (is_writable($path)) {
        echo "[+] Path Writable: OK ($path)\n";
    } else {
        echo "[-] Path Writable: FAIL ($path)\n";
        $failed = true;
    }
}

if ($failed) {
    echo "\n[-] HEALTH CHECK: FAIL\n";
    exit(1);
}

echo "\n[+] HEALTH CHECK: PASS\n";
exit(0);
