-- AppForm Migration 020: LDAP / Active Directory Integration
-- MySQL 8.0.44 compatible - uses stored procedures for conditional column adds

DROP PROCEDURE IF EXISTS _appform_mig_020;
DELIMITER $$
CREATE PROCEDURE _appform_mig_020()
BEGIN
    -- authentication_provider
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'authentication_provider'
    ) THEN
        ALTER TABLE users ADD COLUMN authentication_provider VARCHAR(20) NOT NULL DEFAULT 'local';
    END IF;

    -- external_identifier
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'external_identifier'
    ) THEN
        ALTER TABLE users ADD COLUMN external_identifier VARCHAR(150) NULL;
    END IF;

    -- directory_dn
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'directory_dn'
    ) THEN
        ALTER TABLE users ADD COLUMN directory_dn VARCHAR(255) NULL;
    END IF;

    -- directory_department
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'directory_department'
    ) THEN
        ALTER TABLE users ADD COLUMN directory_department VARCHAR(100) NULL;
    END IF;

    -- last_directory_sync_at
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'last_directory_sync_at'
    ) THEN
        ALTER TABLE users ADD COLUMN last_directory_sync_at TIMESTAMP NULL;
    END IF;
END$$
DELIMITER ;
CALL _appform_mig_020();
DROP PROCEDURE IF EXISTS _appform_mig_020;

-- LDAP configuration table
CREATE TABLE IF NOT EXISTS ldap_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider_enabled TINYINT(1) DEFAULT 0,
    ldap_host VARCHAR(255) NULL,
    ldap_port INT DEFAULT 389,
    use_ssl TINYINT(1) DEFAULT 0,
    use_starttls TINYINT(1) DEFAULT 0,
    base_dn VARCHAR(255) NULL,
    bind_dn VARCHAR(255) NULL,
    bind_password VARCHAR(255) NULL,
    user_search_base VARCHAR(255) NULL,
    user_filter VARCHAR(255) NULL,
    group_search_base VARCHAR(255) NULL,
    connection_timeout INT DEFAULT 5,
    default_role_id INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- LDAP role mappings table
CREATE TABLE IF NOT EXISTS ldap_role_mappings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    directory_group VARCHAR(255) NOT NULL,
    appform_role_id INT NOT NULL,
    FOREIGN KEY (appform_role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
