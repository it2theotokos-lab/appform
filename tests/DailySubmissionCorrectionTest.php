<?php

echo "Running DailySubmissionCorrection Tests...\n";

$schema = file_get_contents(__DIR__ . '/../db/schema.sql');
$migration = file_get_contents(__DIR__ . '/../db/migrations/040_daily_submission_correction_requests.sql');
$statusMigration = file_get_contents(__DIR__ . '/../db/migrations/041_correction_request_status.sql');
$recoveryMigration = file_get_contents(__DIR__ . '/../db/migrations/042_enforce_correction_request_status.sql');
$backfillMigration = file_get_contents(__DIR__ . '/../db/migrations/043_backfill_pending_correction_requests.sql');
$notificationMigration = file_get_contents(__DIR__ . '/../db/migrations/044_notification_sender.sql');
$notificationBackfillMigration = file_get_contents(__DIR__ . '/../db/migrations/045_backfill_correction_notification_senders.sql');
$availability = file_get_contents(__DIR__ . '/../src/Services/FormAvailabilityService.php');
$controller = file_get_contents(__DIR__ . '/../src/controllers/SubmissionController.php');
$formController = file_get_contents(__DIR__ . '/../src/controllers/FormController.php');
$notificationService = file_get_contents(__DIR__ . '/../src/Services/NotificationService.php');
$notificationController = file_get_contents(__DIR__ . '/../src/controllers/NotificationController.php');
$notificationView = file_get_contents(__DIR__ . '/../src/Views/notifications/index.php');
$routes = file_get_contents(__DIR__ . '/../public/index.php');
$portalView = file_get_contents(__DIR__ . '/../src/Views/portal/submission-view.php');
$reviewView = file_get_contents(__DIR__ . '/../src/Views/admin/submissions/view.php');

assert(str_contains($schema, 'daily_submission_enabled'));
assert(str_contains($migration, 'submission_correction_requests'));
assert(str_contains($statusMigration, "'correction_requested'"));
assert(str_contains($recoveryMigration, "'correction_requested'"));
assert(str_contains($recoveryMigration, 'original_status'));
assert(str_contains($backfillMigration, "s.status = 'correction_requested'"));
assert(str_contains($notificationMigration, 'sender_user_id'));
assert(str_contains($notificationBackfillMigration, 'sender_user_id'));
assert(str_contains($availability, "return 'already_submitted_today'"));
assert(str_contains($availability, "'correction_requested'"));
assert(str_contains($controller, 'requestCorrection'));
assert(str_contains($controller, 'approveCorrectionRequest'));
assert(str_contains($controller, 'rejectCorrectionRequest'));
assert(str_contains($controller, 'original_status'));
assert(str_contains($controller, 'EmailService::sendEmail'));
assert(str_contains($controller, "['draft', 'returned']"));
assert(str_contains($controller, "'Νέο αίτημα διόρθωσης από '"));
assert(str_contains($routes, '/correction-request'));
assert(str_contains($routes, '/notifications/delete-all'));
assert(str_contains($formController, '!$existingSub'));
assert(str_contains($notificationService, 'getNotificationCount'));
assert(str_contains($notificationService, 'deleteNotification'));
assert(str_contains($notificationService, 'WHERE n.user_id = :user_id'));
assert(str_contains($notificationController, 'deleteAllUserNotifications'));
assert(str_contains($notificationView, 'Διαγραφή όλων'));
assert(str_contains($portalView, 'Αποστολή αιτήματος στον reviewer'));
assert(str_contains($reviewView, 'Έγκριση και άνοιγμα'));

echo "DailySubmissionCorrection Tests passed.\n";
return true;
