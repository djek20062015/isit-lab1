<?php
namespace App\Models;
class CategoryRepository extends JsonRepository {
    public function __construct() { $this->file = BASE_PATH . '/storage/categories.json'; }
}
