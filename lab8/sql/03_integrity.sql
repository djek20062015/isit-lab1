-- Каждая проверка выполняется в отдельном блоке; ожидаемая ошибка перехватывается.
-- Скрипт не меняет исходные данные.
DO $$ BEGIN
 BEGIN
  INSERT INTO events(venue_id,category_id,title,starts_at,ends_at)
  VALUES(99999,1,'Проверка FK','2027-07-01','2027-07-02');
  RAISE EXCEPTION 'Не сработал внешний ключ';
 EXCEPTION WHEN foreign_key_violation THEN RAISE NOTICE 'PASS: FK venues'; END;
 BEGIN
  INSERT INTO events(venue_id,category_id,title,starts_at,ends_at)
  VALUES(1,1,'Да','2027-07-01','2027-07-02');
  RAISE EXCEPTION 'Не сработал CHECK title';
 EXCEPTION WHEN check_violation THEN RAISE NOTICE 'PASS: CHECK title'; END;
 BEGIN
  INSERT INTO events(venue_id,category_id,title,starts_at,ends_at,status)
  VALUES(1,1,'Проверка статуса','2027-07-01','2027-07-02','unknown');
  RAISE EXCEPTION 'Не сработал CHECK status';
 EXCEPTION WHEN check_violation THEN RAISE NOTICE 'PASS: CHECK status'; END;
 BEGIN
  INSERT INTO events(venue_id,category_id,title,starts_at,ends_at)
  VALUES(1,1,NULL,'2027-07-01','2027-07-02');
  RAISE EXCEPTION 'Не сработал NOT NULL';
 EXCEPTION WHEN not_null_violation THEN RAISE NOTICE 'PASS: NOT NULL'; END;
 BEGIN
  INSERT INTO organizers(full_name,email) VALUES ('Другой организатор','anna@example.org');
  RAISE EXCEPTION 'Не сработал UNIQUE email';
 EXCEPTION WHEN unique_violation THEN RAISE NOTICE 'PASS: UNIQUE email'; END;
 BEGIN
  INSERT INTO event_details(event_id,agenda) VALUES (1,'Повторный план');
  RAISE EXCEPTION 'Не сработал 1:1';
 EXCEPTION WHEN unique_violation THEN RAISE NOTICE 'PASS: 1:1'; END;
 BEGIN
  INSERT INTO event_tags(event_id,tag_id) VALUES (1,1);
  RAISE EXCEPTION 'Не сработал N:M PK';
 EXCEPTION WHEN unique_violation THEN RAISE NOTICE 'PASS: N:M'; END;
 BEGIN
  DELETE FROM venues WHERE id=1;
  RAISE EXCEPTION 'Не сработал RESTRICT';
 EXCEPTION WHEN foreign_key_violation THEN RAISE NOTICE 'PASS: RESTRICT'; END;
END $$;
-- Проверка SET NULL и CASCADE в транзакции с откатом.
BEGIN;
DELETE FROM organizers WHERE id=1;
DO $$ BEGIN
 IF EXISTS(SELECT 1 FROM events WHERE id=1 AND organizer_id IS NOT NULL) THEN
   RAISE EXCEPTION 'SET NULL не выполнен';
 END IF;
 RAISE NOTICE 'PASS: SET NULL';
END $$;
DELETE FROM events WHERE id=1;
DO $$ BEGIN
 IF EXISTS(SELECT 1 FROM registrations WHERE event_id=1)
 OR EXISTS(SELECT 1 FROM event_tags WHERE event_id=1)
 OR EXISTS(SELECT 1 FROM event_details WHERE event_id=1) THEN
   RAISE EXCEPTION 'CASCADE не выполнен';
 END IF;
 RAISE NOTICE 'PASS: CASCADE';
END $$;
ROLLBACK;
