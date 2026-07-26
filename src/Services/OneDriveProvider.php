<?php
namespace App\Services;

class OneDriveProvider implements CloudStorageProviderInterface {
    public function connect(string $code): array {
        return ['access_token' => 'mock_onedrive_access_token', 'refresh_token' => 'mock_onedrive_refresh_token', 'expires_in' => 3600];
    }
    public function disconnect() { return true; }
    public function refreshToken(): bool { return true; }
    public function getQuota(): array { return ['used' => 3.75 * 1024 * 1024 * 1024, 'total' => 15.00 * 1024 * 1024 * 1024]; }
    public function startUpload(string $filePath, string $remoteName): array {
        return ['session_url' => 'http://localhost/mock-onedrive-resumable-session', 'bytes_uploaded' => 0];
    }
    public function uploadChunk(string $uploadSessionUrl, string $chunkData, int $rangeStart, int $rangeEnd, int $totalBytes): array {
        return ['success' => true, 'bytes_uploaded' => $rangeEnd + 1];
    }
    public function resumeUpload(string $uploadSessionUrl, int $totalBytes): int { return 0; }
    public function verifyUpload(string $remoteFileId, string $localFilePath): bool { return true; }
    public function downloadForVerification(string $remoteFileId, string $localDestPath): bool { return true; }
    public function deleteRemoteBackup(string $remoteFileId): bool { return true; }
    public function listRemoteBackups(): array { return []; }
}
