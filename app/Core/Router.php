<?php
namespace App\Core;

/**
 * Router แบบเบา: รองรับ static path และ {param}
 *   $r->get('highland/edit/{id}', [HighlandEvalController::class, 'edit']);
 */
class Router
{
    private array $routes = ['GET' => [], 'POST' => []];

    public function get(string $path, array $handler): void  { $this->add('GET',  $path, $handler); }
    public function post(string $path, array $handler): void { $this->add('POST', $path, $handler); }

    private function add(string $method, string $path, array $handler): void
    {
        $this->routes[$method][trim($path, '/')] = $handler;
    }

    public function dispatch(string $method, string $path): void
    {
        $path = trim($path, '/');
        $routes = $this->routes[$method] ?? [];

        foreach ($routes as $pattern => $handler) {
            $params = $this->match($pattern, $path);
            if ($params !== null) {
                [$class, $action] = $handler;
                (new $class())->$action(...array_values($params));
                return;
            }
        }
        $this->notFound();
    }

    /** คืน array params ถ้า match, ไม่งั้น null */
    private function match(string $pattern, string $path): ?array
    {
        if ($pattern === $path) return [];
        if (!str_contains($pattern, '{')) return null;

        $regex = '#^' . preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern) . '$#';
        if (preg_match($regex, $path, $m)) {
            return array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
        }
        return null;
    }

    private function notFound(): void
    {
        http_response_code(404);
        View::render('errors/404', [], 'layout');
    }
}
