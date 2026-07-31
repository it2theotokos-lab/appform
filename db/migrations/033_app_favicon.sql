-- Migration 033: Add app_favicon_path to system_settings
-- v1.1.22 — Application Favicon Upload feature

INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_type, is_public)
VALUES ('app_favicon_path', '', 'string', 0);
