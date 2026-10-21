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

        // Notifications must show human-readable selections, never option IDs such as "1".
        if ($fieldDef && isset($fieldDef['type'])) {
            $type = $fieldDef['type'];
            if (in_array($type, ['select', 'radio', 'checkbox'], true)) {
                $optionValue = self::formatOptionValue($val, $fieldDef);
                if ($optionValue !== null) {
                    return $optionValue;
                }
            }
            if (in_array($type, ['date', 'datetime', 'date_time'], true)) {
                return self::formatDateValue($val, $type);
            }
            // Single consent checkboxes are scalar values. Checkbox groups,
            // including repository-backed beneficiary lists, are arrays and
            // must continue to the generic array formatter below.
            if (in_array($type, ['checkbox', 'consent'], true) && !is_array($val)) {
                if (!self::isSelected($val)) {
                    return '';
                }
                return trim((string)($fieldDef['label'] ?? $fieldDef['placeholder'] ?? 'Ναι'));
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

    /** Returns option labels instead of their internal stored values. */
    private static function formatOptionValue($val, array $fieldDef): ?string {
        $options = $fieldDef['options'] ?? [];
        if (!is_array($options) || $options === []) {
            return null;
        }

        $values = is_array($val) ? $val : [$val];
        $labels = [];
        foreach ($values as $value) {
            foreach ($options as $option) {
                if (!is_array($option) || !array_key_exists('value', $option)) {
                    continue;
                }
                if ((string)$option['value'] === (string)$value) {
                    $label = trim((string)($option['label'] ?? $option['value']));
                    if ($label !== '') {
                        $labels[] = $label;
                    }
                    continue 2;
                }
            }
        }
        // Repository-backed checkbox groups keep their options outside the
        // schema.  Their submitted values are already displayable names, so
        // falling back to the generic array formatter preserves old and new
        // beneficiary lists instead of rendering an empty value.
        return $labels === [] ? null : implode(', ', array_values(array_unique($labels)));
    }

    private static function formatDateValue($val, string $type): string {
        if (!is_scalar($val) || trim((string)$val) === '') {
            return '';
        }
        try {
            $date = new \DateTimeImmutable((string)$val);
            return in_array($type, ['datetime', 'date_time'], true)
                ? $date->format('d/m/Y H:i')
                : $date->format('d/m/Y');
        } catch (\Exception $e) {
            return (string)$val;
        }
    }

    private static function isSelected($val): bool {
        if (is_bool($val)) {
            return $val;
        }
        return in_array(strtolower(trim((string)$val)), ['1', 'true', 'yes', 'on'], true);
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
