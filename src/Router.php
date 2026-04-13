<?php
/**
 * Simple front-controller Router
 *
 * Supports static routes and one dynamic segment (:id).
 *
 * Usage (in public/index.php):
 *   $router = new Router();
 *   $router->get('/',              'DashboardController@index');
 *   $router->get('/customers',     'CustomerController@index');
 *   $router->get('/customers/:id', 'CustomerController@show');
 *   $router->post('/customers',    'CustomerController@store');
 *   $router->dispatch();
 */
class Router
{
    private array $routes = [];

    // ── Route registration ────────────────────────────────────────────────────

    public function get(string $path, string $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, string $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function any(string $path, string $handler): void
    {
        $this->addRoute('GET',  $path, $handler);
        $this->addRoute('POST', $path, $handler);
    }

    private function addRoute(string $method, string $path, string $handler): void
    {
        $this->routes[] = [
            'method'  => strtoupper($method),
            'path'    => rtrim($path, '/') ?: '/',
            'handler' => $handler,
        ];
    }

    // ── Dispatch ──────────────────────────────────────────────────────────────

    public function dispatch(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        // Support method override via hidden _method field (for PUT/DELETE in forms)
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        $uri = $this->getUri();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = $this->matchPath($route['path'], $uri);
            if ($params !== false) {
                $this->callHandler($route['handler'], $params);
                return;
            }
        }

        // No route matched
        $this->notFound();
    }

    // ── Path matching ─────────────────────────────────────────────────────────

    /**
     * Match $routePath against $uri.
     * Returns an array of captured params, or false if no match.
     * Supports :id, :slug, :any style segments.
     */
    private function matchPath(string $routePath, string $uri): array|false
    {
        // Exact match fast-path
        if ($routePath === $uri) {
            return [];
        }

        $routeParts = explode('/', trim($routePath, '/'));
        $uriParts   = explode('/', trim($uri, '/'));

        if (count($routeParts) !== count($uriParts)) {
            return false;
        }

        $params = [];
        foreach ($routeParts as $i => $segment) {
            if (str_starts_with($segment, ':')) {
                // Dynamic segment — capture it
                $params[ltrim($segment, ':')] = $uriParts[$i];
            } elseif ($segment !== $uriParts[$i]) {
                return false;
            }
        }

        return $params;
    }

    // ── Handler invocation ────────────────────────────────────────────────────

    /**
     * Parse "ControllerClass@method" and call it.
     * Params captured from the URL are passed as method arguments.
     */
    private function callHandler(string $handler, array $params): void
    {
        [$class, $method] = explode('@', $handler, 2);

        if (!class_exists($class)) {
            throw new RuntimeException("Controller not found: {$class}");
        }

        $controller = new $class();

        if (!method_exists($controller, $method)) {
            throw new RuntimeException("Method {$class}::{$method}() not found");
        }

        // Pass URL params as positional args (e.g. show($id))
        call_user_func_array([$controller, $method], array_values($params));
    }

    // ── URI helpers ───────────────────────────────────────────────────────────

    private function getUri(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        // Strip the base path (e.g. /repair-system/public) from the URI
        $basePath = parse_url(BASE_URL, PHP_URL_PATH);
        if ($basePath && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath));
        }

        return rtrim($uri, '/') ?: '/';
    }

    private function notFound(): void
    {
        http_response_code(404);
        require VIEWS_PATH . '/errors/404.php';
        exit;
    }
}
