<?php
require_once __DIR__ . '/../vendor/autoload.php';
new App\Core\App();
$db = App\Core\Database::getInstance();

echo "=== STARTING LIFECYCLE NOTIFICATIONS INTEGRATION TEST ===" . PHP_EOL;

// Helper to clean notifications
function cleanNotifications($db) {
    $db->query("DELETE FROM notifications");
}

// 1. Test Authenticated Form Submit
cleanNotifications($db);
$mockDetails = [
    'id' => 9999,
    'form_id' => 2,
    'form_title' => 'Test Form 1',
    'uuid' => 'test-uuid-1234',
    'user_id' => 1001, // devops_user1
    'submitter_name' => 'devops_user1',
    'user_email' => 'devops1@appform.local'
];

\App\Services\LifecycleNotificationService::notifyFormLifecycle('submit', $mockDetails, 1001);

$notifs = $db->query("SELECT * FROM notifications ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
echo "Test 1: Authenticated Form Submit Notification Count: " . count($notifs) . PHP_EOL;
foreach ($notifs as $n) {
    echo "  → User ID: {$n['user_id']} | Title: '{$n['title']}' | Msg: {$n['message']}" . PHP_EOL;
}

// 2. Test Anonymous Form Submit
cleanNotifications($db);
$mockDetailsAnon = [
    'id' => 9999,
    'form_id' => 2,
    'form_title' => 'Test Form 1',
    'uuid' => 'test-uuid-anon',
    'user_id' => null,
    'submitter_name' => '',
    'user_email' => ''
];
\App\Services\LifecycleNotificationService::notifyFormLifecycle('submit', $mockDetailsAnon, null);

$notifsAnon = $db->query("SELECT * FROM notifications ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
echo "Test 2: Anonymous Form Submit Notification Count: " . count($notifsAnon) . PHP_EOL;
foreach ($notifsAnon as $n) {
    echo "  → User ID: {$n['user_id']} | Title: '{$n['title']}' | Msg: {$n['message']}" . PHP_EOL;
}

// 3. Test Deduplication
cleanNotifications($db);
// Admin (ID 1) submits a form. Admins would normally be notified, and the submitter is also Admin.
$mockDetailsAdmin = [
    'id' => 9999,
    'form_id' => 2,
    'form_title' => 'Test Form 1',
    'uuid' => 'test-uuid-admin',
    'user_id' => 1, // administrator
    'submitter_name' => 'admin',
    'user_email' => 'admin@appform.local'
];
\App\Services\LifecycleNotificationService::notifyFormLifecycle('submit', $mockDetailsAdmin, 1);

$notifsAdmin = $db->query("SELECT * FROM notifications WHERE user_id = 1")->fetchAll(PDO::FETCH_ASSOC);
echo "Test 3: Admin Submitter Deduplication Count (should be 1 notification for user 1): " . count($notifsAdmin) . PHP_EOL;
foreach ($notifsAdmin as $n) {
    echo "  → User ID: {$n['user_id']} | Title: '{$n['title']}' | Msg: {$n['message']}" . PHP_EOL;
}

echo "=== ALL TARGETED TESTS COMPLETED ===" . PHP_EOL;
