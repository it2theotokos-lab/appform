<?php
/**
 * AppForm v1.1.2 → v1.1.3 Local Update Acceptance Test
 * 
 * Εκτελεί πλήρες update μέσω UpdateEngineService (όπως το κάνει ο worker)
 * με LocalReleaseProvider που χρησιμοποιεί το τοπικό update ZIP.
 * 
 * RUN: php tests/run_update_acceptance.php
 */

$root = dirname(__DIR__);
chdir($root);

if (file_exists($root . '/vendor/autoload.php')) {
    require_once $root . '/vendor/autoload.php';
}

// Bootstrap — same as test runner
new \App\Core\App();

use App\Core\Database;
use App\Core\Logger;
use App\Services\Update\LocalReleaseProvider;
use App\Services\Update\UpdateStatusService;
use App\Services\Update\UpdateEngineService;
use App\Services\Update\PackageValidatorService;
use App\Services\VersionService;

$db = Database::getInstance();

echo str_repeat("=", 60) . "\n";
echo "  AppForm Local Update Acceptance Test\n";
echo "  v1.1.2 → v1.1.3 (local package)\n";
echo str_repeat("=", 60) . "\n\n";

$PASS = true;
$log = [];

function step(string $msg) {
    echo "  ► {$msg}\n";
    global $log;
    $log[] = $msg;
}
function ok(string $msg) {
    echo "  ✓ {$msg}\n";
    global $log;
    $log[] = "OK: {$msg}";
}
function fail(string $msg) {
    echo "  ✗ FAIL: {$msg}\n";
    global $log, $PASS;
    $log[] = "FAIL: {$msg}";
    $PASS = false;
}
function hr() { echo str_repeat("-", 60) . "\n"; }

// -----------------------------------------------------------------------
// PRE-CONDITIONS
// -----------------------------------------------------------------------
hr();
echo "1. PRE-CONDITIONS\n";
hr();

// Read installed version
$verData = VersionService::getVersionData();
step("Current version: {$verData['version']} (build {$verData['build']})");

// Read installed.lock
$lockFile = $root . '/config/installed.lock';
$lock = json_decode(file_get_contents($lockFile), true);
step("installed.lock: version={$lock['version']}, installed=" . ($lock['installed'] ? 'true' : 'false'));

// Set the installed version to 1.1.2 for testing (temporarily)
$prevVersion = $verData['version'];
$prevBuild   = $verData['build'];

step("Simulating v1.1.2 as installed (setting previous_version in update record)");

// -----------------------------------------------------------------------
// STEP 1: CHECK AVAILABLE UPDATE
// -----------------------------------------------------------------------
hr();
echo "2. CHECK AVAILABLE UPDATE\n";
hr();

$provider = new LocalReleaseProvider($root . '/release');
if (!$provider->isConfigured()) {
    fail("LocalReleaseProvider: release/ directory not found at {$root}/release");
    exit(1);
}

$latest = $provider->getLatestCompatibleRelease('1.1.2', 'stable');
if (!$latest) {
    fail("No update found. Check that AppForm-1.1.3-update.zip exists in release/");
    exit(1);
}

ok("Update found: v{$latest['version']} ({$latest['package_name']}, " . number_format($latest['package_size']) . " bytes)");
ok("SHA-256: {$latest['sha256']}");

// -----------------------------------------------------------------------
// STEP 2: COPY UPDATE PACKAGE TO STORAGE
// -----------------------------------------------------------------------
hr();
echo "3. PACKAGE DOWNLOAD (local copy)\n";
hr();

$storageDir  = $root . '/public/storage';
$updateId    = null;
$packagePath = "{$storageDir}/update_package_acceptance_test.zip";

// Remove any previous test package
if (file_exists($packagePath)) {
    unlink($packagePath);
}

$copied = $provider->downloadAsset($latest['package_url'], $packagePath);
if (!$copied || !file_exists($packagePath)) {
    fail("Failed to copy update package to storage.");
    exit(1);
}
ok("Package copied to: {$packagePath} (" . filesize($packagePath) . " bytes)");

// -----------------------------------------------------------------------
// STEP 3: SHA-256 VERIFICATION
// -----------------------------------------------------------------------
hr();
echo "4. SHA-256 VERIFICATION\n";
hr();

$actualHash = hash_file('sha256', $packagePath);
if (!hash_equals($latest['sha256'], $actualHash)) {
    fail("SHA-256 MISMATCH! Expected: {$latest['sha256']} Got: {$actualHash}");
    exit(1);
}
ok("SHA-256 verified: {$actualHash}");

// -----------------------------------------------------------------------
// STEP 4: PACKAGE VALIDATION
// -----------------------------------------------------------------------
hr();
echo "5. PACKAGE VALIDATION\n";
hr();

$validation = PackageValidatorService::validatePackage($packagePath);
if (!$validation['success']) {
    fail("Package validation failed: " . $validation['message']);
    exit(1);
}
ok("Package validated: " . $validation['message']);

// Read manifest
$zip = new ZipArchive();
$zip->open($packagePath);
$manifest = json_decode($zip->getFromName('manifest.json'), true);
$zip->close();

step("Manifest: version={$manifest['version']}, build={$manifest['build']}, type={$manifest['package_type']}");
step("Migrations in package: " . implode(', ', $manifest['migrations'] ?? []));
step("Files in package: " . count($manifest['files'] ?? []) . " files");

ok("Manifest validation passed.");

// -----------------------------------------------------------------------
// STEP 5: CHECK PROTECTED FILES BEFORE UPDATE
// -----------------------------------------------------------------------
hr();
echo "6. PROTECTED FILES (pre-update snapshot)\n";
hr();

$protectedFiles = [
    'config/installed.lock'    => $root . '/config/installed.lock',
    'config/config.local.php'  => $root . '/config/config.local.php',
];

$preHashes = [];
foreach ($protectedFiles as $label => $path) {
    if (file_exists($path)) {
        $preHashes[$label] = hash_file('sha256', $path);
        step("{$label}: " . substr($preHashes[$label], 0, 16) . "...");
    } else {
        fail("{$label} does not exist before update!");
    }
}

$preStorageExists = is_dir($root . '/public/storage');
ok("public/storage exists: " . ($preStorageExists ? 'YES' : 'NO'));

// -----------------------------------------------------------------------
// STEP 6: CREATE UPDATE RECORD
// -----------------------------------------------------------------------
hr();
echo "7. CREATE UPDATE RECORD\n";
hr();

// Clean up any stale acceptance test records
$db->exec("DELETE FROM application_updates WHERE release_version = '1.1.3' AND provider = 'local_test'");

$updateId = UpdateStatusService::createUpdateRecord([
    'release_version'  => $latest['version'],
    'build_number'     => (int)($manifest['build'] ?? 5),
    'release_channel'  => 'stable',
    'previous_version' => '1.1.2',
    'previous_build'   => 4,
    'provider'         => 'local_test',
    'started_by'       => 1,
]);

// Update the record with package path and hash
$stmtPkg = $db->prepare("UPDATE application_updates SET package_path = ?, package_sha256 = ? WHERE id = ?");
$stmtPkg->execute([$packagePath, $actualHash, $updateId]);

ok("Update record created: ID={$updateId}");

// -----------------------------------------------------------------------
// STEP 7: RUN UPDATE ENGINE (the same code the worker calls)
// -----------------------------------------------------------------------
hr();
echo "8. UPDATE ENGINE EXECUTION (worker equivalent)\n";
hr();

step("Running UpdateEngineService::runUpdate({$updateId})...");
$startTime = microtime(true);

$success = UpdateEngineService::runUpdate($updateId);

$elapsed = round(microtime(true) - $startTime, 2);
step("Elapsed: {$elapsed}s");

if ($success) {
    ok("Update engine completed successfully.");
} else {
    fail("Update engine returned false — update FAILED.");
}

// -----------------------------------------------------------------------
// STEP 8: READ UPDATE LOGS
// -----------------------------------------------------------------------
hr();
echo "9. UPDATE LOGS\n";
hr();

$logs = UpdateStatusService::getLogs($updateId);
foreach ($logs as $entry) {
    $level  = strtoupper($entry['log_level'] ?? 'info');
    $phase  = $entry['update_phase'] ?? '?';
    $msg    = $entry['message'] ?? '';
    echo "   [{$level}] [{$phase}] {$msg}\n";
}

// -----------------------------------------------------------------------
// STEP 9: VERIFY PROTECTED FILES UNCHANGED
// -----------------------------------------------------------------------
hr();
echo "10. PROTECTED FILES (post-update verification)\n";
hr();

foreach ($protectedFiles as $label => $path) {
    if (file_exists($path)) {
        $postHash = hash_file('sha256', $path);
        if (hash_equals($preHashes[$label], $postHash)) {
            ok("{$label}: UNCHANGED ✓");
        } else {
            fail("{$label}: MODIFIED! Pre={$preHashes[$label]} Post={$postHash}");
        }
    } else {
        fail("{$label}: DELETED during update!");
    }
}

$postStorageExists = is_dir($root . '/public/storage');
if ($postStorageExists) {
    ok("public/storage: PRESERVED ✓");
} else {
    fail("public/storage: DELETED during update!");
}

// -----------------------------------------------------------------------
// STEP 10: VERSION VERIFICATION
// -----------------------------------------------------------------------
hr();
echo "11. POST-UPDATE VERSION VERIFICATION\n";
hr();

// Reload version data from updated files
$verCfg = require $root . '/config/version.php';
$newVersion = $verCfg['version'] ?? 'unknown';
$newBuild   = $verCfg['build'] ?? 'unknown';

step("version.php after update: v{$newVersion} (build {$newBuild})");

if ($newVersion === '1.1.3' && (int)$newBuild === 5) {
    ok("Version upgraded to v1.1.3 (build 5) ✓");
} else {
    fail("Version mismatch! Expected 1.1.3 build 5, got {$newVersion} build {$newBuild}");
}

// -----------------------------------------------------------------------
// STEP 11: DATABASE HEALTH CHECK
// -----------------------------------------------------------------------
hr();
echo "12. DATABASE HEALTH CHECK\n";
hr();

try {
    $stmt = $db->query("SELECT 1");
    $result = $stmt->fetchColumn();
    if ($result == 1) {
        ok("Database connection: HEALTHY ✓");
    } else {
        fail("Database query returned unexpected result.");
    }

    // Check migration was applied
    $applied = $db->query("SELECT COUNT(*) FROM schema_migrations WHERE migration LIKE '%026%'")->fetchColumn();
    if ($applied > 0) {
        ok("Migration 026_add_oauth_settings.sql: APPLIED ✓");
    } else {
        // Migration might not have been applied if already in schema_migrations or table doesn't exist
        step("Note: migration 026 tracking check — checking oauth_settings table directly");
        try {
            $db->query("SELECT COUNT(*) FROM oauth_settings LIMIT 1");
            ok("oauth_settings table EXISTS (migration already applied) ✓");
        } catch (\Throwable $e) {
            fail("Migration 026 not applied and oauth_settings table missing: " . $e->getMessage());
        }
    }
} catch (\Throwable $e) {
    fail("Database health check failed: " . $e->getMessage());
}

// -----------------------------------------------------------------------
// STEP 12: MAINTENANCE MODE OFF
// -----------------------------------------------------------------------
hr();
echo "13. MAINTENANCE MODE STATUS\n";
hr();

$statusFile = $root . '/public/storage/update_status.json';
if (file_exists($statusFile)) {
    $statusData = json_decode(file_get_contents($statusFile), true);
    $maintenanceOn = $statusData['maintenance_mode'] ?? false;
    if (!$maintenanceOn) {
        ok("Maintenance mode: OFF ✓");
    } else {
        fail("Maintenance mode still ON after update completed!");
    }
} else {
    step("No update_status.json found (cleared after completion — OK)");
}

// Final DB update record status
$finalRecord = $db->prepare("SELECT status, progress_percent FROM application_updates WHERE id = ?");
$finalRecord->execute([$updateId]);
$record = $finalRecord->fetch(\PDO::FETCH_ASSOC);
step("DB update record: status={$record['status']}, progress={$record['progress_percent']}%");

if ($record['status'] === 'completed') {
    ok("DB update status: completed ✓");
} elseif (in_array($record['status'], ['rolled_back', 'failed'])) {
    fail("DB update status: {$record['status']} — update did NOT complete.");
}

// Cleanup test package
if (file_exists($packagePath)) {
    unlink($packagePath);
    step("Test package cleaned up.");
}

// -----------------------------------------------------------------------
// FINAL SUMMARY
// -----------------------------------------------------------------------
hr();
echo "\n";
echo str_repeat("=", 60) . "\n";
if ($PASS) {
    echo "  ✅  ACCEPTANCE TEST: PASS\n";
    echo "  Update v1.1.2 → v1.1.3 completed successfully.\n";
} else {
    echo "  ❌  ACCEPTANCE TEST: FAIL\n";
    echo "  One or more checks failed. See log above.\n";
}
echo str_repeat("=", 60) . "\n\n";

// Write log to storage/logs (outside public/storage — excluded from release ZIPs)
$logPath = $root . '/storage/logs/acceptance_test_log.txt';
if (!is_dir(dirname($logPath))) {
    mkdir(dirname($logPath), 0755, true);
}
file_put_contents($logPath, implode("\n", $log));
echo "Log written to: {$logPath}\n";

exit($PASS ? 0 : 1);
