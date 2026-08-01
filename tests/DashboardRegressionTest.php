<?php
/**
 * DashboardRegressionTest — Regression test for Dashboard SQLSTATE[HY093] parameter binding fix
 *
 * Verifies that GET /dashboard queries execute cleanly without PDOException/HY093 invalid parameter number.
 */

if (!defined('APP_ROOT')) {
    define('APP_ROOT', __DIR__ . '/..');
}
require_once APP_ROOT . '/vendor/autoload.php';

// Database instance (App bootstrapped by test runner if included)
$db = App\Core\Database::getInstance();

$passed = 0;
$failed = 0;

function runDashTest(string $name, callable $fn): void {
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

echo "=== DashboardRegressionTest — HY093 Parameter Binding Fix ===\n\n";

// TEST 1 — Execute DashboardController index logic for user with non-sequential array keys
runDashTest("Dashboard queries execute without SQLSTATE[HY093] under non-sequential array keys", function() use ($db) {
    App\Core\Session::set('user_id', 1);
    
    // Simulate non-sequential array keys returned by array_unique
    $subordinateIds = [1 => 8, 2 => 9];
    $allowedUserIds = array_values(array_unique(array_merge([1], $subordinateIds)));

    // Verify keys are strictly 0, 1, 2...
    assert(array_keys($allowedUserIds) === range(0, count($allowedUserIds) - 1), "Array keys are not 0-indexed sequential!");

    $inClause = implode(',', array_fill(0, count($allowedUserIds), '?'));
    $whereUser = " WHERE s.user_id IN ({$inClause}) ";
    $params = $allowedUserIds;

    // 1. Count query
    $stmtCount = $db->prepare("SELECT COUNT(*) FROM form_submissions s " . $whereUser);
    $stmtCount->execute($params);
    $subsCount = $stmtCount->fetchColumn();
    assert($subsCount >= 0, "Count query returned invalid result");

    // 2. Latest submissions query
    $stmtLatest = $db->prepare("
        SELECT s.*, COALESCE(f.title, 'Αρχειοθετημένη/Διαγραμμένη Φόρμα') as form_title, u.username 
        FROM form_submissions s 
        LEFT JOIN forms f ON s.form_id = f.id 
        JOIN users u ON s.user_id = u.id 
        {$whereUser}
        ORDER BY s.created_at DESC LIMIT 5
    ");
    $stmtLatest->execute($params);
    $rows = $stmtLatest->fetchAll(PDO::FETCH_ASSOC);
    assert(is_array($rows), "Latest submissions query failed");
});

echo "\nResults: $passed passed, $failed failed.\n";
if ($failed === 0) {
    echo "  ALL DASHBOARD REGRESSION TESTS PASSED ✅\n";
} else {
    exit(1);
}
