-- Migration 026: Add OAuth settings for Cloud Backup
INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_type, is_public) VALUES 
('google_client_id', '', 'string', 0),
('google_client_secret', '', 'string', 0),
('google_redirect_uri', '', 'string', 0),
('onedrive_client_id', '', 'string', 0),
('onedrive_client_secret', '', 'string', 0),
('onedrive_tenant_id', 'common', 'string', 0),
('onedrive_redirect_uri', '', 'string', 0);
