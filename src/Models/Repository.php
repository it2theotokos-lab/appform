<?php
namespace App\Models;

use App\Core\Model;

class Repository extends Model {
    public static function findById(int $id) {
        return self::fetch("SELECT * FROM repositories WHERE id = ?", [$id]);
    }

    public static function findBySlug(string $slug) {
        return self::fetch("SELECT * FROM repositories WHERE slug = ?", [$slug]);
    }

    public static function getAll(): array {
        return self::fetchAll("SELECT * FROM repositories ORDER BY id DESC");
    }

    public static function getActive(): array {
        return self::fetchAll("SELECT * FROM repositories WHERE is_active = 1 ORDER BY name ASC");
    }

    public static function isUsedInForms(int $repoId): bool {
        // Search in forms versions schema JSON to see if this repository is referenced
        self::init();
        $stmt = self::$db->query("SELECT schema_json FROM form_versions");
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $schema = json_decode($row['schema_json'], true);
            if (isset($schema['sections'])) {
                foreach ($schema['sections'] as $sec) {
                    if (isset($sec['fields'])) {
                        foreach ($sec['fields'] as $f) {
                            if (isset($f['repositoryId']) && $f['repositoryId'] == $repoId) {
                                return true;
                            }
                        }
                    }
                }
            }
        }
        return false;
    }
}
