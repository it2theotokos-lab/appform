<?php
/**
 * LogoUploadTest.php — v1.1.20 Build 22
 *
 * Tests for the Logo Upload feature:
 * - Migration 032_app_logo.sql exists
 * - Storage directory exists
 * - SettingsController methods exist
 * - Routes are registered in index.php
 * - Build exclusions include logos dir
 * - Sidebar uses custom logo logic
 * - Settings index includes logo UI
 */

require_once __DIR__ . '/../vendor/autoload.php';

echo "--- Running Logo Upload Tests ---\n";

$projectRoot = dirname(__DIR__);

// ── Test 1: Migration 032_app_logo.sql exists ─────────────────────────────────
$migrationFile = $projectRoot . '/db/migrations/032_app_logo.sql';
assert(file_exists($migrationFile), "Test 1 Failed: 032_app_logo.sql migration must exist.");
$migrationContent = file_get_contents($migrationFile);
assert(
    strpos($migrationContent, 'app_logo_path') !== false,
    "Test 1b Failed: Migration must insert 'app_logo_path' key."
);
echo "Test 1 Passed: Migration 032_app_logo.sql exists and contains correct key.\n";

// ── Test 2: Storage logos directory exists ────────────────────────────────────
$logosDir = $projectRoot . '/public/storage/logos';
assert(is_dir($logosDir), "Test 2 Failed: public/storage/logos/ directory must exist.");
echo "Test 2 Passed: public/storage/logos/ directory exists.\n";

// ── Test 3: SettingsController has uploadLogo() method ───────────────────────
$controllerFile = $projectRoot . '/src/Controllers/SettingsController.php';
assert(file_exists($controllerFile), "Test 3 Failed: SettingsController.php must exist.");
$controllerContent = file_get_contents($controllerFile);
assert(
    strpos($controllerContent, 'public function uploadLogo') !== false,
    "Test 3 Failed: SettingsController must have uploadLogo() method."
);
echo "Test 3 Passed: uploadLogo() method exists in SettingsController.\n";

// ── Test 4: SettingsController has deleteLogo() method ───────────────────────
assert(
    strpos($controllerContent, 'public function deleteLogo') !== false,
    "Test 4 Failed: SettingsController must have deleteLogo() method."
);
echo "Test 4 Passed: deleteLogo() method exists in SettingsController.\n";

// ── Test 5: Logo upload route registered in index.php ────────────────────────
$routerFile = $projectRoot . '/public/index.php';
assert(file_exists($routerFile), "Test 5 Failed: public/index.php must exist.");
$routerContent = file_get_contents($routerFile);
assert(
    strpos($routerContent, '/admin/settings/logo/upload') !== false,
    "Test 5 Failed: Route POST /admin/settings/logo/upload must be registered."
);
echo "Test 5 Passed: Logo upload route is registered.\n";

// ── Test 6: Logo delete route registered in index.php ────────────────────────
assert(
    strpos($routerContent, '/admin/settings/logo/delete') !== false,
    "Test 6 Failed: Route POST /admin/settings/logo/delete must be registered."
);
echo "Test 6 Passed: Logo delete route is registered.\n";

// ── Test 7: Build script excludes logos directory ─────────────────────────────
$buildFile = $projectRoot . '/tools/release/build.php';
assert(file_exists($buildFile), "Test 7 Failed: tools/release/build.php must exist.");
$buildContent = file_get_contents($buildFile);
assert(
    strpos($buildContent, 'public/storage/logos') !== false,
    "Test 7 Failed: build.php must exclude 'public/storage/logos' from release ZIPs."
);
echo "Test 7 Passed: Logo dir is excluded from release build.\n";

// ── Test 8: Sidebar contains custom logo resolution logic ────────────────────
$sidebarFile = $projectRoot . '/src/Views/layouts/sidebar.php';
assert(file_exists($sidebarFile), "Test 8 Failed: sidebar.php must exist.");
$sidebarContent = file_get_contents($sidebarFile);
assert(
    strpos($sidebarContent, 'app_logo_path') !== false,
    "Test 8 Failed: sidebar.php must query app_logo_path from system_settings."
);
assert(
    strpos($sidebarContent, 'hasCustomLogo') !== false,
    "Test 8b Failed: sidebar.php must have \$hasCustomLogo conditional."
);
echo "Test 8 Passed: Sidebar contains custom logo resolution logic.\n";

// ── Test 9: Settings index contains logo upload UI ────────────────────────────
$settingsIndexFile = $projectRoot . '/src/Views/settings/index.php';
assert(file_exists($settingsIndexFile), "Test 9 Failed: settings/index.php must exist.");
$settingsContent = file_get_contents($settingsIndexFile);
assert(
    strpos($settingsContent, 'logo-upload-form') !== false,
    "Test 9 Failed: settings/index.php must contain logo-upload-form."
);
assert(
    strpos($settingsContent, '/admin/settings/logo/upload') !== false,
    "Test 9b Failed: settings/index.php must point to the upload route."
);
echo "Test 9 Passed: Settings index contains logo upload UI.\n";

// ── Test 10: Upload uses MIME validation (getimagesize) ───────────────────────
assert(
    strpos($controllerContent, 'getimagesize') !== false,
    "Test 10 Failed: uploadLogo() must use getimagesize() for MIME validation."
);
echo "Test 10 Passed: Server-side MIME validation via getimagesize() is present.\n";

// ── Test 11: Upload enforces size limit ───────────────────────────────────────
assert(
    strpos($controllerContent, '2 * 1024 * 1024') !== false,
    "Test 11 Failed: uploadLogo() must enforce 2 MB size limit."
);
echo "Test 11 Passed: 2 MB size limit is enforced.\n";

// ── Test 12: Old logo is deleted before saving new one ────────────────────────
assert(
    strpos($controllerContent, '@unlink') !== false,
    "Test 12 Failed: uploadLogo() must unlink the old logo file before saving new one."
);
echo "Test 12 Passed: Old logo cleanup (unlink) is implemented.\n";

echo "\n✓ All Logo Upload Tests Passed!\n";
return true;
