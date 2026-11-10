DROP TRIGGER IF EXISTS miec_candidate_scores_immutable ON analytics.miec_candidate_scores;
DROP TRIGGER IF EXISTS mifiv_candidates_immutable ON analytics.mifiv_candidates;
DROP FUNCTION IF EXISTS analytics.reject_candidate_mutation();
DROP TABLE IF EXISTS analytics.miec_candidate_scores;
DROP TABLE IF EXISTS analytics.mifiv_candidates;
