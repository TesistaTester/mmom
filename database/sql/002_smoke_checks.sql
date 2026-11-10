-- Execute AFTER migrating in a dedicated test database.
-- These queries return counts/booleans to inspect, they do not replace an automated test suite.
SELECT count(*) AS expected_seven_clubs FROM catalog.discotheques;
SELECT count(*) AS expected_four_segments FROM catalog.consumer_segments;
SELECT sum(share_percent) AS expected_100_percent FROM catalog.consumer_segments;
SELECT schema_name FROM information_schema.schemata
 WHERE schema_name IN ('identity','catalog','game','market','operations','inventory',
    'commerce','finance','simulation','analytics','audit') ORDER BY schema_name;
SELECT conname, convalidated FROM pg_constraint
 WHERE conrelid = 'game.games'::regclass ORDER BY conname;
SELECT indexname FROM pg_indexes WHERE schemaname='game' AND tablename='assignments'
 ORDER BY indexname;
