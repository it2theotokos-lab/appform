<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class SharedFile {

    // ---------------------------------------------------------------------------
    // Allowed MIME types and extensions
    // ---------------------------------------------------------------------------
    private static array $allowedMimes = [
        'application/pdf'                                                       => 'pdf',
        'application/msword'                                                    => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel'                                              => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'    => 'xlsx',
        'application/vnd.ms-powerpoint'                                         => 'ppt',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
        'text/plain'                                                            => 'txt',
        'image/jpeg'                                                            => 'jpg',
        'image/png'                                                             => 'png',
        'image/gif'                                                             => 'gif',
        'image/webp'                                                            => 'webp',
        'application/zip'                                                       => 'zip',
        'application/x-zip-compressed'                                          => 'zip',
    ];

    public static function storageDir(): string {
        return dirname(__DIR__, 2) . '/storage/shared_files';
    }

    // ---------------------------------------------------------------------------
    // MIME validation
    // ---------------------------------------------------------------------------
    public static function isAllowedMime(string $tmpPath, string $clientMime): bool {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->file($tmpPath);
        // Accept if real MIME matches an allowed type
        return isset(self::$allowedMimes[$realMime]);
    }

    public static function detectMime(string $tmpPath): string {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        return $finfo->file($tmpPath) ?: 'application/octet-stream';
    }

    // ---------------------------------------------------------------------------
    // File CRUD
    // ---------------------------------------------------------------------------
    public static function create(int $uploaderId, string $title, ?string $description, array $fileInfo): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO shared_files (uploader_id, title, description, original_name, stored_filename, mime_type, file_size)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $uploaderId,
            trim($title),
            $description !== null ? trim($description) : null,
            $fileInfo['original_name'],
            $fileInfo['stored_filename'],
            $fileInfo['mime_type'],
            $fileInfo['file_size'],
        ]);
        $id = (int)$db->lastInsertId();
        return self::find($id);
    }

    public static function find(int $id): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT sf.*, u.username as uploader_username, u.full_name as uploader_name FROM shared_files sf LEFT JOIN users u ON sf.uploader_id = u.id WHERE sf.id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function getByUploader(int $uploaderId): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT sf.*, u.username as uploader_username, u.full_name as uploader_name
            FROM shared_files sf
            LEFT JOIN users u ON sf.uploader_id = u.id
            WHERE sf.uploader_id = ?
            ORDER BY sf.created_at DESC
        ");
        $stmt->execute([$uploaderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getSharedWithUser(int $userId): array {
        // Get files shared with user directly, or through their org unit
        $db = Database::getInstance();

        // Get user's org_unit_id and type
        $stmt = $db->prepare("SELECT org_unit_id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $userRow = $stmt->fetch(PDO::FETCH_ASSOC);
        $orgUnitId = $userRow['org_unit_id'] ?? null;

        // Build list of grantee_id/type conditions
        // Always include direct user grant
        $conditions = ["(sfp.grantee_type = 'user' AND sfp.grantee_id = ?)"];
        $params = [$userId];

        if ($orgUnitId) {
            // Get this unit's info
            $stmtUnit = $db->prepare("SELECT type, parent_id FROM org_units WHERE id = ?");
            $stmtUnit->execute([$orgUnitId]);
            $unit = $stmtUnit->fetch(PDO::FETCH_ASSOC);

            if ($unit) {
                $unitType = $unit['type'];
                $parentId = $unit['parent_id'];

                // Add condition for user's own unit
                $conditions[] = "(sfp.grantee_type = ? AND sfp.grantee_id = ?)";
                $params[] = $unitType;
                $params[] = $orgUnitId;

                // If subdepartment or team, also check parent department
                if ($parentId && in_array($unitType, ['subdepartment', 'team'], true)) {
                    $conditions[] = "(sfp.grantee_type = 'department' AND sfp.grantee_id = ?)";
                    $params[] = $parentId;
                }
            }
        }

        $whereClause = implode(' OR ', $conditions);

        $stmt = $db->prepare("
            SELECT DISTINCT sf.*, u.username as uploader_username, u.full_name as uploader_name
            FROM shared_files sf
            LEFT JOIN users u ON sf.uploader_id = u.id
            INNER JOIN shared_file_permissions sfp ON sfp.file_id = sf.id
            WHERE sf.uploader_id != ?
              AND ($whereClause)
            ORDER BY sf.created_at DESC
        ");
        // Prepend uploader_id exclusion parameter
        array_unshift($params, $userId);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function delete(int $id): bool {
        $file = self::find($id);
        if (!$file) return false;

        // Delete physical file
        $path = self::storageDir() . '/' . $file['stored_filename'];
        if (file_exists($path)) {
            unlink($path);
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM shared_files WHERE id = ?");
        $stmt->execute([$id]);
        return true;
    }

    // ---------------------------------------------------------------------------
    // Permissions CRUD
    // ---------------------------------------------------------------------------
    public static function addPermission(int $fileId, string $granteeType, int $granteeId): string {
        if (!in_array($granteeType, ['user', 'department', 'subdepartment', 'team'], true)) {
            return 'invalid';
        }
        $db = Database::getInstance();
        // Prevent duplicates
        $stmt = $db->prepare("SELECT id FROM shared_file_permissions WHERE file_id = ? AND grantee_type = ? AND grantee_id = ?");
        $stmt->execute([$fileId, $granteeType, $granteeId]);
        if ($stmt->fetch()) {
            return 'duplicate';
        }

        $stmt = $db->prepare("INSERT INTO shared_file_permissions (file_id, grantee_type, grantee_id) VALUES (?, ?, ?)");
        $stmt->execute([$fileId, $granteeType, $granteeId]);
        return 'added';
    }

    public static function removePermission(int $permId): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM shared_file_permissions WHERE id = ?");
        $stmt->execute([$permId]);
        return $stmt->rowCount() > 0;
    }

    public static function getPermissions(int $fileId): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT sfp.*,
                CASE sfp.grantee_type
                    WHEN 'user' THEN (SELECT CONCAT(full_name, ' (@', username, ')') FROM users WHERE id = sfp.grantee_id)
                    ELSE (SELECT name FROM org_units WHERE id = sfp.grantee_id)
                END as grantee_label
            FROM shared_file_permissions sfp
            WHERE sfp.file_id = ?
            ORDER BY sfp.grantee_type, sfp.id
        ");
        $stmt->execute([$fileId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function syncPermissions(int $fileId, array $permissions): void {
        $db = Database::getInstance();
        $db->prepare("DELETE FROM shared_file_permissions WHERE file_id = ?")->execute([$fileId]);
        foreach ($permissions as $perm) {
            if (!empty($perm['type']) && !empty($perm['id'])) {
                self::addPermission($fileId, $perm['type'], (int)$perm['id']);
            }
        }
    }

    // ---------------------------------------------------------------------------
    // Authorization check — core security method
    // ---------------------------------------------------------------------------
    /**
     * Returns true if a user can download/access a file.
     * Access granted if:
     *   1. User is the uploader
     *   2. User is an administrator
     *   3. User is explicitly named in shared_file_permissions (grantee_type='user')
     *   4. User's org_unit matches a permission (grantee_type=unit_type, grantee_id=unit_id)
     *   5. User's org_unit's parent department is in permissions (for subdept/team members)
     */
    public static function canUserAccess(int $fileId, int $userId, bool $isAdmin): bool {
        $file = self::find($fileId);
        if (!$file) return false;

        // Uploader always has access
        if ((int)$file['uploader_id'] === $userId) return true;

        // Administrator always has access
        if ($isAdmin) return true;

        // Get user's org unit
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT org_unit_id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $userRow = $stmt->fetch(PDO::FETCH_ASSOC);
        $orgUnitId = $userRow['org_unit_id'] ?? null;

        // Get all permissions for this file
        $stmt = $db->prepare("SELECT grantee_type, grantee_id FROM shared_file_permissions WHERE file_id = ?");
        $stmt->execute([$fileId]);
        $perms = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($perms)) return false;

        foreach ($perms as $perm) {
            // Direct user grant
            if ($perm['grantee_type'] === 'user' && (int)$perm['grantee_id'] === $userId) {
                return true;
            }

            // Org unit grant
            if ($orgUnitId && in_array($perm['grantee_type'], ['department', 'subdepartment', 'team'], true)) {
                // Direct org unit match
                if ((int)$perm['grantee_id'] === $orgUnitId) {
                    return true;
                }

                // Check if permission is department-level and user is in a child unit
                if ($perm['grantee_type'] === 'department') {
                    // Check if user's org unit has this department as parent
                    $stmtUnit = $db->prepare("SELECT parent_id FROM org_units WHERE id = ?");
                    $stmtUnit->execute([$orgUnitId]);
                    $unit = $stmtUnit->fetch(PDO::FETCH_ASSOC);
                    if ($unit && (int)$unit['parent_id'] === (int)$perm['grantee_id']) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    // ---------------------------------------------------------------------------
    // Store uploaded file to private storage
    // ---------------------------------------------------------------------------
    public static function storeUploadedFile(array $uploadedFile): array {
        $storageDir = self::storageDir();
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0750, true);
        }

        $originalName = basename($uploadedFile['name']);
        $mime         = self::detectMime($uploadedFile['tmp_name']);
        $ext          = self::$allowedMimes[$mime] ?? 'bin';
        $storedName   = bin2hex(random_bytes(16)) . '_' . time() . '.' . $ext;
        $destPath     = $storageDir . '/' . $storedName;

        if (!move_uploaded_file($uploadedFile['tmp_name'], $destPath)) {
            throw new \RuntimeException('Failed to move uploaded file to storage.');
        }

        return [
            'original_name'   => $originalName,
            'stored_filename' => $storedName,
            'mime_type'       => $mime,
            'file_size'       => filesize($destPath),
        ];
    }
}
