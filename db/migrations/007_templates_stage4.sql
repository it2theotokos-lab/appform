-- Migration script for Document Templates Stage 4 (User Instances)
CREATE TABLE IF NOT EXISTS document_instances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_template_id INT NOT NULL,
    template_version_id INT NOT NULL,
    document_number VARCHAR(100) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    owner_type VARCHAR(50) DEFAULT 'user',
    owner_id INT NOT NULL,
    created_by INT NOT NULL,
    assigned_to INT NULL,
    status VARCHAR(50) DEFAULT 'draft', -- 'draft', 'submitted', 'pending_signature', 'finalized', 'rejected', 'cancelled'
    current_step INT DEFAULT 1,
    submitted_at TIMESTAMP NULL DEFAULT NULL,
    finalized_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (document_template_id) REFERENCES document_templates(id) ON DELETE CASCADE,
    FOREIGN KEY (template_version_id) REFERENCES document_template_versions(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS document_instance_values (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_instance_id INT NOT NULL,
    field_key VARCHAR(150) NOT NULL,
    field_type VARCHAR(50) NOT NULL,
    value_text TEXT NULL,
    value_json TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (document_instance_id) REFERENCES document_instances(id) ON DELETE CASCADE,
    UNIQUE KEY uq_instance_field (document_instance_id, field_key),
    INDEX idx_instance_field_key (field_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
