<?php
namespace App\Services;

use App\Core\Database;

class CloudVerificationService {
    public static function verifyRemoteHash(string $localFilePath, string $remoteChecksum): bool {
        if (!file_exists($localFilePath)) return false;
        $localHash = hash_file('sha256', $localFilePath);
        return hash_equals($localHash, $remoteChecksum);
    }
}
