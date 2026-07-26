-- Migration script for Document Templates (Stage 3 fields mapping addition)
DROP PROCEDURE IF EXISTS _appform_mig_006_fschema;
DELIMITER $$
CREATE PROCEDURE _appform_mig_006_fschema()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'document_template_versions' AND COLUMN_NAME = 'fields_schema_json'
    ) THEN
        ALTER TABLE document_template_versions ADD COLUMN fields_schema_json LONGTEXT NULL AFTER page_dimensions_json;
    END IF;
END$$
DELIMITER ;
CALL _appform_mig_006_fschema();
DROP PROCEDURE IF EXISTS _appform_mig_006_fschema;
