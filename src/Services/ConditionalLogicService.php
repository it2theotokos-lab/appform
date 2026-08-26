<?php
namespace App\Services;

class ConditionalLogicService {
    /**
     * Evaluates whether a field (or section) is visible based on conditional logic rules.
     */
    public static function isVisible(array $conditionalLogic, array $submittedData, array $fieldsMap): bool {
        if (empty($conditionalLogic) || empty($conditionalLogic['enabled'])) {
            return true;
        }

        $action = $conditionalLogic['action'] ?? 'show'; // 'show' or 'hide'
        $matchMode = $conditionalLogic['match'] ?? 'all'; // 'all' or 'any'
        $rules = $conditionalLogic['rules'] ?? [];

        if (empty($rules)) {
            return true;
        }

        $results = [];
        foreach ($rules as $rule) {
            $results[] = self::evaluateRule($rule, $submittedData, $fieldsMap);
        }

        $matched = false;
        if ($matchMode === 'all') {
            $matched = !in_array(false, $results, true);
        } else {
            $matched = in_array(true, $results, true);
        }

        return ($action === 'show') ? $matched : !$matched;
    }

    /**
     * Evaluates a single visibility rule.
     */
    private static function evaluateRule(array $rule, array $submittedData, array $fieldsMap): bool {
        $sourceKey = $rule['field'] ?? '';
        $operator = $rule['operator'] ?? 'equals';
        $compVal = $rule['value'] ?? '';

        if (!$sourceKey || !isset($fieldsMap[$sourceKey])) {
            return false;
        }

        $fieldDef = $fieldsMap[$sourceKey];
        $rawVal = $submittedData[$sourceKey] ?? null;

        // Normalize checked choices or simple values
        $isCheckbox = ($fieldDef['type'] === 'checkbox');
        
        switch ($operator) {
            case 'is_empty':
                return ($rawVal === null || (is_string($rawVal) && trim($rawVal) === '') || (is_array($rawVal) && empty($rawVal)));
            case 'is_not_empty':
                return ($rawVal !== null && (!is_string($rawVal) || trim($rawVal) !== '') && (!is_array($rawVal) || !empty($rawVal)));
            case 'is_checked':
                return $isCheckbox && ($rawVal == 1 || $rawVal === '1' || $rawVal === true || !empty($rawVal));
            case 'is_not_checked':
                return $isCheckbox && ($rawVal === null || $rawVal == 0 || $rawVal === '0' || $rawVal === false || empty($rawVal));
        }

        // Standard comparison operations
        $valString = is_array($rawVal) ? implode(',', $rawVal) : (string)$rawVal;
        $compString = (string)$compVal;

        // Numeric checks
        $isNumeric = is_numeric($valString) && is_numeric($compString);
        if ($isNumeric) {
            $numVal = (float)$valString;
            $numComp = (float)$compString;
            switch ($operator) {
                case 'equals': return $numVal === $numComp;
                case 'not_equals': return $numVal !== $numComp;
                case 'greater_than': return $numVal > $numComp;
                case 'greater_than_or_equal': return $numVal >= $numComp;
                case 'less_than': return $numVal < $numComp;
                case 'less_than_or_equal': return $numVal <= $numComp;
            }
        }

        // Date check fallback helper
        $isDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $valString) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $compString);
        if ($isDate) {
            $tVal = strtotime($valString);
            $tComp = strtotime($compString);
            switch ($operator) {
                case 'equals': return $tVal === $tComp;
                case 'not_equals': return $tVal !== $tComp;
                case 'greater_than': return $tVal > $tComp;
                case 'greater_than_or_equal': return $tVal >= $tComp;
                case 'less_than': return $tVal < $tComp;
                case 'less_than_or_equal': return $tVal <= $tComp;
            }
        }

        // String operations
        switch ($operator) {
            case 'equals': return strcasecmp($valString, $compString) === 0;
            case 'not_equals': return strcasecmp($valString, $compString) !== 0;
            case 'contains': return str_contains(strtolower($valString), strtolower($compString));
            case 'not_contains': return !str_contains(strtolower($valString), strtolower($compString));
            case 'starts_with': return str_starts_with(strtolower($valString), strtolower($compString));
            case 'ends_with': return str_ends_with(strtolower($valString), strtolower($compString));
        }

        return false;
    }

    /**
     * Detects direct or indirect circular dependencies in field logic.
     */
    public static function checkCircularDependencies(array $fields): ?string {
        $graph = [];
        foreach ($fields as $f) {
            $deps = [];
            // Parse Calculated Field formula dependencies ({key} or bare field_xxx)
            if ($f['type'] === 'calculated' && !empty($f['formula'])) {
                preg_match_all('/(?:\{([a-zA-Z0-9_]+)\}|(field_[a-zA-Z0-9_]+))/', $f['formula'], $matches);
                foreach ($matches[0] as $idx => $m) {
                    $d = !empty($matches[1][$idx]) ? $matches[1][$idx] : $matches[2][$idx];
                    $deps[$d] = true;
                }
            }
            // Parse Conditional Logic dependencies
            if (!empty($f['conditional_logic']) && !empty($f['conditional_logic']['enabled'])) {
                $rules = $f['conditional_logic']['rules'] ?? [];
                foreach ($rules as $r) {
                    if (!empty($r['field'])) {
                        $deps[$r['field']] = true;
                    }
                }
            }
            if (!empty($deps)) {
                $graph[$f['key']] = array_keys($deps);
            }
        }

        $visited = [];
        $recStack = [];

        $dfs = function($node) use (&$dfs, &$graph, &$visited, &$recStack) {
            $visited[$node] = true;
            $recStack[$node] = true;

            foreach (($graph[$node] ?? []) as $neighbor) {
                if (!isset($visited[$neighbor])) {
                    if ($dfs($neighbor)) {
                        return true;
                    }
                } elseif (isset($recStack[$neighbor]) && $recStack[$neighbor]) {
                    return true;
                }
            }

            $recStack[$node] = false;
            return false;
        };

        foreach (array_keys($graph) as $node) {
            if (!isset($visited[$node])) {
                if ($dfs($node)) {
                    return "Εντοπίστηκε κυκλική εξάρτηση (Circular Dependency) στο πεδίο: " . htmlspecialchars($node);
                }
            }
        }

        return null;
    }
}
