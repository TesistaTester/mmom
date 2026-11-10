-- MMOM E1: PostgreSQL 15+ foundational schema.
-- Run only against a dedicated, empty development database.
-- Identity/auth integration, game logic, and the remaining normalized model are E2+.
CREATE SCHEMA IF NOT EXISTS identity;
CREATE SCHEMA IF NOT EXISTS catalog;
CREATE SCHEMA IF NOT EXISTS game;
CREATE SCHEMA IF NOT EXISTS market;
CREATE SCHEMA IF NOT EXISTS operations;
CREATE SCHEMA IF NOT EXISTS inventory;
CREATE SCHEMA IF NOT EXISTS commerce;
CREATE SCHEMA IF NOT EXISTS finance;
CREATE SCHEMA IF NOT EXISTS simulation;
CREATE SCHEMA IF NOT EXISTS analytics;
CREATE SCHEMA IF NOT EXISTS audit;

CREATE TABLE identity.users (
    id uuid PRIMARY KEY,
    email varchar(254) NOT NULL UNIQUE,
    display_name varchar(150) NOT NULL,
    password_hash varchar(255) NOT NULL,
    is_active boolean NOT NULL DEFAULT true,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE catalog.discotheques (
    id uuid PRIMARY KEY,
    code varchar(30) NOT NULL UNIQUE,
    name varchar(100) NOT NULL UNIQUE,
    created_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE catalog.consumer_segments (
    id uuid PRIMARY KEY,
    code varchar(40) NOT NULL UNIQUE,
    name varchar(100) NOT NULL,
    share_percent numeric(5,2) NOT NULL CHECK (share_percent >= 0 AND share_percent <= 100),
    created_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE game.games (
    id uuid PRIMARY KEY,
    name varchar(150) NOT NULL,
    status varchar(20) NOT NULL DEFAULT 'draft'
        CHECK (status IN ('draft','prepared','active','suspended','processing','completed','archived')),
    initial_model char(1) NOT NULL CHECK (initial_model IN ('A','B')),
    reference_capital numeric(14,2) NOT NULL DEFAULT 100000.00
        CHECK (reference_capital = 100000.00),
    round_count smallint NOT NULL DEFAULT 8 CHECK (round_count = 8),
    round_minutes smallint NOT NULL DEFAULT 20 CHECK (round_minutes = 20),
    config_version integer NOT NULL DEFAULT 1 CHECK (config_version > 0),
    config_frozen_at timestamptz NULL,
    created_by uuid NOT NULL REFERENCES identity.users(id),
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now(),
    CHECK (status = 'draft' OR config_frozen_at IS NOT NULL)
);

CREATE TABLE game.companies (
    id uuid PRIMARY KEY,
    game_id uuid NOT NULL REFERENCES game.games(id) ON DELETE RESTRICT,
    discotheque_id uuid NOT NULL REFERENCES catalog.discotheques(id) ON DELETE RESTRICT,
    created_at timestamptz NOT NULL DEFAULT now(),
    UNIQUE (game_id, discotheque_id),
    UNIQUE (game_id, id)
);

CREATE TABLE game.assignments (
    id uuid PRIMARY KEY,
    game_id uuid NOT NULL,
    company_id uuid NOT NULL,
    user_id uuid NOT NULL REFERENCES identity.users(id) ON DELETE RESTRICT,
    is_current boolean NOT NULL DEFAULT true,
    assigned_at timestamptz NOT NULL DEFAULT now(),
    unassigned_at timestamptz,
    FOREIGN KEY (game_id, company_id) REFERENCES game.companies(game_id,id) ON DELETE RESTRICT,
    CHECK (is_current OR unassigned_at IS NOT NULL),
    CHECK (NOT is_current OR unassigned_at IS NULL)
);
CREATE UNIQUE INDEX assignments_unique_active_company
 ON game.assignments(game_id,company_id) WHERE is_current;
CREATE UNIQUE INDEX assignments_unique_active_user
 ON game.assignments(game_id,user_id) WHERE is_current;

CREATE TABLE game.rounds (
    id uuid PRIMARY KEY,
    game_id uuid NOT NULL REFERENCES game.games(id) ON DELETE RESTRICT,
    round_number smallint NOT NULL CHECK (round_number BETWEEN 1 AND 8),
    status varchar(20) NOT NULL DEFAULT 'pending'
        CHECK (status IN ('pending','active','suspended','locked','processing','validating','closed','published')),
    starts_at timestamptz,
    deadline_at timestamptz,
    closed_at timestamptz,
    created_at timestamptz NOT NULL DEFAULT now(),
    UNIQUE (game_id,round_number),
    UNIQUE (game_id,id),
    CHECK (deadline_at IS NULL OR starts_at IS NOT NULL),
    CHECK (deadline_at IS NULL OR deadline_at > starts_at)
);

CREATE TABLE audit.change_events (
    id uuid PRIMARY KEY,
    game_id uuid NULL REFERENCES game.games(id) ON DELETE RESTRICT,
    actor_id uuid NULL REFERENCES identity.users(id) ON DELETE RESTRICT,
    event_type varchar(120) NOT NULL,
    correlation_id uuid NOT NULL,
    payload jsonb NOT NULL DEFAULT '{}'::jsonb,
    happened_at timestamptz NOT NULL DEFAULT now()
);
CREATE INDEX change_events_game_time ON audit.change_events(game_id,happened_at);
CREATE INDEX change_events_correlation ON audit.change_events(correlation_id);

INSERT INTO catalog.discotheques (id, code, name) VALUES
('00000000-0000-4000-8000-000000000101','LA_PACENA','La Paceña'),
('00000000-0000-4000-8000-000000000102','JET','Jet'),
('00000000-0000-4000-8000-000000000103','LUNA_PARK','Luna Park'),
('00000000-0000-4000-8000-000000000104','FORUM','Forum'),
('00000000-0000-4000-8000-000000000105','FABRI_K','Fabri-K'),
('00000000-0000-4000-8000-000000000106','IBIZA','Ibiza'),
('00000000-0000-4000-8000-000000000107','GREEN_PLANET','Green Planet');

INSERT INTO catalog.consumer_segments(id,code,name,share_percent) VALUES
('00000000-0000-4000-8000-000000000201','STUDENTS','Universitarios',40.00),
('00000000-0000-4000-8000-000000000202','YOUNG_WORKERS','Trabajadores jóvenes',30.00),
('00000000-0000-4000-8000-000000000203','ADULTS','Adultos',20.00),
('00000000-0000-4000-8000-000000000204','VIP','VIP',10.00);
