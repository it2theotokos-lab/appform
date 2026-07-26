<?php
namespace App\Services;

use PDO;
use PDOException;

class InstallationService {
    private static $lockFile = __DIR__ . '/../../public/storage/install.lock';
    private static $installedLock = __DIR__ . '/../../config/installed.lock';
    private static $configPath = __DIR__ . '/../../config/config.local.php';

    public static function isInstalled(): bool {
        if (!file_exists(self::$installedLock) || !file_exists(self::$configPath)) {
            return false;
        }
        $content = file_get_contents(self::$installedLock);
        $data = json_decode($content, true);
        return isset($data['installed']) && $data['installed'] === true;
    }

    public static function acquireLock(): bool {
        $fp = @fopen(self::$lockFile, 'c+');
        if (!$fp) {
            return false;
        }
        if (!flock($fp, LOCK_EX | LOCK_NB)) {
            fclose($fp);
            return false;
        }
        // Save resource in static property to hold it during script execution
        self::$lockResource = $fp;
        return true;
    }

    private static $lockResource = null;

    public static function releaseLock() {
        if (self::$lockResource) {
            flock(self::$lockResource, LOCK_UN);
            fclose(self::$lockResource);
            @unlink(self::$lockFile);
            self::$lockResource = null;
        }
    }

    public static function checkRequirements(): array {
        $reqs = [
            'php_version' => [
                'name' => 'PHP Version',
                'required' => '8.0.0',
                'detected' => PHP_VERSION,
                'pass' => version_compare(PHP_VERSION, '8.0.0', '>=')
            ],
            'ext_pdo' => [
                'name' => 'PDO Extension',
                'required' => 'Enabled',
                'detected' => extension_loaded('pdo') ? 'Enabled' : 'Disabled',
                'pass' => extension_loaded('pdo')
            ],
            'ext_json' => [
                'name' => 'JSON Extension',
                'required' => 'Enabled',
                'detected' => extension_loaded('json') ? 'Enabled' : 'Disabled',
                'pass' => extension_loaded('json')
            ],
            'ext_mbstring' => [
                'name' => 'mbstring Extension',
                'required' => 'Enabled',
                'detected' => extension_loaded('mbstring') ? 'Enabled' : 'Disabled',
                'pass' => extension_loaded('mbstring')
            ],
            'ext_openssl' => [
                'name' => 'OpenSSL Extension',
                'required' => 'Enabled',
                'detected' => extension_loaded('openssl') ? 'Enabled' : 'Disabled',
                'pass' => extension_loaded('openssl')
            ],
            'ext_fileinfo' => [
                'name' => 'fileinfo Extension',
                'required' => 'Enabled',
                'detected' => extension_loaded('fileinfo') ? 'Enabled' : 'Disabled',
                'pass' => extension_loaded('fileinfo')
            ],
            'dir_config_writable' => [
                'name' => 'Config Directory Writable',
                'required' => 'Writable',
                'detected' => self::isWritableDirectory(__DIR__ . '/../../config') ? 'Writable' : 'Unwritable',
                'pass' => self::isWritableDirectory(__DIR__ . '/../../config')
            ],
            'dir_storage_writable' => [
                'name' => 'Storage Directory Writable',
                'required' => 'Writable',
                'detected' => self::isWritableDirectory(__DIR__ . '/../../public/storage') ? 'Writable' : 'Unwritable',
                'pass' => self::isWritableDirectory(__DIR__ . '/../../public/storage')
            ]
        ];

        return $reqs;
    }

    public static function testDbConnection(array $db): array {
        $dsn = "mysql:host={$db['host']};port={$db['port']};charset=utf8mb4";
        try {
            $pdo = new PDO($dsn, $db['user'], $db['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5
            ]);
            
            // Check character set / collation support
            $stmt = $pdo->query("SHOW CHARACTER SET LIKE 'utf8mb4'");
            if (!$stmt->fetch()) {
                return ['success' => false, 'message' => 'Το σύστημα βάσης δεδομένων δεν υποστηρίζει utf8mb4.'];
            }

            return ['success' => true, 'message' => 'Η σύνδεση με τη βάση δεδομένων ήταν επιτυχής!'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function runInstallation(array $configData, array $adminData): array {
        if (self::isInstalled()) {
            return ['success' => false, 'message' => 'Η εφαρμογή είναι ήδη εγκατεστημένη.'];
        }

        if (!self::acquireLock()) {
            return ['success' => false, 'message' => 'Μια άλλη εγκατάσταση βρίσκεται ήδη σε εξέλιξη.'];
        }

        $dbConfig = $configData['db'];
        $dsnBase = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};charset=utf8mb4";
        
        try {
            $pdo = new PDO($dsnBase, $dbConfig['user'], $dbConfig['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);

            // 1. Create DB if not exists
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbConfig['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$dbConfig['name']}`");

            // Check if DB is empty
            $stmt = $pdo->query("SHOW TABLES");
            if ($stmt->fetch()) {
                self::releaseLock();
                return ['success' => false, 'message' => 'Η βάση δεδομένων δεν είναι άδεια. Παρακαλώ χρησιμοποιήστε μια νέα βάση.'];
            }



            // 2. Load schema.sql
            $schemaSqlPath = __DIR__ . '/../../db/schema.sql';
            if (!file_exists($schemaSqlPath)) {
                throw new \Exception("schema.sql not found");
            }
            $schemaSql = file_get_contents($schemaSqlPath);
            self::executeSqlQueries($pdo, $schemaSql);

            // 3. Setup schema_migrations table and run migrations
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS schema_migrations (
                    id         INT AUTO_INCREMENT PRIMARY KEY,
                    migration  VARCHAR(255) NOT NULL UNIQUE,
                    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $migrationsDir = __DIR__ . '/../../db/migrations';
            $files = glob($migrationsDir . '/*.sql');
            if ($files) {
                natsort($files);
                foreach ($files as $file) {
                    $name = basename($file);
                    $sql = file_get_contents($file);
                    self::executeSqlQueries($pdo, $sql);
                    $pdo->prepare("INSERT INTO schema_migrations (migration) VALUES (?)")->execute([$name]);
                }
            }

            // Start database transaction for data inserts
            $pdo->beginTransaction();

            // 4. Seed basic roles
            $pdo->exec("INSERT INTO roles (id, name, slug, description, is_system) VALUES
                (1, 'Administrator', 'administrator', 'Πλήρης πρόσβαση στο σύστημα', 1),
                (2, 'Manager', 'manager', 'Διαχείριση φορμών, υποβολών και στατιστικών', 1),
                (3, 'Reviewer', 'reviewer', 'Αξιολόγηση και έγκριση υποβολών', 1),
                (4, 'User', 'user', 'Πρόσβαση στο portal υποβολής φορμών', 1)");

            // 5. Seed Permissions
            $permissionsPath = __DIR__ . '/../../config/permissions.php';
            if (file_exists($permissionsPath)) {
                $permissions = require $permissionsPath;
                $stmt = $pdo->prepare("INSERT INTO permissions (name, slug, description) VALUES (?, ?, ?)");
                foreach ($permissions as $slug => $name) {
                    $stmt->execute([$name, $slug, $name]);
                }

                // Map all permissions to admin
                $allPerms = $pdo->query("SELECT id FROM permissions")->fetchAll(PDO::FETCH_COLUMN);
                $rpStmt = $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (1, ?)");
                foreach ($allPerms as $pid) {
                    $rpStmt->execute([$pid]);
                }
            }

            // 6. Create Administrator user
            $adminPassHash = password_hash($adminData['password'], PASSWORD_BCRYPT);
            $stmtUser = $pdo->prepare("
                INSERT INTO users (username, email, password_hash, full_name, role_id, is_active)
                VALUES (?, ?, ?, ?, 1, 1)
            ");
            $stmtUser->execute([
                $adminData['username'],
                $adminData['email'],
                $adminPassHash,
                $adminData['full_name']
            ]);

            // 7. Seed defaults system settings
            $pdo->exec("INSERT INTO system_settings (setting_key, setting_value, setting_type, is_public) VALUES
                ('app_name', " . $pdo->quote($configData['app']['name']) . ", 'string', 1),
                ('default_locale', 'el', 'string', 1),
                ('records_per_page', '15', 'integer', 1),
                ('csv_delimiter', ';', 'string', 0),
                ('upload_max_filesize_mb', '10', 'integer', 1),
                ('maintenance_mode', '0', 'boolean', 0)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_type = VALUES(setting_type), is_public = VALUES(is_public)");

            // Commit database transaction
            $pdo->commit();

            // 8. Generate and write configuration
            $appKey = 'base64:' . base64_encode(random_bytes(32));
            $configContent = "<?php\nreturn [\n";
            $configContent .= "    'app' => [\n";
            $configContent .= "        'name' => " . var_export($configData['app']['name'], true) . ",\n";
            $configContent .= "        'url' => " . var_export($configData['app']['url'], true) . ",\n";
            $configContent .= "        'env' => " . var_export($configData['app']['env'], true) . ",\n";
            $configContent .= "        'debug' => false,\n";
            $configContent .= "        'app_key' => " . var_export($appKey, true) . ",\n";
            $configContent .= "    ],\n";
            $configContent .= "    'db' => [\n";
            $configContent .= "        'host' => " . var_export($dbConfig['host'], true) . ",\n";
            $configContent .= "        'port' => " . var_export($dbConfig['port'], true) . ",\n";
            $configContent .= "        'name' => " . var_export($dbConfig['name'], true) . ",\n";
            $configContent .= "        'user' => " . var_export($dbConfig['user'], true) . ",\n";
            $configContent .= "        'pass' => " . var_export($dbConfig['pass'], true) . ",\n";
            $configContent .= "        'charset' => 'utf8mb4',\n";
            $configContent .= "    ],\n";
            $configContent .= "    'session' => [\n";
            $configContent .= "        'lifetime' => 28800,\n";
            $configContent .= "        'idle_timeout' => 1800,\n";
            $configContent .= "        'cookie_secure' => false,\n";
            $configContent .= "        'cookie_httponly' => true,\n";
            $configContent .= "        'cookie_samesite' => 'Lax',\n";
            $configContent .= "    ],\n";
            $configContent .= "    'storage' => [\n";
            $configContent .= "        'logs' => __DIR__ . '/../storage/logs/app.log',\n";
            $configContent .= "        'private_uploads' => __DIR__ . '/../storage/private_uploads',\n";
            $configContent .= "        'exports' => __DIR__ . '/../storage/exports',\n";
            $configContent .= "    ]\n";
            $configContent .= "];\n";

            file_put_contents(self::$configPath, $configContent);

            // 9. Write permanent installed lock file
            $lockData = [
                'installed' => true,
                'timestamp' => date('Y-m-d H:i:s'),
                'version' => '1.0.0-rc1'
            ];
            file_put_contents(self::$installedLock, json_encode($lockData, JSON_PRETTY_PRINT));

            self::releaseLock();
            return ['success' => true];
        } catch (\Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            @unlink(self::$configPath);
            self::releaseLock();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private static function executeSqlQueries(PDO $pdo, string $sql) {
        $sql = preg_replace('/--[^\n]*\n?/', "\n", $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        $lines = explode("\n", $sql);
        $currentQuery = '';
        $delimiter = ';';

        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            if ($trimmedLine === '') continue;

            if (stripos($trimmedLine, 'DELIMITER') === 0) {
                $parts = preg_split('/\s+/', $trimmedLine);
                if (isset($parts[1])) {
                    $delimiter = $parts[1];
                }
                continue;
            }

            $currentQuery .= $line . "\n";

            if (str_ends_with(rtrim($trimmedLine), $delimiter)) {
                $queryToAppend = rtrim($currentQuery);
                if (str_ends_with($queryToAppend, $delimiter)) {
                    $queryToAppend = substr($queryToAppend, 0, -strlen($delimiter));
                }
                $pdo->exec($queryToAppend);
                $currentQuery = '';
            }
        }
    }

    /**
     * Checks if a directory is writable by attempting to write a temporary file.
     */
    public static function isWritableDirectory(string $path): bool {
        if (!is_dir($path)) {
            return false;
        }
        $tempFile = $path . '/' . uniqid('test_write_', true) . '.tmp';
        $fp = @fopen($tempFile, 'w');
        if ($fp === false) {
            return false;
        }
        fclose($fp);
        @unlink($tempFile);
        return true;
    }
}
