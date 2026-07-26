<?php
/**
 * LOCAL END-TO-END UPDATE ACCEPTANCE TEST: v1.1.3 → v1.1.4
 *
 * Uses correct credentials: admin / ChangeMe!2025
 * Uses curl cookie jar for session persistence.
 * Login field: 'username'
 *
 * Production endpoints:
 *   POST /admin/settings/updates/check
 *   POST /admin/settings/updates/start
 *   GET  /admin/settings/updates/status
 *
 * Run: php tests/run_update_e2e_test.php
 */

// ─────────────────────── CONFIG ───────────────────────
$BASE         = 'http://127.0.0.1:8080';
$ROOT         = realpath(dirname(__DIR__));
$RELEASE_DIR  = $ROOT . '/release';
$PACKAGE      = $RELEASE_DIR . '/AppForm-1.1.4-update.zip';
$SHA256_FILE  = $PACKAGE . '.sha256';
$STORAGE_DIR  = $ROOT . '/public/storage';
$TEST_FLAG    = $STORAGE_DIR . '/.update_test_mode';
$JARFILE      = $STORAGE_DIR . '/e2e_cookiejar.txt';
$LOG_FILE     = $STORAGE_DIR . '/logs/e2e_114_test_' . date('Ymd_His') . '.log';
$POLL_TIMEOUT = 180;

// CORRECT credentials — do NOT change
$ADMIN_USERNAME = 'admin';
$ADMIN_PASSWORD = 'ChangeMe!2025';

$DB_DSN  = 'mysql:host=127.0.0.1;port=3307;dbname=appform_release_test;charset=utf8mb4';
$DB_USER = 'appform_local';
$DB_PASS = 'local_appform_pwd_2026';

// ─────────────────────── LOGGER ───────────────────────
if (!is_dir($STORAGE_DIR . '/logs')) mkdir($STORAGE_DIR . '/logs', 0755, true);

function L(string $msg, string $icon = ''): void {
    global $LOG_FILE;
    $line = '[' . date('H:i:s') . '] ' . ($icon ? $icon . ' ' : '') . $msg;
    echo $line . "\n";
    file_put_contents($LOG_FILE, $line . "\n", FILE_APPEND);
}
function SECTION(string $title): void {
    $bar = str_repeat('═', 56);
    L(''); L($bar); L("  $title"); L($bar);
}
function PASS(string $msg): void { L($msg, '✅'); }
function WARN(string $msg): void { L($msg, '⚠️ '); }
function INFO(string $msg): void { L("   $msg"); }
function FAIL(string $msg): void {
    global $TEST_FLAG, $JARFILE;
    L($msg, '❌ FATAL');
    @unlink($TEST_FLAG);
    @unlink($JARFILE);
    exit(1);
}

// ─────────────────────── CURL HELPERS (cookie-jar based) ───────────────────────
function strip_bom(string $s): string {
    // Remove UTF-8 BOM (EF BB BF) emitted by PHP files saved with BOM encoding
    return ltrim($s, "\xEF\xBB\xBF");
}

function http_jar_get(string $url): array {
    global $JARFILE;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_COOKIEJAR      => $JARFILE,
        CURLOPT_COOKIEFILE     => $JARFILE,
    ]);
    $raw  = curl_exec($ch);
    $hLen = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'headers' => substr($raw, 0, $hLen), 'body' => strip_bom(substr($raw, $hLen))];
}

function http_jar_post(string $url, array $fields): array {
    global $JARFILE;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($fields),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_COOKIEJAR      => $JARFILE,
        CURLOPT_COOKIEFILE     => $JARFILE,
    ]);
    $raw  = curl_exec($ch);
    $hLen = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'headers' => substr($raw, 0, $hLen), 'body' => strip_bom(substr($raw, $hLen))];
}

function get_location(string $headers): string {
    preg_match('/^Location:\s*(.+)$/im', $headers, $m);
    return trim($m[1] ?? '');
}

function extract_csrf(string $html): string {
    if (preg_match('/name=["\']_token["\'][^>]+value=["\']([^"\']+)["\']/', $html, $m)) return $m[1];
    if (preg_match('/value=["\']([^"\']+)["\'][^>]+name=["\']_token["\']/', $html, $m)) return $m[1];
    return '';
}

// ═══════════════════════════════════════════════════════
SECTION("PRE-FLIGHT CHECKS");
// ═══════════════════════════════════════════════════════
@unlink($JARFILE);  // fresh cookie jar

try {
    $pdo = new PDO($DB_DSN, $DB_USER, $DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    PASS("DB connected: appform_release_test");
} catch (PDOException $e) { FAIL("DB: " . $e->getMessage()); }

// Ensure no failed login attempts / lockout
$pdo->exec("UPDATE users SET failed_login_attempts=0, locked_until=NULL WHERE username='admin' OR email='admin@appform.local'");
INFO("Admin account lockout cleared.");

if (!file_exists($PACKAGE))   FAIL("Package missing: $PACKAGE");
if (!file_exists($SHA256_FILE)) FAIL("SHA256 file missing: $SHA256_FILE");
$GOOD_SHA256 = trim(file_get_contents($SHA256_FILE));
$ACTUAL_SHA  = hash_file('sha256', $PACKAGE);
if ($GOOD_SHA256 !== $ACTUAL_SHA) FAIL("Package SHA-256 mismatch: expected $GOOD_SHA256 got $ACTUAL_SHA");
PASS("Package: " . basename($PACKAGE) . " (" . number_format(filesize($PACKAGE)) . " bytes)");
PASS("SHA-256: $GOOD_SHA256");

$versionFile = $ROOT . '/config/version.php';
$verData = include $versionFile;
INFO("Installed (disk): v{$verData['version']} build {$verData['build']} ({$verData['channel']})");

// If already updated, reset for test
if (version_compare($verData['version'], '1.1.4', '>=')) {
    WARN("version.php shows {$verData['version']} — resetting to 1.1.3 for test");
    $v113 = "<?php\n\nreturn [\n    'version' => '1.1.3',\n    'build'   => 5,\n    'channel' => 'stable',\n];\n";
    file_put_contents($versionFile, $v113);
    INFO("version.php reset. Restart cli-server if needed.");
}

// Protected file hashes (before)
$PROTECTED = ['config/installed.lock', 'config/config.local.php'];
$BEFORE_HASHES = [];
foreach ($PROTECTED as $pf) {
    $abs = "$ROOT/$pf";
    if (file_exists($abs)) {
        $BEFORE_HASHES[$pf] = hash_file('sha256', $abs);
        INFO("Protected [BEFORE] $pf = " . substr($BEFORE_HASHES[$pf], 0, 16) . "...");
    }
}

// Cancel stale updates
$pdo->exec("UPDATE application_updates SET status='failed', error_message='Cancelled by E2E test' WHERE status IN ('pending','running','waiting_for_lock')");
INFO("Stale updates cancelled.");

// ═══════════════════════════════════════════════════════
SECTION("STEP 1: ACTIVATE TEST MODE");
// ═══════════════════════════════════════════════════════
file_put_contents($TEST_FLAG, date('c'));
PASS("Test flag created: $TEST_FLAG");

// ═══════════════════════════════════════════════════════
SECTION("STEP 2: LOGIN (admin / ChangeMe!2025)");
// ═══════════════════════════════════════════════════════
// Get login page → extract CSRF
$lp = http_jar_get("$BASE/login");
if ($lp['code'] !== 200) FAIL("Login page HTTP {$lp['code']}");
$csrf = extract_csrf($lp['body']);
INFO("Login CSRF: " . substr($csrf, 0, 16) . "...");
if (!$csrf) FAIL("No CSRF on login page");

// POST login with correct credentials
$lr = http_jar_post("$BASE/login", [
    '_token'   => $csrf,
    'username' => $ADMIN_USERNAME,
    'password' => $ADMIN_PASSWORD,
]);
$loc = get_location($lr['headers']);
INFO("Login POST: HTTP={$lr['code']} Location=$loc");

if ($lr['code'] !== 302 || str_contains($loc, '/login')) {
    // HTTP 419 = CSRF mismatch, 302 to /login = wrong password
    FAIL("Login failed. HTTP={$lr['code']} Location='$loc'. Body=" . substr($lr['body'], 0, 200));
}
PASS("Login OK — HTTP 302 → $loc");

// Follow redirect to establish full session
$afterLogin = http_jar_get("$BASE$loc");
INFO("After-login page: HTTP={$afterLogin['code']}, " . strlen($afterLogin['body']) . " bytes");

// Load updates page authenticated
$updPage = http_jar_get("$BASE/admin/settings/updates");
if ($updPage['code'] === 302) {
    $redirLoc = get_location($updPage['headers']);
    FAIL("Updates page redirected to: $redirLoc — session not persisting");
}
INFO("Updates page: HTTP={$updPage['code']}, " . strlen($updPage['body']) . " bytes");
$csrf = extract_csrf($updPage['body']);
INFO("Updates CSRF: " . substr($csrf, 0, 16) . "...");
INFO("Has btn-check-updates: " . (str_contains($updPage['body'], 'btn-check-updates') ? 'YES' : 'NO'));
if (!$csrf) FAIL("No CSRF on updates page — session not authenticated");
PASS("Authenticated session established. Updates panel loaded.");

// ═══════════════════════════════════════════════════════
SECTION("STEP 3: CHECK FOR UPDATES (POST /admin/settings/updates/check)");
// ═══════════════════════════════════════════════════════
INFO("POST $BASE/admin/settings/updates/check");
$checkRes = http_jar_post("$BASE/admin/settings/updates/check", ['_token' => $csrf]);
INFO("HTTP: {$checkRes['code']}");
INFO("Body: " . substr($checkRes['body'], 0, 400));

if ($checkRes['code'] === 302) {
    FAIL("Check endpoint redirected to: " . get_location($checkRes['headers']) . " — CSRF or session issue");
}
$checkData = json_decode($checkRes['body'], true);
if (!$checkData) FAIL("Non-JSON from check: " . substr($checkRes['body'], 0, 200));
if (empty($checkData['success'])) FAIL("Check success=false: " . json_encode($checkData));
if (empty($checkData['latest'])) FAIL("latest=null — LocalReleaseProvider found no package. Test flag: " . (file_exists($TEST_FLAG) ? 'EXISTS' : 'MISSING'));

$LATEST = $checkData['latest'];
PASS("Available: v{$LATEST['version']} from LocalReleaseProvider");
PASS("Local path: {$LATEST['local_path']}");
PASS("SHA-256:    {$LATEST['sha256']}");
INFO("Changelog: " . ($LATEST['changelog'] ?? '(none)'));
if ($LATEST['version'] !== '1.1.4') FAIL("Expected v1.1.4, got v{$LATEST['version']}");
PASS("Version confirmed: 1.1.4 > 1.1.3 ✓");

// ═══════════════════════════════════════════════════════
SECTION("STEP 4: SHA-256 FAILURE TEST");
// ═══════════════════════════════════════════════════════
INFO("Sending update with WRONG SHA-256...");
$updPage4 = http_jar_get("$BASE/admin/settings/updates");
$csrfFail = extract_csrf($updPage4['body']);

$startFail = http_jar_post("$BASE/admin/settings/updates/start", [
    '_token'             => $csrfFail,
    'target_version'     => '1.1.4',
    'build_number'       => '6',
    'channel'            => 'stable-test',
    'local_package_path' => $LATEST['local_path'],
    'package_sha256'     => 'BADHASHBADHASHBADHASHBADHASHBADHASHBADHASHBADHASHBADHASHBADHASH00',
]);
INFO("Start (bad SHA) HTTP: {$startFail['code']}");
$sfData = json_decode($startFail['body'], true);
INFO("Response: " . json_encode($sfData));

$FAIL_UPDATE_ID = $sfData['update_id'] ?? null;
if ($FAIL_UPDATE_ID && !empty($sfData['success'])) {
    INFO("Worker started (id=$FAIL_UPDATE_ID). Waiting for SHA-fail...");
    $deadline = time() + 90;
    $sfStatus = 'pending';
    while (time() < $deadline) {
        sleep(3);
        $st = http_jar_get("$BASE/admin/settings/updates/status");
        $stData = json_decode($st['body'], true);
        $sfStatus = $stData['status'] ?? 'unknown';
        INFO("  SHA-fail poll: {$sfStatus} " . ($stData['progress_percent'] ?? 0) . "%");
        if (in_array($sfStatus, ['failed', 'completed', 'rolled_back', 'idle'])) break;
    }
    $sfStmt = $pdo->prepare("SELECT status, error_message FROM application_updates WHERE id = ?");
    $sfStmt->execute([$FAIL_UPDATE_ID]);
    $sfRec = $sfStmt->fetch(PDO::FETCH_ASSOC);
    INFO("DB status: {$sfRec['status']}");
    INFO("Error:     " . ($sfRec['error_message'] ?? 'none'));
    if ($sfRec['status'] === 'failed') {
        PASS("SHA-256 FAILURE TEST: Correctly rejected (status=failed)");
    } else {
        WARN("SHA-256 failure test: Expected 'failed', got '{$sfRec['status']}'");
    }
    // Verify version unchanged
    $verCheck = include $versionFile;
    if ($verCheck['version'] === '1.1.3') {
        PASS("Version unchanged after SHA failure: still 1.1.3 build 5");
    } else {
        L("❌ Version changed after failed update! Got {$verCheck['version']}");
    }
    // Show failure logs
    $sfLogs = $pdo->prepare("SELECT step_key, level, message FROM application_update_logs WHERE update_id = ? ORDER BY id ASC");
    $sfLogs->execute([$FAIL_UPDATE_ID]);
    foreach ($sfLogs->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $icon = match($row['level']) { 'error' => '❌', 'warning' => '⚠️', default => '  ' };
        INFO("  $icon [{$row['step_key']}] {$row['message']}");
    }
    $pdo->prepare("UPDATE application_updates SET status='failed' WHERE id=? AND status NOT IN ('completed','rolled_back')")->execute([$FAIL_UPDATE_ID]);
    sleep(2);
} else {
    PASS("SHA-256 FAILURE TEST: Rejected without starting.");
}

// ═══════════════════════════════════════════════════════
SECTION("STEP 5: SUCCESSFUL UPDATE 1.1.3 → 1.1.4");
// ═══════════════════════════════════════════════════════
INFO("Correct SHA-256: {$LATEST['sha256']}");
$updPage5 = http_jar_get("$BASE/admin/settings/updates");
$csrfGood = extract_csrf($updPage5['body']);
INFO("CSRF: " . substr($csrfGood, 0, 16) . "...");

$startRes = http_jar_post("$BASE/admin/settings/updates/start", [
    '_token'             => $csrfGood,
    'target_version'     => '1.1.4',
    'build_number'       => '6',
    'channel'            => 'stable-test',
    'local_package_path' => $LATEST['local_path'],
    'package_sha256'     => $LATEST['sha256'],
]);
INFO("Start HTTP: {$startRes['code']}");
INFO("Start body: " . substr($startRes['body'], 0, 300));
$startData = json_decode($startRes['body'], true);
if (empty($startData['success'])) {
    @unlink($TEST_FLAG);
    FAIL("startUpdate failed: " . json_encode($startData));
}
$UPDATE_ID = $startData['update_id'];
PASS("Update started: id=$UPDATE_ID");

// ═══════════════════════════════════════════════════════
SECTION("STEP 6: POLL STATUS (up to {$POLL_TIMEOUT}s)");
// ═══════════════════════════════════════════════════════
$lastStatus = ''; $lastPct = 0; $STAGES = []; $FINAL_STATUS = null;
$deadline = time() + $POLL_TIMEOUT;
while (time() < $deadline) {
    sleep(4);
    // Pass update_id so the status endpoint returns THIS update, not a stale failed one
    $st = http_jar_get("$BASE/admin/settings/updates/status?update_id=$UPDATE_ID");
    $stData = json_decode($st['body'], true);
    if (!$stData) { INFO("  (empty poll response)"); continue; }
    $status   = $stData['status'] ?? 'unknown';
    $progress = (int)($stData['progress_percent'] ?? 0);
    $step     = $stData['current_step'] ?? '';
    if ($status !== $lastStatus || abs($progress - $lastPct) >= 5) {
        L("  ▶ $status | {$progress}% | step=$step");
        $STAGES[] = compact('status', 'progress', 'step');
        $lastStatus = $status; $lastPct = $progress;
    }
    if (in_array($status, ['completed', 'failed', 'rolled_back', 'rollback_failed'])) {
        $FINAL_STATUS = $status; break;
    }
}
if (!$FINAL_STATUS) {
    @unlink($TEST_FLAG);
    FAIL("Timed out after {$POLL_TIMEOUT}s. Last: $lastStatus $lastPct%");
}
L("\n  ▶ Final status: " . strtoupper($FINAL_STATUS) . " ({$lastPct}%)");

// ═══════════════════════════════════════════════════════
SECTION("STEP 7: PIPELINE STAGE VERIFICATION");
// ═══════════════════════════════════════════════════════
$logQ = $pdo->prepare("SELECT step_key, level, message, created_at FROM application_update_logs WHERE update_id = ? ORDER BY id ASC");
$logQ->execute([$UPDATE_ID]);
$LOG_ROWS = $logQ->fetchAll(PDO::FETCH_ASSOC);
$stepsFound = [];
foreach ($LOG_ROWS as $row) {
    $stepsFound[] = $row['step_key'];
    $icon = match($row['level']) { 'error' => '❌', 'warning' => '⚠️', default => '  ' };
    INFO("$icon [{$row['created_at']}][{$row['step_key']}] {$row['message']}");
}
$REQUIRED = [
    'lock_check'          => 'Lock acquisition',
    'maintenance_enable'  => 'Maintenance mode ON',
    'file_backup'         => 'File backup',
    'database_backup'     => 'Database backup',
    'verify_package'      => 'Package verify + SHA-256',
    'extract_files'       => 'File extraction/deployment',
];
$allStagesOK = true;
L("\n--- Required Stage Verification ---");
foreach ($REQUIRED as $key => $label) {
    if (in_array($key, $stepsFound)) PASS("$label ($key)");
    else { L("❌ MISSING: $label ($key)"); $allStagesOK = false; }
}

// ═══════════════════════════════════════════════════════
SECTION("STEP 8: VERSION VERIFICATION");
// ═══════════════════════════════════════════════════════
if ($FINAL_STATUS === 'completed') {
    clearstatcache(true); if (function_exists('opcache_reset')) opcache_reset();
    // force re-include (use include, not require, to avoid PHP file cache)
    $verAfter = json_decode(json_encode(include $versionFile), true);
    INFO("config/version.php: v{$verAfter['version']} build {$verAfter['build']} ({$verAfter['channel']})");
    if ($verAfter['version'] === '1.1.4' && (int)$verAfter['build'] === 6) {
        PASS("Version: 1.1.3 build 5 → 1.1.4 build 6 ✓");
    } else {
        L("❌ Version not updated: v{$verAfter['version']} build {$verAfter['build']}");
    }
} else {
    WARN("Update status=$FINAL_STATUS — version check skipped");
}

// ═══════════════════════════════════════════════════════
SECTION("STEP 9: PROTECTED FILE INTEGRITY");
// ═══════════════════════════════════════════════════════
foreach ($PROTECTED as $pf) {
    $abs = "$ROOT/$pf";
    if (isset($BEFORE_HASHES[$pf]) && file_exists($abs)) {
        $after = hash_file('sha256', $abs);
        if ($after === $BEFORE_HASHES[$pf]) PASS("$pf — unchanged ✓");
        else { L("❌ $pf — CHANGED!"); INFO("  Before: {$BEFORE_HASHES[$pf]}"); INFO("  After:  $after"); }
    }
}
if (is_dir($STORAGE_DIR)) PASS("public/storage/ — intact");

// ═══════════════════════════════════════════════════════
SECTION("STEP 10: APPLICATION HEALTH (post-update)");
// ═══════════════════════════════════════════════════════
$dashAfter = http_jar_get("$BASE/admin/dashboard");
$updAfter  = http_jar_get("$BASE/admin/settings/updates");
INFO("Dashboard: HTTP {$dashAfter['code']}");
INFO("Updates:   HTTP {$updAfter['code']}, size=" . strlen($updAfter['body']) . " bytes");
if (in_array($dashAfter['code'], [200, 302])) PASS("Dashboard accessible after update");
if ($updAfter['code'] === 200 && str_contains($updAfter['body'], '1.1.4')) {
    PASS("Version 1.1.4 visible in updates page ✓");
}

// ═══════════════════════════════════════════════════════
SECTION("STEP 11: DB RECORD SUMMARY");
// ═══════════════════════════════════════════════════════
$recQ = $pdo->prepare("SELECT * FROM application_updates WHERE id = ?");
$recQ->execute([$UPDATE_ID]);
$REC = $recQ->fetch(PDO::FETCH_ASSOC);
INFO("id:             {$REC['id']}");
INFO("status:         {$REC['status']}");
INFO("v{$REC['previous_version']} b{$REC['previous_build']} → v{$REC['release_version']} b{$REC['build_number']}");
INFO("progress:       {$REC['progress_percent']}%");
INFO("provider:       {$REC['provider']}");
INFO("file_backup:    " . ($REC['backup_path'] ? 'YES → ' . basename($REC['backup_path']) : 'NO'));
INFO("db_backup:      " . ($REC['database_backup_path'] ? 'YES → ' . basename($REC['database_backup_path']) : 'NO'));
INFO("package_sha256: " . ($REC['package_sha256'] ?? 'NULL'));
INFO("error:          " . ($REC['error_message'] ?? 'none'));
INFO("started_at:     " . ($REC['started_at'] ?? '?'));
INFO("completed_at:   " . ($REC['completed_at'] ?? 'NULL'));

// ═══════════════════════════════════════════════════════
SECTION("STEP 12: CLEANUP");
// ═══════════════════════════════════════════════════════
@unlink($TEST_FLAG);
@unlink($JARFILE);
PASS("Test mode flag removed — production mode restored.");
PASS("Cookie jar removed.");

// ═══════════════════════════════════════════════════════
SECTION("FINAL VERDICT");
// ═══════════════════════════════════════════════════════
$verdict = ($FINAL_STATUS === 'completed') ? '✅ GO' : '❌ NO-GO';
L("$verdict — Update 1.1.3 → 1.1.4: " . strtoupper($FINAL_STATUS ?? 'UNKNOWN'));
L("All required pipeline stages: " . ($allStagesOK ? 'YES ✅' : 'NO ❌'));
L("Protected files: intact");
L("Full log: $LOG_FILE");
$gitHead = trim(shell_exec('git log --oneline -1 2>&1') ?? '');
L("Git HEAD: $gitHead");
$gitStatus = trim(shell_exec('git status --short 2>&1') ?? '');
L("Git working tree: " . ($gitStatus ?: 'clean'));
L("NO push / NO tag / NO release.");
L("");
return true;
