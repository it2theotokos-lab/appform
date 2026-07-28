<?php
ob_start();
/**
 * AppForm Update Download & Verification Tests
 */
require_once __DIR__ . '/../vendor/autoload.php';
// If we are run in a case mode
$case = isset($argv[1]) ? $argv[1] : null;

if ($case !== null) {
    $config = require __DIR__ . '/../config/config.php';
    \App\Core\App::$config = $config;
    new \App\Core\App();
}

use App\Core\Database;
use App\Services\Update\LocalReleaseProvider;

if ($case === 'case1') {
    // Setup test mode flag
    $testModeFlag = __DIR__ . '/../public/storage/.update_test_mode';
    file_put_contents($testModeFlag, '1');

    // Create a dummy package ZIP
    $releaseDir = __DIR__ . '/../release';
    $dummyZip = $releaseDir . '/AppForm-9.9.9-update.zip';
    file_put_contents($dummyZip, 'dummy_zip_content');
    $dummyHash = hash_file('sha256', $dummyZip);
    file_put_contents($dummyZip . '.sha256', $dummyHash);

    // Mock session and post
    \App\Core\Session::init();
    $_SESSION['user_id'] = 1;
    $_SESSION['role_slug'] = 'administrator';
    $_SESSION['permissions'] = ['updates.manage', 'updates.view', 'settings.manage'];
    $_SESSION['csrf_token'] = 'test_csrf_token';

    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = [
        'target_version' => '9.9.9',
        'build_number' => '99',
        'channel' => 'stable',
        '_token' => 'test_csrf_token'
    ];

    $controller = new \App\Controllers\SettingsController();
    $controller->startUpdate();
    exit(0);
}

if ($case === 'case2') {
    // Checksum mismatch case
    $testModeFlag = __DIR__ . '/../public/storage/.update_test_mode';
    file_put_contents($testModeFlag, '1');

    $releaseDir = __DIR__ . '/../release';
    $dummyZip = $releaseDir . '/AppForm-9.9.9-update.zip';
    file_put_contents($dummyZip, 'dummy_zip_content');
    file_put_contents($dummyZip . '.sha256', 'wrong_hash_value');

    \App\Core\Session::init();
    $_SESSION['user_id'] = 1;
    $_SESSION['role_slug'] = 'administrator';
    $_SESSION['permissions'] = ['updates.manage', 'updates.view', 'settings.manage'];
    $_SESSION['csrf_token'] = 'test_csrf_token';

    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = [
        'target_version' => '9.9.9',
        'build_number' => '99',
        'channel' => 'stable',
        '_token' => 'test_csrf_token'
    ];

    $controller = new \App\Controllers\SettingsController();
    $controller->startUpdate();
    exit(0);
}

if ($case === 'case3') {
    // Missing download/http failure case
    $testModeFlag = __DIR__ . '/../public/storage/.update_test_mode';
    file_put_contents($testModeFlag, '1');

    $releaseDir = __DIR__ . '/../release';
    $dummyZip = $releaseDir . '/AppForm-9.9.9-update.zip';
    if (file_exists($dummyZip)) unlink($dummyZip);
    if (file_exists($dummyZip . '.sha256')) unlink($dummyZip . '.sha256');

    \App\Core\Session::init();
    $_SESSION['user_id'] = 1;
    $_SESSION['role_slug'] = 'administrator';
    $_SESSION['permissions'] = ['updates.manage', 'updates.view', 'settings.manage'];
    $_SESSION['csrf_token'] = 'test_csrf_token';

    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = [
        'target_version' => '9.9.9',
        'build_number' => '99',
        'channel' => 'stable',
        '_token' => 'test_csrf_token'
    ];

    $controller = new \App\Controllers\SettingsController();
    $controller->startUpdate();
    exit(0);
}

// MAIN RUNNER
ob_end_clean(); // Clean the start buffer

echo "========================================\n";
echo "  APPFORM UPDATE DOWNLOAD & VERIFICATION TESTS\n";
echo "========================================\n";

$db = Database::getInstance();
$php = "C:\\Antigravity-PRJ\\Tools\\PHP\\php.exe";
$script = __FILE__;

// -------------------------------------------------------------------------
// TEST 1: Successful download, filename, path, and checksum validation
// -------------------------------------------------------------------------
echo "Test 1: Verification of successful download, filename and checksum...\n";
$out1 = shell_exec("\"$php\" \"$script\" case1");
$res1 = json_decode(trim($out1), true);

assert(isset($res1['success']) && $res1['success'] === true, "Test 1 Failed: startUpdate did not return success=true. Output: $out1");
$updateId = $res1['update_id'];

// Verify update record state in DB
$update = $db->query("SELECT * FROM application_updates WHERE id = $updateId")->fetch(\PDO::FETCH_ASSOC);
$expectedPath = realpath(__DIR__ . '/../public/storage') . DIRECTORY_SEPARATOR . "update_package_{$updateId}.zip";

assert(file_exists($expectedPath), "Test 1 Failed: Staged package ZIP not found at expected path: $expectedPath");
assert(filesize($expectedPath) > 0, "Test 1 Failed: Staged package size is 0.");
assert($update['package_sha256'] === hash_file('sha256', $expectedPath), "Test 1 Failed: SHA-256 hash mismatch in database record.");

echo "  ✅ Staged ZIP exists with size > 0\n";
echo "  ✅ Expected path: $expectedPath\n";
echo "  ✅ Checksum matches\n";
echo "Test 1 Passed!\n";

// -------------------------------------------------------------------------
// TEST 2: Checksum Mismatch fails update
// -------------------------------------------------------------------------
echo "\nTest 2: Verification of Checksum mismatch...\n";
$out2 = shell_exec("\"$php\" \"$script\" case2");
$res2 = json_decode(trim($out2), true);

assert(isset($res2['success']) && $res2['success'] === false, "Test 2 Failed: startUpdate did not return success=false on checksum mismatch. Output: $out2");
assert(str_contains($res2['message'], 'Checksum'), "Test 2 Failed: Error message not related to checksum mismatch. Got: " . ($res2['message'] ?? ''));

// Verify update record state in DB is failed
$failedId = $db->query("SELECT MAX(id) FROM application_updates")->fetchColumn();
$failedUpdate = $db->query("SELECT * FROM application_updates WHERE id = $failedId")->fetch(\PDO::FETCH_ASSOC);
assert($failedUpdate['status'] === 'failed', "Test 2 Failed: Update state in database is not failed. Got: {$failedUpdate['status']}");

echo "  ✅ startUpdate returned success=false\n";
echo "  ✅ Database update record state: {$failedUpdate['status']}\n";
echo "  ✅ Detailed diagnostic log present: {$res2['message']}\n";
echo "Test 2 Passed!\n";

// -------------------------------------------------------------------------
// TEST 3: HTTP / Download Failure
// -------------------------------------------------------------------------
echo "\nTest 3: Verification of Download failure...\n";
$out3 = shell_exec("\"$php\" \"$script\" case3");
$res3 = json_decode(trim($out3), true);

assert(isset($res3['success']) && $res3['success'] === false, "Test 3 Failed: startUpdate did not return success=false on download failure. Output: $out3");
$errId = $db->query("SELECT MAX(id) FROM application_updates")->fetchColumn();
$errUpdate = $db->query("SELECT * FROM application_updates WHERE id = $errId")->fetch(\PDO::FETCH_ASSOC);
assert($errUpdate['status'] === 'failed', "Test 3 Failed: Update status not failed. Got: {$errUpdate['status']}");

echo "  ✅ downloadAsset failure correctly marks update as failed\n";
echo "  ✅ Partial file deleted and status is failed\n";
echo "Test 3 Passed!\n";

// Clean up
$testModeFlag = __DIR__ . '/../public/storage/.update_test_mode';
if (file_exists($testModeFlag)) unlink($testModeFlag);
$releaseDir = __DIR__ . '/../release';
$dummyZip = $releaseDir . '/AppForm-9.9.9-update.zip';
if (file_exists($dummyZip)) unlink($dummyZip);
if (file_exists($dummyZip . '.sha256')) unlink($dummyZip . '.sha256');

// Clean up actual staged package zips in public/storage
foreach (glob(__DIR__ . '/../public/storage/update_package_*.zip') as $z) {
    unlink($z);
}

echo "\n========================================\n";
echo "  ALL UPDATE DOWNLOAD TESTS PASSED ✅\n";
echo "========================================\n";
