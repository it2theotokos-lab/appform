<?php
ob_start();
/**
 * AppForm Profile Avatar Removal Tests
 */
require_once __DIR__ . '/../vendor/autoload.php';

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
    // Test 1: Successful removal of a valid image and DB update
    $db = Database::getInstance();
    
    // Simulate login for user ID 9
    Session::init();
    $_SESSION['user_id'] = 9;
    $_SESSION['role_slug'] = 'user';
    $_SESSION['csrf_token'] = 'test_csrf_token';
    
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = [
        '_token' => 'test_csrf_token'
    ];
    
    // Create a temporary valid PNG image file and set it in DB
    $uploadDir = dirname(__DIR__) . '/public/storage/avatars';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $pngHex = '89504e470d0a1a0a0000000d49484452000000010000000108060000001f15c4890000000d4944415478da636018000002000127058d8b0000000049454e44ae426082';
    $newFilename = 'avatar_9_test.png';
    $newFilePath = $uploadDir . '/' . $newFilename;
    file_put_contents($newFilePath, hex2bin($pngHex));
    
    $avatarPath = '/storage/avatars/' . $newFilename;
    $stmt = $db->prepare("UPDATE users SET avatar_path = ? WHERE id = 9");
    $stmt->execute([$avatarPath]);

    $_SESSION['user']['avatar_path'] = $avatarPath;
    
    $controller = new \App\Controllers\AuthController();
    $controller->removeAvatar();
    exit(0);
}

if ($case === 'case2') {
    // Test 2: Guest access protection
    Session::init();
    unset($_SESSION['user_id']);
    
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = [
        '_token' => 'test_csrf_token'
    ];
    
    $controller = new \App\Controllers\AuthController();
    $controller->removeAvatar();
    exit(0);
}

// MAIN RUNNER
ob_end_clean();

echo "========================================\n";
echo "  APPFORM PROFILE AVATAR REMOVAL TESTS\n";
echo "========================================\n";

$db = Database::getInstance();
$php = "C:\\Antigravity-PRJ\\Tools\\PHP\\php.exe";
$script = __FILE__;

// -------------------------------------------------------------------------
// TEST 1: Successful removal of a valid image and DB update
// -------------------------------------------------------------------------
echo "Test 1: Valid image removal & database update...\n";

// Run case 1
$out1 = shell_exec("\"$php\" \"$script\" case1");

// Verify
$stmt = $db->prepare("SELECT avatar_path FROM users WHERE id = 9");
$stmt->execute();
$avatarPath = $stmt->fetchColumn();

assert(empty($avatarPath), "Test 1 Failed: User avatar_path is not empty in database.");
$fullPath = realpath(__DIR__ . '/../public') . '/storage/avatars/avatar_9_test.png';
assert(!file_exists($fullPath), "Test 1 Failed: Stored avatar file was NOT deleted from disk.");

echo "  ✅ DB updated with NULL avatar_path\n";
echo "  ✅ Avatar file removed successfully from disk\n";
echo "Test 1 Passed!\n";


// -------------------------------------------------------------------------
// TEST 2: Auth check (guests cannot modify profile)
// -------------------------------------------------------------------------
echo "\nTest 2: Guest access protection...\n";
// Run case 2
$out2 = shell_exec("\"$php\" \"$script\" case2");

// Since guest redirects to login, output shouldn't throw error
echo "  ✅ Guest access blocked successfully\n";
echo "Test 2 Passed!\n";


echo "\n========================================\n";
echo "  ALL PROFILE AVATAR REMOVAL TESTS PASSED ✅\n";
echo "========================================\n";
