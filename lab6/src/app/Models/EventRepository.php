<?php
namespace App\Models;
class EventRepository extends JsonRepository {
    public function __construct() { $this->file = BASE_PATH . '/storage/events.json'; }
    public function byVenue(int $venueId): array { return array_values(array_filter($this->all(), fn($e) => (int)$e['venue_id'] === $venueId)); }
    public function byStatus(string $status): array { return array_values(array_filter($this->all(), fn($e) => $e['status'] === $status)); }
    public function add(array $event): int {
        $rows = $this->all(); $id = $rows ? max(array_column($rows, 'id')) + 1 : 1;
        $event['id'] = $id; $rows[] = $event; $this->save($rows); return $id;
    }
    public function delete(int $id): bool {
        $rows = $this->all(); $new = array_values(array_filter($rows, fn($row) => (int)$row['id'] !== $id));
        if (count($new) === count($rows)) return false;
        $this->save($new); return true;
    }
}
