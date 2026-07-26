<?php
namespace App\Services;

class VersionService {
    private static $cachedVersion = null;

    /**
     * Loads, validates and returns version metadata.
     * Enforces semantic version patterns and falls back safely on errors.
     */
    public static function getVersionData(): array {
        if (self::$cachedVersion !== null) {
            return self::$cachedVersion;
        }

        $default = [
            'version' => '1.0.0',
            'build' => 1,
            'channel' => 'stable'
        ];

        $versionFile = dirname(__DIR__) . '/../config/version.php';

        if (!file_exists($versionFile)) {
            self::$cachedVersion = $default;
            return $default;
        }

        try {
            $data = include $versionFile;
            if (!is_array($data)) {
                self::$cachedVersion = $default;
                return $default;
            }

            $version = $data['version'] ?? '1.0.0';
            $build = $data['build'] ?? 1;
            $channel = $data['channel'] ?? 'stable';

            // 1. Semantic Version Validation (e.g. 1.0.0 or 1.0.0-rc1)
            if (!preg_match('/^\d+\.\d+\.\d+(-[a-zA-Z0-9.]+)?$/', $version)) {
                $version = '1.0.0';
            }

            // 2. Build is int
            if (!is_int($build)) {
                $build = (int)$build;
                if ($build <= 0) {
                    $build = 1;
                }
            }

            // 3. Channel validation
            $allowedChannels = ['stable', 'beta', 'rc', 'dev'];
            if (!in_array($channel, $allowedChannels)) {
                $channel = 'stable';
            }

            self::$cachedVersion = [
                'version' => $version,
                'build' => $build,
                'channel' => $channel
            ];
        } catch (\Throwable $t) {
            self::$cachedVersion = $default;
        }

        return self::$cachedVersion;
    }

    /**
     * Returns semantic version string.
     */
    public static function getVersionString(): string {
        $data = self::getVersionData();
        return $data['version'];
    }
}
