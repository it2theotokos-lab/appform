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
            return self::escapeHtml(self::formatSubmittedValue($val, $fieldsMap[$key] ?? null));
        }, $template);

        $template = preg_replace_callback('/\{field_label:([a-zA-Z0-9_]+)\}/', function($matches) use ($fieldsMap) {
            $key = $matches[1];
            return self::escapeHtml($fieldsMap[$key]['label'] ?? $key);
        }, $template);

        // Replace all fields dynamic tag
        if (str_contains($template, '{all_fields}')) {
            $allFieldsHtml = self::renderSubmittedFields($answers, $schema, $fieldsMap);
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

    public static function formatSubmittedValue($val, ?array $fieldDef = null): string {
        if ($val === null || $val === '') return '';

        if (is_string($val)) {
            $trimmed = trim($val);
            if (($trimmed[0] ?? '') === '[' || ($trimmed[0] ?? '') === '{') {
                $decoded = json_decode($trimmed, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $val = $decoded;
                }
            }
        }

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

        if (is_array($val)) {
            return implode("\n", self::flattenDisplayValues($val));
        }

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

    private static function flattenDisplayValues(array $values): array {
        $result = [];
        foreach ($values as $value) {
            if (is_array($value)) {
                if (array_key_exists('value', $value) && self::hasMeaningfulValue($value['value'])) {
                    $result[] = (string)$value['value'];
                } elseif (array_key_exists('label', $value) && self::hasMeaningfulValue($value['label'])) {
                    $result[] = (string)$value['label'];
                } else {
                    $result = array_merge($result, self::flattenDisplayValues($value));
                }
            } elseif (self::hasMeaningfulValue($value)) {
                $result[] = (string)$value;
            }
        }
        return $result;
    }

    public static function renderSubmittedFields(array $answers, array $schema, array $fieldsMap): string {
        $html = '<table style="width:100%; border-collapse:collapse; font-family:sans-serif; font-size:14px; margin-top:10px;">';
        $html .= '<thead><tr style="background:#f3f4f6;"><th style="border:1px solid #e5e7eb; padding:8px; text-align:left;">Πεδίο</th><th style="border:1px solid #e5e7eb; padding:8px; text-align:left;">Τιμή</th></tr></thead>';
        $html .= '<tbody>';

        foreach ($schema['sections'] ?? [] as $sec) {
            foreach ($sec['fields'] ?? [] as $f) {
                if (isset($f['type']) && in_array($f['type'], ['heading', 'divider', 'page_break', 'html'])) continue;

                $key = $f['key'] ?? '';
                if ($key === '' || !array_key_exists($key, $answers)) continue;

                $val = $answers[$key];
                if (!self::hasMeaningfulValue($val)) continue;

                $formatted = self::formatSubmittedValue($val, $f);
                $html .= '<tr>';
                $html .= '<td style="border:1px solid #e5e7eb; padding:8px; font-weight:bold;">' . self::escapeHtml($f['label']) . '</td>';
                $html .= '<td style="border:1px solid #e5e7eb; padding:8px;">' . nl2br(self::escapeHtml($formatted), false) . '</td>';
                $html .= '</tr>';
            }
        }

        $html .= '</tbody></table>';
        return $html;
    }

    /**
     * Returns true when a submitted answer contains information worth showing.
     * Numeric zero and boolean false are valid answers and must not be discarded.
     */
    public static function hasMeaningfulValue($value): bool {
        if ($value === null) return false;

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') return false;

            // Checkbox/repeater values may be stored as JSON strings.
            if (($trimmed[0] ?? '') === '[' || ($trimmed[0] ?? '') === '{') {
                $decoded = json_decode($trimmed, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return self::hasMeaningfulValue($decoded);
                }
            }

            return true;
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                if (self::hasMeaningfulValue($item)) return true;
            }
            return false;
        }

        return true;
    }
}
