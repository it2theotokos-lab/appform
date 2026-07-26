<?php
// Router test script

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Router;

echo "--- Running Router Tests ---\n";

$router = new Router();

// Define test routes
$router->get('/login', function() { return 'login'; });
$router->get('/admin/forms/{id}/edit', function($params) { return "edit_form_" . $params['id']; });

// Test 1: Simple Route matching
$routes = $router->getRoutes();
$matched = false;
foreach ($routes as $route) {
    if ($route['path'] === '/login' && $route['method'] === 'GET') {
        $matched = true;
    }
}
assert($matched === true, "Test 1 Failed: Route matching /login failed.");
echo "Test 1 Passed: Simple Route matching.\n";

// Test 2: Parameter Extraction
$testPath = '/admin/forms/42/edit';
$parameterMatched = false;
foreach ($routes as $route) {
    if (preg_match($route['pattern'], $testPath, $matches)) {
        if (isset($matches['id']) && $matches['id'] == '42') {
            $parameterMatched = true;
        }
    }
}
assert($parameterMatched === true, "Test 2 Failed: Parameter extraction {id} failed.");
echo "Test 2 Passed: Parameter extraction.\n";

echo "All Router Tests Passed!\n";
return true;
