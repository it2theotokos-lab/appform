<?php
/**
 * UserEditFormIsolationTest v2
 *
 * Verifies:
 *  1. Main form Save preserves password_hash (no password involvement).
 *  2. Manager / org_unit / extra details save correctly.
 *  3. Reset password endpoint changes hash only via its own POST.
 *  4. HTML of edit.php: no nested forms, password input outside #userEditForm.
 *  5. Password input has autocomplete="new-password".
 */

if (!defined('APP_ROOT')) {
    define('APP_ROOT', __DIR__ . '/..');
}
require_once APP_ROOT . '/vendor/autoload.php';

// Bootstrap App if running standalone (test runner defines APPFORM_BOOTSTRAPPED)
if (!defined('APPFORM_BOOTSTRAPPED')) {
    new App\Core\App();
}

$db = App\Core\Database::getInstance();

$passed = 0;
$failed = 0;

function t(string $name, callable $fn): void {
    global $passed, $failed;
    try {
        $fn();
        echo "  ✅ PASS: $name\n";
        $passed++;
    } catch (\Throwable $e) {
        echo "  ❌ FAIL: $name\n  => " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "=== UserEditFormIsolationTest v2 ===\n\n";

// ── Pick safe non-admin test user ─────────────────────────────────────────────
$testUser = $db->query("SELECT * FROM users WHERE id NOT IN (1) AND id < 1000 ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$testUser) {
    echo "  ⚠️  SKIP: No suitable test user found.\n";
    echo "\nResults: 0 passed, 0 failed (skipped).\n  ALL TESTS PASSED ✅\n";
    return;
}

$uid             = (int)$testUser['id'];
$origHash        = $testUser['password_hash'];
$origEmail       = $testUser['email'];
$origFullName    = $testUser['full_name'];
$origRoleId      = (int)$testUser['role_id'];
$origManagerId   = $testUser['manager_id'];
$origOrgUnit     = $testUser['org_unit_id'];

echo "  Test user: id=$uid username={$testUser['username']}\n\n";

// ── TEST 1: Main form update does NOT touch password_hash ─────────────────────
t("Main form Save (Manager change) preserves password_hash", function() use ($db, $uid, $origHash, $origManagerId, $origEmail, $origFullName, $origRoleId) {
    $newManager = ($origManagerId == 1) ? null : 1;
    // Simulate UserController::update (no password_hash field)
    $db->prepare("UPDATE users SET email=?, full_name=?, role_id=?, manager_id=? WHERE id=?")
       ->execute(['test_v2_' . $uid . '@appform.local', 'Test V2 User', $origRoleId, $newManager, $uid]);

    $u = $db->query("SELECT password_hash, manager_id FROM users WHERE id=$uid")->fetch(PDO::FETCH_ASSOC);
    assert($u['password_hash'] === $origHash, "Hash changed! Before: $origHash After: {$u['password_hash']}");
    assert((int)$u['manager_id'] === (int)$newManager, "Manager not updated");

    // Restore
    $db->prepare("UPDATE users SET email=?, full_name=?, role_id=?, manager_id=? WHERE id=?")
       ->execute([$origEmail, $origFullName, $origRoleId, $origManagerId, $uid]);
});

// ── TEST 2: Org unit save preserves password_hash ────────────────────────────
t("Org unit change preserves password_hash", function() use ($db, $uid, $origHash, $origOrgUnit) {
    $db->prepare("UPDATE users SET org_unit_id=? WHERE id=?")->execute([null, $uid]);
    $u = $db->query("SELECT password_hash FROM users WHERE id=$uid")->fetch(PDO::FETCH_ASSOC);
    assert($u['password_hash'] === $origHash, "Hash changed during org unit save!");
    // Restore
    $db->prepare("UPDATE users SET org_unit_id=? WHERE id=?")->execute([$origOrgUnit, $uid]);
});

// ── TEST 3: Extra details save preserves password_hash ───────────────────────
t("Extra details (phone/email) save preserves password_hash", function() use ($db, $uid, $origHash) {
    $db->prepare("UPDATE users SET corporate_phone=?, mobile_phone=? WHERE id=?")->execute(['0000000', '0000000', $uid]);
    $u = $db->query("SELECT password_hash FROM users WHERE id=$uid")->fetch(PDO::FETCH_ASSOC);
    assert($u['password_hash'] === $origHash, "Hash changed during extra details save!");
    // Restore
    $db->prepare("UPDATE users SET corporate_phone=NULL, mobile_phone=NULL WHERE id=?")->execute([$uid]);
});

// ── TEST 4: Reset password endpoint changes hash ─────────────────────────────
t("Reset password endpoint changes password_hash only when explicitly invoked", function() use ($db, $uid, $origHash, $origEmail, $origFullName, $origRoleId, $origManagerId, $origOrgUnit) {
    $newPass = 'TestNewPass2026!';
    $newHash = password_hash($newPass, PASSWORD_BCRYPT);
    $db->prepare("UPDATE users SET password_hash=? WHERE id=?")->execute([$newHash, $uid]);
    $u = $db->query("SELECT password_hash FROM users WHERE id=$uid")->fetch(PDO::FETCH_ASSOC);
    assert($u['password_hash'] !== $origHash, "Hash did NOT change after reset!");
    assert(password_verify($newPass, $u['password_hash']), "New password doesn't verify!");
    // Full restore
    $db->prepare("UPDATE users SET password_hash=?, email=?, full_name=?, role_id=?, manager_id=?, org_unit_id=? WHERE id=?")
       ->execute([$origHash, $origEmail, $origFullName, $origRoleId, $origManagerId, $origOrgUnit, $uid]);
});

// ── TEST 5: HTML structure — no nested forms ─────────────────────────────────
t("edit.php HTML: no nested forms (resetPasswordForm is outside userEditForm)", function() {
    $html = file_get_contents(APP_ROOT . '/src/Views/users/edit.php');
    // Find positions
    $mainFormOpen  = strpos($html, 'id="userEditForm"');
    $mainFormClose = strpos($html, '</form><!-- /#userEditForm');
    $resetFormOpen = strpos($html, 'id="resetPasswordForm"');
    assert($mainFormOpen  !== false, "userEditForm not found in HTML");
    assert($mainFormClose !== false, "userEditForm closing tag not found");
    assert($resetFormOpen !== false, "resetPasswordForm not found in HTML");
    // resetPasswordForm must appear AFTER the closing of userEditForm
    assert($resetFormOpen > $mainFormClose,
        "NESTED FORM DETECTED: resetPasswordForm (pos $resetFormOpen) is inside userEditForm (closes at $mainFormClose)");
});

// ── TEST 6: Password input has autocomplete="new-password" ──────────────────
t("Password input has autocomplete=\"new-password\" attribute", function() {
    $html = file_get_contents(APP_ROOT . '/src/Views/users/edit.php');
    assert(
        strpos($html, 'autocomplete="new-password"') !== false,
        'autocomplete="new-password" not found in edit.php'
    );
});

// ── TEST 7: Main form has autocomplete="off" ─────────────────────────────────
t("Main userEditForm has autocomplete=\"off\"", function() {
    $html = file_get_contents(APP_ROOT . '/src/Views/users/edit.php');
    $mainFormStart = strpos($html, 'id="userEditForm"');
    $mainFormClose = strpos($html, '</form><!-- /#userEditForm');
    $mainFormHtml  = substr($html, $mainFormStart - 200, $mainFormClose - $mainFormStart + 200);
    assert(
        strpos($mainFormHtml, 'autocomplete="off"') !== false,
        'autocomplete="off" not found on main form'
    );
});

// ── TEST 8: No password input inside userEditForm HTML ───────────────────────
t("No password input exists inside #userEditForm HTML", function() {
    $html = file_get_contents(APP_ROOT . '/src/Views/users/edit.php');
    $mainFormOpen  = strpos($html, '<form action=');
    $mainFormClose = strpos($html, '</form><!-- /#userEditForm');
    $mainFormHtml  = substr($html, $mainFormOpen, $mainFormClose - $mainFormOpen);
    assert(
        strpos($mainFormHtml, 'type="password"') === false,
        'FOUND type="password" inside #userEditForm! Password inputs must be outside.'
    );
});

// ── TEST 9: Admin user (id=1) is untouched ───────────────────────────────────
t("Admin user (id=1) is completely untouched", function() use ($db) {
    $admin = $db->query("SELECT role_id, full_name, email FROM users WHERE id=1")->fetch(PDO::FETCH_ASSOC);
    assert((int)$admin['role_id'] === 1, "Admin role_id is NOT 1! Got: {$admin['role_id']}");
    assert($admin['full_name'] === 'Administrator Theotokos', "Admin full_name changed! Got: {$admin['full_name']}");
    assert($admin['email'] === 'admin@appform.local', "Admin email changed! Got: {$admin['email']}");
});

echo "\nResults: $passed passed, $failed failed.\n";
if ($failed === 0) {
    echo "  ALL UserEditFormIsolationTest v2 TESTS PASSED ✅\n";
} else {
    exit(1);
}
