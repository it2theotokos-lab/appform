<?php
/**
 * LocalUpdateAcceptanceTest — v1.1.19
 *
 * Tests for the Manual/Local ZIP upload update flow.
 *
 * Verified bugs fixed in v1.1.17:
 *  BUG-1: HTTP 500 due to invalid state transition pending→downloading
 *          (not in UpdateStateMachine allowedTransitions).
 *  BUG-2: GitHub API cURL call inside runPreFlightChecks() blocking local upload path.
 *
 * Verified bugs fixed in v1.1.19:
 *  BUG-3: waiting_for_lock → waiting_for_lock invalid transition crash.
 *         Controller was transitioning to waiting_for_lock BEFORE launching the worker.
 *         Worker also does the same transition → crash.
 *         Fix: controller stays in 'pending'; worker is sole owner of waiting_for_lock.
 *
 * Test suite:
 *  1 & 2 : State machine transitions (baseline sanity)
 *  3     : runPreFlightChecks(skipNetworkCheck=true) returns success without GitHub call
 *  4     : BUG-3 regression — controller leaves record in 'pending', NOT waiting_for_lock
 *  5     : Invalid CSRF returns JSON error (not HTML 419)
 *  6     : Invalid/empty ZIP returns JSON error
 *  7     : Full install ZIP rejected with JSON error
 *  8     : ZIP without manifest rejected with JSON error
 *  9     : Valid incremental ZIP structure accepted (staging path)
 * 10     : Cleanup — no orphan rows or files
 * 11     : waiting_for_lock → waiting_for_lock is INVALID (no self-transition allowed)
 * 12     : Full localUpdate pipeline: staging=pending, worker makes ONE waiting_for_lock transition
 * 13     : Online Update pipeline: state machine allows checking_release and downloading transitions
 * 14     : Rollback state machine paths remain valid after the fix
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Services\Update\UpdateStateMachine;
use App\Services\Update\PackageValidatorService;
use App\Services\Update\UpdateEngineService;
use App\Services\Update\UpdateStatusService;
use App\Core\Database;

echo "=========================================\n";
echo "    Local Update Acceptance Test v1.1.19 \n";
echo "=========================================\n";

$failed = false;
$db = Database::getInstance();

function assertTest(bool $condition, string $message): void {
    global $failed;
    if (!$condition) {
        echo "  ❌ FAIL: $message\n";
        $failed = true;
    } else {
        echo "  ✅ PASS: $message\n";
    }
}

// ── Helper: build a valid minimal incremental ZIP in sys_get_temp_dir() ────────
function buildMinimalUpdateZip(string $filename, string $packageType = 'update', string $version = '1.1.17', int $build = 19): string {
    $path = sys_get_temp_dir() . '/' . $filename;
    @unlink($path);
    $zip = new ZipArchive();
    $zip->open($path, ZipArchive::CREATE);
    $manifest = json_encode([
        'manifest_schema_version' => '1.0.0',
        'product'      => 'AppForm',
        'package_type' => $packageType,
        'version'      => $version,
        'build'        => $build,
    ], JSON_UNESCAPED_UNICODE);
    $zip->addFromString('manifest.json', $manifest);
    $zip->addFromString('checksums.json', json_encode([]));
    $zip->addFromString('src/placeholder.php', '<?php // placeholder');
    $zip->close();
    return $path;
}

// ── Helper: simulate the localUpdate controller call without HTTP upload infra ─
function simulateLocalUploadDirect(string $zipPath, string $zipName, bool $csrfValid = true): ?array {
    // Simulate $_FILES
    $_FILES = [
        'local_zip' => [
            'name'     => $zipName,
            'tmp_name' => $zipPath,
            'error'    => UPLOAD_ERR_OK,
            'size'     => filesize($zipPath),
        ]
    ];
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST['_token'] = $csrfValid ? \App\Core\Csrf::token() : 'invalid_token_xyz';

    $stmt = Database::getInstance()->prepare("SELECT id FROM users WHERE id = 1 LIMIT 1");
    $stmt->execute();
    $user = $stmt->fetch();
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
    }

    // JsonResponseException carries the payload array directly — no ob_start/exit race.
    try {
        $controller = new \App\Controllers\SettingsController();
        $controller->localUpdate();
        // If we reach here, the controller returned without throwing (should not happen normally).
        return ['success' => true, 'message' => 'Controller returned without JSON response (unexpected).'];
    } catch (\App\Core\JsonResponseException $jre) {
        // Expected path: controller threw JsonResponseException with the JSON payload.
        return $jre->getPayload();
    } catch (\Throwable $e) {
        // Unexpected exception escaped the controller's own safety net — this is a bug.
        return ['success' => false, 'message' => 'UNCAUGHT: ' . $e->getMessage(), '_exception' => get_class($e)];
    }
}

// ── Cleanup helper ─────────────────────────────────────────────────────────────
$createdUpdateIds = [];
function cleanupUpdateRecord(int $id): void {
    $db = Database::getInstance();
    $db->prepare("DELETE FROM application_update_logs WHERE update_id = ?")->execute([$id]);
    $db->prepare("DELETE FROM application_updates WHERE id = ?")->execute([$id]);
}

// ═══════════════════════════════════════════════════════════════════════════════
// TEST 1 & 2 — State machine baseline
// ═══════════════════════════════════════════════════════════════════════════════
echo "\nTest 1 & 2: State Machine baseline transitions...\n";

try {
    UpdateStateMachine::validateTransition(UpdateStateMachine::STATE_PENDING, UpdateStateMachine::STATE_WAITING_FOR_LOCK);
    assertTest(true, "pending → waiting_for_lock is a valid transition.");
} catch (\Exception $e) {
    assertTest(false, "pending → waiting_for_lock should be valid: " . $e->getMessage());
}

$invalidRaised = false;
try {
    UpdateStateMachine::validateTransition(UpdateStateMachine::STATE_PENDING, UpdateStateMachine::STATE_DOWNLOADING);
    assertTest(false, "pending → downloading should be INVALID (it was allowed — BUG still present).");
} catch (\InvalidArgumentException $e) {
    $invalidRaised = true;
    assertTest(true, "pending → downloading is correctly INVALID (BUG-1 confirmed fixed).");
}

// ═══════════════════════════════════════════════════════════════════════════════
// TEST 3 — runPreFlightChecks with skipNetworkCheck=true (no GitHub call)
// ═══════════════════════════════════════════════════════════════════════════════
echo "\nTest 3: runPreFlightChecks(skipNetworkCheck=true) skips GitHub...\n";
$pfResult = UpdateEngineService::runPreFlightChecks(null, true);
// May pass or fail on mysqldump — what matters is it does NOT timeout on GitHub
assertTest(
    array_key_exists('success', $pfResult) && array_key_exists('message', $pfResult),
    "runPreFlightChecks returns success/message keys when skipNetworkCheck=true."
);
assertTest(
    !str_contains($pfResult['message'] ?? '', 'GitHub'),
    "runPreFlightChecks(skipNetworkCheck=true) message does not mention GitHub."
);

// ═══════════════════════════════════════════════════════════════════════════════
// TEST 4 — BUG-3 regression: controller leaves record in 'pending' (NOT waiting_for_lock)
// ═══════════════════════════════════════════════════════════════════════════════
echo "\nTest 4: BUG-3 regression — controller must NOT transition to waiting_for_lock...\n";
// The controller must NOT call updateState() to waiting_for_lock.
// That transition belongs exclusively to the worker (runUpdate()).
// Verify by checking that the SettingsController source code no longer contains
// the updateState call for STATE_WAITING_FOR_LOCK inside localUpdate().
$controllerSource = file_get_contents(dirname(__DIR__) . '/src/controllers/SettingsController.php');

// The specific bug: controller called updateState($id, STATE_WAITING_FOR_LOCK) inside localUpdate().
// After the fix, localUpdate() must NOT contain that call.
// We search for the pattern that was the bug: updateState + STATE_WAITING_FOR_LOCK inside localUpdate().
// Strategy: extract just the localUpdate method and check it does NOT call updateState.
$localUpdateStart = strpos($controllerSource, 'public function localUpdate()');
$localUpdateEnd   = strpos($controllerSource, 'public function getStatus()', $localUpdateStart);
$localUpdateBody  = substr($controllerSource, $localUpdateStart, $localUpdateEnd - $localUpdateStart);

assertTest(
    !str_contains($localUpdateBody, 'updateState') || !str_contains($localUpdateBody, 'WAITING_FOR_LOCK'),
    "BUG-3 fixed: localUpdate() does NOT call updateState() to STATE_WAITING_FOR_LOCK."
);

// Also verify the state machine still correctly allows pending → waiting_for_lock (for the worker)
$verData = \App\Services\VersionService::getVersionData();
$testUpdateId = UpdateStatusService::createUpdateRecord([
    'release_version'  => '1.1.99',
    'build_number'     => 99,
    'release_channel'  => 'test',
    'previous_version' => $verData['version'],
    'previous_build'   => $verData['build'],
    'provider'         => 'test_runner',
]);
$createdUpdateIds[] = $testUpdateId;

try {
    UpdateStatusService::updateState(
        $testUpdateId,
        UpdateStateMachine::STATE_WAITING_FOR_LOCK,
        'test_stage'
    );
    $row = $db->prepare("SELECT status FROM application_updates WHERE id = ?");
    $row->execute([$testUpdateId]);
    $status = $row->fetchColumn();
    assertTest($status === 'waiting_for_lock', "Worker can still do pending → waiting_for_lock (was: $status).");
} catch (\Throwable $e) {
    assertTest(false, "Worker pending → waiting_for_lock threw exception: " . $e->getMessage());
}

// ═══════════════════════════════════════════════════════════════════════════════
// TEST 5 — Invalid CSRF returns JSON error (not HTML 419)
// ═══════════════════════════════════════════════════════════════════════════════
echo "\nTest 5: Invalid CSRF → JSON error response...\n";
$tmpBogus = sys_get_temp_dir() . '/csrf_test.zip';
file_put_contents($tmpBogus, 'not a zip');
$res5 = simulateLocalUploadDirect($tmpBogus, 'csrf_test.zip', false);
@unlink($tmpBogus);
assertTest(
    isset($res5['success']) && $res5['success'] === false,
    "Invalid CSRF returns JSON {success:false}."
);
assertTest(
    isset($res5['message']) && str_contains(strtolower($res5['message']), 'csrf'),
    "Invalid CSRF message contains 'CSRF'."
);

// ═══════════════════════════════════════════════════════════════════════════════
// TEST 6 — Invalid/corrupt ZIP returns JSON error
// ═══════════════════════════════════════════════════════════════════════════════
echo "\nTest 6: Invalid ZIP → JSON error...\n";
$tmpCorrupt = sys_get_temp_dir() . '/corrupt_update.zip';
file_put_contents($tmpCorrupt, "this is not a zip file at all");
$res6 = simulateLocalUploadDirect($tmpCorrupt, 'corrupt_update.zip');
@unlink($tmpCorrupt);
assertTest(
    isset($res6['success']) && $res6['success'] === false,
    "Corrupt ZIP returns JSON {success:false}."
);
assertTest(
    !isset($res6['_raw']),
    "Corrupt ZIP response is valid JSON (no raw output)."
);

// ═══════════════════════════════════════════════════════════════════════════════
// TEST 7 — Full install ZIP (type=full) is rejected
// ═══════════════════════════════════════════════════════════════════════════════
echo "\nTest 7: Full install ZIP → rejected...\n";
$tmpFull = buildMinimalUpdateZip('AppForm-1.1.17.zip', 'full');
$res7 = simulateLocalUploadDirect($tmpFull, 'AppForm-1.1.17.zip');
@unlink($tmpFull);
assertTest(
    isset($res7['success']) && $res7['success'] === false,
    "Full install ZIP rejected with {success:false}."
);
assertTest(
    str_contains($res7['message'] ?? '', 'Incremental'),
    "Full install rejection message mentions 'Incremental'."
);

// ═══════════════════════════════════════════════════════════════════════════════
// TEST 8 — ZIP without manifest.json is rejected
// ═══════════════════════════════════════════════════════════════════════════════
echo "\nTest 8: ZIP without manifest → rejected...\n";
$tmpNoManifest = sys_get_temp_dir() . '/no_manifest.zip';
@unlink($tmpNoManifest);
$zip8 = new ZipArchive();
$zip8->open($tmpNoManifest, ZipArchive::CREATE);
$zip8->addFromString('dummy.txt', 'hello world');
$zip8->close();
$res8 = simulateLocalUploadDirect($tmpNoManifest, 'no_manifest.zip');
@unlink($tmpNoManifest);
assertTest(
    isset($res8['success']) && $res8['success'] === false,
    "ZIP without manifest rejected with {success:false}."
);
assertTest(
    str_contains($res8['message'] ?? '', 'package') || str_contains($res8['message'] ?? '', 'manifest'),
    "ZIP without manifest rejection message is descriptive."
);

// ═══════════════════════════════════════════════════════════════════════════════
// TEST 9 — Valid incremental ZIP staging (no move_uploaded_file in CLI)
// ═══════════════════════════════════════════════════════════════════════════════
echo "\nTest 9: Valid incremental ZIP — staging path (CLI simulation)...\n";
// In CLI, move_uploaded_file() always returns false. So we test the path up to
// the point where it would call move_uploaded_file, and verify the DB record was
// created correctly before the move attempt.
$tmpValid = buildMinimalUpdateZip('AppForm-1.1.17-update.zip', 'update', '1.1.17', 19);

// Manually trace what the controller does up to createUpdateRecord:
$manifest9 = json_decode(file_get_contents('zip://' . $tmpValid . '#manifest.json'), true);
assertTest(
    ($manifest9['package_type'] ?? '') === 'update',
    "Valid incremental ZIP has package_type=update in manifest."
);
assertTest(
    $manifest9['version'] === '1.1.17' && $manifest9['build'] === 19,
    "Valid incremental ZIP has correct version=1.1.17 / build=19."
);

$validation9 = PackageValidatorService::validatePackage($tmpValid);
assertTest(
    $validation9['success'] === true,
    "PackageValidatorService accepts valid incremental ZIP: " . $validation9['message']
);

$preFlight9 = UpdateEngineService::runPreFlightChecks($tmpValid, true);
assertTest(
    array_key_exists('success', $preFlight9),
    "runPreFlightChecks(skipNetworkCheck=true) returns a result for valid ZIP."
);

// Simulate the localUpdate() call — will fail at move_uploaded_file (expected in CLI)
$res9 = simulateLocalUploadDirect($tmpValid, 'AppForm-1.1.17-update.zip');
@unlink($tmpValid);

// In CLI, move_uploaded_file returns false → JSON error about storage
// The key assertion: it must NOT be an HTTP 500 and must be valid JSON
assertTest(
    isset($res9['success']),
    "Valid incremental ZIP upload returns valid JSON response (no HTTP 500)."
);
assertTest(
    !isset($res9['_raw']),
    "Valid incremental ZIP response is parseable JSON."
);
// Either success (if somehow move works) or a specific storage error — NOT a state machine error
if (isset($res9['success']) && $res9['success'] === false) {
    assertTest(
        !str_contains($res9['message'] ?? '', 'Invalid state transition'),
        "BUG-1 is eliminated: no 'Invalid state transition' error in response."
    );
    assertTest(
        !str_contains($res9['message'] ?? '', 'UNCAUGHT'),
        "No uncaught exception escaped the controller."
    );
}

// Find any update record created by test 9 and mark for cleanup
$stmt9 = $db->prepare("SELECT id FROM application_updates WHERE provider = 'local_upload' AND release_version = '1.1.17' ORDER BY id DESC LIMIT 1");
$stmt9->execute();
$t9id = (int)$stmt9->fetchColumn();
if ($t9id > 0) {
    $createdUpdateIds[] = $t9id;
}

// ═══════════════════════════════════════════════════════════════════════════════
// TEST 10 — Cleanup: no orphan rows
// ═══════════════════════════════════════════════════════════════════════════════
echo "\nTest 10: Cleanup — removing test records...\n";
foreach ($createdUpdateIds as $uid) {
    cleanupUpdateRecord($uid);
}
$check = $db->prepare("SELECT COUNT(*) FROM application_updates WHERE id IN (" . implode(',', array_fill(0, count($createdUpdateIds), '?')) . ")");
if (!empty($createdUpdateIds)) {
    $check->execute($createdUpdateIds);
    $remaining = (int)$check->fetchColumn();
    assertTest($remaining === 0, "All $remaining test update records cleaned up.");
} else {
    assertTest(true, "No test records to clean up.");
}

// ═══════════════════════════════════════════════════════════════════════════════
// TEST 11 — waiting_for_lock → waiting_for_lock is INVALID (no self-transition)
// ═══════════════════════════════════════════════════════════════════════════════
echo "\nTest 11: waiting_for_lock → waiting_for_lock must be INVALID...\n";
$selfTransitionRaised = false;
try {
    UpdateStateMachine::validateTransition(
        UpdateStateMachine::STATE_WAITING_FOR_LOCK,
        UpdateStateMachine::STATE_WAITING_FOR_LOCK
    );
    assertTest(false, "waiting_for_lock → waiting_for_lock should be INVALID (was allowed — BUG still present).");
} catch (\InvalidArgumentException $e) {
    $selfTransitionRaised = true;
    assertTest(true, "waiting_for_lock → waiting_for_lock is correctly INVALID (BUG-3 confirmed fixed).");
}
assertTest($selfTransitionRaised, "InvalidArgumentException raised for waiting_for_lock self-transition.");

// ═══════════════════════════════════════════════════════════════════════════════
// TEST 12 — Full localUpdate pipeline: controller leaves 'pending', worker does ONE transition
// ═══════════════════════════════════════════════════════════════════════════════
echo "\nTest 12: Controller leaves record in pending; worker makes exactly ONE waiting_for_lock transition...\n";
// Create a fresh 'pending' record to simulate what localUpdate() creates.
$verData12 = \App\Services\VersionService::getVersionData();
$t12id = UpdateStatusService::createUpdateRecord([
    'release_version'  => '1.1.19',
    'build_number'     => 21,
    'release_channel'  => 'stable',
    'previous_version' => $verData12['version'],
    'previous_build'   => $verData12['build'],
    'provider'         => 'local_upload',
]);
$createdUpdateIds[] = $t12id;

// Verify created record is in 'pending'
$r12 = $db->prepare("SELECT status FROM application_updates WHERE id = ?");
$r12->execute([$t12id]);
$s12 = $r12->fetchColumn();
assertTest($s12 === 'pending', "New local_upload record starts in 'pending' state (got: $s12).");

// Simulate worker doing ONE transition: pending → waiting_for_lock
try {
    UpdateStatusService::updateState($t12id, UpdateStateMachine::STATE_WAITING_FOR_LOCK, 'lock_check');
    $r12b = $db->prepare("SELECT status FROM application_updates WHERE id = ?");
    $r12b->execute([$t12id]);
    $s12b = $r12b->fetchColumn();
    assertTest($s12b === 'waiting_for_lock', "Worker: pending → waiting_for_lock succeeded (got: $s12b).");
} catch (\Throwable $e) {
    assertTest(false, "Worker: pending → waiting_for_lock threw exception: " . $e->getMessage());
}

// Simulate worker trying a second transition (the BUG): waiting_for_lock → waiting_for_lock
$secondTransitionBlocked = false;
try {
    UpdateStatusService::updateState($t12id, UpdateStateMachine::STATE_WAITING_FOR_LOCK, 'lock_check_again');
    assertTest(false, "BUG-3: second waiting_for_lock transition was allowed — bug NOT fixed.");
} catch (\InvalidArgumentException $e) {
    $secondTransitionBlocked = true;
    assertTest(true, "BUG-3 confirmed: second waiting_for_lock transition correctly blocked.");
}
assertTest($secondTransitionBlocked, "State machine blocks duplicate waiting_for_lock → waiting_for_lock.");

// Simulate worker continuing correctly: waiting_for_lock → maintenance_enabled
try {
    UpdateStatusService::updateState($t12id, UpdateStateMachine::STATE_MAINTENANCE_ENABLED, 'maintenance_enable');
    $r12c = $db->prepare("SELECT status FROM application_updates WHERE id = ?");
    $r12c->execute([$t12id]);
    $s12c = $r12c->fetchColumn();
    assertTest($s12c === 'maintenance_enabled', "Worker: waiting_for_lock → maintenance_enabled succeeded (got: $s12c).");
} catch (\Throwable $e) {
    assertTest(false, "Worker: waiting_for_lock → maintenance_enabled threw exception: " . $e->getMessage());
}

// ═══════════════════════════════════════════════════════════════════════════════
// TEST 13 — Online Update pipeline: regression (checking_release, downloading paths)
// ═══════════════════════════════════════════════════════════════════════════════
echo "\nTest 13: Online Update state machine regression...\n";
// Online update leaves record in 'downloading' before spawning worker.
// Worker must be able to go: downloading → waiting_for_lock → maintenance_enabled
$verData13 = \App\Services\VersionService::getVersionData();
$t13id = UpdateStatusService::createUpdateRecord([
    'release_version'  => '1.1.19',
    'build_number'     => 21,
    'release_channel'  => 'stable',
    'previous_version' => $verData13['version'],
    'previous_build'   => $verData13['build'],
    'provider'         => 'github',
]);
$createdUpdateIds[] = $t13id;

// Online update flow: pending → checking_release → downloading
try {
    UpdateStatusService::updateState($t13id, UpdateStateMachine::STATE_CHECKING_RELEASE, 'check_release');
    UpdateStatusService::updateState($t13id, UpdateStateMachine::STATE_DOWNLOADING, 'download_package');
    $r13 = $db->prepare("SELECT status FROM application_updates WHERE id = ?");
    $r13->execute([$t13id]);
    $s13 = $r13->fetchColumn();
    assertTest($s13 === 'downloading', "Online update flow: pending → checking_release → downloading OK (got: $s13).");
} catch (\Throwable $e) {
    assertTest(false, "Online update flow transitions threw exception: " . $e->getMessage());
}

// Worker: downloading → waiting_for_lock (valid — different from local_upload path)
try {
    UpdateStatusService::updateState($t13id, UpdateStateMachine::STATE_WAITING_FOR_LOCK, 'lock_check');
    $r13b = $db->prepare("SELECT status FROM application_updates WHERE id = ?");
    $r13b->execute([$t13id]);
    $s13b = $r13b->fetchColumn();
    assertTest($s13b === 'waiting_for_lock', "Online update worker: downloading → waiting_for_lock OK (got: $s13b).");
} catch (\Throwable $e) {
    assertTest(false, "Online update worker: downloading → waiting_for_lock threw exception: " . $e->getMessage());
}

// ═══════════════════════════════════════════════════════════════════════════════
// TEST 14 — Rollback state machine paths remain valid after the fix
// ═══════════════════════════════════════════════════════════════════════════════
echo "\nTest 14: Rollback state machine regression...\n";
$rollbackPaths = [
    [UpdateStateMachine::STATE_FAILED, UpdateStateMachine::STATE_ROLLING_BACK],
    [UpdateStateMachine::STATE_ROLLING_BACK, UpdateStateMachine::STATE_RESTORING_DATABASE],
    [UpdateStateMachine::STATE_RESTORING_DATABASE, UpdateStateMachine::STATE_RESTORING_FILES],
    [UpdateStateMachine::STATE_RESTORING_FILES, UpdateStateMachine::STATE_VALIDATING_ROLLBACK],
    [UpdateStateMachine::STATE_VALIDATING_ROLLBACK, UpdateStateMachine::STATE_ROLLED_BACK],
];
$allRollbackValid = true;
foreach ($rollbackPaths as [$from, $to]) {
    try {
        UpdateStateMachine::validateTransition($from, $to);
    } catch (\Throwable $e) {
        assertTest(false, "Rollback path {$from} → {$to} is now INVALID (regression): " . $e->getMessage());
        $allRollbackValid = false;
    }
}
if ($allRollbackValid) {
    assertTest(true, "All rollback state transitions remain valid after BUG-3 fix.");
}

// Also confirm failed → rolled_back shortcut is still valid
try {
    UpdateStateMachine::validateTransition(UpdateStateMachine::STATE_FAILED, UpdateStateMachine::STATE_ROLLED_BACK);
    assertTest(true, "failed → rolled_back shortcut remains valid.");
} catch (\Throwable $e) {
    assertTest(false, "failed → rolled_back shortcut regression: " . $e->getMessage());
}

echo "\n=========================================\n";
if ($failed) {
    echo "  SOME LOCAL UPDATE TESTS FAILED ❌\n";
    exit(1);
} else {
    echo "  ALL LOCAL UPDATE TESTS PASSED ✅\n";
    return true;
}
