-- Выполнять после 01_schema.sql и 02_seed.sql; однократная генерация 150 000 строк.
-- Для повторного теста пересоздать схему и заполнить исходные данные.
INSERT INTO events(venue_id,category_id,organizer_id,title,description,status,starts_at,ends_at,ticket_price)
SELECT 1 + (g % 4), 1 + (g % 3), CASE WHEN g % 7=0 THEN NULL ELSE 1+(g % 3) END,
       'Тестовое мероприятие '||g, 'Данные для анализа производительности',
       (ARRAY['planned','active','completed','cancelled'])[1+(g % 4)],
       '2027-01-01 10:00+03'::timestamptz + (g || ' hours')::interval,
       '2027-01-01 12:00+03'::timestamptz + (g || ' hours')::interval,
       (g % 1000)::numeric
FROM generate_series(1,150000) AS g;
ANALYZE events;
SELECT COUNT(*) AS total_events FROM events;
