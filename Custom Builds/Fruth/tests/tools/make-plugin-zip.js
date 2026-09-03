// Minimal dependency-free ZIP writer (store + deflate), used to package
// plugin releases with forward-slash entry names matching the original
// vendor-supplied zips exactly (Windows PowerShell's Compress-Archive was
// producing backslash-separated entries, which some ZIP consumers mishandle).
const fs = require('fs');
const path = require('path');
const zlib = require('zlib');

function crc32(buf) {
  let table = crc32.table;
  if (!table) {
    table = crc32.table = new Uint32Array(256);
    for (let n = 0; n < 256; n++) {
      let c = n;
      for (let k = 0; k < 8; k++) c = (c & 1) ? (0xEDB88320 ^ (c >>> 1)) : (c >>> 1);
      table[n] = c >>> 0;
    }
  }
  let crc = 0xFFFFFFFF;
  for (let i = 0; i < buf.length; i++) crc = table[(crc ^ buf[i]) & 0xFF] ^ (crc >>> 8);
  return (crc ^ 0xFFFFFFFF) >>> 0;
}

function dosDateTime(date) {
  const dosTime = ((date.getHours() & 0x1F) << 11) | ((date.getMinutes() & 0x3F) << 5) | ((date.getSeconds() / 2) & 0x1F);
  const dosDate = (((date.getFullYear() - 1980) & 0x7F) << 9) | (((date.getMonth() + 1) & 0xF) << 5) | (date.getDate() & 0x1F);
  return { dosTime, dosDate };
}

function buildZip(entries) {
  // entries: [{ name: 'folder/', dir: true }, { name: 'folder/file.php', data: Buffer }]
  const localParts = [];
  const centralParts = [];
  let offset = 0;
  const now = new Date();
  const { dosTime, dosDate } = dosDateTime(now);

  for (const entry of entries) {
    const nameBuf = Buffer.from(entry.name.replace(/\\/g, '/'), 'utf8');
    const isDir = !!entry.dir;
    const data = isDir ? Buffer.alloc(0) : entry.data;
    const crc = isDir ? 0 : crc32(data);
    const compressed = isDir ? Buffer.alloc(0) : zlib.deflateRawSync(data);
    const method = isDir ? 0 : 8; // 0=store, 8=deflate
    const useCompressed = !isDir && compressed.length < data.length;
    const finalData = isDir ? Buffer.alloc(0) : (useCompressed ? compressed : data);
    const finalMethod = isDir ? 0 : (useCompressed ? 8 : 0);

    const localHeader = Buffer.alloc(30);
    localHeader.writeUInt32LE(0x04034b50, 0);
    localHeader.writeUInt16LE(20, 4);
    localHeader.writeUInt16LE(0, 6);
    localHeader.writeUInt16LE(finalMethod, 8);
    localHeader.writeUInt16LE(dosTime, 10);
    localHeader.writeUInt16LE(dosDate, 12);
    localHeader.writeUInt32LE(crc, 14);
    localHeader.writeUInt32LE(finalData.length, 18);
    localHeader.writeUInt32LE(data.length, 22);
    localHeader.writeUInt16LE(nameBuf.length, 26);
    localHeader.writeUInt16LE(0, 28);

    localParts.push(localHeader, nameBuf, finalData);

    const centralHeader = Buffer.alloc(46);
    centralHeader.writeUInt32LE(0x02014b50, 0);
    centralHeader.writeUInt16LE(20, 4);
    centralHeader.writeUInt16LE(20, 6);
    centralHeader.writeUInt16LE(0, 8);
    centralHeader.writeUInt16LE(finalMethod, 10);
    centralHeader.writeUInt16LE(dosTime, 12);
    centralHeader.writeUInt16LE(dosDate, 14);
    centralHeader.writeUInt32LE(crc, 16);
    centralHeader.writeUInt32LE(finalData.length, 20);
    centralHeader.writeUInt32LE(data.length, 24);
    centralHeader.writeUInt16LE(nameBuf.length, 28);
    centralHeader.writeUInt16LE(0, 30);
    centralHeader.writeUInt16LE(0, 32);
    centralHeader.writeUInt16LE(0, 34);
    centralHeader.writeUInt16LE(0, 36);
    centralHeader.writeUInt32LE(isDir ? 0x10 : 0, 38); // external attrs: dir flag
    centralHeader.writeUInt32LE(offset, 42);

    centralParts.push(centralHeader, nameBuf);

    offset += localHeader.length + nameBuf.length + finalData.length;
  }

  const centralDir = Buffer.concat(centralParts);
  const localSection = Buffer.concat(localParts);

  const end = Buffer.alloc(22);
  end.writeUInt32LE(0x06054b50, 0);
  end.writeUInt16LE(0, 4);
  end.writeUInt16LE(0, 6);
  end.writeUInt16LE(entries.length, 8);
  end.writeUInt16LE(entries.length, 10);
  end.writeUInt32LE(centralDir.length, 12);
  end.writeUInt32LE(localSection.length, 16);
  end.writeUInt16LE(0, 20);

  return Buffer.concat([localSection, centralDir, end]);
}

function collectEntries(srcDir, zipPrefix, entries) {
  entries.push({ name: zipPrefix + '/', dir: true });
  const files = fs.readdirSync(srcDir);
  for (const f of files) {
    const full = path.join(srcDir, f);
    if (fs.statSync(full).isDirectory()) {
      collectEntries(full, zipPrefix + '/' + f, entries);
    } else {
      entries.push({ name: zipPrefix + '/' + f, data: fs.readFileSync(full) });
    }
  }
}

function zipDirectory(srcDir, folderNameInZip, destZipPath) {
  const entries = [];
  collectEntries(srcDir, folderNameInZip, entries);
  const zipBuf = buildZip(entries);
  fs.writeFileSync(destZipPath, zipBuf);
  return destZipPath;
}

module.exports = { zipDirectory };

if (require.main === module) {
  const [,, srcDir, folderName, destZip] = process.argv;
  if (!srcDir || !folderName || !destZip) {
    console.error('Usage: node make-plugin-zip.js <srcDir> <folderNameInZip> <destZipPath>');
    process.exit(1);
  }
  zipDirectory(srcDir, folderName, destZip);
  console.log('Wrote', destZip);
}
