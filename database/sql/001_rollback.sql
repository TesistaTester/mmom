-- Development rollback ONLY; drop solely E1-owned tables.
DROP TABLE IF EXISTS audit.change_events;
DROP TABLE IF EXISTS game.rounds;
DROP TABLE IF EXISTS game.assignments;
DROP TABLE IF EXISTS game.companies;
DROP TABLE IF EXISTS game.games;
DROP TABLE IF EXISTS catalog.consumer_segments;
DROP TABLE IF EXISTS catalog.discotheques;
DROP TABLE IF EXISTS identity.users;
-- Empty schemas are deliberately retained to avoid deleting later objects.
