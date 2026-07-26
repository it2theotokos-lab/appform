<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Core\Session;
use App\Core\View;
use App\Core\Database;
use App\Models\Form;
use App\Models\Role;
use App\Models\User;
use App\Models\FormRoleAssignment;
use App\Models\FormUserAssignment;
use PDO;

class FormAssignmentController extends Controller {
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

    public function edit($params) {
        $formId = (int)$params['id'];
        $form = Form::findById($formId);

        if (!$form) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $roles = Role::getAll();
        $users = User::getAll();

        $roleAssignments = FormRoleAssignment::getAssignmentsByFormId($formId);
        $userAssignments = FormUserAssignment::getAssignmentsByFormId($formId);

        View::render('forms/assignments', [
            'title' => 'Ανάθεση Φόρμας: ' . $form['title'],
            'form' => $form,
            'roles' => $roles,
            'users' => $users,
            'roleAssignments' => $roleAssignments,
            'userAssignments' => $userAssignments
        ]);
    }

    public function update($params) {
        $this->checkCsrf();
        $formId = (int)$params['id'];
        $data = Request::all();

        $form = Form::findById($formId);
        if (!$form) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            // 1. Sync Role assignments
            $delRoles = $db->prepare("DELETE FROM form_role_assignments WHERE form_id = ?");
            $delRoles->execute([$formId]);

            if (isset($data['roles']) && is_array($data['roles'])) {
                $insRole = $db->prepare("
                    INSERT INTO form_role_assignments (form_id, role_id, can_view, can_submit, created_by)
                    VALUES (?, ?, 1, 1, ?)
                ");
                foreach ($data['roles'] as $roleId) {
                    $insRole->execute([$formId, (int)$roleId, Auth::id()]);
                }
            }

            // 2. Sync User assignments
            $delUsers = $db->prepare("DELETE FROM form_user_assignments WHERE form_id = ?");
            $delUsers->execute([$formId]);

            if (isset($data['users']) && is_array($data['users'])) {
                $insUser = $db->prepare("
                    INSERT INTO form_user_assignments (form_id, user_id, can_view, can_submit, created_by)
                    VALUES (?, ?, 1, 1, ?)
                ");
                foreach ($data['users'] as $userId) {
                    $insUser->execute([$formId, (int)$userId, Auth::id()]);
                }
            }

            $db->commit();
            $this->logAudit('update_assignments', 'forms', $formId, []);
            Session::flash('success', 'Οι αναθέσεις της φόρμας ενημερώθηκαν.');
        } catch (\Exception $e) {
            $db->rollBack();
            Session::flash('error', 'Σφάλμα αναθέσεων: ' . $e->getMessage());
        }

        $this->redirect('/admin/forms');
    }
}
