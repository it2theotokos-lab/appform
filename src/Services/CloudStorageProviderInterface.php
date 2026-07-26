<?php
namespace App\Services;

interface CloudStorageProviderInterface {
    public function connect(string $code): array;
    public function disconnect();
    public function refreshToken(): bool;
    public function getQuota(): array;
    public function startUpload(string $filePath, string $remoteName): array;
    public function uploadChunk(string $uploadSessionUrl, string $chunkData, int $rangeStart, int $rangeEnd, int $totalBytes): array;
    public function resumeUpload(string $uploadSessionUrl, int $totalBytes): int;
    public function verifyUpload(string $remoteFileId, string $localFilePath): bool;
    public function downloadForVerification(string $remoteFileId, string $localDestPath): bool;
    public function deleteRemoteBackup(string $remoteFileId): bool;
    public function listRemoteBackups(): array;
}
