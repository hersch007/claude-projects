# Formula Validation Report

**Canonical source:** `source/Sales Quoting Calculator v3.xlsx` (17 worksheets)
**Implementation under test:** `plugins/core` v8.6.2-fruth, `sales-quote-system.php`, JS calculators inside `sqs_pricing_calculator_render()` (lines 2206–2388)
**Method:** The xlsx was unzipped as raw OOXML and every formula + cached value was extracted directly from `xl/worksheets/sheetN.xml` (no re-entry, no rounding by a viewer). The PHP file's embedded JS was read directly. Every cell reference below is a literal formula from the workbook.

Legend: ✅ Matches Excel · ⚠️ Modified · ❌ Missing · ❓ Unable to determine

---

## Worksheet Inventory (all 17 tabs)

| Worksheet | Purpose | In scope for plugin? |
|---|---|---|
| Legend | Routing guide: tells staff which tabs are "good to use" (green) vs. work-in-progress (red) | Reference only |
| **Tubing** | Pricing calculator, tubing/extrusion product line | ✅ Implemented (`calculateTubing`) |
| **In-Line BSB** | Pricing calculator, in-line bag-sealing-bottom product line | ✅ Implemented (`calculateInline`) |
| **Zipper** | Pricing calculator, zipper product line | ✅ Implemented (`calculateZipper`) |
| Hudson Sharp BSB | 4th calculator, explicitly marked "DO NOT USE YET - 3/18/24" in cell C1 and flagged red/WIP on the Legend tab | Correctly excluded |
| Off-Line Conversion | Empty placeholder tab (zero cells) | N/A |
| Intercompany Nylon Price | Internal intercompany pricing tool, unrelated to customer quoting | Out of scope |
| Intercompany Zipper Price | Internal intercompany pricing tool, unrelated to customer quoting | Out of scope |
| Manual Calc (WIP) | Alternate work-in-progress calculator layout, not the production formula path | Out of scope |
| Resin Prices | Lookup table: resin/formula cost per lb. Fed by a linked Google Sheet (`IMPORTRANGE`, cached) | ✅ Snapshot ported to `DATA.formulaCosts` |
| Inventory Prices | Live inventory/cost database import (`IMPORTRANGE`) — operational, not pricing-formula input | Out of scope |
| Setup Details | Lookup tables: setup multiplier, setup hours, setup lbs, setup operators, minimum setup fees by film type/width | ✅ Ported to `DATA.setupMultiplier`, `setupHours`, `setupLbsTable`, `setupOpsExtrusion`, `minimumSetupFees` |
| Production Rates | Lookup tables: extrusion/inline lbs-per-hour rate by thickness × width, zipper qty/hour by width | ✅ Ported to `DATA.extrusionRateTable`, `inlineRateTable`, `zipperQtyPerHour` |
| Packaging | Lookup table: packaging cost % by bag type | ✅ Ported to `DATA.packaging` |
| Machine Rates | Live item-code/machine catalog import (`IMPORTRANGE`) — operational, not a pricing formula input | Out of scope |
| Manu. FAQ | Manufacturing capability notes (min/max widths, cleaning procedures) — plain text reference, no formulas | Out of scope |
| Master Data | Live job/order tracking import (`IMPORTRANGE`) — operational reporting, not pricing formula input | Out of scope |

---

## 1. Tubing Calculator

**Worksheet:** `Tubing` · **PHP/JS:** `calculateTubing(f)`, `sales-quote-system.php:2206-2262`

| # | Excel cell/formula | Plain-English description | Business purpose | JS equivalent | Status |
|---|---|---|---|---|---|
| 1 | `O6 = L6*M6*12*N6*2` (width × length-ft × 12 × gauge × 2) | Cubic inches of resin in the roll (layflat tube = 2 walls) | Converts roll dimensions to a material volume | `cubicInchesPerRoll=width*lengthFt*12*gauge*2` | ✅ |
| 2 | `P6 = O6*16.387` → `P8 = O8/453.6` | in³ → cm³ (×16.387) → grams via density → lbs (÷453.6) | Converts volume to weight using resin density | `weightPerRoll=(cubicInchesPerRoll*16.387*density)/453.6` | ✅ |
| 3 | `M10 = L10*O8` / `P10 = L10*P8` (L10 = roll qty) | Total order weight = per-roll weight × quantity | Order-level material weight | `orderWeight=qty*weightPerRoll` | ✅ |
| 4 | `E35 = P10+B12*P10` (B12 = scrap rate) | Add scrap % on top of order weight | Accounts for production waste/scrap in material purchasing | `totalWithScrap=orderWeight*(1+scrapRate)` | ✅ |
| 5 | `W4/X4` (Production Rates) via `IF` cascade on width buckets | Buckets the roll width down to the nearest defined width tier | Production-rate and setup tables are only defined at discrete width breakpoints; actual widths are floored to the nearest tier | `floorBucket(width, prodWidthBuckets)` / `floorBucket(width, setupWidthBuckets)` | ✅ |
| 6 | `E37 = DGET('Production Rates'!...)` keyed on thickness bucket × width bucket | Extrusion lbs/hr production rate lookup | Determines how fast the line can run this job | `prodRate=DATA.extrusionRateTable[thicknessBucket(gauge)][prodWidthBuckets.indexOf(prodWidth)]` | ✅ |
| 7 | `E38 = E34/E37` (order weight ÷ rate) | Labor hours to produce the order | Drives labor cost | `laborHours=orderWeight/prodRate` | ✅ |
| 8 | `E40 = B11*E38*V6` (operators × hours × labor rate) | Labor cost | Direct labor cost of the run | `laborCost=operators*laborHours*laborRate` | ✅ |
| 9 | `B37 = DGET('Setup Details'!...)` | Setup lbs of resin consumed during machine setup/startup, by film type + width bucket | Setup material cost input | `setupLbs` from `DATA.setupLbsTable[filmType][setupIndex]` (`'NA'` → 0) | ✅ |
| 10 | `B38 = DGET(...)` | Setup operators required, by width bucket | Setup labor cost input | `setupOps=DATA.setupOpsExtrusion[setupIndex]` | ✅ |
| 11 | `B39 = B37*B10+B38*V6*B36` (setup lbs × resin cost + setup ops × labor rate × setup hours) | Computed setup cost | Cost of machine changeover for this job | `setupCost=setupLbs*resinCost+setupOps*laborRate*setupHours` | ✅ |
| 12 | `B40 = DGET('Setup Details'!...)` | Minimum setup fee floor, by width bucket | Guarantees a minimum charge for small/quick setups regardless of computed cost | `minSetupFee=DATA.minimumSetupFees[setupIndex]` | ✅ |
| 13 | `B22 = IF(B21>0,B21,MAX(B39,B40))` | Setup charge = custom override if entered, else the greater of computed cost or the minimum fee | Ensures setup is never billed below the floor, but allows a sales override | `setupCharge = customSetupCharge>0 ? customSetupCharge : Math.max(setupCost, minSetupFee)` | ✅ |
| 14 | `B24 = E35*B10` (weight w/ scrap × resin cost/lb) | Material cost | Direct material cost | `materialCost=totalWithScrap*resinCost` | ✅ |
| 15 | `B23 = VLOOKUP(B13,Packaging!A4:B7,2,0)*B24+B14` | Packaging cost = packaging % × material cost + flat custom fee | Bag/packaging cost, scaled to job size | `packagingCost=packagingRate*materialCost+customPackagingFee` | ✅ |
| 16 | `B27 = SUM(B22:B26)` (setup + material + labor + packaging + specialty) | Direct COGS | Total direct cost before overhead | `directCogs=setupCharge+materialCost+laborCost+packagingCost+specialtyCharge` | ✅ |
| 17 | `B29 = B27*B28` (Direct COGS × overhead %) | Overhead allocation | Spreads fixed/indirect costs onto the job | `overhead=directCogs*overheadPct` | ✅ |
| 18 | `B32 = B27+B29` | Total cost | Fully-loaded job cost | `totalCost=directCogs+overhead` | ✅ |
| 19 | `F5 = F4+F3` (upcharge + base margin) | Final profit margin = base margin + upcharge | Combines the standard target margin with any job-specific upcharge | `finalMargin=profitMargin+upcharge` | ✅ |
| 20 | `F9 = B32/(1-F5)` | **Selling price = cost ÷ (1 − margin)** — margin-on-price, not markup-on-cost | Core pricing convention: guarantees the stated margin is a % of the *selling price*, not of cost | `salesAmount=totalCost/(1-finalMargin)` | ✅ |
| 21 | `I11 = I12/B9` (total sales ÷ qty) | Unit price (price/roll) | Customer-facing per-unit price | `unitPrice=salesAmount/qty` | ✅ |
| 22 | `I5 = I6/E34` | Price per lb | Alternate unit-economics view used internally | `pricePerLbs=salesAmount/orderWeight` | ✅ |
| 23 | `F16:J16` price-break table at `PRICE_BREAK_HOURS` = 2/4/8/24/48 production-hour tiers | Quantity-break pricing: shows price at fixed production-time tiers rather than fixed quantities | Lets sales quote a customer's requested run length directly | `priceBreaks = PRICE_BREAK_HOURS.map(...)` reproduces the same per-tier qty/labor/material/packaging/sales math | ✅ Numerically verified — all 5 tiers × {qty, unitPrice, sales} match `Tubing!F16:J20` exactly (`tests/pricing-regression.test.js`, `runPriceBreakCase`) |
| 24 | Target-price panel: `F6=F9-B32` (profit), reverse-solved from a target unit price entered by the user | Lets a salesperson enter a *target* price and see resulting margin/profit instead of solving forward from margin | Sales negotiation tool | `targetSales`, `targetProfit`, `targetMargin`, `targetPricePerLbs` — same reverse math | ✅ |

**Tubing verdict: ✅ Full match.** No formula deviations found.

---

## 2. In-Line BSB Calculator

**Worksheet:** `In-Line BSB` · **PHP/JS:** `calculateInline(f)`, `sales-quote-system.php:2264-2315`

| # | Excel cell/formula | Description | JS equivalent | Status |
|---|---|---|---|---|
| 1 | `O6 = L6*M6*N6*2`, where `M6 = B7+0.375` (length + 3/8" seal allowance) | Cubic inches, with a fixed 0.375" added to bag length for the bottom-seal allowance | `cubicInches=width*(lengthIn+0.375)*gauge*2` | ✅ |
| 2 | `P8 = O8/453.6*1000` (per-thousand-bags weight) | Weight is expressed **per thousand units** for this product (vs. per-roll for Tubing) | `weightPerThou=(cubicInches*16.387*density)/453.6*1000` | ✅ |
| 3 | `E34 = P10+B12*P10` | Order weight + scrap | `totalWithScrap=orderWeight+(scrapRate*orderWeight)` | ✅ |
| 4 | `E36 = DGET('Production Rates'!...)` | In-line lbs/hr rate lookup | `prodRate=DATA.inlineRateTable[thicknessBucket][prodWidthBuckets.indexOf(prodWidth)]` | ✅ |
| 5 | `B24 = E34*B10+B15` (material cost + enclosure charge B15) | Material cost includes an optional "enclosure material charge" line not present in Tubing | `materialCost=totalWithScrap*resinCost+enclosureCharge` | ✅ |
| 6 | `B23 = VLOOKUP(...)*(B24+B25)+B14` — packaging based on **material + labor**, not material alone | In-Line packaging cost base differs from Tubing (labor is included in the packaging-cost base here) | `packagingCost=getPackagingCost(packaging)*(materialCost+laborCost)+customPackagingFee` | ✅ |
| 7 | `I6 = I3*B10/1000` (target price × qty ÷ 1000) | Total sales for per-thousand pricing | `unitPrice=salesAmount/qty*1000`; `targetSales=targetUnitPrice*qty/1000` | ✅ |
| 8 | Setup: `B38 = DGET(...)+DGET(...)` — **sum of two lookups** (base extrusion ops + `inlineAdditionalOps`) | In-Line setup requires additional operators beyond the base extrusion setup crew | `setupOps=DATA.setupOpsExtrusion[setupIndex]+DATA.inlineAdditionalOps[setupIndex]` | ✅ |
| 9 | Remaining structure (setup charge floor, COGS sum, overhead, margin-on-price selling formula, price breaks, target pricing) | Same pattern as Tubing, adjusted for the /1000 unit basis | Same JS pattern, `*1000`/`/1000` scaling applied consistently | ✅ |

**In-Line BSB verdict: ✅ Full match.**

---

## 3. Zipper Calculator

**Worksheet:** `Zipper` · **PHP/JS:** `calculateZipper(f)`, `sales-quote-system.php:2317-2387`

This is the most complex of the three — it models **two production stages** (extrusion of the zipper film, then off-line conversion/application) with separate setup charges, labor pools, and cost stacks that are summed at the end.

| # | Excel cell/formula | Description | JS equivalent | Status |
|---|---|---|---|---|
| 1 | `L6 = B6+0.5` (layflat width = width + 0.5") | Fixed 0.5" allowance for layflat width | `layflatWidth=width+0.5` | ✅ |
| 2 | `M6 = B7+B8` (length + lip) | Effective length includes the zipper "lip" | `effectiveLength=lengthIn+lipIn` | ✅ |
| 3 | `O6 = L6*M6*N6*2` → weight/thou via density | Extrusion film weight, per-thousand basis | `weightPerThou=(layflatWidth*effectiveLength*gauge*2*16.387*density)/453.6*1000` | ✅ |
| 4 | `E35 = P10+B18*P10` (total scrap rate B18, not extrusion/conversion scrap separately at this step) | Order weight + **combined** scrap rate | `totalWithScrap=orderWeight+(totalScrapRate*orderWeight)` | ✅ |
| 5 | Extrusion rate lookup keyed by a **thickness key capped at 0.006** (`zipperThicknessKey`) and length-bucket, not width-bucket | Zipper extrusion rate table is indexed differently from Tubing/In-Line (by effective length, and thickness is clamped) | `zipperThicknessKey=lipIn>=0.006?0.006:lipIn`; `extrusionRateWidth=floorBucket(effectiveLength,prodWidthBuckets)` | ✅ |
| 6 | Extrusion setup: `E45=DGET(...)`, `E46=DGET(...)`, `E47=E45*B11+E46*V6*E44` (setup cost), `E48=DGET(...)` (min fee), `B27=IF(B26>0,B26,MAX(E47,E48))` | Extrusion-stage setup charge, same floor-vs-computed pattern as Tubing | `extrusionSetupCost=setupLbs*resinCost+setupOps*laborRate*setupHours`; `extrusionSetupCharge=customExtrusionSetupCharge>0 ? custom : Math.max(extrusionSetupCost, extrusionMinSetupFee)` | ✅ |
| 7 | `B28 = E35*B11+B21` (extrusion material + enclosure charge) | Extrusion-stage material cost | `extrusionMaterialCost=totalWithScrap*resinCost+enclosureCharge` | ✅ |
| 8 | `B31 = SUM(B27:B30)` | Extrusion COGS subtotal | `extrusionCogs=extrusionSetupCharge+extrusionMaterialCost+extrusionLaborCost+extrusionSpecialtyCost` | ✅ |
| 9 | `I35 = DGET('Production Rates'!A30:O31,...)` | Conversion (off-line) throughput, qty/hour by layflat-width bucket | `qtyPerHour=DATA.zipperQtyPerHour[zipperWidthBuckets.indexOf(conversionRateWidth)]` | ✅ |
| 10 | `I36 = (I34+I34*B17)*L6/12` (qty + conversion scrap) × width ÷ 12 | Linear feet of zipper tape needed, including conversion-stage scrap | `zipperNeededFt=(qty+qty*conversionScrapRate)*layflatWidth/12` | ✅ |
| 11 | `I38 = MAX(0.5, I34/I35)` | Conversion labor hours, with a **half-hour minimum** floor | `conversionLaborHours=Math.max(0.5, qty/qtyPerHour)` | ✅ |
| 12 | `I43=250` (hardcoded conversion setup qty) → `I45=(B28/B10)*I43` (per-unit extrusion material cost × 250) | Conversion setup material cost, priced as if running a fixed 250-unit setup batch | `conversionSetupQty=250`; `conversionSetupResinCost=(extrusionMaterialCost/qty)*conversionSetupQty` | ✅ |
| 13 | `I48=I46*V11*I47+I45` (0.5 hrs × conversion labor rate × 2 operators + setup material) vs. `I49=100` (hardcoded minimum); `B34=IF(B33>0,B33,MAX(I48,I49))` | Conversion-stage setup charge, same floor pattern, with a hardcoded $100 minimum | `conversionSetupCost=(0.5*laborRates.zipperConversion*2)+conversionSetupResinCost`; `conversionSetupCharge=custom>0?custom:Math.max(conversionSetupCost,100)` | ✅ |
| 14 | `B35 = I36*B14` (feet needed × zipper cost/ft) | Zipper tape material cost (separate from the extrusion resin cost) | `zipperMaterialCost=zipperNeededFt*zipperCostPerFt` | ✅ |
| 15 | `B37 = VLOOKUP(...)*(B28+B29)+B20` — packaging based on **extrusion material + extrusion labor** | Packaging cost base for zipper uses only the extrusion-stage costs, not the conversion stage | `packagingCost=getPackagingCost(packaging)*(extrusionMaterialCost+extrusionLaborCost)+customPackagingFee` | ✅ |
| 16 | `B38 = SUM(B34:B37)` | Conversion COGS subtotal | `conversionCogs=conversionSetupCharge+zipperMaterialCost+conversionLaborCost+packagingCost` | ✅ |
| 17 | `B39 = B31+B38` | Direct COGS = extrusion + conversion | `directCogs=extrusionCogs+conversionCogs` | ✅ |
| 18 | `F5 = F3+F4` where the "upcharge" slot is a **cleanroom upcharge** for Zipper (vs. a generic upcharge for Tubing/In-Line) | Zipper's margin stack uses a cleanroom-specific upcharge field | `finalMargin=profitMargin+cleanroomUpcharge` | ✅ |
| 19 | `F9 = B44/(1-F5)`, `I12 = I9*E34`, price breaks at `PRICE_BREAK_HOURS`, target-price panel | Same overall pricing/price-break/target pattern as the other two calculators | Matches | ✅ |
| 20 | Cell `I44 = I43*L6/12*B14` (= 250 × layflat width ÷ 12 × zipper cost/ft) | Computed but **never referenced by any downstream formula** — confirmed by searching every other formula in the sheet for `I44` | Not ported (correctly — it's dead in the source) | N/A — see Known Issues #7 in PROJECT-SUMMARY.md |

**Zipper verdict: ✅ Full match** (one unreferenced/dead Excel cell noted, no functional impact).

---

## 4. Shared Lookup Tables

| Table | Excel location | PHP location | Status |
|---|---|---|---|
| Packaging cost % by bag type | `Packaging!A4:B7` | `DATA.packaging` | ✅ Exact match (No Bags=0, Single=0.02, Double=0.03, Triple=0.05) |
| Resin density by resin type | `Tubing!R3:T10` (mirrored in In-Line/Zipper sheets) | `DATA.resinDensity` | ✅ Exact match (8 resins, values to 4 decimal places) |
| Setup multiplier / setup hours by film type | `Setup Details!A3:C10` | `DATA.setupMultiplier`, `DATA.setupHours` | ✅ Exact match |
| Setup lbs table by film type × width bucket | `Setup Details!A3:K10` (DGET) | `DATA.setupLbsTable` | ✅ Exact match, including `"NA"` cells for Nylon at wide buckets |
| Setup operators by width bucket | `Setup Details!D13:K14` | `DATA.setupOpsExtrusion` | ✅ Exact match `[1,1,1,1,1.5,2,2,2]` |
| In-line additional setup operators | `Setup Details!D19:K20` | `DATA.inlineAdditionalOps` | ✅ Exact match |
| Minimum setup fees by width bucket | `Setup Details!D23:K24` | `DATA.minimumSetupFees` | ✅ Exact match `[75,75,100,100,100,100,200,300]` |
| Extrusion rate table (lbs/hr) by thickness × width | `Production Rates!A3:O17` | `DATA.extrusionRateTable` | ✅ Exact match |
| In-line rate table | `Production Rates` (parallel block) | `DATA.inlineRateTable` | ✅ Exact match (identical to extrusion table in this workbook) |
| Zipper qty/hour by layflat width | `Production Rates!A30:O31` | `DATA.zipperQtyPerHour` | ✅ Exact match `[1562.5,1875,1875,1500,1500,1250,937.5,812.5,812.5,712.5,593.75,593.75,437.5,437.5]` |
| Resin/formula cost per lb | `Resin Prices!A49:K66` (VLOOKUP) | `DATA.formulaCosts` | ✅ Match, with one shared gap — see below |
| Labor rates | `Tubing!V6` (25/hr), `In-Line!V6` (23.75/hr), `Zipper!V6`/`V11` (25 / 23.75) | `DATA.laborRates.tubing=25`, `.inline=23.75`, `.zipperExtrusion=25`, `.zipperConversion=23.75` | ✅ Exact match |

**Data gap shared by both systems:** `FIS-5100RD (Red PP)` is listed as a selectable formula option in both the Excel dropdown and the plugin's `formulaOptions` array, but has no corresponding cost value in either `Resin Prices!B60` (blank in Excel) or `DATA.formulaCosts` (key absent in PHP). Selecting this formula in either system yields a $0 resin cost. This is a data-entry gap in the source workbook, faithfully mirrored — recommend flagging to the client rather than "fixing" it in code, since the correct value isn't known.

**Stale cached value found in the workbook itself:** While building the regression suite (`tests/pricing-regression.test.js`), the In-Line BSB sheet's example row failed an initial comparison against the JS engine by exactly $61 on `materialCost` and every downstream total. Root cause, confirmed by hand: `In-Line BSB!B24` has formula `=E34*B10+B15` (material cost = weight-with-scrap × resin cost + enclosure charge), where `E34=1684.1544166666665`, `B10=1.93`, `B15=61`. `E34*B10` alone equals `3250.4180241666663` — exactly the value cached in `B24`. The `+B15` term is completely absent from the cached result, even though the formula requires it and `B27` (`SUM(B22:B26)`, which includes `B24`) correctly reflects the *stale* `B24`, not a recalculated one. This means the workbook was saved without Excel's last recalculation pass being carried through this cell chain (`B24 → B27 → B29 → B32 → F9 → F7 → F8 → F6`), most likely because `B15` was edited after the last full recalc/save.

The plugin's JS engine recalculates every field from scratch on every input change, so it cannot inherit this kind of stale-cache bug by construction — it produced the mathematically correct value (`3311.4180241666663`) directly. **This is not a plugin defect.** It is, however, worth flagging to the client: anyone eyeballing this one example row in the live spreadsheet (as opposed to forcing a recalculation, e.g. Ctrl+Alt+F9 in Excel) would see a materially wrong "Total Costs" and "Total Sales Amount" for that scenario. The regression suite (`tests/pricing-regression.test.js`) uses the recalculated (correct) values, with a comment documenting the discrepancy at the point of use.

**Same staleness confirmed to propagate through the In-Line BSB quantity-break table too.** Every column of that table's material-cost row (`F24:J24`) references `B15` the same way `B24` does, so the whole cached `F19:J19` (Sales) and `F17:J17` (Price/Thou) row is stale by the same root cause — not a second bug, the same one, just visible in more cells. Hand-verified for the 2-hour and 8-hour tiers by full formula substitution (both matched the JS engine's output to the last cached digit); the JS-recalculated values are used as the expected fixtures in `tests/pricing-regression.test.js`. The quantity row (`F16:J16`) is unaffected by `B15` and matches Excel's cache directly.

**A second, unrelated data artifact found in the Zipper sheet's quantity-break table:** cell `F16` (the 2-hour tier's quantity) is a bare hardcoded number (`4100`), not a formula — every sibling cell (`G16`, `H16`, `I16`, `J16`) carries the expected `=G20*$E$37/($P$8/1000)`-style formula. This makes `F16`, and `F17` (which divides by it), unreliable for that one tier; `F19` (Sales) is unaffected since it doesn't reference `F16`. The 4/8/24/48-hour tiers were all verified to match Excel's cache exactly — see `tests/pricing-regression.test.js`, which deliberately excludes the 2-hour tier from the Zipper quantity-break assertions with this note attached.

---

## 5. Summary

| Calculator | Cells reviewed | Deviations found | Verdict |
|---|---|---|---|
| Tubing | ~45 formula cells + full 5-tier price-break table | 0 | ✅ Matches Excel exactly, including all quantity breaks |
| In-Line BSB | ~40 formula cells + full 5-tier price-break table | 1 (stale cached value affecting the main scenario *and* its price-break table — same root cause, not a plugin bug) | ✅ Matches Excel (once recalculated) |
| Zipper | ~55 formula cells + 4 of 5 price-break tiers | 2 (a dead/unreferenced cell, no output impact; and a hardcoded/non-formula cell isolated to the 2-hour price-break tier) | ✅ Matches Excel (2hr price-break tier untestable due to source data, not a plugin issue) |
| Shared lookup tables | 11 tables | 1 (shared data gap: FIS-5100RD cost missing in both systems) | ✅ Matches Excel |

**No pricing formula in the plugin diverges from the Excel workbook.** Every issue noted above is a pre-existing characteristic of the Excel workbook itself (stale caches, a dead cell, a hardcoded cell breaking one column's formula chain, and a missing data-entry value), correctly carried through — or, in the stale-cache/hardcoded-cell cases, correctly *not* carried through — into the plugin. None are plugin defects. No business-logic changes are recommended or needed at this time, per the "preserve behavior" mandate in `docs/decisions.md`.

A working regression-test suite implementing exactly this validation now exists at `tests/pricing-regression.test.js` (see `tests/README.md`) — it runs the plugin's real JS engine against these Excel-derived values on every run (81 assertions as of this writing, all passing) and will fail loudly if a future change alters pricing output. It now covers the full quantity-break table for all three calculators (with the two documented exceptions above), not just one representative scenario per calculator. See `TODO.md` for recommended next steps to expand its coverage further (film/resin/packaging combinations, zero/blank-input edge cases).
