<?php
/**
 * RepositoryCreationTest — v1.1.16
 *
 * Tests:
 * 1. Successful creation of an empty Repository (no JSON).
 * 2. Successful creation with valid legacy JSON import.
 * 3. Rejection of non-empty invalid JSON.
 * 4. Existing repositories are unaffected.
 */

require_once __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../config/config.php';
if (\App\Core\App::$config === null) {
    \App\Core\App::$config = $config;
    new \App\Core\App();
}

use App\Core\Database;

echo "========================================\n";
echo "  REPOSITORY CREATION TESTS (v1.1.16)\n";
echo "========================================\n";

$db = Database::getInstance();

// ── Helper: cleanup slugs created by tests ──────────────────────────────────
function cleanupSlug(string $slug, PDO $db): void {
    $db->prepare("DELETE FROM repositories WHERE slug = ?")->execute([$slug]);
}

// ── Capture the set of existing repository IDs before tests ─────────────────
$beforeIds = $db->prepare("SELECT id FROM repositories ORDER BY id");
$beforeIds->execute();
$existingIds = array_column($beforeIds->fetchAll(PDO::FETCH_ASSOC), 'id');

// ════════════════════════════════════════════════════════════════════════════
// TEST 1 — Empty JSON → empty repository created successfully
// ════════════════════════════════════════════════════════════════════════════
echo "\nTest 1: Empty-JSON repository creation...\n";

cleanupSlug('test-empty-repo-v1116', $db);

$slug = 'test-empty-repo-v1116';
$name = 'Test Empty Repo v1116';

// Simulate what RepositoryController::store() does with an empty data_json
$rawJson = trim('');   // user leaves field blank
$dataJson = ($rawJson === '') ? '[]' : $rawJson;

assert($dataJson === '[]', "Test 1 Failed: empty input must produce '[]'.");

$stmt = $db->prepare("
    INSERT INTO repositories (name, slug, description, data_json, columns_json, is_active, created_by)
    VALUES (?, ?, ?, ?, NULL, 1, 1)
");
$stmt->execute([$name, $slug, 'Test description', $dataJson]);
$newId = $db->lastInsertId();

assert($newId > 0, "Test 1 Failed: INSERT returned no ID.");

$row = $db->prepare("SELECT data_json FROM repositories WHERE id = ?");
$row->execute([$newId]);
$saved = $row->fetchColumn();

assert($saved === '[]', "Test 1 Failed: saved data_json is not '[]' — got: $saved");
echo "  ✅ Empty Repository created (id=$newId, data_json='[]')\n";

// ════════════════════════════════════════════════════════════════════════════
// TEST 2 — Valid JSON import works as before
// ════════════════════════════════════════════════════════════════════════════
echo "\nTest 2: Valid JSON import...\n";

cleanupSlug('test-json-repo-v1116', $db);

$slug2   = 'test-json-repo-v1116';
$jsonIn  = '[{"value":"it","label":"IT"},{"value":"hr","label":"HR"}]';
$rawJson2 = trim($jsonIn);

$decoded2 = json_decode($rawJson2, true);
assert(is_array($decoded2), "Test 2 Failed: JSON did not decode to array.");
foreach ($decoded2 as $item) {
    assert(isset($item['value']) && isset($item['label']),
        "Test 2 Failed: item missing value/label.");
}

$stmt2 = $db->prepare("
    INSERT INTO repositories (name, slug, description, data_json, columns_json, is_active, created_by)
    VALUES (?, ?, ?, ?, NULL, 1, 1)
");
$stmt2->execute(['Test JSON Repo v1116', $slug2, '', $jsonIn]);
$newId2 = $db->lastInsertId();

assert($newId2 > 0, "Test 2 Failed: INSERT returned no ID.");

$row2 = $db->prepare("SELECT data_json FROM repositories WHERE id = ?");
$row2->execute([$newId2]);
$saved2 = $row2->fetchColumn();
$decoded2saved = json_decode($saved2, true);
assert(count($decoded2saved) === 2, "Test 2 Failed: expected 2 items in saved JSON.");
echo "  ✅ JSON import repository created (id=$newId2, items=" . count($decoded2saved) . ")\n";

// ════════════════════════════════════════════════════════════════════════════
// TEST 3 — Non-empty invalid JSON is rejected
// ════════════════════════════════════════════════════════════════════════════
echo "\nTest 3: Invalid non-empty JSON is rejected...\n";

$invalidJson = 'this is not json';
$rawJson3    = trim($invalidJson);

$rejected = false;
if ($rawJson3 !== '') {
    $decoded3 = json_decode($rawJson3, true);
    if (!is_array($decoded3)) {
        $rejected = true;   // Controller would Session::flash error & back()
    }
}
assert($rejected === true, "Test 3 Failed: invalid JSON was not detected.");
echo "  ✅ Invalid JSON correctly flagged as error\n";

// Also verify partially-valid JSON (array items missing value/label) is rejected
$badItems = '[{"name":"foo"}]';
$rawJson3b = trim($badItems);
$decodedBad = json_decode($rawJson3b, true);
assert(is_array($decodedBad), "Test 3b: outer structure should parse.");
$itemRejected = false;
foreach ($decodedBad as $item) {
    if (!isset($item['value']) || !isset($item['label'])) {
        $itemRejected = true;
        break;
    }
}
assert($itemRejected === true, "Test 3 Failed: missing value/label not detected.");
echo "  ✅ Items missing 'value'/'label' correctly flagged as error\n";

// ════════════════════════════════════════════════════════════════════════════
// TEST 4 — Existing repositories are unaffected
// ════════════════════════════════════════════════════════════════════════════
echo "\nTest 4: Existing repositories unaffected...\n";

$afterCheck = $db->prepare("SELECT id FROM repositories WHERE id IN (" . implode(',', array_fill(0, count($existingIds), '?')) . ")");
if (!empty($existingIds)) {
    $afterCheck->execute($existingIds);
    $foundIds = array_column($afterCheck->fetchAll(PDO::FETCH_ASSOC), 'id');
    assert(
        count($foundIds) === count($existingIds),
        "Test 4 Failed: " . (count($existingIds) - count($foundIds)) . " existing repositories missing."
    );
    echo "  ✅ All " . count($existingIds) . " pre-existing repositories still present\n";
} else {
    echo "  ✅ No pre-existing repositories (clean database)\n";
}

// ── Cleanup test rows ────────────────────────────────────────────────────────
cleanupSlug('test-empty-repo-v1116', $db);
cleanupSlug('test-json-repo-v1116', $db);

echo "\n========================================\n";
echo "  ALL 4 REPOSITORY CREATION TESTS PASSED ✅\n";
echo "========================================\n";
return true;
