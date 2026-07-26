-- Migration script for Enterprise Cloud Replication and Queue System
CREATE TABLE IF NOT EXISTS cloud_replication_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    backup_id INT NOT NULL,
    provider VARCHAR(50) NOT NULL, -- 'googledrive', 'onedrive'
    status VARCHAR(50) DEFAULT 'pending', -- 'pending', 'preparing', 'encrypting', 'uploading', 'verifying', 'completed', 'retrying', 'failed', 'cancelled'
    priority INT DEFAULT 0,
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 5,
    next_retry_at TIMESTAMP NULL DEFAULT NULL,
    started_at TIMESTAMP NULL DEFAULT NULL,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    bytes_uploaded BIGINT DEFAULT 0,
    total_bytes BIGINT DEFAULT 0,
    upload_session_id VARCHAR(255) NULL,
    remote_file_id VARCHAR(255) NULL,
    last_error TEXT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (backup_id) REFERENCES system_backups(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cloud_replication_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    attempt_number INT NOT NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    status VARCHAR(50) NOT NULL, -- 'completed', 'failed'
    bytes_transferred BIGINT DEFAULT 0,
    error_message TEXT NULL,
    FOREIGN KEY (job_id) REFERENCES cloud_replication_jobs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS worker_heartbeats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    worker_name VARCHAR(100) NOT NULL UNIQUE,
    last_heartbeat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
