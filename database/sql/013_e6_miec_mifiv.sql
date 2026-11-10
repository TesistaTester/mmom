-- E6 draft staging only. No official MIFIV closure or leaderboard publication in this release.
-- Requires E1-E5. analytics schema created by E1.
CREATE TABLE analytics.mifiv_candidates (
 id uuid PRIMARY KEY,
 game_id uuid NOT NULL REFERENCES game.games(id) ON DELETE RESTRICT,
 round_number smallint NOT NULL CHECK(round_number BETWEEN 1 AND 8),
 metric_payload jsonb NOT NULL CHECK(jsonb_typeof(metric_payload)='object'),
 evidence_payload jsonb NOT NULL CHECK(jsonb_typeof(evidence_payload)='object'),
 validation_report jsonb NOT NULL CHECK(jsonb_typeof(validation_report)='object'),
 status varchar(20) NOT NULL DEFAULT 'candidate' CHECK(status='candidate'),
 created_at timestamptz NOT NULL DEFAULT now(),
 UNIQUE(game_id,round_number), UNIQUE(id,game_id)
);
CREATE TABLE analytics.miec_candidate_scores (
 id uuid PRIMARY KEY,
 candidate_id uuid NOT NULL,
 game_id uuid NOT NULL,
 company_id uuid NOT NULL,
 rank smallint NOT NULL CHECK(rank BETWEEN 1 AND 7),
 weighted_score numeric(12,8) NOT NULL CHECK(weighted_score BETWEEN 0 AND 100),
 dimension_scores jsonb NOT NULL CHECK(jsonb_typeof(dimension_scores)='object'),
 verified_metrics jsonb NOT NULL CHECK(jsonb_typeof(verified_metrics)='object'),
 FOREIGN KEY(candidate_id,game_id) REFERENCES analytics.mifiv_candidates(id,game_id) ON DELETE RESTRICT,
 FOREIGN KEY(game_id,company_id) REFERENCES game.companies(game_id,id) ON DELETE RESTRICT,
 UNIQUE(candidate_id,company_id)
);
CREATE INDEX miec_candidate_game_idx ON analytics.mifiv_candidates(game_id,round_number);
CREATE FUNCTION analytics.reject_candidate_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN RAISE EXCEPTION 'MMOM E6 candidates are append-only'; END; $$;
CREATE TRIGGER mifiv_candidates_immutable BEFORE UPDATE OR DELETE ON analytics.mifiv_candidates
 FOR EACH ROW EXECUTE FUNCTION analytics.reject_candidate_mutation();
CREATE TRIGGER miec_candidate_scores_immutable BEFORE UPDATE OR DELETE ON analytics.miec_candidate_scores
 FOR EACH ROW EXECUTE FUNCTION analytics.reject_candidate_mutation();
-- No view exposes candidate scores to players. No game.rounds status updates here.
