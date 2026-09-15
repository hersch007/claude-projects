# Phase 2 (Enhancements) — Design Doc

Status: draft v1 — 2026-09-15
Depends on: `REQUIREMENTS.md`, `PHASE1-DESIGN.md` (Phase 1 complete, merged to `main`)

## 1. Goal

Three enhancements on top of the Phase 1 MVP: real keyword search volume,
weekly automatic re-crawls, and email alerts when a client's score drops.

## 2. Decisions from this session

- **Keyword Planner scope: enrich existing keywords, not a standalone tool.**
  Real search volume gets added to the "top organic keywords" data already
  pulled from GSC per client — no new UI, no seed-keyword search page. A
  standalone Ubersuggest-style research tool is a later addition if wanted.
- **Re-crawls: weekly, for all clients, no per-client opt-in toggle.**
- **Alerts: email.** Needs an email-sending service wired in — none exists
  yet.

## 3. Keyword Planner integration

### 3.1 What "enrich" means concretely

Today, `getGSCMetrics()` (in `seo-tool/gsc.js`) returns `topKeywords`, each
`{ keyword, clicks, impressions, position, ctr }`, saved into
`gsc_snapshots.top_keywords` at crawl time. Phase 2 adds one more field per
keyword — `volume` (monthly search volume from Keyword Planner) — fetched
in the same crawl-time flow, for the same keyword list GSC already
surfaced. No new table needed; `top_keywords` is jsonb, so the new field
just needs to start appearing in the objects going forward.

### 3.2 Google Ads API client setup

This needs its own credential setup, parallel to what `gsc-auth.js` already
does for Search Console — a Google Ads API client requires:

- The developer token (already obtained — Explorer access, approved this
  session, under MCC 169-720-1017).
- OAuth credentials + a refresh token authorized against that MCC account
  (same Desktop-app OAuth client pattern already used for GSC — could even
  reuse the same Cloud project's OAuth client if its scope is extended to
  include `https://www.googleapis.com/auth/adwords`).
- A `login-customer-id` (the MCC's customer ID) alongside the request.

Recommend a `seo-tool/lib/keyword-planner.js` module mirroring `gsc.js`'s
shape: `getSearchVolumes(keywords[])` → `{ keyword: volume }`, using
Google's `google-ads-api` npm package (a well-maintained community wrapper)
rather than hand-rolling gRPC/REST calls against the Ads API directly.

### 3.3 Rate limits / caching

Keyword Planner's historical-metrics endpoint isn't meant for high-frequency
calls. At weekly-crawl-for-all-clients scale (~15-20 clients × ~10-25
keywords each, once a week) this is trivial relative to the Explorer tier's
2,880 ops/day, so no caching layer is needed for Phase 2 — call it fresh
each crawl, same as GSC's own data is already fetched fresh each time.

## 4. Scheduled weekly re-crawls

### 4.1 Why this needs a different mechanism than "just add node-cron"

The web service runs on Render's **free tier, which spins down after ~15
minutes of inactivity** (per `PHASE0-DESIGN.md`'s cold-start note). An
in-process scheduler (`node-cron` running inside `webapp/server/index.js`)
only fires if the process happens to be awake at the scheduled time — not
reliable for a weekly job on a sleeping free instance.

**Recommendation: use Render's native Cron Job service type** (a separate
deployable Render saw in the "Create a new Service" picker back in Phase 0
setup — "Cron Jobs: short-lived tasks that run on a periodic schedule").
This runs independently of the web service's sleep state. Concretely:

- A small script, `seo-tool/weekly-crawl.js`, that: queries all `clients`
  rows from Postgres, runs `runAudit()` for each with `storage: dbStorage`
  (reusing everything already built — no new crawl logic), and collects a
  summary (client name, old score, new score, delta) for the email step (§5).
- Deployed as a Render Cron Job pointed at this script, schedule `0 8 * * 1`
  (Monday mornings) or similar — exact time is your call.
- Needs the same `DATABASE_URL` env var as the web service.

### 4.2 Sequencing / load

Running all ~15-20 clients' crawls back-to-back in one script (rather than
parallel) keeps this simple and avoids hammering multiple client sites'
servers simultaneously — a weekly job has no urgency requiring speed.

## 5. Email notifications

### 5.1 Recommended service: Resend, not Gmail API

Given today's session already hit real friction with Google OAuth setup
(GSC, then Google Ads), I'd avoid adding a *third* Google OAuth flow for
transactional email. **Resend** (resend.com) is simpler for this:
API-key-based (no OAuth), generous free tier (3,000 emails/month, far more
than weekly summaries for 15 clients need), and a simple `RESEND_API_KEY`
env var — same pattern as `SITE_PASSWORD`/`DATABASE_URL` already in place.
Flagging as a recommendation, not a decision — say if you'd rather use
something else (SendGrid, Mailgun, or actually Gmail API if you want to
send from an existing GroupRB address specifically).

### 5.2 What the email contains

After the weekly crawl script (§4.1) finishes, send one summary email:
- Every client's old score → new score, with drops visually distinct from
  gains/no-change.
- A clear callout section for clients that dropped (see §5.3 for the
  threshold) — this is the "alert" half of the feature, not a separate
  mechanism from the weekly summary.

### 5.3 Score-drop threshold (open question, defaulting to a starting point)

Recommend flagging any drop of **3 or more points** as a callout (avoids
noise from trivial 1-point fluctuations that aren't meaningful, e.g. a
single word-count line wobbling near a threshold), with the full table
still showing every client's exact numbers regardless. Adjustable — this
is a constant, not an architectural decision, so easy to change later if
3 feels like the wrong number once you see it in practice.

## 6. Definition of done for Phase 2

- [ ] Google Ads API OAuth credentials set up (mirroring the GSC setup),
      developer token wired into a new `keyword-planner.js` module.
- [ ] Weekly crawl script deployed as a Render Cron Job, confirmed to
      actually fire on schedule and crawl all clients.
- [ ] `top_keywords` entries include real search volume after a scheduled
      run.
- [ ] Resend (or chosen alternative) account set up, `RESEND_API_KEY`
      configured, a test email successfully sent from the deployed app.
- [ ] A real weekly run produces and sends a correct summary email,
      including a drop correctly flagged when one is present.

## 7. Explicitly out of scope for Phase 2

Standalone keyword research tool (§3, deferred per this session's
decision), Slack notifications, per-client opt-out of scheduled crawls,
competitor comparison, licensed backlink/DA data. Phase 3+ per
`REQUIREMENTS.md` §11.

## 8. Open questions

1. **Email "from" address/domain** — Resend (and most transactional email
   services) need a verified sending domain for production use, not just
   an API key. Do you want to send from a `grouprb.com` address (needs DNS
   verification you'd do in your domain registrar/SiteGround), or is
   Resend's default sandbox domain acceptable to start?
2. **Score-drop threshold** — is 3 points the right starting bar (§5.3), or
   do you have a different number in mind?
3. **Recipient(s)** — just you, or does anyone else on the team want these
   summary emails?
