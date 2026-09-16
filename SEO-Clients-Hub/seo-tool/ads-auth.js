// Run this once: node ads-auth.js
// Mirrors gsc-auth.js's exact pattern, just for the Google Ads API's scope
// instead of Search Console's — reuses the same OAuth client file
// (gsc-oauth-client.json), since it's the same Google Cloud project; you
// only need to make sure that OAuth client's consent screen includes the
// https://www.googleapis.com/auth/adwords scope (Google Cloud Console →
// APIs & Services → OAuth consent screen → Data Access → add scope) before
// running this, or the consent screen won't offer it.
// It opens a browser, you log in with your Google account (must have
// access to the GroupRB MCC, 169-720-1017), and saves a separate token
// file. After that, keyword volume lookups work automatically.

const { google } = require('googleapis');
const fs = require('fs');
const path = require('path');
const http = require('http');
const { exec } = require('child_process');

const OAUTH_CLIENT_PATH = path.join(__dirname, 'gsc-oauth-client.json');
const TOKEN_PATH = path.join(__dirname, 'ads-token.json');
const SCOPES = ['https://www.googleapis.com/auth/adwords'];
const PORT = 3457; // different port than gsc-auth.js's 3456, in case both are ever run close together

if (!fs.existsSync(OAUTH_CLIENT_PATH)) {
  console.error(`\nMissing: ${OAUTH_CLIENT_PATH}`);
  console.error('This should already exist from the GSC setup — run gsc-auth.js first if not.\n');
  process.exit(1);
}

const { client_id, client_secret } = JSON.parse(fs.readFileSync(OAUTH_CLIENT_PATH, 'utf8')).installed;
const redirectUri = `http://localhost:${PORT}`;
const oAuth2Client = new google.auth.OAuth2(client_id, client_secret, redirectUri);

const authUrl = oAuth2Client.generateAuthUrl({
  access_type: 'offline',
  scope: SCOPES,
  prompt: 'consent'
});

console.log('\nOpening browser for Google Ads authorization...');
console.log('If it does not open automatically, visit:\n' + authUrl + '\n');

const cmd = process.platform === 'win32' ? `start "" "${authUrl}"` : `open "${authUrl}"`;
exec(cmd);

const server = http.createServer(async (req, res) => {
  const url = new URL(req.url, `http://localhost:${PORT}`);
  const code = url.searchParams.get('code');
  if (!code) { res.end('No code received.'); return; }

  res.end('<h2>Authorization successful! You can close this tab and return to the terminal.</h2>');
  server.close();

  try {
    const { tokens } = await oAuth2Client.getToken(code);
    fs.writeFileSync(TOKEN_PATH, JSON.stringify(tokens, null, 2), 'utf8');
    console.log('✓ Token saved to ads-token.json');
    console.log('  You are now authorized for Keyword Planner lookups.\n');
  } catch (e) {
    console.error('Failed to get token:', e.message);
  }
}).listen(PORT);

console.log(`Waiting for authorization on port ${PORT}...`);
