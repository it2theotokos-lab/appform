<?php
// CSV export streaming and injection filtering checks

use App\Services\CsvExportService;

echo "Running CsvExport Tests...\n";

// Test 1: Injection cleaning check
$headers = ['Name', 'Formula'];
$rows = [
    ['Test Account', '=SUM(1,2)']
];

$csv = CsvExportService::export($headers, $rows);
assert(str_contains($csv, "'=SUM(1,2)"), "Test 1 Failed: Semicolon formula prefix must be escaped with a single quote.");
echo "Test 1 Passed: CSV Injection cleaning.\n";

return true;
