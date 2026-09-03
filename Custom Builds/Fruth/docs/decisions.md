Decision #1

Portal page

/fruth/portal/

Reason

Keeps portal separate from quote builder.


Decision #2

Quote Builder

/fruth/quotes/

Reason

Allows future customer-facing portal.

Decision #3

Editor

/fruth/editor/

Reason

Dedicated pricing administration.


# DECISIONS.md

## Decision: Excel workbook is source of truth
The original Excel workbook is the authoritative pricing model.

Reason:
The plugin must preserve the business logic already used by Fruth.

---

## Decision: Preserve pricing behavior before improving code
Do not change formulas, rounding, margins, quantity breaks, or pricing rules without approval.

Reason:
Accuracy matters more than cleaner code.

---

## Decision: Separate portal, quote builder, and editor pages
Portal: `/fruth/portal/`  
Quote Builder: `/fruth/quotes/`  
Editor: `/fruth/editor/`

Reason:
Each page has a different purpose and future permissions may differ.

---

## Decision: Use relative WordPress paths
Use `home_url('/fruth/.../')`. Do not hardcode domains.

Reason:
The plugins must work on staging, production, and future site moves.

---

## Decision: Keep Core and Print Module separate
Core handles pricing, data, and quote building. Print Module handles customer-facing print output.

Reason:
This supports modular growth without bloating the core plugin.

---

## Decision: Do not rewrite from scratch
Make small, testable changes unless a rewrite is explicitly approved.

Reason:
The current plugins work and business continuity matters.

---

## Decision: Future features should become modules
Quote numbers, quote history, customers, PDFs, email, dashboards, approvals, CRM, and AI should be modular where possible.

Reason:
This aligns with the Start Performance platform direction.

---

## Decision: Document before changing business logic
Any formula difference must be documented in `FORMULA-VALIDATION.md` before changing code.

Reason:
Prevents accidental pricing drift.

---

## Decision: Version every release clearly
Use clear plugin version numbers and changelog notes.

Reason:
Rollback and troubleshooting need to be easy.

---

## Decision: Fruth branding is client-facing
Use Fruth Sales System / Fruth Quote Builder for client-facing language. Start Advertising may appear as plugin author/developer.

Reason:


The application should feel like Fruth’s internal sales tool.



## Decision: No destructive database changes without backup
Any schema change must include a backup plan, migration notes, and rollback path.

Reason:
Pricing data and quote data are business-critical.

---

## Important operational fact: pricing tables are database-backed, not code-backed, once a site is live
`SQS_DEFAULT_DATA_B64` in the plugin file is only ever used as a **fallback for rows that don't exist yet** in the `wp_sqs_pricing_data` table. `sqs_pricing_calculator_seed_defaults( $overwrite = false )` runs on activation and explicitly skips any `data_key` that already has a row — it never overwrites. `sqs_pricing_calculator_get_data()` always prefers the database row over the code constant when both exist.

**Consequence discovered 2026-07-18 (Core 8.9.15/8.9.16, `sp-fruth`)**: updating `SQS_DEFAULT_DATA_B64` in code (e.g. the Kris pricing update, 8.9.15) has **zero effect** on any site that has already been activated, because its `formulaCosts` row (and every other top-level `DATA` key) was seeded from the code the very first time the plugin ran and has been database-resident ever since. Uploading a new plugin zip does not touch it.

**What this means for every future pricing-data change**: a code update alone is not sufficient. After shipping a `SQS_DEFAULT_DATA_B64` change, each already-live site's admin must also apply the same values by hand through the "Fruth Pricing Data" Data Editor (`[sqs_pricing_data_admin]` standalone, or the SP "Fruth Pricing Data" view) — editing/adding/removing rows in the relevant table(s) directly. The code change only matters for a brand-new site activation from that point forward.

Reason:
Lets an admin correct a single pricing value from the live UI without a code deploy — but means a code-level pricing update requires a parallel manual data-entry step on every already-active site, not just a new zip upload. Worth an eventual admin "reset this table to the plugin's current defaults" button (not built as of 8.9.16) if this keeps causing confusion.