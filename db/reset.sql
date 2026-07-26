-- AppForm Database RESET Script
-- WARNING: This DESTROYS ALL DATA. For development/reinstall only.
-- Run: php db/install.php  (after this file)

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS schema_migrations;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS ldap_role_mappings;
DROP TABLE IF EXISTS ldap_config;
DROP TABLE IF EXISTS navigation_menu_items;
DROP TABLE IF EXISTS navigation_menus;
DROP TABLE IF EXISTS submission_status_history;
DROP TABLE IF EXISTS submission_files;
DROP TABLE IF EXISTS form_submissions;
DROP TABLE IF EXISTS form_versions;
DROP TABLE IF EXISTS form_role_assignments;
DROP TABLE IF EXISTS form_user_assignments;
DROP TABLE IF EXISTS forms;
DROP TABLE IF EXISTS repositories;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS notification_templates;
DROP TABLE IF EXISTS system_settings;
DROP TABLE IF EXISTS saved_reports;
DROP TABLE IF EXISTS export_jobs;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS role_permissions;
DROP TABLE IF EXISTS permissions;
DROP TABLE IF EXISTS roles;

SET FOREIGN_KEY_CHECKS = 1;
