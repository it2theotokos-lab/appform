<?php
namespace App\Core;

class Router {
    protected $routes = [];
    protected $namedRoutes = [];

    public function add(string $method, string $path, $handler, array $middlewares = [], string $name = '') {
        // Convert route parameters from {id} or {slug} to regular expressions
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[a-zA-Z0-9_\-]+)', $path);
        $pattern = '#^' . $pattern . '$#';

        $route = [
            'method' => strtoupper($method),
            'path' => $path,
            'pattern' => $pattern,
            'handler' => $handler,
            'middlewares' => $middlewares
        ];

        $this->routes[] = $route;

        if ($name) {
            $this->namedRoutes[$name] = $path;
        }
    }

    public function get(string $path, $handler, array $middlewares = [], string $name = '') {
        $this->add('GET', $path, $handler, $middlewares, $name);
    }

    public function post(string $path, $handler, array $middlewares = [], string $name = '') {
        $this->add('POST', $path, $handler, $middlewares, $name);
    }

    public function put(string $path, $handler, array $middlewares = [], string $name = '') {
        $this->add('PUT', $path, $handler, $middlewares, $name);
    }

    public function delete(string $path, $handler, array $middlewares = [], string $name = '') {
        $this->add('DELETE', $path, $handler, $middlewares, $name);
    }

    public function getRoutes(): array {
        return $this->routes;
    }

    public function dispatch(string $path, string $method) {
        $method = strtoupper($method);

        // Process POST spoofing if _method is provided
        if ($method === 'POST' && isset($_POST['_method'])) {
            $spoofedMethod = strtoupper($_POST['_method']);
            if (in_array($spoofedMethod, ['PUT', 'PATCH', 'DELETE'])) {
                $method = $spoofedMethod;
            }
        }

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['pattern'], $path, $matches)) {
                // Keep only named parameters
                $params = array_filter($matches, function($key) {
                    return is_string($key);
                }, ARRAY_FILTER_USE_KEY);

                // Prepend Maintenance check globally
                $resolvedMiddlewares = array_merge(['maintenance'], $route['middlewares']);
                
                // Run middlewares
                $this->runMiddlewares($resolvedMiddlewares);

                // Execute handler
                $handler = $route['handler'];
                if (is_array($handler)) {
                    $controllerName = $handler[0];
                    $actionName = $handler[1];

                    $controller = new $controllerName();
                    $controller->$actionName($params);
                } else if (is_callable($handler)) {
                    call_user_func_array($handler, [$params]);
                }
                return;
            }
        }

        // Catch-all 404 error
        http_response_code(404);
        View::render('errors/404');
        exit;
    }

    protected function runMiddlewares(array $middlewares) {
        foreach ($middlewares as $mwDef) {
            $parts = explode(':', $mwDef);
            $name = $parts[0];
            $args = isset($parts[1]) ? explode(',', $parts[1]) : [];

            $middlewareClass = "App\\Middleware\\" . ucfirst($name) . "Middleware";
            if (class_exists($middlewareClass)) {
                $mwInstance = new $middlewareClass();
                $mwInstance->handle($args);
            } else {
                throw new \Exception("Middleware class $middlewareClass not found");
            }
        }
    }

    public function route(string $name, array $params = []): string {
        if (!isset($this->namedRoutes[$name])) {
            return '';
        }
        $path = $this->namedRoutes[$name];
        foreach ($params as $key => $val) {
            $path = str_replace('{' . $key . '}', $val, $path);
        }
        return $path;
    }
}
