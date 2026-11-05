-- CRUD выполняется в транзакции, чтобы демонстрационные изменения не сохранялись.
BEGIN;
-- CREATE: соответствует POST /api/events.
INSERT INTO events(venue_id,category_id,organizer_id,title,status,starts_at,ends_at,ticket_price)
VALUES(1,2,1,'Новый учебный семинар','planned','2027-09-01 10:00+03','2027-09-01 13:00+03',400)
RETURNING id,title,status;
-- CREATE: связующая таблица N:M.
INSERT INTO event_tags(event_id,tag_id)
SELECT id,1 FROM events WHERE title='Новый учебный семинар';
-- READ: фильтрация, сортировка.
SELECT id,title,status,starts_at FROM events WHERE status='planned' ORDER BY starts_at,id;
-- READ: одна запись.
SELECT * FROM events WHERE id=3;
-- READ: обязательные связи.
SELECT e.id,e.title,v.name AS venue,c.name AS category
FROM events e JOIN venues v ON v.id=e.venue_id
JOIN categories c ON c.id=e.category_id ORDER BY e.id;
-- READ: необязательная связь.
SELECT e.title,o.full_name AS organizer FROM events e
LEFT JOIN organizers o ON o.id=e.organizer_id ORDER BY e.id;
-- READ: N:M.
SELECT e.title,t.name AS tag FROM events e
JOIN event_tags et ON et.event_id=e.id JOIN tags t ON t.id=et.tag_id
ORDER BY e.id,t.name;
-- READ: агрегат по категориям.
SELECT c.name,COUNT(e.id) AS event_count,COALESCE(AVG(e.ticket_price),0)::NUMERIC(12,2) AS avg_price
FROM categories c LEFT JOIN events e ON e.category_id=c.id
GROUP BY c.id,c.name ORDER BY c.name;
-- UPDATE: соответствует PATCH /api/events/{id}.
UPDATE events SET status='active' WHERE id=3 RETURNING id,title,status;
-- DELETE: соответствует DELETE /api/events/{id}.
DELETE FROM events WHERE title='Новый учебный семинар' RETURNING id,title;
ROLLBACK;
