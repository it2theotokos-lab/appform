<?php
namespace App\Models;

use App\Core\Model;

class DocumentSignature extends Model {
    public static function create(array $data): int {
        self::init();
        $stmt = self::$db->prepare("
            INSERT INTO document_signatures (document_instance_id, field_key, page, signature_image, signature_hash, signed_by, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE signature_image = ?, signature_hash = ?, signed_by = ?, ip_address = ?, user_agent = ?, signed_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([
            $data['document_instance_id'],
            $data['field_key'],
            $data['page'],
            $data['signature_image'],
            $data['signature_hash'],
            $data['signed_by'],
            $data['ip_address'],
            $data['user_agent'],
            $data['signature_image'],
            $data['signature_hash'],
            $data['signed_by'],
            $data['ip_address'],
            $data['user_agent']
        ]);
        return (int)self::$db->lastInsertId();
    }

    public static function findByField(int $instanceId, string $key) {
        return self::fetch("
            SELECT * FROM document_signatures 
            WHERE document_instance_id = ? AND field_key = ?
        ", [$instanceId, $key]);
    }

    public static function getForInstance(int $instanceId): array {
        return self::fetchAll("
            SELECT * FROM document_signatures 
            WHERE document_instance_id = ?
        ", [$instanceId]);
    }

    public static function deleteForField(int $instanceId, string $key) {
        self::init();
        $stmt = self::$db->prepare("
            DELETE FROM document_signatures 
            WHERE document_instance_id = ? AND field_key = ?
        ");
        $stmt->execute([$instanceId, $key]);
    }
}
