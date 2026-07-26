<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\View;
use App\Core\Database;
use App\Models\Form;
use App\Models\FormVersion;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Services\CsvExportService;
use App\Services\ExcelExportService;
use App\Services\PdfExportService;
use PDO;

class ExportController extends Controller {
    public function exportCsv($params) {
        $formId = (int)$params['formId'];
        $form = Form::findById($formId);
        if (!$form) die("Not Found");

        // Fetch submissions
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id, uuid, status, data_json, submitted_at FROM form_submissions WHERE form_id = ?");
        $stmt->execute([$formId]);
        $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch schema keys
        $version = FormVersion::getVersion($formId, $form['current_version']);
        $schema = json_decode($version['schema_json'], true);
        $headers = ['ID', 'UUID', 'Status', 'Submitted At'];
        $fields = [];

        foreach ($schema['sections'] as $sec) {
            foreach ($sec['fields'] as $f) {
                if (in_array($f['type'], ['heading', 'divider'])) continue;
                $headers[] = $f['label'];
                $fields[] = $f['key'];
            }
        }

        $rows = [];
        foreach ($submissions as $sub) {
            $data = json_decode($sub['data_json'], true);
            $row = [
                $sub['id'],
                $sub['uuid'],
                $sub['status'],
                $sub['submitted_at'] ?: 'DRAFT'
            ];

            foreach ($fields as $key) {
                $val = $data[$key] ?? '';
                $row[] = is_array($val) ? implode(', ', $val) : $val;
            }
            $rows[] = $row;
        }

        $csv = CsvExportService::export($headers, $rows);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="export_form_' . $form['slug'] . '_' . date('Ymd_His') . '.csv"');
        echo $csv;
        exit;
    }

    public function exportExcel($params) {
        $formId = (int)$params['formId'];
        $form = Form::findById($formId);
        if (!$form) die("Not Found");

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id, uuid, status, data_json, submitted_at FROM form_submissions WHERE form_id = ?");
        $stmt->execute([$formId]);
        $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $version = FormVersion::getVersion($formId, $form['current_version']);
        $schema = json_decode($version['schema_json'], true);
        $headers = ['ID', 'UUID', 'Status', 'Submitted At'];
        $fields = [];

        foreach ($schema['sections'] as $sec) {
            foreach ($sec['fields'] as $f) {
                if (in_array($f['type'], ['heading', 'divider'])) continue;
                $headers[] = $f['label'];
                $fields[] = $f['key'];
            }
        }

        $rows = [];
        foreach ($submissions as $sub) {
            $data = json_decode($sub['data_json'], true);
            $row = [
                $sub['id'],
                $sub['uuid'],
                $sub['status'],
                $sub['submitted_at'] ?: 'DRAFT'
            ];

            foreach ($fields as $key) {
                $val = $data[$key] ?? '';
                $row[] = is_array($val) ? implode(', ', $val) : $val;
            }
            $rows[] = $row;
        }

        $xls = ExcelExportService::export($headers, $rows);

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="export_form_' . $form['slug'] . '_' . date('Ymd_His') . '.xls"');
        echo $xls;
        exit;
    }

    public function exportPdf($params) {
        $uuid = $params['uuid'];
        $submission = Submission::getDetailsByUuid($uuid);
        if (!$submission) die("Not Found");

        // Authorization check
        if (Auth::role() !== 'administrator' && Auth::role() !== 'manager') {
            if ($submission['user_id'] !== Auth::id()) {
                die("Forbidden");
            }
        }

        $schema = json_decode($submission['schema_json'], true);
        $answers = json_decode($submission['data_json'], true);

        // Fetch form to verify if Anonymous Submissions is enabled
        $formObj = Form::findById((int)$submission['form_id']);
        if ($formObj && !empty($formObj['is_anonymous'])) {
            $submission['submitter_name'] = 'Anonymized (Ανώνυμη Υποβολή)';
            $submission['username'] = 'anonymized';
            $submission['user_email'] = 'anonymized@example.local';
            $submission['user_id'] = 0;
        }

        $html = PdfExportService::generateSubmissionHtml($submission, $schema, $answers);

        // Serve HTML as printable page representation natively
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        exit;
    }
}
