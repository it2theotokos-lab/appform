<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class PdfDesign {

    public static function getByFormId(int $formId): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM form_pdf_designs WHERE form_id = ?");
        $stmt->execute([$formId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $row['elements'] = json_decode($row['design_json'], true) ?: [];
        }
        return $row ?: null;
    }

    public static function save(int $formId, array $elements): bool {
        $db = Database::getInstance();
        $json = json_encode($elements, JSON_UNESCAPED_UNICODE);
        
        $stmt = $db->prepare("SELECT id FROM form_pdf_designs WHERE form_id = ?");
        $stmt->execute([$formId]);
        if ($stmt->fetch()) {
            $up = $db->prepare("UPDATE form_pdf_designs SET design_json = ?, updated_at = NOW() WHERE form_id = ?");
            return $up->execute([$json, $formId]);
        } else {
            $ins = $db->prepare("INSERT INTO form_pdf_designs (form_id, design_json) VALUES (?, ?)");
            return $ins->execute([$formId, $json]);
        }
    }

    public static function delete(int $formId): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM form_pdf_designs WHERE form_id = ?");
        return $stmt->execute([$formId]);
    }
}
