-- Migration script for Document Templates Stage 6 (Final immutable PDF generation storage)
CREATE TABLE IF NOT EXISTS document_final_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_instance_id INT NOT NULL UNIQUE,
    file_path VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_size INT NOT NULL,
    sha256_hash VARCHAR(64) NOT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    generated_by INT NOT NULL,
    template_version_id INT NOT NULL,
    generation_status VARCHAR(50) NOT NULL DEFAULT 'pending', -- 'pending', 'processing', 'completed', 'failed'
    generation_error TEXT NULL,
    page_count INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (document_instance_id) REFERENCES document_instances(id) ON DELETE CASCADE,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (template_version_id) REFERENCES document_template_versions(id) ON DELETE RESTRICT,
    INDEX idx_final_sha256 (sha256_hash),
    INDEX idx_final_gen_status (generation_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
