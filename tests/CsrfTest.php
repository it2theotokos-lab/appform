<?php
// CSRF Validation Tests

use App\Core\Csrf;
use App\Core\Session;

echo "Running CSRF Tests...\n";

// Test 1: Generate Token
$token = Csrf::token();
assert(strlen($token) === 64, "Test 1 Failed: CSRF Token must be 64 characters.");
echo "Test 1 Passed: CSRF token generation.\n";

// Test 2: Field Injection html
$field = Csrf::field();
assert(str_contains($field, 'type="hidden"'), "Test 2 Failed: Csrf field must contain a hidden input.");
echo "Test 2 Passed: CSRF form field injection.\n";

// Test 3: POST verification
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['_token'] = $token;
assert(Csrf::validate() === true, "Test 3 Failed: Valid CSRF token validation failed.");
echo "Test 3 Passed: CSRF validation matches.\n";

return true;
