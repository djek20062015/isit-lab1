# Лабораторная работа №7. Проектирование REST API

## Цель
Реализовать REST API информационной системы управления мероприятиями с использованием PHP 8.2+, маршрутизации HTTP-запросов, JSON и операций CRUD.

## Предметная область
Основная сущность — мероприятие (`events`), связанная сущность — площадка (`venues`), третья сущность — категория (`categories`). Мероприятие связано с площадкой через `venue_id`. Статусы: `draft`, `published`, `completed`.

## Ресурсы

| Ресурс | Адрес | Методы | Поля |
|---|---|---|---|
| Мероприятия | `/api/events`, `/api/events/{id}` | GET, POST, PUT, PATCH, DELETE | title, venue_id, date, status, capacity, description |
| Площадки | `/api/venues`, `/api/venues/{id}` | GET, POST, PUT, PATCH, DELETE | name, address, capacity |
| Категории | `/api/categories`, `/api/categories/{id}` | GET, POST, PUT, PATCH, DELETE | name, description |

Дополнительный маршрут `GET /api/venues/{id}/events` возвращает мероприятия выбранной площадки. Для списков доступны `q`, `page`, `limit`, `sort`; для мероприятий — `status`, `venue_id`.

## Запуск

```bash
cd lab7/src
php -S localhost:8000 -t public
```

Сайт: `http://localhost:8000/`. API: `http://localhost:8000/api/events`.

## Примеры операций

```bash
curl -i http://localhost:8000/api/events
curl -i http://localhost:8000/api/events/1
curl -i -X POST http://localhost:8000/api/categories -H 'Content-Type: application/json' -d '{"name":"Спорт","description":"Спортивные события"}'
curl -i -X PATCH http://localhost:8000/api/categories/1 -H 'Content-Type: application/json' -d '{"description":"Обновлённое описание"}'
curl -i -X DELETE http://localhost:8000/api/categories/3
```

Успешные коды: `200`, `201` (с `Location`), `204` (без тела). Ошибки: `400`, `404`, `405` (с `Allow`), `409`, `415`, `422`.

## Структура
`src/public` — точка входа и CSS; `src/app/Core` — маршрутизатор; `src/app/Controllers/Api` — API-контроллеры; `src/app/Models` — JSON-репозитории; `src/app/Views` — HTML-шаблоны сайта из ЛР №6; `src/routes` — таблица маршрутов; `src/storage` — JSON-данные; `docs` — документация и результаты проверок.

## Вывод
Реализованы три ресурса с операциями CRUD, единым форматом JSON-ответов, проверкой данных, фильтрацией и обработкой HTTP-ошибок. Сохранены HTML-страницы приложения предыдущей лабораторной работы.

Полное описание конечных точек: [docs/api.md](docs/api.md). Проверки: [docs/testing.md](docs/testing.md).
