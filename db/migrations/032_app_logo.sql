-- Migration 032: Add app_logo_path to system_settings
-- v1.1.20 Build 22 — Logo Upload feature

INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_type, is_public)
VALUES ('app_logo_path', '', 'string', 0);
