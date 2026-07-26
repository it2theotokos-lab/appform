<?php
namespace App\Services;

use Dompdf\Dompdf;

class SurveyReportService {

    public static function generatePdf(array $form, array $stats, array $filters = []): string {
        $logoUrl = "/assets/images/logo.png"; // Placeholder path representation

        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Έκθεση Αποτελεσμάτων - ' . htmlspecialchars($form['title']) . '</title>
    <style>
        body { font-family: "DejaVu Sans", sans-serif; font-size: 11px; color: #1e293b; margin: 20px; line-height: 1.5; }
        .header { border-bottom: 2px solid #4f46e5; padding-bottom: 10px; margin-bottom: 20px; }
        .header table { width: 100%; }
        .logo-text { font-size: 20px; font-weight: bold; color: #4f46e5; }
        .report-title { font-size: 16px; font-weight: bold; color: #0f172a; margin-top: 10px; }
        .meta-box { background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px; border-radius: 6px; margin-bottom: 20px; }
        .meta-table { width: 100%; border-collapse: collapse; }
        .meta-table td { padding: 4px 8px; vertical-align: top; }
        .meta-table td.label { font-weight: bold; color: #64748b; width: 25%; }
        
        .section-title { font-size: 13px; font-weight: bold; color: #4f46e5; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; margin-top: 25px; margin-bottom: 12px; }
        .question-card { margin-bottom: 20px; page-break-inside: avoid; }
        .question-title { font-size: 11px; font-weight: bold; color: #0f172a; margin-bottom: 8px; }
        .stats-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .stats-table th, .stats-table td { border: 1px solid #e2e8f0; padding: 6px 10px; text-align: left; }
        .stats-table th { background: #f1f5f9; font-weight: bold; color: #475569; }
        
        .footer { position: fixed; bottom: 0; left: 0; right: 0; height: 30px; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 8px; font-size: 9px; color: #94a3b8; }
        .page-number:after { content: counter(page); }
    </style>
</head>
<body>
    <div class="header">
        <table>
            <tr>
                <td class="logo-text">AppForm Portal</td>
                <td style="text-align: right; color: #94a3b8;">Ημερομηνία: ' . date('d/m/Y') . '</td>
            </tr>
        </table>
        <div class="report-title">Έκθεση Αποτελεσμάτων Έρευνας</div>
        <div style="font-weight: 500; color: #475569; font-size: 12px; margin-top: 4px;">' . htmlspecialchars($form['title']) . '</div>
    </div>

    <div class="meta-box">
        <table class="meta-table">
            <tr>
                <td class="label">Συνολικές Υποβολές:</td>
                <td>' . $stats['total_submissions'] . '</td>
                <td class="label">Φίλτρο Τμήματος:</td>
                <td>' . htmlspecialchars($filters['department'] ?? 'Όλα τα Τμήματα') . '</td>
            </tr>
            <tr>
                <td class="label">Περίοδος Αναφοράς:</td>
                <td>' . (!empty($filters['date_from']) ? date('d/m/Y', strtotime($filters['date_from'])) : 'Αρχή') . ' - ' . (!empty($filters['date_to']) ? date('d/m/Y', strtotime($filters['date_to'])) : 'Σήμερα') . '</td>
                <td class="label">Κατάσταση:</td>
                <td>SUBMITTED</td>
            </tr>
        </table>
    </div>

    <div class="section-title">Στατιστικά Ερωτήσεων</div>';

        foreach ($stats['questions'] as $q) {
            $html .= '<div class="question-card">';
            $html .= '<div class="question-title">' . htmlspecialchars($q['label']) . ' (' . htmlspecialchars($q['key']) . ')</div>';
            $html .= '<div style="color: #64748b; margin-bottom: 6px;">Απαντήσεις: ' . $q['total_responses'] . ' | Παραλείψεις: ' . $q['skipped'] . '</div>';

            if (!empty($q['distribution'])) {
                $chartType = ($q['type'] === 'radio' || $q['type'] === 'select' || $q['type'] === 'yesno') ? 'pie' : 'bar';
                $chartSvg = \App\Services\ChartImageService::generateSvgDataUri($q, $chartType);

                if (!empty($chartSvg)) {
                    $html .= '<div style="text-align: center; margin-bottom: 10px;"><img src="' . $chartSvg . '" style="max-width: 380px; max-height: 180px;"></div>';
                }

                $html .= '<table class="stats-table">';
                $html .= '<thead><tr><th>Επιλογή / Απάντηση</th><th style="width: 80px;">Πλήθος</th><th style="width: 80px;">Ποσοστό</th></tr></thead>';
                $html .= '<tbody>';
                foreach ($q['distribution'] as $d) {
                    $html .= '<tr><td>' . htmlspecialchars($d['value']) . '</td><td>' . $d['count'] . '</td><td>' . $d['percentage'] . '%</td></tr>';
                }
                $html .= '</tbody></table>';
            } elseif ($q['average'] !== null) {
                $html .= '<table class="stats-table">';
                $html .= '<thead><tr><th>Μέσος Όρος</th><th>Διάμεσος</th><th>Ελάχιστο</th><th>Μέγιστο</th></tr></thead>';
                $html .= '<tbody>';
                $html .= '<tr><td>' . $q['average'] . '</td><td>' . $q['median'] . '</td><td>' . $q['min'] . '</td><td>' . $q['max'] . '</td></tr>';
                $html .= '</tbody></table>';
            } elseif (!empty($q['text_responses'])) {
                $html .= '<table class="stats-table">';
                $html .= '<thead><tr><th>Κείμενο / Απάντηση</th></tr></thead>';
                $html .= '<tbody>';
                foreach (array_slice($q['text_responses'], 0, 5) as $txt) {
                    $html .= '<tr><td>' . htmlspecialchars($txt) . '</td></tr>';
                }
                $html .= '</tbody></table>';
            }

            $html .= '</div>';
        }

        $html .= '
    <div class="footer">
        <span>AppForm Enterprise © ' . date('Y') . ' - Σελίδα <span class="page-number"></span></span>
    </div>
</body>
</html>';

        if (class_exists('\Dompdf\Dompdf')) {
            $dompdf = new Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            return $dompdf->output();
        }

        return $html;
    }
}
