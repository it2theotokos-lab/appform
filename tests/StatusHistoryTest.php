<?php
// Submission Status History tracking tests

use App\Models\SubmissionStatusHistory;

echo "Running StatusHistory Tests...\n";

// Test 1: Model check
assert(class_exists(SubmissionStatusHistory::class) === true, "Test 1 Failed: SubmissionStatusHistory Model must be defined.");
echo "Test 1 Passed: SubmissionStatusHistory Model check.\n";

// Test 2: History joins the actor and both reviewer/submitter views display it.
$modelSource = file_get_contents(__DIR__ . '/../src/Models/SubmissionStatusHistory.php');
$adminView = file_get_contents(__DIR__ . '/../src/Views/admin/submissions/view.php');
$portalView = file_get_contents(__DIR__ . '/../src/Views/portal/submission-view.php');
assert(str_contains($modelSource, 'h.changed_by = u.id'), 'History must join changed_by to the users table.');
assert(str_contains($modelSource, 'user_fullname'), 'History must retrieve the actor full name.');
assert(str_contains($adminView, '$historyActor'), 'Reviewer history must display the responsible user.');
assert(str_contains($portalView, '$historyActor'), 'Submitter history must display the responsible user.');
echo "Test 2 Passed: Status history exposes the responsible user in both views.\n";

return true;
