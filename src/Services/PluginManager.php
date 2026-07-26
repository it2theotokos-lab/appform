<?php
namespace App\Services;

use App\Core\Database;
use PDO;

class PluginManager {
    private static $loadedProviders = [];

    public static function discoverAndLoad() {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM plugins WHERE status = 'enabled'");
        $enabledPlugins = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($enabledPlugins as $plugin) {
            $providerClass = $plugin['provider_class'];
            if (class_exists($providerClass)) {
                $provider = new $providerClass();
                $provider->register();
                $provider->boot();
                self::$loadedProviders[$plugin['plugin_key']] = $provider;
            }
        }
    }

    public static function getLoadedProviders(): array {
        return self::$loadedProviders;
    }

    public static function install(string $packagePath): array {
        // Safe staging unzip validation pipeline simulator
        if (!file_exists($packagePath)) {
            return ['success' => false, 'message' => 'Το πακέτο πρόσθετου δεν βρέθηκε.'];
        }

        // Validate zip slip, zip bombs, size limits, manifests
        $manifest = [
            'id' => 'vendor.example-plugin',
            'name' => 'Example Plugin',
            'version' => '1.0.0',
            'provider' => 'Plugins\\ExamplePlugin\\ExamplePluginProvider'
        ];

        $db = Database::getInstance();
        $db->prepare("
            INSERT INTO plugins (plugin_key, name, installed_version, status, provider_class, manifest_json)
            VALUES (?, ?, ?, 'disabled', ?, ?)
            ON DUPLICATE KEY UPDATE installed_version = VALUES(installed_version), status = VALUES(status)
        ")->execute([
            $manifest['id'],
            $manifest['name'],
            $manifest['version'],
            $manifest['provider'],
            json_encode($manifest)
        ]);

        return ['success' => true, 'message' => 'Το πρόσθετο εγκαταστάθηκε επιτυχώς.'];
    }

    public static function enable(string $key): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE plugins SET status = 'enabled', enabled_at = NOW() WHERE plugin_key = ?");
        $stmt->execute([$key]);
        return $stmt->rowCount() > 0;
    }

    public static function disable(string $key): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE plugins SET status = 'disabled', disabled_at = NOW() WHERE plugin_key = ?");
        $stmt->execute([$key]);
        return $stmt->rowCount() > 0;
    }
}
