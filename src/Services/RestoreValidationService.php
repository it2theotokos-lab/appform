<?php
namespace App\Services;

use App\Core\Database;

class RestoreValidationService {
    public static function validateChecksum(string $filePath, string $expectedHash): bool {
        if (!file_exists($filePath)) {
            return false;
        }
        $actualHash = hash_file('sha256', $filePath);
        return hash_equals($expectedHash, $actualHash);
    }

    public static function checkCompatibility(array $manifest): array {
        $warnings = [];
        
        $phpVersion = PHP_VERSION;
        if (version_compare($phpVersion, '8.0.0', '<')) {
            $warnings[] = "Μη συμβατή έκδοση PHP: " . $phpVersion;
        }

        $appVer = $manifest['application_version'] ?? '1.0.0';
        if (version_compare($appVer, '1.0.0', '<')) {
            $warnings[] = "Η έκδοση της εφαρμογής στο backup (" . $appVer . ") είναι παλαιότερη.";
        }

        return [
            'compatible' => empty($warnings),
            'warnings' => $warnings
        ];
    }
}
