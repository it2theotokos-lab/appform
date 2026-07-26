<?php
// Submission access permission checks tests

use App\Services\FormAccessService;

echo "Running SubmissionAccess Tests...\n";

// Test 1: Access check on non-existent form
$res1 = FormAccessService::checkSubmissionAccess(null, 99999);
assert($res1['allowed'] === false, "Test 1 Failed: Non-existent form access should be disallowed.");
assert(strpos($res1['reason'], 'φόρμα δεν βρέθηκε') !== false, "Test 1 Failed: Expected form not found message.");
echo "Test 1 Passed: Disallowed access for non-existent form.\n";

// Test 2: Access check structure for valid form
$res2 = FormAccessService::checkSubmissionAccess(null, 1);
assert(isset($res2['allowed']) && isset($res2['reason']), "Test 2 Failed: checkSubmissionAccess should return allowed and reason keys.");
echo "Test 2 Passed: Valid response structure.\n";

return true;
