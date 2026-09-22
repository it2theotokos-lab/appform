-- Keep the database status values aligned with SubmissionWorkflowService.
-- Required for "Return for Corrections" and cancellation transitions.
ALTER TABLE form_submissions
    MODIFY COLUMN status ENUM(
        'draft',
        'submitted',
        'under_review',
        'approved',
        'rejected',
        'returned',
        'cancelled'
    ) NOT NULL DEFAULT 'submitted';
