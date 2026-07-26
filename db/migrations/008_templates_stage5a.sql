-- Migration script for Document Templates Stage 5A (Signature Capture)
CREATE TABLE IF NOT EXISTS document_signatures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_instance_id INT NOT NULL,
    field_key VARCHAR(150) NOT NULL,
    page INT NOT NULL,
    signature_image VARCHAR(255) NOT NULL,
    signature_hash VARCHAR(64) NOT NULL,
    signed_by INT NOT NULL,
    signed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (document_instance_id) REFERENCES document_instances(id) ON DELETE CASCADE,
    FOREIGN KEY (signed_by) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_doc_field_sig (document_instance_id, field_key),
    INDEX idx_sig_doc_inst (document_instance_id),
    INDEX idx_sig_field_key (field_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
