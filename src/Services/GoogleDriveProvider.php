<?php
namespace App\Services;

class GoogleDriveProvider implements CloudStorageProviderInterface {
    private $accessToken;
    private $refreshToken;
    private $expiresAt;
    private $db;
    private $key;

    public function __construct() {
        $this->db = \App\Core\Database::getInstance();
        $this->key = \App\Core\App::$config['app_key'] ?? 'appform_secret_encryption_key_hash';
        $this->loadTokens();
    }

    private function loadTokens() {
        $stmt = $this->db->prepare("SELECT * FROM oauth_tokens WHERE provider = 'googledrive'");
        $stmt->execute();
        $tok = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($tok) {
            $this->accessToken = openssl_decrypt($tok['access_token'], 'AES-128-ECB', $this->key);
            $this->refreshToken = $tok['refresh_token'] ? openssl_decrypt($tok['refresh_token'], 'AES-128-ECB', $this->key) : null;
            $this->expiresAt = strtotime($tok['expires_at']);
        }
    }

    private function checkTokenValidity() {
        if ($this->expiresAt < time()) {
            $this->refreshToken();
        }
    }

    public function refreshToken(): bool {
        if (!$this->refreshToken) {
            return false;
        }

        $clientId = \App\Models\SystemSetting::getVal('google_client_id');
        $clientSecret = \App\Models\SystemSetting::getVal('google_client_secret');

        $ch = curl_init("https://oauth2.googleapis.com/token");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $this->refreshToken,
            'grant_type' => 'refresh_token'
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $res = json_decode($response, true);
        if ($code !== 200 || empty($res['access_token'])) {
            return false;
        }

        $newRefresh = $res['refresh_token'] ?? $this->refreshToken;
        $expiresIn = $res['expires_in'] ?? 3600;

        $encryptedAccess = openssl_encrypt($res['access_token'], 'AES-128-ECB', $this->key);
        $encryptedRefresh = openssl_encrypt($newRefresh, 'AES-128-ECB', $this->key);
        $expiresAtStr = date('Y-m-d H:i:s', time() + $expiresIn);

        $stmt = $this->db->prepare("UPDATE oauth_tokens SET access_token = ?, refresh_token = ?, expires_at = ?, last_connected_at = NOW() WHERE provider = 'googledrive'");
        $stmt->execute([$encryptedAccess, $encryptedRefresh, $expiresAtStr]);

        $this->accessToken = $res['access_token'];
        $this->refreshToken = $newRefresh;
        $this->expiresAt = time() + $expiresIn;

        return true;
    }

    private function makeRequest(string $url, string $method = 'GET', array $headers = [], $body = null, int $attempt = 1) {
        $this->checkTokenValidity();

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        $defaultHeaders = [
            "Authorization: Bearer {$this->accessToken}",
            "User-Agent: AppFormCloudSync/1.0"
        ];
        $allHeaders = array_merge($defaultHeaders, $headers);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $allHeaders);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // 401 Unauthorized handling: refresh token and retry once
        if ($httpCode === 401 && $attempt === 1) {
            if ($this->refreshToken()) {
                return $this->makeRequest($url, $method, $headers, $body, 2);
            }
        }

        // Bounded retry with exponential backoff for temporary server errors
        if (($httpCode >= 500 || $httpCode === 429 || $curlError) && $attempt < 3) {
            usleep(pow(2, $attempt) * 500000); 
            return $this->makeRequest($url, $method, $headers, $body, $attempt + 1);
        }

        return [
            'code' => $httpCode,
            'body' => $response,
            'error' => $curlError
        ];
    }

    private function getOrCreateFolderId(): string {
        $q = "name = 'AppForm-Google-Backups' and mimeType = 'application/vnd.google-apps.folder' and trashed = false";
        $url = "https://www.googleapis.com/drive/v3/files?" . http_build_query([
            'q' => $q,
            'fields' => 'files(id)',
            'pageSize' => 1
        ]);

        $res = $this->makeRequest($url);
        if ($res['code'] !== 200) {
            throw new \Exception("Failed to search folder in Google Drive: " . $res['body']);
        }

        $data = json_decode($res['body'], true);
        if (!empty($data['files'][0]['id'])) {
            return $data['files'][0]['id'];
        }

        // Create folder
        $createUrl = "https://www.googleapis.com/drive/v3/files";
        $body = json_encode([
            'name' => 'AppForm-Google-Backups',
            'mimeType' => 'application/vnd.google-apps.folder'
        ]);
        $headers = ['Content-Type: application/json'];

        $createRes = $this->makeRequest($createUrl, 'POST', $headers, $body);
        if ($createRes['code'] !== 200 && $createRes['code'] !== 201) {
            throw new \Exception("Failed to create folder in Google Drive: " . $createRes['body']);
        }

        $createData = json_decode($createRes['body'], true);
        if (empty($createData['id'])) {
            throw new \Exception("Google Drive folder creation returned no ID.");
        }

        return $createData['id'];
    }

    public function startUpload(string $filePath, string $remoteName): array {
        $folderId = $this->getOrCreateFolderId();
        $totalBytes = filesize($filePath);

        $url = "https://www.googleapis.com/upload/drive/v3/files?uploadType=resumable";
        $headers = [
            "X-Upload-Content-Type: application/zip",
            "X-Upload-Content-Length: {$totalBytes}",
            "Content-Type: application/json"
        ];
        $body = json_encode([
            'name' => $remoteName,
            'parents' => [$folderId]
        ]);

        $this->checkTokenValidity();
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HEADER, true); 

        $defaultHeaders = [
            "Authorization: Bearer {$this->accessToken}",
            "User-Agent: AppFormCloudSync/1.0"
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($defaultHeaders, $headers));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        if ($httpCode === 401) {
            if ($this->refreshToken()) {
                return $this->startUpload($filePath, $remoteName);
            }
        }

        if ($httpCode !== 200 && $httpCode !== 201) {
            $body = substr($response, $headerSize);
            throw new \Exception("Failed to start Google Drive upload session: HTTP {$httpCode} - {$body}");
        }

        $headersText = substr($response, 0, $headerSize);
        preg_match('/^[Ll]ocation:\s*(.*)$/mi', $headersText, $matches);
        $sessionUrl = isset($matches[1]) ? trim($matches[1]) : null;

        if (!$sessionUrl) {
            throw new \Exception("Google Drive session Location header missing.");
        }

        return [
            'session_url' => $sessionUrl,
            'bytes_uploaded' => 0
        ];
    }

    public function uploadChunk(string $uploadSessionUrl, string $chunkData, int $rangeStart, int $rangeEnd, int $totalBytes): array {
        $this->checkTokenValidity();

        $ch = curl_init($uploadSessionUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');

        $contentLength = strlen($chunkData);
        $headers = [
            "Authorization: Bearer {$this->accessToken}",
            "Content-Length: {$contentLength}",
            "Content-Range: bytes {$rangeStart}-{$rangeEnd}/{$totalBytes}",
            "User-Agent: AppFormCloudSync/1.0"
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $chunkData);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 401) {
            if ($this->refreshToken()) {
                return $this->uploadChunk($uploadSessionUrl, $chunkData, $rangeStart, $rangeEnd, $totalBytes);
            }
        }

        if ($httpCode === 200 || $httpCode === 201) {
            $res = json_decode($response, true);
            return [
                'success' => true,
                'completed' => true,
                'remote_file_id' => $res['id'] ?? null
            ];
        }

        if ($httpCode === 308) {
            return [
                'success' => true,
                'completed' => false
            ];
        }

        throw new \Exception("Google Drive chunk upload failed with HTTP Code {$httpCode}: " . substr($response, 0, 500));
    }

    public function resumeUpload(string $uploadSessionUrl, int $totalBytes): int {
        $this->checkTokenValidity();
        $ch = curl_init($uploadSessionUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$this->accessToken}",
            "Content-Range: bytes */{$totalBytes}",
            "User-Agent: AppFormCloudSync/1.0"
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        if ($httpCode === 401) {
            if ($this->refreshToken()) {
                return $this->resumeUpload($uploadSessionUrl, $totalBytes);
            }
        }

        if ($httpCode === 308) {
            $headersText = substr($response, 0, $headerSize);
            preg_match('/^[Rr]ange:\s*bytes=\d+-(.*)$/mi', $headersText, $matches);
            if (isset($matches[1])) {
                return (int)trim($matches[1]) + 1;
            }
        }

        if ($httpCode === 200 || $httpCode === 201) {
            return $totalBytes;
        }

        throw new \Exception("Resumable upload session expired or invalid (HTTP {$httpCode}).");
    }

    public function verifyUpload(string $remoteFileId, string $localFilePath): bool {
        $url = "https://www.googleapis.com/drive/v3/files/{$remoteFileId}?fields=id,name,size,md5Checksum";
        $res = $this->makeRequest($url);

        if ($res['code'] !== 200) {
            return false;
        }

        $data = json_decode($res['body'], true);
        if (empty($data['id'])) {
            return false;
        }

        $localSize = filesize($localFilePath);
        if ((int)($data['size'] ?? 0) !== $localSize) {
            return false;
        }

        $localMd5 = md5_file($localFilePath);
        if (!empty($data['md5Checksum']) && strtolower($data['md5Checksum']) !== strtolower($localMd5)) {
            return false;
        }

        return true;
    }

    public function getQuota(): array {
        $url = "https://www.googleapis.com/drive/v3/about?fields=storageQuota";
        $res = $this->makeRequest($url);
        if ($res['code'] !== 200) {
            return ['used' => 0, 'total' => 15 * 1024 * 1024 * 1024];
        }
        $data = json_decode($res['body'], true);
        $used = (int)($data['storageQuota']['usage'] ?? 0);
        $total = (int)($data['storageQuota']['limit'] ?? 15 * 1024 * 1024 * 1024);
        return ['used' => $used, 'total' => $total];
    }

    public function deleteRemoteBackup(string $remoteFileId): bool {
        $url = "https://www.googleapis.com/drive/v3/files/{$remoteFileId}";
        $res = $this->makeRequest($url, 'DELETE');
        return ($res['code'] === 204);
    }

    public function listRemoteBackups(): array {
        $folderId = $this->getOrCreateFolderId();
        $q = "'{$folderId}' in parents and trashed = false";
        $url = "https://www.googleapis.com/drive/v3/files?" . http_build_query([
            'q' => $q,
            'fields' => 'files(id,name,size,createdTime)'
        ]);
        $res = $this->makeRequest($url);
        if ($res['code'] !== 200) {
            return [];
        }
        $data = json_decode($res['body'], true);
        return $data['files'] ?? [];
    }

    public function downloadForVerification(string $remoteFileId, string $localDestPath): bool {
        $url = "https://www.googleapis.com/drive/v3/files/{$remoteFileId}?alt=media";
        $this->checkTokenValidity();

        $ch = curl_init($url);
        $fp = fopen($localDestPath, 'w+b');
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$this->accessToken}",
            "User-Agent: AppFormCloudSync/1.0"
        ]);

        $success = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);

        if ($httpCode === 401 && $this->refreshToken()) {
            return $this->downloadForVerification($remoteFileId, $localDestPath);
        }

        return $success && ($httpCode === 200);
    }

    public function connect(string $code): array {
        return [];
    }

    public function disconnect() {
        return true;
    }
}
