<?php
// Role and Permission Authorization Checks Tests

use App\Core\Auth;
use App\Core\Session;

echo "Running Permission Tests...\n";

// Test 1: Guest permission check
Session::destroy();
assert(Auth::hasPermission('forms.view') === false, "Test 1 Failed: Unauthenticated guest must not have permissions.");
echo "Test 1 Passed: Guest permission checks.\n";

return true;
