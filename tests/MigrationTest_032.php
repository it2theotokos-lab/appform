<?php
/**
 * MigrationTest_032.php — Migration SQL + Runtime regression test
 *
 * Tests (pure PHP string analysis — no DB driver required):
 *   A) 032_app_logo.sql uses INSERT IGNORE (upgrade-safe / idempotent)
 *   B) Canonical column names: setting_key, setting_value, setting_type, is_public
 *   C) No legacy/non-existent columns: `key`, `value`, `description`
 *   D) Inserts correct key 'app_logo_path'
 *   E) SQL is syntactically well-formed (1 statement, balanced quotes)
 *   F) Upgrade-from-v1.1.19 safety: INSERT IGNORE safe whether row exists or not
 *   G) SettingsController.php uses correct column names in all logo queries
 *   H) sidebar.php uses correct column names in logo SELECT
 *   I) No legacy column references in any logo-related file
 *   J) Release ZIP (if already built) contains corrected migration
 */

echo "=== Migration Test Suite: 032_app_logo.sql ===\n\n";

$projectRoot = dirname(__DIR__);
$migSql  = file_get_contents($projectRoot . '/db/migrations/032_app_logo.sql');
$ctrl    = file_get_contents($projectRoot . '/src/controllers/SettingsController.php');
$sidebar = file_get_contents($projectRoot . '/src/Views/layouts/sidebar.php');

$allPassed = true;
function check($label, $condition, $detail = '') {
    global $allPassed;
    if ($condition) {
        echo "[PASSED] $label\n";
    } else {
        echo "[FAILED] $label" . ($detail ? " — $detail" : "") . "\n";
        $allPassed = false;
    }
}

// ── TEST A ─────────────────────────────────────────────────────────────────────
check('A: INSERT IGNORE present (upgrade-safe / idempotent)',
    stripos($migSql, 'INSERT IGNORE') !== false);

// ── TEST B ─────────────────────────────────────────────────────────────────────
check('B: Uses column setting_key',   strpos($migSql, 'setting_key')   !== false);
check('B: Uses column setting_value', strpos($migSql, 'setting_value') !== false);
check('B: Uses column setting_type',  strpos($migSql, 'setting_type')  !== false);
check('B: Uses column is_public',     strpos($migSql, 'is_public')     !== false);

// ── TEST C ─────────────────────────────────────────────────────────────────────
check('C: No backtick `key` column',         strpos($migSql, '`key`')      === false, 'legacy `key` found');
check('C: No backtick `value` column',       strpos($migSql, '`value`')    === false, 'legacy `value` found');
check('C: No `description` column',          strpos($migSql, 'description') === false, 'non-existent `description` found');

// ── TEST D ─────────────────────────────────────────────────────────────────────
check("D: Inserts 'app_logo_path' value",
    strpos($migSql, "'app_logo_path'") !== false);

// ── TEST E ─────────────────────────────────────────────────────────────────────
$cleanSql   = trim(preg_replace('/--[^\n]*/', '', $migSql));
$semiCount  = substr_count($cleanSql, ';');
$quoteCount = substr_count($cleanSql, "'");
check("E: Exactly 1 SQL statement (1 semicolon)", $semiCount === 1, "found $semiCount");
check("E: Balanced single quotes (even count)",   $quoteCount % 2 === 0, "found $quoteCount");

// ── TEST F: Upgrade-from-v1.1.19 safety ───────────────────────────────────────
// In v1.1.19, 032 was not applied. INSERT IGNORE means:
//   - no row → inserts cleanly
//   - row already exists → silently skipped (no error)
// A plain INSERT INTO would throw a duplicate-key error on re-run.
check('F: Upgrade safety — INSERT IGNORE (not plain INSERT INTO)',
    stripos($migSql, 'INSERT IGNORE') !== false);
check('F: No plain INSERT INTO that would fail on re-run',
    preg_match('/INSERT INTO\s+system_settings/i', $migSql) === 0);

// ── TEST G: SettingsController ─────────────────────────────────────────────────
check('G: uploadLogo SELECT uses setting_value/setting_key',
    strpos($ctrl, "SELECT setting_value FROM system_settings WHERE setting_key = 'app_logo_path'") !== false);
check('G: deleteLogo SELECT uses setting_value/setting_key',
    substr_count($ctrl, "SELECT setting_value FROM system_settings WHERE setting_key = 'app_logo_path'") >= 2);
check('G: UPDATE uses setting_value/setting_key',
    strpos($ctrl, "UPDATE system_settings SET setting_value = '' WHERE setting_key = 'app_logo_path'") !== false);
check('G: INSERT uses setting_key/setting_value columns',
    strpos($ctrl, 'INSERT INTO system_settings (setting_key, setting_value, setting_type, is_public)') !== false);
check('G: No backtick `key` in controller',
    strpos($ctrl, '`key`') === false);
check('G: No legacy SELECT value FROM system_settings',
    strpos($ctrl, "SELECT value FROM system_settings") === false);

// ── TEST H: sidebar.php ────────────────────────────────────────────────────────
check('H: sidebar SELECT uses setting_value',
    strpos($sidebar, 'setting_value') !== false);
check('H: sidebar WHERE uses setting_key',
    strpos($sidebar, 'setting_key') !== false);
check('H: No legacy SELECT value FROM system_settings in sidebar',
    strpos($sidebar, "SELECT value FROM system_settings") === false);

// ── TEST I: No legacy refs anywhere ───────────────────────────────────────────
check('I: migration — no legacy key anywhere',
    strpos($migSql, '`key`') === false && strpos($migSql, " key ") === false);
check('I: controller — no SELECT value FROM system_settings',
    preg_match("/SELECT value FROM system_settings WHERE/", $ctrl) === 0);
check('I: sidebar — no `key` reference',
    strpos($sidebar, '`key`') === false);

// ── TEST J: Release ZIP integrity (skip if not yet built) ─────────────────────
$zipPath = $projectRoot . '/release/AppForm-1.1.21.zip';
if (file_exists($zipPath)) {
    $zip = new ZipArchive();
    if ($zip->open($zipPath) === true) {
        $sqlInZip = $zip->getFromName('db/migrations/032_app_logo.sql');
        $zip->close();
        check('J: ZIP contains 032_app_logo.sql with setting_key',
            $sqlInZip && strpos($sqlInZip, 'setting_key') !== false);
        check('J: ZIP 032_app_logo.sql has no legacy `key` column',
            $sqlInZip && strpos($sqlInZip, '`key`') === false);
    } else {
        echo "[SKIP]  J: Cannot open ZIP\n";
    }
} else {
    echo "[SKIP]  J: AppForm-1.1.21.zip not yet built — will re-verify after build\n";
}

// ── Summary ────────────────────────────────────────────────────────────────────
echo "\n";
if ($allPassed) {
    echo "✓ ALL MIGRATION TESTS PASSED\n";
    echo "  A: INSERT IGNORE — OK\n";
    echo "  B: Correct column names — OK\n";
    echo "  C: No legacy columns — OK\n";
    echo "  D: Correct app_logo_path value — OK\n";
    echo "  E: SQL well-formed — OK\n";
    echo "  F: Upgrade-from-v1.1.19 safe — OK\n";
    echo "  G: SettingsController queries — OK\n";
    echo "  H: sidebar.php queries — OK\n";
    echo "  I: No legacy refs anywhere — OK\n";
} else {
    echo "✗ SOME TESTS FAILED — do NOT proceed with release\n";
    exit(1);
}
