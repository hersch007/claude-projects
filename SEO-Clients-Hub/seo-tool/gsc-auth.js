// Run this once: node gsc-auth.js
// It opens a browser, you log in with your Google account, and saves a token file.
// After that, every audit pulls live GSC data automatically.

const { google } = require('googleapis');
const fs = require('fs');
const path = require('path');
const http = require('http');
const { exec } = require('child_process');

const OAUTH_CLIENT_PATH = path.join(__dirname, 'gsc-oauth-client.json');
const TOKEN_PATH = path.join(__dirname, 'gsc-token.json');
const SCOPES = ['https://www.googleapis.com/auth/webmasters.readonly'];
const PORT = 3456;

if (!fs.existsSync(OAUTH_CLIENT_PATH)) {
  console.error(`\nMissing: ${OAUTH_CLIENT_PATH}`);
  console.error('Download the OAuth 2.0 Client ID JSON from Google Cloud Console and save it there.\n');
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

console.log('\nOpening browser for Google authorization...');
console.log('If it does not open automatically, visit:\n' + authUrl + '\n');

// Open browser
const cmd = process.platform === 'win32' ? `start "" "${authUrl}"` : `open "${authUrl}"`;
exec(cmd);

// Temporary local server to catch the redirect
const server = http.createServer(async (req, res) => {
  const url = new URL(req.url, `http://localhost:${PORT}`);
  const code = url.searchParams.get('code');
  if (!code) { res.end('No code received.'); return; }

  res.end('<h2>Authorization successful! You can close this tab and return to the terminal.</h2>');
  server.close();

  try {
    const { tokens } = await oAuth2Client.getToken(code);
    fs.writeFileSync(TOKEN_PATH, JSON.stringify(tokens, null, 2), 'utf8');
    console.log('✓ Token saved to gsc-token.json');
    console.log('  You are now authorized. Run any audit to pull live GSC data.\n');
  } catch (e) {
    console.error('Failed to get token:', e.message);
  }
}).listen(PORT);

console.log(`Waiting for authorization on port ${PORT}...`);
