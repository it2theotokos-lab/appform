<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Database;
use App\Models\Form;
use App\Models\FormVersion;
use App\Services\AnalyticsService;
use PDO;

class ReportController extends Controller {
    public function analyticsIndex() {
        $forms = Form::getAll();
        View::render('analytics/index', [
            'title' => 'Στατιστικά & Analytics',
            'forms' => $forms
        ]);
    }

    public function formAnalytics($params) {
        $formId = (int)$params['id'];
        $form = Form::findById($formId);

        if (!$form) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $stats = AnalyticsService::getSubmissionStats($formId);

        // Fetch schema field keys configurations
        $version = FormVersion::getVersion($formId, $form['current_version']);
        $schema = json_decode($version['schema_json'], true);

        $fieldsAnalysis = [];
        if (isset($schema['sections'])) {
            foreach ($schema['sections'] as $sec) {
                if (isset($sec['fields'])) {
                    foreach ($sec['fields'] as $f) {
                        if (in_array($f['type'], ['select', 'radio', 'checkbox', 'number', 'yes_no', 'rating'])) {
                            $fieldsAnalysis[$f['key']] = AnalyticsService::getFieldAnalysis($formId, $f['key'], $f);
                        }
                    }
                }
            }
        }

        // Fetch Recent Submissions
        $db = Database::getInstance();
        $stmtSub = $db->prepare("
            SELECT s.*, u.full_name as submitter_name 
            FROM form_submissions s 
            LEFT JOIN users u ON s.user_id = u.id 
            WHERE s.form_id = ? 
            ORDER BY s.updated_at DESC LIMIT 10
        ");
        $stmtSub->execute([$formId]);
        $recentSubmissions = $stmtSub->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($form['is_anonymous'])) {
            foreach ($recentSubmissions as &$rs) {
                $rs['submitter_name'] = 'Anonymized (Ανώνυμη Υποβολή)';
                $rs['user_id'] = 0;
            }
        }

        // Fetch Status History distribution
        $stmtHistory = $db->prepare("
            SELECT new_status, COUNT(*) as count 
            FROM submission_status_history h
            JOIN form_submissions s ON h.submission_id = s.id
            WHERE s.form_id = ?
            GROUP BY new_status
        ");
        $stmtHistory->execute([$formId]);
        $historyStats = $stmtHistory->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

        View::render('analytics/form', [
            'title' => 'Analytics Φόρμας: ' . $form['title'],
            'form' => $form,
            'stats' => $stats,
            'schema' => $schema,
            'fieldsAnalysis' => $fieldsAnalysis,
            'recentSubmissions' => $recentSubmissions,
            'historyStats' => $historyStats
        ]);
    }

    public function exportFormAnalyticsPdf($params) {
        $formId = (int)$params['id'];
        $form = Form::findById($formId);

        if (!$form) {
            http_response_code(404);
            View::render('errors/404');
            exit;
        }

        $stats = AnalyticsService::getSubmissionStats($formId);
        $version = FormVersion::getVersion($formId, $form['current_version']);
        $schema = json_decode($version['schema_json'], true);

        $fieldsAnalysis = [];
        if (isset($schema['sections'])) {
            foreach ($schema['sections'] as $sec) {
                if (isset($sec['fields'])) {
                    foreach ($sec['fields'] as $f) {
                        if (in_array($f['type'], ['select', 'radio', 'checkbox', 'number', 'yes_no', 'rating'])) {
                            $fieldsAnalysis[$f['key']] = AnalyticsService::getFieldAnalysis($formId, $f['key'], $f);
                        }
                    }
                }
            }
        }

        $db = Database::getInstance();
        $stmtSub = $db->prepare("
            SELECT s.*, u.full_name as submitter_name 
            FROM form_submissions s 
            LEFT JOIN users u ON s.user_id = u.id 
            WHERE s.form_id = ? 
            ORDER BY s.updated_at DESC LIMIT 15
        ");
        $stmtSub->execute([$formId]);
        $recentSubmissions = $stmtSub->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($form['is_anonymous'])) {
            foreach ($recentSubmissions as &$rs) {
                $rs['submitter_name'] = 'Anonymized (Ανώνυμη Υποβολή)';
                $rs['user_id'] = 0;
            }
        }

        // Generate print friendly analytics template HTML
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Report - ' . htmlspecialchars($form['title']) . '</title>
    <style>
        body { font-family: "DejaVu Sans", sans-serif; color: #333; margin: 15px; font-size: 11px; }
        .header { margin-bottom: 25px; border-bottom: 2px solid #0056b3; padding-bottom: 10px; }
        .logo-text { font-size: 18px; font-weight: bold; color: #0056b3; }
        .form-title { font-size: 14px; font-weight: bold; margin-top: 5px; }
        .generated { color: #666; font-size: 10px; margin-top: 2px; }
        
        .kpi-row { width: 100%; margin-bottom: 20px; border-spacing: 10px; border-collapse: separate; }
        .kpi-card { background: #f8f9fa; border: 1px solid #ddd; padding: 12px; text-align: center; border-radius: 4px; }
        .kpi-label { font-size: 9px; color: #666; text-transform: uppercase; margin-bottom: 4px; }
        .kpi-value { font-size: 18px; font-weight: bold; }

        .section-title { font-size: 13px; font-weight: bold; color: #0056b3; margin-top: 20px; border-bottom: 1px solid #ddd; padding-bottom: 3px; margin-bottom: 10px; }
        
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .data-table th { background: #f1f3f5; font-weight: bold; text-align: left; padding: 6px; border: 1px solid #dee2e6; }
        .data-table td { padding: 6px; border: 1px solid #dee2e6; }
        .progress-bar-container { background: #e9ecef; height: 6px; border-radius: 3px; position: relative; width: 80px; display: inline-block; vertical-align: middle; margin-right: 5px; }
        .progress-bar-fill { background: #0056b3; height: 100%; border-radius: 3px; }

        .footer { position: fixed; bottom: -10px; left: 0; right: 0; text-align: center; font-size: 8px; color: #999; border-top: 1px solid #eee; padding-top: 5px; }
        .page-number:after { content: counter(page); }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo-text">AppForm Portal</div>
        <div class="form-title">Στατιστική Έκθεση: ' . htmlspecialchars($form['title']) . '</div>
        <div class="generated">Ημερομηνία εξαγωγής: ' . date('d/m/Y H:i') . '</div>
    </div>

    <table class="kpi-row">
        <tr>
            <td class="kpi-card" style="width: 25%;">
                <div class="kpi-label">Συνολικές</div>
                <div class="kpi-value" style="color: #333;">' . $stats['total'] . '</div>
            </td>
            <td class="kpi-card" style="width: 25%;">
                <div class="kpi-label">Εγκεκριμένες</div>
                <div class="kpi-value" style="color: #28a745;">' . $stats['approved'] . '</div>
            </td>
            <td class="kpi-card" style="width: 25%;">
                <div class="kpi-label">Υπό Αξιολόγηση</div>
                <div class="kpi-value" style="color: #ffc107;">' . $stats['under_review'] . '</div>
            </td>
            <td class="kpi-card" style="width: 25%;">
                <div class="kpi-label">Προσχέδια</div>
                <div class="kpi-value" style="color: #17a2b8;">' . $stats['draft'] . '</div>
            </td>
        </tr>
    </table>

    <table class="kpi-row">
        <tr>
            <td class="kpi-card" style="width: 33%;">
                <div class="kpi-label">Επεστραμμένες</div>
                <div class="kpi-value" style="color: #ffc107;">' . $stats['returned'] . '</div>
            </td>
            <td class="kpi-card" style="width: 33%;">
                <div class="kpi-label">Απορριφθείσες</div>
                <div class="kpi-value" style="color: #dc3545;">' . $stats['rejected'] . '</div>
            </td>
            <td class="kpi-card" style="width: 33%;">
                <div class="kpi-label">Υποβληθείσες</div>
                <div class="kpi-value" style="color: #6c757d;">' . $stats['submitted'] . '</div>
            </td>
        </tr>
    </table>';

        if (!empty($form['maximum_submissions'])) {
            $completedCount = $stats['submitted'] + $stats['under_review'] + $stats['returned'] + $stats['approved'] + $stats['rejected'];
            $targetCount = (int)$form['maximum_submissions'];
            $remainingCount = max(0, $targetCount - $completedCount);
            $rate = $targetCount > 0 ? round(($completedCount / $targetCount) * 100, 2) : 0;
            
            // Determine poll status text
            $pollStatus = 'Open';
            $now = date('Y-m-d H:i:s');
            if (!empty($form['submission_expires_at']) && $now > $form['submission_expires_at']) {
                $pollStatus = 'Closed by Date';
            } elseif ($completedCount >= $targetCount) {
                $pollStatus = 'Closed by Capacity';
            }

            $html .= '
            <div class="section-title">Στοιχεία Στόχου & Χωρητικότητας Υποβολών</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Ολοκληρωμένες</th>
                        <th>Στόχος Δείγματος</th>
                        <th>Υπολειπόμενες</th>
                        <th>Ποσοστό Συμμετοχής</th>
                        <th>Κατάσταση Poll</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>' . $completedCount . '</strong></td>
                        <td>' . $targetCount . '</td>
                        <td>' . $remainingCount . '</td>
                        <td><strong>' . $rate . '%</strong></td>
                        <td><strong>' . $pollStatus . '</strong></td>
                    </tr>
                </tbody>
            </table>';
        }

        $html .= '

    <div class="section-title">Κατανομή Απαντήσεων ανά Πεδίο</div>';

        if (!empty($form['survey_analytics']) && !empty($form['survey_charts_pdf'])) {
            foreach ($fieldsAnalysis as $key => $analysis) {
                $html .= '<div style="margin-bottom: 15px; page-break-inside: avoid;">
                    <div style="font-weight: bold; margin-bottom: 5px;">' . htmlspecialchars($key) . ' (' . strtoupper($analysis['type']) . ')</div>';
                
                if ($analysis['type'] === 'number') {
                    $html .= '<table class="data-table" style="width: 50%;">
                        <tr><td style="font-weight: bold;">Average</td><td>' . $analysis['avg'] . '</td></tr>
                        <tr><td style="font-weight: bold;">Sum</td><td>' . $analysis['sum'] . '</td></tr>
                        <tr><td style="font-weight: bold;">Min</td><td>' . $analysis['min'] . '</td></tr>
                        <tr><td style="font-weight: bold;">Max</td><td>' . $analysis['max'] . '</td></tr>
                    </table>';
                } elseif ($analysis['type'] === 'rating') {
                    $html .= '<table class="data-table" style="width: 50%;">
                        <tr><td style="font-weight: bold;">Average Rating</td><td>' . ($analysis['avg'] ?? 0) . ' / 5.00</td></tr>
                    </table>';
                    $html .= '<table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 50%;">Επιλογή</th>
                                <th style="width: 20%;">Υποβολές</th>
                                <th style="width: 30%;">Ποσοστό</th>
                            </tr>
                        </thead>
                        <tbody>';
                    foreach ($analysis['distribution'] as $item) {
                        $html .= '<tr>
                            <td><strong>' . htmlspecialchars($item['label']) . '</strong></td>
                            <td>' . $item['count'] . '</td>
                            <td>
                                <div class="progress-bar-container">
                                    <div class="progress-bar-fill" style="width: ' . $item['percentage'] . '%;"></div>
                                </div>
                                <span>' . $item['percentage'] . '%</span>
                            </td>
                        </tr>';
                    }
                    $html .= '</tbody></table>';
                } else {
                    $html .= '<table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 50%;">Επιλογή</th>
                                <th style="width: 20%;">Υποβολές</th>
                                <th style="width: 30%;">Ποσοστό</th>
                            </tr>
                        </thead>
                        <tbody>';
                    foreach ($analysis['distribution'] as $item) {
                        $html .= '<tr>
                            <td><strong>' . htmlspecialchars($item['label']) . '</strong></td>
                            <td>' . $item['count'] . '</td>
                            <td>
                                <div class="progress-bar-container">
                                    <div class="progress-bar-fill" style="width: ' . $item['percentage'] . '%;"></div>
                                </div>
                                <span>' . $item['percentage'] . '%</span>
                            </td>
                        </tr>';
                    }
                    $html .= '</tbody></table>';
                }
                $html .= '</div>';
            }
        } else {
            $html .= '<div style="color: #666; font-style: italic; margin-bottom: 20px;">Τα Survey Analytics και τα γραφήματα απαντήσεων έχουν απενεργοποιηθεί στην έκθεση PDF για αυτή τη φόρμα.</div>';
        }

        $html .= '<div style="page-break-before: always;"></div>
        
        <div class="section-title">Πρόσφατες Υποβολές</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Χρήστης</th>
                    <th>Κατάσταση</th>
                    <th>Ημερομηνία</th>
                </tr>
            </thead>
            <tbody>';
        foreach ($recentSubmissions as $sub) {
            $html .= '<tr>
                <td>' . $sub['id'] . '</td>
                <td>' . htmlspecialchars($sub['submitter_name'] ?: 'Εξωτερικός') . '</td>
                <td><strong>' . strtoupper($sub['status']) . '</strong></td>
                <td>' . ($sub['submitted_at'] ? date('d/m/Y H:i', strtotime($sub['submitted_at'])) : date('d/m/Y H:i', strtotime($sub['created_at'])) . ' (DRAFT)') . '</td>
            </tr>';
        }
        $html .= '</tbody>
        </table>

        <div class="footer">
            Στατιστική Έκθεση AppForm - Σελίδα <span class="page-number"></span>
        </div>
</body>
</html>';

        if (class_exists('\Dompdf\Dompdf')) {
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="report_form_' . $form['slug'] . '_' . date('Ymd_His') . '.pdf"');
            echo $dompdf->output();
            exit;
        }

        // Fallback printable HTML format
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        exit;
    }
}
