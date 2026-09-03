// Differential regression suite: complements pricing-regression.test.js's narrow-but-
// Excel-verified coverage with much broader scenario coverage, using a *different*
// verification method.
//
// pricing-regression.test.js checks the live engine against hand-verified Excel cell
// values -- exact truth, but only one scenario per calculator plus a few edge cases
// (see its "Known limitation" note in tests/README.md).
//
// This suite instead re-derives each formula independently, straight from the DATA
// lookup tables, in code written separately from the plugin (different structure,
// not copy-pasted) -- then asserts it matches the live engine's actual output across
// 50 scenarios that deliberately span every film type, every resin type, every
// packaging tier, width/gauge bucket boundaries, custom-charge overrides on and off,
// and varied COGS Overhead %/margin combinations. Because it reads DATA directly
// instead of hardcoding expected numbers, it stays valid across future pricing-data
// updates (e.g. a new source workbook) -- it verifies the *formula code* is
// self-consistent with whatever DATA currently holds, not that DATA matches any
// particular workbook (that's pricing-regression.test.js's job).
//
// Run:  node tests/pricing-differential.test.js [path-to-sales-quote-system.php]

const fs = require('fs');
const path = require('path');
const vm = require('vm');
const { extractPricingEngine } = require('./extract-pricing-engine');

const pluginPath = process.argv[2] ||
  path.join(__dirname, '..', 'plugins', 'core', 'extracted', 'sales-quote-system', 'sales-quote-system.php');
const defaultDataPath = path.join(__dirname, 'fixtures', 'default_data.json');
const DATA = JSON.parse(fs.readFileSync(defaultDataPath, 'utf8'));

const engineSrc = extractPricingEngine(pluginPath, defaultDataPath);
const sandbox = { module: { exports: {} } };
vm.createContext(sandbox);
vm.runInContext(engineSrc, sandbox, { filename: 'pricing-engine.js' });
const { calculateTubing, calculateInline, calculateZipper } = sandbox.module.exports;

// ── Independently-written helpers (structured differently from the plugin's own
//    floorBucket/thicknessBucket/getPackagingCost/getResinDensity) ────────────────
function bucketFloor(value, labels) {
  let chosen = labels[0];
  for (let i = 1; i < labels.length; i++) {
    if (Number(value) < Number(labels[i])) return chosen;
    chosen = labels[i];
  }
  return labels[labels.length - 1];
}
function gaugeBucket(g) {
  if (g < 0.003) return '0.002';
  if (g < 0.004) return '0.003';
  if (g < 0.005) return '0.004';
  if (g < 0.006) return '0.005';
  return '0.006';
}
function pkgRate(name) { return DATA.packaging[name] ?? 0; }
function density(resin) { return DATA.resinDensity[resin] ?? 0; }

let pass = 0, fail = 0;
const failures = [];

function close(a, b, label, tol = 1e-6) {
  const denom = Math.max(Math.abs(b), 1e-9);
  const rel = Math.abs(a - b) / denom;
  if (rel > tol) {
    fail++;
    failures.push(`  x ${label}: engine=${a} independent=${b} (rel err ${rel.toExponential(3)})`);
  } else {
    pass++;
  }
}

// ── Independent Tubing re-derivation ────────────────────────────────────────────
function verifyTubing(name, f) {
  const width = Number(f.width), lengthFt = Number(f.lengthFt), gauge = Number(f.gauge), qty = Number(f.qty);
  const resinCost = Number(f.resinCost), scrapRate = Number(f.scrapRate), operators = Number(f.operators);
  const specialty = Number(f.specialtyCharge || 0), customPkgFee = Number(f.customPackagingFee || 0);
  const overheadPct = Number(f.overheadPct);
  const rho = density(f.resinType);
  const laborRate = DATA.laborRates.tubing;

  const cubicIn = width * lengthFt * 12 * gauge * 2;
  const weightPerRoll = (cubicIn * 16.387 * rho) / 453.6;
  const orderWeight = qty * weightPerRoll;
  const totalWithScrap = orderWeight * (1 + scrapRate);

  const setupW = bucketFloor(width, DATA.setupWidthBuckets);
  const setupIdx = DATA.setupWidthBuckets.indexOf(setupW);
  const setupLbsRaw = DATA.setupLbsTable[f.filmType][setupIdx];
  const setupLbs = setupLbsRaw === 'NA' ? 0 : Number(setupLbsRaw);

  const prodW = bucketFloor(width, DATA.prodWidthBuckets);
  const prodRate = DATA.extrusionRateTable[gaugeBucket(gauge)][DATA.prodWidthBuckets.indexOf(prodW)];
  const laborHours = orderWeight / prodRate;
  const laborCost = operators * laborHours * laborRate;

  const setupOps = Number(DATA.setupOpsExtrusion[setupIdx]);
  const setupHours = Number(DATA.setupHours[f.filmType]);
  const setupCost = setupLbs * resinCost + setupOps * laborRate * setupHours;
  const minSetupFee = Number(DATA.minimumSetupFees[setupIdx]);
  const setupCharge = Number(f.customSetupCharge) > 0 ? Number(f.customSetupCharge) : Math.max(setupCost, minSetupFee);

  const materialCost = totalWithScrap * resinCost;
  const packagingCost = pkgRate(f.packaging) * materialCost + customPkgFee;
  const directCogs = setupCharge + materialCost + laborCost + packagingCost + specialty;
  const overhead = directCogs * overheadPct;
  const totalCost = directCogs + overhead;

  const finalMargin = Number(f.profitMargin) + Number(f.upcharge);
  const salesAmount = totalCost / (1 - finalMargin);
  const unitPrice = qty > 0 ? salesAmount / qty : 0;
  const profit = salesAmount - totalCost;

  const r = calculateTubing(f);
  close(r.setupCharge, setupCharge, `${name} :: setupCharge`);
  close(r.materialCost, materialCost, `${name} :: materialCost`);
  close(r.laborCost, laborCost, `${name} :: laborCost`);
  close(r.packagingCost, packagingCost, `${name} :: packagingCost`);
  close(r.overhead, overhead, `${name} :: overhead`);
  close(r.totalCost, totalCost, `${name} :: totalCost`);
  close(r.salesAmount, salesAmount, `${name} :: salesAmount`);
  close(r.unitPrice, unitPrice, `${name} :: unitPrice`);
  close(r.profit, profit, `${name} :: profit`);
  close(r.margin, salesAmount ? profit / salesAmount : 0, `${name} :: margin`);
}

// ── Independent In-Line re-derivation ───────────────────────────────────────────
function verifyInline(name, f) {
  const width = Number(f.width), lengthIn = Number(f.lengthIn), gauge = Number(f.gauge), qty = Number(f.qty);
  const resinCost = Number(f.resinCost), scrapRate = Number(f.scrapRate), operators = Number(f.operators);
  const overheadPct = Number(f.overheadPct);
  const rho = density(f.resinType);
  const laborRate = DATA.laborRates.inline;

  const cubicIn = width * (lengthIn + 0.375) * gauge * 2;
  const weightPerThou = (cubicIn * 16.387 * rho) / 453.6 * 1000;
  const orderWeight = qty * weightPerThou / 1000;
  const totalWithScrap = orderWeight + scrapRate * orderWeight;

  const prodW = bucketFloor(width, DATA.prodWidthBuckets);
  const prodRate = DATA.inlineRateTable[gaugeBucket(gauge)][DATA.prodWidthBuckets.indexOf(prodW)];
  const laborHours = orderWeight / prodRate;
  const laborCost = operators * laborHours * laborRate;

  const setupW = bucketFloor(width, DATA.setupWidthBuckets);
  const setupIdx = DATA.setupWidthBuckets.indexOf(setupW);
  const setupLbsRaw = DATA.setupLbsTable[f.filmType][setupIdx];
  const setupLbs = setupLbsRaw === 'NA' ? 0 : Number(setupLbsRaw);
  const setupOps = Number(DATA.setupOpsExtrusion[setupIdx]) + Number(DATA.inlineAdditionalOps[setupIdx]);
  const setupHours = Number(DATA.setupHours[f.filmType]);
  const setupCost = setupLbs * resinCost + setupOps * laborRate * setupHours;
  const minSetupFee = Number(DATA.minimumSetupFees[setupIdx]);
  const setupCharge = Number(f.customSetupCharge) > 0 ? Number(f.customSetupCharge) : Math.max(setupCost, minSetupFee);

  const materialCost = totalWithScrap * resinCost + Number(f.enclosureCharge || 0);
  const packagingCost = pkgRate(f.packaging) * (materialCost + laborCost) + Number(f.customPackagingFee || 0);
  const specialty = Number(f.specialtyCharge || 0);
  const directCogs = setupCharge + materialCost + laborCost + packagingCost + specialty;
  const overhead = directCogs * overheadPct;
  const totalCost = directCogs + overhead;

  const finalMargin = Number(f.profitMargin) + Number(f.upcharge);
  const salesAmount = totalCost / (1 - finalMargin);
  const unitPrice = qty > 0 ? salesAmount / qty * 1000 : 0;
  const profit = salesAmount - totalCost;

  const r = calculateInline(f);
  close(r.setupCharge, setupCharge, `${name} :: setupCharge`);
  close(r.materialCost, materialCost, `${name} :: materialCost`);
  close(r.laborCost, laborCost, `${name} :: laborCost`);
  close(r.packagingCost, packagingCost, `${name} :: packagingCost`);
  close(r.overhead, overhead, `${name} :: overhead`);
  close(r.totalCost, totalCost, `${name} :: totalCost`);
  close(r.salesAmount, salesAmount, `${name} :: salesAmount`);
  close(r.unitPrice, unitPrice, `${name} :: unitPrice`);
  close(r.profit, profit, `${name} :: profit`);
  close(r.margin, salesAmount ? profit / salesAmount : 0, `${name} :: margin`);
}

// ── Independent Zipper re-derivation ────────────────────────────────────────────
function verifyZipper(name, f) {
  const width = Number(f.width), lengthIn = Number(f.lengthIn), lipIn = Number(f.lipIn), gauge = Number(f.gauge), qty = Number(f.qty);
  const rho = density(f.resinType);

  const layflatWidth = width + 0.5;
  const effLen = lengthIn + lipIn;
  const weightPerThou = (layflatWidth * effLen * gauge * 2 * 16.387 * rho) / 453.6 * 1000;
  const orderWeight = qty * weightPerThou / 1000;
  const totalWithScrap = orderWeight + Number(f.totalScrapRate) * orderWeight;

  const thicknessKey = String(lipIn >= 0.006 ? 0.006 : lipIn);
  const extW = bucketFloor(effLen, DATA.prodWidthBuckets);
  const extrusionRate = DATA.extrusionRateTable[thicknessKey][DATA.prodWidthBuckets.indexOf(extW)];
  const extLaborHours = orderWeight / extrusionRate;
  const extLaborCost = Number(f.extrusionOperators) * extLaborHours * DATA.laborRates.zipperExtrusion;

  const setupW = bucketFloor(effLen, DATA.setupWidthBuckets);
  const setupIdx = DATA.setupWidthBuckets.indexOf(setupW);
  const setupLbsRaw = DATA.setupLbsTable['PE/PP Color (Opaque)'][setupIdx];
  const setupLbs = setupLbsRaw === 'NA' ? 0 : Number(setupLbsRaw);
  const setupOps = Number(DATA.setupOpsExtrusion[setupIdx]);
  const setupHours = Number(DATA.setupHours[f.filmType]);
  const extSetupCost = setupLbs * Number(f.resinCost) + setupOps * DATA.laborRates.zipperExtrusion * setupHours;
  const extMinFee = Number(DATA.minimumSetupFees[setupIdx]);
  const extSetupCharge = Number(f.customExtrusionSetupCharge) > 0 ? Number(f.customExtrusionSetupCharge) : Math.max(extSetupCost, extMinFee);

  const extMaterialCost = totalWithScrap * Number(f.resinCost) + Number(f.enclosureCharge || 0);
  const extSpecialty = Number(f.specialtyCharge || 0);
  const extCogs = extSetupCharge + extMaterialCost + extLaborCost + extSpecialty;

  const convW = bucketFloor(layflatWidth, DATA.zipperWidthBuckets);
  const qtyPerHour = DATA.zipperQtyPerHour[DATA.zipperWidthBuckets.indexOf(convW)];
  const zipperNeededFt = (qty + qty * Number(f.conversionScrapRate)) * layflatWidth / 12;
  const convLaborHours = Math.max(0.5, qty / qtyPerHour);
  const convLaborCost = convLaborHours * DATA.laborRates.zipperConversion * Number(f.conversionOperators);

  const convSetupQty = 250;
  const convSetupResinCost = (extMaterialCost / qty) * convSetupQty;
  const convSetupCost = (0.5 * DATA.laborRates.zipperConversion * 2) + convSetupResinCost;
  const convSetupCharge = Number(f.customConversionSetupCharge) > 0 ? Number(f.customConversionSetupCharge) : Math.max(convSetupCost, 100);

  const zipperMaterialCost = zipperNeededFt * Number(f.zipperCostPerFt);
  const packagingCost = pkgRate(f.packaging) * (extMaterialCost + extLaborCost) + Number(f.customPackagingFee || 0);
  const convCogs = convSetupCharge + zipperMaterialCost + convLaborCost + packagingCost;

  const directCogs = extCogs + convCogs;
  const overhead = directCogs * Number(f.overheadPct);
  const totalCost = directCogs + overhead;

  const finalMargin = Number(f.profitMargin) + Number(f.cleanroomUpcharge);
  const salesAmount = totalCost / (1 - finalMargin);
  const unitPrice = qty > 0 ? salesAmount / qty * 1000 : 0;
  const profit = salesAmount - totalCost;

  const r = calculateZipper(f);
  close(r.setupCharge, extSetupCharge + convSetupCharge, `${name} :: setupCharge`);
  close(r.materialCost, extMaterialCost + zipperMaterialCost, `${name} :: materialCost`);
  close(r.laborCost, extLaborCost + convLaborCost, `${name} :: laborCost`);
  close(r.packagingCost, packagingCost, `${name} :: packagingCost`);
  close(r.overhead, overhead, `${name} :: overhead`);
  close(r.totalCost, totalCost, `${name} :: totalCost`);
  close(r.salesAmount, salesAmount, `${name} :: salesAmount`);
  close(r.unitPrice, unitPrice, `${name} :: unitPrice`);
  close(r.profit, profit, `${name} :: profit`);
  close(r.margin, salesAmount ? profit / salesAmount : 0, `${name} :: margin`);
}

// ── Scenario generation: 50 total, deterministic (not random, so failures reproduce
//    identically every run) -- spans bucket boundaries + every categorical option ──
const filmTypes = DATA.filmTypes;
const resinTypes = DATA.resinTypes;
const packagingTypes = Object.keys(DATA.packaging);
const formulas = DATA.formulaOptions;
const widthBoundaries = [1, 2, 4, 6, 8, 10, 12, 16, 18, 24, 30, 36, 48, 60, 5, 59];
const gauges = [0.002, 0.0025, 0.003, 0.0035, 0.004, 0.0045, 0.005, 0.0055, 0.006, 0.007];
const qtys = [1, 5, 50, 500, 2500, 10000, 50000];

function nextFrom(arr, i) { return arr[i % arr.length]; }

console.log('Tubing (17 scenarios)');
for (let i = 0; i < 17; i++) {
  const f = {
    filmType: nextFrom(filmTypes, i), resinType: nextFrom(resinTypes, i * 3 + 1),
    formula: nextFrom(formulas, i), width: nextFrom(widthBoundaries, i * 2),
    lengthFt: [50, 100, 250, 500, 1000][i % 5], gauge: nextFrom(gauges, i),
    qty: nextFrom(qtys, i * 2 + 1),
    resinCost: [0.5, 0.98, 1.08, 1.5, 2.0, 2.81][i % 6],
    operators: [1, 1.5, 2, 2.5, 3][i % 5], scrapRate: [0.05, 0.1, 0.15, 0.2, 0.25][i % 5],
    packaging: nextFrom(packagingTypes, i), customPackagingFee: i % 4 === 0 ? 25 : 0,
    specialtyCharge: i % 5 === 0 ? 40 : 0, customSetupCharge: i % 3 === 0 ? 300 : 0,
    profitMargin: [0.3, 0.4, 0.5][i % 3], upcharge: [0.05, 0.1, 0.15, 0.2][i % 4],
    targetUnitPrice: 100, overheadPct: [0.10, 0.15, 0.20][i % 3],
  };
  verifyTubing(`T${i + 1} w=${f.width} g=${f.gauge} q=${f.qty} film=${f.filmType} pkg=${f.packaging}`, f);
}

console.log('In-Line BSB (17 scenarios)');
for (let i = 0; i < 17; i++) {
  const f = {
    filmType: nextFrom(filmTypes, i + 2), resinType: nextFrom(resinTypes, i * 5 + 2),
    formula: nextFrom(formulas, i + 3), width: nextFrom(widthBoundaries, i * 3 + 1),
    lengthIn: [3, 6, 12, 24, 36, 48][i % 6], gauge: nextFrom(gauges, i + 2),
    qty: nextFrom(qtys, i * 3 + 2),
    resinCost: [0.5, 1.08, 1.16, 1.93, 2.0, 2.81][i % 6],
    operators: [1, 1.5, 2, 2.5][i % 4], scrapRate: [0.1, 0.15, 0.2][i % 3],
    packaging: nextFrom(packagingTypes, i + 1), customPackagingFee: i % 5 === 0 ? 15 : 0,
    enclosureCharge: i % 4 === 0 ? 61 : 0, specialtyCharge: i % 6 === 0 ? 30 : 0,
    customSetupCharge: i % 3 === 1 ? 450 : 0,
    profitMargin: [0.4, 0.5, 0.6][i % 3], upcharge: [0, 0.05, 0.1][i % 3],
    targetUnitPrice: 200, overheadPct: [0.10, 0.15, 0.20][i % 3],
  };
  verifyInline(`I${i + 1} w=${f.width} g=${f.gauge} q=${f.qty} film=${f.filmType} pkg=${f.packaging}`, f);
}

console.log('Zipper (16 scenarios)');
for (let i = 0; i < 16; i++) {
  const f = {
    filmType: nextFrom(filmTypes, i + 4), resinType: nextFrom(resinTypes, i * 7 + 3),
    formula: nextFrom(formulas, i + 5), width: nextFrom(widthBoundaries, i * 4),
    lengthIn: [6, 10, 12, 18, 24][i % 5], lipIn: [0.004, 0.005, 0.006, 0.007, 0.008][i % 5],
    gauge: nextFrom(gauges, i + 4), qty: nextFrom(qtys, i * 4 + 1),
    resinCost: [0.5, 1.145, 1.35, 1.925, 2.5][i % 5],
    extrusionOperators: [1, 1.5, 2][i % 3], conversionType: '1up Zipper',
    zipperCostPerFt: [0.008, 0.01, 0.012][i % 3], conversionOperators: [1, 2, 3][i % 3],
    extrusionScrapRate: 0.1, conversionScrapRate: [0.05, 0.1, 0.15][i % 3],
    totalScrapRate: 0.15, packaging: nextFrom(packagingTypes, i + 2),
    customPackagingFee: i % 4 === 0 ? 20 : 0, enclosureCharge: i % 5 === 0 ? 40 : 0,
    specialtyCharge: i % 6 === 0 ? 25 : 0,
    customExtrusionSetupCharge: i % 3 === 0 ? 200 : 0,
    customConversionSetupCharge: i % 4 === 2 ? 150 : 0,
    profitMargin: [0.3, 0.4, 0.5][i % 3], cleanroomUpcharge: [0.05, 0.1, 0.15][i % 3],
    targetUnitPrice: 300, overheadPct: [0.10, 0.15, 0.20][i % 3],
  };
  verifyZipper(`Z${i + 1} w=${f.width} lip=${f.lipIn} q=${f.qty} film=${f.filmType} pkg=${f.packaging}`, f);
}

console.log('\n' + '='.repeat(60));
console.log(`${pass} assertions passed, ${fail} failed (50 scenarios, 10 fields each)`);
if (failures.length) {
  console.log('\nFailures:');
  failures.forEach(f => console.log(f));
  process.exitCode = 1;
} else {
  console.log('All 50 scenarios: independent re-derivation matches the live engine exactly.');
}
