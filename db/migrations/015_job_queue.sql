-- Migration script for Enterprise Job Queue Engine
CREATE TABLE IF NOT EXISTS jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(64) NOT NULL UNIQUE,
    type VARCHAR(100) NOT NULL, -- e.g. 'App\Jobs\SendEmailJob'
    queue VARCHAR(50) DEFAULT 'default',
    priority VARCHAR(20) DEFAULT 'normal', -- 'critical', 'high', 'normal', 'low'
    status VARCHAR(50) DEFAULT 'pending', -- 'pending', 'scheduled', 'running', 'completed', 'retrying', 'failed', 'cancelled'
    payload TEXT NOT NULL, -- encrypted or redacted JSON data
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    scheduled_at TIMESTAMP NULL DEFAULT NULL,
    started_at TIMESTAMP NULL DEFAULT NULL,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    failed_at TIMESTAMP NULL DEFAULT NULL,
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 3,
    progress INT DEFAULT 0,
    result TEXT NULL,
    last_error TEXT NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS job_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    attempt INT NOT NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    status VARCHAR(50) NOT NULL,
    error_message TEXT NULL,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS job_workers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hostname VARCHAR(150) NOT NULL,
    pid INT NOT NULL,
    queue_list VARCHAR(255) NOT NULL,
    status VARCHAR(50) DEFAULT 'idle', -- 'idle', 'working', 'offline'
    current_job_id INT NULL,
    memory_usage BIGINT DEFAULT 0,
    heartbeat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (current_job_id) REFERENCES jobs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS job_locks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lock_key VARCHAR(150) NOT NULL UNIQUE,
    owner_pid INT NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
