-- Migration 028 - Alter upload_session_id column to TEXT safely
DELIMITER //

CREATE PROCEDURE AlterUploadSessionIdToText()
BEGIN
    DECLARE colType VARCHAR(50) DEFAULT '';
    
    SELECT DATA_TYPE INTO colType 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'cloud_replication_jobs' 
      AND COLUMN_NAME = 'upload_session_id';
      
    IF colType != 'text' THEN
        ALTER TABLE cloud_replication_jobs 
        MODIFY COLUMN upload_session_id TEXT NULL;
    END IF;
END //

DELIMITER ;

CALL AlterUploadSessionIdToText();
DROP PROCEDURE AlterUploadSessionIdToText;
