# Описание учебного API

Все маршруты работают в браузере через mock API. Тела POST/PATCH передаются в JSON.

| Метод | Адрес | Тело запроса | Ответ | Коды |
|---|---|---|---|---|
| GET | `/api/events?status=&category=&q=` | — | Массив мероприятий | 200, 500 |
| GET | `/api/events/:id` | — | Объект мероприятия | 200, 404, 500 |
| GET | `/api/users` | — | Массив пользователей | 200, 500 |
| POST | `/api/events` | `{ "title":"...", "date":"2026-12-12", "venue":"...", "capacity":50, "category":"conference" }` | Созданное мероприятие с `id`, `status: "draft"` | 201, 422, 500 |
| PATCH | `/api/events/:id` | `{ "status":"published" }` | Обновлённое мероприятие | 200, 404, 422, 500 |
| DELETE | `/api/events/:id` | — | `{ "success":true }` | 200, 404, 500 |

Значения статуса: `draft`, `published`, `cancelled`. Категории: `conference`, `workshop`, `hackathon`, `meeting`.

Ошибки возвращаются как `{ "error": "Текст ошибки" }`. Неизвестный маршрут возвращает 404; отключённая сеть приводит к отклонению Promise.
