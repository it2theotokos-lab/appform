-- Public forms may be submitted by visitors who do not have an AppForm account.
-- Their submission keeps a NULL user_id while authenticated submissions are unchanged.
SET @form_submission_user_nullable := (
    SELECT IS_NULLABLE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'form_submissions'
      AND COLUMN_NAME = 'user_id'
    LIMIT 1
);

SET @form_submission_user_sql := IF(
    @form_submission_user_nullable = 'NO',
    'ALTER TABLE form_submissions MODIFY COLUMN user_id INT NULL',
    'SELECT 1'
);

PREPARE form_submission_user_stmt FROM @form_submission_user_sql;
EXECUTE form_submission_user_stmt;
DEALLOCATE PREPARE form_submission_user_stmt;
