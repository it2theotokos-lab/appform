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
    public function exportFilteredSubmissions($params) {
        $format = strtolower((string)($params['format'] ?? 'csv'));
        if (!in_array($format, ['csv', 'excel'], true)) {
            http_response_code(404);
            exit;
        }

        // This endpoint is intentionally restricted to administrators and reviewers.
        if (Auth::role() !== 'administrator' && !Auth::hasPermission('submissions.review')) {
            http_response_code(403);
            View::render('errors/403');
            exit;
        }

        $db = Database::getInstance();
        $userId = (int)Auth::id();
        $canViewAll = Auth::role() === 'administrator' || Auth::hasPermission('submissions.view.all');
        $canViewSubordinates = $canViewAll
            || Auth::hasPermission('submissions.view.subordinates')
            || Auth::hasPermission('submissions.review');

        $requestedScope = trim((string)($_GET['scope'] ?? ''));
        if ($requestedScope === 'all' && $canViewAll) {
            $activeScope = 'all';
        } elseif ($requestedScope === 'subordinates' && $canViewSubordinates) {
            $activeScope = 'subordinates';
        } elseif ($requestedScope === 'own') {
            $activeScope = 'own';
        } else {
            $activeScope = $canViewAll ? 'all' : ($canViewSubordinates ? 'subordinates' : 'own');
        }

        $where = [];
        $queryParams = [];
        if ($activeScope === 'own') {
            $where[] = 's.user_id = ?';
            $queryParams[] = $userId;
        } elseif ($activeScope === 'subordinates') {
            $ids = array_unique(array_merge([$userId], \App\Services\OrganizationalScopeService::getSubordinateIds($userId)));
            $where[] = 's.user_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')';
            foreach ($ids as $id) $queryParams[] = (int)$id;
        }

        $search = trim((string)($_GET['search'] ?? ''));
        $formId = trim((string)($_GET['form_id'] ?? ''));
        $status = trim((string)($_GET['status'] ?? ''));
        $submitter = trim((string)($_GET['submitter'] ?? ''));
        $dateFrom = trim((string)($_GET['date_from'] ?? ''));
        $dateTo = trim((string)($_GET['date_to'] ?? ''));

        if ($search !== '') {
            $where[] = "(COALESCE(f.title, '') LIKE ? OR u.username LIKE ? OR s.data_json LIKE ?)";
            array_push($queryParams, "%{$search}%", "%{$search}%", "%{$search}%");
        }
        if ($formId !== '') { $where[] = 's.form_id = ?'; $queryParams[] = $formId; }
        if ($status !== '') { $where[] = 's.status = ?'; $queryParams[] = $status; }
        if ($submitter !== '') { $where[] = 'u.username LIKE ?'; $queryParams[] = "%{$submitter}%"; }
        if ($dateFrom !== '') { $where[] = 's.created_at >= ?'; $queryParams[] = $dateFrom . ' 00:00:00'; }
        if ($dateTo !== '') { $where[] = 's.created_at <= ?'; $queryParams[] = $dateTo . ' 23:59:59'; }

        $sql = "
            SELECT s.*, COALESCE(f.title, 'Αρχειοθετημένη/Διαγραμμένη Φόρμα') AS form_title,
                   f.slug AS form_slug, u.username, u.email AS submitter_email,
                   COALESCE(fv.schema_json, '{\"schemaVersion\":1,\"sections\":[]}') AS schema_json
            FROM form_submissions s
            LEFT JOIN forms f ON s.form_id = f.id
            LEFT JOIN form_versions fv ON s.form_version_id = fv.id
            JOIN users u ON s.user_id = u.id
        ";
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY s.created_at DESC';

        $stmt = $db->prepare($sql);
        $stmt->execute($queryParams);
        $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $fieldColumns = [];
        foreach ($submissions as $submission) {
            $schema = json_decode($submission['schema_json'], true) ?: [];
            foreach ($schema['sections'] ?? [] as $section) {
                foreach ($section['fields'] ?? [] as $field) {
                    $key = (string)($field['key'] ?? '');
                    if ($key === '' || in_array($field['type'] ?? '', ['heading', 'divider', 'page_break', 'html'], true)) continue;
                    if (!isset($fieldColumns[$key])) {
                        $fieldColumns[$key] = [
                            'label' => (string)($field['label'] ?? $key),
                            'field' => $field,
                        ];
                    }
                }
            }
        }

        $headers = ['ID', 'UUID', 'Φόρμα', 'Υποβλήθηκε από', 'Email', 'Κατάσταση', 'Δημιουργήθηκε', 'Υποβλήθηκε'];
        foreach ($fieldColumns as $column) $headers[] = $column['label'];

        $rows = [];
        foreach ($submissions as $submission) {
            $answers = json_decode($submission['data_json'], true) ?: [];
            $row = [
                $submission['id'], $submission['uuid'], $submission['form_title'],
                $submission['username'], $submission['submitter_email'], $submission['status'],
                $submission['created_at'], $submission['submitted_at'] ?: '',
            ];
            foreach ($fieldColumns as $key => $column) {
                $row[] = array_key_exists($key, $answers)
                    ? \App\Services\NotificationTemplateService::formatSubmittedValue($answers[$key], $column['field'])
                    : '';
            }
            $rows[] = $row;
        }

        $stamp = date('Ymd_His');
        if ($format === 'excel') {
            $content = ExcelExportService::export($headers, $rows);
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="submissions_' . $stamp . '.xlsx"');
        } else {
            $content = CsvExportService::export($headers, $rows);
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="submissions_' . $stamp . '.csv"');
        }
        header('X-Content-Type-Options: nosniff');
        echo $content;
        exit;
    }

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
