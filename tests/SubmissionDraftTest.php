<?php
// Submissions Draft saving check tests

use App\Services\SubmissionValidator;

echo "Running SubmissionDraft Tests...\n";

// Test 1: Draft allows missing required field
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

$validator = new SubmissionValidator();
$isValidDraft = $validator->validate(['full_name' => ''], $schema, false); // isFinal = false (Draft)
assert($isValidDraft === true, "Test 1 Failed: Draft save must allow empty required field values.");
echo "Test 1 Passed: Draft required field validation bypass.\n";

return true;
