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

// Test 2: Spawning background PHP worker process safely
$updateId = UpdateStatusService::createUpdateRecord([
    'release_version' => '1.2.0',
    'build_number' => 3,
    'release_channel' => 'stable',
    'previous_version' => '1.0.0',
    'previous_build' => 1,
    'provider' => 'github',
    'started_by' => 1
]);

$spawned = ProcessRunner::runBackgroundWorker($updateId);
assert($spawned === true, "Test 2 Failed: Detached background worker could not start.");
echo "Test 2 Passed: Detached background worker successfully spawned.\n";

// Clean up
$db = \App\Core\Database::getInstance();
$db->prepare("DELETE FROM application_updates WHERE id = ?")->execute([$updateId]);

return true;
