# Quote Builder Plugin — Fruth Custom Packaging
**Author:** Start Advertising | RH Brashear  
**Current Version:** 8.1 (core) + add-on modules  
**Files:**  
- Core: `sales-quote-system.php` (single-file plugin)  
- Print Add-on: `quote-builder-print/quote-builder-print.php`

---

## What This Is
A WordPress plugin for Fruth's internal sales team. It provides a pricing
calculator for plastic film products (tubing, inline, zipper) and a full
quote management system. Accessed via frontend pages, not WP admin.

---

## WordPress Pages & Shortcodes
| Page Slug | Shortcode | Purpose |
|---|---|---|
| `sales-quoting-system` | `[sqs_pricing_calculator]` | Quote Builder (sales UI) |
| `front-end-access` | `[sqs_pricing_data_admin]` | Admin Control Panel (data editor) |
| Any page | `[sqs_portal]` | Portal/landing with links to both apps |

---

## Architecture
- **Core:** single-file plugin (`sales-quote-system.php`) — pricing calculator, DB, quote save/load, session auth
- **Add-ons:** separate plugins that hook into core via WordPress actions — each is its own zip
- No build process, no npm, no composer — plain PHP/JS/CSS
- Core functions prefixed `sqs_pricing_calculator_` (except `sqs_app_bar_styles`)
- Add-on functions prefixed by their module slug (e.g. `sqsp_` for print)
- Deployed to WordPress by uploading the zip via WP Admin → Plugins → Add New

---

## Database Tables (created on activation)
| Table | Purpose |
|---|---|
| `{prefix}sqs_pricing_data` | Editable pricing/formula data (key-value JSON store) |
| `{prefix}sqs_quotes` | Saved quotes — number, revision, status, customer, total |
| `{prefix}sqs_quote_items` | Line items belonging to each quote |

---

## Authentication
- **Frontend login** is session-based (`$_SESSION['sqs_calc_sales_logged_in']`)
- Data Editor session: `$_SESSION['sqs_calc_frontend_logged_in']`
- Completely separate from WordPress user accounts
- Password set via WP Admin → Quote Builder → Access Settings
- Admins (`manage_options`) bypass the login automatically
- **Sign Out** uses JS + AJAX (`sqs_signout` action → `admin-ajax.php`) to clear sessions, then redirects to the portal. Do NOT change this back to a form POST — server-side redirects from within shortcodes are unreliable on this host.

---

## Product Types
- **Tubing** — extruded plastic tubing
- **Inline** — inline converted bags/film
- **Zipper** — zipper bags (two-stage: extrusion + conversion)

---

## Pricing Data Keys (editable via Admin Control Panel)
`packaging`, `resinDensity`, `formulaCosts`, `formulaOptions`, `filmTypes`,
`resinTypes`, `setupWidthBuckets`, `prodWidthBuckets`, `zipperWidthBuckets`,
`setupMultiplier`, `setupHours`, `setupLbsTable`, `setupOpsExtrusion`,
`inlineAdditionalOps`, `minimumSetupFees`, `extrusionRateTable`,
`inlineRateTable`, `zipperQtyPerHour`, `laborRates`, `defaults`

---

## Quote System
- **Quote numbers:** `SQS-YYYY-NNNNN` (e.g. SQS-2026-00001)
- **Revisions:** Rev A, Rev B, Rev C... (generated from index)
- **Statuses:** Draft → Final → Archived
- **AJAX endpoints:** `sqs_save_quote`, `sqs_load_quotes`, `sqs_get_quote`, `sqs_signout`

---

## Key Functions Reference
Line numbers are approximate — shift with edits. Search by function name.
| Function | Purpose |
|---|---|
| `sqs_pricing_calculator_activate` | Creates DB tables on plugin activation |
| `sqs_pricing_calculator_get_data` | Fetches pricing data from DB |
| `sqs_pricing_calculator_portal_url` | Finds portal page by querying for `[sqs_portal]` shortcode in DB |
| `sqs_pricing_calculator_render` | Renders the main Quote Builder UI |
| `sqs_pricing_calculator_render_frontend_editor` | Renders the Admin Control Panel |
| `sqs_pricing_calculator_frontend_styles` | CSS for Data Editor — does NOT contain app bar styles (those live in `sqs_app_bar_styles`) |
| `sqs_pricing_calculator_ajax_save_quote` | Saves a quote to DB |
| `sqs_pricing_calculator_ajax_load_quotes` | Loads quote list |
| `sqs_pricing_calculator_ajax_get_quote` | Loads a single quote |
| `sqs_pricing_calculator_ajax_signout` | AJAX handler — clears both sessions, returns portal URL |
| `sqs_app_bar_styles` | Single source of truth for all app bar & button CSS (injected via wp_head) |
| `sqs_pricing_calculator_labels` | Human-readable labels for data keys |

---

## Add-on Module System

### Extension Hooks (core fires these)
| Hook | Type | Where | Purpose |
|---|---|---|---|
| `sqs_toolbar_extra_buttons` | `do_action` | Toolbar (before Reset) | Add buttons to the top toolbar |
| `sqs_quote_info_extra_fields` | `do_action` | Inside Quote Info grid | Inject extra `<div class="sqc-field">` inputs |
| `sqs_bottom_stack_extra` | `do_action` | End of bottom stack | Inject whole cards (e.g. print card) |

### JS Public API (`window.SQS`)
Core exposes these helpers for add-on scripts:
`SQS.money(v)`, `SQS.money3(v)`, `SQS.num(v,d)`, `SQS.pct(v)`, `SQS.esc(s)`,
`SQS.getState()`, `SQS.getResults()`, `SQS.getQuoteNumber()`

### JS Event (`sqs:update`)
After every calculation, core dispatches on `document`:
```js
new CustomEvent('sqs:update', { detail: { result, state, quoteNumber } })
```
Add-ons listen: `document.addEventListener('sqs:update', fn)`

### `data-sqs-meta` Attribute
Any `<input>` or `<textarea>` with `data-sqs-meta="fieldName"` is automatically:
- Included in quote saves (via `getQuoteMeta()`)
- Restored when a quote is loaded (via `applyLoadedQuote()`)
- Bound to trigger `update()` on input

### Available Modules
| Module | Status | Zip | Prefix |
|---|---|---|---|
| Print Module | ✅ v1.1 | `quote-builder-print-1-1.zip` | `sqsp_` |
| Email Module | Planned | — | `sqse_` |
| CRM Integration | Planned | — | `sqsc_` |
| PDF Export | Planned | — | `sqspdf_` |
| Approval Workflow | Planned | — | `sqsw_` |
| Multi-brand/Template | Planned | — | `sqsb_` |

---

## Important — Do NOT Change Without Care
- `SQS_DEFAULT_DATA_B64` constant — base64 encoded default pricing data, changing breaks fresh installs
- DB activation/table structure — requires migration planning if columns change
- Session start hook runs at `init` priority 1 — must stay early
- Nonces are used on all quote AJAX calls — don't remove them
- `sqs_app_bar_styles()` is the single CSS source for app bar, buttons, toolbar, and modal — do not add competing `.sqs-app-bar` or `.sqs-bar-btn` rules elsewhere (previously caused Data Editor button regression)
- Sign Out is AJAX + JS — see Authentication section

---

## Coding Conventions
- WordPress coding standards (tabs, Yoda conditions)
- All user input sanitized (`sanitize_text_field`, `absint`, `esc_html`, etc.)
- AJAX handlers registered for both `wp_ajax_` and `wp_ajax_nopriv_` (frontend users aren't WP logged in)
- CSS/JS is inline (no enqueued assets) — intentional for single-file simplicity
