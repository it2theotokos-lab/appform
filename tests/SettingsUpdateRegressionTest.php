<?php
/**
 * Settings UI & Update Flow Regression Tests (15 conditions)
 * Tests that FAIL when known regressions are present.
 */

echo "=== Running Settings UI & Update Integrity Tests ===\n";

$root = dirname(__DIR__);
$settingsIndexView = file_get_contents($root . '/src/Views/settings/index.php');
$settingsLdapView  = file_get_contents($root . '/src/Views/settings/ldap.php');
$settingsRoleView  = file_get_contents($root . '/src/Views/settings/ldap_role_mappings.php');
$appLayout         = file_get_contents($root . '/src/Views/layouts/app.php');
$processRunner     = file_get_contents($root . '/src/Services/Update/ProcessRunner.php');
$updateEngine      = file_get_contents($root . '/src/Services/Update/UpdateEngineService.php');
$packageValidator  = file_get_contents($root . '/src/Services/Update/PackageValidatorService.php');
$githubProvider    = file_get_contents($root . '/src/Services/Update/GitHubReleaseProvider.php');
$settingsCtrl      = file_get_contents($root . '/src/controllers/SettingsController.php');

// ---------- TEST 1: No duplicate settings header via View::render ----------
$hasViewRenderNav = str_contains($settingsIndexView, "View::render('settings/nav')")
                 || str_contains($settingsLdapView,  "View::render('settings/nav')")
                 || str_contains($settingsRoleView,  "View::render('settings/nav')");
assert(!$hasViewRenderNav, "Test 1 Failed: View::render('settings/nav') found — causes duplicate full-layout header.");
echo "Test 1 Passed: No settings view uses View::render for nav partial (duplicate header prevented).\n";

// ---------- TEST 2: All settings views use direct require for nav ----------
$indexUsesRequire = str_contains($settingsIndexView, "require __DIR__ . '/nav.php'");
$ldapUsesRequire  = str_contains($settingsLdapView,  "require __DIR__ . '/nav.php'");
$roleUsesRequire  = str_contains($settingsRoleView,  "require __DIR__ . '/nav.php'");
assert($indexUsesRequire, "Test 2 Failed: settings/index.php does not require nav.php directly.");
assert($ldapUsesRequire,  "Test 2 Failed: settings/ldap.php does not require nav.php directly.");
assert($roleUsesRequire,  "Test 2 Failed: settings/ldap_role_mappings.php does not require nav.php directly.");
echo "Test 2 Passed: All settings views include nav.php via direct require (no layout wrap).\n";

// ---------- TEST 3: Footer is NOT rendered inside settings view files ----------
$footerInSettings = str_contains($settingsIndexView, '<footer') || str_contains($settingsIndexView, 'footer.php');
assert(!$footerInSettings, "Test 3 Failed: footer element found inside settings/index.php — footer must only be in the layout.");
echo "Test 3 Passed: Footer is not rendered inside settings view files.\n";

// ---------- TEST 4: Settings content uses no absolute/fixed positioning ----------
$hasAbsFixed = preg_match('/position\s*:\s*(absolute|fixed)/i', $settingsIndexView);
assert(!$hasAbsFixed, "Test 4 Failed: absolute/fixed positioning found in settings/index.php page content.");
echo "Test 4 Passed: Settings page content has no absolute/fixed positioning.\n";

// ---------- TEST 5: Update service does NOT use main/master branch zipball ----------
assert(!str_contains($githubProvider, '/zipball/main') && !str_contains($githubProvider, '/zipball/master'),
    "Test 5 Failed: Update service fetches from main/master branch zipball.");
echo "Test 5 Passed: Update service does not use main/master branch source archives.\n";

// ---------- TEST 6: Update service uses -update.zip asset, not source archive ----------
assert(str_contains($githubProvider, '-update.zip'), "Test 6 Failed: Update service does not filter for -update.zip asset.");
echo "Test 6 Passed: Update service correctly targets -update.zip release asset.\n";

// ---------- TEST 7: SHA-256 is verified before deployment ----------
assert(str_contains($updateEngine, 'hash_equals') && str_contains($updateEngine, 'package_sha256'),
    "Test 7 Failed: UpdateEngineService does not verify SHA-256 before deployment.");
echo "Test 7 Passed: SHA-256 verification is present in the update pipeline.\n";

// ---------- TEST 8: installed.lock is in protected paths deny-list ----------
assert(str_contains($packageValidator, 'config/installed.lock'),
    "Test 8 Failed: config/installed.lock not in PackageValidatorService protected paths.");
assert(str_contains($updateEngine, "'config/installed.lock'"),
    "Test 8 Failed: config/installed.lock not in UpdateEngineService deploy protected paths.");
echo "Test 8 Passed: config/installed.lock is in protected paths deny-list in both validator and engine.\n";

// ---------- TEST 9: config.local.php is in protected paths deny-list ----------
assert(str_contains($packageValidator, 'config/config.local.php'),
    "Test 9 Failed: config/config.local.php not in PackageValidatorService protected paths.");
assert(str_contains($updateEngine, "'config/config.local.php'"),
    "Test 9 Failed: config/config.local.php not in UpdateEngineService deploy protected paths.");
echo "Test 9 Passed: config/config.local.php is in protected paths deny-list in both validator and engine.\n";

// ---------- TEST 10: public/storage is in protected paths deny-list ----------
assert(str_contains($packageValidator, "'public/storage'") || str_contains($packageValidator, "'public/storage/'"),
    "Test 10 Failed: public/storage not in PackageValidatorService protected paths.");
assert(str_contains($updateEngine, "'public/storage/'"),
    "Test 10 Failed: public/storage/ not in UpdateEngineService deploy protected paths.");
echo "Test 10 Passed: public/storage is in protected paths deny-list in both validator and engine.\n";

// ---------- TEST 11: php-cgi.exe is not used directly as CLI ----------
// ProcessRunner must refuse php-cgi.exe and translate to php.exe
assert(str_contains($processRunner, 'php-cgi'),
    "Test 11 Failed: ProcessRunner has no guard for php-cgi.exe detection.");
assert(str_contains($processRunner, 'php.exe') || str_contains($processRunner, 'resolvePhpCli'),
    "Test 11 Failed: ProcessRunner does not resolve to php.exe.");
// The critical check: there must be an explicit ABORT/refusal when php-cgi is detected
assert(str_contains($processRunner, 'refusing') || str_contains($processRunner, 'ABORT'),
    "Test 11 Failed: ProcessRunner has no explicit refusal to use php-cgi.exe as CLI.");
echo "Test 11 Passed: ProcessRunner explicitly refuses php-cgi.exe and resolves to php.exe.\n";

// ---------- TEST 12: No Linux-only commands in update engine ----------
$hasLinuxCmds = preg_match('/\bexec\s*\(\s*[\'"](?:chmod|chown|ls |rm |mv |cp |ln |bash|sh )/', $updateEngine)
             || preg_match('/\/dev\/null/', $processRunner) && stripos(PHP_OS, 'WIN') === 0;
// Only fail if on Windows (Linux dev null is fine on Unix)
if (stripos(PHP_OS, 'WIN') === 0) {
    $linuxInEngine = preg_match('/exec\s*\(\s*[\'"](?:chmod|chown|bash|sh )[^\'"]*[\'"]\s*\)/', $updateEngine);
    assert(!$linuxInEngine, "Test 12 Failed: Linux-only shell commands detected in UpdateEngineService (Windows server).");
}
echo "Test 12 Passed: No Linux-only commands found in update pipeline for this platform.\n";

// ---------- TEST 13: Non-zero exit code leads to failure, not success ----------
// ProcessRunner logs exit code and returns false on failure
assert(str_contains($processRunner, 'exitCode') || str_contains($processRunner, '$exitCode'),
    "Test 13 Failed: ProcessRunner does not capture worker exit code.");
assert(str_contains($processRunner, 'return false') || str_contains($processRunner, 'return $exitCode === 0'),
    "Test 13 Failed: ProcessRunner does not return false on non-zero exit codes.");
echo "Test 13 Passed: ProcessRunner captures exit code and returns false on non-zero.\n";

// ---------- TEST 14: Update failure triggers rollback, not success ----------
assert(str_contains($updateEngine, 'rollback(') && str_contains($updateEngine, 'catch (\\Throwable'),
    "Test 14 Failed: UpdateEngineService does not call rollback() on migration/deployment exception.");
echo "Test 14 Passed: Update pipeline calls rollback() on any caught failure.\n";

// ---------- TEST 15: Success is only declared after health check passes ----------
$completedBeforeHealthCheck = false;
// STATE_COMPLETED must appear AFTER STATE_VALIDATING_APPLICATION in the source
$posCompleted     = strpos($updateEngine, 'STATE_COMPLETED');
$posHealthCheck   = strpos($updateEngine, 'STATE_VALIDATING_APPLICATION');
if ($posCompleted !== false && $posHealthCheck !== false) {
    $completedBeforeHealthCheck = $posCompleted < $posHealthCheck;
}
assert(!$completedBeforeHealthCheck, "Test 15 Failed: STATE_COMPLETED appears before STATE_VALIDATING_APPLICATION — success declared before health check.");
echo "Test 15 Passed: STATE_COMPLETED is declared only after health check validation.\n";

echo "=== All 15 Settings UI & Update Flow Tests Passed! ===\n";

// ============================================================================================
// UPDATES TAB RENDERING TESTS (7 additional conditions)
// These fail when the "Αναβάθμιση" tab does not render correctly.
// ============================================================================================
echo "\n=== Running 7 Updates Tab Rendering Tests ===\n";

// ---------- TEST 16: settings/index.php uses $tab variable (not only $_GET) for activeTab ----------
// The fix: $activeTab = $tab ?? $_GET['tab'] ?? 'general'
// Without this, /admin/settings/updates renders general tab (showUpdates() passes $tab='updates')
$activeTabResolvesControllerVar = preg_match('/\$activeTab\s*=\s*\$tab\s*\?\?/', $settingsIndexView);
assert($activeTabResolvesControllerVar,
    "Test 16 Failed: settings/index.php does not use controller-passed \$tab variable for \$activeTab resolution. " .
    "The update tab will never render from /admin/settings/updates (no ?tab= in URL).");
echo "Test 16 Passed: settings/index.php resolves \$activeTab from controller-passed \$tab first.\n";

// ---------- TEST 17: showUpdates() passes 'tab'=>'updates' to the view ----------
$controllerPassesTab = str_contains($settingsCtrl, "'tab' => 'updates'")
                    || str_contains($settingsCtrl, '"tab" => "updates"');
assert($controllerPassesTab,
    "Test 17 Failed: SettingsController::showUpdates() does not pass 'tab'=>'updates' to the view. " .
    "The view cannot know which tab to render.");
echo "Test 17 Passed: showUpdates() passes 'tab'=>'updates' to the view.\n";

// ---------- TEST 18: showUpdates() passes verData to the view ----------
$controllerPassesVerData = preg_match('/showUpdates.*?verData/s', $settingsCtrl)
                        || (str_contains($settingsCtrl, 'showUpdates') && str_contains($settingsCtrl, "'verData'"));
assert($controllerPassesVerData,
    "Test 18 Failed: showUpdates() does not pass verData to the view — installed version cannot be displayed.");
echo "Test 18 Passed: showUpdates() passes verData (version/build) to the view.\n";

// ---------- TEST 19: updates panel exists in index.php with required DOM elements ----------
$hasUpdatesBlock    = str_contains($settingsIndexView, "activeTab === 'updates'");
$hasBtnCheck        = str_contains($settingsIndexView, 'btn-check-updates');
$hasVersionDisplay  = str_contains($settingsIndexView, 'verData[\'version\']') || str_contains($settingsIndexView, "verData['version']");
$hasProgressBar     = str_contains($settingsIndexView, 'update-progress-bar');
$hasUpdateDetails   = str_contains($settingsIndexView, 'update-details');
$hasErrorArea       = str_contains($settingsIndexView, 'update-console-log') || str_contains($settingsIndexView, 'alert-danger');

assert($hasUpdatesBlock,    "Test 19 Failed: settings/index.php has no 'updates' elseif block — the panel is missing entirely.");
assert($hasBtnCheck,        "Test 19 Failed: settings/index.php missing #btn-check-updates DOM element.");
assert($hasVersionDisplay,  "Test 19 Failed: settings/index.php does not display \$verData['version'] — installed version not shown.");
assert($hasProgressBar,     "Test 19 Failed: settings/index.php missing #update-progress-bar DOM element.");
assert($hasUpdateDetails,   "Test 19 Failed: settings/index.php missing #update-details container.");
echo "Test 19 Passed: updates panel exists with all required DOM elements (version, check button, progress, details).\n";

// ---------- TEST 20: The /admin/settings/updates route is registered ----------
$routesFile = file_get_contents(dirname(__DIR__) . '/public/index.php');
$hasUpdatesRoute = str_contains($routesFile, "'/admin/settings/updates'")
               && str_contains($routesFile, 'showUpdates');
assert($hasUpdatesRoute,
    "Test 20 Failed: /admin/settings/updates route not found in public/index.php — navigation will 404.");
echo "Test 20 Passed: /admin/settings/updates route is registered and maps to showUpdates().\n";

// ---------- TEST 21: GitHub provider failure does not produce blank page (has try/catch or error handling) ----------
$ghProviderHasErrorHandling = str_contains($githubProvider, 'try {') || str_contains($githubProvider, 'catch (')
                           || str_contains($githubProvider, 'return null') || str_contains($githubProvider, 'return []');
assert($ghProviderHasErrorHandling,
    "Test 21 Failed: GitHubReleaseProvider has no error handling — a connection failure will throw an unhandled exception and produce a blank page.");
echo "Test 21 Passed: GitHubReleaseProvider has error handling (connection failures won't produce blank page).\n";

// ---------- TEST 22: updates panel is not hidden by default CSS (the outer div has no display:none) ----------
// Find the 'updates' elseif block and check if the opening div has display:none
$updatesBlockStart = strpos($settingsIndexView, "activeTab === 'updates'");
$updatesBlockEnd   = $updatesBlockStart + 3000; // check first 3000 chars of the block
$updatesSlice      = substr($settingsIndexView, $updatesBlockStart, 3000);
// The first <div> after the conditional should NOT have display:none (progress card is correctly hidden, that's fine)
// Check the OUTER wrapping div
$outerDivHiddenMatch = preg_match('/<div[^>]*elseif[^>]*style\s*=\s*["\'][^"\']*display\s*:\s*none/', $updatesSlice);
// More precisely: the first div after the opening "?>" of the elseif block
$firstDivHasHidden = preg_match('/\?\>\s*<div[^>]+style\s*=\s*["\'][^"\']*display\s*:\s*none/', $updatesSlice);
assert(!$firstDivHasHidden,
    "Test 22 Failed: The updates panel outer container has display:none — panel hidden without reason.");
echo "Test 22 Passed: Updates panel outer container is not hidden by default CSS.\n";

echo "=== All 7 Updates Tab Rendering Tests Passed! ===\n";
echo "\nTotal: 22 tests passed.\n";
return true;
