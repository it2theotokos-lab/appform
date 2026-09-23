<?php
// Submission Workflow Transitions Test

use App\Services\SubmissionWorkflowService;

echo "Running SubmissionWorkflow Tests...\n";

// Test 1: Transition checks class instantiation/exists check
assert(class_exists(SubmissionWorkflowService::class) === true, "Test 1 Failed: SubmissionWorkflowService must be defined.");
echo "Test 1 Passed: Workflow Service existence check.\n";

// Test 2: Every status used by the workflow must be accepted by the database schema/migration.
$workflowSource = file_get_contents(__DIR__ . '/../src/Services/SubmissionWorkflowService.php');
$schemaSource = file_get_contents(__DIR__ . '/../db/schema.sql');
$migrationSource = file_get_contents(__DIR__ . '/../db/migrations/039_expand_submission_statuses.sql')
    . file_get_contents(__DIR__ . '/../db/migrations/041_correction_request_status.sql')
    . file_get_contents(__DIR__ . '/../db/migrations/042_enforce_correction_request_status.sql');
foreach (['draft', 'submitted', 'correction_requested', 'under_review', 'approved', 'rejected', 'returned', 'cancelled'] as $status) {
    assert(str_contains($workflowSource, "'{$status}'"), "Workflow status {$status} is missing from the service.");
    assert(str_contains($schemaSource, "'{$status}'"), "Workflow status {$status} is missing from the clean schema.");
    assert(str_contains($migrationSource, "'{$status}'"), "Workflow status {$status} is missing from migration 039.");
}
echo "Test 2 Passed: Workflow and database statuses are aligned.\n";

return true;
