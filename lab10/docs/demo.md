# Демонстрация на защите

## Redis CLI

Команды выполняются из `lab10/src` при запущенных контейнерах.

```powershell
docker compose exec redis redis-cli PING
docker compose exec redis redis-cli SET lab10:temporary 1 EX 30
docker compose exec redis redis-cli GET lab10:temporary
docker compose exec redis redis-cli TTL lab10:temporary
php bin/cache-demo.php
docker compose exec redis redis-cli TTL eventapp:demo:slow
docker compose exec redis redis-cli --scan --pattern 'eventapp:*'
```

Ожидаются `PONG`, `OK`, значение `1` и положительный TTL при выполнении команд подряд. Демонстрационный скрипт очищает свой ключ и показывает последовательность `MISS → HIT → HIT`.

## Заголовки и инвалидация в браузере

1. Открыть `http://localhost:8000/client/`, войти как руководитель и открыть DevTools → Network.
2. Задать новый поисковый запрос. Открыть ответ `GET /api/events?…` и проверить `X-Cache: MISS`. Повторить тот же запрос кнопкой «Обновить» и проверить `X-Cache: HIT`.
3. Завершить мероприятие кнопкой в таблице. В Network проверить успешный PATCH и последующий GET с `X-Cache: MISS`. В строке меняется статус. Следующее обновление возвращает HIT.
4. Вывести ключи командой `redis-cli --scan`. Ключи старой версии ещё присутствуют до истечения TTL, но новый запрос читает только текущую версию.

## Проверка отказа

```powershell
docker compose stop redis
# Нажать «Обновить» в клиенте: GET возвращает HTTP 200 и X-Cache: BYPASS.
# При остановленном Redis можно изменить мероприятие и проверить новый статус.
docker compose start redis
# Нажать «Обновить» после изменения: первый ответ MISS, следующий HIT.
```

Подтверждение фактического теста остановки и повторного запуска приведено в [протоколе HTTP](evidence/outage.txt). Название мероприятия было изменено, пока Redis был выключен. После перезапуска Redis получил ранее сохранённые ключи, а API прочитал новую версию из PostgreSQL.
