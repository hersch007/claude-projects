---
name: run-seo-audit
description: Use this skill whenever the user says "RUN SEO AUDIT", "run seo audit on [directory/client]", "run the audit for [client]", "rerun [client]'s audit", or "run the crawler" — with or without naming a specific client or directory. This drives the whole live-crawl pipeline end to end: resolving which client is meant, wiring up seo-tool/clients/<slug>.json if it doesn't exist yet, installing dependencies if needed, running the crawler, and regenerating the client-facing HTML report. The user should never have to run node/npm themselves, know a slug, or find a .bat file — this skill does all of that. This is a LOCAL, LIVE-NETWORK operation: it directly fetches the client's real website, so it only works when Claude Code is running on a machine with normal internet access (not a sandboxed/remote session with restricted egress).
---

# Run SEO Audit (Live Crawler, Zero-Setup)

The user should be able to say "RUN SEO AUDIT" — optionally pointing at a directory, client name, or URL — and get a finished, regenerated report with no manual steps of their own. Do all of the following automatically; never ask the user to run a command themselves.

## Step 1: Resolve which client is meant

Figure out the target from whatever the user gave you, in this order:

1. **An explicit directory or path** (e.g. "run seo audit on clients/JoeWelchPhotography", or they're `cd`'d into a client folder and just say "RUN SEO AUDIT") → treat that folder under `clients/` as the target.
2. **A client name or business name** they typed → match it against folder names under `clients/`, `CLIENT-BRIEF.md` contents (the `# CLIENT:` line), and `seo-tool/clients/*.json` `name` fields. Fuzzy/partial matches are fine (first name, initials, domain fragment).
3. **No name or path at all** → if the current working directory is itself inside `clients/<Something>/`, use that. Otherwise list the folders under `clients/` and ask which one.
4. **A bare URL** they gave you that doesn't match any existing client → this is effectively a new client. Look for a `clients/<FolderName>/` whose `CLIENT-BRIEF.md` names that site; if genuinely nothing exists yet, tell the user this looks like a brand-new client and offer to run the `new-seo-client` skill first (which creates the brief/audit docs), then continue with this skill's Step 2 once that exists — don't silently invent a client folder with no brief.

Do not ask the user to disambiguate anything you can figure out yourself from folder names, briefs, or configs — only ask if it's genuinely ambiguous (e.g. two clients with very similar names) or truly unresolvable.

## Step 2: Make sure the crawler config exists — create it if not

Look for `seo-tool/clients/<slug>.json` where `<slug>` is a filesystem-safe lowercase version of the client (match against existing configs' `name` field first, per Step 1).

**If it already exists**, use it as-is.

**If it doesn't exist yet** (client was onboarded via `new-seo-client` but never wired up for the crawler, or this is the first live run), create it yourself — do not ask the user to do this:
- `name`: the business name from `CLIENT-BRIEF.md`
- `url`: the client's website from `CLIENT-BRIEF.md`
- `brand_color`: reuse a color already associated with this client if one is visible anywhere (e.g. in an existing HTML report's CSS); otherwise pick a reasonable default distinct from other clients' colors
- `output_dir`: the absolute path to `clients/<FolderName>` — match the exact path convention already used by sibling configs in `seo-tool/clients/*.json` (they point at this machine's real local path, e.g. `C:/Users/richa/Documents/Claude Projects/SEO-Clients-Hub/clients/<FolderName>`)
- `max_pages`: `60` (matches existing configs)
- `ignore_paths`: `["/wp-admin/", "/wp-content/uploads/", "?", "#"]` (matches existing configs)

Pick a `<slug>` filename consistent with existing ones (lowercase, no spaces — e.g. `joewelchphoto.json`, `lawnace.json`).

## Step 3: Make sure dependencies are installed

Check whether `seo-tool/node_modules/` exists. If not, run `npm install` inside `seo-tool/` first, silently, before running the crawler.

## Step 4: Determine which provider company this audit runs under

The crawler stamps every report with a "provider" — the agency company issuing it (name, contact email, and letterhead colors on the cover/report). There are four:

| # | Company | Colors | Email |
|---|---|---|---|
| 1 | Start Advertising | Red / Black / Grey | RBStart@StartAdvertising.com |
| 2 | Start Performance | Red / Black / Grey | RBStart@StartPerformance.com |
| 3 | Parts of Practice | Red / Teal-Aqua | Richard@PartsofPractice.com |
| 4 | GroupRB | Black / Blue | Richard@GroupRB.com |

This is a real business/billing decision — **always ask the user in chat** which of the four this audit should be prepared under before running the crawler. Never infer it yourself, even if `CLIENT-BRIEF.md`'s "Prepared by" field appears to name one of these companies — that field may be stale or wrong, and getting the letterhead/billing company wrong on a client-facing report is exactly the kind of mistake that needs a human check, not a guess.

Exception: if `seo-tool/clients/<slug>.json` already has a `"provider"` field (a number 1-4 or one of the exact names above) — meaning the user already pinned it for this client on a prior run — use it without asking again, but say which one you're using.

Once you know the answer, you'll feed it to the crawler in Step 5 — don't write it into the client's JSON config yourself unless the user says this client should always use the same provider going forward.

## Step 5: Run the crawler yourself

From the `seo-tool/` directory, run:

```
node audit.js <slug>
```

Run this directly (not by shelling out to a `.bat` file) so you see the real console output and can catch and explain any error immediately, rather than the user having to go find and interpret it themselves.

The crawler will first prompt `Which company is this audit being prepared under? ... Enter 1-4:` (skipped if `provider` is pinned in the config per Step 4) — answer it with the number for the provider you determined in Step 4. It will later prompt for performance metrics (`Do you have updated metrics to enter? (y/N)`) — press Enter/answer blank to keep existing data unless the user has new metrics to give you.

If Google Search Console credentials are already configured (`gsc-auth.js` / `gsc.js` in `seo-tool/`), let the crawler pull live performance metrics as normal — don't skip or disable that.

## Step 6: Report results — don't make the user go look

- State plainly that the audit ran, name the exact regenerated HTML file and its path under `clients/<FolderName>/`
- Report the SEO Health Score the crawler printed
- Name which provider company the report was prepared under (from Step 4)
- Open the report for them if you're able to, or give the direct path
- If it failed, explain exactly why (site unreachable, timeout, missing dependency, bad config) — never leave the user to go dig through a terminal to find out what happened

## Step 7: Reconcile with existing docs (ask first)

If `CLIENT-BRIEF.md` or the audit `.md` has "Pending" markers or notes about a previously blocked/incomplete crawl, ask whether the user wants those updated now with the real data just pulled — don't rewrite those files unprompted.

## Notes

- The entire point of this skill is that the user never types `node`, `npm`, or a filename — if you find yourself about to tell them to run something themselves, stop and run it yourself instead (via Bash/PowerShell), unless it's something only they can do (e.g. approving a permission prompt).
- This only works with real internet access to the client's live site. If a crawl attempt fails specifically with a connection/egress error (as opposed to a bug in the crawler or config), say so plainly rather than retrying repeatedly — that means the current session/environment can't reach the site, not that something is broken.
- This skill does not itself write `CLIENT-BRIEF.md` or the audit `.md` for a brand-new client — for onboarding a brand-new client end-to-end, use `new-seo-client`. This skill picks up from there (or from any already-onboarded client) and handles everything crawler-related.
