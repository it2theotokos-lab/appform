<?php
namespace App\Core;

class Validator {
    protected $data;
    protected $errors = [];
    protected $validated = [];

    public function __construct(array $data) {
        $this->data = $data;
    }

    public function validate(array $rules) {
        foreach ($rules as $field => $fieldRules) {
            $value = $this->data[$field] ?? null;
            $this->validated[$field] = $value;

            foreach ($fieldRules as $rule) {
                $ruleName = $rule;
                $ruleValue = null;

                if (str_contains($rule, ':')) {
                    $parts = explode(':', $rule);
                    $ruleName = $parts[0];
                    $ruleValue = $parts[1];
                }

                $method = 'validate' . ucfirst($ruleName);
                if (method_exists($this, $method)) {
                    $this->$method($field, $value, $ruleValue);
                }
            }
        }
    }

    public function passed(): bool {
        return empty($this->errors);
    }

    public function errors(): array {
        return $this->errors;
    }

    public function validated(): array {
        return $this->validated;
    }

    // Rules implementations
    protected function validateRequired($field, $value) {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            $this->errors[$field][] = "Το πεδίο είναι υποχρεωτικό.";
        }
    }

    protected function validateEmail($field, $value) {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = "Η διεύθυνση email δεν είναι έγκυρη.";
        }
    }

    protected function validateMin($field, $value, $min) {
        if (!empty($value) && strlen($value) < (int)$min) {
            $this->errors[$field][] = "Το πεδίο πρέπει να έχει τουλάχιστον $min χαρακτήρες.";
        }
    }

    protected function validateMax($field, $value, $max) {
        if (!empty($value) && strlen($value) > (int)$max) {
            $this->errors[$field][] = "Το πεδίο δεν μπορεί να υπερβαίνει τους $max χαρακτήρες.";
        }
    }

    protected function validateJson($field, $value) {
        if (!empty($value)) {
            json_decode($value);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->errors[$field][] = "Το περιεχόμενο πρέπει να είναι έγκυρη μορφή JSON.";
            }
        }
    }

    protected function validateNumeric($field, $value) {
        if (!empty($value) && !is_numeric($value)) {
            $this->errors[$field][] = "Το πεδίο πρέπει να είναι αριθμητικό.";
        }
    }
}
