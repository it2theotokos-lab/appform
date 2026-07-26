<?php
namespace App\Models;

use App\Core\Model;

class Form extends Model {
    public static function findById(int $id) {
        return self::fetch("SELECT * FROM forms WHERE id = ?", [$id]);
    }

    public static function findBySlug(string $slug) {
        return self::fetch("SELECT * FROM forms WHERE slug = ? AND is_active = 1", [$slug]);
    }

    public static function getAll(): array {
        return self::fetchAll("SELECT * FROM forms ORDER BY id DESC");
    }

    public static function hasSubmissions(int $formId): bool {
        $r = self::fetch("SELECT COUNT(*) as count FROM form_submissions WHERE form_id = ?", [$formId]);
        return ($r['count'] ?? 0) > 0;
    }
}
