<?php
namespace App\Models;

use App\Core\Model;

class FormVersion extends Model {
    public static function getLatestVersion(int $formId) {
        return self::fetch("SELECT * FROM form_versions WHERE form_id = ? ORDER BY version_number DESC LIMIT 1", [$formId]);
    }

    public static function getVersion(int $formId, int $versionNumber) {
        return self::fetch("SELECT * FROM form_versions WHERE form_id = ? AND version_number = ?", [$formId, $versionNumber]);
    }

    public static function getVersionsByFormId(int $formId): array {
        return self::fetchAll("SELECT * FROM form_versions WHERE form_id = ? ORDER BY version_number DESC", [$formId]);
    }
}
