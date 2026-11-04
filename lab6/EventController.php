<?php
namespace App\Controllers;
use App\Models\EventRepository;
use App\Models\VenueRepository;
class EventController {
    private EventRepository $events;
    private VenueRepository $venues;
    public function __construct() { $this->events = new EventRepository(); $this->venues = new VenueRepository(); }
    public function index(): void {
        $status = (string)($_GET['status'] ?? '');
        $events = in_array($status, ['draft','published','completed'], true) ? $this->events->byStatus($status) : $this->events->all();
        $venues = array_column($this->venues->all(), 'name', 'id');
        \view('events/index', compact('events', 'venues', 'status'));
    }
    public function show(string $id): void {
        if (!ctype_digit($id) || !($event = $this->events->find((int)$id))) \notFound();
        $venue = $this->venues->find((int)$event['venue_id']);
        \view('events/show', compact('event','venue'));
    }
    public function create(): void { \view('events/create', ['venues' => $this->venues->all(), 'errors' => [], 'old' => []]); }
    public function store(): void {
        $old = [
            'title' => trim((string)($_POST['title'] ?? '')),
            'venue_id' => (string)($_POST['venue_id'] ?? ''),
            'date' => trim((string)($_POST['date'] ?? '')),
            'status' => (string)($_POST['status'] ?? 'draft'),
            'capacity' => (string)($_POST['capacity'] ?? ''),
            'description' => trim((string)($_POST['description'] ?? '')),
        ];
        $errors = [];
        if (count(preg_split('//u', $old['title'], -1, PREG_SPLIT_NO_EMPTY)) < 3) $errors['title'] = 'Название должно содержать минимум 3 символа.';
        if (!ctype_digit($old['venue_id']) || !$this->venues->find((int)$old['venue_id'])) $errors['venue_id'] = 'Выберите площадку.';
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $old['date']);
        if (!$date || $date->format('Y-m-d') !== $old['date']) $errors['date'] = 'Укажите корректную дату.';
        if (!in_array($old['status'], ['draft','published','completed'], true)) $errors['status'] = 'Некорректный статус.';
        if (filter_var($old['capacity'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) $errors['capacity'] = 'Вместимость должна быть положительным числом.';
        if ($errors) { http_response_code(422); \view('events/create', ['venues' => $this->venues->all(), 'errors' => $errors, 'old' => $old]); return; }
        $this->events->add(['title'=>$old['title'], 'venue_id'=>(int)$old['venue_id'], 'date'=>$old['date'], 'status'=>$old['status'], 'capacity'=>(int)$old['capacity'], 'description'=>$old['description']]);
        \flash('Мероприятие успешно создано.'); \redirect('/events');
    }
    public function delete(string $id): void {
        if (!ctype_digit($id) || !$this->events->delete((int)$id)) \notFound();
        \flash('Мероприятие удалено.'); \redirect('/events');
    }
}
