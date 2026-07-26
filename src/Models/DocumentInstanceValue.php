<?php
namespace App\Models;

use App\Core\Model;

class DocumentInstanceValue extends Model {
    public static function getValuesForInstance(int $instanceId): array {
        $rows = self::fetchAll("SELECT * FROM document_instance_values WHERE document_instance_id = ?", [$instanceId]);
        $vals = [];
        foreach ($rows as $row) {
            $vals[$row['field_key']] = $row['value_text'];
        }
        return $vals;
    }

    public static function saveValue(int $instanceId, string $key, string $type, ?string $value) {
        self::init();
        $stmt = self::$db->prepare("
            INSERT INTO document_instance_values (document_instance_id, field_key, field_type, value_text)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE value_text = ?, updated_at = NOW()
        ");
        $stmt->execute([$instanceId, $key, $type, $value, $value]);
    }
}
