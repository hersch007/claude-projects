const fs   = require('fs');
const path = require('path');
const zlib = require('zlib');

const src     = path.resolve(__dirname, 'nad-core');
const outPath = path.resolve(__dirname, 'nad-core-1.0.0.zip');

function walkSync(dir, base) {
  const results = [];
  for (const entry of fs.readdirSync(dir)) {
    const full = path.join(dir, entry);
    const rel  = base ? `${base}/${entry}` : entry;
    if (fs.statSync(full).isDirectory()) {
      results.push(...walkSync(full, rel));
    } else {
      results.push({ full, rel });
    }
  }
  return results;
}

const files = walkSync(src, 'nad-core');

function uint16LE(n) { const b = Buffer.alloc(2); b.writeUInt16LE(n); return b; }
function uint32LE(n) { const b = Buffer.alloc(4); b.writeUInt32LE(n >>> 0); return b; }

function crc32(buf) {
  let crc = 0xFFFFFFFF;
  const table = crc32.table || (crc32.table = (() => {
    const t = new Uint32Array(256);
    for (let i = 0; i < 256; i++) {
      let c = i;
      for (let j = 0; j < 8; j++) c = (c & 1) ? 0xEDB88320 ^ (c >>> 1) : c >>> 1;
      t[i] = c;
    }
    return t;
  })());
  for (let i = 0; i < buf.length; i++) crc = table[(crc ^ buf[i]) & 0xFF] ^ (crc >>> 8);
  return (crc ^ 0xFFFFFFFF) >>> 0;
}

const parts = [];
const centralDir = [];
let offset = 0;

for (const { full, rel } of files) {
  const data       = fs.readFileSync(full);
  const compressed = zlib.deflateRawSync(data, { level: 6 });
  const useDeflate = compressed.length < data.length;
  const payload    = useDeflate ? compressed : data;
  const crc        = crc32(data);
  const nameBuf    = Buffer.from(rel, 'utf8');

  const lfh = Buffer.concat([
    Buffer.from([0x50,0x4B,0x03,0x04]),
    uint16LE(20), uint16LE(0), uint16LE(useDeflate ? 8 : 0),
    uint16LE(0), uint16LE(0),
    uint32LE(crc), uint32LE(payload.length), uint32LE(data.length),
    uint16LE(nameBuf.length), uint16LE(0), nameBuf,
  ]);

  centralDir.push({ nameBuf, crc, compSize: payload.length, origSize: data.length, method: useDeflate ? 8 : 0, offset });
  parts.push(lfh, payload);
  offset += lfh.length + payload.length;
}

const cdParts = centralDir.map(e => Buffer.concat([
  Buffer.from([0x50,0x4B,0x01,0x02]),
  uint16LE(20), uint16LE(20), uint16LE(0), uint16LE(e.method),
  uint16LE(0), uint16LE(0),
  uint32LE(e.crc), uint32LE(e.compSize), uint32LE(e.origSize),
  uint16LE(e.nameBuf.length), uint16LE(0), uint16LE(0),
  uint16LE(0), uint16LE(0), uint32LE(0), uint32LE(e.offset),
  e.nameBuf,
]));

const cd   = Buffer.concat(cdParts);
const eocd = Buffer.concat([
  Buffer.from([0x50,0x4B,0x05,0x06]),
  uint16LE(0), uint16LE(0),
  uint16LE(centralDir.length), uint16LE(centralDir.length),
  uint32LE(cd.length), uint32LE(offset), uint16LE(0),
]);

fs.writeFileSync(outPath, Buffer.concat([...parts, cd, eocd]));
console.log(`Written: ${outPath}`);
console.log(`Size: ${(fs.statSync(outPath).size/1024).toFixed(1)} KB`);
files.forEach(f => console.log(' ', f.rel));
