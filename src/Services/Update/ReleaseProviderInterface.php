<?php
namespace App\Services\Update;

interface ReleaseProviderInterface {
    /**
     * Checks for the latest compatible release version on the target provider.
     */
    public function getLatestCompatibleRelease(string $currentVersion, string $channel): ?array;

    /**
     * Lists all compatible releases for the target channel.
     */
    public function listCompatibleReleases(string $currentVersion, string $channel): array;

    /**
     * Fetches metadata details for a specific release tag.
     */
    public function getReleaseMetadata(string $tag): ?array;

    /**
     * Downloads a remote release asset to a local path.
     */
    public function downloadAsset(string $assetUrl, string $outputPath): bool;
}
