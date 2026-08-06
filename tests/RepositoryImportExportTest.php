<?php
/**
 * RepositoryImportExportTest.php — Test suite for Repository CSV/XLSX/JSON Import & Export
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Models\Repository;
use App\Services\SpreadsheetService;

if (!function_exists('__')) {
    function __(string $key, array $replace = []): string {
        return \App\Services\Lang::get($key, $replace);
    }
}

\App\Core\App::$config = require __DIR__ . '/../config/config.php';

echo "========================================\n";
echo "  APPFORM REPOSITORY IMPORT/EXPORT TESTS\n";
echo "========================================\n";

$pdo = Database::getInstance();

if (!function_exists('assertRepoTest')) {
    function assertRepoTest(bool $cond, string $msg): void {
        if ($cond) {
            echo "  ✅ PASS: $msg\n";
        } else {
            echo "  ❌ FAIL: $msg\n";
            exit(1);
        }
    }
}

// Cleanup existing test repository
$pdo->exec("DELETE FROM repositories WHERE slug = 'test_repo_ie'");

// ── TEST 1: Create Test Repository ─────────────────────────────────────────
echo "\nTest 1: Creating test repository for import/export...\n";
$stmt = $pdo->prepare("
    INSERT INTO repositories (name, slug, description, data_json, columns_json, is_active, created_by)
    VALUES (?, 'test_repo_ie', 'Test Repo Description', ?, ?, 1, 1)
");
$initialData = json_encode([
    ['value' => 'emp_101', 'label' => 'John Doe', 'dept' => 'IT'],
    ['value' => 'emp_102', 'label' => 'Jane Smith', 'dept' => 'HR']
], JSON_UNESCAPED_UNICODE);

$columnsJson = json_encode([
    ['key' => 'dept', 'label' => 'Department', 'type' => 'text']
], JSON_UNESCAPED_UNICODE);

$stmt->execute(['Test Repository IE', $initialData, $columnsJson]);
$repoId = (int)$pdo->lastInsertId();

$repo = Repository::findById($repoId);
assertRepoTest($repo !== null, 'Test repository created successfully');

// ── TEST 2: CSV Export & Parsing ──────────────────────────────────────────
echo "\nTest 2: CSV export and parsing...\n";
$headers = ['value', 'label', 'dept'];
$rows = json_decode($repo['data_json'], true);

$csvContent = SpreadsheetService::exportCsv($headers, $rows);
assertRepoTest(str_contains($csvContent, 'emp_101') && str_contains($csvContent, 'John Doe'), 'CSV export string contains record data');

$tmpCsvPath = sys_get_temp_dir() . '/test_repo_export.csv';
file_put_contents($tmpCsvPath, $csvContent);

$parsedCsv = SpreadsheetService::parseCsvFile($tmpCsvPath);
@unlink($tmpCsvPath);

assertRepoTest(count($parsedCsv['rows']) === 2, 'CSV parser correctly extracted 2 rows');
assertRepoTest($parsedCsv['rows'][0]['value'] === 'emp_101', 'CSV parser preserved first record key');

// ── TEST 3: XLSX Export & Parsing ─────────────────────────────────────────
echo "\nTest 3: XLSX export and parsing via PhpSpreadsheet...\n";
$xlsxContent = SpreadsheetService::exportXlsx($headers, $rows);
assertRepoTest(strlen($xlsxContent) > 0, 'XLSX export returned non-empty binary string');

$tmpXlsxPath = sys_get_temp_dir() . '/test_repo_export.xlsx';
file_put_contents($tmpXlsxPath, $xlsxContent);

$parsedXlsx = SpreadsheetService::parseXlsxFile($tmpXlsxPath);
@unlink($tmpXlsxPath);

assertRepoTest(count($parsedXlsx['rows']) === 2, 'XLSX parser correctly extracted 2 rows');
assertRepoTest($parsedXlsx['rows'][1]['label'] === 'Jane Smith', 'XLSX parser preserved second record label');

// ── TEST 4: JSON Export & Parsing ─────────────────────────────────────────
echo "\nTest 4: JSON export and parsing...\n";
$jsonContent = SpreadsheetService::exportJson($headers, $rows);
$parsedJson = SpreadsheetService::parseJsonContent($jsonContent);
assertRepoTest(count($parsedJson['rows']) === 2, 'JSON parser correctly extracted 2 rows');

// ── TEST 5: Cleanup ───────────────────────────────────────────────────────
echo "\nTest 5: Cleaning up test repository...\n";
$pdo->prepare("DELETE FROM repositories WHERE id = ?")->execute([$repoId]);
echo "  ✅ PASS: Test repository cleaned up cleanly\n";

echo "\n========================================\n";
echo "  ALL REPOSITORY IMPORT/EXPORT TESTS PASSED ✅\n";
echo "========================================\n";
