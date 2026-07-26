<?php
namespace App\Services\Update;

use App\Core\Logger;

/**
 * LocalReleaseProvider — για local acceptance testing μόνο.
 * Σερβίρει το τοπικό update package χωρίς GitHub API.
 * 
 * ΧΡΗΣΗ: Μόνο για testing. Στην παραγωγή χρησιμοποιείται GitHubReleaseProvider.
 */
class LocalReleaseProvider implements ReleaseProviderInterface {
    private string $packageDir;

    public function __construct(string $packageDir = '') {
        if ($packageDir) {
            $this->packageDir = realpath($packageDir) ?: $packageDir;
        } else {
            // Navigate from src/Services/Update/ → src/Services/ → src/ → project_root/ → release/
            // Using dirname() three times avoids the ../ chain that glob cannot resolve on Windows
            $projectRoot = dirname(dirname(dirname(__DIR__)));
            $resolved = $projectRoot . DIRECTORY_SEPARATOR . 'release';
            $this->packageDir = realpath($resolved) ?: $resolved;
        }
    }


    public function isConfigured(): bool {
        return is_dir($this->packageDir);
    }

    public function getTokenStatus(): string {
        return 'local_test';
    }

    public function getLatestCompatibleRelease(string $currentVersion, string $channel): ?array {
        $releases = $this->listCompatibleReleases($currentVersion, $channel);
        return count($releases) > 0 ? $releases[0] : null;
    }

    public function listCompatibleReleases(string $currentVersion, string $channel): array {
        $compatible = [];
        $dir = $this->packageDir;

        // Scan for update ZIPs: AppForm-X.Y.Z-update.zip
        $pattern = $dir . '/AppForm-*-update.zip';
        foreach (glob($pattern) as $zipPath) {
            $filename = basename($zipPath);
            // Extract version from filename
            if (!preg_match('/^AppForm-(\d+\.\d+\.\d+)-update\.zip$/', $filename, $m)) {
                continue;
            }
            $version = $m[1];

            // Must be newer than installed
            if (version_compare($version, $currentVersion, '<=')) {
                continue;
            }

            // Companion SHA-256
            $sha256Path = $zipPath . '.sha256';
            if (!file_exists($sha256Path)) {
                Logger::log("[LocalReleaseProvider] Missing SHA-256 for: {$filename}");
                continue;
            }

            $sha256 = trim(file_get_contents($sha256Path));

            $compatible[] = [
                'version'      => $version,
                'tag'          => "v{$version}",
                'name'         => "AppForm v{$version} (local test)",
                'changelog'    => "Local test package for v{$version}",
                'package_url'  => 'local://' . $zipPath,  // special local:// scheme
                'package_name' => $filename,
                'package_size' => filesize($zipPath),
                'checksum_url' => 'local://' . $sha256Path,
                'sha256'       => $sha256,
                'local_path'   => $zipPath,
                'created_at'   => date('c', filemtime($zipPath)),
            ];
        }

        usort($compatible, fn($a, $b) => version_compare($b['version'], $a['version']));
        return $compatible;
    }

    public function getReleaseMetadata(string $tag): ?array {
        return null;
    }

    /**
     * For local packages, just copy the file to destination.
     */
    public function downloadAsset(string $assetUrl, string $outputPath): bool {
        // Parse the local:// scheme
        if (str_starts_with($assetUrl, 'local://')) {
            $sourcePath = substr($assetUrl, 8);
            if (!file_exists($sourcePath)) {
                Logger::log("[LocalReleaseProvider] Source file not found: {$sourcePath}");
                return false;
            }
            Logger::log("[LocalReleaseProvider] Copying local package: {$sourcePath} → {$outputPath}");
            return copy($sourcePath, $outputPath);
        }
        return false;
    }
}
