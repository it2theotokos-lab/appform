<?php
return [
    'debug' => false,
    'db' => [
        'host' => '127.0.0.1',
        'dbname' => 'appform_prod',
        'user' => 'prod_user',
        'pass' => 'secure_password',
        'charset' => 'utf8mb4'
    ],
    'session' => [
        'name' => 'APPFORM_SESS',
        'lifetime' => 3600,
        'secure' => true
    ],
    'storage' => [
        'private_uploads' => dirname(__DIR__) . '/storage/private_uploads',
        'exports' => dirname(__DIR__) . '/storage/exports'
    ]
];
