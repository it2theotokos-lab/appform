<?php
// Update Engine & Rollback Unit Tests

use App\Services\Update\UpdateEngineService;
use App\Services\Update\ProcessRunner;
use App\Services\Update\UpdateStatusService;

echo "Running UpdateEngine Tests...\n";

// Test 1: Local offline maintenance mode status file creation
UpdateEngineService::writeLocalStatus(101, 'running_migrations', 80);
$statusFile = dirname(__DIR__) . '/public/storage/update_status.json';
assert(file_exists($statusFile), "Test 1 Failed: Status file was not created.");
$data = json_decode(file_get_contents($statusFile), true);
assert($data['maintenance_mode'] === true, "Test 1 Failed: Offline maintenance override must be true.");
assert($data['progress_percent'] === 80, "Test 1 Failed: Offline progress percentage mismatch.");
echo "Test 1 Passed: Offline maintenance status file verified successfully.\n";

// Cleanup local status override
if (file_exists($statusFile)) {
    unlink($statusFile);
}

// Test 2: Verify ProcessRunner resolves a real php.exe (not php-cgi.exe) for CLI
$phpCli = ProcessRunner::resolvePhpCli();
assert($phpCli !== null, "Test 2 Failed: ProcessRunner::resolvePhpCli() returned null — no php.exe found.");
assert(!stristr((string)$phpCli, 'php-cgi'), "Test 2 Failed: resolvePhpCli() returned php-cgi.exe path, which cannot be used as CLI.");
assert(file_exists($phpCli) || $phpCli === 'php', "Test 2 Failed: Resolved PHP CLI path does not exist: {$phpCli}");
echo "Test 2 Passed: ProcessRunner resolves php CLI correctly (not php-cgi): {$phpCli}\n";

// Test 3: Verify worker.php exists and is accessible
$workerPath = realpath(dirname(__DIR__) . '/tools/release/worker.php');
assert(file_exists($workerPath), "Test 3 Failed: tools/release/worker.php not found at: {$workerPath}");
echo "Test 3 Passed: worker.php is accessible at: {$workerPath}\n";

return true;
