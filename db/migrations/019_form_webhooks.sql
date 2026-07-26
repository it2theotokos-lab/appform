-- Webhook integration migration
CREATE TABLE IF NOT EXISTS form_webhooks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    url VARCHAR(255) NOT NULL,
    method VARCHAR(10) DEFAULT 'POST',
    auth_type VARCHAR(20) DEFAULT 'none',
    auth_token VARCHAR(255) NULL,
    custom_headers TEXT NULL,
    is_enabled TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS form_webhook_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    webhook_id INT NOT NULL,
    submission_id INT NOT NULL,
    response_code INT NULL,
    response_body TEXT NULL,
    duration_ms INT DEFAULT 0,
    status VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (webhook_id) REFERENCES form_webhooks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
