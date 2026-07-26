<?php
namespace App\Services;

class PdfExportService {
    public static function generateSubmissionHtml(array $submission, array $schema, array $answers): string {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Υποβολή #' . $submission['id'] . '</title>
    <style>
        body { font-family: "DejaVu Sans", sans-serif; color: #333; margin: 20px; }
        h2 { border-bottom: 2px solid #5a6268; padding-bottom: 5px; color: #222; }
        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .meta-table td { padding: 8px; border: 1px solid #ddd; }
        .meta-table td.label { font-weight: bold; background-color: #f8f9fa; width: 30%; }
        .section-title { font-size: 16px; font-weight: bold; margin-top: 20px; color: #007bff; border-bottom: 1px dashed #ddd; padding-bottom: 3px; }
        .field-container { margin-bottom: 15px; padding-left: 10px; }
        .field-label { font-weight: bold; color: #555; font-size: 12px; }
        .field-value { margin-top: 3px; font-size: 14px; }
    </style>
</head>
<body>
    <h2>Στοιχεία Υποβολής #' . $submission['id'] . '</h2>
    
    <table class="meta-table">
        <tr>
            <td class="label">Φόρμα</td>
            <td>' . htmlspecialchars($submission['form_title']) . '</td>
        </tr>
        <tr>
            <td class="label">Κατάσταση</td>
            <td><strong>' . strtoupper($submission['status']) . '</strong></td>
        </tr>
        <tr>
            <td class="label">Υποβλήθηκε από</td>
            <td>' . htmlspecialchars($submission['submitter_name']) . '</td>
        </tr>
        <tr>
            <td class="label">Ημερομηνία</td>
            <td>' . ($submission['submitted_at'] ? date('d/m/Y H:i', strtotime($submission['submitted_at'])) : 'Προσχέδιο') . '</td>
        </tr>
    </table>

    <h3>Απαντήσεις Φόρμας</h3>';

        foreach ($schema['sections'] as $sec) {
            $html .= '<div class="section-title">' . htmlspecialchars($sec['title']) . '</div>';
            if (isset($sec['fields'])) {
                foreach ($sec['fields'] as $f) {
                    if (in_array($f['type'], ['heading', 'divider'])) continue;
                    $ans = $answers[$f['key']] ?? '—';
                    $html .= '<div class="field-container">
                        <div class="field-label">' . htmlspecialchars($f['label']) . '</div>
                        <div class="field-value">' . htmlspecialchars(is_array($ans) ? implode(', ', $ans) : $ans) . '</div>
                    </div>';
                }
            }
        }

        $html .= '
</body>
</html>';

        return $html;
    }

    public static function export(array $submission, array $schema, array $answers): string {
        $html = self::generateSubmissionHtml($submission, $schema, $answers);

        if (class_exists('\Dompdf\Dompdf')) {
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            return $dompdf->output();
        }

        // Fallback printable HTML representation
        return $html;
    }
}
