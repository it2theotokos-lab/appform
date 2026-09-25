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

// Test 2: A checkbox group remains valid when any option except the first is selected.
$checkboxSchema = [
    'sections' => [[
        'fields' => [[
            'key' => 'students', 'type' => 'checkbox', 'label' => 'Students', 'required' => true,
            'options' => [
                ['value' => 'student-1', 'label' => 'Student 1'],
                ['value' => 'student-2', 'label' => 'Student 2'],
            ],
        ]],
    ]],
];
$validator = new SubmissionValidator();
assert($validator->validate(['students' => ['student-2']], $checkboxSchema, true) === true, "Test 2 Failed: A selected non-first checkbox must satisfy required validation.");
echo "Test 2 Passed: Required checkbox group accepts any selected option.\n";

// Test 3: A required checkbox group rejects an empty group on final submission.
$validator = new SubmissionValidator();
assert($validator->validate(['students' => []], $checkboxSchema, true) === false, "Test 3 Failed: Empty required checkbox group must be rejected.");
echo "Test 3 Passed: Empty required checkbox group is rejected.\n";

return true;
