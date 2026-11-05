# Протокол проверки

## Последовательность запуска

```bash
docker compose up -d
docker compose exec -T db psql -v ON_ERROR_STOP=1 -U postgres -d eventmanager -f /sql/01_schema.sql
docker compose exec -T db psql -v ON_ERROR_STOP=1 -U postgres -d eventmanager -f /sql/02_seed.sql
docker compose exec -T db psql -v ON_ERROR_STOP=1 -U postgres -d eventmanager -f /sql/03_integrity.sql
docker compose exec -T db psql -v ON_ERROR_STOP=1 -U postgres -d eventmanager -f /sql/04_crud.sql
docker compose exec -T db psql -v ON_ERROR_STOP=1 -U postgres -d eventmanager -f /sql/05_test_data.sql
docker compose exec -T db psql -v ON_ERROR_STOP=1 -U postgres -d eventmanager -f /sql/06_optimization.sql
```

## Контрольные результаты

| Проверка | Ожидается | Фактический результат |
|---|---|---|
| `\dt` | 8 таблиц | Требует запуска PostgreSQL |
| `SELECT COUNT(*) FROM events` после seed | 7 | Требует запуска PostgreSQL |
| `03_integrity.sql` | NOTICE PASS для ограничений | Требует запуска PostgreSQL |
| `04_crud.sql` | INSERT, SELECT, UPDATE, DELETE с ROLLBACK | Требует запуска PostgreSQL |
| `05_test_data.sql` | 150007 мероприятий | Требует запуска PostgreSQL |
| `06_optimization.sql` | 3 пары планов EXPLAIN ANALYZE | Требует запуска PostgreSQL |

Скриншоты SQL-клиента и планов выполнения размещаются в `images/` после фактического запуска.
