-- Запустить после 05_test_data.sql. Сравнивать Execution Time и план в выводе.
-- Скрипт выводит планы ДО и ПОСЛЕ для трёх типовых запросов.
-- При повторном запуске индексы удаляются и создаются заново.
DROP INDEX IF EXISTS idx_events_venue_start;
DROP INDEX IF EXISTS idx_events_category_start;
DROP INDEX IF EXISTS idx_events_status_start;
ANALYZE events;
\echo 'QUERY 1 BEFORE: venue_id + starts_at'
EXPLAIN (ANALYZE, BUFFERS) SELECT id,title,starts_at FROM events
 WHERE venue_id=2 AND starts_at >= '2028-01-01' AND starts_at < '2028-02-01' ORDER BY starts_at;
CREATE INDEX idx_events_venue_start ON events(venue_id,starts_at);
ANALYZE events;
\echo 'QUERY 1 AFTER'
EXPLAIN (ANALYZE, BUFFERS) SELECT id,title,starts_at FROM events
 WHERE venue_id=2 AND starts_at >= '2028-01-01' AND starts_at < '2028-02-01' ORDER BY starts_at;
\echo 'QUERY 2 BEFORE: category_id + starts_at'
EXPLAIN (ANALYZE, BUFFERS) SELECT id,title,starts_at FROM events
 WHERE category_id=3 AND starts_at >= '2030-01-01' AND starts_at < '2030-02-01' ORDER BY starts_at;
CREATE INDEX idx_events_category_start ON events(category_id,starts_at);
ANALYZE events;
\echo 'QUERY 2 AFTER'
EXPLAIN (ANALYZE, BUFFERS) SELECT id,title,starts_at FROM events
 WHERE category_id=3 AND starts_at >= '2030-01-01' AND starts_at < '2030-02-01' ORDER BY starts_at;
\echo 'QUERY 3 BEFORE: status + starts_at'
EXPLAIN (ANALYZE, BUFFERS) SELECT id,title,starts_at FROM events
 WHERE status='planned' AND starts_at >= '2032-01-01' AND starts_at < '2032-02-01' ORDER BY starts_at;
CREATE INDEX idx_events_status_start ON events(status,starts_at);
ANALYZE events;
\echo 'QUERY 3 AFTER'
EXPLAIN (ANALYZE, BUFFERS) SELECT id,title,starts_at FROM events
 WHERE status='planned' AND starts_at >= '2032-01-01' AND starts_at < '2032-02-01' ORDER BY starts_at;
\echo 'CASE WITHOUT INDEX BENEFIT: full table aggregation'
EXPLAIN (ANALYZE, BUFFERS) SELECT COUNT(*) FROM events;
\echo 'CASE WITHOUT BTREE BENEFIT: substring search'
EXPLAIN (ANALYZE, BUFFERS) SELECT id FROM events WHERE title ILIKE '%технология%';
