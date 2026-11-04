<?php
namespace App\Models;
class VenueRepository extends JsonRepository {
    public function __construct() { $this->file = BASE_PATH . '/storage/venues.json'; }
}
