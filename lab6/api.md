# Маршруты серверного приложения

| Метод | Адрес | Действие | Формат ответа | Коды |
|---|---|---|---|---|
| GET | `/` | Дашборд | HTML | 200 |
| GET | `/events` | Список, `?status=draft/published/completed` | HTML | 200 |
| GET | `/events/create` | Форма создания | HTML | 200 |
| GET | `/events/{id}` | Карточка мероприятия | HTML | 200, 404 |
| POST | `/events` | Создать мероприятие (form-urlencoded) | HTML при ошибке; redirect при успехе | 302, 422 |
| POST | `/events/{id}/delete` | Удалить мероприятие | redirect | 302, 404 |
| GET | `/venues` | Список площадок | HTML | 200 |
| GET | `/venues/{id}` | Карточка и связанные мероприятия | HTML | 200, 404 |
| Любой | неизвестный URL | Нет маршрута | HTML | 404 |
| Неподдерживаемый метод | существующий URL | Метод не разрешён | HTML | 405 |

**Поля POST `/events`:** `title` (минимум 3 символа), `venue_id` (существующая площадка), `date` (YYYY-MM-DD), `status` (`draft`, `published`, `completed`), `capacity` (целое > 0), `description` (текст). В успешном случае сервер возвращает `Location: /events`.

**Для следующей ЛР:** REST API в JSON можно разместить на `/api/events` и `/api/venues`; сейчас маршруты возвращают HTML, как требует ЛР №6.
