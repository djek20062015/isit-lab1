<?php
use App\Controllers\HomeController;
use App\Controllers\EventController;
use App\Controllers\VenueController;
$router->get('/', [HomeController::class, 'index']);
$router->get('/events', [EventController::class, 'index']);
$router->get('/events/create', [EventController::class, 'create']);
$router->post('/events', [EventController::class, 'store']);
$router->post('/events/{id}/delete', [EventController::class, 'delete']);
$router->get('/events/{id}', [EventController::class, 'show']);
$router->get('/venues', [VenueController::class, 'index']);
$router->get('/venues/{id}', [VenueController::class, 'show']);

// Маршруты REST API.
use App\Controllers\Api\EventApiController;
use App\Controllers\Api\VenueApiController;
use App\Controllers\Api\CategoryApiController;
foreach (['events' => EventApiController::class, 'venues' => VenueApiController::class, 'categories' => CategoryApiController::class] as $resource => $controller) {
    $router->get('/api/' . $resource, [$controller, 'index']);
    $router->get('/api/' . $resource . '/{id}', [$controller, 'show']);
    $router->post('/api/' . $resource, [$controller, 'store']);
    $router->put('/api/' . $resource . '/{id}', [$controller, 'update']);
    $router->patch('/api/' . $resource . '/{id}', [$controller, 'update']);
    $router->delete('/api/' . $resource . '/{id}', [$controller, 'destroy']);
}
$router->get('/api/venues/{id}/events', [VenueApiController::class, 'events']);
