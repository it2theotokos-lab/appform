<?php
// PDF Dompdf Output Tests

use App\Services\PdfExportService;

echo "Running PdfExport Dompdf Tests...\n";

$submission = [
    'id' => 1,
    'form_title' => 'Test Form',
    'status' => 'submitted',
    'submitter_name' => 'Demo User',
    'submitted_at' => date('Y-m-d H:i:s')
];
$schema = ['sections' => []];
$answers = [];

$pdf = PdfExportService::export($submission, $schema, $answers);
assert(!empty($pdf), "Test 1 Failed: PDF export must generate non-empty output.");
echo "Test 1 Passed: PDF export validation.\n";

return true;
