# Демонстрация работы

В исходном учебном наборе событие № 1 имеет статус `planned`. Для запуска команд применяется PowerShell и `curl.exe`.

```powershell
php bin/queue-check.php
php bin/queue-stats.php --ready --working --dead

$base = 'http://127.0.0.1:8000'
$editor = @{email='igor@example.com'; password='123456'} | ConvertTo-Json
$manager = @{email='anna@example.com'; password='123456'} | ConvertTo-Json
$token = (Invoke-RestMethod "$base/api/auth/login" -Method Post -ContentType 'application/json' -Body $editor).token
$anna = (Invoke-RestMethod "$base/api/auth/login" -Method Post -ContentType 'application/json' -Body $manager).token
$status = @{status='completed'} | ConvertTo-Json -Compress
$headers = @{Authorization="Bearer $token"}
(Invoke-WebRequest "$base/api/events/1" -Method Patch -Headers $headers -ContentType 'application/json' -Body $status).Headers
php bin/queue-stats.php --ready
docker compose exec redis redis-cli LRANGE eventapp11:queue:default:ready 0 -1
php bin/worker.php --until-empty
Invoke-RestMethod "$base/api/notifications" -Headers @{Authorization="Bearer $anna"}

$order = Invoke-WebRequest "$base/api/reports" -Method Post -Headers $headers
$order.StatusCode
$location = [string]($order.Headers.Location | Select-Object -First 1)
Invoke-RestMethod "$base$location" -Headers $headers
php bin/worker.php --queue=reports --until-empty
$result = Invoke-RestMethod "$base$location" -Headers $headers
Invoke-WebRequest "$base$($result.download_url)" -Headers $headers -OutFile report.csv
```

Перед демонстрацией очереди воркеры должны быть остановлены. Первый PATCH меняет статус и возвращает `X-Job-Id`. При повторении надо выбрать другой статус. Экспорт до запуска воркера показывает `queued`, после обработки — `done`.

## Сбои

- `php bin/queue-demo.php 1 --fail` → `php bin/worker.php --until-empty` → `php bin/queue-stats.php --dead`.
- `php bin/queue-demo.php 1 --slow` → запустить воркер → дождаться вывода «попытка 1» → на Linux выполнить `kill -9 PID` → проверить `working:1` → запустить воркер снова. PID выведен в журнале запуска.
- Для плавной остановки повторить медленную задачу, затем выполнить `kill -TERM PID` или Ctrl+C. После окончания работы резерв должен исчезнуть.
- При остановке Redis выполнить PATCH, затем запустить Redis и воркер. Сохранённая запись outbox будет доставлена.
