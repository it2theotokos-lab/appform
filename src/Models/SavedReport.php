<?php
namespace App\Models;

use App\Core\Model;

class SavedReport extends Model {
    public static function findById(int $id) {
        return self::fetch("SELECT * FROM saved_reports WHERE id = ?", [$id]);
    }

    public static function getByFormId(int $formId): array {
        return self::fetchAll("SELECT * FROM saved_reports WHERE form_id = ? ORDER BY created_at DESC", [$formId]);
    }
}
