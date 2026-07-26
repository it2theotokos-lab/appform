<?php
// Quick test of the lookbehind regex used in Test 20
$engineScript = file_get_contents(dirname(__DIR__) . '/src/Services/Update/UpdateEngineService.php');

// Old regex
$r1 = preg_match('/(?<!->|pdo|db|conn|pdo_)\bexec\s*\(/i', $engineScript);
echo "Old regex result: $r1\n";
echo "preg_last_error: " . preg_last_error() . "\n";

// Find all exec( matches and their context
preg_match_all('/(.{0,30})exec\s*\((.{0,30})/i', $engineScript, $matches, PREG_OFFSET_CAPTURE);
echo "\nAll exec( occurrences:\n";
foreach ($matches[0] as $i => $m) {
    echo "  [{$m[1]}] {$m[0]}\n";
}
return true;
