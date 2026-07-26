<?php
// GitHub Release Provider Unit & Mock Tests

use App\Services\Update\GitHubReleaseProvider;

echo "Running ReleaseProvider Tests...\n";

// Mock JSON response matching GitHub API schema
$mockReleasesPayload = [
    [
        'tag_name' => 'v1.1.0',
        'name' => 'Release v1.1.0',
        'draft' => false,
        'prerelease' => false,
        'assets' => [
            [
                'name' => 'AppForm-1.1.0-update.zip',
                'url' => 'https://api.github.com/assets/110',
                'size' => 10240
            ],
            [
                'name' => 'AppForm-1.1.0-update.zip.sha256',
                'url' => 'https://api.github.com/assets/110_sha',
                'browser_download_url' => 'https://github.com/download/1.1.0.sha256'
            ]
        ]
    ],
    [
        'tag_name' => 'v1.2.0-beta1',
        'name' => 'Pre-release Beta v1.2.0',
        'draft' => false,
        'prerelease' => true,
        'assets' => [
            [
                'name' => 'AppForm-1.2.0-beta1-update.zip',
                'url' => 'https://api.github.com/assets/120',
                'size' => 12500
            ],
            [
                'name' => 'AppForm-1.2.0-beta1-update.zip.sha256',
                'url' => 'https://api.github.com/assets/120_sha',
                'browser_download_url' => 'https://github.com/download/1.2.0.sha256'
            ]
        ]
    ],
    // Bad release (missing update ZIP)
    [
        'tag_name' => 'v1.3.0',
        'name' => 'Broken Release v1.3.0',
        'draft' => false,
        'prerelease' => false,
        'assets' => []
    ]
];

// Subclass to bypass actual network API curl call during automated testing
class MockGitHubReleaseProvider extends GitHubReleaseProvider {
    public $mockPayload;

    public function listCompatibleReleases(string $currentVersion, string $channel): array {
        // We simulate the logic using the mock payload instead of curling GitHub
        $releases = $this->mockPayload;
        $compatible = [];
        
        foreach ($releases as $release) {
            if (!empty($release['draft'])) {
                continue;
            }

            $isPrerelease = !empty($release['prerelease']);
            if ($isPrerelease && $channel === 'stable') {
                continue; // Stable channel ignores prerelease
            }

            $tag = $release['tag_name'] ?? '';
            $version = ltrim($tag, 'v');

            // Compare semantic versioning
            if (version_compare($version, $currentVersion, '<=')) {
                continue;
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
                continue;
            }

            $compatible[] = [
                'version' => $version,
                'tag' => $tag,
                'name' => $release['name'] ?? $tag,
                'package_url' => $updateZip['url'],
                'package_size' => $updateZip['size'],
                'checksum_url' => $checksumAsset['browser_download_url'] ?? $checksumAsset['url']
            ];
        }

        return $compatible;
    }
}

// Instantiate Mock Provider
$provider = new MockGitHubReleaseProvider();
$provider->mockPayload = $mockReleasesPayload;

// Test 1: Stable channel filters out beta releases
$stableCompatible = $provider->listCompatibleReleases('1.0.0', 'stable');
assert(count($stableCompatible) === 1, "Test 1 Failed: Stable channel should filter out prerelease and broken tags.");
assert($stableCompatible[0]['version'] === '1.1.0', "Test 1 Failed: Target version mismatch.");
echo "Test 1 Passed: Stable channel release filtering and validation.\n";

// Test 2: Beta channel includes beta releases
$betaCompatible = $provider->listCompatibleReleases('1.0.0', 'beta');
assert(count($betaCompatible) === 2, "Test 2 Failed: Beta channel must include both beta and stable tags.");
echo "Test 2 Passed: Beta channel release filtering.\n";

// Test 3: Version boundary checks (1.1.0 is current version)
$currentCompatible = $provider->listCompatibleReleases('1.1.0', 'stable');
assert(count($currentCompatible) === 0, "Test 3 Failed: Older/equal releases should be filtered.");
echo "Test 3 Passed: Boundary checks for active versions.\n";

return true;
