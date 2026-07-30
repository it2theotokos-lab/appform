<?php
/**
 * HealthCheckTest.php — v1.1.18
 *
 * Tests:
 *  1. Development config  → Health Page shows "Development"
 *  2. Production config   → Health Page shows "Production"
 *  3. Regression: health view file exists and contains Environment card
 */

echo "========================================\n";
echo "  HEALTH PAGE ENVIRONMENT DISPLAY TESTS\n";
echo "========================================\n";

$healthView = __DIR__ . '/../src/Views/admin/health.php';
$viewSource  = file_get_contents($healthView);

// ── Test 1: No hardcoded "Production" literal remains in the view ──────────
// The old bug was a literal string <h4 ...>Production</h4>.
// After the fix, the value comes from App::$config, so the literal must be gone.
$hasHardcodedProduction = (bool) preg_match(
    '/<h4[^>]*>\s*Production\s*<\/h4>/i',
    $viewSource
);
assert(
    !$hasHardcodedProduction,
    "Test 1 Failed: health.php still contains hardcoded 'Production' string — bug not fixed."
);
echo "  ✅ Test 1 Passed: No hardcoded 'Production' literal in health view.\n";

// ── Test 2: View reads env from App::$config ──────────────────────────────
// The fix must use App::$config (the live configuration), not any other source.
$usesAppConfig = str_contains($viewSource, 'App::$config[\'app\'][\'env\']')
              || str_contains($viewSource, 'App::$config["app"]["env"]');
assert(
    $usesAppConfig,
    "Test 2 Failed: health.php does not read env from \\App\\Core\\App::\$config['app']['env']."
);
echo "  ✅ Test 2 Passed: Environment card reads from App::\$config['app']['env'].\n";

// ── Test 3: Development value → "Development" displayed ───────────────────
// Simulate by temporarily patching App::$config and rendering the view output.
$originalConfig = \App\Core\App::$config;

\App\Core\App::$config = array_replace_recursive($originalConfig, [
    'app' => ['env' => 'development']
]);

ob_start();
include $healthView;
$devOutput = ob_get_clean();

$showsDevelopment = str_contains($devOutput, 'Development');
assert(
    $showsDevelopment,
    "Test 3 Failed: With env='development', health page does not show 'Development'."
);
echo "  ✅ Test 3 Passed: env='development' → Health Page shows 'Development'.\n";

// ── Test 4: Production value → "Production" displayed ─────────────────────
\App\Core\App::$config = array_replace_recursive($originalConfig, [
    'app' => ['env' => 'production']
]);

ob_start();
include $healthView;
$prodOutput = ob_get_clean();

$showsProduction = str_contains($prodOutput, 'Production');
assert(
    $showsProduction,
    "Test 4 Failed: With env='production', health page does not show 'Production'."
);
echo "  ✅ Test 4 Passed: env='production' → Health Page shows 'Production'.\n";

// Restore original config
\App\Core\App::$config = $originalConfig;

// ── Test 5: Regression — Environment card structure intact ────────────────
$hasEnvCard = str_contains($viewSource, 'Environment')
           && str_contains($viewSource, 'fa-server');
assert(
    $hasEnvCard,
    "Test 5 Failed: Environment card structure (label + icon) is missing from health view."
);
echo "  ✅ Test 5 Passed: Environment card structure (label + icon) remains intact.\n";

// ── Test 6: Regression — Status, PHP Version cards still present ──────────
$hasStatusCard  = str_contains($viewSource, 'HEALTHY');
$hasPhpCard     = str_contains($viewSource, 'phpversion()');
$hasSubsystems  = str_contains($viewSource, 'Subsystems Status');
assert(
    $hasStatusCard && $hasPhpCard && $hasSubsystems,
    "Test 6 Failed: Regression — other health cards (Status/PHP/Subsystems) were accidentally removed."
);
echo "  ✅ Test 6 Passed: Status, PHP Version and Subsystems cards are all present.\n";

echo "========================================\n";
echo "  ALL HEALTH CHECK TESTS PASSED ✅\n";
echo "========================================\n";

return true;
