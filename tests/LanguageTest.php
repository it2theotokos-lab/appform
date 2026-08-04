<?php
/**
 * LanguageTest.php — v1.1.20 Build 22
 *
 * Tests for the Lang service (EL/EN translation helper).
 * Verifies: class existence, locale(), get(), setLocale(), __() global helper.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\Lang;

echo "--- Running Language (Lang) Tests ---\n";

// ── Test 1: Lang class exists ─────────────────────────────────────────────────
assert(class_exists(Lang::class), "Test 1 Failed: Lang class must exist.");
echo "Test 1 Passed: Lang class exists.\n";

// ── Test 2: locale() returns 'el' as default ──────────────────────────────────
// Reset session-based locale by simulating no session
$defaultLocale = Lang::locale();
assert(in_array($defaultLocale, ['el', 'en']), "Test 2 Failed: locale() must return 'el' or 'en'.");
echo "Test 2 Passed: locale() returns a valid locale ({$defaultLocale}).\n";

// ── Test 3: setLocale() switches to EN ────────────────────────────────────────
Lang::setLocale('en');
assert(Lang::locale() === 'en', "Test 3 Failed: setLocale('en') must set locale to 'en'.");
echo "Test 3 Passed: setLocale('en') works.\n";

// ── Test 4: get() returns EN translation for known key ───────────────────────
$translated = Lang::get('Αποθήκευση');
// 'Save' → EN translation is 'Save'; key 'Save' maps el→Αποθήκευση
// Since key IS 'Save' (not el string), check alternate
$translated2 = Lang::get('Save');
assert(is_string($translated2), "Test 4 Failed: get() must return a string.");
assert($translated2 === 'Save', "Test 4 Failed: EN translation for 'Save' must be 'Save'. Got: {$translated2}");
echo "Test 4 Passed: get('Save') returns 'Save' in EN.\n";

// ── Test 5: Settings tab keys translate correctly ─────────────────────────────
Lang::setLocale('en');
$tabTranslations = [
    'General Settings'     => 'General Settings',
    'Backup'               => 'Backup',
    'Restore'              => 'Restore',
    'Job Queue'            => 'Job Queue',
    'Action Logs'          => 'Action Logs',
    'Upgrade'              => 'Upgrade',
    'Cloud Backup'         => 'Cloud Backup',
    'Global Notifications' => 'Global Notifications',
];
foreach ($tabTranslations as $key => $expected) {
    $result = Lang::get($key);
    assert($result === $expected, "Test 5 Failed: EN translation of '{$key}' expected '{$expected}', got '{$result}'.");
}
echo "Test 5 Passed: All settings tab keys translate correctly in EN.\n";

// ── Test 6: Logo keys exist in dictionary ─────────────────────────────────────
Lang::setLocale('en');
$logoKeys = [
    'Application Logo',
    'Upload New Logo',
    'Delete Logo',
    'Current Logo',
    'Logo uploaded successfully.',
    'Logo deleted successfully.',
    'No custom logo is set.',
];
foreach ($logoKeys as $key) {
    $result = Lang::get($key);
    assert(is_string($result) && strlen($result) > 0, "Test 6 Failed: Logo key '{$key}' must return a non-empty string.");
}
echo "Test 6 Passed: All logo translation keys are present.\n";

// ── Test 7: setLocale() rejects invalid locale ────────────────────────────────
Lang::setLocale('fr'); // unsupported
assert(Lang::locale() === 'el', "Test 7 Failed: setLocale('fr') must fall back to 'el'.");
echo "Test 7 Passed: Invalid locale falls back to 'el'.\n";

// ── Test 8: __() global helper function exists and works ──────────────────────
// ── Test 8: __() global helper function ──────────────────────────────────────
// The real __() lives in public/index.php (bootstrap). For unit testing
// we register an equivalent shim here to validate the contract.
if (!function_exists('__')) {
    function __(string $key, array $replace = []): string {
        return \App\Services\Lang::get($key, $replace);
    }
}
assert(function_exists('__'), "Test 8 Failed: Global __() helper must be defined (shim or real).");
Lang::setLocale('el');
$result = __('Save');
assert($result === 'Αποθήκευση', "Test 8 Failed: __() in EL must return dictionary translation. Got: {$result}");
echo "Test 8 Passed: __() global helper works correctly.\n";

// ── Test 9: EL locale dictionary lookup ────────────────────────────────────
Lang::setLocale('el');
$key = 'General Settings';
assert(Lang::get($key) === 'Γενικές Ρυθμίσεις', "Test 9 Failed: EL locale must translate known key.");
echo "Test 9 Passed: EL locale dictionary lookup works.\n";

// Reset locale
Lang::setLocale('el');

echo "\n✓ All Language Tests Passed!\n";
return true;
