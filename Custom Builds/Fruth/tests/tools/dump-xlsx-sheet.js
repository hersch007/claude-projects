const fs = require('fs');
const path = require('path');

const base = process.argv[2]; // path to xlsx_raw dir
const sheetArg = process.argv[3]; // sheetN.xml

function readFile(p) {
  return fs.readFileSync(path.join(base, p), 'utf8');
}

// Parse sharedStrings.xml -> array of plain text strings
function parseSharedStrings() {
  let xml;
  try { xml = readFile('xl/sharedStrings.xml'); } catch (e) { return []; }
  const items = [];
  const siRegex = /<si>([\s\S]*?)<\/si>/g;
  let m;
  while ((m = siRegex.exec(xml))) {
    const chunk = m[1];
    // concatenate all <t> text nodes (handles rich text runs)
    const tRegex = /<t[^>]*>([\s\S]*?)<\/t>/g;
    let t, text = '';
    while ((t = tRegex.exec(chunk))) {
      text += t[1];
    }
    text = text.replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"').replace(/&apos;/g, "'").replace(/&amp;/g, '&');
    items.push(text);
  }
  return items;
}

function decodeXml(s) {
  return s.replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"').replace(/&apos;/g, "'").replace(/&amp;/g, '&');
}

const sharedStrings = parseSharedStrings();

function parseSheet(sheetFile) {
  const xml = readFile(path.join('xl/worksheets', sheetFile));
  const rowRegex = /<row[^>]*r="(\d+)"[^>]*>([\s\S]*?)<\/row>/g;
  const cellRegex = /<c\s+([^>]*)>([\s\S]*?)<\/c>|<c\s+([^>]*)\/>/g;
  let rowM;
  const rows = [];
  while ((rowM = rowRegex.exec(xml))) {
    const rowNum = rowM[1];
    const rowContent = rowM[2];
    let cellM;
    const cells = [];
    cellRegex.lastIndex = 0;
    while ((cellM = cellRegex.exec(rowContent))) {
      const attrs = cellM[1] !== undefined ? cellM[1] : cellM[3];
      const inner = cellM[2] || '';
      const refMatch = /r="([^"]+)"/.exec(attrs);
      const typeMatch = /t="([^"]+)"/.exec(attrs);
      const ref = refMatch ? refMatch[1] : '?';
      const type = typeMatch ? typeMatch[1] : 'n';
      const fMatch = /<f[^>]*>([\s\S]*?)<\/f>/.exec(inner);
      const vMatch = /<v>([\s\S]*?)<\/v>/.exec(inner);
      const formula = fMatch ? decodeXml(fMatch[1]) : null;
      let value = vMatch ? vMatch[1] : null;
      if (type === 's' && value !== null) {
        value = sharedStrings[parseInt(value, 10)];
      }
      if (formula || (value !== null && value !== '')) {
        cells.push({ ref, formula, value, type });
      }
    }
    if (cells.length) rows.push({ row: rowNum, cells });
  }
  return rows;
}

const rows = parseSheet(sheetArg);
for (const r of rows) {
  for (const c of r.cells) {
    let out = c.ref;
    if (c.formula) out += `\tFORMULA: ${c.formula}\tVALUE: ${c.value}`;
    else out += `\tVALUE: ${c.value}`;
    console.log(out);
  }
}
