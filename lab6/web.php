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
