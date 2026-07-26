<?php
// Excel XLSX Export Tests

use App\Services\ExcelExportService;

echo "Running ExcelExport XLSX Tests...\n";

$headers = ['Name', 'Score'];
$rows = [['User1', 100]];
$xls = ExcelExportService::export($headers, $rows);

assert(!empty($xls), "Test 1 Failed: Excel export must produce non-empty output.");
echo "Test 1 Passed: Excel export validation.\n";

return true;
