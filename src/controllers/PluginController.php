<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Services\PluginManager;
use App\Core\Database;
use PDO;

class PluginController extends Controller {
    protected function logAudit(string $action, string $entityType, ?int $entityId, array $metadata) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                INSERT INTO audit_logs (user_id, action, entity_type, entity_id, metadata_json, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                \App\Core\Auth::id() ?: 1,
                $action,
                $entityType,
                $entityId,
                json_encode($metadata, JSON_UNESCAPED_UNICODE),
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ]);
        } catch (\Exception $e) {}
    }

    public function install() {
        $this->checkCsrf();

        // Simulate receiving staging zip package
        $pkgPath = 'storage/backups/example-plugin.zip';
        $pkgDir = dirname($pkgPath);
        if (!is_dir($pkgDir)) {
            mkdir($pkgDir, 0755, true);
        }
        file_put_contents($pkgPath, "MOCK ZIP ARCHIVE METADATA");

        $res = PluginManager::install($pkgPath);
        
        if ($res['success']) {
            $this->logAudit('plugin.installed', 'plugins', null, ['key' => 'vendor.example-plugin']);
            Session::flash('success', $res['message']);
        } else {
            Session::flash('error', $res['message']);
        }

        $this->redirect('/admin/settings?tab=plugins');
    }

    public function enable(array $params) {
        $this->checkCsrf();
        $key = $params['key'] ?? '';

        if (PluginManager::enable($key)) {
            $this->logAudit('plugin.enabled', 'plugins', null, ['key' => $key]);
            Session::flash('success', 'Το πρόσθετο ενεργοποιήθηκε με επιτυχία.');
        } else {
            Session::flash('error', 'Αποτυχία ενεργοποίησης πρόσθετου.');
        }

        $this->redirect('/admin/settings?tab=plugins');
    }

    public function disable(array $params) {
        $this->checkCsrf();
        $key = $params['key'] ?? '';

        if (PluginManager::disable($key)) {
            $this->logAudit('plugin.disabled', 'plugins', null, ['key' => $key]);
            Session::flash('success', 'Το πρόσθετο απενεργοποιήθηκε με επιτυχία.');
        } else {
            Session::flash('error', 'Αποτυχία απενεργοποίησης πρόσθετου.');
        }

        $this->redirect('/admin/settings?tab=plugins');
    }
}
