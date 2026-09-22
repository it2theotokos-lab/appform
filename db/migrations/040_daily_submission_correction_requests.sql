SET @daily_column_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'forms'
      AND COLUMN_NAME = 'daily_submission_enabled'
);
SET @daily_column_sql := IF(
    @daily_column_exists = 0,
    'ALTER TABLE forms ADD COLUMN daily_submission_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER single_submission_enabled',
    'SELECT 1'
);
PREPARE daily_column_stmt FROM @daily_column_sql;
EXECUTE daily_column_stmt;
DEALLOCATE PREPARE daily_column_stmt;

CREATE TABLE IF NOT EXISTS submission_correction_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    submission_id INT NOT NULL,
    requested_by INT NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    reviewed_by INT NULL,
    reviewer_notes TEXT NULL,
    reviewed_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_correction_submission FOREIGN KEY (submission_id) REFERENCES form_submissions(id) ON DELETE CASCADE,
    CONSTRAINT fk_correction_requester FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_correction_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_correction_submission_status (submission_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
