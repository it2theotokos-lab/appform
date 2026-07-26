-- Migration script for Disaster Recovery Restore Center history
CREATE TABLE IF NOT EXISTS system_restore_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    backup_id INT NOT NULL,
    executed_by INT NOT NULL,
    restore_type VARCHAR(50) NOT NULL, -- 'database', 'files', 'full'
    duration_seconds INT DEFAULT 0,
    status VARCHAR(50) DEFAULT 'pending', -- 'pending', 'completed', 'failed', 'rolled_back'
    warnings_json TEXT NULL,
    errors_json TEXT NULL,
    emergency_backup_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (backup_id) REFERENCES system_backups(id) ON DELETE CASCADE,
    FOREIGN KEY (executed_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Maintenance Mode System Setting if not exists
INSERT INTO system_settings (setting_key, setting_value)
VALUES ('maintenance_mode', '0')
ON DUPLICATE KEY UPDATE setting_value = setting_value;
