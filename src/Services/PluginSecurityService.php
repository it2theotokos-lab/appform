<?php
namespace App\Services;

class PluginSecurityService {
    public static function checkZipEntry(string $fileName): bool {
        // Zip Slip path traversal checks
        if (str_contains($fileName, '../') || str_contains($fileName, '..\\')) {
            return false;
        }

        // Prohibited file extensions
        $badExtensions = ['exe', 'dll', 'bat', 'cmd', 'sh', 'ps1', 'msi', 'com', 'vbs'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (in_array($ext, $badExtensions)) {
            return false;
        }

        return true;
    }
}
