---
name: run-seo-audit
description: Use this skill when the user says "run seo audit [client]", "run the audit for [client]", "rerun [client]'s audit", "run the crawler [for client]", or similar — to execute the local seo-tool crawler (seo-tool/audit.js) against an already-onboarded client and regenerate their client-facing HTML report with real, live-crawled data (title tags, meta descriptions, H1s, schema, image alt text, page speed). This is a LOCAL, LIVE-NETWORK operation — it directly fetches the client's real website, so it only works when Claude Code is running on a machine with normal internet access (not a sandboxed/remote session with restricted egress). This is NOT for onboarding a brand-new client (use the new-seo-client skill for that) — this is for re-crawling a client who already has a config in seo-tool/clients/.
---

# Run SEO Audit (Live Crawler)

When the user asks to run, rerun, or refresh the live crawl for an existing client:

## Step 1: Identify the client's config

Look in `seo-tool/clients/*.json` for a config matching the client name mentioned. Match against:
- the JSON filename (without `.json`) — e.g. `joewelchphoto.json` for "Joe Welch" / "joewelchphoto"
- the `name` field inside the file — e.g. `"name": "Joe Welch Photography"`

Match case-insensitively and allow partial matches (first name, business name, or domain fragment are all fine).

- If more than one config plausibly matches, list them and ask which one.
- If no config matches, this client hasn't been wired up for the crawler yet. Either:
  - tell the user to run the `new-seo-client` skill first if this is actually a brand-new client, or
  - offer to create the missing `seo-tool/clients/<slug>.json` config yourself (following the exact shape of an existing config — `name`, `url`, `brand_color`, `output_dir` pointing at `clients/[FolderName]/`, `max_pages`, `ignore_paths`) if a `CLIENT-BRIEF.md` for them already exists under `clients/`.

## Step 2: Run the crawler

From the `seo-tool/` directory, run:

```
node audit.js <slug>
```

where `<slug>` is the JSON filename without `.json` (e.g. `node audit.js joewelchphoto`).

- If `node_modules` doesn't exist in `seo-tool/` yet, run `npm install` there first.
- A matching `run-audit-<slug>.bat` file (if present) does the same thing and also auto-opens the resulting report — prefer running `node audit.js <slug>` directly instead when you want to see console output and catch errors inline, since a `.bat` run hides that from Claude Code.
- If Google Search Console credentials are configured (`gsc-auth.js` / `gsc.js` in `seo-tool/`), the crawler may also pull live performance metrics — let it run as normal; don't skip that step.

## Step 3: Report results

- Confirm the crawl completed and name the exact HTML file it (re)generated, under `clients/[FolderName]/`
- Report the new/updated SEO Health Score if the console output printed one
- Point the user to the file (or open it) so they can review it
- If the crawl fails (site unreachable, timeout, parse error, missing dependency), report the exact error — don't guess or paper over it

## Step 4: Reconcile with existing docs (ask first)

If `CLIENT-BRIEF.md` or the client's `[INITIALS]-SEO-AUDIT-*.md` contains "Pending" markers or notes about a blocked/incomplete live crawl (common right after onboarding from an environment without live network access), ask the user whether they'd like those files updated now with the real data the crawler just pulled, rather than doing it unprompted.

## Notes

- This skill's whole point is running somewhere with real internet access to the client's site — it cannot succeed in a remote/sandboxed session where outbound access to that domain is blocked. If a crawl attempt fails specifically with a connection/egress error, say so plainly rather than retrying repeatedly.
- This does not touch `CLIENT-BRIEF.md` or write a new audit `.md` — for onboarding a brand-new client end-to-end, use `new-seo-client` instead.
