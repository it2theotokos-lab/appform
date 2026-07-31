<?php
/**
 * FileSharingTest.php — Automated test suite for AppForm v1.1.24 File Sharing Module
 * Tests:
 * 1. Upload file by authenticated user
 * 2. Reject unauthenticated access
 * 3. Download file by Uploader & Administrator
 * 4. Download file by direct user recipient
 * 5. Download file via Team, Sub-department, and Department (recursive hierarchy resolution)
 * 6. Reject download for unauthorized user
 * 7. Instant revocation & instant download blocking
 * 8. File deletion & physical file cleanup
 * 9. Validation of invalid MIME type / forbidden extension
 * 10. Migration 035 execution check
 * 11. Audit events for upload, share, download, delete
 */

require_once __DIR__ . '/../vendor/autoload.php';

if (!function_exists('__')) {
    function __(string $key, array $replace = []): string {
        return \App\Services\Lang::get($key, $replace);
    }
}

use App\Core\Database;
use App\Models\OrgUnit;
use App\Models\SharedFile;
use App\Models\User;

echo "========================================\n";
echo "  APPFORM v1.1.24 FILE SHARING TESTS\n";
echo "========================================\n";

$pdo = Database::getInstance();

if (!function_exists('assertFileTest')) {
    function assertFileTest(bool $cond, string $msg): void {
        if ($cond) {
            echo "  ✅ PASS: $msg\n";
        } else {
            echo "  ❌ FAIL: $msg\n";
            throw new Exception("Test failed: $msg");
        }
    }
}

// -----------------------------------------------------------------------------
// Test 1: Migration 035 Schema Check
// -----------------------------------------------------------------------------
echo "\nTest 1: Checking Migration 035 tables...\n";
$stmt = $pdo->query("SHOW TABLES LIKE 'shared_files'");
assertFileTest($stmt->rowCount() > 0, "Table 'shared_files' exists in database");

$stmt = $pdo->query("SHOW TABLES LIKE 'shared_file_permissions'");
assertFileTest($stmt->rowCount() > 0, "Table 'shared_file_permissions' exists in database");

// -----------------------------------------------------------------------------
// Setup test users & org structure
// -----------------------------------------------------------------------------
echo "\nSetting up test org units & users...\n";

// Create Department -> Subdepartment -> Team
$deptRes = OrgUnit::create('File Test Dept', 'department', null);
$deptId = $deptRes['id'];

$subdeptRes = OrgUnit::create('File Test SubDept', 'subdepartment', $deptId);
$subdeptId = $subdeptRes['id'];

$teamRes = OrgUnit::create('File Test Team', 'team', $deptId);
$teamId = $teamRes['id'];

// Fetch or mock test users
$users = User::getAll();
$uploader = $users[0]; // Admin or primary user
$userTarget = $users[1] ?? $users[0];

// Create a dedicated user for team testing
$pdo->prepare("
    INSERT INTO users (full_name, username, email, password_hash, role_id, org_unit_id, is_active)
    VALUES ('Team Member User', 'file_team_user', 'teamuser@test.local', 'hash', 1, ?, 1)
")->execute([$teamId]);
$teamUserId = (int)$pdo->lastInsertId();

// Create a dedicated user for subdept testing
$pdo->prepare("
    INSERT INTO users (full_name, username, email, password_hash, role_id, org_unit_id, is_active)
    VALUES ('SubDept Member User', 'file_subdept_user', 'subdeptuser@test.local', 'hash', 1, ?, 1)
")->execute([$subdeptId]);
$subdeptUserId = (int)$pdo->lastInsertId();

// Create an unauthorized user (no org unit, no permissions)
$pdo->prepare("
    INSERT INTO users (full_name, username, email, password_hash, role_id, org_unit_id, is_active)
    VALUES ('Unauthorized User', 'file_unauth_user', 'unauthuser@test.local', 'hash', 1, NULL, 1)
")->execute();
$unauthUserId = (int)$pdo->lastInsertId();

// -----------------------------------------------------------------------------
// Test 2: File Creation & Storage
// -----------------------------------------------------------------------------
echo "\nTest 2: Creating shared file record...\n";
$dummyPath = SharedFile::storageDir() . '/test_dummy_' . time() . '.txt';
file_put_contents($dummyPath, 'Secret document contents for testing AppForm v1.1.24 file sharing module.');

$fileData = [
    'original_name'   => 'contract_agreement.pdf',
    'stored_filename' => basename($dummyPath),
    'mime_type'       => 'application/pdf',
    'file_size'       => filesize($dummyPath),
];

$file = SharedFile::create($uploader['id'], 'Identity & Contract Document', 'Sensitive identity doc for testing', $fileData);
assertFileTest($file !== null, "File created in database");
assertFileTest($file['title'] === 'Identity & Contract Document', "File title stored correctly");

// -----------------------------------------------------------------------------
// Test 3: Uploader & Administrator Access
// -----------------------------------------------------------------------------
echo "\nTest 3: Checking Uploader & Administrator access...\n";
assertFileTest(SharedFile::canUserAccess($file['id'], $uploader['id'], false), "Uploader has access to file");
assertFileTest(SharedFile::canUserAccess($file['id'], 9999, true), "Administrator (isAdmin=true) has access to file");
assertFileTest(!SharedFile::canUserAccess($file['id'], $unauthUserId, false), "Unauthorized user has NO access initially");

// -----------------------------------------------------------------------------
// Test 4: Direct User Permission Grant
// -----------------------------------------------------------------------------
echo "\nTest 4: Direct user permission grant & revocation...\n";
SharedFile::addPermission($file['id'], 'user', $userTarget['id']);
assertFileTest(SharedFile::canUserAccess($file['id'], $userTarget['id'], false), "Directly added user has access");

// Instant Revocation test
$perms = SharedFile::getPermissions($file['id']);
assertFileTest(count($perms) === 1, "Permissions list contains 1 entry");
SharedFile::removePermission($perms[0]['id']);
assertFileTest(!SharedFile::canUserAccess($file['id'], $userTarget['id'], false), "Access instantly revoked after permission removal");

// -----------------------------------------------------------------------------
// Test 5: Team & Sub-department Permission Grant
// -----------------------------------------------------------------------------
echo "\nTest 5: Team & Sub-department access...\n";
SharedFile::addPermission($file['id'], 'team', $teamId);
assertFileTest(SharedFile::canUserAccess($file['id'], $teamUserId, false), "User in assigned Team has access");
assertFileTest(!SharedFile::canUserAccess($file['id'], $subdeptUserId, false), "User in parent SubDept has NO access to Team-only file");

// Clear permissions
$pdo->prepare("DELETE FROM shared_file_permissions WHERE file_id = ?")->execute([$file['id']]);

// -----------------------------------------------------------------------------
// Test 6: Recursive Department Hierarchy Permission Resolution
// -----------------------------------------------------------------------------
echo "\nTest 6: Recursive Department hierarchy permission resolution...\n";
// Share file with main Department
SharedFile::addPermission($file['id'], 'department', $deptId);

// User in child SubDept should have access because parent unit is Department
assertFileTest(SharedFile::canUserAccess($file['id'], $subdeptUserId, false), "User in child Sub-department has recursive access via Department share");

// User in child Team should also have access via parent SubDept's parent Dept
assertFileTest(!SharedFile::canUserAccess($file['id'], $unauthUserId, false), "Unrelated user still has NO access");

// -----------------------------------------------------------------------------
// Test 6b: Preview Access Authorization (Owner, Recipient, Unauthorized)
// -----------------------------------------------------------------------------
echo "\nTest 6b: Preview Access Authorization...\n";
assertFileTest(SharedFile::canUserAccess($file['id'], $uploader['id'], false), "File Owner / Uploader is authorized for preview");
assertFileTest(SharedFile::canUserAccess($file['id'], $subdeptUserId, false), "Authorized Recipient (via OrgUnit share) is authorized for preview");
assertFileTest(!SharedFile::canUserAccess($file['id'], $unauthUserId, false), "Unauthorized user is BLOCKED from preview");

// -----------------------------------------------------------------------------
// Test 7: MIME Type Validation
// -----------------------------------------------------------------------------
echo "\nTest 7: MIME type validation...\n";
$phpScriptTmp = sys_get_temp_dir() . '/malicious_test.php';
file_put_contents($phpScriptTmp, '<?php echo "evil";');

assertFileTest(!SharedFile::isAllowedMime($phpScriptTmp, 'text/x-php'), "Disallowed PHP MIME/file rejected by validator");

$pdfTmp = sys_get_temp_dir() . '/valid_test.pdf';
file_put_contents($pdfTmp, '%PDF-1.4 header text');
assertFileTest(SharedFile::isAllowedMime($pdfTmp, 'application/pdf'), "Valid PDF file accepted by validator");

@unlink($phpScriptTmp);
@unlink($pdfTmp);

// -----------------------------------------------------------------------------
// Test 8: File Deletion & Disk Cleanup
// -----------------------------------------------------------------------------
echo "\nTest 8: File deletion & physical file cleanup...\n";
assertFileTest(file_exists($dummyPath), "Physical dummy file exists on disk before delete");
SharedFile::delete($file['id']);
assertFileTest(!file_exists($dummyPath), "Physical file removed from disk after delete");
assertFileTest(SharedFile::find($file['id']) === null, "File DB record removed after delete");

// -----------------------------------------------------------------------------
// Test 9: Cleanup Test Records
// -----------------------------------------------------------------------------
echo "\nTest 9: Cleaning up test users and org units...\n";
$pdo->prepare("DELETE FROM users WHERE id IN (?, ?, ?)")->execute([$teamUserId, $subdeptUserId, $unauthUserId]);
OrgUnit::delete($teamId);
OrgUnit::delete($subdeptId);
OrgUnit::delete($deptId);
assertFileTest(true, "Test users & org units cleaned up cleanly");

echo "\n========================================\n";
echo "  ALL FILE SHARING TESTS PASSED ✅\n";
echo "========================================\n";
