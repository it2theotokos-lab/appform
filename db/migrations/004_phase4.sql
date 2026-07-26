-- AppForm Migration 004: Phase 4 Saved Reports, Exports, Notifications, Settings and Snapshots

-- 1. Saved Reports Table
CREATE TABLE IF NOT EXISTS saved_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    report_type VARCHAR(50) DEFAULT 'submissions',
    form_id INT NOT NULL,
    owner_user_id INT NOT NULL,
    filters_json TEXT NULL,
    columns_json TEXT NULL,
    is_shared TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE,
    FOREIGN KEY (owner_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Export Jobs Table
CREATE TABLE IF NOT EXISTS export_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(64) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    export_type VARCHAR(50) NOT NULL, -- csv, excel, pdf
    report_type VARCHAR(50) DEFAULT 'submissions',
    form_id INT NOT NULL,
    filters_json TEXT NULL,
    status VARCHAR(50) DEFAULT 'pending', -- pending, processing, completed, failed, expired
    file_path VARCHAR(255) NULL,
    file_name VARCHAR(150) NULL,
    error_message TEXT NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Notification Templates Table
CREATE TABLE IF NOT EXISTS notification_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(150) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body_html TEXT NOT NULL,
    body_text TEXT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Notifications Table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) DEFAULT 'system',
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    link_url VARCHAR(255) NULL,
    is_read TINYINT(1) DEFAULT 0,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. System Settings Table
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NULL,
    setting_type VARCHAR(50) DEFAULT 'string', -- string, integer, boolean, array
    is_public TINYINT(1) DEFAULT 0,
    updated_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Default Settings
INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_type, is_public) VALUES 
('app_name', 'AppForm Portal', 'string', 1),
('default_locale', 'el', 'string', 1),
('records_per_page', '15', 'integer', 1),
('csv_delimiter', ';', 'string', 0),
('upload_max_filesize_mb', '10', 'integer', 1);

-- Insert Default Notification Templates
INSERT IGNORE INTO notification_templates (slug, name, subject, body_html, body_text) VALUES 
('submission_submitted', 'Νέα Υποβολή Φόρμας', 'Λάβαμε την υποβολή σας επιτυχώς.', '<p>Η υποβολή σας καταγράφηκε.</p>', 'Η υποβολή σας καταγράφηκε.'),
('submission_approved', 'Έγκριση Υποβολής', 'Η υποβολή σας εγκρίθηκε.', '<p>Η υποβολή σας εγκρίθηκε επιτυχώς.</p>', 'Η υποβολή σας εγκρίθηκε επιτυχώς.');
