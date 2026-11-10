-- Run with psql after E1 and E2 migrations, in the correct development database.
SELECT table_schema,table_name FROM information_schema.tables
WHERE (table_schema='identity' AND table_name IN ('users','user_roles'))
   OR (table_schema='game' AND table_name IN ('games','rounds','assignments','decision_drafts','confirmed_decisions','stage_transitions'))
ORDER BY table_schema,table_name;
SELECT conname FROM pg_constraint WHERE conrelid='game.confirmed_decisions'::regclass;
SELECT count(*) AS clubs_expect_7 FROM catalog.discotheques;
SELECT count(*) AS segments_expect_4, sum(share_percent) AS share_expect_100 FROM catalog.consumer_segments;
SELECT tgname FROM pg_trigger WHERE tgrelid='game.confirmed_decisions'::regclass AND NOT tgisinternal;
