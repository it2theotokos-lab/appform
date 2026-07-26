<?php
// DB Schema and Seed installation script integrity tests

echo "Running Installer Script Integrity Tests...\n";

// Test 1: Verify Schema SQL file presence
assert(file_exists(__DIR__ . '/../db/schema.sql') === true, "Test 1 Failed: schema.sql file is missing.");
echo "Test 1 Passed: schema.sql presence check.\n";

// Test 2: Verify Seeding install script presence
assert(file_exists(__DIR__ . '/../db/install.php') === true, "Test 2 Failed: db/install.php script is missing.");
echo "Test 2 Passed: install.php presence check.\n";

return true;
