-- Migration script for Data Exchange & Reports (AppForm v1.1.0)
-- Creates tables: data_export_jobs, data_import_jobs, data_import_errors, data_exchange_templates, report_definitions, report_runs

CREATE TABLE IF NOT EXISTS data_export_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(64) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    entity_type VARCHAR(100) NOT NULL,
    filters_json TEXT NULL,
    selected_columns_json TEXT NULL,
    format VARCHAR(10) NOT NULL, -- csv, xlsx, json, pdf, zip
    status VARCHAR(50) DEFAULT 'pending', -- pending, processing, completed, failed
    file_path VARCHAR(255) NULL,
    file_name VARCHAR(150) NULL,
    record_count INT DEFAULT 0,
    error_message TEXT NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS data_import_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(64) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    entity_type VARCHAR(100) NOT NULL,
    filename VARCHAR(150) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    duplicate_strategy VARCHAR(50) NOT NULL, -- skip, update, duplicate, stop
    matching_field VARCHAR(100) NOT NULL, -- id, uuid, username, email, etc.
    status VARCHAR(50) DEFAULT 'pending', -- uploaded, validated, completed, failed
    total_rows INT DEFAULT 0,
    successful_rows INT DEFAULT 0,
    failed_rows INT DEFAULT 0,
    skipped_rows INT DEFAULT 0,
    is_dry_run TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS data_import_errors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    import_job_id INT NOT NULL,
    row_num INT NOT NULL,
    record_identifier VARCHAR(100) NULL,
    error_message TEXT NOT NULL,
    raw_data_json TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (import_job_id) REFERENCES data_import_jobs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS data_exchange_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    entity_type VARCHAR(100) NOT NULL,
    direction ENUM('export', 'import') NOT NULL,
    mapping_json TEXT NOT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS report_definitions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    form_id INT NULL,
    filters_json TEXT NULL,
    selected_questions_json TEXT NULL,
    created_by INT NULL,
    is_scheduled TINYINT(1) DEFAULT 0,
    schedule_cron VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS report_runs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_definition_id INT NOT NULL,
    user_id INT NULL,
    status VARCHAR(50) DEFAULT 'completed',
    file_path VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_definition_id) REFERENCES report_definitions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
