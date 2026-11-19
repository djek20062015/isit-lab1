-- EventManager. PostgreSQL 16+. Повторное выполнение пересоздаёт схему.
DROP TABLE IF EXISTS notifications, report_requests, job_outbox;
DROP TABLE IF EXISTS event_tags, registrations, event_details, events, tags, categories, venues, organizers CASCADE;

CREATE TABLE organizers (
    id SERIAL PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL CHECK (char_length(trim(full_name)) >= 3),
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(30),
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE TABLE venues (
    id SERIAL PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE CHECK (char_length(trim(name)) >= 3),
    address TEXT NOT NULL,
    capacity INTEGER NOT NULL CHECK (capacity > 0)
);
CREATE TABLE categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT
);
CREATE TABLE tags (
    id SERIAL PRIMARY KEY,
    name VARCHAR(60) NOT NULL UNIQUE
);
CREATE TABLE events (
    id SERIAL PRIMARY KEY,
    venue_id INTEGER NOT NULL REFERENCES venues(id) ON DELETE RESTRICT,
    category_id INTEGER NOT NULL REFERENCES categories(id) ON DELETE RESTRICT,
    organizer_id INTEGER REFERENCES organizers(id) ON DELETE SET NULL,
    title VARCHAR(200) NOT NULL CHECK (char_length(trim(title)) >= 3),
    description TEXT,
    status VARCHAR(20) NOT NULL DEFAULT 'planned'
        CHECK (status IN ('planned', 'active', 'completed', 'cancelled')),
    starts_at TIMESTAMPTZ NOT NULL,
    ends_at TIMESTAMPTZ NOT NULL,
    ticket_price NUMERIC(12,2) NOT NULL DEFAULT 0 CHECK (ticket_price >= 0),
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT chk_event_dates CHECK (ends_at > starts_at)
);
-- 1:1: идентификатор мероприятия одновременно является первичным и внешним ключом.
CREATE TABLE event_details (
    event_id INTEGER PRIMARY KEY REFERENCES events(id) ON DELETE CASCADE,
    agenda TEXT NOT NULL,
    contact_email VARCHAR(255),
    online_url TEXT
);
-- 1:N: у мероприятия может быть несколько регистраций.
CREATE TABLE registrations (
    id SERIAL PRIMARY KEY,
    event_id INTEGER NOT NULL REFERENCES events(id) ON DELETE CASCADE,
    attendee_name VARCHAR(120) NOT NULL,
    attendee_email VARCHAR(255) NOT NULL,
    registered_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    status VARCHAR(20) NOT NULL DEFAULT 'confirmed'
        CHECK (status IN ('confirmed','cancelled')),
    CONSTRAINT uq_registration UNIQUE (event_id, attendee_email)
);
-- N:M: одно мероприятие имеет несколько тегов; тег относится к нескольким мероприятиям.
CREATE TABLE event_tags (
    event_id INTEGER NOT NULL REFERENCES events(id) ON DELETE CASCADE,
    tag_id INTEGER NOT NULL REFERENCES tags(id) ON DELETE CASCADE,
    PRIMARY KEY (event_id, tag_id)
);

-- Учетные записи приложения и токены авторизации.
DROP TABLE IF EXISTS auth_tokens, users CASCADE;
CREATE TABLE users (
 id SERIAL PRIMARY KEY,
 name VARCHAR(120) NOT NULL CHECK (length(trim(name)) >= 3),
 email VARCHAR(255) NOT NULL UNIQUE,
 password_hash TEXT NOT NULL,
 role VARCHAR(20) NOT NULL CHECK (role IN ('manager','editor','viewer'))
);
CREATE TABLE auth_tokens (
 id BIGSERIAL PRIMARY KEY,
 user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
 token_hash CHAR(64) NOT NULL UNIQUE,
 expires_at TIMESTAMPTZ NOT NULL,
 created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE INDEX idx_auth_tokens_expiry ON auth_tokens(expires_at);
CREATE INDEX idx_events_status_date ON events(status,starts_at);
CREATE INDEX idx_events_venue ON events(venue_id);

-- Версии обновляются вместе с данными, в том числе при остановленном Redis.
DROP TABLE IF EXISTS cache_revisions;
CREATE TABLE cache_revisions (
    scope TEXT PRIMARY KEY,
    revision UUID NOT NULL DEFAULT gen_random_uuid()
);
INSERT INTO cache_revisions (scope) VALUES ('events'), ('venues'), ('categories'), ('report');

CREATE OR REPLACE FUNCTION revise_cache() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
    UPDATE cache_revisions
    SET revision = gen_random_uuid()
    WHERE scope = TG_TABLE_NAME;
    IF TG_TABLE_NAME IN ('events', 'venues') THEN
        UPDATE cache_revisions SET revision = gen_random_uuid() WHERE scope = 'report';
    END IF;
    RETURN NULL;
END;
$$;

CREATE TRIGGER revise_events AFTER INSERT OR UPDATE OR DELETE OR TRUNCATE ON events
    FOR EACH STATEMENT EXECUTE FUNCTION revise_cache();
CREATE TRIGGER revise_venues AFTER INSERT OR UPDATE OR DELETE OR TRUNCATE ON venues
    FOR EACH STATEMENT EXECUTE FUNCTION revise_cache();
CREATE TRIGGER revise_categories AFTER INSERT OR UPDATE OR DELETE OR TRUNCATE ON categories
    FOR EACH STATEMENT EXECUTE FUNCTION revise_cache();

CREATE TABLE notifications (
    job_id UUID NOT NULL,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    message TEXT NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    PRIMARY KEY (job_id, user_id)
);
CREATE TABLE report_requests (
    id BIGSERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    status TEXT NOT NULL DEFAULT 'queued' CHECK (status IN ('queued','processing','done','failed')),
    rows_count INTEGER,
    error TEXT
);
CREATE TABLE job_outbox (
    id UUID PRIMARY KEY,
    queue TEXT NOT NULL,
    body JSONB NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE INDEX job_outbox_ready ON job_outbox(queue, created_at, id);
