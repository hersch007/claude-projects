const fs = require('fs');
const path = require('path');

const base = process.argv[2];
const sheetFile = process.argv[3];
const refs = process.argv.slice(4);

function parseSharedStrings() {
  let xml;
  try { xml = fs.readFileSync(path.join(base, 'xl/sharedStrings.xml'), 'utf8'); } catch (e) { return []; }
  const items = [];
  const siRegex = /<si>([\s\S]*?)<\/si>/g;
  let m;
  while ((m = siRegex.exec(xml))) {
    const chunk = m[1];
    const tRegex = /<t[^>]*>([\s\S]*?)<\/t>/g;
    let t, text = '';
    while ((t = tRegex.exec(chunk))) text += t[1];
    text = text.replace(/&lt;/g,'<').replace(/&gt;/g,'>').replace(/&quot;/g,'"').replace(/&apos;/g,"'").replace(/&amp;/g,'&');
    items.push(text);
  }
  return items;
}
const sharedStrings = parseSharedStrings();

function decodeXml(s) { return s.replace(/&lt;/g,'<').replace(/&gt;/g,'>').replace(/&quot;/g,'"').replace(/&apos;/g,"'").replace(/&amp;/g,'&'); }

const xml = fs.readFileSync(path.join(base, 'xl/worksheets', sheetFile), 'utf8');
const cellRegex = /<c\s+([^>]*)>([\s\S]*?)<\/c>|<c\s+([^>]*)\/>/g;
const cells = {};
let m;
while ((m = cellRegex.exec(xml))) {
  const attrs = m[1] !== undefined ? m[1] : m[3];
  const inner = m[2] || '';
  const refMatch = /r="([^"]+)"/.exec(attrs);
  const typeMatch = /t="([^"]+)"/.exec(attrs);
  if (!refMatch) continue;
  const ref = refMatch[1];
  const type = typeMatch ? typeMatch[1] : 'n';
  const fMatch = /<f[^>]*>([\s\S]*?)<\/f>/.exec(inner);
  const vMatch = /<v>([\s\S]*?)<\/v>/.exec(inner);
  const formula = fMatch ? decodeXml(fMatch[1]) : null;
  let value = vMatch ? vMatch[1] : null;
  if (type === 's' && value !== null) value = sharedStrings[parseInt(value,10)];
  cells[ref] = { formula, value };
}

for (const ref of refs) {
  const c = cells[ref];
  console.log(`${ref}\t${c ? (c.value===null?'':c.value) : '(empty)'}\t${c && c.formula ? c.formula : ''}`);
}
