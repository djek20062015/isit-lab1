<?php
namespace App\Controllers\Api;
use App\Models\EventRepository;
class EventApiController {
    private EventRepository $repository;
    public function __construct() { $this->repository = new EventRepository(); }
    public function index(): never {
        $rows = $this->repository->all();
        if (isset($_GET['status']) && $_GET['status'] !== '') $rows = array_values(array_filter($rows, fn($r) => $r['status'] === $_GET['status']));
        if (isset($_GET['venue_id']) && $_GET['venue_id'] !== '') $rows = array_values(array_filter($rows, fn($r) => (string)$r['venue_id'] === (string)$_GET['venue_id']));
        if (isset($_GET['q']) && $_GET['q'] !== '') {
            $q = \utf_lower((string)$_GET['q']);
            $rows = array_values(array_filter($rows, fn($r) => str_contains(\utf_lower((string)($r['title'] ?? $r['name'] ?? '')), $q)));
        }
        $sort = (string)($_GET['sort'] ?? '');
        if ($sort !== '') {
            $desc = str_starts_with($sort, '-');
            $key = ltrim($sort, '-');
            if (in_array($key, ['id', 'title', 'name', 'date', 'capacity'], true)) {
                usort($rows, fn($a, $b) => ($a[$key] ?? '') <=> ($b[$key] ?? '') ?: ($a['id'] <=> $b['id']));
                if ($desc) $rows = array_reverse($rows);
            }
        }
        if (isset($_GET['page']) || isset($_GET['limit'])) {
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = min(100, max(1, (int)($_GET['limit'] ?? 10)));
            $rows = array_slice($rows, ($page - 1) * $limit, $limit);
        }
        json_response($rows);
    }
    public function show(string $id): never {
        $row = $this->repository->find((int)$id);
        if ($row === null) json_error('Запись не найдена', 404);
        json_response($row);
    }
    public function store(): never {
        $data = $this->clean(json_input());
        $errors = $this->validate($data);
        if ($errors) json_error('Ошибка проверки данных', 422, $errors);
        $id = $this->repository->add($data);
        header('Location: /api/events/' . $id);
        json_response($this->repository->find($id), 201);
    }
    public function update(string $id): never {
        $existing = $this->repository->find((int)$id);
        if ($existing === null) json_error('Запись не найдена', 404);
        $data = array_merge($existing, $this->clean(json_input()));
        $errors = $this->validate($data);
        if ($errors) json_error('Ошибка проверки данных', 422, $errors);
        $this->repository->update((int)$id, $data);
        json_response($this->repository->find((int)$id));
    }
    public function destroy(string $id): never {
        if (!$this->repository->find((int)$id)) json_error('Запись не найдена', 404);
        $this->repository->delete((int)$id);
        no_content();
    }
    private function clean(array $data): array {
        return array_intersect_key($data, array_flip(['venue_id', 'title', 'date', 'status', 'capacity', 'description']));
    }
    private function validate(array $data): array {
        $errors = [];
        if (\utf_length(trim((string)($data['title'] ?? ''))) < 3) $errors['title'] = 'Название должно содержать не менее 3 символов';
        if (!in_array($data['status'] ?? '', ['draft','published','completed'], true)) $errors['status'] = 'Недопустимый статус';
        if (!is_int($data['venue_id'] ?? null) || !(new \App\Models\VenueRepository())->find($data['venue_id'])) $errors['venue_id'] = 'Неизвестная площадка';
        if (!is_int($data['capacity'] ?? null) || $data['capacity'] < 1) $errors['capacity'] = 'Вместимость должна быть положительным целым числом';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($data['date'] ?? ''))) $errors['date'] = 'Дата должна иметь формат YYYY-MM-DD';
        return $errors;
    }
}
