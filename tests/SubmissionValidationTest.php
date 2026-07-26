<?php
// Submission Validation Tests

use App\Services\SubmissionValidator;

echo "Running SubmissionValidation Tests...\n";

$schema = [
    'sections' => [
        [
            'id' => 'sec1',
            'title' => 'Section 1',
            'fields' => [
                ['id' => 'f1', 'key' => 'full_name', 'type' => 'text', 'label' => 'Full Name', 'required' => true]
            ]
        ]
    ]
];

// Test 1: Final submit rejects empty required field
$validator = new SubmissionValidator();
$isValidFinal = $validator->validate(['full_name' => ''], $schema, true); // isFinal = true
assert($isValidFinal === false, "Test 1 Failed: Final submit should fail when required field is empty.");
echo "Test 1 Passed: Final submission required field validation.\n";

return true;
