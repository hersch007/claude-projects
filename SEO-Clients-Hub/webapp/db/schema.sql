-- Phase 1 schema. See webapp/PHASE1-DESIGN.md §4 for the design rationale
-- (deliberately not fully normalized — jsonb for nested/variable-shape data
-- is the right tradeoff at this scale, ~15-20 clients, internal tool).
--
-- Run once against the Render Postgres database:
--   psql "$DATABASE_URL" -f webapp/db/schema.sql

CREATE TABLE IF NOT EXISTS providers (
  id          serial PRIMARY KEY,
  name        text NOT NULL UNIQUE,
  email       text NOT NULL,
  brand_hex   text NOT NULL,
  brand2_hex  text NOT NULL
);

CREATE TABLE IF NOT EXISTS clients (
  id                   serial PRIMARY KEY,
  slug                 text UNIQUE NOT NULL,
  name                 text NOT NULL,
  url                  text NOT NULL,
  brand_color          text,
  max_pages            int DEFAULT 60,
  ignore_paths         jsonb DEFAULT '[]',
  gsc_property         text,
  default_provider_id  int REFERENCES providers(id),
  archived             boolean NOT NULL DEFAULT false,
  created_at           timestamptz DEFAULT now()
);

-- clients existed before the archived/business_notes columns were added —
-- CREATE TABLE IF NOT EXISTS above won't add columns to an already-created
-- table, so add them explicitly (idempotent, safe to re-run on every boot).
ALTER TABLE clients ADD COLUMN IF NOT EXISTS archived boolean NOT NULL DEFAULT false;
ALTER TABLE clients ADD COLUMN IF NOT EXISTS business_notes text;
ALTER TABLE clients ADD COLUMN IF NOT EXISTS competitor_urls jsonb DEFAULT '[]';

CREATE TABLE IF NOT EXISTS audit_runs (
  id                serial PRIMARY KEY,
  client_id         int NOT NULL REFERENCES clients(id),
  provider_id       int REFERENCES providers(id),
  run_date          date NOT NULL,
  seo_health_score  int NOT NULL,
  pages_crawled     int,
  status            text NOT NULL DEFAULT 'done',
  deductions        jsonb,
  html_report       text,
  created_at        timestamptz DEFAULT now(),
  -- one row per client per calendar day — matches score-history.json's
  -- existing "update today's entry if re-run same day" behavior.
  UNIQUE (client_id, run_date)
);

CREATE TABLE IF NOT EXISTS page_results (
  id              serial PRIMARY KEY,
  audit_run_id    int NOT NULL REFERENCES audit_runs(id) ON DELETE CASCADE,
  url             text NOT NULL,
  title           text,
  title_len       int,
  meta_desc       text,
  meta_len        int,
  h1_count        int,
  image_count     int,
  images_no_alt   int,
  canonical       text,
  schema_types    jsonb,
  word_count      int,
  issues          jsonb,
  warnings        jsonb
);

CREATE TABLE IF NOT EXISTS gsc_snapshots (
  id              serial PRIMARY KEY,
  client_id       int NOT NULL REFERENCES clients(id),
  period_start    date,
  period_end      date NOT NULL,
  metrics         jsonb,
  top_keywords    jsonb,
  created_at      timestamptz DEFAULT now(),
  -- one snapshot per client per day, matching keyword-history.json's
  -- existing "replace same-date entry on re-run" behavior.
  UNIQUE (client_id, period_end)
);

CREATE TABLE IF NOT EXISTS manual_score_entries (
  id              serial PRIMARY KEY,
  client_id       int NOT NULL REFERENCES clients(id),
  entry_date      date NOT NULL,
  score           int NOT NULL,
  category_scores jsonb,
  note            text,
  created_at      timestamptz DEFAULT now()
);

-- referral_partners is also the "which company account is this audit
-- prepared/branded under" list that drives the Run Audit dropdown — it
-- replaced the old hardcoded PROVIDERS map in audit-engine.js and the
-- `providers` table above (kept only so historical audit_runs.provider_id
-- values still resolve; nothing new writes to it).
CREATE TABLE IF NOT EXISTS referral_partners (
  id            serial PRIMARY KEY,
  name          text NOT NULL,
  contact_name  text,
  email         text,
  phone         text,
  website       text,
  brand_hex     text,
  brand2_hex    text,
  accent_hex    text,
  created_at    timestamptz DEFAULT now()
);
CREATE UNIQUE INDEX IF NOT EXISTS referral_partners_name_idx ON referral_partners (name);
ALTER TABLE referral_partners ADD COLUMN IF NOT EXISTS website text;
ALTER TABLE referral_partners ADD COLUMN IF NOT EXISTS brand_hex text;
ALTER TABLE referral_partners ADD COLUMN IF NOT EXISTS brand2_hex text;
ALTER TABLE referral_partners ADD COLUMN IF NOT EXISTS accent_hex text;

-- Attribution only (§ referral/reseller partners who send clients our way,
-- and/or the default "runs under this company" pick for the client) — one
-- partner per client, not a login or access boundary.
ALTER TABLE clients ADD COLUMN IF NOT EXISTS referral_partner_id int REFERENCES referral_partners(id);

-- Records which referral partner/company an audit ran under — added
-- alongside (not replacing) the older provider_id column above, since that
-- one points at the old `providers` table's id space, not this one's.
ALTER TABLE audit_runs ADD COLUMN IF NOT EXISTS referral_partner_id int REFERENCES referral_partners(id);

-- Seed the 4 known provider companies (from seo-tool/lib/audit-engine.js's
-- former PROVIDERS map) as the first 4 referral partners — safe to re-run,
-- does nothing once already seeded.
INSERT INTO providers (name, email, brand_hex, brand2_hex) VALUES
  ('Start Advertising', 'RBStart@StartAdvertising.com', '#ED1C24', '#212121'),
  ('Start Performance', 'RBStart@StartPerformance.com', '#ED1C24', '#212121'),
  ('Parts of Practice',  'Richard@PartsofPractice.com', '#003366', '#f59e0b'),
  ('GroupRB',            'Richard@GroupRB.com',         '#0B0B0C', '#1D4ED8')
ON CONFLICT (name) DO NOTHING;

INSERT INTO referral_partners (name, email, brand_hex, brand2_hex) VALUES
  ('Start Advertising', 'RBStart@StartAdvertising.com', '#ED1C24', '#212121'),
  ('Start Performance', 'RBStart@StartPerformance.com', '#ED1C24', '#212121'),
  ('Parts of Practice',  'Richard@PartsofPractice.com', '#003366', '#f59e0b'),
  ('GroupRB',            'Richard@GroupRB.com',         '#0B0B0C', '#1D4ED8')
ON CONFLICT (name) DO NOTHING;
