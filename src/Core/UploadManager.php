<?php
namespace App\Core;

class UploadManager {
    protected static $globalMaxSize = 10485760; // 10MB default
    protected static $allowedExtensions = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];

    public static function upload(array $fileInfo, ?array $allowedExts = null, ?int $maxSize = null): array {
        if ($fileInfo['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception("Σφάλμα μεταφόρτωσης αρχείου: " . $fileInfo['error']);
        }

        $size = $fileInfo['size'];
        $limit = $maxSize ?? self::$globalMaxSize;
        if ($size > $limit) {
            throw new \Exception("Το αρχείο υπερβαίνει το μέγιστο μέγεθος των " . ($limit / 1024 / 1024) . "MB.");
        }

        // Verify MIME type using finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $fileInfo['tmp_name']);
        finfo_close($finfo);

        $originalName = basename($fileInfo['name']);
        
        // Double extension block & Extension extraction
        if (preg_match('/\.[a-zA-Z0-9]+\.[a-zA-Z0-9]+$/', $originalName)) {
            throw new \Exception("Μη αποδεκτό όνομα αρχείου (double extension block).");
        }

        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        // Validation against whitelisted extensions
        $whitelist = $allowedExts ?? array_keys(self::$allowedExtensions);
        if (!in_array($ext, $whitelist)) {
            throw new \Exception("Μη επιτρεπτός τύπος αρχείου: $ext");
        }

        // Check if extension matches the MIME type
        $expectedMime = self::$allowedExtensions[$ext] ?? null;
        if ($expectedMime && $expectedMime !== $mime) {
            throw new \Exception("Το περιεχόμενο του αρχείου δεν αντιστοιχεί στην επέκτασή του.");
        }

        // Generate unique name and path
        $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
        $uploadDir = App::$config['storage']['private_uploads'] ?? __DIR__ . '/../../storage/private_uploads';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $targetPath = $uploadDir . '/' . $storedName;

        if (!move_uploaded_file($fileInfo['tmp_name'], $targetPath)) {
            throw new \Exception("Αποτυχία αποθήκευσης αρχείου στον διακομιστή.");
        }

        // SHA-256 Checksum
        $checksum = hash_file('sha256', $targetPath);

        return [
            'original_name' => $originalName,
            'stored_name' => $storedName,
            'mime_type' => $mime,
            'file_extension' => $ext,
            'file_size' => $size,
            'checksum_sha256' => $checksum,
            'storage_path' => $targetPath
        ];
    }
}
