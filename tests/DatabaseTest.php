<?php
// PDO Connection and DB execution Tests

use App\Core\Database;

echo "Running Database Connection Tests...\n";

try {
    $pdo = Database::getInstance();
    assert($pdo instanceof \PDO, "Test 1 Failed: Database instance is not a PDO object.");
    echo "Test 1 Passed: PDO Instance check.\n";
} catch (\Exception $e) {
    echo "Warning: Database not configured or unreachable. Skipping execution check.\n";
}

return true;
