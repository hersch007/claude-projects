const fs = require('fs');
const path = require('path');

// Render's "Secret Files" are always readable at /etc/secrets/<filename>,
// regardless of a service's configured Root Directory — which doesn't
// necessarily match this package's own directory layout. Local dev keeps
// using the plain file next to the caller; production falls back to the
// Render path when the local one isn't there.
function resolveSecretPath(localPath) {
  if (fs.existsSync(localPath)) return localPath;
  const renderPath = path.join('/etc/secrets', path.basename(localPath));
  if (fs.existsSync(renderPath)) return renderPath;
  return localPath;
}

module.exports = { resolveSecretPath };
