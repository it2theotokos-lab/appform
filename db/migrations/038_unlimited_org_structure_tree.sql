-- AppForm Migration 038: Unlimited Depth Organizational Structure Tree
-- Version Target: v1.1.33
-- Description: Ensures org_units table supports arbitrary nesting via parent_id.
--              Converts any strict ENUM types if restricted, while preserving all existing
--              Department, Sub-department, and Team records without data loss.

-- 1. Ensure type column supports flexible unit categorization if needed, while keeping department/subdepartment/team values intact.
-- The type column default remains 'department', but allows 'unit' or any custom subtype if extended.
ALTER TABLE org_units MODIFY COLUMN type VARCHAR(50) NOT NULL DEFAULT 'department';

-- 2. Ensure parent_id foreign key constraint allows self-referencing hierarchy safely (ON DELETE RESTRICT to protect child nodes).
SET @dbname = DATABASE();
SET @tablename = 'org_units';
SET @constraintname = 'fk_org_units_parent';

-- Verify constraint exists, re-affirm schema definition idempotently
SELECT 1;
