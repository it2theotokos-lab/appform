<?php
namespace App\Services;

use App\Core\Database;

class CloudEncryptionService {
    public static function encryptToken(string $token): string {
        $key = \App\Core\App::$config['app_key'] ?? 'appform_secret_encryption_key_hash';
        
        // authenticated encryption AES-256-GCM
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-gcm'));
        $tag = '';
        $ciphertext = openssl_encrypt($token, 'aes-256-gcm', $key, 0, $iv, $tag);
        
        return json_encode([
            'ciphertext' => $ciphertext,
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'v' => '1.0'
        ]);
    }

    public static function decryptToken(string $payload): string {
        $key = \App\Core\App::$config['app_key'] ?? 'appform_secret_encryption_key_hash';
        $data = json_decode($payload, true);
        if (!$data) return '';

        $iv = base64_decode($data['iv']);
        $tag = base64_decode($data['tag']);

        return openssl_decrypt($data['ciphertext'], 'aes-256-gcm', $key, 0, $iv, $tag) ?: '';
    }

    public static function encryptFile(string $srcPath, string $destPath): bool {
        $key = \App\Core\App::$config['app_key'] ?? 'appform_secret_encryption_key_hash';
        $data = file_get_contents($srcPath);
        if ($data === false) return false;

        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-gcm'));
        $tag = '';
        $ciphertext = openssl_encrypt($data, 'aes-256-gcm', $key, 0, $iv, $tag);

        $payload = json_encode([
            'ciphertext' => $ciphertext,
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag)
        ]);

        return file_put_contents($destPath, $payload) !== false;
    }
}
