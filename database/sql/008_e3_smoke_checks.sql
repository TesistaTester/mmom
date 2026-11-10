-- Run in psql AFTER E1, E2, E3 migrations. Read-only verification.
SELECT schemaname,tablename FROM pg_tables
 WHERE schemaname IN ('finance','inventory','commerce','operations') ORDER BY 1,2;
SELECT code,kind FROM finance.accounts ORDER BY code;
SELECT COUNT(*) AS clubs FROM catalog.discotheques;
SELECT COUNT(*) AS segments FROM catalog.consumer_segments;
SELECT conname,contype FROM pg_constraint WHERE connamespace IN
 (SELECT oid FROM pg_namespace WHERE nspname IN ('finance','inventory','commerce','operations')) ORDER BY conname;
SELECT event_object_schema,event_object_table,trigger_name FROM information_schema.triggers
 WHERE event_object_schema IN ('finance','inventory','commerce','operations') ORDER BY 1,2,3;
-- Actual atomic cash movements, journal balancing, purchasing and sales need integrated fixtures.
