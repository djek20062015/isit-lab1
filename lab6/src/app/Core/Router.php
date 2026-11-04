<?php
namespace App\Core;
class Router {
    private array $routes = [];
    public function add(string $method, string $path, array $handler): void { $this->routes[] = compact('method', 'path', 'handler'); }
    public function get(string $path, array $handler): void { $this->add('GET', $path, $handler); }
    public function post(string $path, array $handler): void { $this->add('POST', $path, $handler); }
    public function dispatch(string $method, string $path): void {
        $allowed = [];
        foreach ($this->routes as $route) {
            $pattern = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', fn($m) => '(?P<' . $m[1] . '>[^/]+)', $route['path']);
            if (!preg_match('~^' . $pattern . '$~D', $path, $matches)) continue;
            $allowed[] = $route['method'];
            if ($route['method'] !== $method) continue;
            $args = [];
            foreach ($matches as $key => $value) if (is_string($key)) $args[$key] = $value;
            [$class, $action] = $route['handler'];
            (new $class())->$action(...$args);
            return;
        }
        if ($allowed) { http_response_code(405); header('Allow: ' . implode(', ', array_unique($allowed))); view('errors/405'); return; }
        \notFound();
    }
}
