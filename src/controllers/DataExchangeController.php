<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Session;
use App\Core\Request;
use App\Core\View;
use App\Core\Database;
use App\Services\DataExchangeService;
use App\Services\CsvExportService;
use App\Services\ExcelExportService;
use App\Services\ImportService;
use App\Services\SurveyAnalyticsService;
use App\Services\SurveyReportService;
use PDO;
use Exception;

class DataExchangeController extends Controller {

    public function index(array $params = []): void {
        if (!Auth::check() || !Auth::hasPermission('data_exchange.view')) {
            Session::flash('error', 'Δεν έχετε δικαίωμα πρόσβασης σε αυτή τη σελίδα.');
            header("Location: /dashboard");
            exit;
        }

        $db = Database::getInstance();
        
        // Fetch data export jobs history
        $exportJobs = $db->query("
            SELECT j.*, u.username 
            FROM data_export_jobs j 
            JOIN users u ON j.user_id = u.id 
            ORDER BY j.created_at DESC 
            LIMIT 20
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Fetch data import jobs history
        $importJobs = $db->query("
            SELECT j.*, u.username 
            FROM data_import_jobs j 
            JOIN users u ON j.user_id = u.id 
            ORDER BY j.created_at DESC 
            LIMIT 20
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Fetch forms list for reporting and exports
        $forms = $db->query("SELECT id, title, slug FROM forms WHERE is_active = 1 ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);
        
        // Fetch system users list for filters
        $users = $db->query("SELECT id, username, full_name FROM users ORDER BY username ASC")->fetchAll(PDO::FETCH_ASSOC);

        // Fetch departments list from LDAP users synced
        $departments = $db->query("SELECT DISTINCT directory_department FROM users WHERE directory_department IS NOT NULL AND directory_department != ''")->fetchAll(PDO::FETCH_COLUMN);

        View::render('admin/data_exchange/index', [
            'title' => 'Εισαγωγές, Εξαγωγές & Αναφορές',
            'entities' => DataExchangeService::getSupportedEntities(),
            'exportJobs' => $exportJobs,
            'importJobs' => $importJobs,
            'forms' => $forms,
            'users' => $users,
            'departments' => $departments,
            'selectedEntity' => $_GET['entity'] ?? 'users',
            'selectedForm' => $_GET['form_id'] ?? ''
        ]);
    }

    public function export(array $params = []): void {
        if (!Auth::check() || !Auth::hasPermission('data_exchange.export')) {
            http_response_code(403);
            exit("Access Denied");
        }

        $entity = $_POST['entity'] ?? '';
        $format = $_POST['format'] ?? 'csv';
        
        $filters = [
            'date_from' => $_POST['date_from'] ?? '',
            'date_to' => $_POST['date_to'] ?? '',
            'user_id' => $_POST['user_id'] ?? '',
            'status' => $_POST['status'] ?? ''
        ];

        $data = DataExchangeService::getData($entity, $filters);
        if (empty($data)) {
            Session::flash('error', 'Δεν βρέθηκαν δεδομένα για εξαγωγή με τα επιλεγμένα κριτήρια.');
            header("Location: /admin/data-exchange");
            exit;
        }

        $headers = array_keys($data[0]);

        $fileName = $entity . '-export-' . date('Y-m-d') . '.' . $format;

        // Record export job in DB
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO data_export_jobs (uuid, user_id, entity_type, format, status, file_name, record_count) 
            VALUES (?, ?, ?, ?, 'completed', ?, ?)
        ");
        $stmt->execute([
            uniqid('exp_'),
            Auth::id(),
            $entity,
            $format,
            $fileName,
            count($data)
        ]);

        if ($format === 'json') {
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            echo json_encode([
                'metadata' => [
                    'entity' => $entity,
                    'timestamp' => date('c'),
                    'schema_version' => '1.1.0'
                ],
                'data' => $data
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit;
        } elseif ($format === 'xlsx') {
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            echo ExcelExportService::export($headers, $data);
            exit;
        } else {
            // Default CSV
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            echo CsvExportService::export($headers, $data);
            exit;
        }
    }

    public function import(array $params = []): void {
        if (!Auth::check() || !Auth::hasPermission('data_exchange.import')) {
            Session::flash('error', 'Δεν έχετε δικαίωμα εισαγωγής δεδομένων.');
            header("Location: /admin/data-exchange");
            exit;
        }

        $entity = $_POST['entity'] ?? '';
        $strategy = $_POST['strategy'] ?? 'skip';
        $matchingField = $_POST['matching_field'] ?? 'id';
        $dryRun = isset($_POST['dry_run']) && $_POST['dry_run'] == 1;

        if (empty($_FILES['import_file']['tmp_name'])) {
            Session::flash('error', 'Παρακαλώ επιλέξτε ένα αρχείο για εισαγωγή.');
            header("Location: /admin/data-exchange");
            exit;
        }

        $tmpPath = $_FILES['import_file']['tmp_name'];
        $origName = $_FILES['import_file']['name'];
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        $rows = [];

        // Parse file based on extension
        if ($ext === 'json') {
            $jsonContent = file_get_contents($tmpPath);
            $decoded = json_decode($jsonContent, true);
            $rows = $decoded['data'] ?? $decoded ?? [];
        } elseif ($ext === 'csv') {
            if (($handle = fopen($tmpPath, "r")) !== FALSE) {
                // Read headers
                $headers = fgetcsv($handle, 1000, ";");
                if (!$headers) {
                    $headers = fgetcsv($handle, 1000, ",");
                }
                while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
                    if (count($headers) == count($data)) {
                        $rows[] = array_combine($headers, $data);
                    }
                }
                fclose($handle);
            }
        }

        if (empty($rows)) {
            Session::flash('error', 'Το αρχείο είναι κενό ή έχει μη υποστηριζόμενη μορφή.');
            header("Location: /admin/data-exchange");
            exit;
        }

        $res = ImportService::import($entity, $rows, $strategy, $matchingField, $dryRun, Auth::id());

        // Save job stats
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO data_import_jobs (uuid, user_id, entity_type, filename, file_path, duplicate_strategy, matching_field, status, total_rows, successful_rows, failed_rows, skipped_rows, is_dry_run) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            uniqid('imp_'),
            Auth::id(),
            $entity,
            $origName,
            $tmpPath,
            $strategy,
            $matchingField,
            $res['status'],
            $res['total'],
            $res['success'],
            $res['failed'],
            $res['skipped'],
            $dryRun ? 1 : 0
        ]);
        $jobId = $db->lastInsertId();

        // Save individual errors if any
        if (!empty($res['errors'])) {
            $stmtErr = $db->prepare("INSERT INTO data_import_errors (import_job_id, row_num, record_identifier, error_message) VALUES (?, ?, ?, ?)");
            foreach ($res['errors'] as $err) {
                $stmtErr->execute([$jobId, $err['row'], $err['identifier'], $err['message']]);
            }
        }

        $msg = "Επιτυχής εισαγωγή: " . $res['success'] . " γραμμές.";
        if ($res['failed'] > 0) {
            $msg .= " Απέτυχαν: " . $res['failed'] . " γραμμές.";
        }
        if ($res['skipped'] > 0) {
            $msg .= " Παρακάμφθηκαν: " . $res['skipped'] . " γραμμές.";
        }
        if ($dryRun) {
            $msg = "[DRY RUN] " . $msg;
        }

        Session::flash($res['failed'] > 0 ? 'warning' : 'success', $msg);
        header("Location: /admin/data-exchange");
        exit;
    }

    public function generateReport(array $params = []): void {
        if (!Auth::check() || !Auth::hasPermission('data_exchange.reports')) {
            Session::flash('error', 'Δεν έχετε δικαίωμα δημιουργίας αναφορών.');
            header("Location: /admin/data-exchange");
            exit;
        }

        $formId = (int)($_POST['form_id'] ?? 0);
        if ($formId === 0) {
            Session::flash('error', 'Παρακαλώ επιλέξτε μια φόρμα.');
            header("Location: /admin/data-exchange");
            exit;
        }

        $db = Database::getInstance();
        
        // Fetch Form details
        $stmtForm = $db->prepare("SELECT * FROM forms WHERE id = ?");
        $stmtForm->execute([$formId]);
        $form = $stmtForm->fetch(PDO::FETCH_ASSOC);

        if (!$form) {
            Session::flash('error', 'Η φόρμα δεν βρέθηκε.');
            header("Location: /admin/data-exchange");
            exit;
        }

        $filters = [
            'date_from' => $_POST['date_from'] ?? '',
            'date_to' => $_POST['date_to'] ?? '',
            'department' => $_POST['department'] ?? ''
        ];

        $stats = SurveyAnalyticsService::getFormStats($formId, $filters);

        if (isset($_POST['download_pdf'])) {
            $pdf = SurveyReportService::generatePdf($form, $stats, $filters);
            $fileName = 'survey-results-form-' . $formId . '-' . date('Y-m-d') . '.pdf';
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            echo $pdf;
            exit;
        }

        // Render HTML results preview screen
        View::render('admin/data_exchange/survey_results', [
            'title' => 'Αποτελέσματα Έρευνας - ' . $form['title'],
            'form' => $form,
            'stats' => $stats,
            'filters' => $filters
        ]);
    }

    public function downloadTemplate(array $params = []): void {
        if (!Auth::check() || !Auth::hasPermission('data_exchange.import')) {
            http_response_code(403);
            exit("Access Denied");
        }

        $entity = $_GET['entity'] ?? '';
        $format = $_GET['format'] ?? 'csv';

        $supported = DataExchangeService::getSupportedEntities();
        if (!array_key_exists($entity, $supported) || $entity === 'audit_logs') {
            http_response_code(400);
            exit("Unsupported Entity");
        }

        if ($format === 'xlsx') {
            $file = \App\Services\ImportTemplateService::generateXlsx($entity);
            $fileName = $entity . '-template.xlsx';
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            echo $file;
            exit;
        } else {
            $file = \App\Services\ImportTemplateService::generateCsv($entity);
            $fileName = $entity . '-template.csv';
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            echo $file;
            exit;
        }
    }
}
