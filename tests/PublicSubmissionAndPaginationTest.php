<?php

echo "--- Running Public Submission and Pagination Tests ---\n";

$migration = file_get_contents(__DIR__ . '/../db/migrations/046_allow_guest_public_submissions.sql');
assert($migration !== false);
assert(str_contains($migration, 'MODIFY COLUMN user_id INT NULL'));
echo "Test 1 Passed: production migration permits guest submission ownership.\n";

$schema = file_get_contents(__DIR__ . '/../db/schema.sql');
assert($schema !== false);
assert(str_contains($schema, 'user_id INT NULL,'));
echo "Test 2 Passed: fresh installations permit guest submission ownership.\n";

$controller = file_get_contents(__DIR__ . '/../src/controllers/SubmissionController.php');
assert($controller !== false);
assert(str_contains($controller, 'LEFT JOIN users u ON s.user_id = u.id'));
assert(str_contains($controller, 'COALESCE(u.username, \'Δημόσια υποβολή\') AS username'));
assert(str_contains($controller, 'LIMIT {$pageSize} OFFSET {$offset}'));
assert(str_contains($controller, '$pageSize = 20;'));
echo "Test 3 Passed: guest submissions remain visible and database pagination is limited to 20.\n";

$view = file_get_contents(__DIR__ . '/../src/Views/admin/submissions/index.php');
assert($view !== false);
assert(str_contains($view, 'Προηγούμενη σελίδα'));
assert(str_contains($view, 'Επόμενη σελίδα'));
assert(str_contains($view, "unset(\$paginationQuery['page']);"));
echo "Test 4 Passed: pagination controls preserve active filters.\n";

echo "All Public Submission and Pagination Tests Passed!\n";
return true;
