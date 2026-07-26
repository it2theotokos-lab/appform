<?php
namespace App\Services;

class PluginManifestValidator {
    public static function validate(array $manifest): array {
        $required = ['id', 'name', 'version', 'provider'];
        foreach ($required as $field) {
            if (empty($manifest[$field])) {
                return ['valid' => false, 'message' => "Το υποχρεωτικό πεδίο '{$field}' λείπει από το manifest."];
            }
        }

        // Check plugin ID format (vendor.plugin)
        if (!preg_match('/^[a-z0-9\-]+\.[a-z0-9\-]+$/', $manifest['id'])) {
            return ['valid' => false, 'message' => "Το plugin ID '{$manifest['id']}' έχει μη έγκυρη μορφή (απαιτείται vendor.plugin)."];
        }

        // Check semantic versioning
        if (!preg_match('/^\d+\.\d+\.\d+$/', $manifest['version'])) {
            return ['valid' => false, 'message' => "Η έκδοση '{$manifest['version']}' δεν είναι έγκυρη έκδοση Semantic Versioning (x.y.z)."];
        }

        return ['valid' => true];
    }
}
