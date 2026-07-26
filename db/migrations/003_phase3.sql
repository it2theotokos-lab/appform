-- AppForm Migration 003: Phase 3 Assignments, Files, Menu Items and Workflow Support

-- 1. Form Role Assignments Table
CREATE TABLE IF NOT EXISTS form_role_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_id INT NOT NULL,
    role_id INT NOT NULL,
    can_view TINYINT(1) DEFAULT 1,
    can_submit TINYINT(1) DEFAULT 1,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY uq_form_role_assignment (form_id, role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Form User Assignments Table
CREATE TABLE IF NOT EXISTS form_user_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_id INT NOT NULL,
    user_id INT NOT NULL,
    can_view TINYINT(1) DEFAULT 1,
    can_submit TINYINT(1) DEFAULT 1,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY uq_form_user_assignment (form_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Submission Status History Table
CREATE TABLE IF NOT EXISTS submission_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    submission_id INT NOT NULL,
    old_status VARCHAR(50) NOT NULL,
    new_status VARCHAR(50) NOT NULL,
    notes TEXT NULL,
    changed_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (submission_id) REFERENCES form_submissions(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Normalized Navigation Menu Items Table
CREATE TABLE IF NOT EXISTS navigation_menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    menu_id INT NOT NULL,
    parent_id INT NULL,
    label VARCHAR(150) NOT NULL,
    icon VARCHAR(100) NULL,
    item_type ENUM('route', 'external', 'form', 'header', 'divider') DEFAULT 'route',
    route_name VARCHAR(150) NULL,
    url VARCHAR(255) NULL,
    form_id INT NULL,
    permission_slug VARCHAR(100) NULL,
    sort_order INT DEFAULT 0,
    open_in_new_tab TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (menu_id) REFERENCES navigation_menus(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES navigation_menu_items(id) ON DELETE SET NULL,
    FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add uuid column to form_submissions if it does not already exist
DROP PROCEDURE IF EXISTS _appform_mig_003_uuid;
DELIMITER $$
CREATE PROCEDURE _appform_mig_003_uuid()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'form_submissions' AND COLUMN_NAME = 'uuid'
    ) THEN
        ALTER TABLE form_submissions ADD COLUMN uuid VARCHAR(64) UNIQUE AFTER id;
    END IF;
END$$
DELIMITER ;
CALL _appform_mig_003_uuid();
DROP PROCEDURE IF EXISTS _appform_mig_003_uuid;

-- Add file_extension column to submission_files if not exists
DROP PROCEDURE IF EXISTS _appform_mig_003_ext;
DELIMITER $$
CREATE PROCEDURE _appform_mig_003_ext()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'submission_files' AND COLUMN_NAME = 'file_extension'
    ) THEN
        ALTER TABLE submission_files ADD COLUMN file_extension VARCHAR(10) NULL AFTER mime_type;
    END IF;
END$$
DELIMITER ;
CALL _appform_mig_003_ext();
DROP PROCEDURE IF EXISTS _appform_mig_003_ext;

-- Add checksum_sha256 column to submission_files if not exists
DROP PROCEDURE IF EXISTS _appform_mig_003_chk;
DELIMITER $$
CREATE PROCEDURE _appform_mig_003_chk()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'submission_files' AND COLUMN_NAME = 'checksum_sha256'
    ) THEN
        ALTER TABLE submission_files ADD COLUMN checksum_sha256 VARCHAR(64) NULL AFTER file_size;
    END IF;
END$$
DELIMITER ;
CALL _appform_mig_003_chk();
DROP PROCEDURE IF EXISTS _appform_mig_003_chk;

-- Add uploaded_by column to submission_files if not exists
DROP PROCEDURE IF EXISTS _appform_mig_003_upby;
DELIMITER $$
CREATE PROCEDURE _appform_mig_003_upby()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'submission_files' AND COLUMN_NAME = 'uploaded_by'
    ) THEN
        ALTER TABLE submission_files ADD COLUMN uploaded_by INT NULL AFTER storage_path;
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'submission_files' AND CONSTRAINT_NAME = 'fk_sub_files_user'
    ) THEN
        ALTER TABLE submission_files ADD CONSTRAINT fk_sub_files_user FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL;
    END IF;
END$$
DELIMITER ;
CALL _appform_mig_003_upby();
DROP PROCEDURE IF EXISTS _appform_mig_003_upby;
