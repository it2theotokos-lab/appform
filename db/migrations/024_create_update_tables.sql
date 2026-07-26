-- Migration: Create application_updates and application_update_logs tables
-- Rollback: DROP TABLE IF EXISTS application_update_logs; DROP TABLE IF EXISTS application_updates;

CREATE TABLE IF NOT EXISTS application_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    release_version VARCHAR(50) NOT NULL,
    build_number INT NOT NULL,
    release_channel VARCHAR(50) NOT NULL,
    previous_version VARCHAR(50) NOT NULL,
    previous_build INT NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    current_step VARCHAR(100) NULL,
    progress_percent INT DEFAULT 0,
    release_tag VARCHAR(100) NULL,
    provider VARCHAR(50) NOT NULL,
    package_identifier VARCHAR(255) NULL,
    package_sha256 VARCHAR(256) NULL,
    package_size BIGINT NULL,
    backup_path VARCHAR(500) NULL,
    backup_sha256 VARCHAR(256) NULL,
    database_backup_path VARCHAR(500) NULL,
    database_backup_sha256 VARCHAR(256) NULL,
    started_by INT NULL,
    started_at TIMESTAMP NULL,
    heartbeat_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    failed_at TIMESTAMP NULL,
    error_code VARCHAR(100) NULL,
    error_message TEXT NULL,
    rollback_status VARCHAR(50) NULL,
    rollback_started_at TIMESTAMP NULL,
    rollback_completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (started_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_updates_status (status),
    INDEX idx_updates_version (release_version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS application_update_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    update_id INT NOT NULL,
    step_key VARCHAR(100) NOT NULL,
    level ENUM('info', 'warning', 'error') NOT NULL DEFAULT 'info',
    message TEXT NOT NULL,
    context_json LONGTEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (update_id) REFERENCES application_updates(id) ON DELETE CASCADE,
    INDEX idx_update_logs_update (update_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
