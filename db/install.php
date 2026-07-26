<?php
/**
 * AppForm Installer v1.0.2
 *
 * Usage:   php db/install.php
 */

$configPath = __DIR__ . '/../config/config.php';
if (!file_exists($configPath)) {
    die("ERROR: config.php not found. Copy config.example.php to config.php first.\n");
}

$config  = require $configPath;
$host    = $config['db']['host'];
$port    = $config['db']['port'];
$dbName  = $config['db']['name'];
$user    = $config['db']['user'];
$pass    = $config['db']['pass'];
$charset = $config['db']['charset'];

// ── Step 1: Create database if not exists ─────────────────────────────────────
echo "[1/4] Creating database if not exists: $dbName ...\n";
try {
    $pdoRoot = new PDO(
        "mysql:host=$host;port=$port;charset=$charset",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdoRoot->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET $charset COLLATE {$charset}_unicode_ci");
    echo "      OK\n";
} catch (PDOException $e) {
    die("ERROR: Cannot connect to MySQL server: " . $e->getMessage() . "\n");
}
unset($pdoRoot);

// ── Step 2: Connect to the target database ────────────────────────────────────
echo "[2/4] Connecting to $dbName ...\n";
try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbName;charset=$charset",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    echo "      Connected.\n";
} catch (PDOException $e) {
    die("ERROR: " . $e->getMessage() . "\n");
}

// ── Step 3: Apply schema.sql (base tables) ────────────────────────────────────
echo "[3/4] Applying base schema from schema.sql ...\n";
$schemaSqlPath = __DIR__ . '/schema.sql';
if (!file_exists($schemaSqlPath)) {
    die("ERROR: schema.sql not found at $schemaSqlPath\n");
}
$schemaSql = file_get_contents($schemaSqlPath);
$schemaQueries = parseSqlToQueries($schemaSql);
foreach ($schemaQueries as $query) {
    $query = trim($query);
    if ($query === '') continue;
    $pdo->exec($query);
}
echo "      Schema applied.\n";

// ── Step 4: Run all migrations ────────────────────────────────────────────────
echo "[4/4] Running migrations ...\n";

$pdo->exec("
    CREATE TABLE IF NOT EXISTS schema_migrations (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        migration  VARCHAR(255) NOT NULL UNIQUE,
        applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$applied = array_flip(
    $pdo->query("SELECT migration FROM schema_migrations")->fetchAll(PDO::FETCH_COLUMN)
);

$migrationsDir = __DIR__ . '/migrations';
$files = glob($migrationsDir . '/*.sql');
if ($files) {
    natsort($files);
    foreach ($files as $file) {
        $name = basename($file);
        if (isset($applied[$name])) {
            echo "      SKIP   $name\n";
            continue;
        }
        echo "      APPLY  $name ... ";
        $sql = file_get_contents($file);
        $queries = parseSqlToQueries($sql);
        try {
            foreach ($queries as $query) {
                $query = trim($query);
                if ($query === '') continue;
                $pdo->exec($query);
            }
            $pdo->prepare("INSERT INTO schema_migrations (migration) VALUES (?)")->execute([$name]);
            echo "OK\n";
        } catch (PDOException $e) {
            die("\nERROR in $name: " . $e->getMessage() . "\n");
        }
    }
} else {
    echo "      No migration files found.\n";
}

// ── Step 5: Seed default data (idempotent - INSERT IGNORE) ────────────────────
echo "\n[5/5] Seeding default data ...\n";

// Roles
$pdo->exec("INSERT IGNORE INTO roles (id, name, slug, description, is_system) VALUES
    (1, 'Administrator', 'administrator', 'Πλήρης πρόσβαση στο σύστημα', 1),
    (2, 'Manager', 'manager', 'Διαχείριση φορμών, υποβολών και στατιστικών', 1),
    (3, 'Reviewer', 'reviewer', 'Αξιολόγηση και έγκριση υποβολών', 1),
    (4, 'User', 'user', 'Πρόσβαση στο portal υποβολής φορμών', 1)");
echo "      Roles seeded.\n";

// Permissions
$permissionsPath = __DIR__ . '/../config/permissions.php';
if (file_exists($permissionsPath)) {
    $permissions = require $permissionsPath;
    $stmt = $pdo->prepare("INSERT IGNORE INTO permissions (name, slug, description) VALUES (?, ?, ?)");
    foreach ($permissions as $slug => $name) {
        $stmt->execute([$name, $slug, $name]);
    }
    echo "      Permissions seeded.\n";

    // Admin gets all permissions
    $allPerms = $pdo->query("SELECT id FROM permissions")->fetchAll(PDO::FETCH_COLUMN);
    $rpStmt   = $pdo->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (1, ?)");
    foreach ($allPerms as $pid) {
        $rpStmt->execute([$pid]);
    }

    // Manager permissions
    $managerPerms = ['dashboard.view', 'forms.view', 'submissions.view.all', 'submissions.review', 'analytics.view', 'exports.create'];
    foreach ($managerPerms as $slug) {
        $r = $pdo->query("SELECT id FROM permissions WHERE slug = " . $pdo->quote($slug))->fetch();
        if ($r) $pdo->exec("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (2, {$r['id']})");
    }

    // Reviewer permissions
    $reviewerPerms = ['dashboard.view', 'forms.view', 'submissions.view.all', 'submissions.review'];
    foreach ($reviewerPerms as $slug) {
        $r = $pdo->query("SELECT id FROM permissions WHERE slug = " . $pdo->quote($slug))->fetch();
        if ($r) $pdo->exec("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (3, {$r['id']})");
    }

    // User permissions
    $userPerms = ['dashboard.view', 'forms.view', 'forms.submit', 'submissions.view.own'];
    foreach ($userPerms as $slug) {
        $r = $pdo->query("SELECT id FROM permissions WHERE slug = " . $pdo->quote($slug))->fetch();
        if ($r) $pdo->exec("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (4, {$r['id']})");
    }
    echo "      Role-permissions seeded.\n";
}

// Default admin user (only if no users exist)
$userCount = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
if ($userCount === 0) {
    echo "      Creating default admin account ...\n";
    $adminPass = password_hash('ChangeMe!2025', PASSWORD_BCRYPT);
    $pdo->prepare("INSERT INTO users (id, username, email, password_hash, full_name, role_id, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)")
        ->execute([1, 'admin', 'admin@appform.local', $adminPass, 'Administrator', 1]);
    echo "      Admin user created (password: ChangeMe!2025 — CHANGE THIS IMMEDIATELY)\n";
} else {
    echo "      Users already exist — skipping admin seed.\n";
}

// System settings
$pdo->exec("INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_type, is_public) VALUES
    ('app_name', 'AppForm Portal', 'string', 1),
    ('default_locale', 'el', 'string', 1),
    ('records_per_page', '15', 'integer', 1),
    ('csv_delimiter', ';', 'string', 0),
    ('upload_max_filesize_mb', '10', 'integer', 1),
    ('maintenance_mode', '0', 'boolean', 0)");
echo "      System settings seeded.\n";

echo "\n============================================================\n";
echo " AppForm Installation Complete!\n";
echo "============================================================\n";

/**
 * Parses SQL file content into individual queries, handling DELIMITER directives properly.
 */
function parseSqlToQueries(string $sql): array {
    $sql = preg_replace('/--[^\n]*\n?/', "\n", $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
    $lines = explode("\n", $sql);
    $queries = [];
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
            $queries[] = $queryToAppend;
            $currentQuery = '';
        }
    }

    $remainingQuery = trim($currentQuery);
    if ($remainingQuery !== '') {
        $queries[] = $remainingQuery;
    }

    return $queries;
}
