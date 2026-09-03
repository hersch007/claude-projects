# Pricing Test Suites

Two complementary suites validate the plugin's live JavaScript pricing engine. Both are zero-dependency, pure Node, run anywhere.

- **`pricing-regression.test.js`** — narrow but ground-truth: exact values pulled from `source/Sales Quoting Calculator v3.xlsx`.
- **`pricing-differential.test.js`** — broad but self-referential: 50 scenarios spanning every film/resin/packaging type and bucket boundary, checked against a second, independently-written re-derivation of the same formulas (reads `DATA` directly, not hardcoded numbers — stays valid across future pricing-data updates like a new source workbook).

Run either with no argument to test `plugins/core/extracted/sales-quote-system/sales-quote-system.php` (the latest approved Core version), or point it at another extracted plugin copy to check a different version before release. Both exit `0` on success, `1` on any failed assertion (safe to wire into CI).

## `pricing-regression.test.js`

### How it works

1. `extract-pricing-engine.js` reads the **real plugin file** (not a hand-copied duplicate) and pulls out the `DATA` constant plus `calculateTubing` / `calculateInline` / `calculateZipper` and their helpers, substituting the two PHP interpolation points (`$sqs_data_json`, `$sqs_quote_ajax_url`) with static values so the block is plain, runnable JS.
2. `pricing-regression.test.js` runs that extracted engine in a Node `vm` sandbox and asserts its output against values read directly out of the workbook's cell formulas/cached values (see `docs/FORMULA-VALIDATION.md` for the full derivation of every expected number).
3. Because the engine is extracted from the actual plugin source on every run, this suite will **fail loudly** if a future edit changes pricing behavior — that's the point. If a fix is intentional, update the expected values here and in `FORMULA-VALIDATION.md` together, with a note explaining why.

### Adding new cases

Each `runCase(name, fn, input, expected)` call is self-contained. To add a new boundary condition, quantity break, or product scenario:
1. Unzip the `.xlsx` as a plain OOXML zip (`unzip "source/Sales Quoting Calculator v3.xlsx" -d /tmp/xlsx_raw`), then pull exact input/output cells with `node tests/tools/read-xlsx-cells.js /tmp/xlsx_raw sheetN.xml <cell refs...>` (sheet-name-to-file mapping is in `xl/workbook.xml`). `tests/tools/dump-xlsx-sheet.js` dumps a whole sheet's formulas/values at once if you need to browse rather than target specific cells.
2. Cross-check any surprising mismatch against the cell's own precedent cells by hand before assuming the plugin is wrong — the source workbook has at least one confirmed stale cached value (see `docs/FORMULA-VALIDATION.md`, In-Line BSB `B24`), so "the plugin doesn't match Excel" sometimes means "Excel's cache is stale," not "the plugin has a bug."
3. Add a new `runCase(...)` block with a comment citing the exact cell references used.

### Known limitation

This suite covers the three implemented calculators (Tubing, In-Line BSB, Zipper) with one representative scenario each plus four edge cases (setup-fee floor, custom-charge override, large production run, margin-on-price identity). It does not cover every quantity-break tier or every film/resin combination — that gap is what `pricing-differential.test.js` is for.

## `pricing-differential.test.js`

### How it works

Each of the three calculators' formulas is re-derived independently in this file — read fresh from the plugin source and re-typed with different structure/variable names, not copy-pasted — computing every intermediate (setup charge, material/labor/packaging cost, overhead, sales amount, unit price, profit, margin) straight from the live `DATA` lookup tables. 50 deterministic scenarios (17 Tubing, 17 In-Line, 16 Zipper) are generated to span every film type, every resin type, every packaging tier, width/gauge bucket boundaries, custom-charge overrides on and off, and varied COGS Overhead %/margin combinations, then each of the 10 output fields is asserted against the real extracted engine.

Because the independent re-derivation reads `DATA` directly rather than hardcoding expected numbers, this suite **automatically stays valid** across future pricing-data updates (a new source workbook, a Data Editor change to the live defaults) — it verifies the *formula code* is self-consistent with whatever `DATA` currently holds, not that a specific number matches a specific workbook. `pricing-regression.test.js` is what anchors the numbers to Excel truth; this suite is what catches a logic bug (wrong bucket index, wrong lookup table, dropped term) across a much wider input space than any one hand-verified example can cover.

### Adding new cases

Extend the deterministic scenario-generation loops (`for (let i = 0; i < 17; i++)` etc.) or add one-off calls to `verifyTubing`/`verifyInline`/`verifyZipper` with a specific `f` object. Scenario generation is intentionally deterministic (no `Math.random`) so a failure reproduces identically on every run.
