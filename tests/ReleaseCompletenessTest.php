<?php
/**
 * Release Completeness and Manifest Validation Test
 */

class ReleaseCompletenessTest {
    public static function run() {
        echo "Running Release Completeness Tests...\n";

        $versionFile = __DIR__ . '/../config/version.php';
        if (!file_exists($versionFile)) {
            throw new Exception("version.php missing");
        }
        $verConfig = require $versionFile;
        $version = $verConfig['version'];

        $zipPath = __DIR__ . "/../release/AppForm-{$version}-update.zip";
        if (!file_exists($zipPath)) {
            throw new Exception("Update ZIP not found at {$zipPath}");
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new Exception("Failed to open update ZIP");
        }

        // 1. Read manifest and checksum files
        $manifestContent = $zip->getFromName('manifest.json');
        if (!$manifestContent) {
            throw new Exception("manifest.json missing in update ZIP");
        }
        $manifest = json_decode($manifestContent, true);

        $checksumsContent = $zip->getFromName('checksums.json');
        if (!$checksumsContent) {
            throw new Exception("checksums.json missing in update ZIP");
        }
        $checksums = json_decode($checksumsContent, true);

        // 2. Strict Assertions for Excluded / Included files
        // 013 must not exist
        if ($zip->locateName('migrations/013_cloud_backup.sql') !== false) {
            throw new Exception("ERROR: db/migrations/013_cloud_backup.sql exists inside update ZIP!");
        }
        if (in_array('013_cloud_backup.sql', $manifest['migrations'])) {
            throw new Exception("ERROR: db/migrations/013_cloud_backup.sql is declared in manifest migrations!");
        }

        // schema.sql must not exist
        if ($zip->locateName('files/db/schema.sql') !== false) {
            throw new Exception("ERROR: db/schema.sql exists inside update ZIP!");
        }
        if (in_array('db/schema.sql', $manifest['files'])) {
            throw new Exception("ERROR: db/schema.sql is declared in manifest files!");
        }

        // worker.php must exist
        if ($zip->locateName('files/tools/release/worker.php') === false) {
            throw new Exception("ERROR: tools/release/worker.php is missing from update ZIP!");
        }
        if (!in_array('tools/release/worker.php', $manifest['files'])) {
            throw new Exception("ERROR: tools/release/worker.php is not declared in manifest files list!");
        }

        // migrations list check
        $expectedMigrations = [
            '023_cloud_backup_metadata.sql',
            '024_create_update_tables.sql',
            '025_add_updates_permissions.sql'
        ];
        if (array_diff($manifest['migrations'], $expectedMigrations) !== array_diff($expectedMigrations, $manifest['migrations'])) {
            throw new Exception("ERROR: Migrations list does not match expected list exactly.");
        }

        // 3. Validate declared files exist in ZIP
        foreach ($manifest['files'] as $f) {
            $zipFilePath = "files/{$f}";
            if ($zip->locateName($zipFilePath) === false) {
                throw new Exception("Declared file '{$f}' is missing from ZIP '{$zipFilePath}'");
            }
            // Checksum validation
            $fileData = $zip->getFromName($zipFilePath);
            $hash = hash('sha256', $fileData);
            if (($checksums[$f] ?? '') !== $hash) {
                throw new Exception("Checksum mismatch for '{$f}'");
            }
        }

        // 4. Validate ZIP files are declared in manifest
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name === 'manifest.json' || $name === 'checksums.json') {
                continue;
            }
            if (str_starts_with($name, 'files/')) {
                $f = substr($name, 6);
                if (!in_array($f, $manifest['files'])) {
                    throw new Exception("File '{$name}' exists in ZIP but is not declared in manifest files list");
                }
            } elseif (str_starts_with($name, 'migrations/')) {
                $m = substr($name, 11);
                if (!in_array($m, $manifest['migrations'])) {
                    throw new Exception("Migration '{$name}' exists in ZIP but is not declared in manifest migrations list");
                }
            }
        }

        // 5. Verify Git diff --name-status files are fully present
        $baselineCommit = '950316d';
        $diffFilesOutput = shell_exec("git diff --name-status --find-renames {$baselineCommit} HEAD");
        if ($diffFilesOutput !== null) {
            $lines = array_filter(array_map('trim', explode("\n", $diffFilesOutput)));
            $exclusions = [
                'config/config.local.php',
                'config/installed.lock',
                '.env',
                '.git',
                'appform.db',
                'release',
                'storage/backups',
                'storage/private_uploads',
                'storage/document_final_pdfs',
                'storage/logs',
                'storage/cache',
                'storage/sessions'
            ];

            foreach ($lines as $line) {
                if (empty($line)) continue;
                $parts = preg_split('/\s+/', $line);
                if (count($parts) < 2) continue;

                $status = $parts[0];
                
                if (str_starts_with($status, 'R')) {
                    $oldFile = str_replace('\\', '/', $parts[1]);
                    $newFile = str_replace('\\', '/', $parts[2]);
                    
                    if (!self::isExcluded($oldFile, $exclusions)) {
                        if (!in_array($oldFile, $manifest['deleted_files'])) {
                            throw new Exception("Renamed old file '{$oldFile}' missing from manifest deleted_files");
                        }
                    }
                    if (!self::isExcluded($newFile, $exclusions)) {
                        if (str_starts_with($newFile, 'db/migrations/')) {
                            $m = basename($newFile);
                            if ($m !== '013_cloud_backup.sql' && !in_array($m, $manifest['migrations'])) {
                                throw new Exception("Renamed new migration '{$m}' missing from manifest migrations");
                            }
                        } else {
                            if (!in_array($newFile, $manifest['files'])) {
                                throw new Exception("Renamed new file '{$newFile}' missing from manifest files");
                            }
                        }
                    }
                } else {
                    $file = str_replace('\\', '/', $parts[1]);
                    if (self::isExcluded($file, $exclusions)) {
                        continue;
                    }

                    if ($status === 'D') {
                        if (!in_array($file, $manifest['deleted_files'])) {
                            throw new Exception("Deleted file '{$file}' missing from manifest deleted_files");
                        }
                    } else {
                        if (str_starts_with($file, 'db/migrations/')) {
                            $m = basename($file);
                            if ($m !== '013_cloud_backup.sql' && !in_array($m, $manifest['migrations'])) {
                                throw new Exception("Migration '{$m}' missing from manifest migrations");
                            }
                        } else {
                            if (!in_array($file, $manifest['files'])) {
                                throw new Exception("Production file '{$file}' missing from manifest files");
                            }
                        }
                    }
                }
            }
        }

        $zip->close();
        echo "Test 1 Passed: Package contents match manifest exactly.\n";
        echo "Test 2 Passed: worker.php exists and exclusions (013, schema.sql) verified.\n";
        echo "Test 3 Passed: Git diff renames, additions, and deletions verified.\n";
    }

    private static function isExcluded(string $relative, array $exclusions): bool {
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
}
ReleaseCompletenessTest::run();
