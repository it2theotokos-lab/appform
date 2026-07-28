-- Migration: Add user avatar path column
ALTER TABLE users ADD COLUMN avatar_path VARCHAR(255) DEFAULT NULL;
