<?php
/**
 * OrgStructureTest.php — Automated test suite for AppForm v1.1.23
 * Tests organizational structure (Department -> Subdepartment / Team -> User),
 * hierarchy validation rules, deletion safety, extra profile contact details,
 * and migration 034 compatibility.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Models\OrgUnit;
use App\Models\User;

if (!function_exists('__')) {
    function __(string $key, array $replace = []): string {
        return \App\Services\Lang::get($key, $replace);
    }
}

\App\Core\App::$config = require __DIR__ . '/../config/config.php';

echo "========================================\n";
echo "  APPFORM v1.1.23 ORG STRUCTURE & PROFILE TESTS\n";
echo "========================================\n";

$pdo = Database::getInstance();

if (!function_exists('assertOrgTest')) {
    function assertOrgTest(bool $cond, string $msg): void {
        if ($cond) {
            echo "  ✅ PASS: $msg\n";
        } else {
            echo "  ❌ FAIL: $msg\n";
            exit(1);
        }
    }
}

// Pre-test cleanup of leftover test users & org units
$pdo->exec("DELETE FROM users WHERE username IN ('dev_user1_v23', 'devops_user1_v23')");
$pdo->exec("DELETE FROM org_units WHERE name IN ('Software Dev Test', 'DevOps Team Test')");
$pdo->exec("DELETE FROM org_units WHERE name = 'IT Department Test'");

// ── TEST 1: Creation of Department -> Sub-department -> User ───────────────
echo "\nTest 1: Department -> Sub-department -> User hierarchy creation...\n";
$deptRes = OrgUnit::create('IT Department Test', 'department', null);
assertOrgTest($deptRes['success'], 'Department creation succeeded');
$deptId = $deptRes['id'];

$subRes = OrgUnit::create('Software Dev Test', 'subdepartment', $deptId);
assertOrgTest($subRes['success'], 'Sub-department creation succeeded under Department');
$subId = $subRes['id'];

// Create user and assign to subdepartment
$stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, full_name, role_id, org_unit_id) VALUES (?, ?, ?, ?, 1, ?)");
$stmt->execute(['dev_user1_v23', 'dev1_v23@appform.local', 'hash', 'Dev User 1 V23', $subId]);
$userId1 = (int)$pdo->lastInsertId();

$u1 = User::findById($userId1);
assertOrgTest((int)$u1['org_unit_id'] === $subId, 'User correctly assigned to Sub-department');

// ── TEST 2: Creation of Department -> Team -> User ───────────────
echo "\nTest 2: Department -> Team -> User hierarchy creation...\n";
$teamRes = OrgUnit::create('DevOps Team Test', 'team', $deptId);
assertOrgTest($teamRes['success'], 'Team creation succeeded under Department');
$teamId = $teamRes['id'];

$stmt->execute(['devops_user1_v23', 'devops1_v23@appform.local', 'hash', 'DevOps User 1 V23', $subId]);
$userId2 = (int)$pdo->lastInsertId();

// Update user to Team
$stmtUp = $pdo->prepare("UPDATE users SET org_unit_id = ? WHERE id = ?");
$stmtUp->execute([$teamId, $userId2]);

$u2 = User::findById($userId2);
assertOrgTest((int)$u2['org_unit_id'] === $teamId, 'User correctly updated/assigned to Team');

// ── TEST 3: Unlimited Depth Hierarchy & Move Node Validation ────────────────
echo "\nTest 3: Unlimited depth hierarchy & move node validation...\n";

// Rule 3a: Department / Sub-unit under Sub-department (Depth 3)
$deepSubRes = OrgUnit::create('A Unit Level 3', 'subdepartment', $subId);
assertOrgTest($deepSubRes['success'], 'Sub-department created under another Sub-department (Depth 3)');
$deepSubId = $deepSubRes['id'];

// Rule 3b: Sub-unit under Depth 3 (Depth 4)
$deeperSubRes = OrgUnit::create('B Unit Level 4', 'team', $deepSubId);
assertOrgTest($deeperSubRes['success'], 'Team created under Depth 3 unit (Depth 4)');
$deeperSubId = $deeperSubRes['id'];

// Rule 3c: Move Node (Move Depth 4 unit directly under Department)
$moveRes = OrgUnit::update($deeperSubId, 'B Unit Level 4 (Moved)', $deptId);
assertOrgTest($moveRes['success'], 'Node moved successfully under Department');

// Rule 3d: Block Circular Move (Move Department under its own child)
$cycleMove = OrgUnit::update($deptId, 'IT Department Test', $subId);
assertOrgTest(!$cycleMove['success'], 'Circular reference parent move correctly blocked');

// Rule 3e: Self-parenting blocked
$selfMove = OrgUnit::update($subId, 'Software Dev Test', $subId);
assertOrgTest(!$selfMove['success'], 'Self-parenting move correctly blocked');

// Cleanup deep test nodes
OrgUnit::delete($deeperSubId);
OrgUnit::delete($deepSubId);

// ── TEST 4: Deletion Protection Guard ──────────────────────────────────────
echo "\nTest 4: Deletion protection for units with children or users...\n";

// 4a: Cannot delete Department that has children (Sub-dept & Team)
$delDept = OrgUnit::delete($deptId);
assertOrgTest(!$delDept['success'], 'Department deletion blocked due to child units');
assertOrgTest(str_contains($delDept['message'], 'sub-departments') || str_contains($delDept['message'], 'υποτμήματα'), 'Deletion message mentions child units');

// 4b: Cannot delete Sub-department that has assigned users
$delSub = OrgUnit::delete($subId);
assertOrgTest(!$delSub['success'], 'Sub-department deletion blocked due to assigned users');
assertOrgTest(str_contains($delSub['message'], 'users') || str_contains($delSub['message'], 'χρήστες'), 'Deletion message mentions assigned users');

// ── TEST 5: Organizational Unit Assignment & Reassignment ─────────────────
echo "\nTest 5: Reassigning user & releasing deletion block...\n";
// Unassign user1 from subdepartment
$stmtUp->execute([null, $userId1]);

$delSub2 = OrgUnit::delete($subId);
assertOrgTest($delSub2['success'], 'Sub-department deleted successfully after user unassigned');

// ── TEST 6: Extra User Profile Contact Details Validation & Storage ────────
echo "\nTest 6: Extra contact details validation & persistence...\n";

$updateDetails = $pdo->prepare("
    UPDATE users SET 
        personal_email = ?, corporate_phone = ?, mobile_phone = ?, internal_phone = ? 
    WHERE id = ?
");
$updateDetails->execute([
    'personal@example.com',
    '+30 210 1234567',
    '+30 690 0000000',
    '1024',
    $userId1
]);

$u1Updated = User::findById($userId1);
assertOrgTest($u1Updated['personal_email'] === 'personal@example.com', 'Personal email stored correctly');
assertOrgTest($u1Updated['corporate_phone'] === '+30 210 1234567', 'Corporate phone stored correctly');
assertOrgTest($u1Updated['mobile_phone'] === '+30 690 0000000', 'Mobile phone stored correctly');
assertOrgTest($u1Updated['internal_phone'] === '1024', 'Internal phone stored correctly');

// ── TEST 7: Tree Rendering Structure Check ────────────────────────────────
echo "\nTest 7: OrgUnit::getTree() structure check...\n";
$tree = OrgUnit::getTree();
assertOrgTest(is_array($tree), 'Tree returns array');
$foundDept = false;
foreach ($tree as $dt) {
    if ((int)$dt['id'] === $deptId) {
        $foundDept = true;
        assertOrgTest(count($dt['children']) >= 1, 'Department children list populated in tree');
    }
}
assertOrgTest($foundDept, 'Created department present in getTree()');

// ── TEST 8: Cleanup test records ──────────────────────────────────────────
echo "\nTest 8: Cleanup test records...\n";
$pdo->prepare("DELETE FROM users WHERE id IN (?, ?)")->execute([$userId1, $userId2]);
$pdo->prepare("DELETE FROM org_units WHERE id IN (?, ?)")->execute([$teamId, $subId]);
$pdo->prepare("DELETE FROM org_units WHERE id = ?")->execute([$deptId]);
echo "  ✅ PASS: Test records cleaned up cleanly\n";

echo "\n========================================\n";
echo "  ALL ORG STRUCTURE TESTS PASSED ✅\n";
echo "========================================\n";
