<?php
return [
    'app' => [
        'name' => 'AppForm',
        'url' => 'http://localhost:8000',
        'env' => 'production',
        'debug' => false,
    ],
    'db' => [
        'host' => '127.0.0.1',
        'port' => '3306',
        'name' => 'appform_db',
        'user' => 'your_db_user',
        'pass' => 'your_db_password',
        'charset' => 'utf8mb4',
    ],
    'session' => [
        'lifetime' => 28800,
        'idle_timeout' => 1800,
        'cookie_secure' => true, // Secure in production
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ],
    'upload' => [
        'max_size' => 10485760,
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
        'logs' => __DIR__ . '/../storage/logs/app.log',
        'private_uploads' => __DIR__ . '/../storage/private_uploads',
        'exports' => __DIR__ . '/../storage/exports',
    ]
];
