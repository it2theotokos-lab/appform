-- AppForm Migration 031: Structured Repository Columns

ALTER TABLE repositories ADD COLUMN columns_json LONGTEXT NULL AFTER description;
