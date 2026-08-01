<?php
/**
 * UserControllerTest — Regression test for /admin/users HTTP 500 fix (v1.1.26)
 *
 * Verifies that the UserController::index() COUNT query is correct under
 * MySQL/MariaDB with ONLY_FULL_GROUP_BY sql_mode (the mode that triggers SQLSTATE 1140).
 *
 * Tests:
 *  1. COUNT query without filters — must return a non-negative integer
 *  2. COUNT query with a search filter
 *  3. COUNT query with a role filter
 *  4. COUNT query with a status filter
 *  5. COUNT query under ONLY_FULL_GROUP_BY — must NOT throw SQLSTATE 1140
 *  6. Main list query with LEFT JOIN org_units — must succeed
 */

define('APP_ROOT', __DIR__ . '/..');
require_once APP_ROOT . '/vendor/autoload.php';

// When included from run.php, App is already bootstrapped — only get the DB instance
$db = App\Core\Database::getInstance();


$passed = 0;
$failed = 0;

function runTest(string $name, callable $fn): void {
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

echo "=== UserControllerTest — COUNT Query Regression (v1.1.26) ===\n\n";

// Helpers that reproduce the exact logic from UserController::index()
function buildCountQuery(string $search, string $roleId, string $status, array &$params): string {
    $params = [];
    $q  = "SELECT COUNT(*) FROM users u";
    $q .= " JOIN roles r ON u.role_id = r.id";
    $q .= " LEFT JOIN org_units o ON u.org_unit_id = o.id";
    $q .= " WHERE 1=1";
    if (!empty($search)) {
        $q .= " AND (u.username LIKE ? OR u.email LIKE ? OR u.full_name LIKE ?)";
        $s  = "%$search%";
        $params[] = $s; $params[] = $s; $params[] = $s;
    }
    if ($roleId !== '') {
        $q .= " AND u.role_id = ?";
        $params[] = (int)$roleId;
    }
    if ($status !== '') {
        $q .= " AND u.is_active = ?";
        $params[] = (int)$status;
    }
    return $q;
}

// TEST 1 — No filters
runTest("COUNT query (no filters) executes without error", function() use ($db) {
    $params = [];
    $q = buildCountQuery('', '', '', $params);
    $stmt = $db->prepare($q);
    $stmt->execute($params);
    $count = (int)$stmt->fetchColumn();
    assert($count >= 0, "Count must be non-negative, got $count");
});

// TEST 2 — Search filter
runTest("COUNT query (search filter) executes without error", function() use ($db) {
    $params = [];
    $q = buildCountQuery('admin', '', '', $params);
    $stmt = $db->prepare($q);
    $stmt->execute($params);
    $count = (int)$stmt->fetchColumn();
    assert($count >= 0, "Count must be non-negative, got $count");
});

// TEST 3 — Role filter (role_id = 1)
runTest("COUNT query (role filter) executes without error", function() use ($db) {
    $params = [];
    $q = buildCountQuery('', '1', '', $params);
    $stmt = $db->prepare($q);
    $stmt->execute($params);
    $count = (int)$stmt->fetchColumn();
    assert($count >= 0, "Count must be non-negative, got $count");
});

// TEST 4 — Status filter (is_active = 1)
runTest("COUNT query (status filter) executes without error", function() use ($db) {
    $params = [];
    $q = buildCountQuery('', '', '1', $params);
    $stmt = $db->prepare($q);
    $stmt->execute($params);
    $count = (int)$stmt->fetchColumn();
    assert($count >= 0, "Count must be non-negative, got $count");
});

// TEST 5 — COUNT query under ONLY_FULL_GROUP_BY (the exact mode that caused HTTP 500)
runTest("COUNT query does NOT trigger SQLSTATE 1140 under ONLY_FULL_GROUP_BY", function() use ($db) {
    // Enable the strict sql_mode that caused the HTTP 500
    $db->exec("SET SESSION sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
    try {
        $params = [];
        $q = buildCountQuery('', '', '', $params);
        $stmt = $db->prepare($q);
        $stmt->execute($params);
        $count = (int)$stmt->fetchColumn();
        assert($count >= 0, "Count must be non-negative under ONLY_FULL_GROUP_BY");
    } finally {
        // Restore default session mode
        $db->exec("SET SESSION sql_mode=DEFAULT");
    }
});

// TEST 6 — Old broken query must be GONE: str_replace approach produces bad SQL
runTest("Old str_replace approach WOULD fail under ONLY_FULL_GROUP_BY (confirming it was the bug)", function() use ($db) {
    $db->exec("SET SESSION sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
    $brokenQuery = str_replace(
        "u.*, r.name as role_name",
        "COUNT(*)",
        "\n            SELECT u.*, r.name as role_name, o.name as org_unit_name, o.type as org_unit_type \n            FROM users u \n            JOIN roles r ON u.role_id = r.id \n            LEFT JOIN org_units o ON u.org_unit_id = o.id \n            WHERE 1=1\n        "
    );
    $threw = false;
    try {
        $stmt = $db->prepare($brokenQuery);
        $stmt->execute();
        $stmt->fetchColumn();
    } catch (\PDOException $e) {
        $threw = true;
        assert(strpos($e->getCode(), '42000') !== false || $e->getCode() === '42000',
            "Expected SQLSTATE 42000, got: " . $e->getCode());
    } finally {
        $db->exec("SET SESSION sql_mode=DEFAULT");
    }
    assert($threw, "Old broken query MUST throw PDOException under ONLY_FULL_GROUP_BY — confirms the fix was necessary");
});

// TEST 7 — Main list query with all columns succeeds
runTest("Main list SELECT (with org_unit columns) executes without error", function() use ($db) {
    $q = "
        SELECT u.*, r.name as role_name, o.name as org_unit_name, o.type as org_unit_type 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        LEFT JOIN org_units o ON u.org_unit_id = o.id 
        WHERE 1=1
        ORDER BY u.id ASC LIMIT 10 OFFSET 0
    ";
    $stmt = $db->prepare($q);
    $stmt->execute([]);
    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    assert(is_array($rows), "Expected array of users");
});

echo "\n";
echo "Results: $passed passed, $failed failed.\n";
if ($failed === 0) {
    echo "  ALL UserController COUNT QUERY TESTS PASSED ✅\n";
} else {
    exit(1);
}
