<?php
namespace App\Models;
class EventRepository extends JsonRepository {
    public function __construct() { $this->file = BASE_PATH . '/storage/events.json'; }
    public function byVenue(int $venueId): array { return array_values(array_filter($this->all(), fn($e) => (int)$e['venue_id'] === $venueId)); }
    public function byStatus(string $status): array { return array_values(array_filter($this->all(), fn($e) => $e['status'] === $status)); }
}
