# Phase 1 (MVP) — Design Doc

Status: draft v1 — 2026-09-15
Depends on: `REQUIREMENTS.md`, `PHASE0-DESIGN.md` (Phase 0 complete, deployed
to https://seo-platform-phase0.onrender.com)

## 1. Goal

Turn the single-client Phase 0 pilot into a real MVP covering the whole
client roster: a dashboard, persistent database-backed history, all 15
current clients migrated in, and report export matching today's quality —
per `REQUIREMENTS.md` §11's Phase 1 scope.

## 2. Decisions carried in from this session

- **Auth: shared password.** One password for the whole team via a simple
  login screen — no per-user accounts for Phase 1. This is now urgent, not
  optional: the Phase 0 app is live on a public URL with zero auth today.
- **Database: Render Postgres.** Same platform as the already-deployed app.
  Confirm current free-tier terms when provisioning it — Render's free
  database tiers have historically been time-limited (e.g. expiring after a
  set period), unlike the free web service tier, so don't assume "free"
  means "free forever" without checking at signup.
- **Migration: all 15 clients at once**, not a staged rollout.

## 3. Auth design

Simplest approach that's still real security: Express middleware that gates
every route behind a password check, backed by a signed session cookie so
you don't retype the password on every request.

- `SITE_PASSWORD` set as a Render environment variable (never committed).
- A login route (`POST /api/login`) checks the submitted password against
  it, and on success sets a signed, httpOnly cookie.
- All other routes (API and static frontend) check for that cookie via
  middleware; missing/invalid → redirect to a login page.
- Use a small library (`cookie-session` or `express-session`) rather than
  hand-rolling cookie signing — this is exactly the kind of thing worth not
  reinventing.

This is a deliberately modest bar for Phase 1 — it stops a random person
who finds the URL from seeing client data, which is the actual near-term
risk. Per-user accounts (if you want to track who ran what, or give
different people different access) is a reasonable Phase 2 upgrade, not a
Phase 1 requirement.

## 4. Database schema

Refines the sketch from `REQUIREMENTS.md` §7 / `PHASE0-DESIGN.md` §7 into
real Phase 1 tables. Still deliberately not fully normalized — jsonb for
nested/variable-shape data (issues, warnings, deductions, top keywords) is
the right tradeoff at this scale (~15-20 clients, internal tool), not a
shortcut that bites later.

```sql
providers (
  id            serial primary key,
  name          text not null,          -- 'Start Advertising', etc.
  email         text not null,
  brand_hex     text not null,
  brand2_hex    text not null
)

clients (
  id                serial primary key,
  slug              text unique not null,   -- matches seo-tool/clients/<slug>.json today
  name              text not null,
  url               text not null,
  brand_color       text,
  max_pages         int default 60,
  ignore_paths      jsonb default '[]',
  gsc_property      text,                   -- nullable — only 6 of 15 clients have this
  default_provider_id  int references providers(id),
  created_at        timestamptz default now()
)

audit_runs (
  id                serial primary key,
  client_id         int references clients(id),
  provider_id       int references providers(id),
  run_date          date not null,
  seo_health_score  int not null,
  pages_crawled     int not null,
  status            text not null,          -- 'done' | 'error'
  deductions        jsonb,                  -- [{pts, label}, ...]
  html_report       text,                   -- the full generated report
  created_at        timestamptz default now()
)

page_results (
  id              serial primary key,
  audit_run_id    int references audit_runs(id),
  url             text not null,
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
)

gsc_snapshots (
  id              serial primary key,
  client_id       int references clients(id),
  period_start    date,
  period_end      date,
  metrics         jsonb,      -- the metrics[] array (clicks, impressions, etc.)
  top_keywords    jsonb,
  created_at      timestamptz default now()
)

manual_score_entries (
  id              serial primary key,
  client_id       int references clients(id),
  entry_date      date not null,
  score           int not null,
  category_scores jsonb,      -- optional per-category breakdown
  note            text,
  created_at      timestamptz default now()
)
```

A client's trend chart merges `audit_runs.seo_health_score` (real crawls)
with `manual_score_entries.score` (hand-entered past scores) by date — this
is the historical-backfill mechanism decided in `REQUIREMENTS.md` §10.4.

## 5. Making the engine DB-aware without breaking the CLI

`audit-engine.js`'s `runAudit()` currently calls file-based helpers directly
(`loadHistory`, `saveHistory`, `loadKeywordHistory`, `saveKeywordHistory`,
`loadMetrics`) — hardcoded to read/write JSON files in `outDir`. The CLI
(`audit.js`) needs to keep working exactly as it does today (other clients
aren't on the web app yet), so we don't want to just rip these out.

Add an optional `storage` parameter to `runAudit()`, matching the same
pattern already used for `onProgress`/`onNeedMetrics`:

```js
async function runAudit(client, { provider, onProgress, onNeedMetrics, storage } = {}) {
  const store = storage || fileStorage; // fileStorage wraps today's load/save* functions
  const history = store.loadHistory(client);
  ...
  store.saveHistory(client, history);
  ...
}
```

- `fileStorage` (default): today's exact file-based implementation, used by
  the CLI and unchanged in behavior.
- `dbStorage` (new, Phase 1): same five methods, backed by the Postgres
  tables above instead of files. The webapp server passes this in.

This is a small, surgical change — the crawl/scoring/report logic in the
engine (the bulk of the file) doesn't change at all, only the five
history/metrics functions get an injectable seam.

## 6. Backend API changes

Phase 0's server hardcoded one client and had no client-scoping in its
routes. Phase 1 needs:

- `POST /api/login`, session middleware on everything else (§3).
- `GET /api/clients` — dashboard list: name, latest score, trend sparkline
  data, last-crawled date, for all clients.
- `GET /api/clients/:slug` — full detail: history, latest report summary.
- `POST /api/clients/:slug/audit/run` — now takes a `provider` in the
  request body (a dropdown in the UI) instead of Phase 0's hardcoded `'4'`.
- `GET /api/clients/:slug/audit/status/:runId`, `GET .../report/:runId` —
  same shape as Phase 0, just client-scoped.
- `POST /api/clients/:slug/manual-score` — the historical backfill entry
  point (date + score, optional category breakdown) from §4's
  `manual_score_entries` table.

The in-memory run-status `Map` from Phase 0 doesn't need to change — it
already supports multiple concurrent runs (each gets its own `runId`), so
multi-client doesn't require a real job queue any more than Phase 0 did.
Revisit only if scheduled/automatic crawls (Phase 2) start needing to run
many clients unattended.

## 7. Frontend changes

- **Login page** — password field, nothing fancier.
- **Dashboard** — replaces Phase 0's single-client card with a list/grid of
  all clients: name, latest score (color-coded like the report), trend
  sparkline, last-crawled date, "Run Audit" per row.
- **Client detail page** — full history chart (real runs + manual entries
  merged), latest report link, "Run Audit" with a provider dropdown, and the
  manual-score-entry form.

## 8. Report export (Word/PDF)

`REQUIREMENTS.md`'s MVP feature list called for both the **Master**
(technical) and **Prospect** (sales) Word reports, matching what the
`new-seo-client` skill already generates. Worth flagging a real gap before
committing to both in Phase 1:

- The **Master report** is a direct reformatting of audit data (sections,
  tables, scores) — this maps cleanly onto data the app already has after a
  crawl. Straightforward to generate from the app with the `docx` package,
  same styling approach the skill already uses.
- The **Prospect report** is not just reformatting — its narrative sections
  ("Where You're Losing Clients Right Now," "The Opportunity Ahead") are
  *written* by Claude in the skill today, not derived mechanically from
  audit data. Reproducing that automatically from the app means integrating
  an LLM API call (cost, an API key to manage, and a new "who reviews
  AI-written client-facing copy before it goes out" question) — real new
  scope, not something that falls out of what's already built.

**Recommendation:** Master report export is in Phase 1. Defer the Prospect
report to Phase 2 (or keep generating it via the existing skill manually,
same as today, until there's appetite to add LLM integration to the app
itself). Flagging this rather than silently narrowing scope — say if you'd
rather tackle the LLM integration now instead.

## 9. Migration

**Built as `POST /api/admin/migrate`** (a protected route triggered by a
"Sync Clients from Config" button on the dashboard) rather than a standalone
script — simpler for Richard to run from the browser than installing `psql`
or using a local terminal, and safe to re-run any time a client's JSON
config changes (upserts by slug). On each run:

1. Providers are already seeded by `schema.sql` on boot (§3/§5's
   `ensureSchema()`), not by this endpoint.
2. For each file in `seo-tool/clients/*.json` — upsert a `clients` row.
3. For each client with a `score-history.json` / `keyword-history.json` on
   disk (from prior CLI runs), import those into `audit_runs`/
   `gsc_snapshots` — structured data, not prose, so distinct from the
   `.md`/`.docx` narrative-backfill question already decided against in
   `REQUIREMENTS.md` §10.4.
4. Existing `.md`/`.docx`/`.html` report files stay on disk as archive only,
   per the earlier decision — not imported.

**Confirmed 2026-09-15** against the real Render Postgres database: 15
clients synced, 26 history rows imported, 7 keyword snapshots, 0 errors.

## 10. Definition of done for Phase 1

- [x] Login page gates the whole app. **Confirmed** — Phase 0's
      unauthenticated Render URL is no longer reachable without the shared
      password.
- [x] All 15 clients visible on the dashboard with correct latest scores.
      **Confirmed** via the live migration run above.
- [x] Running an audit from the dashboard works. **Confirmed** — two live
      runs against GroupRB (which has GSC configured) completed correctly,
      including correct provider-branded colors on the generated report.
      Still worth trying once on a client *without* `gsc_property` set to
      confirm the graceful no-metrics fallback also holds on the live app,
      not just in local testing.
- [x] Trend chart shows real historical data, not just new-run points.
      **Confirmed** — GroupRB's chart included the imported Aug 11 score
      alongside today's live runs.
- [x] Manual score entry form works and merges correctly with real run
      data. **Confirmed** in local testing against a real Postgres instance
      before deploying (not yet exercised on the live Render app itself —
      worth a quick live check).
- [ ] Master report Word export produces output matching the quality of
      what the `new-seo-client` skill generates today. **Not yet built.**
- [x] Deployed to Render with the database wired up and auth active.
      **Confirmed.**

## 11. Explicitly out of scope for Phase 1

Prospect report export (§8), per-user accounts, scheduled/automatic
crawls, Keyword Planner integration, competitor comparison. Phase 2+ per
`REQUIREMENTS.md` §11.
