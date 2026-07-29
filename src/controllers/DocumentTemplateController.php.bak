<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Core\Session;
use App\Core\View;
use App\Core\Database;
use App\Core\UploadManager;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Services\PdfParserService;

class DocumentTemplateController extends Controller {
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
        $templates = DocumentTemplate::getAll();
        View::render('document_templates/index', [
            'title' => 'Πρότυπα Εγγράφων',
            'templates' => $templates
        ]);
    }

    public function create() {
        View::render('document_templates/create', [
            'title' => 'Δημιουργία Προτύπου Εγγράφου'
        ]);
    }

    private function getLibreOfficePath(): ?string {
        $paths = [
            'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
            'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe'
        ];
        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        return null;
    }

    private function executeConversionWithTimeout(string $libreOfficePath, string $sourcePath, string $outputDir, int $timeoutSeconds = 90): bool {
        $cmd = sprintf(
            '"%s" --headless --convert-to pdf --outdir %s %s',
            $libreOfficePath,
            escapeshellarg($outputDir),
            escapeshellarg($sourcePath)
        );

        $descriptorspec = [
            0 => ["pipe", "r"], // stdin
            1 => ["pipe", "w"], // stdout
            2 => ["pipe", "w"]  // stderr
        ];

        $process = proc_open($cmd, $descriptorspec, $pipes);

        if (!is_resource($process)) {
            return false;
        }

        fclose($pipes[0]);

        $start = time();
        $terminated = false;

        while (true) {
            $status = proc_get_status($process);

            if (!$status['running']) {
                break;
            }

            if ((time() - $start) > $timeoutSeconds) {
                proc_terminate($process);
                $terminated = true;
                break;
            }

            usleep(100000); // Wait 100ms
        }

        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        return !$terminated;
    }

    public function store() {
        $this->checkCsrf();
        $data = Request::all();

        $validated = $this->validate($data, [
            'title' => ['required'],
            'slug' => ['required']
        ]);

        if (empty($_FILES['document_file']['name'])) {
            Session::flash('error', 'Παρακαλώ επιλέξτε ένα αρχείο PDF ή DOCX.');
            $this->back();
        }

        $file = $_FILES['document_file'];
        $originalName = basename($file['name']);
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($ext, ['pdf', 'docx'])) {
            Session::flash('error', 'Μη επιτρεπτός τύπος αρχείου. Επιτρέπονται μόνο αρχεία PDF και DOCX.');
            $this->back();
        }

        $libreOfficePath = $this->getLibreOfficePath();
        if ($ext === 'docx' && !$libreOfficePath) {
            Session::flash('error', 'Η μετατροπή αρχείων DOCX δεν είναι διαθέσιμη στον διακομιστή (Λείπει το LibreOffice). Παρακαλώ ανεβάστε αρχείο PDF.');
            $this->back();
        }

        try {
            $storageDir = __DIR__ . '/../../storage/document_templates/originals';
            if (!is_dir($storageDir)) {
                mkdir($storageDir, 0755, true);
            }

            $upload = UploadManager::upload($file, ['pdf', 'docx']);

            $convertedPath = null;
            $pageCount = 1;
            $dimensions = [];

            if ($ext === 'docx') {
                $convertedDir = __DIR__ . '/../../storage/document_templates/converted';
                if (!is_dir($convertedDir)) {
                    mkdir($convertedDir, 0755, true);
                }
                $outputPdfName = pathinfo($upload['stored_name'], PATHINFO_FILENAME) . '.pdf';
                $convertedPath = $convertedDir . '/' . $outputPdfName;

                $this->logAudit('docx.conversion.started', 'document_templates', null, [
                    'original_name' => $originalName
                ]);

                // Run LibreOffice conversion securely with 90s timeout
                $success = $this->executeConversionWithTimeout($libreOfficePath, $upload['storage_path'], $convertedDir, 90);

                if (!$success || !file_exists($convertedPath)) {
                    if (file_exists($convertedPath)) {
                        @unlink($convertedPath);
                    }
                    $this->logAudit('docx.conversion.failed', 'document_templates', null, [
                        'original_name' => $originalName,
                        'error' => 'Conversion process timed out or failed to produce PDF output.'
                    ]);
                    throw new \Exception("Αποτυχία μετατροπής αρχείου DOCX σε PDF λόγω σφάλματος ή υπέρβασης ορίου χρόνου (timeout).");
                }

                $this->logAudit('docx.conversion.completed', 'document_templates', null, [
                    'original_name' => $originalName
                ]);
            }

            $pdfTarget = $convertedPath ?? $upload['storage_path'];
            try {
                $pdfData = PdfParserService::parseMetadata($pdfTarget);
                $pageCount = $pdfData['page_count'];
                $dimensions = $pdfData['dimensions'];
            } catch (\Exception $ex) {
                if (file_exists($upload['storage_path'])) @unlink($upload['storage_path']);
                if ($convertedPath && file_exists($convertedPath)) @unlink($convertedPath);
                throw $ex;
            }

            $templateId = DocumentTemplate::create([
                'title' => $validated['title'],
                'slug' => $validated['slug'],
                'description' => $data['description'] ?? null,
                'source_type' => $ext,
                'original_file_path' => $upload['storage_path'],
                'converted_pdf_path' => $convertedPath,
                'created_by' => Auth::id()
            ]);

            $versionId = DocumentTemplateVersion::create([
                'template_id' => $templateId,
                'version_number' => 1,
                'pdf_file_path' => $pdfTarget,
                'page_count' => $pageCount,
                'page_dimensions_json' => json_encode($dimensions)
            ]);

            $db = Database::getInstance();
            $stmt = $db->prepare("UPDATE document_templates SET current_version_id = ? WHERE id = ?");
            $stmt->execute([$versionId, $templateId]);

            $this->logAudit('template.upload', 'document_templates', $templateId, [
                'title' => $validated['title'],
                'slug' => $validated['slug'],
                'file_name' => $originalName
            ]);

            Session::flash('success', 'Το πρότυπο εγγράφου δημιουργήθηκε με επιτυχία ως Σχέδιο (Draft).');
            $this->redirect('/admin/document-templates');

        } catch (\Exception $e) {
            Session::flash('error', $e->getMessage());
            $this->back();
        }
    }

    public function show(array $params) {
        $id = (int)($params['id'] ?? 0);
        $template = DocumentTemplate::findById($id);
        if (!$template) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $db = Database::getInstance();
        $audits = $db->prepare("
            SELECT a.*, u.full_name as user_name 
            FROM audit_logs a 
            JOIN users u ON a.user_id = u.id 
            WHERE a.entity_type = 'document_templates' AND a.entity_id = ? 
            ORDER BY a.id DESC 
            LIMIT 10
        ");
        $audits->execute([$id]);

        View::render('document_templates/show', [
            'title' => 'Στοιχεία Προτύπου Εγγράφου',
            'template' => $template,
            'audits' => $audits->fetchAll()
        ]);
    }

    public function downloadOriginal(array $params) {
        $id = (int)($params['id'] ?? 0);
        $template = DocumentTemplate::findById($id);
        if (!$template || !file_exists($template['original_file_path'])) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $this->logAudit('original.downloaded', 'document_templates', $id, [
            'title' => $template['title']
        ]);

        if (strtolower($template['source_type']) === 'pdf') {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . basename($template['original_file_path']) . '"');
        } else {
            header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            header('Content-Disposition: attachment; filename="' . basename($template['original_file_path']) . '"');
        }
        header('Content-Length: ' . filesize($template['original_file_path']));
        readfile($template['original_file_path']);
        exit;
    }

    public function downloadConverted(array $params) {
        $id = (int)($params['id'] ?? 0);
        $template = DocumentTemplate::findById($id);
        if (!$template || !$template['converted_pdf_path'] || !file_exists($template['converted_pdf_path'])) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $this->logAudit('converted.downloaded', 'document_templates', $id, [
            'title' => $template['title']
        ]);

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($template['converted_pdf_path']) . '"');
        header('Content-Length: ' . filesize($template['converted_pdf_path']));
        readfile($template['converted_pdf_path']);
        exit;
    }

    private function resolvePreviewPdfUrl(array $template): string {
        $id = (int)$template['id'];
        if (strtolower($template['source_type']) === 'pdf') {
            return "/admin/document-templates/{$id}/file/original";
        }
        return "/admin/document-templates/{$id}/file/converted";
    }

    public function preview(array $params) {
        $id = (int)($params['id'] ?? 0);
        $template = DocumentTemplate::findById($id);
        if (!$template) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $pdfPath = (strtolower($template['source_type']) === 'pdf') ? $template['original_file_path'] : $template['converted_pdf_path'];
        if (!$pdfPath || !file_exists($pdfPath)) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $this->logAudit('template.preview.opened', 'document_templates', $id, [
            'title' => $template['title']
        ]);

        View::render('document_templates/preview', [
            'title' => 'Προεπισκόπηση Προτύπου',
            'template' => $template,
            'pdfUrl' => $this->resolvePreviewPdfUrl($template)
        ]);
    }

    public function retryConversion(array $params) {
        $id = (int)($params['id'] ?? 0);
        $template = DocumentTemplate::findById($id);
        if (!$template || $template['source_type'] !== 'docx') {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $libreOfficePath = $this->getLibreOfficePath();
        if (!$libreOfficePath) {
            Session::flash('error', 'Η μετατροπή αρχείων DOCX δεν είναι διαθέσιμη στον διακομιστή.');
            $this->back();
        }

        try {
            $convertedDir = __DIR__ . '/../../storage/document_templates/converted';
            if (!is_dir($convertedDir)) {
                mkdir($convertedDir, 0755, true);
            }
            $outputPdfName = pathinfo($template['original_file_path'], PATHINFO_FILENAME) . '.pdf';
            $convertedPath = $convertedDir . '/' . $outputPdfName;

            $this->logAudit('conversion.retried', 'document_templates', $id, [
                'title' => $template['title']
            ]);

            // Run LibreOffice conversion with 90s timeout
            $success = $this->executeConversionWithTimeout($libreOfficePath, $template['original_file_path'], $convertedDir, 90);

            if (!$success || !file_exists($convertedPath)) {
                if (file_exists($convertedPath)) {
                    @unlink($convertedPath);
                }
                $this->logAudit('docx.conversion.failed', 'document_templates', $id, [
                    'title' => $template['title'],
                    'error' => 'Conversion process timed out or failed to produce PDF output.'
                ]);
                throw new \Exception("Αποτυχία μετατροπής αρχείου DOCX σε PDF λόγω σφάλματος ή υπέρβασης ορίου χρόνου (timeout).");
            }

            $pdfData = PdfParserService::parseMetadata($convertedPath);

            $db = Database::getInstance();
            $db->prepare("UPDATE document_templates SET converted_pdf_path = ? WHERE id = ?")
               ->execute([$convertedPath, $id]);

            $db->prepare("UPDATE document_template_versions SET pdf_file_path = ?, page_count = ?, page_dimensions_json = ? WHERE template_id = ?")
               ->execute([$convertedPath, $pdfData['page_count'], json_encode($pdfData['dimensions']), $id]);

            Session::flash('success', 'Η μετατροπή ολοκληρώθηκε επιτυχώς.');
            $this->redirect("/admin/document-templates/{$id}");

        } catch (\Exception $e) {
            Session::flash('error', $e->getMessage());
            $this->back();
        }
    }

    public function designer(array $params) {
        $id = (int)($params['id'] ?? 0);
        $template = DocumentTemplate::findById($id);
        if (!$template) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $pdfPath = (strtolower($template['source_type']) === 'pdf') ? $template['original_file_path'] : $template['converted_pdf_path'];
        if (!$pdfPath || !file_exists($pdfPath)) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        View::render('document_templates/designer', [
            'title' => 'Σχεδιαστής Πεδίων Προτύπου',
            'template' => $template,
            'pdfUrl' => $this->resolvePreviewPdfUrl($template)
        ]);
    }

    public function saveDesignerSchema(array $params) {
        $this->checkCsrf();
        $id = (int)($params['id'] ?? 0);
        $template = DocumentTemplate::findById($id);
        if (!$template) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        if ($template['status'] === 'published') {
            Session::flash('error', 'Δεν επιτρέπεται η τροποποίηση δημοσιευμένου (published) προτύπου. Παρακαλώ δημιουργήστε νέα έκδοση (Draft Version).');
            $this->redirect("/admin/document-templates/{$id}");
        }

        $reqData = Request::all();
        $schema = $reqData['fields_schema'] ?? '[]';

        // Validate JSON fields structure
        $fieldsList = json_decode($schema, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($fieldsList)) {
            Session::flash('error', 'Τα δεδομένα του σχεδιασμού δεν είναι έγκυρα JSON.');
            $this->back();
        }

        // Validate individual fields coordinate offsets and values
        $supportedTypes = ['text', 'number', 'date', 'signature', 'consent'];
        foreach ($fieldsList as $f) {
            if (empty($f['key']) || !preg_match('/^[a-zA-Z0-9_\-]+$/', $f['key'])) {
                Session::flash('error', 'Μη έγκυρο key πεδίου: ' . ($f['key'] ?? ''));
                $this->back();
            }
            if (empty($f['type']) || !in_array($f['type'], $supportedTypes)) {
                Session::flash('error', 'Μη υποστηριζόμενος τύπος πεδίου: ' . ($f['type'] ?? ''));
                $this->back();
            }
            $page = (int)($f['page'] ?? 0);
            if ($page < 1) {
                Session::flash('error', 'Μη έγκυρος αριθμός σελίδας: ' . $page);
                $this->back();
            }
            
            $x = (float)($f['x_ratio'] ?? -1);
            $y = (float)($f['y_ratio'] ?? -1);
            $w = (float)($f['width_ratio'] ?? -1);
            $h = (float)($f['height_ratio'] ?? -1);

            if ($x < 0 || $x > 1 || $y < 0 || $y > 1) {
                Session::flash('error', 'Οι συντεταγμένες x_ratio/y_ratio πρέπει να είναι μεταξύ 0 και 1.');
                $this->back();
            }
            if ($w <= 0 || $w > 1 || $h <= 0 || $h > 1) {
                Session::flash('error', 'Οι διαστάσεις width_ratio/height_ratio πρέπει να είναι μεταξύ 0 και 1.');
                $this->back();
            }
            if (($x + $w) > 1.001 || ($y + $h) > 1.001) {
                Session::flash('error', 'Το πεδίο ξεπερνά τα όρια της σελίδας.');
                $this->back();
            }

            // Validate display_order value
            if (isset($f['display_order'])) {
                $orderVal = (int)$f['display_order'];
                if ($orderVal < 0 || $orderVal > 100000) {
                    Session::flash('error', 'Η σειρά εμφάνισης στη φόρμα πρέπει να είναι μεταξύ 0 και 100000.');
                    $this->back();
                }
            }
        }

        try {
            $db = Database::getInstance();
            $db->beginTransaction();

            $stmt = $db->prepare("UPDATE document_template_versions SET fields_schema_json = ? WHERE id = ?");
            $stmt->execute([$schema, $template['current_version_id']]);

            $db->commit();

            $this->logAudit('template.designer.saved', 'document_templates', $id, [
                'title' => $template['title']
            ]);

            Session::flash('success', 'Ο σχεδιασμός των πεδίων αποθηκεύτηκε επιτυχώς.');
            $this->redirect("/admin/document-templates/{$id}");

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
        $template = DocumentTemplate::findById($id);
        if (!$template) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        try {
            $db = Database::getInstance();
            $db->beginTransaction();

            // Set template status to published
            $stmt1 = $db->prepare("UPDATE document_templates SET status = 'published' WHERE id = ?");
            $stmt1->execute([$id]);

            // Set active version status to published
            $stmt2 = $db->prepare("UPDATE document_template_versions SET status = 'published', published_by = ?, published_at = NOW() WHERE id = ?");
            $stmt2->execute([Auth::id(), $template['current_version_id']]);

            $db->commit();

            $this->logAudit('template.publish', 'document_templates', $id, [
                'title' => $template['title']
            ]);

            Session::flash('success', 'Το πρότυπο εγγράφου δημοσιεύτηκε με επιτυχία.');
            $this->redirect("/admin/document-templates/{$id}");

        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Session::flash('error', $e->getMessage());
            $this->back();
        }
    }

    public function createDraftVersion(array $params) {
        $this->checkCsrf();
        $id = (int)($params['id'] ?? 0);
        $template = DocumentTemplate::findById($id);
        if (!$template || $template['status'] !== 'published') {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        try {
            $db = Database::getInstance();
            $db->beginTransaction();

            // Re-fetch current version data to copy
            $stmt = $db->prepare("SELECT * FROM document_template_versions WHERE id = ?");
            $stmt->execute([$template['current_version_id']]);
            $currVer = $stmt->fetch();

            if (!$currVer) {
                throw new \Exception("Δεν βρέθηκε η ενεργή έκδοση του προτύπου.");
            }

            // Create new version with status draft and increment version_number
            $newVersionNumber = (int)$currVer['version_number'] + 1;
            $stmtIns = $db->prepare("
                INSERT INTO document_template_versions (template_id, version_number, pdf_file_path, page_count, page_dimensions_json, fields_schema_json, consent_text, consent_version, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'draft')
            ");
            $stmtIns->execute([
                $id,
                $newVersionNumber,
                $currVer['pdf_file_path'],
                $currVer['page_count'],
                $currVer['page_dimensions_json'],
                $currVer['fields_schema_json'],
                $currVer['consent_text'],
                $currVer['consent_version']
            ]);
            $newVersionId = $db->lastInsertId();

            // Set current version id link, and set template status to draft
            $stmtUpd = $db->prepare("UPDATE document_templates SET status = 'draft', current_version_id = ? WHERE id = ?");
            $stmtUpd->execute([$newVersionId, $id]);

            $db->commit();

            $this->logAudit('template.draft.created', 'document_templates', $id, [
                'title' => $template['title'],
                'version' => $newVersionNumber
            ]);

            Session::flash('success', "Δημιουργήθηκε νέα Draft έκδοση {$newVersionNumber} με επιτυχία.");
            $this->redirect("/admin/document-templates/{$id}");

        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            Session::flash('error', $e->getMessage());
            $this->back();
        }
    }
}
