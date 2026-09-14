# Deployment Note — Quote Builder Core 8.9.6 → 8.9.16 on sp-fruth

**Target:** the Start Performance instance (`sp-fruth`), currently running Quote Builder Core `sales-quote-system` v8.9.6, embedded via the `sp-views/fruth-quotes.php` / `sp-views/fruth-pricing.php` bridge files (Core 8.9.0+ design — see `PROJECT-SUMMARY.md` §2.1).

**Package:** `plugins/core/sales-quote-system-v8.9.16-fruth.zip`

**Pre-deployment verification (done, this session):**
- `tests/check-plugin-versions.js` — pass. Core header and `SQS_VERSION` both agree on `8.9.16` (Print Module 1.14.9, Customer DB 1.3.2 also in sync, though neither is part of this deploy).
- `tests/pricing-regression.test.js` — 81/81 assertions pass against the Excel-derived reference values. No formula/calculation regressions.

## What changes for sp-fruth

### Real bug fixes (not cosmetic)
- **8.9.13** — Custom Setup Charge / Setup Charge rows in the results table are now flagged amber whenever an override is active, with the label "(from override, not computed)". This is what would have caught the earlier $578.54-vs-$149.61 discrepancy at the time it happened instead of after the fact.
- **8.9.14** — Custom Setup Charge and COGS Overhead % become real, editable per-quote inputs on Tubing and In-Line (Zipper already had equivalents). Previously the only way to change either was the admin-only Data Editor's *global* default — a rep had no way to fix a bad value on a single quote.
- **8.9.15 (code portion only)** — `FIS-3000BK (Black Cond)` and `FIS-1010 (Zipper)` are removed from the Formula **dropdown** (`formulaOptions`), not just their cost table. sp-fruth's pricing *data* already reflects the Kris update (client applied it by hand in the live Data Editor — see `TODO.md`), but the dropdown itself is code, so until this upgrade sp-fruth may still be offering these two formulas as selectable even though they have no real cost behind them.
- **8.9.16** — fixes a real pricing-accuracy bug: a new quote pre-loads with a Formula already selected, so if a rep never touches that dropdown, Resin Cost/LBS silently keeps a stale unrelated default instead of the formula's real cost. New/reset quotes now start with Formula unselected and Resin Cost/LBS blank, forcing a real `change` event before any dollar total shows. **Loading a previously saved quote is unaffected.**

### Start Performance–specific (cosmetic/UX, 8.9.7–8.9.12)
- Dashboard "Fruth Quotes" / "Quote Value" widget, two KPI cards, AI-summary lines, and a "Quotes by Product Type" panel (8.9.7).
- Aging-quote tracking (30+ days untouched) surfaced as a dashboard banner, KPI card, and panel footnote, with in-request caching so it doesn't add repeat queries per page load (8.9.8–8.9.9).
- Toolbar restyled from dark theme to a light card matching Start Performance's own UI, using SP's accent color when embedded (8.9.10).
- New/reset quotes no longer pre-fill dimensions from the built-in example scenario; shows "Enter product specs to see pricing" until real values are entered, with divide-by-zero guards (8.9.11).
- Fixed KPI numbers overflowing/clipping in the embedded narrow-column layout, and the sticky toolbar's top-offset math (8.9.3–8.9.6, already superseded by later fixes but included in the diff).
- Fixed the toolbar status dropdown's text being vertically clipped by Start Performance's own global `<select>` styles (8.9.12).

## What does NOT change

- **No pricing data is touched.** `SQS_DEFAULT_DATA_B64` only fills in rows the database doesn't already have — it never overwrites an existing row. sp-fruth's live pricing table (already hand-corrected to the Kris values) is untouched by this upgrade.
- **No database schema changes** between 8.9.6 and 8.9.16.
- **No change to the SP auth/role-mapping logic** (that's all from 8.9.0, already live on sp-fruth since before 8.9.6).

## Deployment steps

1. Back up the currently active `sales-quote-system.php` (or just keep the 8.9.6 zip on hand) in case a rollback is needed.
2. This plugin is deployed by **overwriting the file**, not deactivate/reactivate — WordPress's activation hook does not re-fire on that kind of deploy. (This matters less here than it did for the User Accounts migration, since there's no schema change in this range, but it's the established deploy pattern for this plugin per `docs/CHANGELOG.md` 8.7.0/8.8.1.)
3. Upload/overwrite with `sales-quote-system-v8.9.16-fruth.zip`.
4. Hard-refresh the "Fruth Quotes" and "Fruth Pricing Data" sidebar views in Start Performance to confirm they still load without error.

## Post-deployment smoke test

- [ ] Open a **new** Tubing/In-Line/Zipper quote — Formula dropdown starts unselected, Resin Cost/LBS starts blank, results area shows "Enter product specs to see pricing" until a formula + dimensions are entered.
- [ ] Confirm `FIS-3000BK (Black Cond)` and `FIS-1010 (Zipper)` no longer appear in the Formula dropdown on any product.
- [ ] Enter a Custom Setup Charge on a Tubing or In-Line quote — confirm the Custom Setup Charge / Setup Charge rows highlight amber with the override label.
- [ ] Confirm COGS Overhead % is now an editable field on all three product forms.
- [ ] Load an existing saved quote — confirm it opens exactly as before (values not blanked, no override flags falsely triggered unless one was actually saved).
- [ ] Save/Load/Print still work as expected inside the SP shell (toolbar, status dropdown, KPI row all render correctly at desktop and narrow widths).
- [ ] Dashboard widgets (Fruth Quotes card, KPI cards, aging-quotes banner if any quotes are 30+ days stale) render without error.

## Rollback

Single-file overwrite plugin — if anything regresses, re-upload the 8.9.6 zip to restore the previous behavior. No schema/data changes in this range means rollback carries no data-loss risk either direction.
