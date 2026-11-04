<?php
namespace App\Models;
abstract class JsonRepository {
    protected string $file;
    public function all(): array {
        if (!is_file($this->file)) return [];
        $rows = json_decode(file_get_contents($this->file), true);
        return is_array($rows) ? $rows : [];
    }
    public function find(int $id): ?array { foreach ($this->all() as $row) if ((int)$row['id'] === $id) return $row; return null; }
    protected function save(array $rows): void {
        $json = json_encode(array_values($rows), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        if (file_put_contents($this->file, $json . "\n", LOCK_EX) === false) throw new \RuntimeException('Ошибка сохранения данных');
    }
}
