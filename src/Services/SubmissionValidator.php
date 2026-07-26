<?php
namespace App\Services;

use App\Models\Repository;

class SubmissionValidator {
    protected $errors = [];
    protected $validated = [];
    protected $fieldsMap = [];

    public function validate(array $submittedData, array $schema, bool $isFinal = true): bool {
        $this->errors = [];
        $this->validated = [];

        // Build list of valid keys from schema
        $validKeys = [];
        $fieldsMap = [];

        if (isset($schema['sections'])) {
            foreach ($schema['sections'] as $sec) {
                if (isset($sec['fields'])) {
                    foreach ($sec['fields'] as $f) {
                        $validKeys[] = $f['key'];
                        $fieldsMap[$f['key']] = $f;
                    }
                }
            }
        }
        $this->fieldsMap = $fieldsMap;

        // 1. Reject unknown keys
        foreach ($submittedData as $key => $val) {
            // Ignore system/CSRF inputs
            if (in_array($key, ['_token', '_method', 'form_version_id', 'status', 'submission_uuid'])) {
                continue;
            }
            if (!in_array($key, $validKeys)) {
                $this->errors[$key][] = "Μη αποδεκτό πεδίο.";
            }
        }

        // 2. Perform validations per field type
        foreach ($fieldsMap as $key => $field) {
            $value = $submittedData[$key] ?? null;

            // Skip divider or headings type
            if (in_array($field['type'], ['heading', 'divider'])) {
                continue;
            }

            // Required validation on final submit
            if ($isFinal && ($field['required'] ?? false)) {
                if ($value === null || (is_string($value) && trim($value) === '')) {
                    // Check conditional visibility - if conditional vis check hides it, it's not required!
                    if (!$this->isFieldHidden($field, $submittedData)) {
                        $this->errors[$key][] = "Το πεδίο " . htmlspecialchars($field['label']) . " είναι υποχρεωτικό.";
                    }
                }
            }

            // Value formatting validations if present
            if ($value !== null && (!is_string($value) || trim($value) !== '')) {
                $this->validated[$key] = $value;
                $this->validateFieldConstraints($key, $value, $field);
            }
        }

        return empty($this->errors);
    }

    public function errors(): array {
        return $this->errors;
    }

    public function validated(): array {
        return $this->validated;
    }

    protected function validateFieldConstraints(string $key, $value, array $field) {
        // Types checking
        if ($field['type'] === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$key][] = "Μη έγκυρη μορφή email.";
        }

        if ($field['type'] === 'number' && !is_numeric($value)) {
            $this->errors[$key][] = "Η τιμή πρέπει να είναι αριθμητική.";
        }

        // Phone validation
        if ($field['type'] === 'phone') {
            $clean = preg_replace('/[^\d+]/', '', $value);
            if (empty($clean) || strlen($clean) < 7 || strlen($clean) > 15) {
                $this->errors[$key][] = "Μη έγκυρη μορφή τηλεφώνου.";
            }
        }

        // URL validation
        if ($field['type'] === 'url') {
            $protocols = $field['urlProtocols'] ?? ['http', 'https'];
            $valid = false;
            foreach ($protocols as $proto) {
                if (str_starts_with(strtolower($value), $proto . '://')) {
                    $valid = true;
                    break;
                }
            }
            // Check for malicious protocols
            if (preg_match('/^(javascript|data|file|vbscript):/i', $value)) {
                $valid = false;
            }
            if (!$valid && !filter_var($value, FILTER_VALIDATE_URL)) {
                $this->errors[$key][] = "Μη έγκυρη μορφή URL.";
            }
        }

        // Time format HH:MM:SS validation
        if ($field['type'] === 'time') {
            if (!preg_match('/^(?:2[0-3]|[01][0-9]):[0-5][0-9](?::[0-5][0-9])?$/', $value)) {
                $this->errors[$key][] = "Μη έγκυρη μορφή ώρας (αναμένεται HH:MM).";
            }
        }

        // Password policy validation
        if ($field['type'] === 'password') {
            if (strlen($value) < 6) {
                $this->errors[$key][] = "Ο κωδικός πρόσβασης είναι πολύ μικρός (ελάχιστο 6 χαρακτήρες).";
            }
        }

        // Rating value range validation
        if ($field['type'] === 'rating') {
            $max = $field['ratingMax'] ?? 5;
            $valInt = (int)$value;
            if ($valInt < 0 || $valInt > $max) {
                $this->errors[$key][] = "Μη αποδεκτή τιμή αξιολόγησης.";
            }
        }

        // Range Slider step validation
        if ($field['type'] === 'range') {
            $min = $field['rangeMin'] ?? 0;
            $max = $field['rangeMax'] ?? 100;
            $valNum = (float)$value;
            if ($valNum < $min || $valNum > $max) {
                $this->errors[$key][] = "Η τιμή βρίσκεται εκτός των ορίων.";
            }
        }

        // Address Validation (composite array checking)
        if ($field['type'] === 'address') {
            if (is_array($value)) {
                if (empty($value['line1']) || empty($value['city']) || empty($value['postal_code'])) {
                    $this->errors[$key][] = "Η διεύθυνση πρέπει να περιέχει Διεύθυνση 1, Πόλη και Ταχυδρομικό Κώδικα.";
                }
            } else {
                $this->errors[$key][] = "Μη αποδεκτή μορφή διεύθυνσης.";
            }
        }

        // Currency checks
        if ($field['type'] === 'currency') {
            if (!is_numeric($value)) {
                $this->errors[$key][] = "Το ποσό πρέπει να είναι αριθμητικό.";
            }
        }

        // NPS checks
        if ($field['type'] === 'nps') {
            $valInt = (int)$value;
            if ($valInt < 0 || $valInt > 10) {
                $this->errors[$key][] = "Η βαθμολογία NPS πρέπει να είναι μεταξύ 0 και 10.";
            }
        }

        // Repeater checks
        if ($field['type'] === 'repeater') {
            if (!is_array($value)) {
                $this->errors[$key][] = "Μη αποδεκτή δομή repeater.";
            }
        }

        // Options check for select / radio / checkbox
        if (in_array($field['type'], ['select', 'radio', 'checkbox'])) {
            $validOptionsValues = [];
            $hasOptions = false;
            if (isset($field['dataSource']) && $field['dataSource'] === 'repository' && !empty($field['repositoryId'])) {
                $repo = Repository::findById((int)$field['repositoryId']);
                if ($repo) {
                    $items = json_decode($repo['data_json'], true);
                    $validOptionsValues = array_column($items, 'value');
                }
                $hasOptions = true;
            } else {
                $options = $field['options'] ?? [];
                if (!empty($options)) {
                    $validOptionsValues = array_column($options, 'value');
                    $hasOptions = true;
                }
            }

            if ($hasOptions) {
                if ($field['type'] === 'checkbox') {
                    // Checkbox is expected to be an array of selected option values
                    $vals = is_array($value) ? $value : json_decode($value, true);
                    if ($vals === null) {
                        $vals = [$value];
                    }
                    foreach ($vals as $v) {
                        if (!in_array((string)$v, array_map('strval', $validOptionsValues))) {
                            $this->errors[$key][] = "Μη αποδεκτή επιλογή.";
                            break;
                        }
                    }
                } else {
                    if (!in_array($value, $validOptionsValues)) {
                        $this->errors[$key][] = "Μη αποδεκτή επιλογή.";
                    }
                }
            }
        }
    }

    protected function isFieldHidden(array $field, array $data): bool {
        if (isset($field['conditional_logic']) && !empty($field['conditional_logic']['enabled'])) {
            $fieldsMap = $this->fieldsMap ?? [];
            return !\App\Services\ConditionalLogicService::isVisible($field['conditional_logic'], $data, $fieldsMap);
        }
        return false;
    }
}
