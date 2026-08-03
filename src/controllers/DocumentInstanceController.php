<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Core\Session;
use App\Core\View;
use App\Core\Database;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\DocumentInstance;
use App\Models\DocumentInstanceValue;
use App\Services\NotificationService;

class DocumentInstanceController extends Controller {
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

    private function checkInstanceOwnership(array $instance) {
        $isAdmin = Auth::hasPermission('document_instances.view_all') || Auth::role() === 'administrator';
        if ($isAdmin) {
            return;
        }

        $userId = Auth::id();
        $isOwner = ((int)$instance['created_by'] === $userId || (int)$instance['owner_id'] === $userId || (int)$instance['assigned_to'] === $userId);
        if (!$isOwner) {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }
    }

    public function index() {
        $userId = Auth::id();
        $isAdmin = Auth::hasPermission('document_instances.view_all') || Auth::role() === 'administrator';

        $instances = $isAdmin ? DocumentInstance::getAllAdmin() : DocumentInstance::getByUser($userId);

        View::render('document_instances/drafts', [
            'title' => 'Τα Έγγραφά μου',
            'instances' => $instances
        ]);
    }

    public function drafts() {
        $userId = Auth::id();
        $isAdmin = Auth::hasPermission('document_instances.view_all') || Auth::role() === 'administrator';

        $instances = $isAdmin ? DocumentInstance::getAllAdmin('draft') : DocumentInstance::getByUser($userId, 'draft');

        View::render('document_instances/drafts', [
            'title' => 'Τα Πρόχειρά μου',
            'instances' => $instances
        ]);
    }

    public function submissions() {
        $userId = Auth::id();
        $isAdmin = Auth::hasPermission('document_instances.view_all') || Auth::role() === 'administrator';

        $instances = $isAdmin ? DocumentInstance::getAllAdmin('not_draft') : DocumentInstance::getByUser($userId, 'not_draft');

        View::render('document_instances/submissions', [
            'title' => 'Οι Υποβολές μου',
            'instances' => $instances
        ]);
    }

    public function templatesList() {
        // Fetch only templates with status = published
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT t.*, u.full_name as creator_name, v.version_number, v.page_count
            FROM document_templates t
            JOIN users u ON t.created_by = u.id
            JOIN document_template_versions v ON t.current_version_id = v.id
            WHERE t.status = 'published'
            ORDER BY t.id DESC
        ");
        $stmt->execute();
        $templates = $stmt->fetchAll();

        View::render('document_instances/templates', [
            'title' => 'Νέο Έγγραφο',
            'templates' => $templates
        ]);
    }

    public function startInstance(array $params) {
        $this->checkCsrf();
        $templateId = (int)($params['id'] ?? 0);
        $template = DocumentTemplate::findById($templateId);

        if (!$template || $template['status'] !== 'published') {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        try {
            $docNumber = DocumentInstance::generateDocumentNumber($templateId);

            $instanceId = DocumentInstance::create([
                'document_template_id' => $templateId,
                'template_version_id' => $template['current_version_id'],
                'document_number' => $docNumber,
                'title' => $template['title'] . ' - ' . date('d/m/Y H:i'),
                'owner_id' => Auth::id(),
                'created_by' => Auth::id()
            ]);

            $this->logAudit('document.created', 'document_instances', $instanceId, [
                'document_number' => $docNumber,
                'template_title' => $template['title']
            ]);

            Session::flash('success', 'Το έγγραφο ξεκίνησε με επιτυχία.');
            $this->redirect("/documents/{$instanceId}/edit");

        } catch (\Exception $e) {
            Session::flash('error', $e->getMessage());
            $this->back();
        }
    }

    public function edit(array $params) {
        $this->checkMaintenance();
        $id = (int)($params['id'] ?? 0);
        $instance = DocumentInstance::findById($id);

        if (!$instance) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $this->checkInstanceOwnership($instance);

        if ($instance['status'] !== 'draft' && $instance['status'] !== 'returned_for_correction') {
            Session::flash('error', 'Το έγγραφο έχει ήδη υποβληθεί και είναι Read Only.');
            $this->redirect("/documents/{$id}");
        }

        $values = DocumentInstanceValue::getValuesForInstance($id);

        // Fetch active repository options to dynamically bind
        $db = Database::getInstance();
        $repos = [];
        foreach (['departments', 'request-types', 'priority-levels'] as $slug) {
            $stmt = $db->prepare("SELECT data_json FROM repositories WHERE slug = ? AND is_active = 1");
            $stmt->execute([$slug]);
            $row = $stmt->fetch();
            $repos[$slug] = $row ? json_decode($row['data_json'], true) : [];
        }

        $this->logAudit('document.opened', 'document_instances', $id, [
            'document_number' => $instance['document_number']
        ]);

        // Fetch latest published version details to check warning alerts
        $stmtLatest = $db->prepare("
            SELECT v.version_number, v.id as latest_version_id
            FROM document_template_versions v
            WHERE v.template_id = ? AND v.status = 'published'
            ORDER BY v.version_number DESC LIMIT 1
        ");
        $stmtLatest->execute([$instance['document_template_id']]);
        $latest = $stmtLatest->fetch();

        View::render('document_instances/edit', [
            'title' => 'Συμπλήρωση Εγγράφου',
            'instance' => $instance,
            'values' => $values,
            'repos' => $repos,
            'pdfUrl' => "/documents/{$id}/source-pdf",
            'latestVersionNumber' => $latest ? (int)$latest['version_number'] : (int)$instance['version_number'],
            'latestVersionId' => $latest ? (int)$latest['latest_version_id'] : (int)$instance['template_version_id']
        ]);
    }

    public function save(array $params) {
        $this->checkCsrf();
        $id = (int)($params['id'] ?? 0);
        $instance = DocumentInstance::findById($id);

        if (!$instance) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $this->checkInstanceOwnership($instance);

        if ($instance['status'] !== 'draft' && $instance['status'] !== 'returned_for_correction') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Το έγγραφο δεν είναι σε κατάσταση επεξεργασίας.']);
            exit;
        }

        $reqData = Request::all();
        $formValues = $reqData['values'] ?? [];

        try {
            $db = Database::getInstance();
            $db->beginTransaction();

            $fields = json_decode($instance['fields_schema_json'], true) ?: [];
            foreach ($fields as $field) {
                $key = $field['key'];
                $val = $formValues[$key] ?? '';
                DocumentInstanceValue::saveValue($id, $key, $field['type'], $val);
            }

            // Update title
            if (!empty($reqData['title'])) {
                $stmt = $db->prepare("UPDATE document_instances SET title = ? WHERE id = ?");
                $stmt->execute([$reqData['title'], $id]);
            }

            $db->commit();

            $this->logAudit('document.draft.saved', 'document_instances', $id, [
                'document_number' => $instance['document_number']
            ]);

            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Το έγγραφο αποθηκεύτηκε με επιτυχία.']);
                exit;
            }

            Session::flash('success', 'Το πρόχειρο αποθηκεύτηκε επιτυχώς.');
            $this->redirect("/documents/{$id}/edit");

        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                exit;
            }
            Session::flash('error', $e->getMessage());
            $this->back();
        }
    }

    public function autosave(array $params) {
        $this->checkCsrf();
        $id = (int)($params['id'] ?? 0);
        $instance = DocumentInstance::findById($id);

        if (!$instance) {
            http_response_code(404);
            exit;
        }

        $this->checkInstanceOwnership($instance);

        if ($instance['status'] !== 'draft') {
            http_response_code(403);
            exit;
        }

        $reqData = Request::all();
        $formValues = $reqData['values'] ?? [];

        try {
            $db = Database::getInstance();
            $db->beginTransaction();

            $fields = json_decode($instance['fields_schema_json'], true) ?: [];
            foreach ($fields as $field) {
                $key = $field['key'];
                if (isset($formValues[$key])) {
                    DocumentInstanceValue::saveValue($id, $key, $field['type'], $formValues[$key]);
                }
            }

            $db->commit();

            $this->logAudit('document.autosaved', 'document_instances', $id, [
                'document_number' => $instance['document_number']
            ]);

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'time' => date('H:i:s')]);
            exit;

        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            http_response_code(500);
            exit;
        }
    }

    public function submit(array $params) {
        $this->checkCsrf();
        $id = (int)($params['id'] ?? 0);
        $instance = DocumentInstance::findById($id);

        if (!$instance) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $this->checkInstanceOwnership($instance);

        if ($instance['status'] !== 'draft' && $instance['status'] !== 'returned_for_correction') {
            Session::flash('error', 'Το έγγραφο έχει ήδη υποβληθεί.');
            $this->redirect("/documents/{$id}");
        }

        $values = DocumentInstanceValue::getValuesForInstance($id);
        $fields = json_decode($instance['fields_schema_json'], true) ?: [];

        // Validate values strictly server-side
        $errors = [];
        foreach ($fields as $field) {
            $key = $field['key'];
            $val = $values[$key] ?? '';

            if (!empty($field['required']) && trim($val) === '') {
                $errors[$key] = "Το πεδίο {$field['label']} είναι υποχρεωτικό.";
            }

            // Min/Max/Regex validation checks
            if (trim($val) !== '') {
                if (!empty($field['validation']['min'])) {
                    if (is_numeric($val) && (float)$val < (float)$field['validation']['min']) {
                        $errors[$key] = "Η τιμή πρέπει να είναι τουλάχιστον {$field['validation']['min']}.";
                    } elseif (mb_strlen($val) < (int)$field['validation']['min']) {
                        $errors[$key] = "Το κείμενο πρέπει να είναι τουλάχιστον {$field['validation']['min']} χαρακτήρες.";
                    }
                }
                if (!empty($field['validation']['max'])) {
                    if (is_numeric($val) && (float)$val > (float)$field['validation']['max']) {
                        $errors[$key] = "Η τιμή δεν μπορεί να ξεπερνά το {$field['validation']['max']}.";
                    } elseif (mb_strlen($val) > (int)$field['validation']['max']) {
                        $errors[$key] = "Το κείμενο δεν μπορεί να ξεπερνά τους {$field['validation']['max']} χαρακτήρες.";
                    }
                }
                if (!empty($field['validation']['regex'])) {
                    if (!preg_match($field['validation']['regex'], $val)) {
                        $errors[$key] = "Η τιμή δεν ταιριάζει με το απαιτούμενο format.";
                    }
                }
            }
        }

        // Validate signature field values
        foreach ($fields as $field) {
            if ($field['type'] === 'signature') {
                $sig = \App\Models\DocumentSignature::findByField($id, $field['key']);
                if (!empty($field['required']) && !$sig) {
                    $errors[$field['key']] = "Η υπογραφή για το πεδίο {$field['label']} είναι υποχρεωτική.";
                }
            }
        }

        if (!empty($errors)) {
            $this->logAudit('document.validation.failed', 'document_instances', $id, [
                'document_number' => $instance['document_number']
            ]);
            Session::flash('errors', $errors);
            $this->redirect("/documents/{$id}/edit");
        }

        try {
            $db = Database::getInstance();
            $db->beginTransaction();

            $stmt = $db->prepare("UPDATE document_instances SET status = 'submitted', submitted_at = NOW() WHERE id = ?");
            $stmt->execute([$id]);

            $db->commit();

            $this->logAudit('document.submitted', 'document_instances', $id, [
                'document_number' => $instance['document_number']
            ]);

            // Check if active workflow is configured for the template, if yes execute it
            $hasWorkflow = \App\Services\WorkflowEngineService::start($id, Auth::id());

            if (!$hasWorkflow) {
                // Keep original immediate finalized PDF generation path if no workflow exists
                \App\Services\FinalDocumentPdfService::generate($id, Auth::id());
            }

            // Notify Creator and Admin inside separate try/catch so exceptions do not abort submission
            try {
                \App\Services\LifecycleNotificationService::notifyDocumentLifecycle(
                    'document_submitted',
                    $instance,
                    Auth::id()
                );
            } catch (\Exception $notifEx) {
                error_log("Document submission notifications creation failed: " . $notifEx->getMessage());
            }


            Session::flash('success', 'Το έγγραφο υποβλήθηκε επιτυχώς.');
            $this->redirect("/documents/{$id}");

        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Session::flash('error', $e->getMessage());
            $this->back();
        }
    }

    public function cancel(array $params) {
        $this->checkCsrf();
        $id = (int)($params['id'] ?? 0);
        $instance = DocumentInstance::findById($id);

        if (!$instance) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $this->checkInstanceOwnership($instance);

        if ($instance['status'] !== 'draft') {
            Session::flash('error', 'Μόνο πρόχειρα έγγραφα μπορούν να ακυρωθούν.');
            $this->redirect("/documents/{$id}");
        }

        try {
            $db = Database::getInstance();
            $db->beginTransaction();

            $stmt = $db->prepare("UPDATE document_instances SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$id]);

            $db->commit();

            $this->logAudit('document.cancelled', 'document_instances', $id, [
                'document_number' => $instance['document_number']
            ]);

            Session::flash('success', 'Το πρόχειρο ακυρώθηκε επιτυχώς.');
            $this->redirect("/documents/drafts");
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Session::flash('error', $e->getMessage());
            $this->back();
        }
    }

    public function delete(array $params) {
        $this->checkCsrf();
        $id = (int)($params['id'] ?? 0);
        $instance = DocumentInstance::findById($id);

        if (!$instance) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        // Only Administrator or users with document_instances.manage permission can delete permanently
        $isAuthorized = Auth::hasPermission('document_instances.manage') || Auth::role() === 'administrator';
        if (!$isAuthorized) {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }

        if ($instance['status'] !== 'cancelled') {
            Session::flash('error', 'Μπορείτε να διαγράψετε οριστικά μόνο ακυρωμένα έγγραφα.');
            $this->redirect("/documents/{$id}");
        }

        try {
            $db = Database::getInstance();

            // Fetch signature files for physical cleanup deletion
            $stmtSig = $db->prepare("SELECT signature_image FROM document_signatures WHERE document_instance_id = ?");
            $stmtSig->execute([$id]);
            $sigs = $stmtSig->fetchAll();

            $db->beginTransaction();

            // Delete database references
            $db->prepare("DELETE FROM document_instance_values WHERE document_instance_id = ?")->execute([$id]);
            $db->prepare("DELETE FROM document_signatures WHERE document_instance_id = ?")->execute([$id]);
            $db->prepare("DELETE FROM document_instances WHERE id = ?")->execute([$id]);

            $db->commit();

            // Delete physical PNG files after successful database commit
            foreach ($sigs as $s) {
                $filePath = "storage/document_signatures/" . $s['signature_image'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            $this->logAudit('document.deleted', 'document_instances', $id, [
                'document_number' => $instance['document_number']
            ]);

            Session::flash('success', 'Το έγγραφο διαγράφηκε οριστικά.');
            $this->redirect("/documents/drafts");
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Session::flash('error', $e->getMessage());
            $this->back();
        }
    }

    public function migrate(array $params) {
        $this->checkCsrf();
        $id = (int)($params['id'] ?? 0);
        $instance = DocumentInstance::findById($id);

        if (!$instance) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $this->checkInstanceOwnership($instance);

        if ($instance['status'] !== 'draft') {
            Session::flash('error', 'Δεν επιτρέπεται η αναβάθμιση έκδοσης σε μη πρόχειρα έγγραφα.');
            $this->redirect("/documents/{$id}");
        }

        $db = Database::getInstance();
        
        // Fetch latest published version metadata details
        $stmtLatest = $db->prepare("
            SELECT v.id, v.version_number, v.fields_schema_json
            FROM document_template_versions v
            WHERE v.template_id = ? AND v.status = 'published'
            ORDER BY v.version_number DESC LIMIT 1
        ");
        $stmtLatest->execute([$instance['document_template_id']]);
        $latest = $stmtLatest->fetch();

        if (!$latest || (int)$latest['id'] === (int)$instance['template_version_id']) {
            Session::flash('info', 'Το έγγραφο χρησιμοποιεί ήδη την τελευταία έκδοση.');
            $this->redirect("/documents/{$id}/edit");
        }

        // Auditing Started Event
        $this->logAudit('document.template_migration.started', 'document_instances', $id, [
            'old_version_id' => $instance['template_version_id'],
            'new_version_id' => $latest['id']
        ]);

        try {
            $oldSchema = json_decode($instance['fields_schema_json'], true) ?: [];
            $newSchema = json_decode($latest['fields_schema_json'], true) ?: [];

            $oldValues = DocumentInstanceValue::getValuesForInstance($id);
            $db->beginTransaction();

            $preservedFields = 0;
            $addedFields = 0;
            $removedFields = 0;
            $incompatibleFields = 0;
            $migratedSignatures = 0;

            // Map old fields structure types
            $oldTypes = [];
            foreach ($oldSchema as $of) {
                $oldTypes[$of['key']] = $of['type'];
            }

            // Map new fields structure types
            $newKeys = [];
            foreach ($newSchema as $nf) {
                $newKeys[$nf['key']] = $nf['type'];
            }

            // Process deleted database values rows that no longer exist in new version schema
            foreach ($oldSchema as $of) {
                if (!isset($newKeys[$of['key']])) {
                    // Removed field
                    $removedFields++;
                    // Cleanup values row
                    $db->prepare("DELETE FROM document_instance_values WHERE document_instance_id = ? AND field_key = ?")->execute([$id, $of['key']]);
                    // Cleanup signature rows
                    if ($of['type'] === 'signature') {
                        $stmtSig = $db->prepare("SELECT signature_image FROM document_signatures WHERE document_instance_id = ? AND field_key = ?");
                        $stmtSig->execute([$id, $of['key']]);
                        $sRow = $stmtSig->fetch();
                        if ($sRow) {
                            $filePath = "storage/document_signatures/" . $sRow['signature_image'];
                            if (file_exists($filePath)) {
                                unlink($filePath);
                            }
                            $db->prepare("DELETE FROM document_signatures WHERE document_instance_id = ? AND field_key = ?")->execute([$id, $of['key']]);
                        }
                    }
                }
            }

            // Process new fields and type compatibility mapping
            foreach ($newSchema as $nf) {
                $key = $nf['key'];
                if (isset($oldTypes[$key])) {
                    $oldType = $oldTypes[$key];
                    $newType = $nf['type'];

                    // Compatibility check
                    $isCompatible = false;
                    if ($oldType === $newType) {
                        $isCompatible = true;
                    } elseif ($oldType === 'text' && $newType === 'textarea') {
                        $isCompatible = true;
                    } elseif ($oldType === 'number' && $newType === 'text') {
                        $isCompatible = true;
                    }

                    if ($isCompatible) {
                        $preservedFields++;
                        if ($oldType === 'signature') {
                            $migratedSignatures++;
                        }
                        // Update the type field stored in db values table row if it changed
                        if ($oldType !== $newType) {
                            $db->prepare("UPDATE document_instance_values SET field_type = ? WHERE document_instance_id = ? AND field_key = ?")
                               ->execute([$newType, $id, $key]);
                        }
                    } else {
                        // Incompatible: Delete old value
                        $incompatibleFields++;
                        $db->prepare("DELETE FROM document_instance_values WHERE document_instance_id = ? AND field_key = ?")->execute([$id, $key]);
                        if ($oldType === 'signature') {
                            $db->prepare("DELETE FROM document_signatures WHERE document_instance_id = ? AND field_key = ?")->execute([$id, $key]);
                        }
                    }
                } else {
                    $addedFields++;
                    // New field: do not write to db yet (will be saved when completing)
                }
            }

            // Update instance link reference to new version
            $stmtUp = $db->prepare("UPDATE document_instances SET template_version_id = ?, updated_at = NOW() WHERE id = ?");
            $stmtUp->execute([$latest['id'], $id]);

            $db->commit();

            $this->logAudit('document.template_migration.completed', 'document_instances', $id, [
                'old_version_id' => $instance['template_version_id'],
                'new_version_id' => $latest['id'],
                'preserved_fields_count' => $preservedFields,
                'added_fields_count' => $addedFields,
                'removed_fields_count' => $removedFields,
                'incompatible_fields_count' => $incompatibleFields,
                'migrated_signatures_count' => $migratedSignatures
            ]);

            Session::flash('success', 'Το έγγραφο ενημερώθηκε επιτυχώς στη νέα έκδοση του προτύπου.');
            $this->redirect("/documents/{$id}/edit");

        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->logAudit('document.template_migration.failed', 'document_instances', $id, [
                'error' => $e->getMessage()
            ]);
            Session::flash('error', $e->getMessage());
            $this->redirect("/documents/{$id}/edit");
        }
    }

    public function show(array $params) {
        $id = (int)($params['id'] ?? 0);
        $instance = DocumentInstance::findById($id);

        if (!$instance) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $this->checkInstanceOwnership($instance);

        $values = DocumentInstanceValue::getValuesForInstance($id);

        // Fetch audit logs summary for this instance
        $db = Database::getInstance();
        $audits = $db->prepare("
            SELECT a.*, u.full_name as user_name 
            FROM audit_logs a 
            JOIN users u ON a.user_id = u.id 
            WHERE a.entity_type = 'document_instances' AND a.entity_id = ? 
            ORDER BY a.id DESC 
            LIMIT 10
        ");
        $audits->execute([$id]);

        // Fetch latest published version details to check warning alerts
        $stmtLatest = $db->prepare("
            SELECT v.version_number, v.id as latest_version_id
            FROM document_template_versions v
            WHERE v.template_id = ? AND v.status = 'published'
            ORDER BY v.version_number DESC LIMIT 1
        ");
        $stmtLatest->execute([$instance['document_template_id']]);
        $latest = $stmtLatest->fetch();

        // Fetch final document generated file info
        $stmtFile = $db->prepare("
            SELECT f.*, u.full_name as generator_name 
            FROM document_final_files f
            JOIN users u ON f.generated_by = u.id
            WHERE f.document_instance_id = ?
        ");
        $stmtFile->execute([$id]);
        $finalFile = $stmtFile->fetch();

        // Fetch workflow timeline history
        $stmtWfHist = $db->prepare("
            SELECT wsi.*, ws.name as step_name, u.full_name as actor_name
            FROM workflow_step_instances wsi
            JOIN workflow_instances wi ON wsi.workflow_instance_id = wi.id
            JOIN workflow_steps ws ON wsi.workflow_step_id = ws.id
            LEFT JOIN users u ON wsi.acted_by = u.id
            WHERE wi.document_instance_id = ?
            ORDER BY wsi.step_order ASC, wsi.id ASC
        ");
        $stmtWfHist->execute([$id]);
        $workflowHistory = $stmtWfHist->fetchAll();

        View::render('document_instances/show', [
            'title' => 'Στοιχεία Εγγράφου',
            'instance' => $instance,
            'values' => $values,
            'audits' => $audits->fetchAll(),
            'pdfUrl' => "/documents/{$id}/preview",
            'latestVersionNumber' => $latest ? (int)$latest['version_number'] : (int)$instance['version_number'],
            'latestVersionId' => $latest ? (int)$latest['latest_version_id'] : (int)$instance['template_version_id'],
            'finalFile' => $finalFile,
            'workflowHistory' => $workflowHistory
        ]);
    }

    public function sourcePdf(array $params) {
        $id = (int)($params['id'] ?? 0);
        $instance = DocumentInstance::findById($id);

        if (!$instance) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Το έγγραφο δεν βρέθηκε.']);
            exit;
        }

        $this->checkInstanceOwnership($instance);

        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT t.source_type, t.original_file_path, t.converted_pdf_path, v.pdf_file_path
            FROM document_instances i
            JOIN document_templates t ON i.document_template_id = t.id
            JOIN document_template_versions v ON i.template_version_id = v.id
            WHERE i.id = ?
        ");
        $stmt->execute([$id]);
        $res = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$res) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Τα στοιχεία του προτύπου δεν βρέθηκαν.']);
            exit;
        }

        // Determine correct template version PDF path
        $pdfPath = '';
        if (strtolower($res['source_type']) === 'pdf') {
            $pdfPath = $res['original_file_path'];
        } else {
            // For docx templates, the generated PDF resides on the template version pdf_file_path or t.converted_pdf_path
            $pdfPath = $res['pdf_file_path'] ?: $res['converted_pdf_path'];
        }

        if (!$pdfPath || !file_exists($pdfPath)) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Το αρχείο PDF του προτύπου δεν βρέθηκε.']);
            exit;
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="source.pdf"');
        header('Content-Length: ' . filesize($pdfPath));
        readfile($pdfPath);
        exit;
    }

    public function previewFile(array $params) {
        $id = (int)($params['id'] ?? 0);
        $instance = DocumentInstance::findById($id);

        if (!$instance) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $this->checkInstanceOwnership($instance);

        // Fetch values and signatures
        $values = DocumentInstanceValue::getValuesForInstance($id);

        $this->logAudit('document.preview.opened', 'document_instances', $id, [
            'document_number' => $instance['document_number']
        ]);

        View::render('document_instances/preview', [
            'title' => 'Προεπισκόπηση Συμπληρωμένου Εγγράφου',
            'instance' => $instance,
            'values' => $values,
            'pdfUrl' => "/documents/{$id}/preview-pdf"
        ], 'focus');
    }

    public function previewPdf(array $params) {
        $id = (int)($params['id'] ?? 0);
        $instance = DocumentInstance::findById($id);

        if (!$instance) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Το έγγραφο δεν βρέθηκε.']);
            exit;
        }

        $this->checkInstanceOwnership($instance);

        try {
            $pdfPath = \App\Services\FinalDocumentPdfService::generateDraftPdf($id);
            if (!file_exists($pdfPath)) {
                http_response_code(404);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Το αρχείο PDF προεπισκόπησης δεν βρέθηκε.']);
                exit;
            }

            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $instance['document_number'] . '-preview.pdf"');
            header('Content-Length: ' . filesize($pdfPath));
            readfile($pdfPath);
            exit;
        } catch (\Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    public function previewPdfDownload(array $params) {
        $id = (int)($params['id'] ?? 0);
        $instance = DocumentInstance::findById($id);

        if (!$instance) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Το έγγραφο δεν βρέθηκε.']);
            exit;
        }

        $this->checkInstanceOwnership($instance);

        try {
            $pdfPath = \App\Services\FinalDocumentPdfService::generateDraftPdf($id);
            if (!file_exists($pdfPath)) {
                http_response_code(404);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Το αρχείο PDF προεπισκόπησης δεν βρέθηκε.']);
                exit;
            }

            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $instance['document_number'] . '-preview.pdf"');
            header('Content-Length: ' . filesize($pdfPath));
            readfile($pdfPath);
            exit;
        } catch (\Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    public function saveSignature(array $params) {
        $this->checkCsrf();
        $id = (int)($params['id'] ?? 0);
        $instance = DocumentInstance::findById($id);

        if (!$instance) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Το έγγραφο δεν βρέθηκε.']);
            exit;
        }

        $this->checkInstanceOwnership($instance);

        if ($instance['status'] !== 'draft') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Το έγγραφο δεν είναι σε κατάσταση Draft.']);
            exit;
        }

        $reqData = Request::all();
        $fieldKey = $reqData['field_key'] ?? '';
        $page = (int)($reqData['page'] ?? 1);
        $imgData = $reqData['signature_data'] ?? ''; // base64 encoded PNG

        if (empty($fieldKey) || empty($imgData)) {
            http_response_code(400);
            $this->logAudit('signature.validation.failed', 'document_instances', $id, [
                'document_number' => $instance['document_number']
            ]);
            echo json_encode(['success' => false, 'message' => 'Ελλιπή στοιχεία υπογραφής.']);
            exit;
        }

        // Validate field ownership inside templates schema
        $fields = json_decode($instance['fields_schema_json'], true) ?: [];
        $isValidKey = false;
        foreach ($fields as $f) {
            if ($f['key'] === $fieldKey && $f['type'] === 'signature') {
                $isValidKey = true;
                break;
            }
        }

        if (!$isValidKey) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Μη έγκυρο κλειδί πεδίου υπογραφής.']);
            exit;
        }

        try {
            // Log started event
            $this->logAudit('signature.started', 'document_instances', $id, [
                'field_key' => $fieldKey
            ]);

            // Validate base64 structure format
            if (strpos($imgData, 'data:image/png;base64,') !== 0) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Μη υποστηριζόμενο format υπογραφής (πρέπει να είναι PNG).']);
                exit;
            }

            // Decode image payload
            $filteredData = substr($imgData, strpos($imgData, ",") + 1);
            $filteredData = str_replace(' ', '+', $filteredData);
            $decodedImg = base64_decode($filteredData, true);

            if ($decodedImg === false || strlen($decodedImg) === 0) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Σφάλμα αποκωδικοποίησης δεδομένων υπογραφής.']);
                exit;
            }

            // Verify PNG magic signature bytes: 89 50 4E 47 0D 0A 1A 0A
            $magic = substr($decodedImg, 0, 8);
            if ($magic !== "\x89PNG\r\n\x1a\n") {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Τα δεδομένα δεν αποτελούν έγκυρη εικόνα PNG.']);
                exit;
            }

            // Generate SHA256 hash
            $sigHash = hash('sha256', $decodedImg);
            
            // Check storage directory write capability
            $dir = "storage/document_signatures";
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }

            if (!is_writable($dir)) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Ο φάκελος αποθήκευσης υπογραφών δεν είναι εγγράψιμος.']);
                exit;
            }

            // Save file privately
            $filename = "sig_{$id}_{$fieldKey}_" . time() . ".png";
            $storagePath = "{$dir}/{$filename}";
            
            $bytesWritten = file_put_contents($storagePath, $decodedImg);
            if ($bytesWritten !== strlen($decodedImg) || !file_exists($storagePath)) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Αποτυχία εγγραφής του αρχείου υπογραφής στο δίσκο.']);
                exit;
            }

            // Fetch if existing signature is replaced
            $existing = \App\Models\DocumentSignature::findByField($id, $fieldKey);
            $auditAction = $existing ? 'signature.replaced' : 'signature.saved';

            $db = Database::getInstance();
            $db->beginTransaction();

            try {
                // Insert/Update DB record
                \App\Models\DocumentSignature::create([
                    'document_instance_id' => $id,
                    'field_key' => $fieldKey,
                    'page' => $page,
                    'signature_image' => $filename,
                    'signature_hash' => $sigHash,
                    'signed_by' => Auth::id(),
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
                ]);

                $db->commit();
            } catch (\Exception $dbEx) {
                $db->rollBack();
                if (file_exists($storagePath)) {
                    unlink($storagePath);
                }
                throw $dbEx;
            }

            $this->logAudit($auditAction, 'document_instances', $id, [
                'document_number' => $instance['document_number'],
                'field_key' => $fieldKey,
                'hash' => $sigHash
            ]);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Η υπογραφή αποθηκεύτηκε επιτυχώς.',
                'signature_url' => "/documents/{$id}/signature/{$fieldKey}"
            ]);
            exit;

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    public function getSignatureImage(array $params) {
        $id = (int)($params['id'] ?? 0);
        $key = $params['key'] ?? '';
        $instance = DocumentInstance::findById($id);

        if (!$instance) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $this->checkInstanceOwnership($instance);

        $sig = \App\Models\DocumentSignature::findByField($id, $key);
        if (!$sig) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $filePath = "storage/document_signatures/" . $sig['signature_image'];
        if (!file_exists($filePath)) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        // Cache browser-side for performance
        header('Content-Type: image/png');
        header('Cache-Control: private, max-age=86400');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }

    public function clearSignature(array $params) {
        $this->checkCsrf();
        $id = (int)($params['id'] ?? 0);
        $key = $params['key'] ?? '';
        $instance = DocumentInstance::findById($id);

        if (!$instance) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Το έγγραφο δεν βρέθηκε.']);
            exit;
        }

        $this->checkInstanceOwnership($instance);

        if ($instance['status'] !== 'draft') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Το έγγραφο δεν είναι σε κατάσταση Draft.']);
            exit;
        }

        $sig = \App\Models\DocumentSignature::findByField($id, $key);
        if ($sig) {
            // Delete file
            $filePath = "storage/document_signatures/" . $sig['signature_image'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            \App\Models\DocumentSignature::deleteForField($id, $key);

            $this->logAudit('signature.cleared', 'document_instances', $id, [
                'document_number' => $instance['document_number'],
                'field_key' => $key
            ]);
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Η υπογραφή διαγράφηκε επιτυχώς.']);
        exit;
    }

    public function finalPdf(array $params) {
        $id = (int)($params['id'] ?? 0);
        $instance = DocumentInstance::findById($id);

        if (!$instance) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $this->checkInstanceOwnership($instance);

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM document_final_files WHERE document_instance_id = ?");
        $stmt->execute([$id]);
        $fileRow = $stmt->fetch();

        if (!$fileRow || $fileRow['generation_status'] !== 'completed') {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $filePath = $fileRow['file_path'];
        if (!file_exists($filePath)) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $this->logAudit('final_pdf.opened', 'document_instances', $id, [
            'document_number' => $instance['document_number']
        ]);

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }

    public function finalPdfDownload(array $params) {
        $id = (int)($params['id'] ?? 0);
        $instance = DocumentInstance::findById($id);

        if (!$instance) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $this->checkInstanceOwnership($instance);

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM document_final_files WHERE document_instance_id = ?");
        $stmt->execute([$id]);
        $fileRow = $stmt->fetch();

        if (!$fileRow || $fileRow['generation_status'] !== 'completed') {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $filePath = $fileRow['file_path'];
        if (!file_exists($filePath)) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $this->logAudit('final_pdf.downloaded', 'document_instances', $id, [
            'document_number' => $instance['document_number']
        ]);

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }

    public function retryFinalPdf(array $params) {
        $this->checkCsrf();
        $id = (int)($params['id'] ?? 0);
        $instance = DocumentInstance::findById($id);

        if (!$instance) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Το έγγραφο δεν βρέθηκε.']);
            exit;
        }

        $isAuthorized = Auth::hasPermission('document_instances.manage') || Auth::role() === 'administrator';
        if (!$isAuthorized) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Δεν έχετε δικαίωμα αναπαραγωγής του τελικού PDF.']);
            exit;
        }

        if ($instance['status'] !== 'submitted' && $instance['status'] !== 'finalized') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Η αναπαραγωγή επιτρέπεται μόνο σε υποβληθέντα ή οριστικοποιημένα έγγραφα.']);
            exit;
        }

        $res = \App\Services\FinalDocumentPdfService::generate($id, Auth::id());
        if ($res['success']) {
            Session::flash('success', 'Το τελικό PDF δημιουργήθηκε επιτυχώς.');
        } else {
            Session::flash('error', 'Αποτυχία παραγωγής τελικού PDF: ' . $res['message']);
        }

        $this->redirect("/documents/{$id}");
    }
}
