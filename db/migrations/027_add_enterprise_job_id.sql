-- Migration 027 - Add enterprise_job_id column and index to cloud_replication_jobs
DELIMITER //

CREATE PROCEDURE AddEnterpriseJobIdColumn()
BEGIN
    DECLARE colExists INT DEFAULT 0;
    
    SELECT COUNT(*) INTO colExists 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'cloud_replication_jobs' 
      AND COLUMN_NAME = 'enterprise_job_id';
      
    IF colExists = 0 THEN
        ALTER TABLE cloud_replication_jobs 
        ADD COLUMN enterprise_job_id INT NULL AFTER provider,
        ADD INDEX idx_enterprise_job_id (enterprise_job_id),
        ADD CONSTRAINT fk_cloud_replication_jobs_enterprise_job 
            FOREIGN KEY (enterprise_job_id) REFERENCES jobs(id) ON DELETE SET NULL;
    END IF;
END //

DELIMITER ;

CALL AddEnterpriseJobIdColumn();
DROP PROCEDURE AddEnterpriseJobIdColumn;
