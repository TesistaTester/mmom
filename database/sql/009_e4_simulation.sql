-- E4 V0.1: reproducible, NON-OFFICIAL simulation previews. Requires E1-E3.
-- Server MUST validate payload using MarketRoundEngine before insertion.
CREATE TABLE simulation.engine_runs (
 id uuid PRIMARY KEY,
 game_id uuid NOT NULL REFERENCES game.games(id) ON DELETE RESTRICT,
 round_number smallint NOT NULL CHECK (round_number BETWEEN 1 AND 8),
 engine_version varchar(60) NOT NULL CHECK (engine_version='MMOM-E4-V0.1'),
 config_hash char(64) NOT NULL CHECK(config_hash ~ '^[a-f0-9]{64}$'),
 seed_hash char(64) NOT NULL CHECK(seed_hash ~ '^[a-f0-9]{64}$'),
 input_data jsonb NOT NULL CHECK(jsonb_typeof(input_data)='object'),
 output_data jsonb NOT NULL CHECK(jsonb_typeof(output_data)='object'),
 status varchar(20) NOT NULL DEFAULT 'preview' CHECK(status='preview'),
 created_at timestamptz NOT NULL DEFAULT now(),
 UNIQUE(game_id,round_number,engine_version,config_hash,seed_hash)
);
CREATE INDEX engine_runs_game_round ON simulation.engine_runs(game_id,round_number,created_at);
CREATE FUNCTION simulation.reject_run_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
 BEGIN RAISE EXCEPTION 'MMOM E4 preview run is immutable'; END; $$;
CREATE TRIGGER engine_runs_immutable BEFORE UPDATE OR DELETE ON simulation.engine_runs
 FOR EACH ROW EXECUTE FUNCTION simulation.reject_run_mutation();
