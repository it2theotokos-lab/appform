<?php
ob_start();
/**
 * AppForm Form Builder Autocomplete & Tags Tests
 */
require_once __DIR__ . '/../vendor/autoload.php';

$case = isset($argv[1]) ? $argv[1] : null;

if (\App\Core\App::$config === null) {
    $config = require __DIR__ . '/../config/config.php';
    \App\Core\App::$config = $config;
    new \App\Core\App();
}

use App\Core\Database;
use App\Core\Session;

if ($case === 'case1') {
    // Test 1: apiAutocomplete endpoint
    $_GET['repo_id'] = 1;
    $_GET['search_field'] = 'name';
    $_GET['query'] = 'test';
    
    // Create a mock repository
    $db = Database::getInstance();
    $stmt = $db->prepare("INSERT INTO repositories (name, slug, data_json, is_active, created_by) VALUES ('Test Repo', 'test-repo', '[{\"name\":\"Test Item 1\", \"val\":\"1\"}, {\"name\":\"Other Item\", \"val\":\"2\"}]', 1, 1)");
    $stmt->execute();
    $repoId = $db->lastInsertId();
    
    $_GET['repo_id'] = $repoId;
    
    $controller = new \App\Controllers\RepositoryController();
    $controller->apiAutocomplete();
    exit(0);
}

if ($case === 'case2') {
    // Test 2: apiTags endpoint
    $db = Database::getInstance();
    $stmt = $db->prepare("INSERT INTO repositories (name, slug, data_json, is_active, created_by) VALUES ('Tags Repo', 'tags-repo', '[{\"tag\":\"PHP\"}, {\"tag\":\"JS\"}]', 1, 1)");
    $stmt->execute();
    $repoId = $db->lastInsertId();
    
    $_GET['repo1'] = $repoId;
    $_GET['value_field'] = 'tag';
    
    $controller = new \App\Controllers\RepositoryController();
    $controller->apiTags();
    exit(0);
}

// MAIN RUNNER
ob_end_clean();

echo "========================================\n";
echo "  APPFORM FORM BUILDER FIELDS TESTS\n";
echo "========================================\n";

$db = Database::getInstance();
$php = "C:\\Antigravity-PRJ\\Tools\\PHP\\php.exe";
$script = __FILE__;

// -------------------------------------------------------------------------
// TEST 1: apiAutocomplete
// -------------------------------------------------------------------------
echo "Test 1: apiAutocomplete returns matching items...\n";
$out1 = shell_exec("\"$php\" \"$script\" case1");

assert(str_contains((string)$out1, 'Test Item 1'), "Test 1 Failed: Autocomplete did not return expected item.");
assert(!str_contains((string)$out1, 'Other Item'), "Test 1 Failed: Autocomplete returned non-matching item.");

echo "  ✅ apiAutocomplete filters correctly\n";
echo "Test 1 Passed!\n";

// -------------------------------------------------------------------------
// TEST 2: apiTags
// -------------------------------------------------------------------------
echo "\nTest 2: apiTags returns list of tags...\n";
$out2 = shell_exec("\"$php\" \"$script\" case2");

assert(str_contains((string)$out2, 'PHP'), "Test 2 Failed: Tags did not return expected tag.");
assert(str_contains((string)$out2, 'JS'), "Test 2 Failed: Tags did not return expected tag.");

echo "  ✅ apiTags returns tags correctly\n";
echo "Test 2 Passed!\n";


// Clean up test repos
$db->exec("DELETE FROM repositories WHERE slug IN ('test-repo', 'tags-repo')");

echo "\n========================================\n";
echo "  ALL FORM BUILDER FIELDS TESTS PASSED ✅\n";
echo "========================================\n";
