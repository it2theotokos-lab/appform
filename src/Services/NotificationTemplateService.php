<?php
namespace App\Services;

class NotificationTemplateService {
    /**
     * Resolves smart tags in subject and body templates.
     */
    public static function resolve(string $template, array $submission, array $form, array $schema, array $fieldsMap): string {
        $answers = json_decode($submission['data_json'] ?? '{}', true) ?: [];

        // Replace basic submission and form variables
        $vars = [
            '{form_id}' => $form['id'],
            '{form_name}' => $form['title'],
            '{submission_id}' => $submission['id'],
            '{submitted_at}' => $submission['submitted_at'] ?? date('Y-m-d H:i:s'),
            '{submission_status}' => $submission['status'] ?? 'submitted',
            '{site_name}' => 'AppForm Enterprise Portal',
            '{site_url}' => 'http://127.0.0.1:8080'
        ];

        $template = strtr($template, $vars);

        // Replace dynamic field keys {field:field_key} and {field_label:field_key}
        $template = preg_replace_callback('/\{field:([a-zA-Z0-9_]+)\}/', function($matches) use ($answers, $fieldsMap) {
            $key = $matches[1];
            $val = $answers[$key] ?? '';
            return self::escapeHtml(self::formatValue($val, $fieldsMap[$key] ?? null));
        }, $template);

        $template = preg_replace_callback('/\{field_label:([a-zA-Z0-9_]+)\}/', function($matches) use ($fieldsMap) {
            $key = $matches[1];
            return self::escapeHtml($fieldsMap[$key]['label'] ?? $key);
        }, $template);

        // Replace all fields dynamic tag
        if (str_contains($template, '{all_fields}')) {
            $allFieldsHtml = self::renderAllFields($answers, $schema, $fieldsMap);
            $template = str_replace('{all_fields}', $allFieldsHtml, $template);
        }

        return $template;
    }

    /**
     * Sanitizes values to prevent HTML injection in notifications.
     */
    public static function escapeHtml(string $val): string {
        // Strip unsafe HTML tags
        $clean = strip_tags($val, '<b><i><strong><em><u><p><br><ul><li><ol>');
        // Escape standard HTML special chars
        return htmlspecialchars($clean, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function formatValue($val, ?array $fieldDef): string {
        if ($val === null || $val === '') return '';

        // Exclude passwords
        if ($fieldDef && isset($fieldDef['type']) && $fieldDef['type'] === 'password') {
            return '[PROTECTED PASSWORD]';
        }

        // Format address array values
        if ($fieldDef && isset($fieldDef['type']) && $fieldDef['type'] === 'address') {
            if (is_array($val)) {
                return ($val['line1'] ?? '') . ', ' . ($val['line2'] ?? '') . ', ' . ($val['city'] ?? '') . ', ' . ($val['postal_code'] ?? '');
            }
        }

        // Format repeater JSON array values
        if ($fieldDef && isset($fieldDef['type']) && $fieldDef['type'] === 'repeater') {
            if (is_array($val)) {
                $lines = [];
                foreach ($val as $idx => $row) {
                    $lines[] = 'Row #' . ($idx + 1) . ': ' . json_encode($row, JSON_UNESCAPED_UNICODE);
                }
                return implode(' | ', $lines);
            }
        }

        // Format Likert JSON values
        if ($fieldDef && isset($fieldDef['type']) && $fieldDef['type'] === 'likert') {
            if (is_array($val)) {
                $lines = [];
                foreach ($val as $rowKey => $colVal) {
                    $lines[] = $rowKey . ': ' . $colVal;
                }
                return implode(', ', $lines);
            }
        }

        if (is_array($val)) return implode(', ', $val);

        if ($fieldDef && isset($fieldDef['type'])) {
            if ($fieldDef['type'] === 'checkbox' || $fieldDef['type'] === 'consent') {
                return $val ? 'Ναι' : 'Όχι';
            }
            if ($fieldDef['type'] === 'rating') {
                $max = $fieldDef['ratingMax'] ?? 5;
                return $val . ' / ' . $max;
            }
            if ($fieldDef['type'] === 'nps') {
                return $val . ' / 10';
            }
        }

        return (string)$val;
    }

    private static function renderAllFields(array $answers, array $schema, array $fieldsMap): string {
        $html = '<table style="width:100%; border-collapse:collapse; font-family:sans-serif; font-size:14px; margin-top:10px;">';
        $html .= '<thead><tr style="background:#f3f4f6;"><th style="border:1px solid #e5e7eb; padding:8px; text-align:left;">Πεδίο</th><th style="border:1px solid #e5e7eb; padding:8px; text-align:left;">Τιμή</th></tr></thead>';
        $html .= '<tbody>';

        foreach ($schema['sections'] ?? [] as $sec) {
            foreach ($sec['fields'] ?? [] as $f) {
                if (isset($f['type']) && in_array($f['type'], ['heading', 'divider'])) continue;
                $val = $answers[$f['key']] ?? '';
                $formatted = self::formatValue($val, $f);
                $html .= '<tr>';
                $html .= '<td style="border:1px solid #e5e7eb; padding:8px; font-weight:bold;">' . self::escapeHtml($f['label']) . '</td>';
                $html .= '<td style="border:1px solid #e5e7eb; padding:8px;">' . self::escapeHtml($formatted) . '</td>';
                $html .= '</tr>';
            }
        }

        $html .= '</tbody></table>';
        return $html;
    }
}
