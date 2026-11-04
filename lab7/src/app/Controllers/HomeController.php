<?php
namespace App\Controllers;
use App\Models\EventRepository;
use App\Models\VenueRepository;
class HomeController {
    public function index(): void {
        $events = (new EventRepository())->all();
        $stats = ['total' => count($events), 'draft' => 0, 'published' => 0, 'completed' => 0, 'venues' => count((new VenueRepository())->all())];
        foreach ($events as $event) if (isset($stats[$event['status']])) $stats[$event['status']]++;
        \view('home', compact('stats'));
    }
}
