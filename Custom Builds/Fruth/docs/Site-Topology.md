# Site Topology — Corrected 2026-09-16

Earlier docs in this project (`PROJECT-SUMMARY.md`, `TODO.md`) assumed the standalone Fruth site and "a Start Performance instance" were two unrelated things — one real/live, one hypothetical/inert. Testing the 8.9.17 Zipper-hiding release surfaced that this was wrong. Recording the real topology here so it doesn't get re-litigated.

## There are two separate WordPress installs, both under the same host account

Both live on `startwebservicesbackup.com` (SSH host `start6zs`, per `start-performance-platform/deploy.ps1`'s `$SITES` map), as two of five site folders on that account (`smti`, `sp`, `fruth`, `wts`, `fiber`).

### 1. `startwebservicesbackup.com/fruth/` — the real production site

- This **is** the live Fruth Custom Packaging deployment reps and the client actually use.
- It runs **both** the standalone Quote Builder Core (`[sqs_pricing_calculator]`, `[sqs_portal]`, `[sqs_pricing_data_admin]` shortcodes at `/fruth/quotes/`, `/fruth/portal/`, `/fruth/editor/`) **and** the Start Performance platform, embedded via the `sp-views/` bridge files (8.9.0+) at `/fruth/sp-app/?view=fruth-quotes` and `/fruth/sp-app/?view=fruth-pricing`.
- **`/fruth/portal/` now forwards to `/fruth/sp-app/`** — the old standalone tile-based Portal page is no longer the real front door; Start Performance's own dashboard/sidebar is. The Portal tile's description text (e.g. "Quote pricing for Tubing & BSB") is still correct in the code, it's just not seen by anyone in normal use anymore.
- Has its own database, its own `wp_sqs_*` tables, its own pricing data. Per a standing client decision (see `TODO.md`), this site is **intentionally grandfathered on pre-Kris Formula Costs** — don't apply the Kris pricing update here even though the code defaults now include it, unless the client explicitly says otherwise. (Removing discontinued formula *rows* like `FIS-3000BK`/`FIS-1010 (Zipper)` is fine — that's cleanup, not a pricing change.)
- **As of 2026-09-16**: updated to Core 8.9.17-fruth, Print Module 1.14.9-fruth, Customer Database 1.3.2. Zipper tab hidden, Print/Customers toolbar buttons confirmed working, dead formula rows removed from this site's own Data Editor.

### 2. `startwebservicesbackup.com/sp-fruth/` — a separate internal demo/staging instance

- Labeled in the hosting panel as "Start Performance Core - LIVE DEMO SITE No Custom."
- A distinct WordPress install with its own separate database, its own separate `wp_sqs_*` tables and pricing data, its own separate WordPress user accounts. Not the same site as `/fruth/` despite the very similar name and near-identical Start Performance sidebar UI.
- This is what most of this session's Zipper-hiding testing was actually run against, based on the client's own description of it as "my working version." It is **not** what the customer/reps use day to day.
- Also updated to 8.9.17 during this session, ahead of `/fruth/` — which is how the discrepancy (Zipper gone on one, still present on the other) surfaced in the first place.

## The lesson

The two sites' Start Performance shells look nearly identical (same "Fruth Quotes" nav label, same sidebar structure, same branding), and a URL as short as `/fruth/` vs `/sp-fruth/` is easy to misread mid-testing. Several rounds of "it's fixed / no it's still broken" contradictions this session, plus a login screen showing different user lists in two browser tabs, all traced back to unknowingly comparing these two separate installs rather than one site behaving inconsistently. **Always confirm the full hostname/path (not just glance at the sidebar) before treating two test results as being about the "same" page.**
