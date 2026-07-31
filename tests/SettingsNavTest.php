<?php
/**
 * SettingsNavTest.php — v1.1.20 Build 22
 *
 * Tests for settings navigation cleanup:
 * Verifies that Demo Data, Active Directory/LDAP, and Πρόσθετα tabs
 * are removed from nav.php, and that remaining tabs are present.
 */

echo "--- Running Settings Navigation Tests ---\n";

$navFile = __DIR__ . '/../src/Views/settings/nav.php';

// ── Test 1: nav.php file exists ───────────────────────────────────────────────
assert(file_exists($navFile), "Test 1 Failed: src/Views/settings/nav.php must exist.");
echo "Test 1 Passed: nav.php exists.\n";

$navContent = file_get_contents($navFile);

// ── Test 2: Demo Data tab is ABSENT ──────────────────────────────────────────
assert(
    strpos($navContent, 'tab=demo') === false,
    "Test 2 Failed: 'tab=demo' (Demo Data tab) must NOT appear in nav.php."
);
assert(
    strpos($navContent, 'fa-cubes') === false,
    "Test 2b Failed: Demo Data icon (fa-cubes) must NOT appear in nav.php."
);
echo "Test 2 Passed: Demo Data tab is absent.\n";

// ── Test 3: Active Directory / LDAP tab is ABSENT ────────────────────────────
assert(
    strpos($navContent, '/admin/settings/ldap') === false,
    "Test 3 Failed: LDAP route must NOT appear in nav.php."
);
assert(
    strpos($navContent, 'Active Directory') === false,
    "Test 3b Failed: 'Active Directory' label must NOT appear in nav.php."
);
echo "Test 3 Passed: Active Directory/LDAP tab is absent.\n";

// ── Test 4: Πρόσθετα / Plugins tab is ABSENT ─────────────────────────────────
assert(
    strpos($navContent, 'tab=plugins') === false,
    "Test 4 Failed: 'tab=plugins' (Πρόσθετα tab) must NOT appear in nav.php."
);
assert(
    strpos($navContent, 'Πρόσθετα') === false,
    "Test 4b Failed: Greek label 'Πρόσθετα' must NOT appear in nav.php."
);
echo "Test 4 Passed: Πρόσθετα/Plugins tab is absent.\n";

// ── Test 5: Required tabs ARE present ────────────────────────────────────────
$requiredTabs = [
    'tab=general'             => 'General Settings tab',
    'tab=backup'              => 'Backup tab',
    'tab=smtp'                => 'SMTP tab',
    'tab=global_notifications' => 'Global Notifications tab',
    'tab=restore'             => 'Restore tab',
    'tab=cloud'               => 'Cloud Backup tab',
    'tab=queue'               => 'Job Queue tab',
    'tab=audit'               => 'Audit tab',
];
foreach ($requiredTabs as $marker => $label) {
    assert(
        strpos($navContent, $marker) !== false,
        "Test 5 Failed: Required tab '{$label}' ({$marker}) must be present in nav.php."
    );
}
echo "Test 5 Passed: All required tabs are present.\n";

// ── Test 6: Translations are applied (uses __() calls) ───────────────────────
assert(
    strpos($navContent, "__('General Settings')") !== false ||
    strpos($navContent, '__("General Settings")') !== false,
    "Test 6 Failed: __() translation wrapper must be applied to tab labels."
);
echo "Test 6 Passed: Translation wrapper __() is applied to labels.\n";

// ── Test 7: Updates tab is conditional (permission-gated) ────────────────────
assert(
    strpos($navContent, 'hasUpdatesPermission') !== false,
    "Test 7 Failed: Updates tab must be wrapped in permission check."
);
echo "Test 7 Passed: Updates tab is permission-gated.\n";

// ── Test 8: No duplicate content (file should be under 8 KB after cleanup) ───
$fileSize = strlen($navContent);
assert(
    $fileSize < 8192,
    "Test 8 Failed: nav.php is {$fileSize} bytes — likely contains duplicate content. Expected < 8 KB."
);
echo "Test 8 Passed: nav.php file size is acceptable ({$fileSize} bytes).\n";

echo "\n✓ All Settings Navigation Tests Passed!\n";
return true;
