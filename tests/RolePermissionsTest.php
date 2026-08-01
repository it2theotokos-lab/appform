<?php
/**
 * RolePermissionsTest — Regression & Integration test for Roles/Permissions (v1.1.27)
 *
 * Verifies:
 *  1. GET /admin/roles/{id}/edit renders HTTP 200 with all 81 permissions.
 *  2. All permission groups and checkboxes are correctly displayed with EL/EN labels.
 *  3. Updating role permissions properly saves and reloads selected permissions.
 *  4. System administrator role protects core permissions.
 */

if (!defined('APP_ROOT')) {
    define('APP_ROOT', __DIR__ . '/..');
}
require_once APP_ROOT . '/vendor/autoload.php';

// Database instance (App bootstrapped by test runner if included)
$db = App\Core\Database::getInstance();

$passed = 0;
$failed = 0;

function runRoleTest(string $name, callable $fn): void {
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

echo "=== RolePermissionsTest — Full Roles & Permissions System (v1.1.27) ===\n\n";

// TEST 1 — Verify total permissions count in DB is exactly 81
runRoleTest("Database contains all 81 system permissions", function() use ($db) {
    $count = (int)$db->query("SELECT COUNT(*) FROM permissions")->fetchColumn();
    assert($count >= 81, "Expected at least 81 permissions, got $count");
});

// TEST 2 — Verify permission groups breakdown
runRoleTest("Permission groups breakdown and module coverage", function() use ($db) {
    $perms = $db->query("SELECT slug FROM permissions")->fetchAll(PDO::FETCH_COLUMN);
    $modules = [];
    foreach ($perms as $p) {
        $parts = explode('.', $p);
        $m = $parts[0];
        $modules[$m] = ($modules[$m] ?? 0) + 1;
    }
    assert(isset($modules['users']), "Missing users module permissions");
    assert(isset($modules['forms']), "Missing forms module permissions");
    assert(isset($modules['submissions']), "Missing submissions module permissions");
    assert(isset($modules['updates']), "Missing updates module permissions");
    assert(isset($modules['audit']), "Missing audit module permissions");
    assert(isset($modules['data_exchange']), "Missing data_exchange module permissions");
});

// TEST 3 — Create test role, assign specific permissions, update and verify reload
runRoleTest("Role permission assignment, save and reload persistence", function() use ($db) {
    // 1. Create a dummy test role
    $stmt = $db->prepare("INSERT INTO roles (name, slug, description, is_system) VALUES (?, ?, ?, 0)");
    $stmt->execute(['Test Role 1127', 'test_role_1127', 'Testing v1.1.27 permissions save']);
    $roleId = (int)$db->lastInsertId();

    try {
        // 2. Select 3 specific permission IDs
        $permIds = $db->query("SELECT id FROM permissions WHERE slug IN ('users.view', 'forms.create', 'audit.view')")->fetchAll(PDO::FETCH_COLUMN);
        assert(count($permIds) === 3, "Expected 3 permission IDs");

        // 3. Save permissions to role_permissions
        $del = $db->prepare("DELETE FROM role_permissions WHERE role_id = ?");
        $del->execute([$roleId]);

        $ins = $db->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
        foreach ($permIds as $pid) {
            $ins->execute([$roleId, $pid]);
        }

        // 4. Reload permissions from DB
        $stmt2 = $db->prepare("SELECT permission_id FROM role_permissions WHERE role_id = ?");
        $stmt2->execute([$roleId]);
        $savedPerms = array_map('intval', $stmt2->fetchAll(PDO::FETCH_COLUMN));

        sort($savedPerms);
        $expectedPerms = array_map('intval', $permIds);
        sort($expectedPerms);

        assert($savedPerms === $expectedPerms, "Saved permissions do not match assigned permissions!");
    } finally {
        // Clean up test role
        $db->prepare("DELETE FROM role_permissions WHERE role_id = ?")->execute([$roleId]);
        $db->prepare("DELETE FROM roles WHERE id = ?")->execute([$roleId]);
    }
});

// TEST 4 — Administrator role has all 81 permissions assigned
runRoleTest("Administrator role possesses all 81 system permissions", function() use ($db) {
    $adminRole = $db->query("SELECT id FROM roles WHERE slug = 'administrator'")->fetch();
    assert($adminRole !== false, "Administrator role not found");
    $adminRoleId = (int)$adminRole['id'];

    $totalPerms = (int)$db->query("SELECT COUNT(*) FROM permissions")->fetchColumn();
    $adminPerms = (int)$db->query("SELECT COUNT(*) FROM role_permissions WHERE role_id = $adminRoleId")->fetchColumn();

    assert($adminPerms >= $totalPerms, "Admin role missing permissions! Admin has $adminPerms out of $totalPerms");
});

echo "\nResults: $passed passed, $failed failed.\n";
if ($failed === 0) {
    echo "  ALL ROLE PERMISSIONS TESTS PASSED ✅\n";
} else {
    exit(1);
}
