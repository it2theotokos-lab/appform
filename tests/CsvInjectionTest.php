<?php
// CSV injection validation checks

use App\Services\CsvExportService;

echo "Running CsvInjection Tests...\n";

$headers = ['Data'];
$rows = [
    ['=SUM(1+1)'],
    ['+cmd|'],
    ['-10+20'],
    ['@SUM(A1)']
];

$csv = CsvExportService::export($headers, $rows);
assert(str_contains($csv, "'=SUM"), "Test 1 Failed: Formula prefix = must be escaped.");
assert(str_contains($csv, "'+cmd"), "Test 2 Failed: Formula prefix + must be escaped.");
assert(str_contains($csv, "'-10"), "Test 3 Failed: Formula prefix - must be escaped.");
assert(str_contains($csv, "'@SUM"), "Test 4 Failed: Formula prefix @ must be escaped.");

echo "Test 1-4 Passed: CSV Injection protection active.\n";

return true;
