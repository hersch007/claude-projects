# Phase 0 — Pilot Design Doc

Status: draft v1 — 2026-09-15
Depends on: `REQUIREMENTS.md` (all open questions resolved as of this date)

## 1. Goal

Prove that `audit.js`'s crawl/scoring/report-generation logic can run **headless,
server-side, triggered from a browser** — with no terminal, no interactive
prompts, no `.bat` file — before investing in a database, multi-client
dashboard, or job queue. Phase 0 is deliberately narrow: one pilot client,
one "Run Audit" button, one report view.

## 2. What `audit.js` actually is today (read in full for this doc)

It's a single 1,215-line script, structured as:

- **Top-level side effects at require-time**: reads `process.argv[2]`, loads
  `clients/<slug>.json` from disk, and sets `BASE_URL`/`MAX_PAGES`/`IGNORE` as
  module-level constants — all before any function is callable. This means
  the file can't currently be `require()`'d as a library; it only works as
  `node audit.js <slug>`.
- **Mutable module-level state**: `visited` (Set), `queue` (Array), `results`
  (Array) are declared at module scope and mutated in place by `crawl()`.
  Not reentrant — two audits can't run in the same process concurrently as-is.
- **Pure, reusable functions**: `analyzePage()`, `calcScore()`,
  `getQuickWins()`, `friendlyIssue()`, `buildReport()`, `buildTrendChart()`,
  `parseSitemap()` — these take their inputs as arguments (mostly) and need
  little to no change. `buildReport()` and a couple of others do close over
  the module-level `client`/`BASE_URL`/`PROVIDERS`, which needs to become
  explicit parameters instead.
- **Interactive prompts**: `promptProvider()` (skippable if `provider` is
  pinned in the client's JSON config) and `promptMetrics()` (manual
  fallback when no GSC data is available). There's already a clever
  piped-stdin accommodation (`getSharedPrompt()`) for driving this
  non-interactively — worth knowing it exists, but a web app shouldn't
  drive prompts through stdin at all; see §4.
- **File-based persistence**: `score-history.json`, `keyword-history.json`,
  `metrics.json` — read/written directly in the client's `output_dir` on
  disk. This is today's entire "database."
- **A bottom-of-file IIFE** (`(async () => { ... })()`) that wires all of the
  above into the actual CLI run and writes the final HTML report to disk.

## 3. Refactor needed for Phase 0

Extract the reusable parts into an importable module — **`seo-tool/lib/audit-engine.js`** —
exporting one function:

```js
async function runAudit(clientConfig, { provider, onProgress } = {}) {
  // returns { results, scoreData, html, metrics, liveGSC, keywordHistory }
}
```

Concretely:

1. Move `visited`/`queue`/`results` from module scope into local variables
   created fresh inside `runAudit()` (or a small `Crawler` class instance) —
   this alone makes it reentrant/safe to call more than once per process.
2. `crawl()`, `calcScore()`, `buildReport()`, `getQuickWins()`, etc. take
   `clientConfig` and `provider` as explicit parameters instead of reading
   module-level `client`/`BASE_URL`/`PROVIDERS`. Minimal signature changes —
   the logic inside is untouched.
3. Add an optional `onProgress(event)` callback, called from inside `crawl()`'s
   loop (where it currently does `console.log`) — this is how the web UI gets
   live "crawling page 12 of 60" status without polling log files.
4. **Drop the interactive prompts entirely from this path.** Provider
   selection becomes a required parameter the caller (the web UI) supplies
   up front — not a runtime question. The manual-metrics-entry fallback
   (`promptMetrics`) is **out of scope for Phase 0**: if a client has no
   `gsc_property` configured, the performance-metrics section of the report
   is simply omitted for this pilot, same as it already gracefully handles
   `metrics: null`. A proper manual-entry UI is Phase 1 work (ties to the
   historical-score entry form already planned in `REQUIREMENTS.md` §9).
5. The old `seo-tool/audit.js` CLI keeps working unchanged for other clients
   during the transition — it becomes a thin wrapper that calls
   `runAudit()` and handles argv/prompts/file-writing itself, so nothing
   breaks for clients not yet migrated to the web app.

## 4. Phase 0 backend

A minimal Node/Express (or Fastify — either is fine, Express is simplest
given the team's existing familiarity) server with three routes:

- `POST /api/audit/run` — starts an audit for the (single, hardcoded for
  Phase 0) pilot client. Returns immediately with a `runId`; does **not**
  block the HTTP request for the full crawl duration.
- `GET /api/audit/status/:runId` — polls progress (`{ status: 'crawling',
  pagesCrawled: 12, totalQueued: 43 }` etc., fed by the `onProgress`
  callback from §3.4).
- `GET /api/audit/report/:runId` — once complete, returns the generated
  HTML report (the exact same self-contained HTML `buildReport()` already
  produces — no changes needed to how the report itself looks).

**No real job queue (BullMQ/Redis) for Phase 0.** A single in-memory
`Map<runId, status>` on the server process is enough for "one pilot client,
one person clicking the button." Introduce a real queue in Phase 1 once
multiple clients and scheduled/concurrent crawls are actually happening —
building that infrastructure now would be solving a problem Phase 0 doesn't
have yet.

## 5. Phase 0 storage

**Keep the existing JSON-file-per-client approach** (`score-history.json`,
`keyword-history.json`, `metrics.json`) written to the pilot client's
`output_dir` on the server's disk, unchanged. The point of Phase 0 is to
prove the crawl engine runs headless server-side — not to also prove out a
database schema in the same step. Postgres replaces these files in Phase 1
(schema sketched in §7 below, so this doc still covers the DB-schema part of
the Phase 0 design brief even though Phase 0's code won't touch it yet).

## 6. Phase 0 frontend

One page. No dashboard, no client picker, no auth beyond whatever basic
protection the hosting setup already requires:

- Pilot client name/URL displayed statically.
- "Run Audit" button → calls `POST /api/audit/run`, then polls
  `GET /api/audit/status/:runId` every 2-3 seconds, showing a simple
  "Crawling page 14 of 60..." progress line.
- On completion, either redirect to `GET /api/audit/report/:runId` (it's a
  complete standalone HTML page already) or embed it in an iframe.

## 7. Phase 1 database schema (sketch, for continuity — not built in Phase 0)

Deliberately not fully normalized — jsonb columns for nested/variable-shape
data (issues, warnings, deductions) are a reasonable tradeoff at this scale
(~15-20 clients, internal tool), not a shortcut that will bite later:

```
providers            id, name, email, brand_hex, brand2_hex
clients              id, slug, name, url, brand_color, max_pages,
                      ignore_paths (jsonb), gsc_property (nullable),
                      default_provider_id (fk), created_at
audit_runs           id, client_id (fk), provider_id (fk), run_date,
                      seo_health_score, pages_crawled, status,
                      deductions (jsonb), created_at
page_results         id, audit_run_id (fk), url, title, title_len,
                      meta_desc, meta_len, h1_count, image_count,
                      images_no_alt, canonical, schema_types (jsonb),
                      word_count, issues (jsonb), warnings (jsonb)
gsc_snapshots        id, client_id (fk), period_start, period_end,
                      metrics (jsonb), top_keywords (jsonb), created_at
manual_score_entries id, client_id (fk), entry_date, score,
                      category_scores (jsonb, nullable), note, created_at
```

`manual_score_entries` is the historical-backfill mechanism decided in
`REQUIREMENTS.md` §10.4 — a client's trend chart in Phase 1 merges
`audit_runs.seo_health_score` (real crawls) with `manual_score_entries.score`
(hand-entered past scores) by date.

## 8. Pilot client selection

Recommend **GroupRB** (`grouprb.json`) itself as the Phase 0 pilot:
- Already has `gsc_property` configured, so the pilot exercises the full
  path (crawl + live GSC data), not just the no-metrics fallback.
- Lowest risk — it's the agency's own site, not a paying client's.
- An existing recent audit file (`clients/GroupRB/GroupRB-SEO-Audit-2026-08-11.html`)
  is already on disk to diff the new pipeline's output against for parity.

## 9. Definition of done for Phase 0

- [ ] `audit-engine.js` extracted, existing `audit.js` CLI still works
      unchanged for all other clients (regression check).
- [ ] Web UI triggers a live crawl of GroupRB's site end-to-end, no terminal
      involved.
- [ ] Resulting report is diffed against the existing GroupRB audit HTML —
      same score (±expected drift from content changes since the last
      crawl), same issue/warning categories, same visual design.
- [ ] Deployed to the actual hosted server (per `REQUIREMENTS.md` §10.3's
      decision), not just proven locally — Phase 0 should validate the real
      hosting target's network egress to client sites, not a developer
      machine's.

## 10. Explicitly out of scope for Phase 0

Dashboard, multi-client support, database, real auth, job queue, manual
metrics entry UI, scheduled/automatic crawls, Keyword Planner. All Phase 1+
per `REQUIREMENTS.md` §11.
