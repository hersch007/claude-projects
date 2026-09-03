// Pulls the client-side pricing engine (DATA + calculators) out of the live
// plugin source so regression tests always run against the real, shipped
// code -- never a hand-copied duplicate that could drift from the plugin.
const fs = require('fs');

function extractPricingEngine(pluginPhpPath, defaultDataJsonPath) {
  const php = fs.readFileSync(pluginPhpPath, 'utf8');

  const startMarker = 'const DATA = <?php echo $sqs_data_json; ?>;';
  const endMarker = 'function getResults()';

  const startIdx = php.indexOf(startMarker);
  const endIdx = php.indexOf(endMarker, startIdx);
  if (startIdx === -1 || endIdx === -1) {
    throw new Error('Could not locate pricing engine markers in ' + pluginPhpPath);
  }

  let block = php.slice(startIdx, endIdx);

  // Replace the two PHP interpolation points with static JS equivalents.
  const defaultData = fs.readFileSync(defaultDataJsonPath, 'utf8');
  block = block.replace(
    'const DATA = <?php echo $sqs_data_json; ?>;',
    `const DATA = ${defaultData};`
  );
  block = block.replace(
    /const SQS_QUOTE_AJAX = <\?php[\s\S]*?\?>;/,
    'const SQS_QUOTE_AJAX = {};'
  );

  const wrapped = `
    ${block}
    module.exports = { DATA, calculateTubing, calculateInline, calculateZipper, floorBucket, thicknessBucket };
  `;

  return wrapped;
}

module.exports = { extractPricingEngine };
