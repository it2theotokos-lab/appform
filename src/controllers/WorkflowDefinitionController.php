<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Core\View;
use App\Core\Database;
use App\Core\Request;
use PDO;

class WorkflowDefinitionController extends Controller {
    public function index() {
        $db = Database::getInstance();
        $stmt = $db->query("
            SELECT w.*, t.title as template_title, u.full_name as creator_name
            FROM workflow_definitions w
            LEFT JOIN document_templates t ON w.document_template_id = t.id
            JOIN users u ON w.created_by = u.id
            ORDER BY w.id DESC
        ");
        View::render('workflow_definitions/index', [
            'title' => 'Ορισμοί Workflows',
            'workflows' => $stmt->fetchAll()
        ]);
    }

    public function create() {
        $db = Database::getInstance();
        $templates = $db->query("SELECT id, title FROM document_templates ORDER BY title ASC")->fetchAll();
        $forms = $db->query("SELECT id, title FROM forms ORDER BY title ASC")->fetchAll();
        View::render('workflow_definitions/create', [
            'title' => 'Δημιουργία Workflow',
            'templates' => $templates,
            'forms' => $forms
        ]);
    }

    public function store() {
        $this->checkCsrf();
        $data = Request::all();

        if (empty($data['name'])) {
            Session::flash('error', 'Το όνομα είναι υποχρεωτικό.');
            $this->back();
        }

        try {
            $db = Database::getInstance();
            $entityType = $data['entity_type'] ?? 'document';
            $entityId = ($entityType === 'form') ? (!empty($data['form_id']) ? (int)$data['form_id'] : null) : (!empty($data['document_template_id']) ? (int)$data['document_template_id'] : null);

            $stmt = $db->prepare("
                INSERT INTO workflow_definitions (name, description, document_template_id, entity_type, entity_id, is_active, version, created_by)
                VALUES (?, ?, ?, ?, ?, 1, 1, ?)
            ");
            $stmt->execute([
                $data['name'],
                $data['description'] ?? null,
                ($entityType === 'document') ? $entityId : null,
                $entityType,
                $entityId,
                Auth::id()
            ]);
            $id = $db->lastInsertId();

            Session::flash('success', 'Ο ορισμός workflow δημιουργήθηκε επιτυχώς.');
            $this->redirect("/admin/workflows/{$id}/edit");
        } catch (\Exception $e) {
            Session::flash('error', $e->getMessage());
            $this->back();
        }
    }

    public function edit(array $params) {
        $id = (int)($params['id'] ?? 0);
        $db = Database::getInstance();
        
        $stmt = $db->prepare("SELECT * FROM workflow_definitions WHERE id = ?");
        $stmt->execute([$id]);
        $wf = $stmt->fetch();

        if (!$wf) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $templates = $db->query("SELECT id, title FROM document_templates ORDER BY title ASC")->fetchAll();
        $roles = $db->query("SELECT id, name FROM roles ORDER BY name ASC")->fetchAll();
        $users = $db->query("SELECT id, full_name as name FROM users ORDER BY full_name ASC")->fetchAll();
        $forms = $db->query("SELECT id, title FROM forms ORDER BY title ASC")->fetchAll();

        // Get steps
        $stmtSteps = $db->prepare("SELECT * FROM workflow_steps WHERE workflow_definition_id = ? ORDER BY step_order ASC");
        $stmtSteps->execute([$id]);
        $steps = $stmtSteps->fetchAll();

        View::render('workflow_definitions/edit', [
            'title' => 'Σχεδίαση Workflow: ' . $wf['name'],
            'wf' => $wf,
            'steps' => $steps,
            'templates' => $templates,
            'roles' => $roles,
            'users' => $users,
            'forms' => $forms
        ]);
    }

    public function update(array $params) {
        $this->checkCsrf();
        $id = (int)($params['id'] ?? 0);
        $data = Request::all();

        try {
            $db = Database::getInstance();
            $db->beginTransaction();

            // 1. Update basic fields
            $entityType = $data['entity_type'] ?? 'document';
            $entityId = ($entityType === 'form') ? (!empty($data['form_id']) ? (int)$data['form_id'] : null) : (!empty($data['document_template_id']) ? (int)$data['document_template_id'] : null);

            $stmt = $db->prepare("
                UPDATE workflow_definitions 
                SET name = ?, description = ?, document_template_id = ?, entity_type = ?, entity_id = ? 
                WHERE id = ?
            ");
            $stmt->execute([
                $data['name'],
                $data['description'] ?? null,
                ($entityType === 'document') ? $entityId : null,
                $entityType,
                $entityId,
                $id
            ]);

            // Map workflow to entity active columns
            if ($entityId) {
                if ($entityType === 'form') {
                    $stmtFrm = $db->prepare("UPDATE forms SET active_workflow_definition_id = ? WHERE id = ?");
                    $stmtFrm->execute([$id, $entityId]);
                } else {
                    $stmtTpl = $db->prepare("UPDATE document_templates SET active_workflow_definition_id = ? WHERE id = ?");
                    $stmtTpl->execute([$id, $entityId]);
                }
            }

            // 2. Synchronize steps payload JSON
            $stepsJson = $data['steps_data'] ?? '[]';
            $steps = json_decode($stepsJson, true) ?: [];

            // Delete old steps
            $stmtDel = $db->prepare("DELETE FROM workflow_steps WHERE workflow_definition_id = ?");
            $stmtDel->execute([$id]);

            // Re-insert steps keeping step_order
            $stmtIns = $db->prepare("
                INSERT INTO workflow_steps (
                    workflow_definition_id, step_order, name, step_type, assignment_type, 
                    assigned_user_id, assigned_role_id, requires_signature, signature_field_key, 
                    approval_mode, due_days, allow_return, allow_reject, is_required
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");

            foreach ($steps as $idx => $step) {
                $stmtIns->execute([
                    $id,
                    $idx + 1,
                    $step['name'] ?? 'Βήμα ' . ($idx + 1),
                    $step['step_type'] ?? 'approval',
                    $step['assignment_type'] ?? 'role',
                    !empty($step['assigned_user_id']) ? (int)$step['assigned_user_id'] : null,
                    !empty($step['assigned_role_id']) ? (int)$step['assigned_role_id'] : null,
                    !empty($step['requires_signature']) ? 1 : 0,
                    $step['signature_field_key'] ?? null,
                    $step['approval_mode'] ?? 'sequential',
                    !empty($step['due_days']) ? (int)$step['due_days'] : null,
                    isset($step['allow_return']) ? (int)$step['allow_return'] : 1,
                    isset($step['allow_reject']) ? (int)$step['allow_reject'] : 1
                ]);
            }

            $db->commit();
            Session::flash('success', 'Ο σχεδιασμός του workflow αποθηκεύτηκε επιτυχώς.');
            $this->redirect("/admin/workflows/{$id}/edit");
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Session::flash('error', $e->getMessage());
            $this->back();
        }
    }

    public function publish(array $params) {
        $this->checkCsrf();
        $id = (int)($params['id'] ?? 0);
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("UPDATE workflow_definitions SET is_active = 1 WHERE id = ?");
            $stmt->execute([$id]);
            Session::flash('success', 'Το workflow ενεργοποιήθηκε επιτυχώς.');
        } catch (\Exception $e) {
            Session::flash('error', $e->getMessage());
        }
        $this->redirect('/admin/workflows');
    }

    public function archive(array $params) {
        $this->checkCsrf();
        $id = (int)($params['id'] ?? 0);
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("UPDATE workflow_definitions SET is_active = 0 WHERE id = ?");
            $stmt->execute([$id]);
            Session::flash('success', 'Το workflow αρχειοθετήθηκε/απενεργοποιήθηκε επιτυχώς.');
        } catch (\Exception $e) {
            Session::flash('error', $e->getMessage());
        }
        $this->redirect('/admin/workflows');
    }
}
