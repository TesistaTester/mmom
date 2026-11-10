-- Development rollback only. Destructive, never use on production ledger.
DROP TRIGGER IF EXISTS palo_schedule_no_delete ON finance.palo_schedule;
DROP TRIGGER IF EXISTS palo_loan_no_delete ON finance.palo_loans;
DROP FUNCTION IF EXISTS finance.prevent_palo_issue_mutation();
DROP TABLE IF EXISTS finance.palo_schedule;
DROP TABLE IF EXISTS finance.palo_loans;
DROP TABLE IF EXISTS finance.palo_obligations;
DELETE FROM finance.accounts WHERE code IN ('2200','2300','5300');
