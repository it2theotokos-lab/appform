<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Core\View;
use App\Core\Database;
use App\Core\Request;
use PDO;

class WorkflowTaskController extends Controller {
    private function checkMaintenance() {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'maintenance_mode'");
        $isMaint = (int)$stmt->fetchColumn() === 1;
        if ($isMaint && Auth::role() !== 'administrator') {
            Session::flash('error', 'Η εφαρμογή βρίσκεται προσωρινά σε λειτουργία συντήρησης.');
            $this->redirect('/dashboard');
            exit;
        }
    }

    public function index() {
        $this->checkMaintenance();
        $userId = Auth::id();
        $db = Database::getInstance();

        // Fetch assigned tasks. If user is admin/manager, they can view more.
        // We load user specific tasks where status = 'active'
        $stmt = $db->prepare("
            SELECT wsi.*, wi.document_instance_id, wi.current_step_order, ws.name as step_name, ws.step_type, i.document_number, i.title as doc_title, t.title as template_title, u.full_name as creator_name
            FROM workflow_step_instances wsi
            JOIN workflow_instances wi ON wsi.workflow_instance_id = wi.id
            JOIN workflow_steps ws ON wsi.workflow_step_id = ws.id
            JOIN document_instances i ON wi.document_instance_id = i.id
            JOIN document_templates t ON i.document_template_id = t.id
            JOIN users u ON i.created_by = u.id
            WHERE wsi.assigned_user_id = ?
            ORDER BY wsi.id DESC
        ");
        $stmt->execute([$userId]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        View::render('workflow_tasks/index', [
            'title' => 'Εκκρεμείς Ενέργειες Έγκρισης',
            'tasks' => $tasks
        ]);
    }

    public function show(array $params) {
        $this->checkMaintenance();
        $id = (int)($params['id'] ?? 0);
        $userId = Auth::id();

        $data = \App\Services\DocumentViewService::loadWorkflowDocument($id);

        if (empty($data)) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $task = $data['task'];

        // Authorize: only assigned user can view/act
        if ((int)$task['assigned_user_id'] !== $userId && Auth::role() !== 'administrator') {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }

        View::render('workflow_tasks/show', [
            'title' => 'Εργασία Έγκρισης: ' . $task['document_number'],
            'task' => $task,
            'values' => $data['values'],
            'comments' => $data['comments'],
            'history' => $data['history'],
            'pdfUrl' => "/documents/{$task['document_instance_id']}/preview"
        ], 'focus');
    }

    public function decide(array $params) {
        $this->checkCsrf();
        $id = (int)($params['id'] ?? 0);
        $userId = Auth::id();

        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT wsi.* 
            FROM workflow_step_instances wsi
            WHERE wsi.id = ?
        ");
        $stmt->execute([$id]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$task) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        // Authorize: only assigned user or administrator may decide
        if ((int)$task['assigned_user_id'] !== $userId && Auth::role() !== 'administrator') {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }

        $data = Request::all();
        $decision = $data['decision'] ?? ''; // 'approved', 'rejected', 'returned'
        $comment = $data['comment'] ?? '';
        $signatureId = !empty($data['signature_id']) ? (int)$data['signature_id'] : null;

        if (!in_array($decision, ['approved', 'rejected', 'returned'])) {
            Session::flash('error', 'Μη έγκυρη απόφαση.');
            $this->back();
        }

        if (($decision === 'rejected' || $decision === 'returned') && empty(trim($comment))) {
            Session::flash('error', 'Το σχόλιο είναι υποχρεωτικό για απόρριψη ή επιστροφή.');
            $this->back();
        }

        $res = \App\Services\WorkflowEngineService::processDecision($id, $decision, $comment, $signatureId, Auth::id());

        if ($res['success']) {
            Session::flash('success', 'Η ενέργεια ολοκληρώθηκε επιτυχώς.');
            $this->redirect('/workflow/tasks');
        } else {
            Session::flash('error', $res['message']);
            $this->back();
        }
    }

    public function reassign(array $params) {
        $this->checkCsrf();
        if (Auth::role() !== 'administrator') {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }

        $id = (int)($params['id'] ?? 0);
        $data = Request::all();

        $newUserId = !empty($data['new_assigned_user_id']) ? (int)$data['new_assigned_user_id'] : 0;
        $reason = $data['reason'] ?? '';

        if (!$newUserId || empty(trim($reason))) {
            Session::flash('error', 'Η επιλογή χρήστη και η αιτιολογία είναι υποχρεωτικά πεδία.');
            $this->back();
        }

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("
                SELECT wsi.*, wi.document_instance_id, ws.name as step_name, i.document_number
                FROM workflow_step_instances wsi
                JOIN workflow_instances wi ON wsi.workflow_instance_id = wi.id
                JOIN workflow_steps ws ON wsi.workflow_step_id = ws.id
                JOIN document_instances i ON wi.document_instance_id = i.id
                WHERE wsi.id = ? FOR UPDATE
            ");
            $stmt->execute([$id]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$task || $task['status'] !== 'active') {
                $db->rollBack();
                http_response_code(409);
                Session::flash('error', 'Η εργασία δεν είναι πλέον ενεργή.');
                $this->back();
            }

            // Verify target user is active
            $stmtUser = $db->prepare("SELECT id, full_name FROM users WHERE id = ? AND is_active = 1");
            $stmtUser->execute([$newUserId]);
            $newUser = $stmtUser->fetch();
            if (!$newUser) {
                $db->rollBack();
                Session::flash('error', 'Ο επιλεγμένος χρήστης δεν βρέθηκε ή είναι ανενεργός.');
                $this->back();
            }

            $oldUserId = (int)$task['assigned_user_id'];

            // Update assignment
            $stmtUpd = $db->prepare("UPDATE workflow_step_instances SET assigned_user_id = ? WHERE id = ?");
            $stmtUpd->execute([$newUserId, $id]);

            // Save workflow comment for reassignment
            $stmtComm = $db->prepare("
                INSERT INTO workflow_comments (workflow_instance_id, workflow_step_instance_id, document_instance_id, user_id, comment)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmtComm->execute([
                $task['workflow_instance_id'],
                $id,
                $task['document_instance_id'],
                Auth::id(),
                "Επανανάθεση εργασίας από " . Auth::user()['full_name'] . ". Αιτιολογία: {$reason}"
            ]);

            // Audit reassignment
            $stmtAudit = $db->prepare("
                INSERT INTO audit_logs (action, entity_type, entity_id, metadata_json, user_id, ip_address, user_agent, created_at)
                VALUES ('workflow.task.reassigned', 'document_instances', ?, ?, ?, ?, ?, NOW())
            ");
            $stmtAudit->execute([
                $task['document_instance_id'],
                json_encode([
                    'task_id' => $id,
                    'old_user_id' => $oldUserId,
                    'new_user_id' => $newUserId,
                    'reason' => $reason
                ]),
                Auth::id(),
                Request::ip(),
                Request::userAgent()
            ]);

            // Notify old and new assignees
            try {
                \App\Services\LifecycleNotificationService::notifyDocumentLifecycle(
                    'workflow_reassigned_old',
                    [
                        'id' => $task['document_instance_id'],
                        'document_number' => $task['document_number'],
                        'step_name' => $task['step_name'],
                        'old_user_id' => $oldUserId
                    ],
                    Auth::id()
                );
                \App\Services\LifecycleNotificationService::notifyDocumentLifecycle(
                    'workflow_reassigned_new',
                    [
                        'id' => $task['document_instance_id'],
                        'document_number' => $task['document_number'],
                        'step_name' => $task['step_name'],
                        'new_user_id' => $newUserId,
                        'task_instance_id' => $id
                    ],
                    Auth::id()
                );
            } catch (\Exception $e) {}

            $db->commit();
            Session::flash('success', 'Η εργασία επανανατέθηκε επιτυχώς.');
            $this->redirect("/workflow/tasks/{$id}");
        } catch (\Exception $e) {
            $db->rollBack();
            Session::flash('error', 'Σφάλμα κατά την επανανάθεση: ' . $e->getMessage());
            $this->back();
        }
    }

    public function cancel(array $params) {
        $this->checkCsrf();
        if (Auth::role() !== 'administrator') {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }

        $id = (int)($params['id'] ?? 0); // Workflow instance ID
        $data = Request::all();
        $reason = $data['reason'] ?? '';

        if (empty(trim($reason))) {
            Session::flash('error', 'Η αιτιολογία ακύρωσης είναι υποχρεωτική.');
            $this->back();
        }

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("
                SELECT wi.*, i.document_number, i.created_by as doc_creator_id
                FROM workflow_instances wi
                JOIN document_instances i ON wi.document_instance_id = i.id
                WHERE wi.id = ? FOR UPDATE
            ");
            $stmt->execute([$id]);
            $wf = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$wf || !in_array($wf['status'], ['active', 'blocked'])) {
                $db->rollBack();
                http_response_code(409);
                Session::flash('error', 'Το workflow δεν είναι σε ενεργή ή μπλοκαρισμένη κατάσταση.');
                $this->back();
            }

            $docId = (int)$wf['document_instance_id'];

            // Cancel active and pending tasks count
            $stmtTasks = $db->prepare("SELECT COUNT(*) FROM workflow_step_instances WHERE workflow_instance_id = ? AND status IN ('active', 'pending')");
            $stmtTasks->execute([$id]);
            $openTasksCount = $stmtTasks->fetchColumn();

            $stmtCancelTasks = $db->prepare("UPDATE workflow_step_instances SET status = 'cancelled' WHERE workflow_instance_id = ? AND status IN ('active', 'pending')");
            $stmtCancelTasks->execute([$id]);

            // Update workflow status
            $stmtUpdWf = $db->prepare("UPDATE workflow_instances SET status = 'cancelled', completed_at = NOW() WHERE id = ?");
            $stmtUpdWf->execute([$id]);

            // Update document status
            $stmtUpdDoc = $db->prepare("UPDATE document_instances SET status = 'cancelled' WHERE id = ?");
            $stmtUpdDoc->execute([$docId]);

            // Save cancellation comment
            $stmtComm = $db->prepare("
                INSERT INTO workflow_comments (workflow_instance_id, document_instance_id, user_id, comment)
                VALUES (?, ?, ?, ?)
            ");
            $stmtComm->execute([
                $id,
                $docId,
                Auth::id(),
                "Ακύρωση workflow από " . Auth::user()['full_name'] . ". Αιτιολογία: {$reason}"
            ]);

            // Log Audit
            $stmtAudit = $db->prepare("
                INSERT INTO audit_logs (action, entity_type, entity_id, metadata_json, user_id, ip_address, user_agent, created_at)
                VALUES ('workflow.cancelled', 'document_instances', ?, ?, ?, ?, ?, NOW())
            ");
            $stmtAudit->execute([
                $docId,
                json_encode([
                    'workflow_instance_id' => $id,
                    'reason' => $reason,
                    'cancelled_tasks_count' => $openTasksCount
                ]),
                Auth::id(),
                Request::ip(),
                Request::userAgent()
            ]);

            // Notify Creator
            try {
                \App\Services\LifecycleNotificationService::notifyDocumentLifecycle(
                    'workflow_cancelled',
                    [
                        'id' => $docId,
                        'document_number' => $wf['document_number'],
                        'created_by' => $wf['doc_creator_id']
                    ],
                    Auth::id()
                );
            } catch (\Exception $e) {}

            $db->commit();
            Session::flash('success', 'Το workflow ακυρώθηκε επιτυχώς.');
            $this->redirect("/documents/{$docId}");
        } catch (\Exception $e) {
            $db->rollBack();
            Session::flash('error', 'Σφάλμα κατά την ακύρωση: ' . $e->getMessage());
            $this->back();
        }
    }
}
