-- E2 overlay; requires E1 foundation migration. Run in an isolated DEV database.
-- Identity roles are global; player authority still additionally requires active game assignment.
CREATE TABLE identity.user_roles (
  user_id uuid NOT NULL REFERENCES identity.users(id) ON DELETE RESTRICT,
  role varchar(20) NOT NULL CHECK (role IN ('admin','player','observer')),
  granted_by uuid REFERENCES identity.users(id) ON DELETE RESTRICT,
  granted_at timestamptz NOT NULL DEFAULT now(),
  PRIMARY KEY (user_id,role)
);
CREATE INDEX user_roles_role_idx ON identity.user_roles(role);

-- Columns below prepare compatibility with Fortify's 2FA secret storage, but do NOT enable 2FA alone.
ALTER TABLE identity.users ADD COLUMN two_factor_secret text NULL;
ALTER TABLE identity.users ADD COLUMN two_factor_recovery_codes text NULL;
ALTER TABLE identity.users ADD COLUMN two_factor_confirmed_at timestamptz NULL;
ALTER TABLE identity.users ADD COLUMN email_verified_at timestamptz NULL;
ALTER TABLE identity.users ADD COLUMN remember_token varchar(100) NULL;

ALTER TABLE game.games ADD COLUMN state_version bigint NOT NULL DEFAULT 1 CHECK (state_version >= 1);
ALTER TABLE game.games ADD COLUMN current_round smallint NULL CHECK (current_round BETWEEN 1 AND 8);
ALTER TABLE game.games ADD CONSTRAINT game_current_round_status_chk
 CHECK (current_round IS NULL OR status IN ('active','suspended','processing','completed','archived'));
ALTER TABLE game.rounds ADD COLUMN state_version bigint NOT NULL DEFAULT 1 CHECK (state_version >= 1);
ALTER TABLE game.rounds ADD COLUMN current_stage smallint NULL CHECK (current_stage BETWEEN 1 AND 7);
ALTER TABLE game.rounds ADD COLUMN paused_at timestamptz NULL;
ALTER TABLE game.rounds ADD COLUMN remaining_seconds integer NULL CHECK (remaining_seconds BETWEEN 0 AND 1200);
CREATE UNIQUE INDEX round_one_active_per_game ON game.rounds(game_id)
 WHERE status IN ('active','suspended','locked','processing','validating');

CREATE TABLE game.decision_drafts (
 id uuid PRIMARY KEY,
 game_id uuid NOT NULL,
 company_id uuid NOT NULL,
 round_number smallint NOT NULL CHECK(round_number BETWEEN 1 AND 8),
 stage smallint NOT NULL CHECK(stage BETWEEN 1 AND 7),
 user_id uuid NOT NULL REFERENCES identity.users(id),
 payload jsonb NOT NULL CHECK(jsonb_typeof(payload) = 'object'),
 version integer NOT NULL DEFAULT 1 CHECK(version > 0),
 updated_at timestamptz NOT NULL DEFAULT now(),
 FOREIGN KEY (game_id,company_id) REFERENCES game.companies(game_id,id),
 UNIQUE(game_id,company_id,round_number,stage)
);
CREATE TABLE game.confirmed_decisions (
 id uuid PRIMARY KEY,
 game_id uuid NOT NULL,
 company_id uuid NOT NULL,
 round_number smallint NOT NULL CHECK(round_number BETWEEN 1 AND 8),
 stage smallint NOT NULL CHECK(stage BETWEEN 1 AND 7),
 user_id uuid NOT NULL REFERENCES identity.users(id),
 payload jsonb NOT NULL CHECK(jsonb_typeof(payload) = 'object'),
 idempotency_key varchar(120) NOT NULL,
 payload_hash char(64) NOT NULL,
 confirmed_at timestamptz NOT NULL DEFAULT now(),
 FOREIGN KEY(game_id,company_id) REFERENCES game.companies(game_id,id),
 UNIQUE(game_id,company_id,round_number,stage),
 UNIQUE(game_id,idempotency_key)
);
CREATE INDEX decisions_round_idx ON game.confirmed_decisions(game_id,round_number);
CREATE TABLE game.stage_transitions (
 id uuid PRIMARY KEY,
 game_id uuid NOT NULL REFERENCES game.games(id),
 round_number smallint NOT NULL CHECK(round_number BETWEEN 1 AND 8),
 from_stage smallint NULL CHECK(from_stage BETWEEN 1 AND 7),
 to_stage smallint NOT NULL CHECK(to_stage BETWEEN 1 AND 7),
 actor_id uuid NOT NULL REFERENCES identity.users(id),
 reason varchar(120) NOT NULL,
 happened_at timestamptz NOT NULL DEFAULT now()
);
CREATE INDEX stage_transitions_round_idx ON game.stage_transitions(game_id,round_number,happened_at);
-- Deny UPDATE/DELETE to confirmed decisions at database level, even if ORM code attempts it.
CREATE FUNCTION game.reject_confirmed_decision_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN RAISE EXCEPTION 'Confirmed MMOM decisions are immutable'; END; $$;
CREATE TRIGGER protect_confirmed_decisions BEFORE UPDATE OR DELETE ON game.confirmed_decisions
 FOR EACH ROW EXECUTE FUNCTION game.reject_confirmed_decision_mutation();
