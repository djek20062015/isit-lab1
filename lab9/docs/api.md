# Документация REST API EventManager

Базовый адрес: `http://localhost:8000/api`. Все ответы — JSON, кроме `204 No Content`. Для запросов с телом используется `Content-Type: application/json`. Для защищённых запросов необходим заголовок `Authorization: Bearer <token>`.

## Авторизация

| Метод | Адрес | Назначение | Код |
|---|---|---|---|
| POST | `/api/auth/login` | Проверка email и пароля, выдача токена | 200 |
| GET | `/api/auth/me` | Текущий пользователь | 200 |
| POST | `/api/auth/logout` | Отзыв токена | 204 |

**Вход**: `POST /api/auth/login` с `{"email":"anna@example.com","password":"123456"}`. Ответ: `{"token":"<64 hex>","expires_at":"2027-01-01T00:00:00+00:00","user":{"id":1,"name":"Анна Петрова","email":"anna@example.com","role":"manager"}}`. Время истечения и токен зависят от момента входа. Ошибки: 400, 401, 415, 422.

**Текущий пользователь**: `GET /api/auth/me` с Bearer-токеном; ответ 200: `{"id":1,"name":"Анна Петрова","email":"anna@example.com","role":"manager"}`. Ошибка 401.

**Выход**: `POST /api/auth/logout` с Bearer-токеном; ответ 204 без тела; повторное использование токена даёт 401.

## Права

| Действие | manager | editor | viewer |
|---|---|---|---|
| Просмотр всех ресурсов | Да | Да | Да |
| Создание и изменение мероприятий | Да | Да | Нет |
| Удаление мероприятий | Да | Нет | Нет |
| Создание, изменение и удаление площадок/категорий | Да | Нет | Нет |

`401` — нет действующего токена; `403` — роль не позволяет действие. Пароли и хеши паролей никогда не включаются в JSON.

## Ресурсы

| Метод | URL | Назначение | Код |
|---|---|---|---|
| GET | `/api/events` | Список, фильтрация и поиск | 200 |
| GET | `/api/events/{id}` | Мероприятие | 200 |
| POST | `/api/events` | Создание | 201 |
| PUT/PATCH | `/api/events/{id}` | Изменение | 200 |
| DELETE | `/api/events/{id}` | Удаление | 204 |
| GET | `/api/venues` | Список площадок | 200 |
| GET | `/api/venues/{id}` | Площадка | 200 |
| POST | `/api/venues` | Создание площадки | 201 |
| PUT/PATCH | `/api/venues/{id}` | Изменение площадки | 200 |
| DELETE | `/api/venues/{id}` | Удаление площадки | 204 |
| GET | `/api/categories` | Список категорий | 200 |
| GET | `/api/categories/{id}` | Категория | 200 |
| POST | `/api/categories` | Создание категории | 201 |
| PUT/PATCH | `/api/categories/{id}` | Изменение категории | 200 |
| DELETE | `/api/categories/{id}` | Удаление категории | 204 |

### Примеры операций

**Список мероприятий**: `GET /api/events?status=planned&q=форум&sort=-id&page=1&limit=10` → `200`, массив объектов. Доступные фильтры: `status`, `venue_id`, `category_id`, `q`; сортировка: `id`, `-id`, `starts_at`, `-starts_at`, `title`, `-title`; `page` от 1, `limit` от 1 до 100.

**Одна запись**: `GET /api/events/1` → `200` с полями `id`, `title`, `venue_id`, `category_id`, `organizer_id`, `description`, `status`, `starts_at`, `ends_at`, `ticket_price`, `created_at`. Несуществующий ID → 404.

**Создание мероприятия**: `POST /api/events`:

```json
{"title":"Практикум по дизайну","venue_id":1,"category_id":2,"status":"planned","starts_at":"2027-07-01T10:00:00+03:00","ends_at":"2027-07-01T13:00:00+03:00","ticket_price":0,"description":"Учебное мероприятие"}
```

Ответ `201 Created`, объект с новым `id`, заголовок `Location: /api/events/{id}`.

**Изменение мероприятия**: `PATCH /api/events/1` с `{"status":"completed"}` → `200` и обновлённый объект. `PUT` в учебной реализации также изменяет только переданные поля.

**Удаление мероприятия**: `DELETE /api/events/1` → `204` без тела (только manager).

**Площадка**: `POST /api/venues` с `{"name":"Зал конференций","address":"Москва, ул. Примерная, 1","capacity":100}` → 201; `PATCH /api/venues/1` с `{"capacity":150}` → 200; `GET /api/venues/1` → 200; `DELETE /api/venues/1` → 204 либо 409 при наличии связанных мероприятий.

**Категория**: `POST /api/categories` с `{"name":"Семинары","description":"Образовательные встречи"}` → 201; `PATCH /api/categories/1` с `{"description":"Новая информация"}` → 200; `GET /api/categories/1` → 200; `DELETE /api/categories/1` → 204 либо 409.

## Ошибки

Формат: `{"error":"Ошибка проверки данных","errors":{"title":"Название — не короче 3 символов"}}`. Статусы: 400 — некорректный JSON; 401 — неавторизован; 403 — недостаточно прав; 404 — отсутствует запись/маршрут; 405 — метод не разрешён (`Allow`); 409 — конфликт ограничений БД; 415 — неверный Content-Type; 422 — ошибка валидации; 500 — ошибка сервера; 503 — недоступная БД.
