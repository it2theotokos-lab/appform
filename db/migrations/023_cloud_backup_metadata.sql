-- Migration: Add missing metadata columns for Cloud Backup & Replication (Idempotent schema correction)
-- Emergency Rollback SQL:
-- ALTER TABLE forms DROP COLUMN submission_access_mode;
-- ALTER TABLE oauth_tokens DROP COLUMN connected_account, DROP COLUMN last_connected_at, DROP COLUMN destination_folder;

DROP PROCEDURE IF EXISTS _appform_mig_023_columns;
DELIMITER $$
CREATE PROCEDURE _appform_mig_023_columns()
BEGIN
    -- 1. Add forms.submission_access_mode
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'forms' AND COLUMN_NAME = 'submission_access_mode'
    ) THEN
        ALTER TABLE forms ADD COLUMN submission_access_mode VARCHAR(50) DEFAULT 'all_authenticated' AFTER form_mode;
    END IF;

    -- 2. Add oauth_tokens.connected_account
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'oauth_tokens' AND COLUMN_NAME = 'connected_account'
    ) THEN
        ALTER TABLE oauth_tokens ADD COLUMN connected_account VARCHAR(150) NULL AFTER expires_at;
    END IF;

    -- 3. Add oauth_tokens.last_connected_at
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'oauth_tokens' AND COLUMN_NAME = 'last_connected_at'
    ) THEN
        ALTER TABLE oauth_tokens ADD COLUMN last_connected_at TIMESTAMP NULL AFTER connected_account;
    END IF;

    -- 4. Add oauth_tokens.destination_folder
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'oauth_tokens' AND COLUMN_NAME = 'destination_folder'
    ) THEN
        ALTER TABLE oauth_tokens ADD COLUMN destination_folder VARCHAR(255) NULL AFTER last_connected_at;
    END IF;
END$$
DELIMITER ;
CALL _appform_mig_023_columns();
DROP PROCEDURE IF EXISTS _appform_mig_023_columns;
