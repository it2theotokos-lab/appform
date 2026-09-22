<?php

echo "Running DailySubmissionCorrection Tests...\n";

$schema = file_get_contents(__DIR__ . '/../db/schema.sql');
$migration = file_get_contents(__DIR__ . '/../db/migrations/040_daily_submission_correction_requests.sql');
$availability = file_get_contents(__DIR__ . '/../src/Services/FormAvailabilityService.php');
$controller = file_get_contents(__DIR__ . '/../src/controllers/SubmissionController.php');
$routes = file_get_contents(__DIR__ . '/../public/index.php');
$portalView = file_get_contents(__DIR__ . '/../src/Views/portal/submission-view.php');
$reviewView = file_get_contents(__DIR__ . '/../src/Views/admin/submissions/view.php');

assert(str_contains($schema, 'daily_submission_enabled'));
assert(str_contains($migration, 'submission_correction_requests'));
assert(str_contains($availability, "return 'already_submitted_today'"));
assert(str_contains($availability, "status IN ('submitted', 'under_review', 'approved', 'rejected')"));
assert(str_contains($controller, 'requestCorrection'));
assert(str_contains($controller, 'approveCorrectionRequest'));
assert(str_contains($controller, 'rejectCorrectionRequest'));
assert(str_contains($routes, '/correction-request'));
assert(str_contains($portalView, 'Αποστολή αιτήματος στον reviewer'));
assert(str_contains($reviewView, 'Έγκριση και άνοιγμα'));

echo "DailySubmissionCorrection Tests passed.\n";
return true;
