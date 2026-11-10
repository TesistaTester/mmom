-- MMOM E5 V0.1: requires E1-E4 + PostgreSQL 15+. Run with migration OR this SQL, not both.
-- Loan accounting: origin Dr Cash Cr PALO principal liability; interest recognized only at future settlement.
INSERT INTO finance.accounts(code,name,kind) VALUES
 ('2200','Préstamos automáticos PALO por pagar','liability'),
 ('5300','Intereses PALO','expense'),
 ('2300','Intereses PALO por pagar','liability') ON CONFLICT (code) DO NOTHING;
CREATE TABLE finance.palo_obligations (
 id uuid PRIMARY KEY, game_id uuid NOT NULL, company_id uuid NOT NULL,
 round_number smallint NOT NULL CHECK(round_number BETWEEN 1 AND 8),
 kind varchar(20) NOT NULL CHECK(kind IN ('maintenance','payroll','approved_admin')),
 -- Only trusted server-side compulsory-obligation accrual is authorized to insert these.
 source_key varchar(160) NOT NULL,
 amount_cents bigint NOT NULL CHECK(amount_cents>0),
 paid_cents bigint NOT NULL DEFAULT 0 CHECK(paid_cents>=0 AND paid_cents<=amount_cents),
 status varchar(12) NOT NULL DEFAULT 'due' CHECK(status IN ('due','paid')),
 accrual_journal_id uuid NOT NULL REFERENCES finance.journals(id),
 registered_at timestamptz NOT NULL DEFAULT now(),
 FOREIGN KEY(game_id,company_id) REFERENCES game.companies(game_id,id),
 UNIQUE(game_id,company_id,source_key)
);
CREATE TABLE finance.palo_loans (
 id uuid PRIMARY KEY, game_id uuid NOT NULL, company_id uuid NOT NULL,
 obligation_id uuid NOT NULL UNIQUE REFERENCES finance.palo_obligations(id),
 origin_round smallint NOT NULL CHECK(origin_round BETWEEN 1 AND 8),
 principal_cents bigint NOT NULL CHECK(principal_cents>0 AND principal_cents<=2000000),
 outstanding_cents bigint NOT NULL CHECK(outstanding_cents>=0 AND outstanding_cents<=principal_cents),
 source_key varchar(130) NOT NULL,
 issued_journal_id uuid NOT NULL REFERENCES finance.journals(id),
 created_at timestamptz NOT NULL DEFAULT now(),
 FOREIGN KEY(game_id,company_id) REFERENCES game.companies(game_id,id),
 UNIQUE(game_id,company_id,source_key)
);
CREATE INDEX palo_loans_company_idx ON finance.palo_loans(game_id,company_id);
CREATE TABLE finance.palo_schedule (
 loan_id uuid NOT NULL REFERENCES finance.palo_loans(id) ON DELETE RESTRICT,
 due_round smallint NOT NULL CHECK(due_round BETWEEN 2 AND 12),
 principal_due_cents bigint NOT NULL CHECK(principal_due_cents>=0),
 interest_due_cents bigint NOT NULL CHECK(interest_due_cents>=0),
 principal_paid_cents bigint NOT NULL DEFAULT 0 CHECK(principal_paid_cents>=0 AND principal_paid_cents<=principal_due_cents),
 interest_paid_cents bigint NOT NULL DEFAULT 0 CHECK(interest_paid_cents>=0 AND interest_paid_cents<=interest_due_cents),
 PRIMARY KEY(loan_id,due_round)
);
CREATE FUNCTION finance.prevent_palo_issue_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN RAISE EXCEPTION 'PALO origination is immutable; only authorized repayment alters outstanding'; END $$;
CREATE TRIGGER palo_loan_no_delete BEFORE DELETE ON finance.palo_loans
 FOR EACH ROW EXECUTE FUNCTION finance.prevent_palo_issue_mutation();
CREATE TRIGGER palo_schedule_no_delete BEFORE DELETE ON finance.palo_schedule
 FOR EACH ROW EXECUTE FUNCTION finance.prevent_palo_issue_mutation();
-- The remaining saldo/settlement fields are restricted by server application authorization.
-- Cross-loan cap is serialized by the company's finance.cash_balances row lock in PaloOriginationService.
-- Never grant direct INSERT/UPDATE on these finance tables to untrusted clients.
