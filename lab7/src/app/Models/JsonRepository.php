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
    public function add(array $record): int {
        $rows = $this->all();
        $id = $rows ? max(array_column($rows, 'id')) + 1 : 1;
        $record['id'] = $id;
        $rows[] = $record;
        $this->save($rows);
        return $id;
    }
    public function update(int $id, array $record): bool {
        $rows = $this->all();
        foreach ($rows as &$row) {
            if ((int)$row['id'] === $id) {
                $record['id'] = $id;
                $row = $record;
                unset($row);
                $this->save($rows);
                return true;
            }
        }
        return false;
    }
    public function delete(int $id): bool {
        $rows = $this->all();
        $remaining = array_values(array_filter($rows, fn($row) => (int)$row['id'] !== $id));
        if (count($rows) === count($remaining)) return false;
        $this->save($remaining);
        return true;
    }
    protected function save(array $rows): void {
        $json = json_encode(array_values($rows), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        if (file_put_contents($this->file, $json . "\n", LOCK_EX) === false) throw new \RuntimeException('Ошибка сохранения данных');
    }
}
