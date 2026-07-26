<?php
namespace App\Services;

use App\Core\Database;
use App\Models\DocumentInstance;
use App\Models\DocumentInstanceValue;
use App\Models\DocumentSignature;
use PDO;

class DocumentViewService {
    public static function loadWorkflowDocument(int $taskId): array {
        $db = Database::getInstance();

        // 1. Fetch Task details
        $stmt = $db->prepare("
            SELECT wsi.*, wi.document_instance_id, wi.entity_type, wi.entity_id, wi.current_step_order, ws.name as step_name, ws.step_type, ws.allow_reject, ws.allow_return, ws.signature_field_key,
                   wi.workflow_definition_id
            FROM workflow_step_instances wsi
            JOIN workflow_instances wi ON wsi.workflow_instance_id = wi.id
            JOIN workflow_steps ws ON wsi.workflow_step_id = ws.id
            WHERE wsi.id = ?
        ");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$task) {
            return [];
        }

        $entityType = $task['entity_type'] ?? 'document';
        $entityId = (int)$task['entity_id'];
        $values = [];
        $comments = [];
        $history = [];

        if ($entityType === 'form') {
            // Fetch Form Submission details
            $stmtSub = $db->prepare("
                SELECT s.*, f.title as form_title, f.slug as form_slug, u.full_name as creator_name, u.username as creator_username
                FROM form_submissions s
                JOIN forms f ON s.form_id = f.id
                LEFT JOIN users u ON s.user_id = u.id
                WHERE s.id = ?
            ");
            $stmtSub->execute([$entityId]);
            $sub = $stmtSub->fetch(PDO::FETCH_ASSOC);

            if ($sub) {
                $task['document_number'] = 'SUB-' . $sub['id'];
                $task['doc_title'] = $sub['form_title'] . ' Submission #' . $sub['id'];
                $task['template_title'] = $sub['form_title'];
                $task['creator_name'] = $sub['creator_name'] ?: $sub['creator_username'] ?: 'External User';
                $task['fields_schema_json'] = '[]'; // Simplified schema fallback

                $values = json_decode($sub['data_json'], true) ?: [];
            }
        } else {
            // Fetch Document details
            $stmtDoc = $db->prepare("
                SELECT i.document_number, i.title as doc_title, v.fields_schema_json, t.title as template_title, u.full_name as creator_name
                FROM document_instances i
                JOIN document_template_versions v ON i.template_version_id = v.id
                JOIN document_templates t ON i.document_template_id = t.id
                JOIN users u ON i.created_by = u.id
                WHERE i.id = ?
            ");
            $stmtDoc->execute([$entityId]);
            $doc = $stmtDoc->fetch(PDO::FETCH_ASSOC);
            if ($doc) {
                $task['document_number'] = $doc['document_number'];
                $task['doc_title'] = $doc['doc_title'];
                $task['template_title'] = $doc['template_title'];
                $task['creator_name'] = $doc['creator_name'];
                $task['fields_schema_json'] = $doc['fields_schema_json'];

                $values = DocumentInstanceValue::getValuesForInstance($entityId);
            }
        }

        // 3. Fetch workflow comments history
        $stmtComm = $db->prepare("
            SELECT c.*, u.full_name as user_name 
            FROM workflow_comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.workflow_instance_id = ?
            ORDER BY c.id ASC
        ");
        $stmtComm->execute([$task['workflow_instance_id']]);
        $comments = $stmtComm->fetchAll(PDO::FETCH_ASSOC);

        // 4. Fetch step history timeline logs
        $stmtHist = $db->prepare("
            SELECT wsi.*, ws.name as step_name, u.full_name as actor_name
            FROM workflow_step_instances wsi
            JOIN workflow_steps ws ON wsi.workflow_step_id = ws.id
            LEFT JOIN users u ON wsi.acted_by = u.id
            WHERE wsi.workflow_instance_id = ?
            ORDER BY wsi.step_order ASC, wsi.id ASC
        ");
        $stmtHist->execute([$task['workflow_instance_id']]);
        $history = $stmtHist->fetchAll(PDO::FETCH_ASSOC);

        return [
            'task' => $task,
            'values' => $values,
            'comments' => $comments,
            'history' => $history
        ];
    }
}
