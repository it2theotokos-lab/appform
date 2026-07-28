<?php
/**
 * Update system database schema migration regression test
 */
require_once __DIR__ . '/../vendor/autoload.php';

if (\App\Core\App::$config === null) {
    $config = require __DIR__ . '/../config/config.php';
    \App\Core\App::$config = $config;
    new \App\Core\App();
}

use App\Core\Database;

echo "=========================================\n";
echo "    AppForm Update Migration Regression Test\n";
echo "=========================================\n";

$db = Database::getInstance();

// 1. Simulating previous version schema (remove package_path if exists)
echo "Step 1: Dropping column 'package_path' if exists to simulate previous version schema...\n";
try {
    $db->exec("ALTER TABLE application_updates DROP COLUMN package_path");
    echo "  ✅ Column 'package_path' dropped successfully.\n";
} catch (\PDOException $e) {
    // If it didn't exist, ignore
    echo "  ℹ️ Column did not exist or drop failed: " . $e->getMessage() . "\n";
}

// 2. Verify column is missing
$stmt = $db->prepare("SHOW COLUMNS FROM application_updates LIKE 'package_path'");
$stmt->execute();
$column = $stmt->fetch();
assert($column === false, "Precondition Failed: 'package_path' column still exists.");
echo "  ✅ Column 'package_path' is verified missing.\n";

// 3. Apply the new migration 030_add_package_path_to_updates.sql
$migrationFile = __DIR__ . '/../db/migrations/030_add_package_path_to_updates.sql';
assert(file_exists($migrationFile), "Migration file 030 does not exist.");
echo "Step 2: Applying migration 030_add_package_path_to_updates.sql...\n";
$sql = file_get_contents($migrationFile);
$db->exec($sql);
echo "  ✅ Migration applied successfully.\n";

// 4. Verify the column exists now and check properties
$stmt = $db->prepare("SHOW COLUMNS FROM application_updates LIKE 'package_path'");
$stmt->execute();
$column = $stmt->fetch();

assert($column !== false, "Test Failed: 'package_path' column was not created.");
assert(str_starts_with(strtolower($column['Type']), 'varchar'), "Test Failed: Column type is not varchar.");
assert(strtolower($column['Null']) === 'yes', "Test Failed: Column is not nullable.");

echo "  ✅ Column 'package_path' verified exists with type: {$column['Type']} and nullability: {$column['Null']}\n";
echo "=== Update Migration Regression Test Passed! ===\n";
return true;
