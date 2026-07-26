<?php
// AppForm Release Packager Utility

$src = dirname(__DIR__);
$dst = $src . '/release/AppForm-1.0.0';

echo "Building release package...\n";

if (!is_dir($dst)) {
    mkdir($dst, 0755, true);
}

// Function to copy directory recursively
function copyDir($src, $dst, $exclude = []) {
    $dir = opendir($src);
    @mkdir($dst);
    while (false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            if (in_array($file, $exclude)) continue;
            if (is_dir($src . '/' . $file)) {
                copyDir($src . '/' . $file, $dst . '/' . $file, $exclude);
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

$excludes = [
    'release',
    '.git',
    '.gemini',
    'appform.db',
    'logs',
    'storage',
    'tests',
    'task.md',
    'walkthrough.md',
    'implementation_plan.md',
    'config.local.php',
    'installed.lock'
];

copyDir($src, $dst, $excludes);

// Ensure empty storage directories exist in the release package
$storageDirs = [
    'storage',
    'storage/backups',
    'storage/document_final_pdfs',
    'storage/document_signatures',
    'storage/document_templates',
    'storage/exports',
    'storage/logs',
    'storage/private_uploads'
];
foreach ($storageDirs as $sDir) {
    @mkdir($dst . '/' . $sDir, 0755, true);
}

echo "[+] SUCCESS: Production release package built under release/AppForm-1.0.0/\n";
exit(0);
