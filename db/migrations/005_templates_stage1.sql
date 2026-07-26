-- Migration script for Document Templates (Stage 1)

CREATE TABLE IF NOT EXISTS document_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT NULL,
    source_type VARCHAR(50) NOT NULL, -- 'pdf' or 'docx'
    original_file_path VARCHAR(255) NOT NULL,
    converted_pdf_path VARCHAR(255) NULL,
    status VARCHAR(50) DEFAULT 'draft', -- 'draft', 'published', 'archived'
    current_version_id INT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    archived_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS document_template_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT NOT NULL,
    version_number INT NOT NULL,
    pdf_file_path VARCHAR(255) NOT NULL,
    page_count INT DEFAULT 1,
    page_dimensions_json TEXT NULL,
    consent_text TEXT NULL,
    consent_version INT DEFAULT 1,
    status VARCHAR(50) DEFAULT 'draft',
    published_by INT NULL,
    published_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_template_versions_template FOREIGN KEY (template_id) REFERENCES document_templates(id) ON DELETE CASCADE,
    CONSTRAINT fk_template_versions_pub FOREIGN KEY (published_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add foreign key constraint from templates to versions
ALTER TABLE document_templates ADD CONSTRAINT fk_template_curr_version 
    FOREIGN KEY (current_version_id) REFERENCES document_template_versions(id) ON DELETE SET NULL;
