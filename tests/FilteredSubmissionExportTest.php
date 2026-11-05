<?php

$source = file_get_contents(__DIR__ . '/../src/controllers/ExportController.php');

function assertExportCondition(bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
    echo "PASS: {$message}\n";
}

assertExportCondition(
    str_contains($source, 'LEFT JOIN users u ON s.user_id = u.id'),
    'filtered export retains guest submissions with a left user join'
);
assertExportCondition(
    str_contains($source, "COALESCE(u.username, 'Δημόσια υποβολή') AS username"),
    'guest submissions receive a clear export label'
);
assertExportCondition(
    str_contains($source, 'ORDER BY s.created_at DESC, s.id DESC'),
    'export uses the same deterministic newest-first ordering as the submissions screen'
);

