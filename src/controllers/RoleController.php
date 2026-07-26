<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Core\Session;
use App\Core\View;
use App\Core\Database;
use App\Models\Role;
use App\Models\Permission;
use PDO;

class RoleController extends Controller {
    protected function logAudit(string $action, string $entityType, ?int $entityId, array $metadata) {
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
                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ]);
        } catch (\Exception $e) {}
    }

    public function index() {
        $roles = Role::getAll();
        View::render('roles/index', [
            'title' => 'Διαχείριση Ρόλων',
            'roles' => $roles
        ]);
    }

    public function store() {
        $this->checkCsrf();
        $data = Request::all();

        $validated = $this->validate($data, [
            'name' => ['required'],
            'slug' => ['required']
        ]);

        $db = Database::getInstance();
        $chk = $db->prepare("SELECT COUNT(*) FROM roles WHERE slug = ?");
        $chk->execute([$validated['slug']]);
        if ($chk->fetchColumn() > 0) {
            Session::flash('error', 'Υπάρχει ήδη ρόλος με αυτό το Slug.');
            $this->back();
        }

        $stmt = $db->prepare("INSERT INTO roles (name, slug, description, is_system) VALUES (?, ?, ?, 0)");
        $stmt->execute([
            $validated['name'],
            strtolower($validated['slug']),
            $data['description'] ?? ''
        ]);

        $newRoleId = $db->lastInsertId();
        $this->logAudit('create', 'roles', $newRoleId, ['slug' => $validated['slug']]);

        Session::flash('success', 'Ο ρόλος δημιουργήθηκε επιτυχώς.');
        $this->redirect('/admin/roles');
    }

    public function edit($params) {
        $id = (int)$params['id'];
        $db = Database::getInstance();
        
        $stmt = $db->prepare("SELECT * FROM roles WHERE id = ?");
        $stmt->execute([$id]);
        $role = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$role) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $permissions = Permission::getAll();

        // Get current role permissions
        $stmt2 = $db->prepare("SELECT permission_id FROM role_permissions WHERE role_id = ?");
        $stmt2->execute([$id]);
        $currentPerms = $stmt2->fetchAll(PDO::FETCH_COLUMN);

        View::render('roles/edit', [
            'title' => 'Επεξεργασία Ρόλου',
            'role' => $role,
            'permissions' => $permissions,
            'currentPerms' => $currentPerms
        ]);
    }

    public function update($params) {
        $this->checkCsrf();
        $id = (int)$params['id'];
        $data = Request::all();

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM roles WHERE id = ?");
        $stmt->execute([$id]);
        $role = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$role) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $validated = $this->validate($data, [
            'name' => ['required']
        ]);

        // Prevent modification of system Administrator role slug
        if ($role['slug'] === 'administrator') {
            // Cannot remove core permissions
            $validatedPerms = $data['permissions'] ?? [];
            if (count($validatedPerms) < 10) {
                Session::flash('error', 'Ο Administrator πρέπει να διατηρεί όλα τα permissions.');
                $this->back();
            }
        }

        $db->beginTransaction();
        try {
            // Update name / description
            $upd = $db->prepare("UPDATE roles SET name = ?, description = ? WHERE id = ?");
            $upd->execute([
                $validated['name'],
                $data['description'] ?? '',
                $id
            ]);

            // Sync Permissions
            $del = $db->prepare("DELETE FROM role_permissions WHERE role_id = ?");
            $del->execute([$id]);

            if (isset($data['permissions']) && is_array($data['permissions'])) {
                $ins = $db->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
                foreach ($data['permissions'] as $permId) {
                    $ins->execute([$id, (int)$permId]);
                }
            }

            $db->commit();
            $this->logAudit('edit', 'roles', $id, ['name' => $validated['name']]);
            Session::flash('success', 'Ο ρόλος ενημερώθηκε επιτυχώς.');
        } catch (\Exception $e) {
            $db->rollBack();
            Session::flash('error', 'Σφάλμα κατά την ενημέρωση: ' . $e->getMessage());
        }

        $this->redirect('/admin/roles');
    }

    public function delete($params) {
        $this->checkCsrf();
        $id = (int)$params['id'];

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM roles WHERE id = ?");
        $stmt->execute([$id]);
        $role = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$role) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        if ($role['is_system']) {
            Session::flash('error', 'Δεν μπορείτε να διαγράψετε ρόλο συστήματος.');
            $this->back();
        }

        // Check if role is assigned to users
        $chk = $db->prepare("SELECT COUNT(*) FROM users WHERE role_id = ?");
        $chk->execute([$id]);
        if ($chk->fetchColumn() > 0) {
            Session::flash('error', 'Δεν μπορείτε να διαγράψετε ρόλο που χρησιμοποιείται από χρήστες.');
            $this->back();
        }

        $del = $db->prepare("DELETE FROM roles WHERE id = ?");
        $del->execute([$id]);

        $this->logAudit('delete', 'roles', $id, ['slug' => $role['slug']]);

        Session::flash('success', 'Ο ρόλος διαγράφηκε επιτυχώς.');
        $this->redirect('/admin/roles');
    }
}
