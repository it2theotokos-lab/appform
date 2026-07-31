<?php
require __DIR__ . '/../vendor/autoload.php';
chdir(__DIR__ . '/..');
new App\Core\App();
$pdo = App\Core\Database::getInstance();
$sql = file_get_contents(__DIR__ . '/../db/migrations/035_file_sharing_module.sql');
$pdo->exec($sql);
echo "Migration 035 executed successfully!\n";
