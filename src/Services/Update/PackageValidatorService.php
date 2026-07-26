<?php
namespace App\Services\Update;

use ZipArchive;

class PackageValidatorService {
    private const MAX_FILE_COUNT = 5000;
    private const MAX_TOTAL_SIZE = 524288000; // 500MB
    private const MAX_COMPRESSION_RATIO = 100;

    private static $protectedPaths = [
        'config/config.local.php',
        'config/installed.lock',
        '.env',
        '.env.local',
        'public/storage'
    ];

    /**
     * Performs complete security validation on the update zip package.
     */
    public static function validatePackage(string $zipPath): array {
        if (!file_exists($zipPath)) {
            return ['success' => false, 'message' => 'Το αρχείο package δεν υπάρχει.'];
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return ['success' => false, 'message' => 'Αδυναμία ανοίγματος του αρχείου ZIP.'];
        }

        try {
            $numFiles = $zip->numFiles;
            if ($numFiles > self::MAX_FILE_COUNT) {
                return ['success' => false, 'message' => 'Υπερβολικός αριθμός αρχείων στο ZIP.'];
            }

            $totalSize = 0;
            $manifestContent = null;
            $checksumsContent = null;
            $fileNames = [];

            for ($i = 0; $i < $numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $name = $stat['name'];

                // Path and traversal security checks
                if (self::hasSecurityVulnerability($name)) {
                    return ['success' => false, 'message' => "Εντοπίστηκε μη ασφαλές path στο ZIP: {$name}"];
                }

                // Check case-insensitive collision
                $lowerName = strtolower($name);
                if (in_array($lowerName, $fileNames, true)) {
                    return ['success' => false, 'message' => "Εντοπίστηκε case-insensitive collision: {$name}"];
                }
                $fileNames[] = $lowerName;

                // Check protected path overwrite
                foreach (self::$protectedPaths as $protected) {
                    if (str_starts_with($name, $protected)) {
                        return ['success' => false, 'message' => "Απαγορεύεται η αντικατάσταση προστατευμένου path: {$name}"];
                    }
                }

                $compSize = $stat['comp_size'];
                $size = $stat['size'];
                $totalSize += $size;

                if ($size > 0 && $compSize > 0) {
                    $ratio = $size / $compSize;
                    if ($ratio > self::MAX_COMPRESSION_RATIO) {
                        return ['success' => false, 'message' => "Ύποπτο compression ratio (ZIP bomb): {$name}"];
                    }
                }

                if ($name === 'manifest.json') {
                    $manifestContent = $zip->getFromIndex($i);
                } elseif ($name === 'checksums.json') {
                    $checksumsContent = $zip->getFromIndex($i);
                }
            }

            if ($totalSize > self::MAX_TOTAL_SIZE) {
                return ['success' => false, 'message' => 'Το αποσυμπιεσμένο μέγεθος υπερβαίνει το επιτρεπτό όριο.'];
            }

            if (!$manifestContent) {
                return ['success' => false, 'message' => 'Το αρχείο manifest.json λείπει.'];
            }

            // Manifest parsing
            $manifest = json_decode($manifestContent, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['success' => false, 'message' => 'Το manifest.json δεν είναι έγκυρο JSON.'];
            }

            // Verify Manifest properties
            $verResult = self::validateManifestData($manifest);
            if (!$verResult['success']) {
                return $verResult;
            }

        } finally {
            $zip->close();
        }

        return ['success' => true, 'message' => 'Το package είναι έγκυρο και ασφαλές.'];
    }

    /**
     * Validates structural values within manifest.json.
     */
    private static function validateManifestData(array $manifest): array {
        if (($manifest['manifest_schema_version'] ?? '') !== '1.0.0') {
            return ['success' => false, 'message' => 'Μη υποστηριζόμενη έκδοση manifest schema.'];
        }
        if (($manifest['product'] ?? '') !== 'AppForm') {
            return ['success' => false, 'message' => 'Το package δεν αφορά την εφαρμογή AppForm.'];
        }
        if (!in_array($manifest['package_type'] ?? '', ['full', 'update'], true)) {
            return ['success' => false, 'message' => 'Μη έγκυρο package type στο manifest.'];
        }
        if (empty($manifest['version']) || empty($manifest['build'])) {
            return ['success' => false, 'message' => 'Τα πεδία version και build είναι υποχρεωτικά.'];
        }
        return ['success' => true];
    }

    /**
     * Checks paths for path traversals, absolute keys, or drive letters.
     */
    public static function hasSecurityVulnerability(string $path): bool {
        if (str_contains($path, '..') || str_contains($path, '\\')) {
            return true;
        }
        if (preg_match('/^[a-zA-Z]:/', $path)) {
            return true;
        }
        if (str_starts_with($path, '/') || str_contains($path, "\0")) {
            return true;
        }
        return false;
    }
}
