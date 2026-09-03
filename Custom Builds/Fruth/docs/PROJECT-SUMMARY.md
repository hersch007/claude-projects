# Fruth Sales System — Project Summary

**Generated from direct code inspection of:**
- Quote Builder Core `sales-quote-system` v8.6, v8.6.1-fruth, v8.6.2-fruth
- Quote Builder Print Module `quote-builder-print` v1.14, v1.14.1-fruth, v1.14.2-fruth
- `source/Sales Quoting Calculator v3.xlsx` (17-tab workbook, authoritative pricing source)
- Staging site screenshots (`screenshots/`)

This document reflects what the code actually does today, not the aspirational architecture in `docs/architecture.md`. Where the two differ, this file is the ground truth.

---

## 1. What This System Is

A three-plugin WordPress system that replaces an Excel-based pricing calculator with a database-backed, browser-based quote builder for Fruth Custom Packaging's three extrusion product lines: **Tubing**, **In-Line BSB**, and **Zipper** — now also with a reusable customer database.

All three plugins are single-file PHP plugins (no build step, no Composer, no external PHP dependencies). All three pricing calculators and the entire Quote Builder UI are implemented as **inline JavaScript rendered by PHP**, not as server-side PHP calculations. WordPress/PHP's role is: routing, shortcodes, admin menus, session-based auth, and MySQL persistence. The math itself runs in the browser.

## 2. Plugin Architecture

### 2.1 Quote Builder Core (`sales-quote-system/sales-quote-system.php`, ~3,470 lines)

| Concern | Implementation |
|---|---|
| Plugin header version | `8.9.6` (matches `SQS_VERSION` constant) |
| Default pricing data | Base64-encoded JSON blob (`SQS_DEFAULT_DATA_B64`) decoded at runtime into a `DATA` object consumed by the JS calculators |
| Database tables | `wp_sqs_pricing_data`, `wp_sqs_quotes`, `wp_sqs_quote_items`, `wp_sqs_users` (created via `dbDelta` on `register_activation_hook`) |
| Auth model | Custom session-based login, independent of WordPress user accounts. As of 8.7.0, individual accounts live in `wp_sqs_users` (`sqs_pricing_calculator_find_active_user()`, username + password, then role check — `sales`/`admin`/`both`), managed via a User Accounts UI on the Login & Branding admin page; the two old shared Sales/Editor option-based credentials remain only as an automatic fallback if the table can't be read. On success, both login paths still set the original `$_SESSION['sqs_calc_sales_logged_in']` / `$_SESSION['sqs_calc_frontend_logged_in']` flags (a hard requirement — the separate Customer Database plugin reads these two exact session keys directly) plus new `$_SESSION['sqs_calc_current_user_id']`/`current_user_name`, and call `session_regenerate_id(true)`. WP admins (`manage_options`) always pass regardless. As of 8.8.0, `wp_sqs_users` also has an independent `can_manage_users` flag (orthogonal to `role`) plus a third front-end login flow (`[sqs_user_admin]`, its own namespaced session keys `sqs_calc_useradmin_*`), so accounts with that flag can fully manage other users without ever touching WordPress admin. |
| Admin menu | Top-level "Quote Builder" (`sqs-pricing-calculator`) with submenus: Pricing Tables, Quote Builder (external launcher), Data Editor (external launcher), Login & Branding, Reset Logins, Print Settings (added by Print Module) |
| Shortcodes | `[sqs_portal]`, `[sqs_pricing_calculator]`, `[sqs_pricing_data_admin]`, `[sqs_user_admin]` (added 8.8.0) |
| Start Performance integration (8.9.0, optional) | Two small bridge files under `sp-views/` (`fruth-quotes.php`, `fruth-pricing.php`) that a separate, unrelated WordPress platform ("Start Performance", not installed on the live Fruth site) can `require` directly as sidebar-embedded views, if that platform's core plugin is active. Entirely inert otherwise -- gated behind `sqs_pricing_calculator_sp_mode()` (`function_exists('sp_register_addon')`). See `docs/CHANGELOG.md` 8.9.0 for the full design. |
| AJAX endpoints | `sqs_signout`, `sqs_save_quote`, `sqs_load_quotes`, `sqs_get_quote` — all registered for both logged-in and `nopriv`, but gated internally by `sqs_pricing_calculator_user_can_quote_ajax()` (session flag or `manage_options`) + `check_ajax_referer()` nonce. `sqs_list_users`/`sqs_save_user`/`sqs_deactivate_user` (added 8.7.0 as `manage_options`-only) gained `nopriv` registration in 8.8.0 — authorization is now `sqs_pricing_calculator_user_can_manage_users_ajax()` (WP admin OR a `can_manage_users=1` account authenticated via the new `[sqs_user_admin]` session, re-checked fresh from the DB every call), with its own nonce (`sqs_users_admin`) rather than the shared `sqs_quote_actions` one. |
| REST API | None. Everything goes through `admin-ajax.php`. |
| Extension hooks fired | `sqs_toolbar_extra_buttons`, `sqs_quote_info_extra_fields`, `sqs_bottom_stack_extra`, `sqs_quote_saved`, `sqs_portal_extra_tiles` (added in 8.6.5) — these exist specifically so add-on modules (Print, Customer Database) can attach to Core without Core knowing they exist |
| Known-broken-in-8.6.4, fixed-in-8.6.6 | The Quote Builder/Data Editor URL fallback paths briefly double-prefixed `/fruth` on this site's `home_url()` base — never visible in production since both pages already exist, but caught via the Customer Database module hitting the same latent bug. See `docs/CHANGELOG.md` 8.6.6. |
| Header description | Now lists all three shortcodes (`[sqs_portal]`, `[sqs_pricing_calculator]`, `[sqs_pricing_data_admin]`) so they're visible on the WP Admin Plugins list page, not just in docs (8.6.7). |
| Browser-autofill fix | `#companyName`, `#customerName`, `#preparedBy`, `#internalRef` now have `autocomplete="off"` (8.6.8) — without it, browsers silently re-filled these from form history on a fresh page load, which looked like stale quote data but wasn't coming from the plugin at all. |
| Print button dedupe | Core's native `#printBtn` (bare `window.print()`) removed in 8.6.9 — it duplicated the Print Module's `#sqsPrintBtn`, which properly formats the quote before printing. Kept the Print Module's version. |
| Toolbar contrast | The sticky Quote Builder toolbar background darkened (`#1f2937`, 8.6.10) to match the Customer Database's sticky bar and stand out from the page content scrolling beneath it. |

### 2.2 Quote Builder Print Module (`quote-builder-print/quote-builder-print.php`, 437 lines)

- Boots only if `SQS_VERSION` is defined (Core is active); otherwise shows an admin notice and no-ops. No hard `Requires Plugins` header dependency — it's a soft runtime check.
- Adds one submenu: **Print Settings** (`sqs-print-settings`) for company address + quote terms, saved via `update_option` with a nonce (`sqsp_settings_save` / `sqsp_nonce`).
- Attaches to Core's three extension hooks to inject: a "Print Quote" toolbar button, extra customer-info fields, and the printable quote card. No own database tables, no own shortcodes, no own AJAX handlers — it's purely additive via the hook contract.
- **Zero functional changes across v1.14 → v1.14.1 → v1.14.2.** Every diff between these three versions is a version-number bump only.

### 2.3 Quote Builder Customer Database (`quote-builder-customers/quote-builder-customers.php`, new, v1.3.2)

- Same soft-dependency boot pattern as the Print Module (`defined('SQS_VERSION')` check, admin notice if Core is missing).
- Own tables: `wp_sqs_customers` (customer records) and `wp_sqs_customer_quotes` (a pure linking table keyed on both `quote_id` and `quote_number` — necessary because Core creates a new row/id for every quote *revision* while keeping the same quote number, so a revision-safe key was needed for accurate quote history and delete-guard logic). **No changes to Core's `wp_sqs_quotes` schema.**
- Own shortcode `[sqs_customer_database]` — full add/edit/delete/search UI, gated by the same session flags Core's Quote Builder and Data Editor use (anyone logged into either, or a WP admin).
- Own AJAX endpoints: `sqs_list_customers`, `sqs_get_customer`, `sqs_save_customer`, `sqs_delete_customer`, `sqs_search_customers`, `sqs_get_customer_quotes` — all reuse Core's existing `sqs_quote_actions` nonce rather than inventing a new one.
- Integrates with the Quote Builder via Core's existing `sqs_quote_info_extra_fields` hook (adds a customer search box + a hidden field) and `sqs_quote_saved` hook (links the selected customer to the saved quote) — **zero Core code needed for this integration**, since Core's `data-sqs-meta` field-collection mechanism is already generic.
- Navigation: a toolbar button in the Quote Builder (via Core's existing `sqs_toolbar_extra_buttons` hook) plus a third Portal tile (via one new Core hook, `sqs_portal_extra_tiles`, added in Core 8.6.5 — see §5 below). Both use a dynamic shortcode-lookup-with-fallback URL, same as Portal/Quotes/Editor — see the fallback-path bug fixed in v1.0.1/Core 8.6.6, `docs/CHANGELOG.md`.
- Delete guard: a customer with any linked quotes cannot be deleted (enforced server-side, not just hidden in the UI).
- Customer record fields: company, contact, email, phone, assigned rep (free text — no per-rep logins exist in this system), street address, city, state, zip, notes.
- Self-upgrading schema: `qbc_maybe_upgrade()` (hooked to `admin_init`) re-runs `dbDelta()` whenever `QBC_VERSION` changes, so future column additions take effect on the next admin page load after uploading a new zip — no deactivate/reactivate cycle needed.
- Also has a "Start Quote" button per customer row (opens the Quote Builder in a new tab pre-filled with that customer, via a `?qbc_customer_id=` URL param) and an inline "+ Add New Customer" quick-create directly in the Quote Builder's customer search field.
- **Confirmed working live** against staging (`https://startwebservicesbackup.com/fruth/`) as of v1.3.2: Portal tile, CRUD page (add/edit/delete/search/delete-guard), City/State/ZIP/Assigned Rep fields, "Start Quote" prefill, the autofill-clear fix, quote linking, and quote history all tested directly. One real bug was found and fixed in this pass (see `docs/CHANGELOG.md` 1.3.2) — a JS event-bubbling issue that silently broke the "+ Add New Customer" quick-add.

## 3. URL Structure — Code vs. Live Site

Required paths per `docs/decisions.md` / `docs/requirements.md`:

| Page | Required path | Shortcode |
|---|---|---|
| Portal / Home | `/fruth/portal/` | `[sqs_portal]` |
| Quote Builder | `/fruth/quotes/` | `[sqs_pricing_calculator]` |
| Pricing Editor | `/fruth/editor/` | `[sqs_pricing_data_admin]` |

**How the code resolves these URLs today (v8.6.4):**

All three URL functions now share one helper, `sqs_pricing_calculator_shortcode_page_url( $shortcode_tag, $fallback_path )`: it queries `wp_posts` for whichever published page contains the given shortcode and returns `get_permalink()` for it, falling back to `home_url( $fallback_path )` only if no such page exists yet.

- `sqs_pricing_calculator_portal_url()` → looks up `[sqs_portal]`, falls back to `home_url('/')`.
- `sqs_pricing_calculator_sales_quote_builder_url()` → looks up `[sqs_pricing_calculator]`, falls back to `home_url('/fruth/quotes/')`.
- `sqs_pricing_calculator_data_editor_url()` → looks up `[sqs_pricing_data_admin]`, falls back to `home_url('/fruth/editor/')`.

All three are correctly relative (never hardcode a domain) and now self-heal if any of the three pages is ever moved or recreated at a different slug — previously only the Portal link had this property; the Quote Builder and Editor links were hardcoded string literals. See `docs/CHANGELOG.md` (8.6.4) for the change.

**Version history of this specific logic (confirmed by diffing all supplied versions plus this session's change):**
- v8.6: `sales_quote_builder_url()` returned `/quotes/`, editor returned `/editor/` (no `/fruth/` prefix at all).
- v8.6.1-fruth: introduced the Fruth prefix, but with a copy/paste bug — `sales_quote_builder_url()` returned `/fruth/portal/` (wrong; duplicated the portal path) while `data_editor_url()` correctly returned `/fruth/editor/`.
- v8.6.2-fruth: fixed — `sales_quote_builder_url()` now correctly returns `/fruth/quotes/`.
- v8.6.3-fruth: unrelated CSS contrast fix (Data Editor buttons).
- v8.6.4-fruth: all three URL functions unified onto the dynamic shortcode-lookup pattern described above.

**Live staging site reality:**
- `https://startwebservicesbackup.com/fruth/quotes/` → Quote Builder login. ✅ Correct.
- `https://startwebservicesbackup.com/fruth/editor/` → Admin Control Panel login. ✅ Correct.
- `https://startwebservicesbackup.com/fruth/portal/` → ✅ **Fixed.** Originally (per 2026 screenshots) this fell through to `https://startwebservicesbackup.com/fruth/` (site root) because the `[sqs_portal]` page was published at the wrong slug — a WordPress content issue, not a plugin defect, since `sqs_pricing_calculator_portal_url()` was already correctly finding wherever that page lived. The page has since been moved to the `/fruth/portal/` slug in WP admin and is now confirmed correct. No code change was needed or made.

## 4. The Pricing Engine

All calculation logic lives in three JavaScript functions embedded in `sales-quote-system.php`'s `sqs_pricing_calculator_render()` output (`[sqs_pricing_calculator]` shortcode, ~line 1351 onward):

- `calculateTubing(f)` — line 2206
- `calculateInline(f)` — line 2264
- `calculateZipper(f)` — line 2317

Shared helpers: `floorBucket()`, `thicknessBucket()`, `roundDown()`, `getPackagingCost()`, `getResinDensity()`, `getFormulaCost()`, all operating against the `DATA` object decoded from `SQS_DEFAULT_DATA_B64` (overridable per-key via the `wp_sqs_pricing_data` table and the Pricing Editor UI).

**Validated against the Excel workbook** (`source/Sales Quoting Calculator v3.xlsx`) — see [FORMULA-VALIDATION.md](FORMULA-VALIDATION.md) for the full cell-by-cell mapping. Summary: all three calculators are faithful, formula-for-formula ports of the `Tubing`, `In-Line BSB`, and `Zipper` worksheets, using the same margin-on-price convention (`salesAmount = totalCost / (1 - finalMargin)`), the same setup-charge floor logic (`MAX(computed setup cost, minimum setup fee)`), and the same lookup tables (Setup Details, Production Rates, Packaging, Resin Prices).

Quote persistence (`sqs_save_quote` AJAX action) stores the calculator's JS output as JSON (`quote_json`, `item_json` columns) rather than re-deriving it server-side — PHP never recalculates a price, it only stores what the browser computed.

## 5. Known Issues / Findings

1. ~~**Portal page slug** — live site's `[sqs_portal]` page is not published at `/fruth/portal/`.~~ **Resolved** — page moved to the correct slug in WP admin, confirmed working.
2. ~~**Inconsistent URL resolution strategy** — `sales_quote_builder_url()` / `data_editor_url()` were hardcoded strings; `portal_url()` dynamically resolved via shortcode lookup.~~ **Resolved in v8.6.4** — all three now share the same dynamic shortcode-lookup-with-fallback pattern. See §3 above.
3. **Data Editor "Add Row" / "Advanced View" buttons rendered with no visible text** — root cause: `.sqs-data-mini` CSS rule (`sales-quote-system.php`, inline `<style>` block near line 1018) defined border/background/padding but had **no `color` property**, unlike every sibling button class (`.sqs-data-btn`, `.sqs-data-remove`, `.sqs-data-tabs a`) which all set an explicit color. Confirmed visually in `screenshots/editor main screen.png` (blank pill buttons below the Packaging table). This was almost certainly the "edit buttons hard to read" issue the client flagged. **Fixed in v8.6.3** (`plugins/core/sales-quote-system-v8.6.3-fruth.zip`) — added `color:#172033;`. Regression suite re-confirmed clean (39/39) against the patched build.
4. **Hudson Sharp BSB** worksheet exists in the Excel workbook but is explicitly marked `"DO NOT USE YET - 3/18/24"` in cell C1, and the workbook's own `Legend` tab lists it as a "red" (work-in-progress) tab. Correctly **not** implemented in the plugin. No action needed — just noting it's an intentional gap, not a missed feature.
5. **`FIS-5100RD (Red PP)` formula** appears in the dropdown options in both Excel and the plugin, but has no cost value in the Excel `Resin Prices` lookup table (row 60, blank) or in the plugin's `formulaCosts` map. This is a **data gap in the source-of-truth spreadsheet itself**, faithfully carried into the plugin — not a plugin bug. Flag to the client for data entry.
6. **`Resin Prices`, `Inventory Prices`, and `Machine Rates` worksheets are live Google Sheets imports** (`IMPORTRANGE` formulas cached via `xludf.DUMMYFUNCTION`), not static data. The values baked into the plugin's `SQS_DEFAULT_DATA_B64` constant are a point-in-time snapshot. This is expected — it's exactly what the Pricing Editor (`[sqs_pricing_data_admin]`) exists to keep in sync — but it means "the Excel file" is not itself the live source of truth for resin costs; the linked Google Sheet is. Worth confirming with the client how often that re-sync should happen.
7. **Zipper worksheet cell `I44`** (`=I43*L6/12*B14`) is computed but never referenced by any downstream formula — an orphaned/dead calculation in the spreadsheet. Not ported to the plugin, and correctly so (nothing would be lost).
8. **`Off-Line Conversion` worksheet is empty** (no cells, no data) — a placeholder tab. No corresponding plugin feature exists or is needed.
9. **`Manual Calc (WIP)`, `Intercompany Nylon Price`, `Intercompany Zipper Price`** worksheets are separate internal/historical pricing tools unrelated to the customer-facing three-product calculator. Correctly out of scope for the plugin.

## 6. Security Posture (as observed)

- AJAX quote endpoints check both a capability/session gate (`sqs_pricing_calculator_user_can_quote_ajax()`) and a nonce (`check_ajax_referer('sqs_quote_actions', 'nonce')`) before touching the database.
- All DB reads/writes use `$wpdb->prepare()` / `$wpdb->insert()` / `$wpdb->update()` (no raw string-concatenated SQL found).
- Output escaping (`esc_html`, `esc_attr`, `esc_url`) is used consistently in the PHP-rendered chrome; the calculator's own DOM updates (JS `textContent`, not `innerHTML`, for computed values) avoid script injection via pricing data.
- Print Module settings save path uses `check_admin_referer()` correctly.

No REST API surface exists, so no REST-specific auth concerns.

## 7. What's Explicitly Out of Scope Today

Per `docs/feature-roadmap.md` and confirmed absent from the code: quote numbering beyond a simple `SQS-YYYY-NNNNN` sequence (already implemented, see `sqs_pricing_calculator_generate_quote_number()`), customer database, PDF generation, email delivery, approvals workflow, reporting dashboard, CRM/ERP integration, AI features. These are Phase 2+ per the roadmap and are not present in either plugin.
