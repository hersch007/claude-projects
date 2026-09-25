/**
 * DocuSign embedded signing with your existing template.
 *
 * Sign-in uses DocuSign's "JWT grant": the script signs in as one DocuSign user
 * (the account admin who approved access once) with an RSA key from the DocuSign
 * app settings. Each client becomes an "embedded" signer (clientUserId = link ID),
 * so DocuSign never emails them. They sign on the page we open from our link.
 */

function docusignReady_(c) {
  return !!(c.DS_KEY && c.DS_USER && c.DS_PRIVATE && c.DS_TEMPLATE && c.DS_ROLE);
}

function dsAuthHost_() {
  return cfg_().DS_ENV === 'production' ? 'account.docusign.com' : 'account-d.docusign.com';
}

function dsConsentUrl_() {
  var c = cfg_();
  if (!c.DS_KEY) throw new Error('Enter the DocuSign Integration Key first.');
  if (!c.WEBAPP_URL) throw new Error('Enter the client page address (web app URL) first.');
  return 'https://' + dsAuthHost_() + '/oauth/auth?response_type=code' +
    '&scope=' + encodeURIComponent('signature impersonation') +
    '&client_id=' + encodeURIComponent(c.DS_KEY) +
    '&redirect_uri=' + encodeURIComponent(c.WEBAPP_URL);
}

/* ---------- sign-in ---------- */

function b64url_(bytesOrString) {
  return Utilities.base64EncodeWebSafe(bytesOrString).replace(/=+$/, '');
}

/**
 * DocuSign hands out keys as "BEGIN RSA PRIVATE KEY" (PKCS#1). Apps Script needs
 * "BEGIN PRIVATE KEY" (PKCS#8). Wrap it so nobody has to run openssl.
 */
function toPkcs8Pem_(pem) {
  pem = String(pem || '').replace(/\\n/g, '\n').trim();
  if (pem.indexOf('BEGIN PRIVATE KEY') >= 0) return pem;
  if (pem.indexOf('BEGIN RSA PRIVATE KEY') < 0) throw new Error('The DocuSign private key doesn’t look right. Paste the whole key, including the BEGIN and END lines.');
  var b64 = pem.replace(/-----[^-]+-----/g, '').replace(/\s+/g, '');
  var pkcs1 = Utilities.base64Decode(b64).map(function (b) { return b & 0xff; });
  var derLen = function (n) {
    if (n < 128) return [n];
    var out = [];
    while (n > 0) { out.unshift(n & 0xff); n = n >> 8; }
    return [0x80 | out.length].concat(out);
  };
  var version = [0x02, 0x01, 0x00];
  var algId = [0x30, 0x0d, 0x06, 0x09, 0x2a, 0x86, 0x48, 0x86, 0xf7, 0x0d, 0x01, 0x01, 0x01, 0x05, 0x00];
  var octet = [0x04].concat(derLen(pkcs1.length), pkcs1);
  var inner = version.concat(algId, octet);
  var der = [0x30].concat(derLen(inner.length), inner).map(function (b) { return b > 127 ? b - 256 : b; });
  var body = Utilities.base64Encode(der).replace(/(.{64})/g, '$1\n').trim();
  return '-----BEGIN PRIVATE KEY-----\n' + body + '\n-----END PRIVATE KEY-----\n';
}

function dsAccessToken_() {
  var cache = CacheService.getScriptCache();
  var cached = cache.get('ds_access');
  if (cached) return cached;
  var c = cfg_();
  if (!docusignReady_(c)) throw new Error('DocuSign isn’t set up yet. Add its details in Settings.');

  var now = Math.floor(Date.now() / 1000);
  var header = b64url_(JSON.stringify({ alg: 'RS256', typ: 'JWT' }));
  var claims = b64url_(JSON.stringify({
    iss: c.DS_KEY, sub: c.DS_USER, aud: dsAuthHost_(),
    iat: now, exp: now + 3600, scope: 'signature impersonation'
  }));
  var sig = b64url_(Utilities.computeRsaSha256Signature(header + '.' + claims, toPkcs8Pem_(c.DS_PRIVATE)));

  var res = UrlFetchApp.fetch('https://' + dsAuthHost_() + '/oauth/token', {
    method: 'post',
    payload: {
      grant_type: 'urn:ietf:params:oauth:grant-type:jwt-bearer',
      assertion: header + '.' + claims + '.' + sig
    },
    muteHttpExceptions: true
  });
  var json = JSON.parse(res.getContentText() || '{}');
  if (res.getResponseCode() !== 200) {
    if (json.error === 'consent_required') {
      throw new Error('DocuSign needs a one-time approval. In Settings, click "Approve DocuSign access".');
    }
    throw new Error('DocuSign sign-in failed: ' + (json.error_description || json.error || res.getResponseCode()));
  }
  cache.put('ds_access', json.access_token, Math.max(60, (json.expires_in || 3600) - 300));
  return json.access_token;
}

/** Account ID and API address, looked up once and remembered. */
function dsAccount_() {
  var p = PropertiesService.getScriptProperties();
  var id = p.getProperty('DS_ACCOUNT_ID'), base = p.getProperty('DS_BASE_URI');
  if (id && base) return { id: id, base: base };
  var res = UrlFetchApp.fetch('https://' + dsAuthHost_() + '/oauth/userinfo', {
    headers: { Authorization: 'Bearer ' + dsAccessToken_() }, muteHttpExceptions: true
  });
  if (res.getResponseCode() !== 200) throw new Error('Couldn’t read the DocuSign account (' + res.getResponseCode() + ').');
  var accts = JSON.parse(res.getContentText()).accounts || [];
  var acct = accts.filter(function (a) { return a.is_default; })[0] || accts[0];
  if (!acct) throw new Error('This DocuSign user has no account.');
  p.setProperty('DS_ACCOUNT_ID', acct.account_id);
  p.setProperty('DS_BASE_URI', acct.base_uri);
  return { id: acct.account_id, base: acct.base_uri, name: acct.account_name };
}

function dsApi_(method, path, body, raw) {
  var a = dsAccount_();
  var opts = {
    method: method,
    headers: { Authorization: 'Bearer ' + dsAccessToken_() },
    muteHttpExceptions: true
  };
  if (body) { opts.contentType = 'application/json'; opts.payload = JSON.stringify(body); }
  var res = UrlFetchApp.fetch(a.base + '/restapi/v2.1/accounts/' + a.id + path, opts);
  var code = res.getResponseCode();
  if (code >= 300) {
    var msg = res.getContentText();
    try { var j = JSON.parse(msg); msg = j.message || j.errorCode || msg; } catch (e) { /* keep text */ }
    throw new Error('DocuSign error (' + code + '): ' + msg);
  }
  return raw ? res : JSON.parse(res.getContentText() || '{}');
}

/* ---------- signing ---------- */

function dsSignerEmail_(link) {
  return link.email || cfg_().DS_FALLBACK_EMAIL || Session.getEffectiveUser().getEmail();
}

/** Creates the envelope from the template the first time; reuses it after. */
function dsEnsureEnvelope_(link) {
  if (link.envelopeId) return link.envelopeId;
  var c = cfg_();
  var env = dsApi_('post', '/envelopes', {
    templateId: c.DS_TEMPLATE,
    status: 'sent',
    emailSubject: 'Please sign: ' + (c.AGENCY_NAME || 'your form'),
    templateRoles: [{
      roleName: c.DS_ROLE,
      name: link.name,
      email: dsSignerEmail_(link),
      clientUserId: link.id
    }]
  });
  return env.envelopeId;
}

/** A one-time, 5-minute signing address for this client. */
function dsSigningUrl_(link) {
  var c = cfg_();
  var view = dsApi_('post', '/envelopes/' + link.envelopeId + '/views/recipient', {
    returnUrl: c.WEBAPP_URL + (c.WEBAPP_URL.indexOf('?') >= 0 ? '&' : '?') + 't=' + link.id + '&ds=1',
    authenticationMethod: 'none',
    email: dsSignerEmail_(link),
    userName: link.name,
    clientUserId: link.id
  });
  return view.url;
}

function dsSignerCompleted_(link) {
  var r = dsApi_('get', '/envelopes/' + link.envelopeId + '/recipients');
  return (r.signers || []).some(function (s) {
    return s.clientUserId === link.id && s.status === 'completed';
  });
}

function dsSignedPdf_(link) {
  return dsApi_('get', '/envelopes/' + link.envelopeId + '/documents/combined', null, true)
    .getBlob().setContentType('application/pdf');
}

/** Settings → "Test DocuSign": sign in, find the account, template and role. */
function dsDescribeSetup_() {
  var c = cfg_();
  if (!docusignReady_(c)) throw new Error('Fill in all the DocuSign fields and save first.');
  var a = dsAccount_();
  var t = dsApi_('get', '/templates/' + c.DS_TEMPLATE + '?include=recipients');
  var roles = ((t.recipients && t.recipients.signers) || []).map(function (s) { return s.roleName; });
  var ok = roles.indexOf(c.DS_ROLE) >= 0;
  return 'Connected to DocuSign account ' + a.id + '. Template: "' + t.name + '". ' +
    (ok ? 'Role "' + c.DS_ROLE + '" found. Ready to go.'
        : 'Role "' + c.DS_ROLE + '" was NOT found. The template’s roles are: ' + (roles.join(', ') || 'none') + '.');
}
