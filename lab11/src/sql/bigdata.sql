-- Добавление 100 000 мероприятий для сравнительных измерений.
-- Выполнять после reset-db.php. Повторный запуск добавит ещё 100 000 записей.
INSERT INTO events(venue_id,category_id,organizer_id,title,description,status,starts_at,ends_at,ticket_price)
SELECT 1+(g%4),1+(g%3),1+(g%3),
 'Мероприятие №'||g||CASE WHEN g%20=0 THEN ' технологии' ELSE '' END,
 'Нагрузочные данные',
 (ARRAY['planned','active','completed','cancelled'])[1+(g%4)],
 TIMESTAMPTZ '2027-01-01 09:00:00+03' + (g||' hours')::interval,
 TIMESTAMPTZ '2027-01-01 11:00:00+03' + (g||' hours')::interval,
 (g%3000)::numeric
FROM generate_series(1,100000) AS g;
ANALYZE events;
