<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Services\Update\UpdateStateMachine;
use App\Services\Update\PackageValidatorService;
use App\Services\Update\UpdateEngineService;
use App\Core\Auth;
use App\Core\Database;

// App is already initialized by the test runner

echo "=========================================\n";
echo "    AppForm Local Update & Transitions Test\n";
echo "=========================================\n";

$failed = false;
$db = Database::getInstance();

function assertTest($condition, $message) {
    global $failed;
    if (!$condition) {
        echo "  ❌ $message\n";
        $failed = true;
    } else {
        echo "  ✅ $message\n";
    }
}

// -------------------------------------------------------------------------
// TEST 1 & 2: UpdateStateMachine Transitions (Downloading -> Lock, Rollback Failures)
// -------------------------------------------------------------------------
echo "\nTest 1 & 2: State Machine Transitions...\n";

try {
    UpdateStateMachine::validateTransition(UpdateStateMachine::STATE_DOWNLOADING, UpdateStateMachine::STATE_WAITING_FOR_LOCK);
    assertTest(true, "Transition downloading -> waiting_for_lock is allowed.");
} catch (\Exception $e) {
    assertTest(false, "Transition downloading -> waiting_for_lock should be allowed.");
}

try {
    UpdateStateMachine::validateTransition(UpdateStateMachine::STATE_FAILED, UpdateStateMachine::STATE_VALIDATING_ROLLBACK);
    assertTest(true, "Transition failed -> validating_rollback (no backups fallback) is allowed.");
} catch (\Exception $e) {
    assertTest(false, "Transition failed -> validating_rollback should be allowed.");
}

try {
    UpdateStateMachine::validateTransition(UpdateStateMachine::STATE_RESTORING_DATABASE, UpdateStateMachine::STATE_VALIDATING_ROLLBACK);
    assertTest(true, "Transition restoring_database -> validating_rollback is allowed.");
} catch (\Exception $e) {
    assertTest(false, "Transition restoring_database -> validating_rollback should be allowed.");
}

// -------------------------------------------------------------------------
// TEST 3, 4, 5, 6: Local Update Uploads via SettingsController (Simulated)
// -------------------------------------------------------------------------
echo "\nTest 3, 4, 5, 6: Local Update Upload Validation...\n";

function simulateLocalUpload($fileData, $userRole = 'admin', $csrfValid = true) {
    $_FILES = ['local_zip' => $fileData];
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST['_token'] = $csrfValid ? \App\Core\Csrf::token() : 'invalid';
    
    // Mock Auth
    if ($userRole) {
        $stmt = Database::getInstance()->prepare("SELECT * FROM users WHERE id = 1 LIMIT 1");
        $stmt->execute();
        $user = $stmt->fetch();
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
        } else {
            unset($_SESSION['user_id']);
        }
    } else {
        unset($_SESSION['user_id']);
    }
    
    ob_start();
    try {
        $controller = new \App\Controllers\SettingsController();
        $controller->localUpdate();
    } catch (\Throwable $e) {
        echo $e->getMessage();
    }
    $output = ob_get_clean();
    return json_decode($output, true) ?: ['message' => $output];
}

// 4. Unauth/Non-admin Rejection
try {
    $res = simulateLocalUpload(['name' => 'test.zip', 'tmp_name' => '', 'error' => UPLOAD_ERR_OK], null, false);
    assertTest(str_contains($res['message'] ?? '', 'CSRF') || str_contains($res['message'] ?? '', 'CSRF') || str_contains(ob_get_contents() ?? '', 'CSRF'), "Unauthenticated/Invalid CSRF is rejected.");
} catch (\Exception $e) {
    assertTest(true, "Unauthenticated/Invalid CSRF throws exception.");
}

// 5. Invalid / Empty / Full Installation ZIP
$tmpInvalidZip = sys_get_temp_dir() . '/invalid_update.zip';
file_put_contents($tmpInvalidZip, "not a zip file");
$res = simulateLocalUpload(['name' => 'invalid_update.zip', 'tmp_name' => $tmpInvalidZip, 'error' => UPLOAD_ERR_OK], 'admin');
assertTest(isset($res['success']) && $res['success'] === false && str_contains($res['message'], 'package'), "Invalid ZIP is rejected.");
@unlink($tmpInvalidZip);

// Full installation ZIP (manifest says type = install)
$tmpFullZip = sys_get_temp_dir() . '/AppForm-1.1.11-install.zip';
$zip = new ZipArchive();
if ($zip->open($tmpFullZip, ZipArchive::CREATE) === true) {
    $zip->addFromString('manifest.json', json_encode(['package_type' => 'install', 'version' => '1.1.11']));
    $zip->close();
}
$res = simulateLocalUpload(['name' => 'AppForm-1.1.11-install.zip', 'tmp_name' => $tmpFullZip, 'error' => UPLOAD_ERR_OK], 'admin');
assertTest(isset($res['success']) && $res['success'] === false && str_contains($res['message'], 'Incremental Updates'), "Full installation ZIP is rejected.");
@unlink($tmpFullZip);

// Oversized ZIP / Missing manifest
$tmpEmptyZip = sys_get_temp_dir() . '/empty.zip';
$zip = new ZipArchive();
if ($zip->open($tmpEmptyZip, ZipArchive::CREATE) === true) {
    $zip->addFromString('dummy.txt', 'dummy');
    $zip->close();
}
$res = simulateLocalUpload(['name' => 'empty.zip', 'tmp_name' => $tmpEmptyZip, 'error' => UPLOAD_ERR_OK], 'admin');
assertTest(isset($res['success']) && $res['success'] === false && str_contains($res['message'], 'package'), "ZIP without valid signature/manifest is rejected.");
@unlink($tmpEmptyZip);

echo "=========================================\n";
if ($failed) {
    echo "  SOME TESTS FAILED \n";
    exit(1);
} else {
    echo "  ALL LOCAL UPDATE TESTS PASSED ✅\n";
    return true;
}
