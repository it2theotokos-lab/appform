<?php

echo "Running DailySubmissionCorrection Tests...\n";

$schema = file_get_contents(__DIR__ . '/../db/schema.sql');
$migration = file_get_contents(__DIR__ . '/../db/migrations/040_daily_submission_correction_requests.sql');
$statusMigration = file_get_contents(__DIR__ . '/../db/migrations/041_correction_request_status.sql');
$recoveryMigration = file_get_contents(__DIR__ . '/../db/migrations/042_enforce_correction_request_status.sql');
$availability = file_get_contents(__DIR__ . '/../src/Services/FormAvailabilityService.php');
$controller = file_get_contents(__DIR__ . '/../src/controllers/SubmissionController.php');
$routes = file_get_contents(__DIR__ . '/../public/index.php');
$portalView = file_get_contents(__DIR__ . '/../src/Views/portal/submission-view.php');
$reviewView = file_get_contents(__DIR__ . '/../src/Views/admin/submissions/view.php');

assert(str_contains($schema, 'daily_submission_enabled'));
assert(str_contains($migration, 'submission_correction_requests'));
assert(str_contains($statusMigration, "'correction_requested'"));
assert(str_contains($recoveryMigration, "'correction_requested'"));
assert(str_contains($recoveryMigration, 'original_status'));
assert(str_contains($availability, "return 'already_submitted_today'"));
assert(str_contains($availability, "'correction_requested'"));
assert(str_contains($controller, 'requestCorrection'));
assert(str_contains($controller, 'approveCorrectionRequest'));
assert(str_contains($controller, 'rejectCorrectionRequest'));
assert(str_contains($controller, 'original_status'));
assert(str_contains($controller, 'EmailService::sendEmail'));
assert(str_contains($routes, '/correction-request'));
assert(str_contains($portalView, 'Αποστολή αιτήματος στον reviewer'));
assert(str_contains($reviewView, 'Έγκριση και άνοιγμα'));

echo "DailySubmissionCorrection Tests passed.\n";
return true;
