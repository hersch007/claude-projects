# Changelog

Compiled by diffing the three supplied zip files for each plugin byte-for-byte. Both plugins are single-file (`sales-quote-system.php`, `quote-builder-print.php`), so every change below is a complete, exhaustive diff — nothing is summarized or inferred.

## Quote Builder Customer Database (`quote-builder-customers`) — NEW

### 1.3.2 — latest — first version confirmed working end-to-end on live staging
- Fixed: the "+ Add New Customer" quick-add option in the Quote Builder's customer search dropdown appeared to do nothing when clicked. Root cause: a JS event-bubbling timing bug — clicking "+ Add New Customer" replaces the dropdown's contents with the quick-add form (`showQuickAddForm()`), which detaches the clicked element from the DOM mid-event. The page also has a document-level "close dropdown on outside click" listener; by the time that listener ran (later in the same event's bubble phase), the detached element no longer registered as being inside the dropdown, so the listener concluded the click was "outside" and immediately hid the dropdown again — undoing the form the same click had just opened. Fixed with `e.stopPropagation()` in the dropdown's own click handler. Found via live browser testing against staging (`https://startwebservicesbackup.com/fruth/`), not caught by code review.
- Every other piece of this module (Portal tile, Customer Database CRUD page including City/State/ZIP/Assigned Rep, "Start Quote" prefill, quote linking + delete-guard, quote history) was tested live and confirmed working with no changes needed.

### 1.3.1
- Added a defensive clear of Core's Company/Contact/Prepared By/Internal Reference fields on Quote Builder page load. Root cause of the client-reported "quote fields still show the last customer" issue: those four fields have no `autocomplete="off"`, so the **browser** (not the plugin) was re-filling them from its own form history on a fresh page load — confirmed by a client screenshot showing "Start Advertising" / "Richard Brashear" pre-filled with no quote loaded. Fixed at the root in Core 8.6.8 (see below) by adding `autocomplete="off"` to those four inputs; this clear is a belt-and-suspenders layer in case a browser ignores that hint. Runs before the `qbc_customer_id` prefill check and before Core's own "Load Quote" flow can act (a later user click), so neither is affected.

### 1.3.0
Data model additions, both explicitly requested and confirmed by the client before implementing (no production customer data existed yet, so this was the cheapest possible time to do it — no migration risk):

- Added structured address fields: `city`, `state`, `zip` (new columns; the existing `address` field is now labeled "Street Address" and holds just the street line — its underlying column/type is unchanged, only new columns were added).
- Added `assigned_rep` (free-text) to track which salesperson owns a customer account. **Note**: this system has no individual sales-rep logins (one shared "sales_team" credential for everyone), so this is a manual text field, not derived from who's logged in — same pattern as the existing "Prepared By" field on quotes.
- Added a lightweight schema-upgrade check (`qbc_maybe_upgrade()`, hooked to `admin_init`) that re-runs `dbDelta()` whenever `QBC_VERSION` changes, so this and future schema additions take effect immediately after uploading a new zip — no deactivate/reactivate needed. `dbDelta()` only adds missing columns, it never drops data.
- Assigned Rep now also shows as a column in the customer list and is included in the list-page search.
- The Quote Builder's "+ Add New Customer" quick-create form (added in 1.1.0) intentionally still only asks for Company/Contact/Email/Phone, to keep it fast — City/State/ZIP/Assigned Rep are filled in later via the full Edit form.

### 1.2.0
- Added a **"Start Quote"** link on each row of the Customer Database list — opens the Quote Builder in a new tab with that customer already selected (`?qbc_customer_id=<id>` on the Quote Builder URL; a small JS check on page load reads it and prefills the customer field via the existing `sqs_get_customer` endpoint — no Core changes, no new endpoint).
- Increased contrast on the Customer Database page's sticky search/add-customer bar — was plain white on a near-white page background, now a dark toolbar so it reads clearly as a persistent header while the page content scrolls beneath it.

### 1.1.0
Feature addition, driven directly by client feedback while testing on staging: the Customer field in the Quote Builder previously only supported *selecting* an existing customer — if a customer didn't exist yet, there was no way to create one without leaving the page.

- Added an inline **"+ Add New Customer"** option in the customer search dropdown. Clicking it opens a small form (Company, Contact, Email, Phone) right in place; saving immediately fills in the quote's Company/Contact fields and links the new customer, all without navigating away from the Quote Builder. Reuses the existing `sqs_save_customer` endpoint — no new AJAX handler needed.
- Repositioned the Customer field to the top-left of the Quote Info grid (previously it rendered wherever `sqs_quote_info_extra_fields` happened to place it, after Core's own 5 fields and the Print Module's 4). Since that hook always fires after Core's fields — there's no earlier hook to move into — this uses CSS Grid's `order: -1` on the field's wrapper to reposition it visually without changing DOM/hook order.
- Gave the search field a distinct blue tint and a "🔍 (type to search)" label hint so it reads clearly as a lookup field rather than a plain text input.
- No database or AJAX-endpoint changes; purely a UI/UX enhancement to the existing `qbc_extra_info_fields()` hook handler.

### 1.0.2
- Description-only change: plugin header now lists its shortcode (`[sqs_customer_database]`) so it's visible directly on the WP Admin Plugins list page, not just in the docs. No functional change.

### 1.0.1
- Fixed: the fallback URL used when the Customer Database page doesn't exist yet (`qbc_customer_database_url()`) resolved to `/fruth/fruth/customers/` — a doubled path — on this site. Root cause: this WordPress install's own site address already resolves to `.../fruth` (confirmed by the Portal page living at exactly that URL as the site's front page), so `home_url()` already supplies the `/fruth` prefix; the fallback path was wrongly passing `/fruth/customers/` on top of that. Changed to `home_url('/customers/')`. Caught immediately when the client tried the "Customers" navigation before the real page existed yet — the fallback path is the only part of the URL logic that had never been exercised in production before this.
- The exact same class of bug was found and fixed in Core `sales-quote-system` at the same time — see 8.6.6 below. Both fixes shipped together since they were caused by the same misunderstanding of this site's `home_url()` base.

### 1.0.0 — initial release
New third plugin, built as an independent module attaching to Core exactly the way the Print Module does — zero changes to Core's database schema. Implements every item in `docs/testing.md`'s "Customer Tests" checklist: add/edit/search customers, delete blocked when quotes are linked, select-and-autofill during quote creation, and quote history per customer.

- New tables: `wp_sqs_customers` (customer records) and `wp_sqs_customer_quotes` (a pure linking table, keyed on `quote_number` as well as raw `quote_id` — necessary because Core creates a new row/id for every quote *revision* while keeping the same quote number, so history and delete-guard logic needed a revision-safe key).
- New shortcode `[sqs_customer_database]` — full list/search/add/edit/delete UI, styled to match the existing Pricing Editor page.
- Quote Builder integration: a customer search/autocomplete box injected into Core's Quote Info grid via the existing `sqs_quote_info_extra_fields` hook (the same extension point the Print Module already uses) — selecting a customer fills in the existing Company/Contact fields and a new hidden field, which flows into the saved quote automatically via Core's existing generic `data-sqs-meta` mechanism. No Core code needed for any of this.
- Quote-to-customer linking happens via Core's existing `sqs_quote_saved` hook — reads the selected customer ID from the save payload, with no Core schema changes.
- Navigation: a "Customers" button in the Quote Builder toolbar (via the existing `sqs_toolbar_extra_buttons` hook, zero Core changes) plus a third Portal tile.
- Delete protection: a customer with any linked quotes cannot be deleted — enforced server-side (not just in the UI).

**Note on testing**: this plugin was built and reviewed without a live WordPress/MySQL environment available in this session (no PHP interpreter, no WP install) — every function was written by closely mirroring Core's and the Print Module's own already-working code paths, and the file was read through in full for consistency, but it has not been executed. Test on staging using the acceptance criteria in `docs/testing.md`'s "Customer Tests" section before relying on it in production.

## Quote Builder Core (`sales-quote-system`)

### 8.9.16 (Fruth) — latest
Bug fix, found while manually testing 8.9.15's pricing update against the live app: a Tubing quote (FCR-1000, 24"x300ft, qty 20) showed Total Sales $1,317.53 in the app against an expected $1,402.08. Root cause was **not** a pricing bug -- **Resin Cost/LBS** only auto-fills from the Formula dropdown when the dropdown actually fires a `change` event (`syncResinCostFromFormula()`). Every new Tubing/In-Line/Zipper quote pre-loads with a formula *already selected* (from `DATA.defaults`), so if the rep never touches that dropdown, Resin Cost/LBS silently keeps its own separate, unrelated default value (Tubing's was `1.04`, pre-dating the 8.9.15 update, instead of FCR-1000's current $1.1278) rather than the formula's real cost -- producing a real-looking but wrong total with no indication anything was off.

- **Fixed**: new/reset quotes now start with Formula **unselected** ("&mdash; Select Formula &mdash;") instead of pre-filled, and Resin Cost/LBS starts blank instead of carrying a stale default. Picking any formula is now always a genuine `change` event, so the correct cost auto-fills every time -- the underlying auto-fill logic (`syncResinCostFromFormula`) is unchanged, it just now actually fires on a fresh quote.
- The "Enter product specs to see pricing" blank-result state (added in 8.9.11 for width/length/qty) now also requires a formula to be selected, so a quote can't show a dollar total before Resin Cost has a real value behind it.
- New `makeFormulaSelect()` helper (Formula's own select builder, with the placeholder option) alongside the existing generic `makeSelect()`, which is unchanged and still used for Film Type/Resin Type/Packaging.
- Loading a previously saved quote is unaffected -- this only changes the starting state of a brand-new quote or "Reset Defaults," never a loaded one.
- No formula/calculation changes. Regression suite (81/81) and differential suite (500/500) both re-run clean -- neither exercises `freshForms()`/`isEmptyQuote()`, so this is purely a UI-state fix.

### 8.9.15 (Fruth)
Pricing-data update from the client's new "Kris Hall" workbook (`source/Kris - Sales Quoting Calculator v3 .xlsx`), following the full sheet-by-sheet comparison done earlier this session. Re-verified every shared pricing table against `docs/FORMULA-VALIDATION.md`'s documented cell locations: resin density (`Tubing!R3:T10`), packaging % (`Packaging!A4:B7`), setup fees/hours/multipliers (`Setup Details!A3:K24`), production rate tables (`Production Rates!A3:O31`), and labor rates (`Tubing!V6`, `In-Line!V6`, `Zipper!V6`/`V11`) are all **unchanged** -- only the Formula Costs table (`Resin Prices!A49:K60`) actually moved. Every change below was confirmed with the client before touching `SQS_DEFAULT_DATA_B64` -- none of this was guessed at, per the standing rule that pricing-formula changes need explicit sign-off.

- **11 formula costs updated** in `DATA.formulaCosts`:

  | Formula | Old | New |
  |---|---|---|
  | CFB-1000 | $0.98 | $1.08 |
  | CFB-2500(CELO) | $1.0475 | $1.16 |
  | CFB-5000(Nylon) | $2.18 | $2.00 |
  | FCR-6000(Nylon) | $2.18 | $2.00 |
  | FCR-1000 | $1.039 | $1.1278 |
  | FCR-1020(Felo) | $1.3228 | $1.4546 |
  | FIS-1000 | $0.7165 | $0.7768 |
  | 75% Repro | $0.425 | $0.47 |
  | FCR-1010 (Zipper) | $1.0675 | $1.145 |
  | FCR-5010 (Stedim) | $1.1675 | $1.1925 |
  | FCR-6010(OAS Nylon) | $2.963 | $2.81 |

- **Removed** `FIS-3000BK (Black Cond)` ($2.70) from `DATA.formulaCosts`/`DATA.formulaOptions` -- it's gone from Kris's workbook entirely. A new blank-cost `FIS-5000 (Black Cond)` row appeared in roughly the same spot, but per the client's direction we are *not* adding it yet (no confirmed cost) -- this formula is simply gone from the app's dropdown until there's a real replacement.
- **Removed** `FIS-1010 (Zipper)` ($0.88452) the same way -- gone from Kris's workbook with no replacement anywhere, and per the client's direction it's now removed from the app rather than left stale.
- **`FIS-5100RD (Red PP)` remains unpriced** -- still blank in Kris's workbook too, so this is the same deferred gap as before (see `docs/TODO.md`), not a new issue. Still in `formulaOptions`, still absent from `formulaCosts`, exactly as it was.
- `tests/fixtures/default_data.json` updated identically to `SQS_DEFAULT_DATA_B64` (kept byte-for-byte in sync, as always).
- **What this does *not* validate**: the 81-assertion regression suite re-runs clean (81/81), but its expected values are keyed to the *original* workbook's worked examples, not Kris's -- it confirms the calculation engine itself is untouched by this change, not that the new numbers reproduce a worked example from Kris's sheet (Kris's workbook doesn't have cached example rows the way the original did). If the client wants the new costs cross-checked against a specific quote example, that's a separate step.

### 8.9.14 (Fruth)
Follow-up to 8.9.13: Custom Setup Charge and COGS Overhead % were never actually editable per-quote for Tubing or In-Line (no input field existed for either), and COGS Overhead % had no input field for any of the three products, including Zipper. The only way to change either was the admin-only Data Editor's "Calculator Defaults" section, which sets the *global default* for all new quotes of that product type, not a single quote.

- **Added**: a "Custom Setup Charge" number input to the Tubing and In-Line forms (Zipper already has the equivalent "Custom Extrusion Setup" / "Custom Conversion Setup" fields).
- **Added**: a "COGS Overhead %" number input to all three forms (Tubing, In-Line, Zipper) -- previously not editable anywhere in the calculator UI on any product.
- Both use the existing generic `bindInputs()`/`onInputChange()` wiring (id format `${product}-${key}`), so no new JS binding logic was needed -- same mechanism every other per-quote field already uses. Values round-trip through the existing results-table row (`Custom Setup Charge`, `COGS Overhead %`) and the 8.9.13 override-highlight unchanged.
- No formula changes -- these fields already existed in `f.customSetupCharge`/`f.overheadPct` and were already read by all three calculators; they just weren't exposed as inputs before now. Regression suite re-run clean (81/81).

### 8.9.13 (Fruth)
Follow-up to a client-reported pricing discrepancy investigation: an In-Line BSB quote showed Price/Thousand $578.54 against Excel's $149.61 for what looked like matching inputs. Traced through the plugin's real pricing engine (`tests/extract-pricing-engine.js`) against the exact screenshot inputs and confirmed the calculation itself is correct -- with Custom Setup Charge at $0 the engine produces $147.29-$149.61, matching Excel. The live quote had a **$450 Custom Setup Charge** in the field (it fully overrides the computed setup fee when >0), which is what actually produced $578.54. Not a pricing bug -- a silent-override UX gap, and now that 8.9.7-8.9.12 have been diffed for the backfill below, the mechanism is clear: $450 is In-Line's built-in example-scenario default (`DATA.defaults.inline.customSetupCharge`), and 8.9.11's `freshForms()` only zeroes width/length/qty on a new quote, not the other example-scenario fields -- so every new In-Line quote silently carries a $450 setup-charge override until someone notices and clears it.

- **Added**: when a Custom Setup Charge (or, for Zipper, Custom Extrusion Setup Charge) override is active, the "Custom Setup Charge" and "Setup Charge" rows in the results table are now visually flagged (amber highlight, reusing the existing `--sqc-warn`/`--sqc-warn-soft` variables already used for KPI warnings) and the Setup Charge label appends "(from override, not computed)". Makes an active override impossible to miss instead of silently distorting the price.
- `rowsToTable()` gained an optional third array element (CSS row class) to support this -- backward compatible, every existing call site still only passes 2 elements.
- No pricing/formula changes at all -- purely a display addition. Regression suite re-run clean (81/81); diff confirmed scoped to exactly this indicator (CSS + the two touched rows + the helper's optional param).
- **Not done here, worth considering next**: `freshForms()` (8.9.11) could also zero `customSetupCharge`/`customExtrusionSetupCharge`/`customPackagingFee` so a brand-new quote never carries an override at all, rather than relying on the rep to notice the new amber flag. Left alone for this fix since it changes new-quote behavior (a real, if small, functional change) rather than just adding visibility -- flagging for a decision rather than making it unilaterally.

### 8.9.12 (Fruth)
CSS specificity fix, found during the client's continued Start Performance testing: the Save/Load toolbar's status dropdown (`.sqs-bar-select`) had its text vertically clipped when embedded.

- **Root cause**: Start Performance's own global `<select>` stylesheet rule (`font-size:14px; padding:10px 12px`) has enough specificity/load-order priority to override Fruth's compact toolbar dropdown style.
- **Fixed**: added a more specific selector (`.sqs-status-control select.sqs-bar-select`) with `!important` on height/padding/font-size to reassert the compact style regardless of load order or specificity.
- No pricing changes. Regression suite re-run clean (81/81).

### 8.9.11 (Fruth)
Two changes from continued Start Performance testing:

- **New/reset quotes no longer pre-fill with the built-in example scenario's dimensions.** Previously, opening a new quote (or clicking "Reset Defaults") populated width/length/qty straight from `DATA.defaults` -- the same hardcoded example values used throughout this project's own regression tests (e.g. In-Line BSB's 30"x36" x5,000 bags) -- so a brand-new quote showed a fully-priced result before the rep had entered anything real. Added `freshForms()`, which deep-clones `DATA.defaults` as before but zeroes out `width`/`lengthFt`/`lengthIn`/`qty` for all three products; new `isEmptyQuote()`/`blankResult()` helpers detect that empty state and show "Enter product specs to see pricing" instead of a stale result. **Note**: this only clears the dimension/qty fields -- other example-scenario values from `DATA.defaults` (Custom Setup Charge, resin cost, packaging, etc.) still carry over into a new quote unchanged. See 8.9.13, where In-Line's carried-over $450 Custom Setup Charge default turned out to be the root cause of a client-reported pricing discrepancy.
- Added divide-by-zero guards (`qty>0`, `orderWeight>0`) around `unitPrice`/`pricePerLbs` in all three calculators, consistent with the new blank-state handling.
- No formula changes to any non-zero-qty calculation -- regression suite (81 assertions, all non-zero-qty scenarios) re-run clean.

### 8.9.10 (Fruth)
Toolbar restyle, prompted by the client's continued Start Performance testing: the toolbar's original dark theme (`#1f2937` background, red-accent buttons) looked out of place against Start Performance's light, card-based UI.

- Restyled `.sqs-quote-toolbar` and its buttons to a light card (white background, subtle border, `--sp-accent` CSS variable for the primary Save button so it inherits Start Performance's own accent color when embedded, falling back to Fruth red `#CC1F1F` standalone).
- **This is an unconditional style change** -- it applies to the standalone Fruth site's toolbar too, not only the embedded view, since the base `.sqs-quote-toolbar` rule isn't `$embedded`-gated.
- No pricing changes. Regression suite re-run clean (81/81).

### 8.9.9 (Fruth)
Follow-up to 8.9.8's aging-quotes tracking, surfacing it visibly and making it cheap to compute:

- Added an in-request cache (`static $cache`) to `sqs_pricing_calculator_sp_quote_stats()` and `sqs_pricing_calculator_sp_aging_quotes()` -- the dashboard widget, KPI cards, AI summary, and product panel all call these, and were each re-running the same aggregate query on every page load without it.
- `sqs_pricing_calculator_sp_aging_quotes()` now also tracks `oldest_days`.
- Quotes open (Draft/Final) with no update in 30+ days now surface in three places: an amber follow-up banner at the top of the SP dashboard (`Review →` link into Fruth Quotes), a new "Needs Follow-up" KPI card, and a footer note on the product-type breakdown panel.
- No pricing changes.

### 8.9.8 (Fruth)
Added aging-quote tracking as a backend function, `sqs_pricing_calculator_sp_aging_quotes( $age_days = 30 )` -- counts and totals open (non-Archived) quotes whose latest revision hasn't been updated in 30+ days -- and surfaced one summary line from it in the AI intel summary ("Aging Fruth quotes still open... candidates to finalize or follow up"). The visible dashboard banner and KPI card come in 8.9.9. No pricing changes.

### 8.9.7 (Fruth)
Added Start Performance dashboard/KPI/AI-summary integration for Fruth quotes, mirroring how the SMTI Sales addon surfaces its own data but reading Fruth's local `wp_sqs_quotes` table directly instead of an external API:

- New aggregate helper `sqs_pricing_calculator_sp_quote_stats()` -- pulls the latest revision per `quote_number` and rolls it up into overall/active counts and value, plus per-status and per-product breakdowns.
- Hooked into four Start Performance extension points: `sp_dashboard_before_stats` (a 2-card "Fruth Quotes" / "Quote Value" widget at the top of the SP dashboard), `sp_intel_kpi_cards` (two KPI cards on the KPI Dashboard), `sp_intel_summary_lines` (quote totals + status mix + new-in-period count in the AI-generated summary), and `sp_intel_after_kpi` (a "Quotes by Product Type" breakdown panel).
- All four are gated behind `sp_is_view_hidden('fruth-quotes')` / an empty-count check, so they render nothing when Fruth has no quotes yet or the view is hidden for a given team member.
- No pricing changes. Regression suite re-run clean (81/81); this is purely additive Start Performance surface area, same "inert unless Start Performance core is active" discipline as the rest of the SP integration.

### 8.9.6 (Fruth)
(8.9.5 was a version-number-only bump with no code change -- confirmed by diff -- so it's skipped here.) 8.9.4's fix for the sticky-toolbar gap didn't actually work once tested live; this replaces it with a different, more reliable approach.

- **Why 8.9.4 didn't work**: it used a negative `margin-top` on the sticky toolbar to reach up into Start Performance's own scroll container padding and cancel it out. That combination -- `position:sticky` + a negative margin reaching into a *scrolling* ancestor's padding (`.sp-main` has `overflow-y:auto`) -- is a known unreliable pattern across browsers; padding on a scroll container doesn't collapse the same way it does on a normal static one.
- **Fixed differently**: left Start Performance's own 32px padding alone entirely -- it's the platform-standard inset that every view in that system gets, so matching it is the *correct* look, not a compromise. Instead, zeroed out `.sqs-calc`'s own 22px top padding when embedded, which is Fruth's own padding to remove and isn't subject to the same scroll-container edge case. Net effect: the toolbar now sits right at Start Performance's normal content inset, same as every other page there, instead of with an extra ~22px of Fruth's own padding stacked on top.
- No pricing changes. Regression suite re-run clean (81/81); diff against the last real code change (8.9.4) confirmed scoped to exactly this swap.

### 8.9.4 (Fruth)
Follow-up to 8.9.3's sticky-toolbar fix: `top:0` alone wasn't enough, because `top:0` on a sticky element means "flush with the top of its scrolling container's padding box," not "flush with the visible top of the screen" -- and that scroll container turned out to have its own padding.

- **Root cause, confirmed directly from Start Performance's own CSS** (not guessed): `.sp-main` -- the platform's actual scroll container that Fruth's embedded view renders inside -- has `padding:32px` (`20px 16px` under 768px), and it has `overflow-y:auto`, meaning it (not the browser viewport) is what `position:sticky` measures against. On top of that, Fruth's own `.sqs-calc` wrapper adds another 22px of its own top padding. Both were stacking above the toolbar regardless of its own `top` value.
- **Fixed**: added a negative `margin-top` (-54px desktop, -42px under 768px, matching Start Performance's own breakpoint) to pull the toolbar up through both padding layers so it sits flush at the true top of the visible scroll area.
- No pricing changes. Regression suite re-run clean (81/81); diff against 8.9.3 confirmed scoped to exactly this one CSS addition.

### 8.9.3 (Fruth)
Two more polish fixes from the client's continued Start Performance testing, same "standalone-page sizing doesn't fit the embedded context" theme as 8.9.2:

- **Sticky toolbar hanging ~1 inch from the top.** `.sqs-quote-toolbar` is `position:sticky; top:58px`, sized to sit directly below the standalone page's 58px-tall app-bar. Embedded mode never renders that app-bar, so the 58px became a dead gap instead. `sqs_app_bar_styles()` now takes an `$embedded` parameter and adds a `top:0` override for that case.
- **KPI numbers overflowing/clipping in the 5-across summary row** (Total Sales / Total Cost / Net Revenue / Price-Roll / Margin). Those cards use `overflow:hidden`, and their font sizes (31px, 36px for the first card) were sized for the standalone page's full desktop width -- too large for the narrower column width left over once Start Performance's own sidebar takes its share of the screen. Added an embedded-only size reduction (20px / 22px) so all five values fit without clipping.
- Both fixes follow the same pattern already established in 8.9.0-8.9.2: an `$embedded`-conditional CSS block, standalone rendering completely untouched.
- No pricing changes. Regression suite re-run clean (81/81); diff against 8.9.2 confirmed scoped to exactly these two additions.

### 8.9.2 (Fruth)
Bug fix, found immediately after 8.9.1: the client confirmed the KPI numbers now update correctly inside Start Performance, but the toolbar/status dropdown/Load Quote panel rendered as an unstyled "hot mess" -- plain white buttons, a full-width dropdown with no toolbar layout, and the Load Quote panel showing inline on the page (with its default placeholder text "Loading saved quotes...") instead of as a hidden popup.

- **Root cause**: all of that CSS (`.sqs-quote-toolbar`, `.sqs-bar-btn`, `.sqs-status-control`, `.sqs-load-modal` and friends -- including the `display:none` that keeps the Load Quote panel hidden until actually opened) lives in `sqs_app_bar_styles()`, which is hooked to WordPress's `wp_head` action. Start Performance's app shell builds its own `<head>` and never fires `wp_head`, so none of that CSS ever printed when embedded -- explaining both the unstyled look and the always-visible Load Quote panel (it was never actually "open," it just had no `display:none` rule to hide it).
- **Fixed**: `sqs_pricing_calculator_render()` now calls `sqs_app_bar_styles()` directly when `$embedded` is true, guaranteeing that CSS exists regardless of whether `wp_head` fires. The standalone Data Editor path was checked too and doesn't have this problem -- its equivalent styling (`sqs_pricing_calculator_frontend_styles()`) was already called directly rather than through a hook.
- No pricing changes. Regression suite re-run clean (81/81); diff against 8.9.1 confirmed scoped to exactly this one addition.

### 8.9.1 (Fruth)
Bug fix, found on the client's first real test of 8.9.0 inside Start Performance: the Quote Builder's live pricing KPI row (Total Sales / Total Cost / Net Revenue / Price-Roll / Margin) stayed stuck at $0.00 no matter what was entered, while the compact summary row directly above it (Profit Margin / Upcharge / Final Margin / Price-Roll) updated correctly.

- **Root cause**: `renderResults()`'s JS unconditionally set `document.getElementById('productLabel').textContent = ...` on every render. `#productLabel` lives inside the standalone app-bar, which 8.9.0's `$embedded` mode deliberately does not render (Start Performance's own sidebar replaces it). With that element missing, `.textContent = ...` on `null` threw immediately -- aborting the rest of `renderResults()` before it ever reached the five KPI values below, on every single render cycle. That's why nothing the client changed ever updated them: the function crashed at the same line every time before getting there.
- **Fixed**: added a null check before setting `#productLabel`, matching the same defensive pattern already used for the (also app-bar-only) sign-out button's script. No other functional change.
- No pricing changes -- confirmed by the client that a prior scenario mismatch (comparing the app's default "PE/PP Color (Tint)" film type against the Excel workbook's "PE/PP Clear" example) accounted for the earlier "quotes don't match the spreadsheet" concern, not a calculation bug; a separate rounding discrepancy was also noted by the client to revisit later, not yet investigated.
- Regression suite re-run clean (81/81); diff against 8.9.0 confirmed scoped to exactly this one-line fix plus the version bump.

### 8.9.0 (Fruth)
Adds optional, dual-mode integration with a separate WordPress multi-plugin platform called "Start Performance" (a different ecosystem some clients run, with its own team-member accounts and dashboard sidebar). The client wants to use Fruth as the "Sales" module on a new Start Performance instance instead of installing that platform's SMTI-specific Sales module. This is **the same plugin file, not a fork** — it detects whether Start Performance's core plugin is active and behaves accordingly.

- **On the live Fruth Custom Packaging site** (Start Performance not installed): zero behavior change. Every new function added by this release starts with a `function_exists()` check that's false there, so none of it ever runs.
- **On a Start Performance site**: registers as an addon (`sp_register_addon('fruth-sales', ...)`) under the platform's existing `sales-core` nav section (the same slot the platform's own "SMTI Sales" and "Sales Core" modules use), and adds two views to the sidebar: **Fruth Quotes** (visible to every team member) and **Fruth Pricing Data** (visible only to admin-role team members). Confirmed by reading the actual Start Performance core plugin and its SMTI Sales addon (both supplied by the client) for the exact integration contract -- registration hooks, view-rendering environment, and auth functions were verified against real code, not guessed.
- **Auth**: extends (does not replace) the two existing capability checks -- `sqs_pricing_calculator_user_can_quote_ajax()` and `sqs_pricing_calculator_frontend_is_logged_in()` -- with an additional OR-branch that checks Start Performance's `sp_get_current_team_member()`/`sp_is_admin_member()` when running there. Role mapping (confirmed by client): Start Performance **Admin** gets Sales + Data Editor; **Agent** gets Sales only. Fruth's own `wp_sqs_users` accounts system, migration, and `[sqs_user_admin]` page are entirely unused in this mode -- Start Performance's own team-member screen already covers "add a person, assign a role" for the whole platform, so nothing needed to be rebuilt.
- **Rendering**: `sqs_pricing_calculator_render()` and `sqs_pricing_calculator_render_frontend_editor()` both gained an `$embedded` parameter (default `false`, meaning **zero change to the one existing caller of each**). When true, the standalone page chrome (branded top bar, Home/Sign-Out buttons) is skipped, since Start Performance's own sidebar already provides navigation -- but the functional Save/Load/Print/Status toolbar is kept. Two new small bridge files, `sp-views/fruth-quotes.php` and `sp-views/fruth-pricing.php`, are what Start Performance's core actually `require`s to render each view; both just call the existing render functions with `$embedded = true`. The `[sqs_pricing_calculator]`/`[sqs_pricing_data_admin]` shortcodes and all their standalone chrome/CSS/JS are completely unchanged for everyone else.
- **Data**: no schema changes. Quotes and pricing data keep using the existing `wp_sqs_pricing_data`/`wp_sqs_quotes`/`wp_sqs_quote_items` tables regardless of mode. The "Prepared By" auto-fill now sources its name from the logged-in Start Performance team member when in that mode (`sqs_pricing_calculator_current_user_display_name()`), same "display convenience only" caveat as before.
- **Packaging**: `tests/tools/make-plugin-zip.js` was extended to recurse into subdirectories (needed for the new `sp-views/` folder) -- purely additive, existing flat single-file plugins zip identically to before.
- No pricing changes. Regression suite re-run clean (81/81); full diff against 8.8.2 reviewed and confirmed scoped to exactly the above.
- **Not testable end-to-end in this environment**: there is no live Start Performance WordPress instance available here, only the platform's plugin source (supplied by the client for this integration). This entire feature is inert and unexercised on the current live Fruth site by design. Before relying on it: install this zip alongside Start Performance's core + Sales Core plugins on a real instance, confirm an agent-role team member sees only "Fruth Quotes" in the sidebar and can save/load quotes, and confirm an admin-role team member also sees "Fruth Pricing Data" and can save table edits there.

### 8.8.2 (Fruth)
Visual follow-up after the client's first look at the working front-end User Management page (8.8.1): it rendered with unstyled tables and plain grey buttons, inconsistent with the rest of the branded front-end pages.

- **Root cause**: the shared User Accounts panel (`sqs_pricing_calculator_render_user_accounts_panel()`) uses WordPress-admin CSS classes (`.widefat`, `.button`, `.button-primary`, `.notice`) that come styled for free inside wp-admin, but have no CSS at all on the public front end (wp-admin's stylesheet isn't loaded there).
- **Fixed**: `sqs_pricing_calculator_render_user_admin_panel()` now wraps the page in the same `.sqs-data-app`/`.sqs-app-bar` structure the Data Editor page already uses (branded top bar with logo, "Home"/"Sign Out" buttons, card-on-light-grey layout), and adds a small scoped stylesheet (under a new `.sqs-user-panel` wrapper class) that gives the shared panel's table, buttons, and notices a matching look on the front end. Scoped deliberately to that one wrapper class so the wp-admin "Login & Branding" page's rendering of the exact same shared panel is completely untouched.
- No pricing, schema, or logic changes -- purely CSS/markup. Regression suite re-run clean (81/81); diff against 8.8.1 confirmed scoped to exactly this one function.

### 8.8.1 (Fruth)
Bug fix, found immediately during the client's first real test of 8.8.0 on staging: "Reset Logins" said "Done!" but the new emergency account's password didn't actually work.

- **Root cause**: `sqs_pricing_calculator_activate()` (which runs `dbDelta()` and adds any missing database columns — necessary because this plugin is deployed by overwriting the file, which never re-fires WordPress's activation hook) was only called defensively from the Data Editor page, the main Pricing Tables admin page, and the quote-saving AJAX handlers. It was **not** called from Reset Logins, the Login & Branding page, the Sales Portal, or the new front-end User Management page. If "Reset Logins" was the first page touched after uploading 8.8.0, the new `can_manage_users` column was never added to `wp_sqs_users`, so the reset's `$wpdb->replace()` call silently failed against a missing column (no visible error, since the message was shown unconditionally) — the account was never actually created or updated.
- **Fixed**: added the same defensive `sqs_pricing_calculator_activate()` call to `sqs_pricing_calculator_reset_page()`, `sqs_pricing_calculator_access_page()`, the Sales Portal shortcode (`sqs_pricing_calculator_shortcode()` — this one had the gap even before 8.8.0, just never mattered until a schema change made it matter), and the new `[sqs_user_admin]` shortcode. Every page that touches `wp_sqs_users` now guarantees the schema is current before reading or writing it.
- No pricing changes. Regression suite re-run clean (81/81); diff against 8.8.0 confirmed scoped to exactly these four defensive calls.

### 8.8.0 (Fruth)
Follow-up to 8.7.0's individual accounts, driven by two client requests: (1) staff must be able to fully manage user accounts without ever touching WordPress admin, and (2) an account should be able to hold Sales + Data Editor + a third "manage other users" capability all at once, which the single-value `role` field (sales/admin/both) couldn't express. A Plan-agent security review of the design (before implementation) caught one real bug to avoid (a single lockout guard silently not covering both capabilities) and one correctness bug to avoid introducing (a new login flow overwriting shared session state used elsewhere) — both addressed below.

- Added `can_manage_users` (tinyint) to `wp_sqs_users`, fully independent of `role` — a `sales`-only account can also manage users, so can `admin` or `both`.
- Added a new front-end shortcode, `[sqs_user_admin]`, structurally identical to the existing Sales/Data-Editor login pages (own session flag, own login form) — any account with `can_manage_users=1` can now create, edit, and deactivate other accounts entirely from the public site. Reachable via a new "User Management" tile on the Portal (`[sqs_portal]`), always visible next to the existing Quote Builder/Data Editor tiles.
- The existing "User Accounts" list/add-edit-form (previously wp-admin-only, on the Login & Branding page) was extracted into a shared render function (`sqs_pricing_calculator_render_user_accounts_panel()`) so it's rendered identically from both the wp-admin page (kept, unchanged for real WordPress admins) and the new front-end page — one shared UI and AJAX backend, two entry points.
- The three User Management AJAX endpoints (`sqs_list_users`/`sqs_save_user`/`sqs_deactivate_user`) gained `nopriv` registration (real users are now anonymous WP visitors authenticated only through the custom session) and a new authorization gate that re-checks the account's status/capability fresh from the database on every call — a mid-session demotion or deactivation takes effect immediately, not just at next login.
- Added a **second, independent lockout guard**: alongside the existing "can't remove the last active admin/both-role account" check, a new one blocks removing the last active `can_manage_users=1` account. These are deliberately separate — role and "can manage users" are orthogonal capabilities, and an early design pass confirmed the existing role-based guard does not (and should not) cover the new capability; both must run on every save/deactivate.
- New login session (`sqs_calc_useradmin_logged_in`, plus `sqs_calc_useradmin_user_id`/`user_name`) is deliberately **not** sharing the existing `sqs_calc_current_user_id`/`current_user_name` keys used by the Sales/Data-Editor logins for the "Prepared By" auto-fill — namespaced separately so signing into User Management in one browser tab can never overwrite which person the Quote Builder thinks is active in another tab of the same session.
- "Reset Logins" break-glass recovery now also grants `can_manage_users=1` on the emergency account, so after a WordPress-admin-triggered reset, the client can immediately use the front-end User Management page again without a second WordPress-admin trip.
- No pricing changes. Regression suite re-run clean (81/81); full diff against the 8.7.0 zip reviewed and confirmed scoped to exactly the above. Not testable end-to-end in this environment (no live WordPress/MySQL) — flagged in `docs/TODO.md`, with extra emphasis on the new front-end AJAX exposure and the two independent lockout guards, since this is the first time User Management AJAX has been reachable by anonymous visitors.

### 8.7.0 (Fruth)
Individual user accounts and roles, replacing the two shared logins ("Sales" / "Data Editor") that every rep and admin previously typed the same credentials into. Requested by the client to get real accountability and per-person access control; explicitly scoped to live inside the existing "Login & Branding" admin page rather than a new plugin, since Core already owns all the session-checking logic every other page/AJAX call depends on.

- Added a new `wp_sqs_users` table (`id, display_name, username (unique), password_hash, role, status, created_at, updated_at, last_login_at`). `role` is `sales | admin | both`; `status` is `active | inactive` — a loose whitelisted varchar, matching the existing convention already used for `quotes.status` rather than a lookup table.
- Added a one-time, defensively-re-checked migration (`sqs_pricing_calculator_maybe_migrate_users()`) that seeds two rows from whatever the old shared Sales/Editor username+password options currently hold, tagged "(legacy)". Runs on every relevant page load (not just plugin activation), because this plugin is deployed by overwriting the file — WordPress's activation hook never re-fires on that kind of deploy. If the old Sales and Editor usernames happened to be identical, the second one is automatically suffixed and an admin notice explains the rename — this avoids a duplicate-username failure that could otherwise lock out one of the two existing logins on upgrade.
- Both login screens (Quote Builder's Sales Portal and the Data Editor) now check the new table first (username + password, then role compatibility), with the old shared-option check kept as an automatic fallback only if the new table can't be read (e.g. restrictive DB permissions) — removing that fallback is planned for a later release once the table's health is confirmed in production.
- Added `session_regenerate_id(true)` on every successful login (a pre-existing session-fixation gap; no functional change to normal use).
- Added a **User Accounts** section to the Login & Branding admin page: list, add, edit, and deactivate individual accounts (name, username, password, role, active/inactive). Deactivating (never hard-deleting) is blocked for the last remaining active admin-level account, so a routine cleanup can't lock the whole team out of the Data Editor or this screen. New AJAX endpoints (`sqs_list_users`/`sqs_save_user`/`sqs_deactivate_user`) are gated to WordPress admins only (`manage_options`), with their own nonce — no public/`nopriv` access, unlike the sales-facing endpoints.
- "Reset Logins" is repurposed as a break-glass safety net: it now unconditionally (re)creates one guaranteed `sales_admin_reset` / `ChangeMe123!` account with `both`-role access (works for both the Quote Builder and the Data Editor), independent of whatever state the rest of the accounts table is in, and also resets the legacy fallback credentials to the same password as a second layer of recovery.
- The Quote Builder's "Prepared By" field now auto-fills with the logged-in user's name if it's blank (never overwrites a value already typed or restored from a loaded quote). Display convenience only — `preparedBy` remains free text inside the saved quote, not a tamper-proof audit trail; a real `created_by_user_id` audit column is noted in `docs/TODO.md` as a natural low-risk follow-up, not built in this pass.
- No pricing changes. Regression suite re-run clean (81/81); full diff against 8.6.10 reviewed and confirmed scoped to exactly the above (see `docs/PROJECT-SUMMARY.md` for the architecture note). Not testable end-to-end in this environment (no live WordPress/MySQL) — needs real staging verification before production use, especially the migration step.

### 8.6.10 (Fruth)
- Changed: the sticky Quote Builder toolbar (Save Quote / New Revision / Load Quote / Customers / Print Quote / Reset row, `.sqs-quote-toolbar`) now has a dark background (`#1f2937`, matching the tone already used for the Customer Database's sticky bar) instead of near-white (`#f8fafc`), so it visibly separates from the page content scrolling beneath it. Also lightened two elements that would otherwise have gone illegible against the darker background: the toolbar separator line (`.sqs-bar-sep`) and the "Unsaved"/save-status text (`.sqs-bar-status`, was `#64748b` medium gray, now `#cbd5e1` light gray). Every button and pill control in the toolbar (Save Quote, New Revision, Load Quote, the Status dropdown, Print Quote, Reset, Customers) already has its own opaque background, so none of those needed changes. CSS-only, no JS/logic touched. Regression suite re-run clean (81/81).

### 8.6.9 (Fruth)
- Removed: the native `#printBtn` toolbar button (bare `window.print()` on the raw on-screen UI) and its click handler. Found during live testing of the Customer Database module — it sat in the toolbar right alongside the Print Module's `#sqsPrintBtn`, which renders a proper formatted quote card before printing. Two buttons labeled "Print Quote" doing different things; clicking the wrong one would print the raw internal UI — margin percentages, cost breakdowns, target-pricing panel — to whatever the user was about to hand a customer. Kept the Print Module's version (the one actually designed for a customer-facing document) and removed Core's. Confirmed present unchanged since v8.6 (predates this entire session, not a regression from any change made here). Client decision: remove the duplicate rather than keep both. Regression suite re-run clean (81/81); diffed against 8.6.8 to confirm only these two lines were removed.

### 8.6.8 (Fruth)
- Fixed: added `autocomplete="off"` to the Quote Info grid's `#companyName`, `#customerName`, `#preparedBy`, and `#internalRef` inputs. Without it, browsers remember and re-fill these fields from form history across page loads — on a fresh, unsaved quote there's no server-side or plugin-side value in any of them, so anything appearing there was the browser, not stale plugin state. Confirmed by a client screenshot showing a previous quote's company/contact name pre-filled on what should have been a blank quote. Purely additive HTML attribute change — no JS/logic touched. Regression suite re-run clean (81/81).

### 8.6.7 (Fruth)
- Description-only change: plugin header now lists all three of its shortcodes (`[sqs_portal]`, `[sqs_pricing_calculator]`, `[sqs_pricing_data_admin]`) so they're visible directly on the WP Admin Plugins list page. No functional change. Print Module bumped to 1.14.3 at the same time for the same reason (its description now clarifies it has no shortcode of its own).

### 8.6.6 (Fruth)
- Fixed a latent bug introduced in 8.6.4's URL-unification refactor: the fallback paths passed to the new shared `sqs_pricing_calculator_shortcode_page_url()` helper for Quote Builder and Data Editor were `/fruth/quotes/` and `/fruth/editor/` — but `home_url()` (which the helper wraps the fallback in) already resolves to this site's `/fruth` base, so those fallbacks would have produced `/fruth/fruth/quotes/` and `/fruth/fruth/editor/`. **This was invisible in production** because the Quotes and Editor pages already exist, so the dynamic shortcode lookup always succeeds and the broken fallback is never actually reached — it would only have surfaced if either page were ever deleted or unpublished. Caught because the identical pattern, copied into the new Customer Database module, hit its fallback immediately (that page didn't exist yet) and produced a visibly broken `/fruth/fruth/customers/` URL. Changed both fallbacks to `/quotes/` and `/editor/` (no leading `/fruth`). The Portal's own fallback (`home_url('/')`) was already correct and untouched.
- No pricing changes. Regression suite re-run against this build (81/81 pass).

### 8.6.5 (Fruth)
- Added one new extension point, `do_action('sqs_portal_extra_tiles')`, inside `sqs_pricing_calculator_portal_shortcode()` — fired right after the existing Quote Builder / Data Editor tiles, before the portal footer. Purely additive (confirmed by diff: exactly one line added, nothing else changed) — lets the new Customer Database module render a third Portal tile without Core needing to know it exists, the same pattern Core already uses for the Print Module's toolbar button and quote-info fields.
- No pricing changes. Regression suite re-run against this build (81/81 pass, `tests/pricing-regression.test.js`); version-consistency check (`tests/check-plugin-versions.js`) also passes and now covers all three plugins.

### 8.6.4 (Fruth)
- Changed: `sqs_pricing_calculator_sales_quote_builder_url()` and `sqs_pricing_calculator_data_editor_url()` now resolve dynamically via a new shared helper, `sqs_pricing_calculator_shortcode_page_url( $shortcode_tag, $fallback_path )` — the same pattern `sqs_pricing_calculator_portal_url()` already used. Each looks up whichever published page contains its shortcode (`[sqs_pricing_calculator]` / `[sqs_pricing_data_admin]`) and falls back to the previous hardcoded path (`/fruth/quotes/` / `/fruth/editor/`) only if no such page exists. On the current live site (both pages exist at their expected slugs) this is a no-op; going forward, moving/renaming either page in WP will no longer silently break navigation the way it just did for the Portal page.
- Added: an inline comment next to `SQS_VERSION` reminding maintainers to keep it in sync with the header `Version:` line, plus a new automated check (`tests/check-plugin-versions.js`) that fails loudly if they ever drift apart again — the exact mistake made in 8.6.1 and Print Module 1.14.1.
- No pricing changes. Regression suite re-run against this build (81/81 pass, `tests/pricing-regression.test.js`).
- **Declined**: hardening the `wp_ajax_nopriv_sqs_*` endpoints (previously recommended in `TODO.md`). On closer inspection this system's sales/admin login is a custom PHP `$_SESSION` flag, never a real WordPress login (`wp_set_current_user()` / `wp_signon()` are never called) — so every real sales rep and pricing admin is, from WordPress's point of view, a logged-out visitor. Removing the `nopriv` registrations would have broken quote save/load for all of them, leaving only `manage_options` WordPress admins able to use the tool. The original recommendation was wrong; corrected in `TODO.md`.

### 8.6.3 (Fruth)
- Fixed: the Data Editor's "Add Row" and "Advanced View" buttons (`.sqs-data-mini` CSS class) rendered with invisible/unreadable text — the rule was missing a `color` property that every sibling button class (`.sqs-data-btn`, `.sqs-data-remove`, `.sqs-data-tabs a`) already had. Added `color:#172033` to match. This is believed to be the "edit buttons hard to read" contrast issue reported by the client. One-line CSS change; confirmed via `screenshots/editor main screen.png` and root-caused in `docs/FORMULA-VALIDATION.md`/`docs/PROJECT-SUMMARY.md`.
- No other changes. Pricing engine untouched — verified via `tests/pricing-regression.test.js` (39/39 assertions pass against this build).

### 8.6.2 (Fruth)
- Fixed: `sqs_pricing_calculator_sales_quote_builder_url()` now correctly returns `/fruth/quotes/`. In 8.6.1 it incorrectly returned `/fruth/portal/`, meaning the "Quote Builder" launcher in the WP admin submenu and the Portal's "Quote Builder" button pointed at the wrong destination.
- Bumped `SQS_VERSION` constant to `8.6.2` (previously the constant was left at `8.6` in 8.6.1 even though the plugin header said `8.6.1` — a version-string mismatch that is now resolved).

### 8.6.1 (Fruth)
- Added the `/fruth/` URL prefix to the Quote Builder and Editor launcher URLs for the first time (previous behavior, see 8.6 below, used bare `/quotes/` and `/editor/`).
- Introduced the copy/paste bug described above (quote builder URL pointed at `/fruth/portal/`).
- Did not update the `SQS_VERSION` constant (stayed at `8.6`) despite the header version bump.

### 8.6 (baseline, pre-Fruth)
- `sqs_pricing_calculator_sales_quote_builder_url()` returned `/quotes/`.
- `sqs_pricing_calculator_data_editor_url()` returned `/editor/`.
- No `/fruth/` prefix anywhere.

No other lines differ across these three versions — no schema changes, no shortcode changes, no admin menu changes, no AJAX/hook changes, no pricing-formula changes in this window.

## Quote Builder Print Module (`quote-builder-print`)

### 1.14.3 (Fruth) — latest
- Description-only change: plugin header now clarifies it has no shortcode of its own — it attaches to the `[sqs_pricing_calculator]` Quotes page via Core's hooks. No functional change.

### 1.14.2 (Fruth)
- Version bump only (`Version: 1.14.2`, `SQSP_VERSION` constant updated to match). No functional or behavioral change from 1.14.1.

### 1.14.1 (Fruth)
- Version bump only (`Version: 1.14.1`). `SQSP_VERSION` constant was **not** updated in this release (stayed at `1.14`) — same class of version-string mismatch seen in Core 8.6.1, fixed in the next release.

### 1.14 (baseline, pre-Fruth)
- Baseline. No functional changes to report across any of the three supplied versions — the print module's settings page, hook attachments (`sqs_toolbar_extra_buttons`, `sqs_quote_info_extra_fields`, `sqs_bottom_stack_extra`, `wp_head`), and rendering logic are byte-identical in all three zips.

## Notes on Versioning Discipline

Both plugins show the same pattern across this three-version window: the version *string* in the constant (`SQS_VERSION` / `SQSP_VERSION`) lagged one release behind the plugin header's `Version:` line, then caught up in the following release. Recommend keeping these in lockstep going forward — see `TODO.md`.
