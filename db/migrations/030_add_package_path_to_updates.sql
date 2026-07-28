-- Migration: Add package_path column to application_updates
-- Rollback: ALTER TABLE application_updates DROP COLUMN package_path;

ALTER TABLE application_updates ADD COLUMN package_path VARCHAR(500) DEFAULT NULL;
