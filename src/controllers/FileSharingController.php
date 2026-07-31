<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Core\View;
use App\Models\OrgUnit;
use App\Models\SharedFile;
use App\Models\User;
use PDO;

class FileSharingController extends Controller {

    // ─── Audit Logging ──────────────────────────────────────────────────────────
    protected function logAudit(string $action, string $entityType, ?int $entityId, array $metadata): void {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                INSERT INTO audit_logs (user_id, action, entity_type, entity_id, metadata_json, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                Auth::id(),
                $action,
                $entityType,
                $entityId,
                json_encode($metadata, JSON_UNESCAPED_UNICODE),
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            ]);
        } catch (\Throwable $e) {
            // Audit failure must never crash the application
        }
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────────
    private function requireLogin(): void {
        if (!Auth::check()) {
            Session::set('redirect_after_login', $_SERVER['REQUEST_URI'] ?? '/admin/files');
            header('Location: /login');
            exit;
        }
    }

    private function isAdmin(): bool {
        return Auth::role() === 'administrator';
    }

    private function jsonResponse(bool $success, string $message, array $data = []): void {
        header('Content-Type: application/json');
        echo json_encode(['success' => $success, 'message' => $message] + $data);
        exit;
    }

    // ─── GET /admin/files ─────────────────────────────────────────────────────────
    public function index($params = []): void {
        $this->requireLogin();
        $userId    = Auth::id();
        $myFiles   = SharedFile::getByUploader($userId);
        $sharedFiles = SharedFile::getSharedWithUser($userId);
        $allUsers  = User::getAll();
        $orgUnits  = OrgUnit::all();

        View::render('files/index', [
            'title'        => __('Files'),
            'myFiles'      => $myFiles,
            'sharedFiles'  => $sharedFiles,
            'allUsers'     => $allUsers,
            'orgUnits'     => $orgUnits,
            'isAdmin'      => $this->isAdmin(),
        ]);
    }

    // ─── POST /admin/files/upload ─────────────────────────────────────────────────
    public function store($params = []): void {
        $this->requireLogin();
        $this->checkCsrf();

        $data      = Request::post();
        $title     = trim($data['title'] ?? '');
        $desc      = trim($data['description'] ?? '') ?: null;
        $userId    = Auth::id();

        // Validate title
        if (empty($title)) {
            Session::flash('error', __('File title is required.'));
            header('Location: /admin/files');
            exit;
        }

        // Validate file upload
        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $errMap = [
                UPLOAD_ERR_INI_SIZE   => __('File exceeds server upload size limit.'),
                UPLOAD_ERR_FORM_SIZE  => __('File exceeds form upload size limit.'),
                UPLOAD_ERR_NO_FILE    => __('No file was uploaded.'),
            ];
            $errCode = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
            Session::flash('error', $errMap[$errCode] ?? __('File upload failed.'));
            header('Location: /admin/files');
            exit;
        }

        $uploadedFile = $_FILES['file'];

        // MIME validation
        if (!SharedFile::isAllowedMime($uploadedFile['tmp_name'], $uploadedFile['type'])) {
            Session::flash('error', __('File type not allowed. Allowed: PDF, Word, Excel, PowerPoint, TXT, Images, ZIP.'));
            header('Location: /admin/files');
            exit;
        }

        // Store file securely
        try {
            $fileInfo = SharedFile::storeUploadedFile($uploadedFile);
        } catch (\Throwable $e) {
            Session::flash('error', __('File storage failed: ') . $e->getMessage());
            header('Location: /admin/files');
            exit;
        }

        // Create DB record
        $file = SharedFile::create($userId, $title, $desc, $fileInfo);

        // Process permissions (recipients)
        $permissions = $this->parsePermissions($data);
        foreach ($permissions as $perm) {
            SharedFile::addPermission($file['id'], $perm['type'], $perm['id']);
        }

        $this->logAudit('file.uploaded', 'shared_files', $file['id'], [
            'title'     => $title,
            'filename'  => $fileInfo['original_name'],
            'size'      => $fileInfo['file_size'],
            'recipients' => count($permissions),
        ]);

        Session::flash('success', __('File uploaded successfully.'));
        header('Location: /admin/files');
        exit;
    }

    // ─── GET /admin/files/{id}/manage ─────────────────────────────────────────────
    public function manage($params = []): void {
        $this->requireLogin();
        $id   = (int)($params['id'] ?? 0);
        $file = SharedFile::find($id);

        if (!$file) {
            Session::flash('error', __('File not found.'));
            header('Location: /admin/files');
            exit;
        }

        $userId = Auth::id();
        $isAdmin = $this->isAdmin();

        // Only uploader or admin may manage
        if ((int)$file['uploader_id'] !== $userId && !$isAdmin) {
            Session::flash('error', __('You do not have permission to manage this file.'));
            header('Location: /admin/files');
            exit;
        }

        $permissions = SharedFile::getPermissions($id);
        $allUsers    = User::getAll();
        $orgUnits    = OrgUnit::all();

        View::render('files/manage', [
            'title'       => __('Manage File') . ' — ' . htmlspecialchars($file['title']),
            'file'        => $file,
            'permissions' => $permissions,
            'allUsers'    => $allUsers,
            'orgUnits'    => $orgUnits,
            'isAdmin'     => $isAdmin,
        ]);
    }

    // ─── POST /admin/files/{id}/permissions ───────────────────────────────────────
    public function updatePermissions($params = []): void {
        $this->requireLogin();
        $this->checkCsrf();

        $id   = (int)($params['id'] ?? 0);
        $file = SharedFile::find($id);

        if (!$file) {
            $this->jsonResponse(false, __('File not found.'));
        }

        $userId  = Auth::id();
        $isAdmin = $this->isAdmin();

        if ((int)$file['uploader_id'] !== $userId && !$isAdmin) {
            $this->jsonResponse(false, __('Permission denied.'));
        }

        $data = Request::post();
        $action = $data['perm_action'] ?? '';

        if ($action === 'add') {
            $type = $data['grantee_type'] ?? '';
            $gid  = (int)($data['grantee_id'] ?? 0);
            if (empty($type) || $gid <= 0) {
                $this->jsonResponse(false, __('Invalid recipient data.'));
            }
            $result = SharedFile::addPermission($id, $type, $gid);
            if ($result === 'duplicate') {
                $this->jsonResponse(false, __('Ο παραλήπτης έχει ήδη δικαίωμα πρόσβασης σε αυτό το αρχείο.'));
            }
            if ($result === 'invalid') {
                $this->jsonResponse(false, __('Μη έγκυρος τύπος παραλήπτη.'));
            }
            $this->logAudit('file.permission.added', 'shared_files', $id, [
                'grantee_type' => $type,
                'grantee_id'   => $gid,
            ]);
            $this->jsonResponse(true, __('Η πρόσβαση παραχωρήθηκε επιτυχώς.'));

        } elseif ($action === 'remove') {
            $permId = (int)($data['perm_id'] ?? 0);
            if ($permId <= 0) {
                $this->jsonResponse(false, __('Invalid permission ID.'));
            }
            SharedFile::removePermission($permId);
            $this->logAudit('file.permission.removed', 'shared_files', $id, ['perm_id' => $permId]);
            $this->jsonResponse(true, __('Recipient removed successfully.'));

        } else {
            $this->jsonResponse(false, __('Unknown action.'));
        }
    }

    // ─── GET /admin/files/{id}/preview ───────────────────────────────────────────
    public function preview($params = []): void {
        $this->requireLogin();

        $id      = (int)($params['id'] ?? 0);
        $userId  = Auth::id();
        $isAdmin = $this->isAdmin();

        // Strict authorization check (same as download)
        if (!SharedFile::canUserAccess($id, $userId, $isAdmin)) {
            http_response_code(403);
            echo __('Access denied. You do not have permission to view or preview this file.');
            exit;
        }

        $file = SharedFile::find($id);
        if (!$file) {
            http_response_code(404);
            echo __('File not found.');
            exit;
        }

        $filePath = SharedFile::storageDir() . '/' . $file['stored_filename'];
        if (!file_exists($filePath)) {
            http_response_code(404);
            echo __('Physical file not found on server. It may have been deleted.');
            exit;
        }

        $allowedPreviewMimes = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/webp',
            'text/plain',
        ];

        // If file type does NOT support preview
        if (!in_array($file['mime_type'], $allowedPreviewMimes, true)) {
            http_response_code(400);
            header('Content-Type: text/html; charset=UTF-8');
            $ext = strtoupper(pathinfo($file['original_name'], PATHINFO_EXTENSION));
            $title = htmlspecialchars($file['title']);
            $filename = htmlspecialchars($file['original_name']);
            $downloadUrl = "/admin/files/{$id}/download";

            echo <<<HTML
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Preview Unavailable - {$title}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100 p-3">
    <div class="card shadow-sm text-center p-4" style="max-width: 480px;">
        <div class="mb-3 text-warning">
            <i class="fa-solid fa-file-excel fa-4x opacity-75"></i>
        </div>
        <h4 class="fw-bold mb-2">Προεπισκόπηση μη διαθέσιμη</h4>
        <p class="text-muted mb-3">
            Η προεπισκόπηση δεν υποστηρίζεται για αρχεία τύπου <strong>.{$ext}</strong>.<br>
            Διαθέσιμη είναι μόνο η λήψη του αρχείου.
        </p>
        <div class="alert alert-secondary text-start small mb-3">
            <strong>Αρχείο:</strong> {$filename}<br>
            <strong>Τύπος:</strong> {$file['mime_type']}
        </div>
        <a href="{$downloadUrl}" class="btn btn-primary btn-lg w-100 mb-2">
            <i class="fa-solid fa-download me-2"></i>Λήψη Αρχείου (Download)
        </a>
    </div>
</body>
</html>
HTML;
            exit;
        }

        // Audit logging
        $this->logAudit('file.previewed', 'shared_files', $id, [
            'filename'  => $file['original_name'],
            'mime_type' => $file['mime_type'],
        ]);

        // Stream file inline for browser rendering
        header('Content-Description: File Preview');
        header('Content-Type: ' . $file['mime_type']);
        header('Content-Disposition: inline; filename="' . addslashes($file['original_name']) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        ob_clean();
        flush();
        readfile($filePath);
        exit;
    }

    // ─── GET /admin/files/{id}/download ──────────────────────────────────────────
    public function download($params = []): void {
        $this->requireLogin();

        $id      = (int)($params['id'] ?? 0);
        $userId  = Auth::id();
        $isAdmin = $this->isAdmin();

        // Authorization check
        if (!SharedFile::canUserAccess($id, $userId, $isAdmin)) {
            http_response_code(403);
            echo __('Access denied. You do not have permission to download this file.');
            exit;
        }

        $file = SharedFile::find($id);
        if (!$file) {
            http_response_code(404);
            echo __('File not found.');
            exit;
        }

        $filePath = SharedFile::storageDir() . '/' . $file['stored_filename'];
        if (!file_exists($filePath)) {
            http_response_code(404);
            echo __('Physical file not found on server. It may have been deleted.');
            exit;
        }

        $this->logAudit('file.downloaded', 'shared_files', $id, [
            'filename' => $file['original_name'],
        ]);

        // Stream file securely
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $file['mime_type']);
        header('Content-Disposition: attachment; filename="' . addslashes($file['original_name']) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        ob_clean();
        flush();
        readfile($filePath);
        exit;
    }

    // ─── POST /admin/files/{id}/delete ───────────────────────────────────────────
    public function destroy($params = []): void {
        $this->requireLogin();
        $this->checkCsrf();

        $id      = (int)($params['id'] ?? 0);
        $file    = SharedFile::find($id);
        $userId  = Auth::id();
        $isAdmin = $this->isAdmin();

        if (!$file) {
            Session::flash('error', __('File not found.'));
            header('Location: /admin/files');
            exit;
        }

        if ((int)$file['uploader_id'] !== $userId && !$isAdmin) {
            Session::flash('error', __('You do not have permission to delete this file.'));
            header('Location: /admin/files');
            exit;
        }

        $this->logAudit('file.deleted', 'shared_files', $id, [
            'title'    => $file['title'],
            'filename' => $file['original_name'],
        ]);

        SharedFile::delete($id);

        Session::flash('success', __('File deleted successfully.'));
        header('Location: /admin/files');
        exit;
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────────
    private function parsePermissions(array $data): array {
        $permissions = [];

        // Direct users
        $userIds = $data['recipient_users'] ?? [];
        if (!is_array($userIds)) $userIds = [];
        foreach ($userIds as $uid) {
            if ((int)$uid > 0) {
                $permissions[] = ['type' => 'user', 'id' => (int)$uid];
            }
        }

        // Org units (departments, subdepartments, teams)
        $unitIds = $data['recipient_units'] ?? [];
        if (!is_array($unitIds)) $unitIds = [];

        $db = Database::getInstance();
        foreach ($unitIds as $unitId) {
            if ((int)$unitId <= 0) continue;
            $stmt = $db->prepare("SELECT type FROM org_units WHERE id = ?");
            $stmt->execute([(int)$unitId]);
            $unit = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($unit) {
                $permissions[] = ['type' => $unit['type'], 'id' => (int)$unitId];
            }
        }

        return $permissions;
    }
}
