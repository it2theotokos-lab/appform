<?php
namespace App\Models;

use App\Core\Model;

class Submission extends Model {
    public static function findById(int $id) {
        return self::fetch("SELECT * FROM form_submissions WHERE id = ?", [$id]);
    }

    public static function findByUuid(string $uuid) {
        $sub = self::fetch("SELECT * FROM form_submissions WHERE uuid = ?", [$uuid]);
        if (!$sub && is_numeric($uuid)) {
            $sub = self::fetch("SELECT * FROM form_submissions WHERE id = ?", [(int)$uuid]);
        }
        return $sub;
    }

    public static function getDetailsByUuid(string $uuid) {
        $sql = "
            SELECT s.*, COALESCE(f.title, 'Αρχειοθετημένη/Διαγραμμένη Φόρμα') as form_title, f.slug as form_slug, COALESCE(fv.schema_json, '{\"schemaVersion\":1,\"sections\":[]}') as schema_json, COALESCE(u.username, 'External User') as submitter_name, u.email as user_email
            FROM form_submissions s 
            LEFT JOIN forms f ON s.form_id = f.id 
            LEFT JOIN form_versions fv ON s.form_version_id = fv.id
            LEFT JOIN users u ON s.user_id = u.id
            WHERE s.uuid = ?
        ";
        $sub = self::fetch($sql, [$uuid]);
        if (!$sub && is_numeric($uuid)) {
            $sqlId = str_replace("WHERE s.uuid = ?", "WHERE s.id = ?", $sql);
            $sub = self::fetch($sqlId, [(int)$uuid]);
        }
        return $sub;
    }
}
