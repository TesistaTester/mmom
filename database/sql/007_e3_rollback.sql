-- DEVELOPMENT ONLY. Destructive. Do not run against a live game.
DROP TABLE IF EXISTS commerce.sale_components;
DROP TABLE IF EXISTS commerce.sales;
DROP TABLE IF EXISTS operations.payroll_accruals;
DROP TABLE IF EXISTS operations.staff_positions;
DROP TABLE IF EXISTS finance.cash_movements;
DROP TABLE IF EXISTS finance.cash_balances;
DROP TABLE IF EXISTS finance.entries;
DROP TABLE IF EXISTS finance.journals;
DROP TABLE IF EXISTS finance.accounts;
DROP TABLE IF EXISTS inventory.stock_movements;
DROP TABLE IF EXISTS inventory.stock_balances;
DROP TABLE IF EXISTS inventory.supplier_quotes;
DROP TABLE IF EXISTS catalog.stock_items;
DROP FUNCTION IF EXISTS finance.enforce_balanced_journal();
DROP FUNCTION IF EXISTS finance.reject_posted_mutation();
