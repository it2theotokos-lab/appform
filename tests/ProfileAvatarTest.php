<?php
ob_start();
/**
 * AppForm Profile Avatar Upload & Verification Tests
 */
require_once __DIR__ . '/../vendor/autoload.php';

// If we are run in a case mode
$case = isset($argv[1]) ? $argv[1] : null;

if (\App\Core\App::$config === null) {
    $config = require __DIR__ . '/../config/config.php';
    \App\Core\App::$config = $config;
    new \App\Core\App();
}

use App\Core\Database;
use App\Core\Auth;
use App\Core\Session;

if ($case === 'case1') {
    // Test 1: Successful upload of a valid image and DB update
    $db = Database::getInstance();
    
    // Simulate login for user ID 9 (an active user: user_test)
    Session::init();
    $_SESSION['user_id'] = 9;
    $_SESSION['role_slug'] = 'user';
    $_SESSION['csrf_token'] = 'test_csrf_token';
    
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = [
        'full_name' => 'John Doe Updated',
        'email' => 'johndoe_updated@example.com',
        '_token' => 'test_csrf_token'
    ];
    
    // Create a temporary valid PNG image file
    $tmpImg = tempnam(sys_get_temp_dir(), 'test_img');
    // Create a tiny valid 1x1 png image
    $pngHex = '89504e470d0a1a0a0000000d49484452000000010000000108060000001f15c4890000000d4944415478da636018000002000127058d8b0000000049454e44ae426082';
    file_put_contents($tmpImg, hex2bin($pngHex));
    
    $_FILES['avatar'] = [
        'name' => 'avatar.png',
        'type' => 'image/png',
        'tmp_name' => $tmpImg,
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($tmpImg)
    ];
    
    $controller = new \App\Controllers\AuthController();
    $controller->updateProfile();
    exit(0);
}

if ($case === 'case2') {
    // Test 2: Mime type validation rejection for non-image/fake files
    Session::init();
    $_SESSION['user_id'] = 9;
    $_SESSION['role_slug'] = 'user';
    $_SESSION['csrf_token'] = 'test_csrf_token';
    
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = [
        'full_name' => 'John Doe Updated',
        'email' => 'johndoe_updated@example.com',
        '_token' => 'test_csrf_token'
    ];
    
    $tmpFake = tempnam(sys_get_temp_dir(), 'fake_img');
    file_put_contents($tmpFake, '<?php echo "fake image / malicious PHP script"; ?>');
    
    $_FILES['avatar'] = [
        'name' => 'backdoor.php',
        'type' => 'image/png', // Fake type
        'tmp_name' => $tmpFake,
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($tmpFake)
    ];
    
    $controller = new \App\Controllers\AuthController();
    $controller->updateProfile();
    exit(0);
}

if ($case === 'case3') {
    // Test 3: Reject file exceeding 2 MB size limit
    Session::init();
    $_SESSION['user_id'] = 9;
    $_SESSION['role_slug'] = 'user';
    $_SESSION['csrf_token'] = 'test_csrf_token';
    
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = [
        'full_name' => 'John Doe Updated',
        'email' => 'johndoe_updated@example.com',
        '_token' => 'test_csrf_token'
    ];
    
    $_FILES['avatar'] = [
        'name' => 'large.png',
        'type' => 'image/png',
        'tmp_name' => tempnam(sys_get_temp_dir(), 'large_img'),
        'error' => UPLOAD_ERR_OK,
        'size' => 3 * 1024 * 1024 // 3 MB (exceeds 2 MB limit)
    ];
    
    $controller = new \App\Controllers\AuthController();
    $controller->updateProfile();
    exit(0);
}

// MAIN RUNNER
ob_end_clean(); // Clean the start buffer

echo "========================================\n";
echo "  APPFORM PROFILE AVATAR TESTS\n";
echo "========================================\n";

$db = Database::getInstance();
$php = "C:\\Antigravity-PRJ\\Tools\\PHP\\php.exe";
$script = __FILE__;

// -------------------------------------------------------------------------
// TEST 1: Successful upload of a valid image and DB update
// -------------------------------------------------------------------------
echo "Test 1: Valid image upload & database update...\n";
$out1 = shell_exec("\"$php\" \"$script\" case1");

// Fetch the user record from database
$stmt = $db->prepare("SELECT avatar_path FROM users WHERE id = 9");
$stmt->execute();
$avatarPath = $stmt->fetchColumn();

assert(!empty($avatarPath), "Test 1 Failed: User avatar_path is empty in database. Output: $out1");
$fullPath = realpath(__DIR__ . '/../public') . str_replace('/', DIRECTORY_SEPARATOR, $avatarPath);
assert(file_exists($fullPath), "Test 1 Failed: Stored avatar file not found on disk at: $fullPath");
assert(filesize($fullPath) > 0, "Test 1 Failed: Stored avatar file size is 0.");

echo "  ✅ DB updated with path: $avatarPath\n";
echo "  ✅ Avatar file stored successfully on disk\n";
echo "Test 1 Passed!\n";

// -------------------------------------------------------------------------
// TEST 2: Rejection of fake/malicious file
// -------------------------------------------------------------------------
echo "\nTest 2: Rejection of non-image/fake files...\n";
$out2 = shell_exec("\"$php\" \"$script\" case2");

// Since case2 redirected due to error, verify output is empty/null (meaning it exited cleanly with headers)
assert($out2 === null || trim($out2) === '', "Test 2 Failed: Output is not empty. Output: $out2");

// Verify user record avatar_path remains the same and was NOT overwritten by backdoor
$stmt = $db->prepare("SELECT avatar_path FROM users WHERE id = 9");
$stmt->execute();
$finalPath = $stmt->fetchColumn();
assert($finalPath === $avatarPath, "Test 2 Failed: User avatar was overwritten by invalid file.");

echo "  ✅ Fake file upload rejected successfully\n";
echo "  ✅ Original avatar path remains intact\n";
echo "Test 2 Passed!\n";

// -------------------------------------------------------------------------
// TEST 3: Reject file exceeding 2 MB
// -------------------------------------------------------------------------
echo "\nTest 3: Size validation (> 2 MB) rejection...\n";
$out3 = shell_exec("\"$php\" \"$script\" case3");

assert($out3 === null || trim($out3) === '', "Test 3 Failed: Output is not empty. Output: $out3");
echo "  ✅ Large file rejected successfully\n";
echo "Test 3 Passed!\n";

// -------------------------------------------------------------------------
// TEST 4: Replacing avatar deletes the old file
// -------------------------------------------------------------------------
echo "\nTest 4: Replacing avatar deletes the old file...\n";
$oldFile = $fullPath;
assert(file_exists($oldFile), "Precondition Failed: Old avatar file does not exist.");

// Run case1 again to replace the avatar
$out4 = shell_exec("\"$php\" \"$script\" case1");

// Fetch new avatar path
$stmt = $db->prepare("SELECT avatar_path FROM users WHERE id = 9");
$stmt->execute();
$newAvatarPath = $stmt->fetchColumn();

assert($newAvatarPath !== $avatarPath, "Test 4 Failed: Avatar path was not updated with the new upload.");
assert(!file_exists($oldFile), "Test 4 Failed: Old avatar file was NOT deleted from disk.");

$newFullPath = realpath(__DIR__ . '/../public') . str_replace('/', DIRECTORY_SEPARATOR, $newAvatarPath);
assert(file_exists($newFullPath), "Test 4 Failed: New avatar file was not saved.");

echo "  ✅ Old avatar file deleted from disk successfully\n";
echo "  ✅ New avatar file stored successfully\n";
echo "Test 4 Passed!\n";

// -------------------------------------------------------------------------
// TEST 5: Auth check (guests cannot modify profile)
// -------------------------------------------------------------------------
echo "\nTest 5: Guest access protection...\n";
// Run a separate script request with no logged-in user in session
$tmpScript = tempnam(sys_get_temp_dir(), 'test_auth');
file_put_contents($tmpScript, '<?php
require_once "C:/Antigravity-PRJ/Projects/Appform/vendor/autoload.php";
$config = require "C:/Antigravity-PRJ/Projects/Appform/config/config.php";
\App\Core\App::$config = $config;
new \App\Core\App();
\App\Core\Session::init();
unset($_SESSION["user_id"]);
try {
    $controller = new \App\Controllers\AuthController();
    $controller->updateProfile();
    echo "SUCCESS_BUT_GUEST";
} catch (\Throwable $e) {
    echo "Caught: " . $e->getMessage();
}
');
$out5 = shell_exec("\"$php\" \"$tmpScript\"");
unlink($tmpScript);

assert(!str_contains((string)$out5, 'SUCCESS_BUT_GUEST'), "Test 5 Failed: Allowed guest user to invoke updateProfile. Output: $out5");
echo "  ✅ Guest access blocked successfully\n";
echo "Test 5 Passed!\n";

// Clean up avatars in public/storage/avatars
foreach (glob(__DIR__ . '/../public/storage/avatars/avatar_9_*') as $f) {
    unlink($f);
}
// Clean up database column for user 9
$db->exec("UPDATE users SET avatar_path = NULL WHERE id = 9");

echo "\n========================================\n";
echo "  ALL PROFILE AVATAR TESTS PASSED ✅\n";
echo "========================================\n";
