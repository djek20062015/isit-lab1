<?php
declare(strict_types=1);
const BASE_PATH = __DIR__ . '/..';
const APP_PATH = BASE_PATH . '/app';
require APP_PATH . '/autoload.php';
require APP_PATH . '/helpers.php';
session_start();
$router = new App\Core\Router();
require BASE_PATH . '/routes/web.php';
$router->dispatch($_SERVER['REQUEST_METHOD'], parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
