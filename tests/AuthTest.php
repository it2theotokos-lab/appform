<?php
// Auth system test script

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Auth;
use App\Core\Database;

echo "--- Running Auth Tests ---\n";

try {
    $db = Database::getInstance();
} catch (\Exception $e) {
    echo "Warning: Database connection not available. Skipping active auth attempts. (Ensure DB is installed via db/install.php)\n";
    return false;
}

// Test 1: Check guest/check status
$isLoggedIn = Auth::check();
assert(is_bool($isLoggedIn), "Test 1 Failed: Auth::check() must return a boolean.");
echo "Test 1 Passed: Guest check.\n";

// Test 2: Attempt with invalid user
$success = Auth::attempt('nonexistent_user_xyz', 'wrongpass');
assert($success === false, "Test 2 Failed: Auth::attempt should fail for invalid user.");
echo "Test 2 Passed: Invalid login rejection.\n";

echo "All Auth Tests Passed!\n";
return true;
