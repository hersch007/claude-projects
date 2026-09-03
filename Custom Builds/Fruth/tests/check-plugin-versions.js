// Guards against the exact version-string mismatch seen twice in this
// project's history (Core 8.6.1 and Print Module 1.14.1 both shipped with
// the plugin header's "Version:" line ahead of the internal SQS_VERSION /
// SQSP_VERSION constant). WordPress reads the header via a static text scan
// (get_plugin_data()), so the header and the constant are two separate
// string literals that can never be derived from one source -- this script
// is the enforcement instead.
//
// Run:  node tests/check-plugin-versions.js
// Exits 0 if every plugin's header version matches its internal constant,
// 1 otherwise (safe to wire into CI or a pre-release checklist).

const fs = require('fs');
const path = require('path');

const plugins = [
  {
    label: 'Quote Builder Core',
    file: path.join(__dirname, '..', 'plugins', 'core', 'extracted', 'sales-quote-system', 'sales-quote-system.php'),
    constantName: 'SQS_VERSION',
  },
  {
    label: 'Quote Builder Print Module',
    file: path.join(__dirname, '..', 'plugins', 'print-module', 'extracted', 'quote-builder-print', 'quote-builder-print.php'),
    constantName: 'SQSP_VERSION',
  },
  {
    label: 'Quote Builder Customer Database',
    file: path.join(__dirname, '..', 'plugins', 'customer-module', 'extracted', 'quote-builder-customers', 'quote-builder-customers.php'),
    constantName: 'QBC_VERSION',
  },
];

let failed = false;

for (const plugin of plugins) {
  if (!fs.existsSync(plugin.file)) {
    console.log(`? ${plugin.label}: file not found at ${plugin.file} (skipped)`);
    continue;
  }
  const src = fs.readFileSync(plugin.file, 'utf8');

  const headerMatch = /^\s*\*\s*Version:\s*([0-9.]+)/m.exec(src);
  const constantMatch = new RegExp(`define\\(\\s*'${plugin.constantName}'\\s*,\\s*'([0-9.]+)'\\s*\\)`).exec(src);

  if (!headerMatch) {
    console.log(`✗ ${plugin.label}: could not find "Version:" line in plugin header`);
    failed = true;
    continue;
  }
  if (!constantMatch) {
    console.log(`✗ ${plugin.label}: could not find define('${plugin.constantName}', ...) in file`);
    failed = true;
    continue;
  }

  const headerVersion = headerMatch[1];
  const constantVersion = constantMatch[1];

  if (headerVersion !== constantVersion) {
    console.log(`✗ ${plugin.label}: header says Version: ${headerVersion}, but ${plugin.constantName} is '${constantVersion}' -- these must match`);
    failed = true;
  } else {
    console.log(`✓ ${plugin.label}: header and ${plugin.constantName} both agree on ${headerVersion}`);
  }
}

if (failed) {
  console.log('\nVersion consistency check FAILED.');
  process.exitCode = 1;
} else {
  console.log('\nAll plugin headers match their internal version constants.');
}
