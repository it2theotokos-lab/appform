<?php
/**
 * AppForm Production Release Builder
 */

// Load Version settings
$versionFile = __DIR__ . '/../../config/version.php';
if (!file_exists($versionFile)) {
    die("ERROR: Version configuration file config/version.php is missing.\n");
}
$verConfig = require $versionFile;
$version = $verConfig['version'] ?? '1.0.0';
$buildNumber = $verConfig['build'] ?? 1;
$channel = $verConfig['channel'] ?? 'stable';

// Read config.json settings
$releaseConfigPath = __DIR__ . '/config.json';
if (!file_exists($releaseConfigPath)) {
    die("ERROR: Release config file tools/release/config.json is missing.\n");
}
$releaseConfig = json_decode(file_get_contents($releaseConfigPath), true);
$outputDir = __DIR__ . '/../../' . ($releaseConfig['outputDirectory'] ?? 'release');

if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

echo "=========================================\n";
echo "    AppForm Production Release Builder   \n";
echo "=========================================\n";
echo "Target Version: {$version} (Build: {$buildNumber})\n";
echo "Channel       : {$channel}\n";
echo "Output Path   : {$outputDir}\n";
echo "-----------------------------------------\n";

// 1. Audit Git Repository State
echo "Auditing Git repository state... ";
$allowDirty = in_array('--allow-dirty', $argv, true);
$gitStatus = shell_exec('git status --porcelain');
if (!empty(trim($gitStatus)) && !$allowDirty) {
    die("FAILED: Git working tree is dirty. Commit all changes first (or use --allow-dirty for testing).\n");
}

$currentBranch = trim(shell_exec('git rev-parse --abbrev-ref HEAD'));
if ($currentBranch !== 'main') {
    die("FAILED: Not on branch 'main'. Active branch is '{$currentBranch}'.\n");
}

$commitHash = trim(shell_exec('git rev-parse HEAD'));
echo "OK (Commit: " . substr($commitHash, 0, 7) . ")\n";

// 2. Build Paths and Exclusions
$rootPath = realpath(__DIR__ . '/../../');
$fullZipName = "AppForm-{$version}.zip";
$fullZipPath = "{$outputDir}/{$fullZipName}";

$updateZipName = "AppForm-{$version}-update.zip";
$updateZipPath = "{$outputDir}/{$updateZipName}";

$exclusions = [
    'config/config.local.php',
    'config/installed.lock',
    '.env',
    '.git',
    'appform.db',
    'release',
    'storage'
];

$runtimeDirs = [
    'public/storage/document_final_pdfs',
    'public/storage/document_signatures',
    'public/storage/logs'
];

// 3. Build Full Clean-Install ZIP
echo "Building Full Clean-Install package ({$fullZipName})... ";
$zipFull = new ZipArchive();
if ($zipFull->open($fullZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    die("FAILED: Cannot open ZIP archive for output.\n");
}

// Add empty runtime directories with .gitkeep first
foreach ($runtimeDirs as $dir) {
    $zipFull->addFromString($dir . '/.gitkeep', '');
}

$allFiles = getDirectoryFiles($rootPath);
foreach ($allFiles as $file) {
    $relative = str_replace($rootPath . DIRECTORY_SEPARATOR, '', $file);
    $relative = str_replace('\\', '/', $relative);

    // Filter exclusions
    $isExcluded = false;
    foreach ($exclusions as $exclude) {
        if ($relative === $exclude || str_starts_with($relative, $exclude . '/')) {
            $isExcluded = true;
            break;
        }
    }

    // Filter runtime data
    foreach ($runtimeDirs as $rdir) {
        if (str_starts_with($relative, $rdir . '/')) {
            $isExcluded = true;
            break;
        }
    }

    if (!$isExcluded) {
        $zipFull->addFile($file, $relative);
    }
}
$zipFull->close();
echo "OK\n";

// 4. Build Incremental Update ZIP
echo "Building Incremental Update package ({$updateZipName})... ";
$zipUpdate = new ZipArchive();
if ($zipUpdate->open($updateZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    die("FAILED: Cannot open Update ZIP archive for output.\n");
}

// Run git diff dynamically to get changed files since baseline commit with status and renames
$baselineCommit = '950316d';
$diffFilesOutput = shell_exec("git diff --name-status --find-renames {$baselineCommit} HEAD");
if ($diffFilesOutput === null) {
    die("FAILED: Cannot run git diff to determine changed files.\n");
}

$lines = array_filter(array_map('trim', explode("\n", $diffFilesOutput)));
$manifestFiles = [];
$deletedFiles = [];
$manifestMigrations = [];

foreach ($lines as $line) {
    if (empty($line)) continue;
    $parts = preg_split('/\s+/', $line);
    if (count($parts) < 2) continue;

    $status = $parts[0];
    
    if (str_starts_with($status, 'R')) {
        // Rename status
        $oldFile = str_replace('\\', '/', $parts[1]);
        $newFile = str_replace('\\', '/', $parts[2]);
        
        if (!isExcludedFile($oldFile, $exclusions)) {
            $deletedFiles[] = $oldFile;
        }
        if (!isExcludedFile($newFile, $exclusions)) {
            if (str_starts_with($newFile, 'db/migrations/')) {
                $mName = basename($newFile);
                if ($mName !== '013_cloud_backup.sql') {
                    $manifestMigrations[] = $mName;
                }
            } else {
                $manifestFiles[] = $newFile;
            }
        }
    } else {
        $file = str_replace('\\', '/', $parts[1]);
        if (isExcludedFile($file, $exclusions)) {
            continue;
        }

        if ($status === 'D') {
            $deletedFiles[] = $file;
        } else {
            if (str_starts_with($file, 'db/migrations/')) {
                $mName = basename($file);
                if ($mName !== '013_cloud_backup.sql') {
                    $manifestMigrations[] = $mName;
                }
            } else {
                $manifestFiles[] = $file;
            }
        }
    }
}

sort($manifestFiles);
sort($deletedFiles);
sort($manifestMigrations);

// Generate Update Manifest
$manifest = [
    'manifest_schema_version' => '1.0.0',
    'product' => 'AppForm',
    'package_type' => 'update',
    'version' => $version,
    'build' => $buildNumber,
    'channel' => $channel,
    'minimum_supported_version' => '1.0.0',
    'release_tag' => "v{$version}",
    'created_at' => date('Y-m-d H:i:s'),
    'commit_hash' => $commitHash,
    'required_php_version' => '8.1.0',
    'required_php_extensions' => ['pdo_mysql', 'openssl', 'zip'],
    'required_database_engine' => 'mariadb',
    'required_database_version' => '10.6',
    'migrations' => $manifestMigrations,
    'seeds' => [],
    'files' => $manifestFiles,
    'deleted_files' => $deletedFiles,
    'protected_paths' => $exclusions,
    'rollback_supported' => true,
    'maintenance_required' => true
];

$zipUpdate->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// Generate Checksums for the manifest files list
$checksums = [];
foreach ($manifest['files'] as $fileRel) {
    $filePath = "{$rootPath}/{$fileRel}";
    if (file_exists($filePath)) {
        $checksums[$fileRel] = hash_file('sha256', $filePath);
        $zipUpdate->addFile($filePath, "files/{$fileRel}");
    }
}

// Add migrations
foreach ($manifest['migrations'] as $migration) {
    $migrationPath = "{$rootPath}/db/migrations/{$migration}";
    if (file_exists($migrationPath)) {
        $zipUpdate->addFile($migrationPath, "migrations/{$migration}");
    }
}

$zipUpdate->addFromString('checksums.json', json_encode($checksums, JSON_PRETTY_PRINT));
$zipUpdate->close();
echo "OK\n";

// 5. Generate External SHA-256 Checksum Files
echo "Generating SHA-256 checksum tags... ";
file_put_contents($fullZipPath . '.sha256', hash_file('sha256', $fullZipPath));
file_put_contents($updateZipPath . '.sha256', hash_file('sha256', $updateZipPath));
echo "OK\n";

echo "-----------------------------------------\n";
echo "Build finished successfully!\n";
echo "Clean-Install Asset: {$fullZipName} (" . number_format(filesize($fullZipPath)) . " bytes)\n";
echo "Incremental Asset  : {$updateZipName} (" . number_format(filesize($updateZipPath)) . " bytes)\n";
echo "=========================================\n";

/**
 * Returns recursive directory file list.
 */
function getDirectoryFiles(string $dir): array {
    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($iterator as $fileinfo) {
        if ($fileinfo->isFile()) {
            $files[] = $fileinfo->getPathname();
        }
    }
    return $files;
}

/**
 * Checks if a file path is excluded from update packaging.
 */
function isExcludedFile(string $relative, array $exclusions): bool {
    if ($relative === 'db/schema.sql' || $relative === 'db/migrations/013_cloud_backup.sql') {
        return true;
    }
    
    foreach ($exclusions as $exclude) {
        if ($relative === $exclude || str_starts_with($relative, $exclude . '/')) {
            return true;
        }
    }
    
    if (str_starts_with($relative, 'tests/') || 
        str_starts_with($relative, 'tools/release/config') || 
        str_starts_with($relative, 'storage/') || 
        $relative === 'tools/release/build.php' ||
        $relative === 'README.md' ||
        $relative === '.gitignore') {
        return true;
    }
    return false;
}
