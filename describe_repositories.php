<?php
require 'vendor/autoload.php';
$app = new App\Core\App();
$db = App\Core\Database::getInstance();
$stmt = $db->query('DESCRIBE repositories');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
