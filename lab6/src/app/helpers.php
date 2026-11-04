<?php
function e(mixed $value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function config(string $key): mixed { $cfg = require BASE_PATH . '/config/app.php'; return $cfg[$key] ?? null; }
function view(string $name, array $data = []): void {
    extract($data, EXTR_SKIP);
    require APP_PATH . '/Views/layout/header.php';
    require APP_PATH . '/Views/' . $name . '.php';
    require APP_PATH . '/Views/layout/footer.php';
}
function redirect(string $path): never { header('Location: ' . $path, true, 302); exit; }
function notFound(): never { http_response_code(404); view('errors/404'); exit; }
function flash(string $message): void { $_SESSION['flash'] = $message; }
