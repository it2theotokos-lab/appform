-- AppForm Migration 035: Secure File Sharing Module
-- Description: Create shared_files and shared_file_permissions tables for file uploads and access controls.

CREATE TABLE IF NOT EXISTS `shared_files` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `uploader_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `original_name` VARCHAR(255) NOT NULL,
    `stored_filename` VARCHAR(255) NOT NULL,
    `mime_type` VARCHAR(100) NOT NULL,
    `file_size` BIGINT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_shared_files_uploader` (`uploader_id`),
    CONSTRAINT `fk_shared_files_uploader` FOREIGN KEY (`uploader_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `shared_file_permissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `file_id` INT NOT NULL,
    `grantee_type` ENUM('user', 'department', 'subdepartment', 'team') NOT NULL,
    `grantee_id` INT NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_permissions_file` (`file_id`),
    KEY `idx_permissions_grantee` (`grantee_type`, `grantee_id`),
    CONSTRAINT `fk_permissions_file` FOREIGN KEY (`file_id`) REFERENCES `shared_files` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
