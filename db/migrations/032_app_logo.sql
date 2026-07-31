-- Migration 032: Add app_logo_path to system_settings
-- v1.1.20 Build 22 — Logo Upload feature

INSERT INTO system_settings (`key`, `value`, `description`, `is_public`)
VALUES ('app_logo_path', '', 'Path to the custom application logo (relative to public/). Empty = use default.', 0)
ON DUPLICATE KEY UPDATE `key` = `key`;
