-- Migration script for ExampleReports Plugin
CREATE TABLE IF NOT EXISTS plg_example_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    status VARCHAR(50) DEFAULT 'draft',
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS plg_example_report_outputs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    job_id INT NULL,
    storage_reference VARCHAR(255) NOT NULL,
    format VARCHAR(20) NOT NULL,
    file_size INT NOT NULL,
    sha256 VARCHAR(64) NOT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    generated_by INT NULL,
    FOREIGN KEY (report_id) REFERENCES plg_example_reports(id) ON DELETE CASCADE,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
