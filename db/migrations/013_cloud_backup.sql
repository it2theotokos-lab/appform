-- Migration script for Cloud Backup extension parameters
ALTER TABLE system_backups ADD COLUMN cloud_provider VARCHAR(50) DEFAULT 'local' AFTER storage_driver;
ALTER TABLE system_backups ADD COLUMN cloud_status VARCHAR(50) DEFAULT 'none' AFTER status; -- 'none', 'uploading', 'uploaded', 'failed'
ALTER TABLE system_backups ADD COLUMN cloud_error TEXT NULL AFTER error_message;
ALTER TABLE system_backups ADD COLUMN cloud_checksum VARCHAR(256) NULL AFTER sha256_hash;
ALTER TABLE system_backups ADD COLUMN upload_speed_kbps INT DEFAULT 0 AFTER file_size;
ALTER TABLE system_backups ADD COLUMN cloud_quota_used_gb DOUBLE DEFAULT 0.0;
ALTER TABLE system_backups ADD COLUMN cloud_quota_total_gb DOUBLE DEFAULT 15.0;

-- OAuth token storage table for OneDrive & Google Drive integration
CREATE TABLE IF NOT EXISTS oauth_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider VARCHAR(50) NOT NULL UNIQUE, -- 'onedrive', 'googledrive'
    access_token TEXT NOT NULL,
    refresh_token TEXT NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
