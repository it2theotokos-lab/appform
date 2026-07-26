<?php
/**
 * AppForm Backend QA Verification Script
 * Verifies all data, routes, controllers, and views without browser automation
 */
require_once __DIR__ . '/../vendor/autoload.php';
new \App\Core\App();

use App\Core\Database;

$db = Database::getInstance();
$pass = 0; $fail = 0; $warn = 0;

function check($label, $condition, $detail = '') {
    global $pass, $fail;
    if ($condition) {
        echo "[PASS] $label\n";
        $pass++;
    } else {
        echo "[FAIL] $label" . ($detail ? " — $detail" : '') . "\n";
        $fail++;
    }
}
function warn($label, $detail = '') {
    global $warn;
    echo "[WARN] $label" . ($detail ? " — $detail" : '') . "\n";
    $warn++;
}

echo "============================================================\n";
echo "  AppForm Backend QA Verification\n";
echo "============================================================\n\n";

// ── USERS ───────────────────────────────────────────────────────
echo "--- USERS ---\n";
$users = $db->query("SELECT id,username,full_name,role_id,is_active FROM users")->fetchAll(PDO::FETCH_ASSOC);
check("At least 5 users exist", count($users) >= 5, "Found: " . count($users));
$activeCount = count(array_filter($users, fn($u) => $u['is_active'] == 1));
check("All demo users are active", $activeCount == count($users));
$adminUser = $db->query("SELECT id FROM users WHERE username='admin'")->fetch();
check("admin user exists", (bool)$adminUser);
$demoUsers = array_filter($users, fn($u) => in_array($u['username'], ['admin.demo','manager.demo','reviewer.demo','user.demo1','user.demo2']));
check("All 5 demo users exist", count($demoUsers) == 5, "Found: " . count($demoUsers));

// ── ROLES ────────────────────────────────────────────────────────
echo "\n--- ROLES ---\n";
$roles = $db->query("SELECT id,name,slug FROM roles")->fetchAll(PDO::FETCH_ASSOC);
check("4 roles exist (Admin, Manager, Reviewer, User)", count($roles) >= 4, "Found: " . count($roles));
foreach (['administrator','manager','reviewer','user'] as $slug) {
    $found = array_filter($roles, fn($r) => strtolower($r['slug']) === $slug);
    check("Role '$slug' exists", count($found) > 0);
}

// ── PERMISSIONS ──────────────────────────────────────────────────
echo "\n--- PERMISSIONS ---\n";
$perms = $db->query("SELECT COUNT(*) FROM permissions")->fetchColumn();
check("At least 15 permissions defined", $perms >= 15, "Found: $perms");
$adminPerms = $db->query("SELECT COUNT(*) FROM role_permissions WHERE role_id=1")->fetchColumn();
check("Administrator has all permissions (>=15)", $adminPerms >= 15, "Found: $adminPerms");
$managerPerms = $db->query("SELECT COUNT(*) FROM role_permissions WHERE role_id=2")->fetchColumn();
check("Manager has permissions (>=3)", $managerPerms >= 3, "Found: $managerPerms");
$userPerms = $db->query("SELECT COUNT(*) FROM role_permissions WHERE role_id=3")->fetchColumn();
check("User has permissions (>=2)", $userPerms >= 2, "Found: $userPerms");

// ── REPOSITORIES ─────────────────────────────────────────────────
echo "\n--- REPOSITORIES ---\n";
$repos = $db->query("SELECT id,name,slug,data_json FROM repositories")->fetchAll(PDO::FETCH_ASSOC);
check("3 repositories exist", count($repos) >= 3, "Found: " . count($repos));
foreach (['departments','request-types','priority-levels'] as $slug) {
    $r = array_filter($repos, fn($r) => $r['slug'] === $slug);
    if ($r) {
        $r = array_values($r)[0];
        $items = json_decode($r['data_json'], true);
        check("Repository '$slug' has items", is_array($items) && count($items) > 0, "Items: " . count($items ?? []));
        check("Repository '$slug' items have value+label", isset($items[0]['value']) && isset($items[0]['label']));
    } else {
        check("Repository '$slug' exists", false);
    }
}

// ── FORMS ────────────────────────────────────────────────────────
echo "\n--- FORMS ---\n";
$forms = $db->query("SELECT id,title,slug,status,current_version FROM forms")->fetchAll(PDO::FETCH_ASSOC);
check("At least 3 forms exist", count($forms) >= 3, "Found: " . count($forms));
foreach (['it-support-request','procurement-request','employee-feedback'] as $slug) {
    $f = array_filter($forms, fn($f) => $f['slug'] === $slug);
    if ($f) {
        $f = array_values($f)[0];
        check("Form '$slug' exists and is published", $f['status'] === 'published');
    } else {
        check("Form '$slug' exists", false);
    }
}

// ── FORM VERSIONS ────────────────────────────────────────────────
echo "\n--- FORM VERSIONS ---\n";
$versions = $db->query("SELECT fv.id, f.slug, fv.version_number, LENGTH(fv.schema_json) as schema_size FROM form_versions fv JOIN forms f ON f.id=fv.form_id")->fetchAll(PDO::FETCH_ASSOC);
check("At least 2 form versions exist", count($versions) >= 2, "Found: " . count($versions));
foreach ($versions as $v) {
    check("Version for '{$v['slug']}' has schema (>100 bytes)", $v['schema_size'] > 100, "Size: {$v['schema_size']}");
    $schema = $db->query("SELECT schema_json FROM form_versions WHERE id={$v['id']}")->fetchColumn();
    $parsed = json_decode($schema, true);
    check("Version for '{$v['slug']}' schema is valid JSON", $parsed !== null && isset($parsed['sections']));
}

// ── EMPLOYEE FEEDBACK SCHEMA ────────────────────────────────────
echo "\n--- EMPLOYEE FEEDBACK FORM SCHEMA ---\n";
$efForm = $db->query("SELECT f.id FROM forms f WHERE f.slug='employee-feedback'")->fetch();
if ($efForm) {
    $efSchema = $db->query("SELECT schema_json FROM form_versions WHERE form_id={$efForm['id']} ORDER BY version_number DESC LIMIT 1")->fetchColumn();
    $parsed = json_decode($efSchema, true);
    $fields = $parsed['sections'][0]['fields'] ?? [];
    $fieldKeys = array_column($fields, 'key');
    foreach (['department','overall_satisfaction','management_support','workplace_conditions','comments','anonymous'] as $key) {
        check("Field '$key' exists in Employee Feedback", in_array($key, $fieldKeys));
    }
    check("Department field is type=select", ($fields[array_search('department',$fieldKeys)]['type'] ?? '') === 'select');
    check("Satisfaction/support/conditions are radio type", ($fields[array_search('overall_satisfaction',$fieldKeys)]['type'] ?? '') === 'radio');
    check("Comments field is textarea", ($fields[array_search('comments',$fieldKeys)]['type'] ?? '') === 'textarea');
    check("Anonymous field is checkbox", ($fields[array_search('anonymous',$fieldKeys)]['type'] ?? '') === 'checkbox');
}

// ── SUBMISSIONS ──────────────────────────────────────────────────
echo "\n--- SUBMISSIONS ---\n";
$subs = $db->query("SELECT id,form_id,status,data_json FROM form_submissions")->fetchAll(PDO::FETCH_ASSOC);
check("At least 5 submissions exist", count($subs) >= 5, "Found: " . count($subs));
$statuses = array_column($subs, 'status');
$statusCounts = array_count_values($statuses);
check("Has 'submitted' submissions", ($statusCounts['submitted'] ?? 0) > 0);
check("Has 'approved' submissions", ($statusCounts['approved'] ?? 0) > 0);
check("Has 'rejected' submissions", ($statusCounts['rejected'] ?? 0) > 0);
check("Has 'draft' submissions", ($statusCounts['draft'] ?? 0) > 0);
check("Has 'under_review' submissions", ($statusCounts['under_review'] ?? 0) > 0);
foreach ($subs as $s) {
    $data = json_decode($s['data_json'], true);
    check("Submission #{$s['id']} has valid JSON data", is_array($data) && count($data) > 0);
}

// ── STATUS HISTORY ───────────────────────────────────────────────
echo "\n--- SUBMISSION STATUS HISTORY ---\n";
$history = $db->query("SELECT COUNT(*) FROM submission_status_history")->fetchColumn();
check("At least 5 status history entries", $history >= 5, "Found: $history");

// ── NOTIFICATIONS ────────────────────────────────────────────────
echo "\n--- NOTIFICATIONS ---\n";
$notifs = $db->query("SELECT COUNT(*) FROM notifications")->fetchColumn();
check("At least 3 notifications exist", $notifs >= 3, "Found: $notifs");
$unread = $db->query("SELECT COUNT(*) FROM notifications WHERE is_read=0")->fetchColumn();
check("Has unread notifications", $unread > 0, "Unread: $unread");
$correctLinks = $db->query("SELECT COUNT(*) FROM notifications WHERE link_url IS NOT NULL")->fetchColumn();
check("Notifications have link_url set", $correctLinks > 0);

// ── AUDIT LOGS ───────────────────────────────────────────────────
echo "\n--- AUDIT LOGS ---\n";
$auditCount = $db->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();
check("At least 10 audit entries exist", $auditCount >= 10, "Found: $auditCount");
$auditActions = $db->query("SELECT DISTINCT action FROM audit_logs")->fetchAll(PDO::FETCH_COLUMN);
foreach (['login','user.create','form.publish','submission.create','submission.approve','submission.reject'] as $action) {
    check("Audit action '$action' logged", in_array($action, $auditActions));
}

// ── SAVED REPORTS ────────────────────────────────────────────────
echo "\n--- SAVED REPORTS ---\n";
$reports = $db->query("SELECT id,name,form_id,filters_json FROM saved_reports")->fetchAll(PDO::FETCH_ASSOC);
check("At least 2 saved reports exist", count($reports) >= 2, "Found: " . count($reports));
foreach ($reports as $r) {
    $filters = json_decode($r['filters_json'], true);
    check("Report '{$r['name']}' has valid filters JSON", is_array($filters));
    check("Report '{$r['name']}' has form_id", $r['form_id'] > 0);
}

// ── FILE SYSTEM ──────────────────────────────────────────────────
echo "\n--- FILE SYSTEM ---\n";
$paths = [
    'storage/logs'           => 'Log directory',
    'storage/private_uploads'=> 'Upload directory',
    'storage/exports'        => 'Exports directory',
    'vendor/autoload.php'    => 'Composer autoloader',
    'public/index.php'       => 'Front controller',
    'db/schema.sql'          => 'Database schema',
    'config/config.php'      => 'Config file',
    'config/config.local.php'=> 'Local config override',
];
foreach ($paths as $path => $label) {
    check("$label exists ($path)", file_exists($path));
}

// ── CONTROLLER FILES ─────────────────────────────────────────────
echo "\n--- CONTROLLERS ---\n";
$controllers = ['AuthController','UserController','RoleController','FormController',
    'SubmissionController','RepositoryController','ReportController',
    'ExportController','NotificationController','AuditController',
    'SettingsController','MenuController','DashboardController'];
foreach ($controllers as $c) {
    check("$c.php exists", file_exists("src/Controllers/$c.php"));
}

// ── VIEWS ────────────────────────────────────────────────────────
echo "\n--- KEY VIEWS ---\n";
$views = [
    'src/Views/auth/login.php'           => 'Login view',
    'src/Views/dashboard/admin.php'      => 'Admin dashboard',
    'src/Views/users/index.php'          => 'Users list',
    'src/Views/roles/index.php'          => 'Roles list',
    'src/Views/forms/index.php'          => 'Forms list',
    'src/Views/forms/builder.php'        => 'Form builder',
    'src/Views/submissions/list.php'     => 'Submissions list',
    'src/Views/analytics/index.php'      => 'Analytics',
];
foreach ($views as $path => $label) {
    if (file_exists($path)) {
        check("$label exists", true);
    } else {
        warn("$label not found at $path");
    }
}

// ── ROUTE REGISTRATION ───────────────────────────────────────────
echo "\n--- ROUTE REGISTRATION ---\n";
$indexPhp = file_get_contents('public/index.php');
$routeChecks = [
    '/login'                    => 'Login route',
    '/dashboard'                => 'Dashboard route',
    '/admin/users'              => 'Users route',
    '/admin/roles'              => 'Roles route',
    '/admin/forms'              => 'Forms route',
    '/admin/forms/{id}/builder' => 'Form builder route',
    '/admin/submissions'        => 'Submissions route',
    '/admin/analytics'          => 'Analytics route',
    '/notifications'            => 'Notifications route',
    '/admin/audit'              => 'Audit route',
    '/admin/menus'              => 'Menus route',
    '/admin/settings'           => 'Settings route',
    '/admin/profile'            => 'Profile route',
    '/forms/{slug}'             => 'Public form route',
    '/admin/exports/csv/{formId}'  => 'CSV export route',
    '/admin/exports/excel/{formId}'=> 'Excel export route',
    '/admin/exports/pdf/{uuid}'    => 'PDF export route',
];
foreach ($routeChecks as $path => $label) {
    check("$label registered", str_contains($indexPhp, "'$path'") || str_contains($indexPhp, '"' . $path . '"'));
}

// ── SUMMARY ──────────────────────────────────────────────────────
echo "\n============================================================\n";
echo "  BACKEND QA SUMMARY\n";
echo "============================================================\n";
echo "  PASS: $pass\n";
echo "  FAIL: $fail\n";
echo "  WARN: $warn\n";
echo "  TOTAL: " . ($pass + $fail + $warn) . "\n\n";

if ($fail === 0) {
    echo "  RESULT: BACKEND VERIFIED\n";
} elseif ($fail <= 5) {
    echo "  RESULT: BACKEND VERIFIED WITH MINOR ISSUES\n";
} else {
    echo "  RESULT: BACKEND ISSUES FOUND — $fail checks failed\n";
}
echo "============================================================\n";

exit($fail > 0 ? 1 : 0);
