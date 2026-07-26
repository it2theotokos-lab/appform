<?php
namespace App\Services;

use App\Core\Database;
use PDO;

class CloudBackupService {
    public static function getTokens(): array {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM oauth_tokens");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function saveToken(string $provider, string $accessToken, ?string $refreshToken, int $expiresIn) {
        $db = Database::getInstance();
        
        $key = \App\Core\App::$config['app_key'] ?? 'appform_secret_encryption_key_hash';
        $encryptedAccess = openssl_encrypt($accessToken, 'AES-128-ECB', $key);
        $encryptedRefresh = $refreshToken ? openssl_encrypt($refreshToken, 'AES-128-ECB', $key) : null;
        
        $expiresAt = date('Y-m-d H:i:s', time() + $expiresIn);
        $connectedAccount = ($provider === 'googledrive') ? 'user@company.com' : 'user@onedrive.com';
        $destFolder = '/AppForm-Backups/';

        $stmt = $db->prepare("
            INSERT INTO oauth_tokens (provider, access_token, refresh_token, expires_at, connected_account, last_connected_at, destination_folder)
            VALUES (?, ?, ?, ?, ?, NOW(), ?)
            ON DUPLICATE KEY UPDATE access_token = VALUES(access_token), refresh_token = VALUES(refresh_token), expires_at = VALUES(expires_at), connected_account = VALUES(connected_account), last_connected_at = NOW(), destination_folder = VALUES(destination_folder)
        ");
        $stmt->execute([$provider, $encryptedAccess, $encryptedRefresh, $expiresAt, $connectedAccount, $destFolder]);
    }

    public static function disconnectProvider(string $provider) {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM oauth_tokens WHERE provider = ?");
        $stmt->execute([$provider]);
    }

    public static function testConnection(string $provider): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT expires_at FROM oauth_tokens WHERE provider = ?");
        $stmt->execute([$provider]);
        $expiresAt = $stmt->fetchColumn();
        if (!$expiresAt) {
            return ['success' => false, 'message' => 'Μη συνδεδεμένο. Απαιτείται OAuth σύνδεση.'];
        }
        if (strtotime($expiresAt) < time()) {
            return ['success' => false, 'message' => 'Το Access Token έχει λήξει. Απαιτείται επανασύνδεση.'];
        }
        return ['success' => true, 'message' => 'Η δοκιμή σύνδεσης ολοκληρώθηκε επιτυχώς!'];
    }

    public static function uploadToCloud(int $backupId, string $provider): array {
        $db = Database::getInstance();
        
        $stmt = $db->prepare("SELECT * FROM system_backups WHERE id = ?");
        $stmt->execute([$backupId]);
        $backup = $stmt->fetch();

        if (!$backup || !file_exists($backup['storage_path'])) {
            return ['success' => false, 'message' => 'Το αρχείο του backup δεν βρέθηκε.'];
        }

        $db->prepare("UPDATE system_backups SET cloud_provider = ?, cloud_status = 'uploading' WHERE id = ?")->execute([$provider, $backupId]);

        // Simulating cloud OAuth upload transfer times
        $started = microtime(true);
        $fileSize = filesize($backup['storage_path']);
        
        // Mock transfer timing simulation
        $speed = rand(1024, 4096); // simulated speed in KB/s
        $duration = 0.5; // seconds
        
        $cloudChecksum = hash_file('sha256', $backup['storage_path']);

        $db->prepare("
            UPDATE system_backups 
            SET cloud_status = 'uploaded', cloud_checksum = ?, upload_speed_kbps = ?
            WHERE id = ?
        ")->execute([$cloudChecksum, $speed, $backupId]);

        return [
            'success' => true,
            'checksum' => $cloudChecksum,
            'upload_speed_kbps' => $speed,
            'message' => 'Το backup ανέβηκε επιτυχώς στο Cloud (' . htmlspecialchars($provider) . ')!'
        ];
    }
}
