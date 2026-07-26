<?php
/**
 * AppForm Migration Runner v1.0.2
 *
 * Usage:   php db/migrate.php
 */

$configPath = __DIR__ . '/../config/config.php';
if (!file_exists($configPath)) {
    fwrite(STDERR, "ERROR: config.php not found. Copy config.example.php to config.php first.\n");
    exit(1);
}

$config  = require $configPath;
$host    = $config['db']['host'];
$port    = $config['db']['port'];
$dbName  = $config['db']['name'];
$user    = $config['db']['user'];
$pass    = $config['db']['pass'];
$charset = $config['db']['charset'];

// Connect via PDO
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
} catch (PDOException $e) {
    fwrite(STDERR, "ERROR: Cannot connect to database: " . $e->getMessage() . "\n");
    exit(1);
}

echo "Connected to database: $dbName\n";

// Create schema_migrations table
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS schema_migrations (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            migration  VARCHAR(255) NOT NULL UNIQUE,
            applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (PDOException $e) {
    fwrite(STDERR, "ERROR: Cannot create schema_migrations: " . $e->getMessage() . "\n");
    exit(1);
}

// Load already-applied migrations
$applied = array_flip(
    $pdo->query("SELECT migration FROM schema_migrations")->fetchAll(PDO::FETCH_COLUMN)
);

// Discover migration files
$migrationsDir = __DIR__ . '/migrations';
$files = glob($migrationsDir . '/*.sql');
if (!$files) {
    echo "No migration files found in $migrationsDir — nothing to do.\n";
    exit(0);
}
natsort($files);
$files = array_values($files);

echo "Found " . count($files) . " migration file(s).\n";
echo str_repeat('-', 60) . "\n";

$executed = 0;
$skipped  = 0;

foreach ($files as $file) {
    $name = basename($file);

    if (isset($applied[$name])) {
        echo "  SKIP   $name\n";
        $skipped++;
        continue;
    }

    echo "  APPLY  $name ... ";
    flush();

    $sql = file_get_contents($file);
    if ($sql === false) {
        fwrite(STDERR, "\nERROR: Cannot read file: $file\n");
        exit(1);
    }

    // Split SQL by custom delimiters to handle stored procedures correctly without crashing multi_query
    $queries = parseSqlToIndividualQueries($sql);

    try {
        foreach ($queries as $query) {
            $query = trim($query);
            if ($query === '') {
                continue;
            }
            $pdo->exec($query);
        }

        // Record the migration
        $pdo->prepare("INSERT INTO schema_migrations (migration) VALUES (?)")->execute([$name]);
        echo "OK\n";
        $executed++;
    } catch (PDOException $e) {
        fwrite(STDERR, "\nERROR in $name: " . $e->getMessage() . "\n");
        exit(1);
    }
}

echo str_repeat('-', 60) . "\n";
echo "Done. Applied: $executed  Skipped: $skipped\n";
exit(0);

/**
 * Parses SQL file content into individual queries, handling DELIMITER directives properly.
 */
function parseSqlToIndividualQueries(string $sql): array {
    // Strip comments
    $sql = preg_replace('/--[^\n]*\n?/', "\n", $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

    $lines = explode("\n", $sql);
    $queries = [];
    $currentQuery = '';
    $delimiter = ';';

    foreach ($lines as $line) {
        $trimmedLine = trim($line);
        if ($trimmedLine === '') {
            continue;
        }

        // Check if the line changes the delimiter (e.g., DELIMITER $$)
        if (stripos($trimmedLine, 'DELIMITER') === 0) {
            $parts = preg_split('/\s+/', $trimmedLine);
            if (isset($parts[1])) {
                $delimiter = $parts[1];
            }
            continue;
        }

        $currentQuery .= $line . "\n";

        // Check if the query ends with the current delimiter
        if (str_ends_with(rtrim($trimmedLine), $delimiter)) {
            // Strip the delimiter from the end of the query
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
