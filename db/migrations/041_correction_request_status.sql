-- A submitted correction request has its own workflow status. This makes it
-- visible in reviewer queues and preserves the previous state if it is declined.
ALTER TABLE form_submissions
    MODIFY COLUMN status ENUM(
        'draft',
        'submitted',
        'correction_requested',
        'under_review',
        'approved',
        'rejected',
        'returned',
        'cancelled'
    ) NOT NULL DEFAULT 'submitted';

SET @original_status_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'submission_correction_requests'
      AND COLUMN_NAME = 'original_status'
);
SET @original_status_sql := IF(
    @original_status_exists = 0,
    'ALTER TABLE submission_correction_requests ADD COLUMN original_status ENUM(''submitted'', ''under_review'', ''approved'', ''rejected'') NOT NULL DEFAULT ''submitted'' AFTER requested_by',
    'SELECT 1'
);
PREPARE original_status_stmt FROM @original_status_sql;
EXECUTE original_status_stmt;
DEALLOCATE PREPARE original_status_stmt;
