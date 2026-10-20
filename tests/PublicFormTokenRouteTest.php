<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Router;

echo "--- Running Public Form Token Route Tests ---\n";

$router = new Router();
$router->get('/f/{token}', static function (): void {});
$tokenRoute = $router->getRoutes()[0];

// A valid public-token URL captures `token` (not `slug`) by design.
assert(preg_match($tokenRoute['pattern'], '/f/abc123', $validMatches) === 1);
assert(($validMatches['token'] ?? null) === 'abc123');
echo "Test 1 Passed: valid public token is captured as token.\n";

// A missing token and an extra path segment must not match the public-token route.
assert(preg_match($tokenRoute['pattern'], '/f/', $missingMatches) === 0);
assert(preg_match($tokenRoute['pattern'], '/f/invalid/token', $invalidMatches) === 0);
echo "Test 2 Passed: missing and malformed public-token URLs do not match.\n";

$controller = file_get_contents(__DIR__ . '/../src/controllers/FormController.php');
assert($controller !== false);

// The token resolver must translate the token lookup result into the renderer's
// expected `slug` parameter, while ordinary /forms/{slug} routes remain unchanged.
assert(str_contains($controller, "'slug' => (string) \$form['slug']"));
assert(str_contains($controller, "\$slug = \$params['slug'] ?? null;"));
assert(str_contains($controller, 'if (!is_string($slug) || $slug === \'\')'));
assert(str_contains($controller, 'if (!$form || empty($form[\'is_public\']))'));
echo "Test 3 Passed: token resolution forwards a validated slug safely.\n";

$routes = file_get_contents(__DIR__ . '/../public/index.php');
assert($routes !== false);
assert(str_contains($routes, "get('/forms/{slug}', [\\App\\Controllers\\FormController::class, 'showPublicForm']"));
assert(str_contains($routes, "get('/f/{token}', [\\App\\Controllers\\FormController::class, 'showPublicFormByToken']"));
assert(strpos($routes, "/f/submitted/success") < strpos($routes, "/f/{token}"));
echo "Test 4 Passed: slug and token routes coexist and success route keeps precedence.\n";

// /f/{token} deliberately has no auth middleware. The authenticated /forms/{slug}
// route remains in place; its auth and permission middleware explicitly bypass public forms.
assert(str_contains($routes, "get('/f/{token}', [\\App\\Controllers\\FormController::class, 'showPublicFormByToken']);"));
foreach (['AuthMiddleware.php', 'PermissionMiddleware.php'] as $middlewareFile) {
    $middleware = file_get_contents(__DIR__ . '/../src/middleware/' . $middlewareFile);
    assert($middleware !== false);
    assert(str_contains($middleware, "SELECT is_public FROM forms WHERE slug = ?"));
    assert(str_contains($middleware, "return; // Bypass"));
}
echo "Test 5 Passed: public access bypass and authenticated slug route behavior are retained.\n";

echo "All Public Form Token Route Tests Passed!\n";
return true;
