-- AppForm Migration 021: User Reporting Hierarchy
-- MySQL 8.0.44 compatible

DROP PROCEDURE IF EXISTS _appform_mig_021;
DELIMITER $$
CREATE PROCEDURE _appform_mig_021()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'manager_id'
    ) THEN
        ALTER TABLE users ADD COLUMN manager_id INT NULL;
    END IF;
END$$
DELIMITER ;
CALL _appform_mig_021();
DROP PROCEDURE IF EXISTS _appform_mig_021;
