# Документация REST API

Базовый адрес: `http://localhost:8000`. Тело POST/PUT/PATCH — JSON с заголовком `Content-Type: application/json`.

## Сводная таблица

| Метод | URL | Назначение | Код |
|---|---|---|---|
| GET | `/api/events` | список: мероприятия | 200 |
| GET | `/api/events/{id}` | одна запись: мероприятия | 200 |
| POST | `/api/events` | создание: мероприятия | 201 |
| PUT/PATCH | `/api/events/{id}` | изменение: мероприятия | 200 |
| DELETE | `/api/events/{id}` | удаление: мероприятия | 204 |
| GET | `/api/venues` | список: площадки | 200 |
| GET | `/api/venues/{id}` | одна запись: площадки | 200 |
| POST | `/api/venues` | создание: площадки | 201 |
| PUT/PATCH | `/api/venues/{id}` | изменение: площадки | 200 |
| DELETE | `/api/venues/{id}` | удаление: площадки | 204 |
| GET | `/api/categories` | список: категории | 200 |
| GET | `/api/categories/{id}` | одна запись: категории | 200 |
| POST | `/api/categories` | создание: категории | 201 |
| PUT/PATCH | `/api/categories/{id}` | изменение: категории | 200 |
| DELETE | `/api/categories/{id}` | удаление: категории | 204 |
| GET | `/api/venues/{id}/events` | Мероприятия площадки | 200 |


## Мероприятия

Поля: `title, venue_id, date, status, capacity, description`. `id` — целое число, назначается сервером.

### GET /api/events

Назначение: Получение списка. Параметры: отсутствуют. Необязательные query-параметры: `q`, `page`, `limit`, `sort`, `status`, `venue_id`. 

Запрос:

```http
GET /api/events HTTP/1.1

```

Ответ: **200**, массив объектов JSON.

Ошибки: отсутствуют при корректном запросе.

### GET /api/events/{id}

Назначение: Получение по идентификатору. Параметры: `id` в пути. 

Запрос:

```http
GET /api/events/1 HTTP/1.1

```

Ответ: **200**, {"id": 8, "venue_id": 1, "title": "Новая конференция", "date": "2026-12-20", "status": "draft", "capacity": 100, "description": "Описание мероприятия"}.

Ошибки: 404.

### POST /api/events

Назначение: Создание. Параметры: отсутствуют. Тело JSON: `venue_id`, `title`, `date`, `status`, `capacity`, `description`. 

Запрос:

```http
POST /api/events HTTP/1.1
Content-Type: application/json

{"venue_id": 1, "title": "Новая конференция", "date": "2026-12-20", "status": "draft", "capacity": 100, "description": "Описание мероприятия"}
```

Ответ: **201**, {"id": 8, "venue_id": 1, "title": "Новая конференция", "date": "2026-12-20", "status": "draft", "capacity": 100, "description": "Описание мероприятия"}. Заголовок `Location: /api/events/8`.

Ошибки: 400, 415, 422.

### PATCH /api/events/{id}

Назначение: Частичное обновление (PUT также поддерживается). Параметры: `id` в пути. Тело JSON: `venue_id`. 

Запрос:

```http
PATCH /api/events/1 HTTP/1.1
Content-Type: application/json

{"venue_id": 1}
```

Ответ: **200**, {"id": 8, "venue_id": 1, "title": "Новая конференция", "date": "2026-12-20", "status": "draft", "capacity": 100, "description": "Описание мероприятия"}.

Ошибки: 400, 404, 415, 422.

### DELETE /api/events/{id}

Назначение: Удаление. Параметры: `id` в пути. 

Запрос:

```http
DELETE /api/events/1 HTTP/1.1

```

Ответ: **204**, без тела.

Ошибки: 404.


## Площадки

Поля: `name, address, capacity`. `id` — целое число, назначается сервером.

### GET /api/venues

Назначение: Получение списка. Параметры: отсутствуют. Необязательные query-параметры: `q`, `page`, `limit`, `sort`. 

Запрос:

```http
GET /api/venues HTTP/1.1

```

Ответ: **200**, массив объектов JSON.

Ошибки: отсутствуют при корректном запросе.

### GET /api/venues/{id}

Назначение: Получение по идентификатору. Параметры: `id` в пути. 

Запрос:

```http
GET /api/venues/1 HTTP/1.1

```

Ответ: **200**, {"id": 8, "name": "Новая площадка", "address": "Москва, ул. Новая, 1", "capacity": 250}.

Ошибки: 404.

### POST /api/venues

Назначение: Создание. Параметры: отсутствуют. Тело JSON: `name`, `address`, `capacity`. 

Запрос:

```http
POST /api/venues HTTP/1.1
Content-Type: application/json

{"name": "Новая площадка", "address": "Москва, ул. Новая, 1", "capacity": 250}
```

Ответ: **201**, {"id": 8, "name": "Новая площадка", "address": "Москва, ул. Новая, 1", "capacity": 250}. Заголовок `Location: /api/venues/8`.

Ошибки: 400, 415, 422.

### PATCH /api/venues/{id}

Назначение: Частичное обновление (PUT также поддерживается). Параметры: `id` в пути. Тело JSON: `name`. 

Запрос:

```http
PATCH /api/venues/1 HTTP/1.1
Content-Type: application/json

{"name": "Новая площадка"}
```

Ответ: **200**, {"id": 8, "name": "Новая площадка", "address": "Москва, ул. Новая, 1", "capacity": 250}.

Ошибки: 400, 404, 415, 422.

### DELETE /api/venues/{id}

Назначение: Удаление. Параметры: `id` в пути. 

Запрос:

```http
DELETE /api/venues/1 HTTP/1.1

```

Ответ: **204**, без тела.

Ошибки: 404 и 409 при удалении площадки, содержащей мероприятия.


## Категории

Поля: `name, description`. `id` — целое число, назначается сервером.

### GET /api/categories

Назначение: Получение списка. Параметры: отсутствуют. Необязательные query-параметры: `q`, `page`, `limit`, `sort`. 

Запрос:

```http
GET /api/categories HTTP/1.1

```

Ответ: **200**, массив объектов JSON.

Ошибки: отсутствуют при корректном запросе.

### GET /api/categories/{id}

Назначение: Получение по идентификатору. Параметры: `id` в пути. 

Запрос:

```http
GET /api/categories/1 HTTP/1.1

```

Ответ: **200**, {"id": 8, "name": "Спорт", "description": "Спортивные мероприятия"}.

Ошибки: 404.

### POST /api/categories

Назначение: Создание. Параметры: отсутствуют. Тело JSON: `name`, `description`. 

Запрос:

```http
POST /api/categories HTTP/1.1
Content-Type: application/json

{"name": "Спорт", "description": "Спортивные мероприятия"}
```

Ответ: **201**, {"id": 8, "name": "Спорт", "description": "Спортивные мероприятия"}. Заголовок `Location: /api/categories/8`.

Ошибки: 400, 415, 422.

### PATCH /api/categories/{id}

Назначение: Частичное обновление (PUT также поддерживается). Параметры: `id` в пути. Тело JSON: `name`. 

Запрос:

```http
PATCH /api/categories/1 HTTP/1.1
Content-Type: application/json

{"name": "Спорт"}
```

Ответ: **200**, {"id": 8, "name": "Спорт", "description": "Спортивные мероприятия"}.

Ошибки: 400, 404, 415, 422.

### DELETE /api/categories/{id}

Назначение: Удаление. Параметры: `id` в пути. 

Запрос:

```http
DELETE /api/categories/1 HTTP/1.1

```

Ответ: **204**, без тела.

Ошибки: 404.

### GET /api/venues/{id}/events

Параметр пути: `id` площадки. Пример: `GET /api/venues/1/events`. Ответ 200 — массив мероприятий; 404 — площадка не найдена.

## Ошибки

```json
{"error":"Ошибка проверки данных","errors":{"name":"Название должно содержать не менее 3 символов"}}
```

400 — некорректный JSON; 404 — объект или адрес отсутствует; 405 — метод не поддерживается, заголовок `Allow`; 409 — площадка содержит мероприятия; 415 — неподдерживаемый Content-Type; 422 — ошибка валидации. Для PATCH передаются только изменяемые поля, для PUT в учебной реализации применяется тот же принцип.
