<?php
// Debug: Check what exactly fails in the update pipeline
if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
}
new \App\Core\App();

use App\Core\Database;
use App\Services\Update\UpdateEngineService;
use App\Services\Update\UpdateStatusService;

$db = Database::getInstance();
$root = dirname(__DIR__);

// Create a test update record
$db->exec("DELETE FROM application_updates WHERE provider = 'debug_test'");
$updateId = UpdateStatusService::createUpdateRecord([
    'release_version'  => '1.1.3',
    'build_number'     => 5,
    'release_channel'  => 'stable',
    'previous_version' => '1.1.2',
    'previous_build'   => 4,
    'provider'         => 'debug_test',
    'started_by'       => 1,
]);
echo "Update ID: $updateId\n";

// Set package path
$packagePath = $root . '/release/AppForm-1.1.3-update.zip';
$hash = hash_file('sha256', $packagePath);
$db->prepare("UPDATE application_updates SET package_path = ?, package_sha256 = ? WHERE id = ?")->execute([$packagePath, $hash, $updateId]);

// Test DB backup directly via Reflection
echo "\n--- Testing DB backup directly (via Reflection) ---\n";
$storageDir = $root . '/public/storage';
$backupPath = $storageDir . '/debug_backup_test.sql';
if (file_exists($backupPath)) unlink($backupPath);

$class  = new ReflectionClass(UpdateEngineService::class);
$method = $class->getMethod('createDatabaseBackup');
$method->setAccessible(true);

$result = $method->invoke(null, $backupPath);
echo "DB backup result: " . ($result ? 'SUCCESS' : 'FAIL') . "\n";
if (file_exists($backupPath)) {
    echo "Backup size: " . filesize($backupPath) . " bytes\n";
    unlink($backupPath);
} else {
    echo "Backup file NOT created\n";
}

// Also test resolveMysqldump
$resolveMethod = $class->getMethod('resolveMysqldump');
$resolveMethod->setAccessible(true);
$resolved = $resolveMethod->invoke(null);
echo "Resolved mysqldump: $resolved\n";
echo "Exists: " . (file_exists($resolved) ? 'YES' : 'NO') . "\n";

// Check app log for errors
echo "\n--- Recent log entries ---\n";
$logFile = $root . '/public/storage/logs/app.log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    $recent = array_slice($lines, -15);
    foreach ($recent as $line) {
        echo trim($line) . "\n";
    }
}

$db->exec("DELETE FROM application_updates WHERE id = {$updateId}");
echo "\nDone.\n";
return true;
