-- Migration script for Document Templates Stage 7 (Workflow & Approvals)

CREATE TABLE IF NOT EXISTS workflow_definitions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    document_template_id INT NULL,
    is_active TINYINT(1) DEFAULT 1,
    version INT DEFAULT 1,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    entity_type VARCHAR(50) DEFAULT 'document',
    entity_id INT(11) DEFAULT NULL,
    FOREIGN KEY (document_template_id) REFERENCES document_templates(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS workflow_steps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workflow_definition_id INT NOT NULL,
    step_order INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    step_type VARCHAR(50) NOT NULL, -- 'review', 'approval', 'signature', 'notification', 'finalization'
    assignment_type VARCHAR(50) NOT NULL, -- 'user', 'role', 'department', 'document_creator', 'manager', 'administrator'
    assigned_user_id INT NULL,
    assigned_role_id INT NULL,
    assigned_department_id INT NULL,
    requires_signature TINYINT(1) DEFAULT 0,
    signature_field_key VARCHAR(150) NULL,
    approval_mode VARCHAR(50) DEFAULT 'sequential', -- 'sequential', 'parallel_all', 'parallel_any'
    due_days INT NULL,
    allow_return TINYINT(1) DEFAULT 1,
    allow_reject TINYINT(1) DEFAULT 1,
    is_required TINYINT(1) DEFAULT 1,
    config_json TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (workflow_definition_id) REFERENCES workflow_definitions(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_role_id) REFERENCES roles(id) ON DELETE SET NULL,
    INDEX idx_wf_steps_def_id (workflow_definition_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS workflow_instances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_instance_id INT NULL UNIQUE,
    workflow_definition_id INT NOT NULL,
    current_step_order INT DEFAULT 1,
    status VARCHAR(50) DEFAULT 'active', -- 'active', 'approved', 'rejected', 'cancelled', 'completed'
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    entity_type VARCHAR(50) DEFAULT 'document',
    entity_id INT(11) DEFAULT NULL,
    FOREIGN KEY (document_instance_id) REFERENCES document_instances(id) ON DELETE CASCADE,
    FOREIGN KEY (workflow_definition_id) REFERENCES workflow_definitions(id) ON DELETE RESTRICT,
    INDEX idx_wf_instances_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS workflow_step_instances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workflow_instance_id INT NOT NULL,
    workflow_step_id INT NOT NULL,
    step_order INT NOT NULL,
    assigned_user_id INT NULL,
    assigned_role_id INT NULL,
    status VARCHAR(50) DEFAULT 'pending', -- 'pending', 'active', 'approved', 'rejected', 'returned', 'skipped', 'completed', 'cancelled', 'expired'
    decision VARCHAR(50) NULL, -- 'approved', 'rejected', 'returned'
    comment TEXT NULL,
    due_at TIMESTAMP NULL DEFAULT NULL,
    acted_by INT NULL,
    acted_at TIMESTAMP NULL DEFAULT NULL,
    signature_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (workflow_instance_id) REFERENCES workflow_instances(id) ON DELETE CASCADE,
    FOREIGN KEY (workflow_step_id) REFERENCES workflow_steps(id) ON DELETE RESTRICT,
    FOREIGN KEY (assigned_user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_role_id) REFERENCES roles(id) ON DELETE SET NULL,
    FOREIGN KEY (acted_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (signature_id) REFERENCES document_signatures(id) ON DELETE SET NULL,
    INDEX idx_wf_step_inst_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS workflow_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workflow_instance_id INT NOT NULL,
    workflow_step_instance_id INT NULL,
    document_instance_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    visibility VARCHAR(50) DEFAULT 'creator_and_reviewers', -- 'workflow_participants', 'creator_and_reviewers', 'administrators'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (workflow_instance_id) REFERENCES workflow_instances(id) ON DELETE CASCADE,
    FOREIGN KEY (workflow_step_instance_id) REFERENCES workflow_step_instances(id) ON DELETE SET NULL,
    FOREIGN KEY (document_instance_id) REFERENCES document_instances(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add active workflow_definition_id to document_templates to assign a workflow definition
ALTER TABLE document_templates ADD COLUMN active_workflow_definition_id INT NULL AFTER status;
ALTER TABLE document_templates ADD FOREIGN KEY (active_workflow_definition_id) REFERENCES workflow_definitions(id) ON DELETE SET NULL;
