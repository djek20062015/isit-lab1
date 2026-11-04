# Результаты тестирования REST API

Проверка выполнена на локальном сервере PHP. После тестирования исходные данные JSON восстановлены.

| № | Сценарий | Метод | Адрес | Ожидался | Получен | Результат |
|---|---|---|---|---:|---:|---|
| 1 | HTML главная | GET | `/` | 200 | 200 | OK |
| 2 | events список | GET | `/api/events` | 200 | 200 | OK |
| 3 | events объект | GET | `/api/events/1` | 200 | 200 | OK |
| 4 | events 404 | GET | `/api/events/999` | 404 | 404 | OK |
| 5 | events 405 | DELETE | `/api/events` | 405 | 405 | OK |
| 6 | venues список | GET | `/api/venues` | 200 | 200 | OK |
| 7 | venues объект | GET | `/api/venues/1` | 200 | 200 | OK |
| 8 | venues 404 | GET | `/api/venues/999` | 404 | 404 | OK |
| 9 | venues 405 | DELETE | `/api/venues` | 405 | 405 | OK |
| 10 | categories список | GET | `/api/categories` | 200 | 200 | OK |
| 11 | categories объект | GET | `/api/categories/1` | 200 | 200 | OK |
| 12 | categories 404 | GET | `/api/categories/999` | 404 | 404 | OK |
| 13 | categories 405 | DELETE | `/api/categories` | 405 | 405 | OK |
| 14 | Вложенный ресурс | GET | `/api/venues/1/events` | 200 | 200 | OK |
| 15 | Фильтр | GET | `/api/events?status=draft` | 200 | 200 | OK |
| 16 | Поиск | GET | `/api/events?q=%D1%84%D0%B5%D1%81%D1%82%D0%B8%D0%B2%D0%B0%D0%BB%D1%8C` | 200 | 200 | OK |
| 17 | Пагинация | GET | `/api/events?page=2&limit=2` | 200 | 200 | OK |
| 18 | Неверный JSON | POST | `/api/categories` | 400 | 400 | OK |
| 19 | Неверный тип | POST | `/api/categories` | 415 | 415 | OK |
| 20 | Валидация | POST | `/api/categories` | 422 | 422 | OK |
| 21 | Конфликт | DELETE | `/api/venues/1` | 409 | 409 | OK |
| 22 | events создание | POST | `/api/events` | 201 | 201 | OK |
| 23 | events изменение | PATCH | `/api/events/9` | 200 | 200 | OK |
| 24 | events полное изменение | PUT | `/api/events/9` | 200 | 200 | OK |
| 25 | events удаление | DELETE | `/api/events/9` | 204 | 204 | OK |
| 26 | events после удаления | GET | `/api/events/9` | 404 | 404 | OK |
| 27 | venues создание | POST | `/api/venues` | 201 | 201 | OK |
| 28 | venues изменение | PATCH | `/api/venues/4` | 200 | 200 | OK |
| 29 | venues полное изменение | PUT | `/api/venues/4` | 200 | 200 | OK |
| 30 | venues удаление | DELETE | `/api/venues/4` | 204 | 204 | OK |
| 31 | venues после удаления | GET | `/api/venues/4` | 404 | 404 | OK |
| 32 | categories создание | POST | `/api/categories` | 201 | 201 | OK |
| 33 | categories изменение | PATCH | `/api/categories/4` | 200 | 200 | OK |
| 34 | categories полное изменение | PUT | `/api/categories/4` | 200 | 200 | OK |
| 35 | categories удаление | DELETE | `/api/categories/4` | 204 | 204 | OK |
| 36 | categories после удаления | GET | `/api/categories/4` | 404 | 404 | OK |

Скриншоты запросов следует дополнительно сделать в Postman или терминале при демонстрации работы.
