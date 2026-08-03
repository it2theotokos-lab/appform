<?php
namespace App\Services;

use App\Core\Database;
use App\Services\NotificationService;
use PDO;

class WorkflowEngineService {
    public static function start(int $entityId, int $creatorId, string $entityType = 'document'): bool {
        $db = Database::getInstance();

        $workflowDefId = null;
        $documentNumber = 'SUB-' . $entityId;

        if ($entityType === 'form') {
            // Resolve form active workflow definition
            $stmtFrm = $db->prepare("
                SELECT f.active_workflow_definition_id, f.title 
                FROM form_submissions s
                JOIN forms f ON s.form_id = f.id
                WHERE s.id = ?
            ");
            $stmtFrm->execute([$entityId]);
            $res = $stmtFrm->fetch(PDO::FETCH_ASSOC);
            if ($res && $res['active_workflow_definition_id']) {
                $workflowDefId = (int)$res['active_workflow_definition_id'];
                $documentNumber = 'FORM-' . $entityId;
            }
        } else {
            // Resolve template active workflow definition
            $stmtTpl = $db->prepare("
                SELECT t.active_workflow_definition_id, i.document_number
                FROM document_instances i
                JOIN document_templates t ON i.document_template_id = t.id
                WHERE i.id = ?
            ");
            $stmtTpl->execute([$entityId]);
            $res = $stmtTpl->fetch(PDO::FETCH_ASSOC);
            if ($res && $res['active_workflow_definition_id']) {
                $workflowDefId = (int)$res['active_workflow_definition_id'];
                $documentNumber = $res['document_number'];
            }
        }

        if (!$workflowDefId) {
            return false;
        }

        // Check if workflow instance already exists for this entity to enforce single instance policy
        $stmtFindWf = $db->prepare("SELECT id FROM workflow_instances WHERE entity_type = ? AND entity_id = ?");
        $stmtFindWf->execute([$entityType, $entityId]);
        $wfInstanceId = $stmtFindWf->fetchColumn();

        if ($wfInstanceId) {
            // Reuse the existing workflow instance: reactivate it and reset current step order to 1
            $stmtUpWf = $db->prepare("
                UPDATE workflow_instances 
                SET status = 'active', current_step_order = 1, completed_at = NULL 
                WHERE id = ?
            ");
            $stmtUpWf->execute([$wfInstanceId]);

            // Clear previous step instances to reset progress
            $stmtDelSteps = $db->prepare("DELETE FROM workflow_step_instances WHERE workflow_instance_id = ?");
            $stmtDelSteps->execute([$wfInstanceId]);
        } else {
            // Create workflow instance record
            $stmtInst = $db->prepare("
                INSERT INTO workflow_instances (document_instance_id, entity_type, entity_id, workflow_definition_id, current_step_order, status, started_at)
                VALUES (?, ?, ?, ?, 1, 'active', NOW())
            ");
            $stmtInst->execute([$entityType === 'document' ? $entityId : null, $entityType, $entityId, $workflowDefId]);
            $wfInstanceId = $db->lastInsertId();
        }

        // Load steps order definition
        $stmtSteps = $db->prepare("SELECT * FROM workflow_steps WHERE workflow_definition_id = ? ORDER BY step_order ASC");
        $stmtSteps->execute([$workflowDefId]);
        $steps = $stmtSteps->fetchAll(PDO::FETCH_ASSOC);

        if (empty($steps)) {
            // Blocked state: workflow has no steps
            self::logAuditStatic($db, 'workflow.blocked', $entityType . '_instances', $entityId, [
                'error' => 'Ορισμός workflow χωρίς βήματα.',
                'workflow_definition_id' => $workflowDefId
            ], $creatorId);
            return false;
        }

        // Update target entity status state
        if ($entityType === 'form') {
            $stmtSub = $db->prepare("UPDATE form_submissions SET status = 'submitted', submitted_at = NOW() WHERE id = ?");
            $stmtSub->execute([$entityId]);
        } else {
            $stmtDoc = $db->prepare("UPDATE document_instances SET status = 'in_review', submitted_at = NOW() WHERE id = ?");
            $stmtDoc->execute([$entityId]);
        }

        // 4. Generate step instances for the entire workflow definition structure
        $stepInstanceIds = [];
        foreach ($steps as $step) {
            $assignedUsers = self::resolveAssignees($db, $step, $creatorId);
            
            // For parallel_all/parallel_any step options we might create multiple step instances or duplicate entries
            // If parallel, we create one workflow_step_instance per resolved user. 
            // If sequential or sequential role, we resolve one or more. If role assignment resolved multiple users,
            // we split tasks or default to multiple assignees (or single assignee if role has multiple users).
            if (empty($assignedUsers)) {
                // Keep step instance but mark pending without assigned user, which blocks workflow execution
                $stmtStepInst = $db->prepare("
                    INSERT INTO workflow_step_instances (workflow_instance_id, workflow_step_id, step_order, assigned_role_id, status)
                    VALUES (?, ?, ?, ?, 'pending')
                ");
                $stmtStepInst->execute([$wfInstanceId, $step['id'], $step['step_order'], $step['assigned_role_id']]);
            } else {
                foreach ($assignedUsers as $uId) {
                    $stmtStepInst = $db->prepare("
                        INSERT INTO workflow_step_instances (workflow_instance_id, workflow_step_id, step_order, assigned_user_id, assigned_role_id, status)
                        VALUES (?, ?, ?, ?, ?, 'pending')
                    ");
                    $stmtStepInst->execute([$wfInstanceId, $step['id'], $step['step_order'], $uId, $step['assigned_role_id']]);
                }
            }
        }

        // 5. Activate first step order tasks
        self::activateStepOrder($db, $wfInstanceId, 1, $entityId, $documentNumber, $creatorId, $entityType);

        self::logAuditStatic($db, 'workflow.created', $entityType . '_instances', $entityId, [
            'workflow_instance_id' => $wfInstanceId,
            'workflow_definition_id' => $workflowDefId
        ], $creatorId);

        self::logAuditStatic($db, 'workflow.started', $entityType . '_instances', $entityId, [
            'workflow_instance_id' => $wfInstanceId
        ], $creatorId);

        return true;
    }

    /**
     * Resolves assignees based on assignment type
     */
    private static function resolveAssignees(PDO $db, array $step, int $creatorId): array {
        $users = [];
        switch ($step['assignment_type']) {
            case 'user':
                $users = $step['assigned_user_id'] ? [$step['assigned_user_id']] : [];
                break;
            case 'role':
                if ($step['assigned_role_id']) {
                    $stmt = $db->prepare("SELECT id FROM users WHERE role_id = ? AND is_active = 1");
                    $stmt->execute([$step['assigned_role_id']]);
                    $users = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
                }
                break;
            case 'document_creator':
                $users = [$creatorId];
                break;
            case 'administrator':
                $stmt = $db->prepare("SELECT u.id FROM users u JOIN roles r ON u.role_id = r.id WHERE r.slug = 'administrator' AND u.is_active = 1");
                $stmt->execute();
                $users = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
                break;
            case 'manager':
                $stmt = $db->prepare("SELECT u.id FROM users u JOIN roles r ON u.role_id = r.id WHERE r.slug = 'manager' AND u.is_active = 1");
                $stmt->execute();
                $users = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
                break;
            default:
                $users = [];
                break;
        }

        // Apply self-approval protection: exclude creator from tasks list by default
        $config = json_decode($step['config_json'] ?? '{}', true);
        $allowCreatorAction = $config['allow_creator_action'] ?? false;
        
        if (!$allowCreatorAction) {
            $users = array_values(array_filter($users, function($u) use ($creatorId) {
                return (int)$u !== $creatorId;
            }));
        }

        return $users;
    }

    public static function activateStepOrder(PDO $db, int $wfInstanceId, int $stepOrder, int $entityId, string $documentNumber, int $actorId, string $entityType = 'document') {
        // Update pending step instances of this step_order to active
        $stmt = $db->prepare("
            SELECT wsi.*, ws.due_days, ws.step_type, ws.name as step_name
            FROM workflow_step_instances wsi
            JOIN workflow_steps ws ON wsi.workflow_step_id = ws.id
            WHERE wsi.workflow_instance_id = ? AND wsi.step_order = ? AND wsi.status = 'pending'
        ");
        $stmt->execute([$wfInstanceId, $stepOrder]);
        $instances = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($instances)) {
            // Check if there are missing assignees causing block
            $stmtCheck = $db->prepare("SELECT COUNT(*) FROM workflow_step_instances WHERE workflow_instance_id = ? AND step_order = ?");
            $stmtCheck->execute([$wfInstanceId, $stepOrder]);
            $count = $stmtCheck->fetchColumn();
            
            if ($count > 0) {
                // Step order has tasks, but they are blocked/no user resolved
                $stmtBlock = $db->prepare("UPDATE workflow_step_instances SET status = 'blocked' WHERE workflow_instance_id = ? AND step_order = ? AND status = 'pending'");
                $stmtBlock->execute([$wfInstanceId, $stepOrder]);

                $stmtWfB = $db->prepare("UPDATE workflow_instances SET status = 'blocked' WHERE id = ?");
                $stmtWfB->execute([$wfInstanceId]);

                self::logAuditStatic($db, 'workflow.blocked', $entityType . '_instances', $entityId, [
                    'workflow_instance_id' => $wfInstanceId,
                    'step_order' => $stepOrder,
                    'error' => 'Δεν βρέθηκε εγγεγραμμένος χρήστης για την ανάθεση.'
                ], $actorId);

                // Notify Admin
                $stmtAdmin = $db->prepare("SELECT id FROM users WHERE role_id = (SELECT id FROM roles WHERE slug = 'administrator') LIMIT 1");
                $stmtAdmin->execute();
                $admin = $stmtAdmin->fetch();
                if ($admin) {
                    try {
                        $redirectUrl = ($entityType === 'form') ? "/admin/submissions" : "/documents/{$entityId}";
                        NotificationService::notify(
                            $admin['id'],
                            'danger',
                            'Μπλοκαρισμένο Workflow',
                            "Το workflow του εγγράφου/φόρμας {$documentNumber} έχει μπλοκάρει στο βήμα {$stepOrder}.",
                            $redirectUrl
                        );
                    } catch (\Exception $e) {}
                }
                return;
            }
        }

        foreach ($instances as $inst) {
            $dueAt = null;
            if ($inst['due_days']) {
                $dueAt = date('Y-m-d H:i:s', strtotime("+{$inst['due_days']} days"));
            }

            $stmtAct = $db->prepare("UPDATE workflow_step_instances SET status = 'active', due_at = ? WHERE id = ?");
            $stmtAct->execute([$dueAt, $inst['id']]);

            self::logAuditStatic($db, 'workflow.step.activated', $entityType . '_instances', $entityId, [
                'workflow_step_instance_id' => $inst['id'],
                'step_name' => $inst['step_name']
            ], $actorId);

            if ($inst['assigned_user_id']) {
                self::logAuditStatic($db, 'workflow.task.assigned', $entityType . '_instances', $entityId, [
                    'workflow_step_instance_id' => $inst['id'],
                    'assigned_user_id' => $inst['assigned_user_id']
                ], $actorId);

                // Send notification
                try {
                    \App\Services\LifecycleNotificationService::notifyDocumentLifecycle(
                        'workflow_task_assigned',
                        [
                            'id' => $entityId,
                            'document_number' => $documentNumber,
                            'assigned_user_id' => $inst['assigned_user_id'],
                            'step_name' => $inst['step_name'],
                            'step_type' => $inst['step_type'],
                            'task_instance_id' => $inst['id']
                        ],
                        $actorId
                    );
                } catch (\Exception $e) {}
            }
        }

        // Trigger workflow_step notifications if entity is a form submission
        if ($entityType === 'form') {
            try {
                $stmtSub = $db->prepare("SELECT uuid, form_id, data_json FROM form_submissions WHERE id = ?");
                $stmtSub->execute([$entityId]);
                $subData = $stmtSub->fetch(PDO::FETCH_ASSOC);
                if ($subData) {
                    $subDetails = Submission::getDetailsByUuid($subData['uuid']);
                    $answers = json_decode($subData['data_json'], true) ?: [];
                    \App\Services\FormNotificationTriggerService::trigger((int)$subData['form_id'], 'workflow_step', $subDetails, $answers);
                }
            } catch (\Throwable $exStepNotif) {}
        }
    }

    /**
     * Transition step decision (approved, rejected, returned) and advance workflow
     */
    public static function processDecision(int $stepInstId, string $decision, ?string $comment, ?int $signatureId, int $actorId): array {
        $db = Database::getInstance();

        // Lock row/transaction control
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("
                SELECT wsi.*, wi.document_instance_id, wi.entity_type, wi.entity_id, wi.workflow_definition_id, wi.current_step_order,
                       ws.name as step_name, ws.approval_mode, ws.step_type, ws.allow_reject, ws.allow_return, ws.signature_field_key
                FROM workflow_step_instances wsi
                JOIN workflow_instances wi ON wsi.workflow_instance_id = wi.id
                JOIN workflow_steps ws ON wsi.workflow_step_id = ws.id
                WHERE wsi.id = ? FOR UPDATE
            ");
            $stmt->execute([$stepInstId]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$task || $task['status'] !== 'active') {
                $db->rollBack();
                return ['success' => false, 'error' => 'invalid_state', 'message' => 'Αυτή η εργασία δεν είναι πλέον ενεργή.'];
            }

            $entityType = $task['entity_type'] ?? 'document';
            $entityId = (int)$task['entity_id'];
            $wfInstanceId = (int)$task['workflow_instance_id'];
            $currentOrder = (int)$task['current_step_order'];

            $docNum = 'SUB-' . $entityId;
            $creatorId = 0;

            if ($entityType === 'form') {
                $stmtSub = $db->prepare("SELECT user_id FROM form_submissions WHERE id = ?");
                $stmtSub->execute([$entityId]);
                $creatorId = (int)$stmtSub->fetchColumn();
                $docNum = 'SUB-' . $entityId;
            } else {
                $stmtDoc = $db->prepare("SELECT document_number, created_by FROM document_instances WHERE id = ?");
                $stmtDoc->execute([$entityId]);
                $doc = $stmtDoc->fetch();
                if ($doc) {
                    $creatorId = (int)$doc['created_by'];
                    $docNum = $doc['document_number'];
                }
            }
            $task['doc_creator_id'] = $creatorId;
            $task['document_number'] = $docNum;

            // Validate signature requirements
            if ($task['step_type'] === 'signature' && $decision === 'approved' && !$signatureId) {
                $db->rollBack();
                return ['success' => false, 'error' => 'signature_required', 'message' => 'Απαιτείται υπογραφή για την ολοκλήρωση αυτού του βήματος.'];
            }

            // Save comment if provided
            if ($comment !== null && trim($comment) !== '') {
                $stmtComm = $db->prepare("
                    INSERT INTO workflow_comments (workflow_instance_id, workflow_step_instance_id, document_instance_id, user_id, comment)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmtComm->execute([$wfInstanceId, $stepInstId, $entityType === 'document' ? $entityId : null, $actorId, $comment]);
            }

            if ($decision === 'approved') {
                // Update current task status
                $stmtUpd = $db->prepare("
                    UPDATE workflow_step_instances 
                    SET status = 'approved', decision = 'approved', acted_by = ?, acted_at = NOW(), signature_id = ?
                    WHERE id = ?
                ");
                $stmtUpd->execute([$actorId, $signatureId, $stepInstId]);

                self::logAuditStatic($db, 'workflow.task.approved', $entityType . '_instances', $entityId, [
                    'workflow_step_instance_id' => $stepInstId,
                    'signature_id' => $signatureId
                ], $actorId);

                // Handle parallel/sequential group progression
                $stmtGroup = $db->prepare("
                    SELECT id, status FROM workflow_step_instances 
                    WHERE workflow_instance_id = ? AND step_order = ?
                ");
                $stmtGroup->execute([$wfInstanceId, $currentOrder]);
                $groupTasks = $stmtGroup->fetchAll(PDO::FETCH_ASSOC);

                $allApproved = true;
                $anyApproved = false;
                foreach ($groupTasks as $t) {
                    if ($t['status'] === 'approved') {
                        $anyApproved = true;
                    } else {
                        $allApproved = false;
                    }
                }

                $advance = false;
                if ($task['approval_mode'] === 'parallel_any' && $anyApproved) {
                    // Skip remaining active tasks in group
                    $stmtSkip = $db->prepare("
                        UPDATE workflow_step_instances 
                        SET status = 'skipped' 
                        WHERE workflow_instance_id = ? AND step_order = ? AND status = 'active'
                    ");
                    $stmtSkip->execute([$wfInstanceId, $currentOrder]);
                    $advance = true;
                } elseif ($task['approval_mode'] === 'parallel_all' && $allApproved) {
                    $advance = true;
                } elseif ($task['approval_mode'] === 'sequential') {
                    if ($allApproved) {
                        $advance = true;
                    }
                }

                if ($advance) {
                    // Resolve if next step order exists
                    $stmtNext = $db->prepare("
                        SELECT DISTINCT step_order 
                        FROM workflow_step_instances 
                        WHERE workflow_instance_id = ? AND step_order > ? 
                        ORDER BY step_order ASC LIMIT 1
                    ");
                    $stmtNext->execute([$wfInstanceId, $currentOrder]);
                    $nextOrder = $stmtNext->fetchColumn();

                    if ($nextOrder) {
                        // Advance to next step
                        $stmtUpWf = $db->prepare("UPDATE workflow_instances SET current_step_order = ? WHERE id = ?");
                        $stmtUpWf->execute([$nextOrder, $wfInstanceId]);

                        self::activateStepOrder($db, $wfInstanceId, $nextOrder, $entityId, $docNum, $actorId, $entityType);
                    } else {
                        // Workflow complete!
                        $stmtUpWf = $db->prepare("UPDATE workflow_instances SET status = 'completed', completed_at = NOW() WHERE id = ?");
                        $stmtUpWf->execute([$wfInstanceId]);

                        if ($entityType === 'form') {
                            $stmtSub = $db->prepare("UPDATE form_submissions SET status = 'approved' WHERE id = ?");
                            $stmtSub->execute([$entityId]);

                            $hist = $db->prepare("
                                INSERT INTO submission_status_history (submission_id, old_status, new_status, notes, changed_by)
                                VALUES (?, 'under_review', 'approved', ?, ?)
                            ");
                            $hist->execute([$entityId, $comment ?: 'Εγκρίθηκε μέσω Workflow', $actorId]);

                            self::logAuditStatic($db, 'workflow.completed', 'form_submissions', $entityId, [
                                'workflow_instance_id' => $wfInstanceId
                            ], $actorId);

                            // Trigger form notification logs matching approved status
                            try {
                                $stmtFindUuid = $db->prepare("SELECT uuid, form_id, data_json FROM form_submissions WHERE id = ?");
                                $stmtFindUuid->execute([$entityId]);
                                $subData = $stmtFindUuid->fetch(PDO::FETCH_ASSOC);
                                if ($subData) {
                                    $subDetails = Submission::getDetailsByUuid($subData['uuid']);
                                    $answers = json_decode($subData['data_json'], true) ?: [];
                                    \App\Services\FormNotificationTriggerService::trigger((int)$subData['form_id'], 'approved', $subDetails, $answers);
                                }
                            } catch (\Throwable $exApproveNotif) {}

                        } else {
                            $stmtDoc = $db->prepare("UPDATE document_instances SET status = 'approved' WHERE id = ?");
                            $stmtDoc->execute([$entityId]);

                            self::logAuditStatic($db, 'workflow.completed', 'document_instances', $entityId, [
                                'workflow_instance_id' => $wfInstanceId
                            ], $actorId);

                            // Trigger Stage 6 PDF Generation
                            $resGen = \App\Services\FinalDocumentPdfService::generate($entityId, $actorId);
                            if (!$resGen['success']) {
                                $stmtDocFail = $db->prepare("UPDATE document_instances SET status = 'approved' WHERE id = ?");
                                $stmtDocFail->execute([$entityId]);
                            }
                        }
                    }
                }

            } elseif ($decision === 'rejected') {
                if (empty($task['allow_reject'])) {
                    $db->rollBack();
                    return ['success' => false, 'error' => 'action_forbidden', 'message' => 'Η απόρριψη δεν επιτρέπεται σε αυτό το βήμα.'];
                }

                // Reject task
                $stmtUpd = $db->prepare("
                    UPDATE workflow_step_instances 
                    SET status = 'rejected', decision = 'rejected', acted_by = ?, acted_at = NOW()
                    WHERE id = ?
                ");
                $stmtUpd->execute([$actorId, $stepInstId]);

                // Terminate workflow instance
                $stmtUpWf = $db->prepare("UPDATE workflow_instances SET status = 'rejected', completed_at = NOW() WHERE id = ?");
                $stmtUpWf->execute([$wfInstanceId]);

                // Cancel all remaining pending/active tasks
                $stmtCancelAll = $db->prepare("
                    UPDATE workflow_step_instances 
                    SET status = 'cancelled' 
                    WHERE workflow_instance_id = ? AND status IN ('pending', 'active')
                ");
                $stmtCancelAll->execute([$wfInstanceId]);

                // Update status
                if ($entityType === 'form') {
                    $stmtSub = $db->prepare("UPDATE form_submissions SET status = 'rejected' WHERE id = ?");
                    $stmtSub->execute([$entityId]);

                    $hist = $db->prepare("
                        INSERT INTO submission_status_history (submission_id, old_status, new_status, notes, changed_by)
                        VALUES (?, 'under_review', 'rejected', ?, ?)
                    ");
                    $hist->execute([$entityId, $comment ?: 'Απορρίφθηκε μέσω Workflow', $actorId]);

                    self::logAuditStatic($db, 'workflow.rejected', 'form_submissions', $entityId, [
                        'workflow_step_instance_id' => $stepInstId
                    ], $actorId);
                    
                    // Trigger form notification logs matching rejected status
                    try {
                        $stmtFindUuid = $db->prepare("SELECT uuid, form_id, data_json FROM form_submissions WHERE id = ?");
                        $stmtFindUuid->execute([$entityId]);
                        $subData = $stmtFindUuid->fetch(PDO::FETCH_ASSOC);
                        if ($subData) {
                            $subDetails = Submission::getDetailsByUuid($subData['uuid']);
                            $answers = json_decode($subData['data_json'], true) ?: [];
                            \App\Services\FormNotificationTriggerService::trigger((int)$subData['form_id'], 'rejected', $subDetails, $answers);
                        }
                    } catch (\Throwable $exRejectNotif) {}

                } else {
                    $stmtDoc = $db->prepare("UPDATE document_instances SET status = 'rejected' WHERE id = ?");
                    $stmtDoc->execute([$entityId]);

                    self::logAuditStatic($db, 'workflow.rejected', 'document_instances', $entityId, [
                        'workflow_step_instance_id' => $stepInstId
                    ], $actorId);
                }

                // Notify Creator & Admins
                try {
                    \App\Services\LifecycleNotificationService::notifyDocumentLifecycle(
                        'workflow_rejected',
                        [
                            'id' => $entityId,
                            'document_number' => $docNum,
                            'created_by' => $task['doc_creator_id'],
                            'step_name' => $task['step_name']
                        ],
                        $actorId,
                        $comment
                    );
                } catch (\Exception $e) {}

            } elseif ($decision === 'returned') {
                if (empty($task['allow_return'])) {
                    $db->rollBack();
                    return ['success' => false, 'error' => 'action_forbidden', 'message' => 'Η επιστροφή για διορθώσεις δεν επιτρέπεται σε αυτό το βήμα.'];
                }

                // Return task
                $stmtUpd = $db->prepare("
                    UPDATE workflow_step_instances 
                    SET status = 'returned', decision = 'returned', acted_by = ?, acted_at = NOW()
                    WHERE id = ?
                ");
                $stmtUpd->execute([$actorId, $stepInstId]);

                // Terminate/pause current workflow instance
                $stmtUpWf = $db->prepare("UPDATE workflow_instances SET status = 'cancelled', completed_at = NOW() WHERE id = ?");
                $stmtUpWf->execute([$wfInstanceId]);

                // Invalidate all tasks at current or future order steps
                $stmtCancelAll = $db->prepare("
                    UPDATE workflow_step_instances 
                    SET status = 'cancelled' 
                    WHERE workflow_instance_id = ? AND status IN ('pending', 'active')
                ");
                $stmtCancelAll->execute([$wfInstanceId]);

                // Update status
                if ($entityType === 'form') {
                    $stmtSub = $db->prepare("UPDATE form_submissions SET status = 'returned' WHERE id = ?");
                    $stmtSub->execute([$entityId]);

                    $hist = $db->prepare("
                        INSERT INTO submission_status_history (submission_id, old_status, new_status, notes, changed_by)
                        VALUES (?, 'under_review', 'returned', ?, ?)
                    ");
                    $hist->execute([$entityId, $comment ?: 'Επιστροφή για διορθώσεις μέσω Workflow', $actorId]);

                    self::logAuditStatic($db, 'workflow.task.returned', 'form_submissions', $entityId, [
                        'workflow_step_instance_id' => $stepInstId
                    ], $actorId);

                    // Trigger form notification logs matching returned status
                    try {
                        $stmtFindUuid = $db->prepare("SELECT uuid, form_id, data_json FROM form_submissions WHERE id = ?");
                        $stmtFindUuid->execute([$entityId]);
                        $subData = $stmtFindUuid->fetch(PDO::FETCH_ASSOC);
                        if ($subData) {
                            $subDetails = Submission::getDetailsByUuid($subData['uuid']);
                            $answers = json_decode($subData['data_json'], true) ?: [];
                            \App\Services\FormNotificationTriggerService::trigger((int)$subData['form_id'], 'returned', $subDetails, $answers);
                        }
                    } catch (\Throwable $exReturnNotif) {}

                } else {
                    $stmtDoc = $db->prepare("UPDATE document_instances SET status = 'returned_for_correction' WHERE id = ?");
                    $stmtDoc->execute([$entityId]);

                    // Safe defaults: clear workflow signatures captured after return (keep creator signatures)
                    $stmtSigs = $db->prepare("
                        SELECT ws.signature_field_key 
                        FROM workflow_steps ws
                        WHERE ws.workflow_definition_id = ? AND ws.step_order >= ? AND ws.signature_field_key IS NOT NULL
                    ");
                    $stmtSigs->execute([$task['workflow_definition_id'], $currentOrder]);
                    $sigKeys = $stmtSigs->fetchAll(PDO::FETCH_COLUMN);

                    if (!empty($sigKeys)) {
                        $inQuery = implode(',', array_fill(0, count($sigKeys), '?'));
                        $stmtDelSigs = $db->prepare("
                            DELETE FROM document_signatures 
                            WHERE document_instance_id = ? AND field_key IN ($inQuery)
                        ");
                        $stmtDelSigs->execute(array_merge([$entityId], $sigKeys));
                    }

                    self::logAuditStatic($db, 'workflow.task.returned', 'document_instances', $entityId, [
                        'workflow_step_instance_id' => $stepInstId
                    ], $actorId);
                }

                // Notify Creator & Admins
                try {
                    \App\Services\LifecycleNotificationService::notifyDocumentLifecycle(
                        'workflow_returned',
                        [
                            'id' => $entityId,
                            'document_number' => $docNum,
                            'created_by' => $task['doc_creator_id'],
                            'step_name' => $task['step_name']
                        ],
                        $actorId,
                        $comment
                    );
                } catch (\Exception $e) {}
            }

            $db->commit();

            // Trigger workflow approved / advanced notifications
            if ($decision === 'approved') {
                try {
                    \App\Services\LifecycleNotificationService::notifyDocumentLifecycle(
                        'workflow_approved',
                        [
                            'id' => $entityId,
                            'document_number' => $docNum,
                            'created_by' => $task['doc_creator_id']
                        ],
                        $actorId
                    );
                } catch (\Exception $exApp) {}
            }

            return ['success' => true];

        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return ['success' => false, 'error' => 'server_error', 'message' => $e->getMessage()];
        }
    }

    private static function logAuditStatic(PDO $db, string $action, string $entityType, ?int $entityId, array $metadata, int $userId) {
        try {
            $stmt = $db->prepare("
                INSERT INTO audit_logs (user_id, action, entity_type, entity_id, metadata_json, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId,
                $action,
                $entityType,
                $entityId,
                json_encode($metadata, JSON_UNESCAPED_UNICODE),
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ]);
        } catch (\Exception $e) {}
    }
}
