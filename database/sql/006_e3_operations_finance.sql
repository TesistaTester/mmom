-- MMOM E3 V0.1: requires E1 and E2 migrations; PostgreSQL 15+.
-- All amounts are INTEGER CENTAVOS (bigint), quantities are whole stock units.
-- Do NOT run this file directly after applying its Laravel migration.
CREATE TABLE catalog.stock_items (
 id uuid PRIMARY KEY, code varchar(40) NOT NULL UNIQUE, name varchar(120) NOT NULL,
 kind varchar(12) NOT NULL CHECK (kind IN ('drink','mixer')),
 active boolean NOT NULL DEFAULT true, created_at timestamptz NOT NULL DEFAULT now()
);
-- No invented initial product catalogue or supplier prices: admin must configure these.
CREATE TABLE inventory.supplier_quotes (
 id uuid PRIMARY KEY, game_id uuid NOT NULL REFERENCES game.games(id),
 round_number smallint NOT NULL CHECK (round_number BETWEEN 1 AND 8),
 item_id uuid NOT NULL REFERENCES catalog.stock_items(id),
 unit_cost_cents bigint NOT NULL CHECK(unit_cost_cents >= 0),
 approved_by uuid NOT NULL REFERENCES identity.users(id),
 approved_at timestamptz NOT NULL DEFAULT now(),
 UNIQUE(game_id,round_number,item_id),
 UNIQUE(game_id,id)
);
CREATE TABLE inventory.stock_balances (
 game_id uuid NOT NULL, company_id uuid NOT NULL, item_id uuid NOT NULL REFERENCES catalog.stock_items(id),
 quantity bigint NOT NULL DEFAULT 0 CHECK (quantity >= 0),
 total_cost_cents bigint NOT NULL DEFAULT 0 CHECK(total_cost_cents >= 0),
 version bigint NOT NULL DEFAULT 1 CHECK(version >= 1),
 PRIMARY KEY(game_id,company_id,item_id),
 FOREIGN KEY(game_id,company_id) REFERENCES game.companies(game_id,id),
 CHECK ((quantity = 0 AND total_cost_cents = 0) OR quantity > 0)
);
CREATE TABLE inventory.stock_movements (
 id uuid PRIMARY KEY, game_id uuid NOT NULL, company_id uuid NOT NULL,
 item_id uuid NOT NULL REFERENCES catalog.stock_items(id),
 round_number smallint NOT NULL CHECK(round_number BETWEEN 1 AND 8),
 movement_type varchar(12) NOT NULL CHECK(movement_type IN ('purchase','sale_usage','adjustment')),
 quantity_delta bigint NOT NULL CHECK(quantity_delta <> 0),
 value_delta_cents bigint NOT NULL,
 quantity_after bigint NOT NULL CHECK(quantity_after >= 0),
 value_after_cents bigint NOT NULL CHECK(value_after_cents >= 0),
 source_key varchar(160) NOT NULL,
 recorded_at timestamptz NOT NULL DEFAULT now(),
 FOREIGN KEY(game_id,company_id,item_id) REFERENCES inventory.stock_balances(game_id,company_id,item_id),
 UNIQUE(game_id,source_key)
);
CREATE TABLE finance.accounts (
 code varchar(24) PRIMARY KEY, name varchar(120) NOT NULL,
 kind varchar(16) NOT NULL CHECK(kind IN ('asset','liability','equity','revenue','expense'))
);
INSERT INTO finance.accounts(code,name,kind) VALUES
 ('1000','Efectivo','asset'),('1100','Inventarios','asset'),
 ('2000','Salarios por pagar','liability'),('2100','Otras cuentas por pagar','liability'),
 ('3000','Capital','equity'),('4000','Venta de entradas','revenue'),
 ('4010','Venta individual','revenue'),('4020','Combos','revenue'),('4030','Servicios VIP','revenue'),
 ('5000','Costo de ventas','expense'),('5100','Sueldos y salarios','expense'),
 ('5200','Otros gastos operativos','expense');
CREATE TABLE finance.journals (
 id uuid PRIMARY KEY, game_id uuid NOT NULL, company_id uuid NOT NULL,
 round_number smallint NULL CHECK(round_number BETWEEN 1 AND 8),
 source_key varchar(160) NOT NULL,
 description varchar(240) NOT NULL,
 posted_at timestamptz NOT NULL DEFAULT now(),
 FOREIGN KEY(game_id,company_id) REFERENCES game.companies(game_id,id),
 UNIQUE(game_id,company_id,source_key),
 UNIQUE(id,game_id,company_id)
);
CREATE TABLE finance.entries (
 id uuid PRIMARY KEY, journal_id uuid NOT NULL REFERENCES finance.journals(id) ON DELETE RESTRICT,
 account_code varchar(24) NOT NULL REFERENCES finance.accounts(code),
 debit_cents bigint NOT NULL DEFAULT 0 CHECK(debit_cents>=0),
 credit_cents bigint NOT NULL DEFAULT 0 CHECK(credit_cents>=0),
 CHECK ((debit_cents > 0 AND credit_cents = 0) OR (credit_cents > 0 AND debit_cents=0))
);
CREATE INDEX entries_journal_idx ON finance.entries(journal_id);
-- Reject journal posting with nonzero difference at transaction commit.
CREATE FUNCTION finance.enforce_balanced_journal() RETURNS trigger LANGUAGE plpgsql AS $$
DECLARE target uuid; n integer; diff numeric;
BEGIN
 target := CASE WHEN TG_OP = 'DELETE' THEN OLD.journal_id ELSE NEW.journal_id END;
 SELECT COUNT(*),COALESCE(SUM(debit_cents::numeric-credit_cents::numeric),0)
 INTO n,diff FROM finance.entries WHERE journal_id=target;
 IF n < 2 OR diff <> 0 THEN
  RAISE EXCEPTION 'Unbalanced/incomplete journal %: entries %, difference %',target,n,diff;
 END IF;
 RETURN NULL;
END; $$;
CREATE CONSTRAINT TRIGGER entries_balanced_deferred
 AFTER INSERT OR UPDATE OR DELETE ON finance.entries DEFERRABLE INITIALLY DEFERRED
 FOR EACH ROW EXECUTE FUNCTION finance.enforce_balanced_journal();
CREATE FUNCTION finance.reject_posted_mutation() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN RAISE EXCEPTION 'Posted MMOM accounting is append-only'; END; $$;
CREATE TRIGGER prevent_journal_mutation BEFORE UPDATE OR DELETE ON finance.journals
 FOR EACH ROW EXECUTE FUNCTION finance.reject_posted_mutation();
CREATE TRIGGER prevent_entry_mutation BEFORE UPDATE OR DELETE ON finance.entries
 FOR EACH ROW EXECUTE FUNCTION finance.reject_posted_mutation();
CREATE TABLE finance.cash_balances (
 game_id uuid NOT NULL, company_id uuid NOT NULL,
 cash_cents bigint NOT NULL DEFAULT 0 CHECK(cash_cents >= 0),
 reserved_cents bigint NOT NULL DEFAULT 0 CHECK(reserved_cents >= 0),
 version bigint NOT NULL DEFAULT 1,
 PRIMARY KEY(game_id,company_id),
 FOREIGN KEY(game_id,company_id) REFERENCES game.companies(game_id,id),
 CHECK(reserved_cents <= cash_cents)
);
CREATE TABLE finance.cash_movements (
 id uuid PRIMARY KEY, game_id uuid NOT NULL, company_id uuid NOT NULL,
 journal_id uuid NOT NULL REFERENCES finance.journals(id),
 delta_cents bigint NOT NULL CHECK(delta_cents<>0),
 cash_after_cents bigint NOT NULL CHECK(cash_after_cents>=0),
 created_at timestamptz NOT NULL DEFAULT now(),
 FOREIGN KEY(game_id,company_id) REFERENCES finance.cash_balances(game_id,company_id),
 UNIQUE(journal_id)
);
CREATE TABLE operations.staff_positions (
 id uuid PRIMARY KEY, game_id uuid NOT NULL, company_id uuid NOT NULL,
 slot_number integer NOT NULL CHECK(slot_number>0),
 title varchar(120) NOT NULL, wage_per_round_cents bigint NULL CHECK(wage_per_round_cents>=0),
 active boolean NOT NULL DEFAULT true,
 FOREIGN KEY(game_id,company_id) REFERENCES game.companies(game_id,id),
 UNIQUE(game_id,company_id,slot_number)
);
CREATE TABLE operations.payroll_accruals (
 id uuid PRIMARY KEY, game_id uuid NOT NULL, company_id uuid NOT NULL,
 round_number smallint NOT NULL CHECK(round_number BETWEEN 1 AND 8),
 staff_position_id uuid NOT NULL REFERENCES operations.staff_positions(id),
 amount_cents bigint NOT NULL CHECK(amount_cents>0),
 journal_id uuid NOT NULL REFERENCES finance.journals(id),
 UNIQUE(game_id,company_id,round_number,staff_position_id)
);
CREATE TABLE commerce.sales (
 id uuid PRIMARY KEY, game_id uuid NOT NULL, company_id uuid NOT NULL,
 round_number smallint NOT NULL CHECK(round_number BETWEEN 1 AND 8),
 category varchar(20) NOT NULL CHECK(category IN ('ticket','individual','combo','vip_extra')),
 gross_cents bigint NOT NULL CHECK(gross_cents>0),
 components_hash char(64) NOT NULL,
 source_key varchar(160) NOT NULL,
 journal_id uuid NOT NULL REFERENCES finance.journals(id),
 recorded_at timestamptz NOT NULL DEFAULT now(),
 FOREIGN KEY(game_id,company_id) REFERENCES game.companies(game_id,id),
 UNIQUE(game_id,company_id,source_key)
);
CREATE TABLE commerce.sale_components (
 sale_id uuid NOT NULL REFERENCES commerce.sales(id),
 item_id uuid NOT NULL REFERENCES catalog.stock_items(id),
 quantity bigint NOT NULL CHECK(quantity>0),
 cogs_cents bigint NOT NULL CHECK(cogs_cents>=0),
 PRIMARY KEY(sale_id,item_id)
);
-- Source key uniqueness, FK, nonnegative balances and append-only journals are database enforced.
-- Operational application users must also have restricted DB permissions; see security notes.

-- Business history append-only. Corrections must be posted as explicit compensating events in later phases.
CREATE TRIGGER prevent_stock_movement_mutation BEFORE UPDATE OR DELETE ON inventory.stock_movements
 FOR EACH ROW EXECUTE FUNCTION finance.reject_posted_mutation();
CREATE TRIGGER prevent_cash_movement_mutation BEFORE UPDATE OR DELETE ON finance.cash_movements
 FOR EACH ROW EXECUTE FUNCTION finance.reject_posted_mutation();
CREATE TRIGGER prevent_sale_mutation BEFORE UPDATE OR DELETE ON commerce.sales
 FOR EACH ROW EXECUTE FUNCTION finance.reject_posted_mutation();
CREATE TRIGGER prevent_sale_component_mutation BEFORE UPDATE OR DELETE ON commerce.sale_components
 FOR EACH ROW EXECUTE FUNCTION finance.reject_posted_mutation();
CREATE TRIGGER prevent_payroll_accrual_mutation BEFORE UPDATE OR DELETE ON operations.payroll_accruals
 FOR EACH ROW EXECUTE FUNCTION finance.reject_posted_mutation();
