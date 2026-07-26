<?php
namespace App\Services;

class CalculationService {
    /**
     * Evaluates a mathematical formula by replacing placeholders with numerical values.
     */
    public static function evaluate(string $formula, array $values, array $fieldsMap): float {
        // Resolve references to numerical values
        $formula = self::resolveReferences($formula, $values, $fieldsMap);

        // Normalize whitespace
        $formula = preg_replace('/\s+/', '', $formula);

        if ($formula === '') {
            return 0.0;
        }

        // Validate characters to prevent code injection
        if (!preg_match('/^[0-9\+\-\*\/\%\(\)\.\,a-zA-Z]+$/i', $formula)) {
            throw new \Exception("Μη έγκυροι χαρακτήρες στον τύπο υπολογισμού.");
        }

        try {
            return self::parseExpression($formula);
        } catch (\Throwable $e) {
            throw new \Exception("Σφάλμα σύνταξης τύπου υπολογισμού: " . $e->getMessage());
        }
    }

    /**
     * Replaces {field_key} placeholders with numerical values.
     */
    public static function resolveReferences(string $formula, array $values, array $fieldsMap): string {
        return preg_replace_callback('/\{([a-zA-Z0-9_]+)\}/', function($matches) use ($values, $fieldsMap) {
            $key = $matches[1];
            if (!isset($fieldsMap[$key])) {
                throw new \Exception("Άγνωστο πεδίο: " . $key);
            }

            $rawVal = $values[$key] ?? 0;
            $fieldDef = $fieldsMap[$key];

            // Resolve numerical values for checkboxes, radios, and dropdown choices
            if (in_array($fieldDef['type'], ['select', 'radio', 'checkbox'])) {
                // If it is a checkbox with no options, standard 1/0
                if ($fieldDef['type'] === 'checkbox' && empty($fieldDef['options'])) {
                    return (float)$rawVal;
                }
                
                // Scan options list for Numeric Calculation Value
                $options = $fieldDef['options'] ?? [];
                foreach ($options as $opt) {
                    if ((string)$opt['value'] === (string)$rawVal) {
                        if (isset($opt['calcValue']) && is_numeric($opt['calcValue'])) {
                            return (float)$opt['calcValue'];
                        }
                    }
                }
            }

            if (is_numeric($rawVal)) {
                return (string)(float)$rawVal;
            }

            return '0';
        }, $formula);
    }

    private static function parseExpression(string $expr): float {
        // Resolve helper functions: round, abs, min, max, sum, floor, ceil
        $expr = preg_replace_callback('/(round|abs|min|max|sum|floor|ceil)\(([^)]+)\)/i', function($m) {
            $func = strtolower($m[1]);
            $rawArgs = $m[2];
            // Split by comma but verify we only parse numeric arguments
            $parts = explode(',', $rawArgs);
            $args = [];
            foreach ($parts as $part) {
                $args[] = self::parseExpression(trim($part));
            }

            switch ($func) {
                case 'abs': return (string)abs($args[0]);
                case 'ceil': return (string)ceil($args[0]);
                case 'floor': return (string)floor($args[0]);
                case 'round': return (string)round($args[0], isset($args[1]) ? (int)$args[1] : 0);
                case 'min': return (string)min($args);
                case 'max': return (string)max($args);
                case 'sum': return (string)array_sum($args);
            }
            return '0';
        }, $expr);

        // Standard math parser using simple evaluation matching
        // Guard against division by zero
        if (str_contains($expr, '/0')) {
            throw new \Exception("Διαίρεση με το μηδέν.");
        }

        // We can safely use a clean mathematical expression evaluator
        // Only allow basic tokens to ensure no code execution can happen
        // Commas should be stripped or processed, but since arguments have been resolved, they won't appear unless it's math
        if (!preg_match('/^[0-9\+\-\*\/\%\(\)\.]+$/', $expr)) {
            throw new \Exception("Μη έγκυρη μαθηματική έκφραση: " . $expr);
        }

        // Custom evaluator to prevent eval
        return self::evalMath($expr);
    }

    private static function evalMath(string $expr): float {
        $expr = str_replace(['+-', '--'], ['-', '+'], $expr);
        
        // Match numbers, operators and parentheses
        preg_match_all('/[0-9\.]+|[\+\-\*\/\%\(\)]/', $expr, $tokens);
        $tokens = $tokens[0] ?? [];
        if (empty($tokens)) {
            throw new \Exception("Empty math expression tokens for: " . $expr);
        }
        
        $outputQueue = [];
        $operatorStack = [];
        
        $precedence = [
            '+' => 1, '-' => 1,
            '*' => 2, '/' => 2, '%' => 2
        ];
        
        foreach ($tokens as $token) {
            if (is_numeric($token)) {
                $outputQueue[] = (float)$token;
            } elseif ($token === '(') {
                $operatorStack[] = $token;
            } elseif ($token === ')') {
                while (!empty($operatorStack) && end($operatorStack) !== '(') {
                    $outputQueue[] = array_pop($operatorStack);
                }
                array_pop($operatorStack); // pop '('
            } else {
                while (!empty($operatorStack) && end($operatorStack) !== '(' && 
                       ($precedence[end($operatorStack)] ?? 0) >= $precedence[$token]) {
                    $outputQueue[] = array_pop($operatorStack);
                }
                $operatorStack[] = $token;
            }
        }
        
        while (!empty($operatorStack)) {
            $outputQueue[] = array_pop($operatorStack);
        }
        
        // Evaluate RPN
        $stack = [];
        foreach ($outputQueue as $token) {
            if (is_numeric($token)) {
                $stack[] = $token;
            } else {
                $b = array_pop($stack);
                $a = array_pop($stack);
                if ($a === null || $b === null) {
                    throw new \Exception("Μη έγκυρη σύνταξη.");
                }
                switch ($token) {
                    case '+': $stack[] = $a + $b; break;
                    case '-': $stack[] = $a - $b; break;
                    case '*': $stack[] = $a * $b; break;
                    case '/': 
                        if ($b == 0.0) throw new \Exception("Διαίρεση με το μηδέν.");
                        $stack[] = $a / $b; 
                        break;
                    case '%': $stack[] = fmod($a, $b); break;
                }
            }
        }
        
        return count($stack) === 1 ? $stack[0] : 0.0;
    }

    /**
     * Detects circular dependencies within form configurations.
     */
    public static function checkCircularDependencies(array $fields): ?string {
        $graph = [];
        foreach ($fields as $f) {
            if ($f['type'] === 'calculated' && !empty($f['formula'])) {
                preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $f['formula'], $matches);
                $graph[$f['key']] = $matches[1] ?? [];
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
