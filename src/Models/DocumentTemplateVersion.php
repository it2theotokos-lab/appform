<?php
namespace App\Models;

use App\Core\Model;

class DocumentTemplateVersion extends Model {
    public static function create(array $data): int {
        self::init();
        $stmt = self::$db->prepare("
            INSERT INTO document_template_versions (template_id, version_number, pdf_file_path, page_count, page_dimensions_json, consent_text, consent_version, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'draft')
        ");
        $stmt->execute([
            $data['template_id'],
            $data['version_number'],
            $data['pdf_file_path'],
            $data['page_count'] ?? 1,
            $data['page_dimensions_json'] ?? null,
            $data['consent_text'] ?? null,
            $data['consent_version'] ?? 1
        ]);
        return (int)self::$db->lastInsertId();
    }
}
