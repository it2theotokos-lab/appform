<?php
// Session Management tests

use App\Core\Session;

echo "Running Session Tests...\n";

// Test 1: Set and Get Session
Session::set('test_param', 'success_value');
assert(Session::get('test_param') === 'success_value', "Test 1 Failed: Session set/get values mismatch.");
echo "Test 1 Passed: Session Set/Get values.\n";

// Test 2: Flash message
Session::flash('flash_message', 'flash_content');
$msg = Session::flash('flash_message');
assert($msg === 'flash_content', "Test 2 Failed: Flash message retrieval failed.");
assert(Session::flash('flash_message') === null, "Test 2 Failed: Flash message must be cleared after first read.");
echo "Test 2 Passed: Flash message lifecycle.\n";

return true;
