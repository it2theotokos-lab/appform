<?php
namespace App\Models;

use App\Core\Model;

class DocumentInstance extends Model {
    public static function create(array $data): int {
        self::init();
        $stmt = self::$db->prepare("
            INSERT INTO document_instances (document_template_id, template_version_id, document_number, title, owner_type, owner_id, created_by, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'draft')
        ");
        $stmt->execute([
            $data['document_template_id'],
            $data['template_version_id'],
            $data['document_number'],
            $data['title'],
            $data['owner_type'] ?? 'user',
            $data['owner_id'],
            $data['created_by']
        ]);
        return (int)self::$db->lastInsertId();
    }

    public static function findById(int $id) {
        return self::fetch("
            SELECT i.*, t.title as template_title, t.slug as template_slug, v.version_number, v.pdf_file_path, v.fields_schema_json, u.full_name as creator_name
            FROM document_instances i
            JOIN document_templates t ON i.document_template_id = t.id
            JOIN document_template_versions v ON i.template_version_id = v.id
            JOIN users u ON i.created_by = u.id
            WHERE i.id = ?
        ", [$id]);
    }

    public static function getByUser(int $userId, $status = null): array {
        $sql = "
            SELECT i.*, t.title as template_title, v.version_number
            FROM document_instances i
            JOIN document_templates t ON i.document_template_id = t.id
            JOIN document_template_versions v ON i.template_version_id = v.id
            WHERE (i.created_by = ? OR i.owner_id = ? OR i.assigned_to = ?)
        ";
        $params = [$userId, $userId, $userId];

        if ($status !== null) {
            if ($status === 'not_draft') {
                $sql .= " AND i.status != 'draft'";
            } else {
                $sql .= " AND i.status = ?";
                $params[] = $status;
            }
        }

        $sql .= " ORDER BY i.id DESC";
        return self::fetchAll($sql, $params);
    }

    public static function getAllAdmin($status = null): array {
        $sql = "
            SELECT i.*, t.title as template_title, v.version_number, u.full_name as creator_name
            FROM document_instances i
            JOIN document_templates t ON i.document_template_id = t.id
            JOIN document_template_versions v ON i.template_version_id = v.id
            JOIN users u ON i.created_by = u.id
        ";
        $params = [];

        if ($status !== null) {
            if ($status === 'not_draft') {
                $sql .= " WHERE i.status != 'draft'";
            } else {
                $sql .= " WHERE i.status = ?";
                $params[] = $status;
            }
        }

        $sql .= " ORDER BY i.id DESC";
        return self::fetchAll($sql, $params);
    }

    public static function generateDocumentNumber(int $templateId): string {
        self::init();
        try {
            self::$db->beginTransaction();
            // Lock template table record to safely fetch current sequence count without race conditions
            $stmt = self::$db->prepare("SELECT id, slug FROM document_templates WHERE id = ? FOR UPDATE");
            $stmt->execute([$templateId]);
            $template = $stmt->fetch();
            
            $prefix = $template ? strtoupper($template['slug']) : 'DOC';
            $year = date('Y');

            // Count existing matching documents
            $stmtCount = self::$db->prepare("SELECT COUNT(*) as total FROM document_instances WHERE document_template_id = ?");
            $stmtCount->execute([$templateId]);
            $count = (int)$stmtCount->fetch()['total'] + 1;

            self::$db->commit();
            
            return sprintf("%s-%s-%06d", $prefix, $year, $count);
        } catch (\Exception $e) {
            if (self::$db->inTransaction()) {
                self::$db->rollBack();
            }
            throw $e;
        }
    }
}
