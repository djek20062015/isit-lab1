-- Запуск после 01_schema.sql. Даты намеренно фиксированы для воспроизводимости.
INSERT INTO organizers (full_name,email,phone) VALUES
 ('Анна Морозова','anna@example.org','+79990000001'),
 ('Илья Смирнов','ilya@example.org',NULL),
 ('Мария Волкова','maria@example.org','+79990000003');
INSERT INTO venues (name,address,capacity) VALUES
 ('Конгресс-холл','Москва, Центральная улица, 10',350),
 ('Арт-пространство','Москва, Набережная, 5',120),
 ('Технопарк','Зеленоград, Проезд 4806, 5',500),
 ('Лекторий','Москва, Садовая улица, 8',80);
INSERT INTO categories (name,description) VALUES
 ('Конференции','Профессиональные конференции'),
 ('Образование','Лекции и практикумы'),
 ('Культура','Выставки и культурные события');
INSERT INTO tags (name) VALUES ('IT'),('Дизайн'),('Наука'),('Нетворкинг');
INSERT INTO events (venue_id,category_id,organizer_id,title,description,status,starts_at,ends_at,ticket_price) VALUES
 (1,1,1,'Форум цифровых технологий','Доклады об информационных системах','planned','2027-03-10 10:00+03','2027-03-10 18:00+03',1500),
 (2,3,2,'Выставка цифрового искусства',NULL,'active','2027-03-12 12:00+03','2027-03-12 20:00+03',500),
 (3,2,1,'Практикум по веб-разработке','Создание веб-приложений','planned','2027-04-02 11:00+03','2027-04-02 16:00+03',0),
 (4,2,NULL,'Открытая лекция по базам данных',NULL,'completed','2026-10-14 14:00+03','2026-10-14 16:00+03',0),
 (1,1,3,'Конференция по дизайну','Интерфейсы и визуальные системы','planned','2027-05-08 09:00+03','2027-05-08 17:00+03',2500),
 (2,3,2,'Встреча творческого сообщества','Обмен опытом','cancelled','2027-06-01 18:00+03','2027-06-01 20:00+03',300),
 (3,1,3,'Технологический фестиваль','Выставка проектов','active','2027-06-20 10:00+03','2027-06-20 19:00+03',900);
INSERT INTO event_details (event_id,agenda,contact_email) VALUES
 (1,'Регистрация, доклады, обсуждение','events@example.org'),
 (3,'Теория, практика, разбор результатов','workshop@example.org'),
 (5,'Пленарная сессия и мастер-классы','design@example.org');
INSERT INTO registrations (event_id,attendee_name,attendee_email,status) VALUES
 (1,'Павел Иванов','pavel@example.org','confirmed'),
 (1,'Елена Соколова','elena@example.org','confirmed'),
 (2,'Никита Петров','nikita@example.org','confirmed'),
 (3,'Павел Иванов','pavel@example.org','cancelled'),
 (5,'Ольга Белова','olga@example.org','confirmed');
INSERT INTO event_tags(event_id,tag_id) VALUES
 (1,1),(1,3),(1,4),(2,2),(3,1),(3,3),(5,2),(5,4),(7,1),(7,4);
