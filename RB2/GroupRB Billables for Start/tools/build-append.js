#!/usr/bin/env node
/*
 * build-append.js — Turn a day's raw rows into a per-client payload for the
 * monthly matrix in Start Billables.xlsx.
 *
 * Reads:  daily/YYYY-MM-DD.json  (make-daily's input; may include non-billable
 *         rows: { client, note, job, billable:false })
 *         tools/client-map.json  (code -> billableName / nonBillableName)
 *
 * Writes: daily/append-YYYY-MM-DD.json = { date, employee,
 *           billable:    { "<client header>": hours, ... },
 *           nonBillable: { "<client header>": hours, ... } }
 *         Names match the matrix column headers; append-month.ps1 resolves them
 *         to columns at write-time (header-based, layout-independent).
 *
 * Errors (non-zero exit) if any client code is unknown or its target name is
 * null — so a day is never silently mis-posted into the billing file.
 *
 * Usage:  node tools/build-append.js daily/YYYY-MM-DD.json
 */
'use strict';
const fs = require('fs');
const path = require('path');

const inputPath = process.argv[2];
if (!inputPath) { console.error('Usage: node build-append.js <daily.json>'); process.exit(1); }

const data = JSON.parse(fs.readFileSync(inputPath, 'utf8'));
const map = JSON.parse(fs.readFileSync(path.join(__dirname, 'client-map.json'), 'utf8'));
const HRS = (data.minutesPerBlock || 15) / 60;

const billable = {};
const nonBillable = {};
const errors = [];
const seenCodes = new Set();

for (const r of data.rows || []) {
  const code = (r.client || '').trim();
  if (!code) continue;                                  // empty slot
  seenCodes.add(code);
  const job = (r.job == null ? '' : String(r.job)).trim();
  const isBillable = r.billable === undefined ? job !== '' : !!r.billable;
  const entry = map.codes[code];
  if (!entry) { errors.push(`Unknown client code "${code}" — add it to tools/client-map.json`); continue; }
  const name = isBillable ? entry.billableName : entry.nonBillableName;
  if (!name) {
    errors.push(`Code "${code}" has no ${isBillable ? 'billableName' : 'nonBillableName'} in client-map.json (row note: "${r.note || ''}")`);
    continue;
  }
  const bucket = isBillable ? billable : nonBillable;
  bucket[name] = Math.round(((bucket[name] || 0) + HRS) * 100) / 100;
}

if (errors.length) {
  console.error('CANNOT BUILD APPEND PAYLOAD:\n  - ' + [...new Set(errors)].join('\n  - '));
  process.exit(2);
}

const out = { date: data.date, employee: data.employee || '', billable, nonBillable };
const outPath = path.join(path.dirname(inputPath), `append-${data.date}.json`);
fs.writeFileSync(outPath, JSON.stringify(out, null, 2));
console.log(`Wrote ${outPath}`);
console.log('  Codes seen: ' + [...seenCodes].join(', '));
for (const [n, v] of Object.entries(billable)) console.log(`  [bill] ${n} = ${v} hr`);
for (const [n, v] of Object.entries(nonBillable)) console.log(`  [n-b]  ${n} = ${v} hr`);
