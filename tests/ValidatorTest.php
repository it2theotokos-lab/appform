<?php
// Input Validator test script

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Validator;

echo "--- Running Validator Tests ---\n";

// Test 1: Required validation
$v1 = new Validator(['username' => '']);
$v1->validate(['username' => ['required']]);
assert($v1->passed() === false, "Test 1 Failed: Required validation passed on empty value.");
echo "Test 1 Passed: Required check validation.\n";

// Test 2: Email validation
$v2 = new Validator(['email' => 'invalid-email-address']);
$v2->validate(['email' => ['email']]);
assert($v2->passed() === false, "Test 2 Failed: Email validation passed on malformed email.");
echo "Test 2 Passed: Email check validation.\n";

// Test 3: Valid JSON checks
$v3 = new Validator(['schema' => '{"version": 1, "fields": []}']);
$v3->validate(['schema' => ['json']]);
assert($v3->passed() === true, "Test 3 Failed: Valid JSON check rejected correct json syntax.");
echo "Test 3 Passed: JSON check validation.\n";

echo "All Validator Tests Passed!\n";
return true;
