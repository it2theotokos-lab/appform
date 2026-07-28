<?php
$default = [
    'app' => [
        'name' => 'AppForm',
        'url' => 'http://localhost:8000',
        'env' => 'development', // development or production
        'debug' => true,
    ],
    'db' => [
        'host' => '127.0.0.1',
        'port' => '3306',
        'name' => 'appform_db',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    'session' => [
        'lifetime' => 28800, // 8 hours in seconds
        'idle_timeout' => 1800, // 30 minutes in seconds
        'cookie_secure' => false, // Set to true if using HTTPS
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ],
    'upload' => [
        'max_size' => 10485760, // 10MB
        'allowed_types' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ],
    ],
    'storage' => [
        'logs' => __DIR__ . '/../public/storage/logs/app.log',
        'private_uploads' => __DIR__ . '/../public/storage/private_uploads',
        'exports' => __DIR__ . '/../public/storage/exports',
    ],
    'queue' => [
        'sync' => true,
        'cloud_upload_timeout' => 1800,
        'worker_heartbeat_timeout' => 300,
    ]
];

$localFile = __DIR__ . '/config.local.php';
if (file_exists($localFile)) {
    $local = require $localFile;
    return array_replace_recursive($default, $local);
}

return $default;
