<?php
// Direct mysqldump test with defaults-extra-file using tempnam
$mysqldump = 'C:\\Antigravity-PRJ\\Tools\\MariaDB\\bin\\mysqldump.exe';
$host = '127.0.0.1';
$port = '3307';
$dbName = 'appform_release_test';
$user = 'appform_local';
$pass = 'local_appform_pwd_2026';

$outputPath = dirname(__DIR__) . '/public/storage/test_backup_direct.sql';
if (file_exists($outputPath)) unlink($outputPath);

// Use tempnam like the actual code
$tmpCnf = tempnam(sys_get_temp_dir(), 'mysqldump_') . '.cnf';
echo "Temp CNF path: $tmpCnf\n";
$cnfContent = "[client]\npassword=" . addslashes($pass) . "\n";
file_put_contents($tmpCnf, $cnfContent);
echo "CNF content:\n$cnfContent\n";

$cmd = '"' . $mysqldump . '"'
    . ' --defaults-extra-file=' . $tmpCnf
    . ' --host=' . escapeshellarg($host)
    . ' --port=' . escapeshellarg($port)
    . ' --user=' . escapeshellarg($user)
    . ' ' . escapeshellarg($dbName);

echo "CMD: $cmd\n\n";

$outputHandle = fopen($outputPath, 'w');
$descriptors = [
    0 => ['pipe', 'r'],
    1 => $outputHandle,
    2 => ['pipe', 'w'],
];
$process = proc_open($cmd, $descriptors, $pipes, null);
fclose($outputHandle);
@unlink($tmpCnf);

if (is_resource($process)) {
    fclose($pipes[0]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);
    echo "Exit code: $exitCode\n";
    echo "Stderr: " . trim($stderr) . "\n";
    $size = file_exists($outputPath) ? filesize($outputPath) : 0;
    echo "Output size: $size bytes\n";
    if ($size > 0) {
        echo "BACKUP SUCCESS!\n";
    } else {
        echo "BACKUP FAILED - empty output\n";
    }
} else {
    echo "proc_open returned false!\n";
}

// Cleanup
if (file_exists($outputPath)) unlink($outputPath);
return true;
