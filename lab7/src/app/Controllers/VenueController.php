<?php
namespace App\Controllers;
use App\Models\VenueRepository;
use App\Models\EventRepository;
class VenueController {
    public function index(): void { $venues = (new VenueRepository())->all(); \view('venues/index', compact('venues')); }
    public function show(string $id): void {
        if (!ctype_digit($id) || !($venue = (new VenueRepository())->find((int)$id))) \notFound();
        $events = (new EventRepository())->byVenue((int)$id);
        \view('venues/show', compact('venue','events'));
    }
}
