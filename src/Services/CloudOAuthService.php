<?php
namespace App\Services;

use App\Core\Database;

class CloudOAuthService {
    public static function saveSecureToken(string $provider, string $accessToken, ?string $refreshToken, int $expiresIn) {
        $db = Database::getInstance();
        
        $encAccess = CloudEncryptionService::encryptToken($accessToken);
        $encRefresh = $refreshToken ? CloudEncryptionService::encryptToken($refreshToken) : null;
        
        $expiresAt = date('Y-m-d H:i:s', time() + $expiresIn);

        $stmt = $db->prepare("
            INSERT INTO oauth_tokens (provider, access_token, refresh_token, expires_at)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE access_token = VALUES(access_token), refresh_token = VALUES(refresh_token), expires_at = VALUES(expires_at)
        ");
        $stmt->execute([$provider, $encAccess, $encRefresh, $expiresAt]);
    }
}
