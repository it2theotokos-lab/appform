<?php
// Update/Upgrade System Unit Tests

use App\Services\Update\UpdateStateMachine;
use App\Services\Update\UpdateLockService;
use App\Services\Update\UpdateStatusService;

echo "Running UpdateSystem Tests...\n";

// Test 1: Valid State Transitions
try {
    UpdateStateMachine::validateTransition('pending', 'checking_release');
    UpdateStateMachine::validateTransition('checking_release', 'downloading');
    UpdateStateMachine::validateTransition('downloading', 'verifying_package');
    echo "Test 1 Passed: Valid state transitions validated successfully.\n";
} catch (\Exception $e) {
    echo "Test 1 Failed: " . $e->getMessage() . "\n";
    assert(false, "Valid transitions failed.");
}

// Test 2: Invalid State Transitions (Must throw Exception)
$invalidCaught = false;
try {
    UpdateStateMachine::validateTransition('pending', 'completed'); // invalid skip
} catch (\InvalidArgumentException $e) {
    $invalidCaught = true;
}
assert($invalidCaught === true, "Test 2 Failed: Invalid state transition was allowed.");
echo "Test 2 Passed: Invalid state transitions prevented correctly.\n";

// Test 3: Progress Percent Boundaries validation
$progressCaught = false;
try {
    UpdateStatusService::updateProgress(9999, 150); // invalid percentage
} catch (\InvalidArgumentException $e) {
    $progressCaught = true;
}
assert($progressCaught === true, "Test 3 Failed: Invalid progress percentage was allowed.");
echo "Test 3 Passed: Progress percentage boundaries validated correctly.\n";

// Test 4: Database updates record creation and log appending
$updateId = UpdateStatusService::createUpdateRecord([
    'release_version' => '1.1.0',
    'build_number' => 2,
    'release_channel' => 'stable',
    'previous_version' => '1.0.0',
    'previous_build' => 1,
    'provider' => 'github',
    'started_by' => 1
]);
assert(is_int($updateId) && $updateId > 0, "Test 4 Failed: Update record was not created.");
echo "Test 4 Passed: Update record created in database with ID: $updateId.\n";

UpdateStatusService::appendLog($updateId, 'init', 'info', 'Initialization test log.');
$logs = UpdateStatusService::getLogs($updateId);
assert(count($logs) === 1, "Test 4 Failed: Log was not appended correctly.");
assert($logs[0]['message'] === 'Initialization test log.', "Test 4 Failed: Log message mismatch.");
echo "Test 4 Passed: Log appending verified successfully.\n";

// Clean up test data
$db = \App\Core\Database::getInstance();
$db->prepare("DELETE FROM application_updates WHERE id = ?")->execute([$updateId]);

return true;
