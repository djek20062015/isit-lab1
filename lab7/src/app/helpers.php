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

function json_response(mixed $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}
function json_error(string $message, int $status, array $errors = []): never {
    $body = ['error' => $message];
    if ($errors) $body['errors'] = $errors;
    json_response($body, $status);
}
function no_content(): never { http_response_code(204); exit; }
function json_input(): array {
    $type = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));
    if ($type !== 'application/json') json_error('Требуется Content-Type: application/json', 415);
    $body = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($body) || array_is_list($body)) json_error('Некорректный JSON-объект', 400);
    return $body;
}

function utf_length(string $value): int { return preg_match_all('/./us', $value); }
function utf_lower(string $value): string {
    if (function_exists('mb_strtolower')) return mb_strtolower($value, 'UTF-8');
    return strtr(strtolower($value), array_combine(preg_split('//u', 'АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯ', -1, PREG_SPLIT_NO_EMPTY), preg_split('//u', 'абвгдеёжзийклмнопрстуфхцчшщъыьэюя', -1, PREG_SPLIT_NO_EMPTY)));
}
