<?php
namespace App\Services\Update;

use App\Core\Logger;

class GitHubReleaseProvider implements ReleaseProviderInterface {
    private $repo;
    private $token;
    private $userAgent = 'AppForm-Updater/1.0';

    public function __construct(string $repo = 'dvlachonatsios-dev/appform') {
        $this->repo = $repo;
        
        // Prioritize: 1. Environment variable GITHUB_TOKEN, 2. Local config variables
        $this->token = getenv('GITHUB_TOKEN');
        if (empty($this->token)) {
            $configPath = dirname(__DIR__) . '/../../config/config.local.php';
            if (file_exists($configPath)) {
                $localConfig = include $configPath;
                $this->token = $localConfig['updates']['github_token'] ?? null;
            }
        }
    }

    /**
     * Checks if a GitHub token is configured.
     */
    public function isConfigured(): bool {
        return !empty($this->token);
    }

    /**
     * Retrieves configured token configuration status (masked for logs/UI).
     */
    public function getTokenStatus(): string {
        return $this->isConfigured() ? 'configured' : 'not_configured';
    }

    public function getLatestCompatibleRelease(string $currentVersion, string $channel): ?array {
        $releases = $this->listCompatibleReleases($currentVersion, $channel);
        return count($releases) > 0 ? $releases[0] : null;
    }

    public function listCompatibleReleases(string $currentVersion, string $channel): array {
        $url = "https://api.github.com/repos/{$this->repo}/releases";
        $response = $this->makeRequest($url);
        
        if (!$response) {
            return [];
        }

        $releases = json_decode($response, true);
        if (!is_array($releases)) {
            return [];
        }

        $compatible = [];
        foreach ($releases as $release) {
            if (!empty($release['draft'])) {
                continue;
            }

            $isPrerelease = !empty($release['prerelease']);
            if ($isPrerelease && $channel === 'stable') {
                continue; // Skip prereleases on stable channel
            }

            $tag = $release['tag_name'] ?? '';
            $version = ltrim($tag, 'v');

            // Compare semantic versioning
            if (version_compare($version, $currentVersion, '<=')) {
                continue; // Skip older versions
            }

            // Locate required assets
            $assets = $release['assets'] ?? [];
            $updateZip = null;
            $checksumAsset = null;

            foreach ($assets as $asset) {
                $name = $asset['name'] ?? '';
                if ($name === "AppForm-{$version}-update.zip") {
                    $updateZip = $asset;
                } elseif ($name === "AppForm-{$version}-update.zip.sha256") {
                    $checksumAsset = $asset;
                }
            }

            if (!$updateZip || !$checksumAsset) {
                continue; // Must contain both update package zip and Sha256 checksum asset
            }

            $compatible[] = [
                'version' => $version,
                'tag' => $tag,
                'name' => $release['name'] ?? $tag,
                'changelog' => $release['body'] ?? '',
                'package_url' => $updateZip['url'],
                'package_name' => $updateZip['name'],
                'package_size' => $updateZip['size'],
                'checksum_url' => $checksumAsset['browser_download_url'] ?? $checksumAsset['url'],
                'created_at' => $release['created_at'] ?? null
            ];
        }

        // Sort descending by version number
        usort($compatible, function($a, $b) {
            return version_compare($b['version'], $a['version']);
        });

        return $compatible;
    }

    public function getReleaseMetadata(string $tag): ?array {
        $url = "https://api.github.com/repos/{$this->repo}/releases/tags/{$tag}";
        $response = $this->makeRequest($url);
        if (!$response) {
            return null;
        }
        return json_decode($response, true);
    }

    public function downloadAsset(string $assetUrl, string $outputPath): bool {
        $tempPath = $outputPath . '.tmp';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $assetUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5 minutes download timeout
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);

        $headers = [
            'Accept: application/octet-stream'
        ];
        if ($this->token) {
            $headers[] = "Authorization: token {$this->token}";
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Open local temporary file for streaming write
        $fp = fopen($tempPath, 'w+');
        if (!$fp) {
            curl_close($ch);
            return false;
        }
        curl_setopt($ch, CURLOPT_FILE, $fp);

        $success = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        fclose($fp);

        if (!$success || $httpCode !== 200) {
            $error = curl_error($ch);
            curl_close($ch);
            unlink($tempPath); // Clean up partial download
            
            // Mask token in log output
            $safeError = $this->redactToken($error);
            Logger::log("GitHub Download error: [HTTP {$httpCode}] {$safeError}");
            return false;
        }

        curl_close($ch);
        
        // Atomic finalize rename
        if (file_exists($outputPath)) {
            unlink($outputPath);
        }
        return rename($tempPath, $outputPath);
    }

    /**
     * Executes authorized GitHub API payload request with network boundaries.
     */
    private function makeRequest(string $url): ?string {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);

        $headers = [
            'Accept: application/vnd.github+json'
        ];
        if ($this->token) {
            $headers[] = "Authorization: token {$this->token}";
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            $safeError = $this->redactToken($error);
            Logger::log("GitHub API request failed: {$safeError}");
            return null;
        }

        curl_close($ch);

        if ($httpCode !== 200) {
            Logger::log("GitHub API returned HTTP Code: {$httpCode} for URL: {$url}");
            return null;
        }

        return $response;
    }

    /**
     * Replaces authorization token strings with a mask to protect privacy.
     */
    private function redactToken(string $message): string {
        if (empty($this->token)) {
            return $message;
        }
        return str_replace($this->token, '[REDACTED_GITHUB_TOKEN]', $message);
    }
}
