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

        // 2. Validate declared files exist in ZIP
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

        // 3. Validate ZIP files are declared in manifest
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

        // 4. Verify Git diff files are fully present
        $baselineCommit = '950316d';
        $diffFilesOutput = shell_exec("git diff --name-only {$baselineCommit} HEAD");
        if ($diffFilesOutput !== null) {
            $diffFiles = array_filter(array_map('trim', explode("\n", $diffFilesOutput)));
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

            foreach ($diffFiles as $f) {
                $relative = str_replace('\\', '/', $f);
                $isExcluded = false;
                foreach ($exclusions as $exclude) {
                    if ($relative === $exclude || str_starts_with($relative, $exclude . '/')) {
                        $isExcluded = true;
                        break;
                    }
                }
                if (str_starts_with($relative, 'tests/') || 
                    str_starts_with($relative, 'tools/release/config') || 
                    str_starts_with($relative, 'storage/') || 
                    $relative === 'tools/release/build.php' ||
                    $relative === 'README.md' ||
                    $relative === '.gitignore') {
                    $isExcluded = true;
                }

                if ($isExcluded) {
                    continue;
                }

                if (str_starts_with($relative, 'db/migrations/')) {
                    $m = basename($relative);
                    if (!in_array($m, $manifest['migrations'])) {
                        throw new Exception("Migration '{$m}' from Git diff is missing from manifest migrations");
                    }
                } else {
                    if (file_exists(__DIR__ . "/../{$relative}")) {
                        if (!in_array($relative, $manifest['files'])) {
                            throw new Exception("Production file '{$relative}' from Git diff is missing from manifest files list");
                        }
                    } else {
                        if (!in_array($relative, $manifest['deleted_files'])) {
                            throw new Exception("Deleted file '{$relative}' from Git diff is missing from manifest deleted_files");
                        }
                    }
                }
            }
        }

        $zip->close();
        echo "Test 1 Passed: Package contents match manifest exactly.\n";
    }
}
ReleaseCompletenessTest::run();
