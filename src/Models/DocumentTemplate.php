<?php
namespace App\Models;

use App\Core\Model;

class DocumentTemplate extends Model {
    public static function getAll(): array {
        return self::fetchAll("
            SELECT t.*, u.full_name as creator_name, v.version_number 
            FROM document_templates t 
            JOIN users u ON t.created_by = u.id 
            LEFT JOIN document_template_versions v ON t.current_version_id = v.id 
            ORDER BY t.id DESC
        ");
    }

    public static function findById(int $id) {
        return self::fetch("
            SELECT t.*, u.full_name as creator_name, v.version_number, v.page_count, v.page_dimensions_json, v.fields_schema_json
            FROM document_templates t
            JOIN users u ON t.created_by = u.id
            LEFT JOIN document_template_versions v ON t.current_version_id = v.id
            WHERE t.id = ?
        ", [$id]);
    }

    public static function create(array $data): int {
        self::init();
        $stmt = self::$db->prepare("
            INSERT INTO document_templates (title, slug, description, source_type, original_file_path, converted_pdf_path, status, created_by)
            VALUES (?, ?, ?, ?, ?, ?, 'draft', ?)
        ");
        $stmt->execute([
            $data['title'],
            $data['slug'],
            $data['description'] ?? null,
            $data['source_type'],
            $data['original_file_path'],
            $data['converted_pdf_path'] ?? null,
            $data['created_by']
        ]);
        return (int)self::$db->lastInsertId();
    }
}
