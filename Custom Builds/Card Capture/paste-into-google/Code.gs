// Card Capture: paste this whole file into Code.gs in the Apps Script editor.
// (Built from the files in apps-script/. Edit those, then rebuild this.)

/* ======================= Code.gs ======================= */

/**
 * Card Capture
 *
 * Callers text an older client a link. The client photographs the front and
 * back of their insurance card and signs the DocuSign form, all from that one
 * link. Photos and the signed PDF land in a Google Drive folder per client, and
 * the Calls tab of this spreadsheet shows progress.
 *
 * Files:
 *   Code.gs      menu, setup, settings, caller panel API
 *   Client.gs    the web page clients open from the text (doGet + its API)
 *   Links.gs     storage for each client link (hidden "_links" tab)
 *   Twilio.gs    sending text messages
 *   DocuSign.gs  embedded signing with your existing template
 */

var CALLS_SHEET = 'Calls';
var CALLS_HEADERS = ['Client name', 'Cell phone', 'Email (optional)', 'Notes', 'Status',
  'Card front', 'Card back', 'Signed', 'Folder', 'Last update', 'Link ID'];
var C = { NAME: 1, PHONE: 2, EMAIL: 3, NOTES: 4, STATUS: 5, FRONT: 6, BACK: 7,
  SIGNED: 8, FOLDER: 9, UPDATED: 10, LINKID: 11 };

// Settings stored in Script Properties. Secret ones are never sent back to the browser.
var SETTING_KEYS = ['AGENCY_NAME', 'AGENCY_PHONE', 'DRIVE_FOLDER', 'WEBAPP_URL', 'LINK_BASE',
  'EXPIRY_HOURS', 'TW_SID', 'TW_AUTH', 'TW_FROM', 'DS_ENV', 'DS_KEY', 'DS_USER',
  'DS_PRIVATE', 'DS_TEMPLATE', 'DS_ROLE', 'DS_FALLBACK_EMAIL'];
var SECRET_KEYS = ['TW_AUTH', 'DS_PRIVATE'];

/* ---------- menu ---------- */

function onOpen() {
  SpreadsheetApp.getUi().createMenu('Card Capture')
    .addItem('Open caller panel', 'openCallerPanel')
    .addSeparator()
    .addItem('Settings…', 'openSettings')
    .addItem('Set up this spreadsheet', 'setupSpreadsheet')
    .addToUi();
}

function openCallerPanel() {
  requireStaff_();
  var html = HtmlService.createHtmlOutputFromFile('Panel').setTitle('Card Capture');
  SpreadsheetApp.getUi().showSidebar(html);
}

function openSettings() {
  requireStaff_();
  var html = HtmlService.createHtmlOutputFromFile('Settings').setWidth(640).setHeight(720);
  SpreadsheetApp.getUi().showModalDialog(html, 'Card Capture settings');
}

function setupSpreadsheet() {
  requireStaff_();
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  PropertiesService.getScriptProperties().setProperty('SPREADSHEET_ID', ss.getId());

  var sh = ss.getSheetByName(CALLS_SHEET);
  if (!sh) sh = ss.insertSheet(CALLS_SHEET, 0);
  sh.getRange(1, 1, 1, CALLS_HEADERS.length).setValues([CALLS_HEADERS])
    .setFontWeight('bold').setBackground('#E7F0FA');
  sh.setFrozenRows(1);
  sh.getRange('B:B').setNumberFormat('@');
  sh.getRange('K:K').setNumberFormat('@');
  sh.setColumnWidth(C.NAME, 180);
  sh.setColumnWidth(C.STATUS, 230);
  sh.setColumnWidth(C.FOLDER, 110);
  sh.hideColumns(C.LINKID);
  linksSheet_(); // creates the hidden tab if needed

  var p = PropertiesService.getScriptProperties();
  if (!p.getProperty('EXPIRY_HOURS')) p.setProperty('EXPIRY_HOURS', '48');
  if (!p.getProperty('DS_ENV')) p.setProperty('DS_ENV', 'demo');

  SpreadsheetApp.getUi().alert('Ready. Paste your call list into the Calls tab (name and cell phone), ' +
    'then open Card Capture → Settings… to connect Drive, texting and DocuSign.');
}

/* ---------- helpers ---------- */

function cfg_() {
  return PropertiesService.getScriptProperties().getProperties();
}

function ss_() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  if (ss) return ss;
  var id = PropertiesService.getScriptProperties().getProperty('SPREADSHEET_ID');
  if (!id) throw new Error('Run Card Capture → Set up this spreadsheet first.');
  return SpreadsheetApp.openById(id);
}

/**
 * Staff-only functions must be run by a signed-in person as themselves (from the
 * spreadsheet). The public web app runs as the owner for anonymous visitors, so
 * there the active user is blank or differs from the effective user.
 */
function requireStaff_() {
  var active = Session.getActiveUser().getEmail();
  var effective = Session.getEffectiveUser().getEmail();
  if (!active || active !== effective) throw new Error('Open this from the Card Capture menu in the spreadsheet.');
}

function withLock_(fn) {
  var lock = LockService.getScriptLock();
  lock.waitLock(20000);
  try { return fn(); } finally { lock.releaseLock(); }
}

function fmtTime_(ms) {
  if (!ms) return '';
  return Utilities.formatDate(new Date(Number(ms)), Session.getScriptTimeZone(), 'MMM d, h:mm a');
}

function firstName_(name) {
  return String(name || '').trim().split(/\s+/)[0] || 'there';
}

function linkUrl_(id) {
  var c = cfg_();
  var base = c.LINK_BASE || c.WEBAPP_URL;
  if (!base) throw new Error('Add the client page address in Card Capture → Settings… first.');
  return base + (base.indexOf('?') >= 0 ? '&' : '?') + 't=' + id;
}

function rootFolder_() {
  var raw = cfg_().DRIVE_FOLDER || '';
  var m = raw.match(/folders\/([\w-]+)/);
  var id = m ? m[1] : raw.trim();
  if (!id) throw new Error('Choose a Google Drive folder in Card Capture → Settings… first.');
  return DriveApp.getFolderById(id);
}

/* ---------- Calls tab ---------- */

function statusText_(link) {
  var dsOn = docusignReady_(cfg_());
  if (link.signedAt) return 'Complete';
  if (link.backAt && !dsOn) return 'Complete (photos only)';
  if (isExpired_(link)) return 'Link expired';
  if (link.backAt) return 'Card received, waiting for signature';
  if (link.frontAt) return 'Front received, waiting for back';
  if (link.openedAt) return 'Opened link';
  if (link.sentAt) return 'Texted, not opened yet';
  return 'Link ready to send';
}

function updateCallsRow_(link) {
  var sh = ss_().getSheetByName(CALLS_SHEET);
  if (!sh) return;
  var hit = sh.getRange(1, C.LINKID, sh.getMaxRows(), 1).createTextFinder(link.id)
    .matchEntireCell(true).findNext();
  if (!hit) return;
  var r = hit.getRow();
  var folder = link.folderId
    ? '=HYPERLINK("https://drive.google.com/drive/folders/' + link.folderId + '","Open folder")' : '';
  sh.getRange(r, C.STATUS, 1, 6).setValues([[
    statusText_(link),
    link.frontAt ? '✓ ' + fmtTime_(link.frontAt) : '',
    link.backAt ? '✓ ' + fmtTime_(link.backAt) : '',
    link.signedAt ? '✓ ' + fmtTime_(link.signedAt) : '',
    folder,
    fmtTime_(Date.now())
  ]]);
}

/* ---------- caller panel API (runs as the signed-in caller) ---------- */

function panelGetCaller() {
  requireStaff_();
  return PropertiesService.getUserProperties().getProperty('CALLER_NAME') || '';
}

function panelSetCaller(name) {
  requireStaff_();
  PropertiesService.getUserProperties().setProperty('CALLER_NAME', String(name || '').slice(0, 60));
}

/** Called every few seconds by the panel: the selected client and their progress. */
function panelPoll() {
  requireStaff_();
  var sh = SpreadsheetApp.getActiveSheet();
  if (sh.getName() !== CALLS_SHEET) return { hint: 'Go to the Calls tab and click a client’s row.' };
  var row = sh.getActiveRange().getRow();
  if (row < 2) return { hint: 'Click a client’s row on the Calls tab.' };
  var v = sh.getRange(row, 1, 1, CALLS_HEADERS.length).getDisplayValues()[0];
  if (!v[C.NAME - 1]) return { hint: 'This row has no client name yet.' };

  var out = {
    row: row,
    name: v[C.NAME - 1],
    firstName: firstName_(v[C.NAME - 1]),
    phone: v[C.PHONE - 1],
    phoneOk: !!normalizePhone_(v[C.PHONE - 1]),
    textingReady: twilioReady_(cfg_()),
    docusignReady: docusignReady_(cfg_())
  };
  var link = getLink_(v[C.LINKID - 1]);
  if (link) {
    maybeCheckSigned_(link);
    out.link = linkStatus_(link);
  }
  return out;
}

/** Create (or reuse) the client's link and text it. */
function panelSendLink(row, forceNew) {
  requireStaff_();
  var caller = PropertiesService.getUserProperties().getProperty('CALLER_NAME') || '';
  if (!caller) throw new Error('Type your first name at the top of the panel first.');
  var c = cfg_();

  var link = withLock_(function () {
    var sh = ss_().getSheetByName(CALLS_SHEET);
    var v = sh.getRange(row, 1, 1, CALLS_HEADERS.length).getDisplayValues()[0];
    var name = v[C.NAME - 1].trim();
    var phone = normalizePhone_(v[C.PHONE - 1]);
    if (!name) throw new Error('This row has no client name.');
    if (!phone) throw new Error('The cell phone number doesn’t look right. Use 10 digits, like 555-014-2291.');

    var existing = getLink_(v[C.LINKID - 1]);
    var reuse = existing && !forceNew && !isExpired_(existing) && !existing.signedAt;
    var l = reuse ? existing : newLink_({
      name: name, phone: phone, email: v[C.EMAIL - 1].trim(), caller: caller
    });
    if (!reuse) sh.getRange(row, C.LINKID).setValue(l.id);
    l.phone = phone;
    l.caller = caller;
    saveLink_(l);
    return l;
  });

  var texted = false;
  if (twilioReady_(c)) {
    var agency = c.AGENCY_NAME || 'our office';
    var body = 'Hi ' + firstName_(link.name) + ', this is ' + caller + ' from ' + agency +
      '. Tap this link to send us a picture of your insurance card and sign your form: ' + linkUrl_(link.id);
    sendSms_(link.phone, body);
    texted = true;
    withLock_(function () {
      var l = getLink_(link.id);
      l.sentAt = Date.now();
      saveLink_(l);
      link = l;
    });
  }
  updateCallsRow_(link);
  var s = linkStatus_(link);
  s.justTexted = texted;
  return s;
}

/** Manual "check DocuSign now" button in the panel. */
function panelCheckSigned(id) {
  requireStaff_();
  var link = getLink_(id);
  if (!link) throw new Error('Link not found.');
  CacheService.getScriptCache().remove('dscheck_' + id);
  maybeCheckSigned_(link, true);
  return linkStatus_(getLink_(id));
}

function linkStatus_(link) {
  return {
    id: link.id,
    url: linkUrl_(link.id),
    expired: isExpired_(link),
    status: statusText_(link),
    screen: CacheService.getScriptCache().get('screen_' + link.id) || '',
    sentAt: fmtTime_(link.sentAt),
    openedAt: fmtTime_(link.openedAt),
    frontAt: fmtTime_(link.frontAt),
    backAt: fmtTime_(link.backAt),
    signedAt: fmtTime_(link.signedAt),
    frontFileId: link.frontFileId || '',
    backFileId: link.backFileId || '',
    pdfFileId: link.pdfFileId || '',
    folderUrl: link.folderId ? 'https://drive.google.com/drive/folders/' + link.folderId : '',
    envelopeId: link.envelopeId || '',
    lastError: link.lastError || ''
  };
}

/**
 * If the client is in DocuSign but we haven't heard back (for example they closed
 * the page right after signing), ask DocuSign directly. At most every 15 seconds.
 */
function maybeCheckSigned_(link, force) {
  if (!link.envelopeId || link.signedAt) return;
  var cache = CacheService.getScriptCache();
  if (!force && cache.get('dscheck_' + link.id)) return;
  cache.put('dscheck_' + link.id, '1', 15);
  try {
    if (dsSignerCompleted_(link)) finishSigned_(link.id);
  } catch (e) {
    console.warn('DocuSign check failed: ' + e.message);
  }
}

/* ---------- settings API ---------- */

function settingsLoad() {
  requireStaff_();
  var c = cfg_();
  var out = {};
  SETTING_KEYS.forEach(function (k) {
    if (SECRET_KEYS.indexOf(k) >= 0) out[k + '_SAVED'] = !!c[k];
    else out[k] = c[k] || '';
  });
  if (!out.DS_ENV) out.DS_ENV = 'demo';
  if (!out.EXPIRY_HOURS) out.EXPIRY_HOURS = '48';
  if (!out.DS_FALLBACK_EMAIL) out.DS_FALLBACK_EMAIL = Session.getEffectiveUser().getEmail();
  out.suggestedWebAppUrl = ScriptApp.getService().getUrl() || '';
  return out;
}

function settingsSave(values) {
  requireStaff_();
  var p = PropertiesService.getScriptProperties();
  SETTING_KEYS.forEach(function (k) {
    if (!(k in values)) return;
    var v = String(values[k] == null ? '' : values[k]).trim();
    if (SECRET_KEYS.indexOf(k) >= 0 && !v) return; // blank secret = keep the saved one
    if (v) p.setProperty(k, v); else p.deleteProperty(k);
  });
  // Changing DocuSign details invalidates the cached account lookup and sign-in.
  p.deleteProperty('DS_ACCOUNT_ID');
  p.deleteProperty('DS_BASE_URI');
  CacheService.getScriptCache().remove('ds_access');
  if (values.DRIVE_FOLDER) rootFolder_(); // throws a clear error if the folder can't be opened
  return settingsLoad();
}

function settingsTestSms(phone) {
  requireStaff_();
  var to = normalizePhone_(phone);
  if (!to) throw new Error('Enter a 10-digit cell phone number.');
  sendSms_(to, 'Card Capture test: texting is working.');
  return 'Sent. Check that phone.';
}

function settingsTestDocuSign() {
  requireStaff_();
  return dsDescribeSetup_();
}

function settingsConsentUrl() {
  requireStaff_();
  return dsConsentUrl_();
}

/* ======================= Links.gs ======================= */

/**
 * One row per client link in the hidden "_links" tab. The Calls tab points at a
 * link through its hidden "Link ID" column, so callers can sort or insert rows
 * without breaking anything.
 */

var LINKS_SHEET = '_links';
var LINK_FIELDS = ['id', 'name', 'phone', 'email', 'caller', 'createdAt', 'expiresAt',
  'sentAt', 'openedAt', 'frontAt', 'backAt', 'signedAt', 'envelopeId', 'folderId',
  'frontFileId', 'backFileId', 'pdfFileId', 'lastError'];
var TIME_FIELDS = ['createdAt', 'expiresAt', 'sentAt', 'openedAt', 'frontAt', 'backAt', 'signedAt'];

function linksSheet_() {
  var ss = ss_();
  var sh = ss.getSheetByName(LINKS_SHEET);
  if (!sh) {
    sh = ss.insertSheet(LINKS_SHEET);
    sh.getRange(1, 1, sh.getMaxRows(), LINK_FIELDS.length).setNumberFormat('@'); // keep "+1555…" as text
    sh.getRange(1, 1, 1, LINK_FIELDS.length).setValues([LINK_FIELDS]);
    sh.hideSheet();
  }
  return sh;
}

function getLink_(id) {
  id = String(id || '');
  if (!/^[a-f0-9]{32}$/.test(id)) return null;
  var sh = linksSheet_();
  var hit = sh.getRange(1, 1, sh.getMaxRows(), 1).createTextFinder(id).matchEntireCell(true).findNext();
  if (!hit) return null;
  var vals = sh.getRange(hit.getRow(), 1, 1, LINK_FIELDS.length).getValues()[0];
  var link = { _row: hit.getRow() };
  LINK_FIELDS.forEach(function (f, i) {
    var v = vals[i];
    link[f] = TIME_FIELDS.indexOf(f) >= 0 ? (v === '' ? 0 : Number(v)) : String(v);
  });
  return link;
}

function newLink_(fields) {
  var hours = Number(cfg_().EXPIRY_HOURS) || 48;
  var now = Date.now();
  var link = { id: Utilities.getUuid().replace(/-/g, '') };
  LINK_FIELDS.forEach(function (f) { if (!(f in link)) link[f] = ''; });
  Object.keys(fields).forEach(function (k) { link[k] = fields[k]; });
  link.createdAt = now;
  link.expiresAt = now + hours * 3600 * 1000;
  var sh = linksSheet_();
  link._row = sh.getLastRow() + 1;
  if (link._row > sh.getMaxRows()) sh.insertRowsAfter(sh.getMaxRows(), 500);
  sh.getRange(link._row, 1, 1, LINK_FIELDS.length).setNumberFormat('@');
  saveLink_(link);
  return link;
}

/** Call inside withLock_ after re-reading the link, so two writers can't clobber each other. */
function saveLink_(link) {
  var row = LINK_FIELDS.map(function (f) { return String(link[f] === undefined || link[f] === null ? '' : link[f]); });
  linksSheet_().getRange(link._row, 1, 1, LINK_FIELDS.length).setValues([row]);
}

function isExpired_(link) {
  return !link.signedAt && Number(link.expiresAt) > 0 && Date.now() > Number(link.expiresAt);
}

/* ======================= Twilio.gs ======================= */

/** Text messages through Twilio. */

function twilioReady_(c) {
  return !!(c.TW_SID && c.TW_AUTH && c.TW_FROM);
}

/** US numbers: accepts "(555) 014-2291", "555.014.2291", "1-555-014-2291", "+15550142291". */
function normalizePhone_(raw) {
  var s = String(raw || '').trim();
  if (/^\+\d{10,15}$/.test(s.replace(/[\s().-]/g, ''))) return s.replace(/[\s().-]/g, '');
  var d = s.replace(/\D/g, '');
  if (d.length === 10) return '+1' + d;
  if (d.length === 11 && d.charAt(0) === '1') return '+' + d;
  return null;
}

function sendSms_(to, body) {
  var c = cfg_();
  if (!twilioReady_(c)) throw new Error('Texting isn’t set up yet. Add your Twilio details in Settings.');
  var payload = { To: to, Body: body };
  // A Messaging Service SID starts with "MG"; otherwise it's a phone number.
  if (/^MG[0-9a-f]{32}$/i.test(c.TW_FROM)) payload.MessagingServiceSid = c.TW_FROM;
  else payload.From = normalizePhone_(c.TW_FROM) || c.TW_FROM;

  var res = UrlFetchApp.fetch('https://api.twilio.com/2010-04-01/Accounts/' +
    encodeURIComponent(c.TW_SID) + '/Messages.json', {
    method: 'post',
    payload: payload,
    headers: { Authorization: 'Basic ' + Utilities.base64Encode(c.TW_SID + ':' + c.TW_AUTH) },
    muteHttpExceptions: true
  });
  var json = {};
  try { json = JSON.parse(res.getContentText() || '{}'); } catch (e) { /* non-JSON error page */ }
  if (res.getResponseCode() >= 300) {
    throw new Error('The text didn’t send: ' + (json.message || ('Twilio error ' + res.getResponseCode())));
  }
  return json.sid;
}

/* ======================= DocuSign.gs ======================= */

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

/* ======================= Client.gs ======================= */

/**
 * The page clients open from the text message. Deployed as a web app that runs
 * as the owner and is open to anyone with the link. Every function here only
 * acts on the one link whose 32-character ID the client holds.
 */

var SCREENS = ['welcome', 'front', 'frontReview', 'back', 'backReview', 'sign', 'signing', 'done'];

function doGet(e) {
  var p = (e && e.parameter) || {};
  var c = cfg_();

  if (p.code && !p.t) {
    return messagePage_('DocuSign is connected', 'You can close this tab and go back to the spreadsheet.');
  }

  var link = getLink_(p.t);
  var callUs = c.AGENCY_PHONE ? ' Please call us at ' + c.AGENCY_PHONE + '.' : ' Please call us.';
  if (!link) return messagePage_('This link isn’t working', 'Please check the text message and try again.' + callUs);
  if (isExpired_(link)) return messagePage_('This link has expired', 'For your safety, links only work for a short time.' + callUs);

  if (!link.openedAt) {
    withLock_(function () {
      var l = getLink_(link.id);
      if (!l.openedAt) { l.openedAt = Date.now(); saveLink_(l); updateCallsRow_(l); }
    });
  }

  var t = HtmlService.createTemplateFromFile('Client');
  t.stateJson = safeJson_({
    id: link.id,
    firstName: firstName_(link.name),
    caller: link.caller || 'We',
    agency: c.AGENCY_NAME || 'Your insurance agency',
    frontDone: !!link.frontAt,
    backDone: !!link.backAt,
    signed: !!link.signedAt,
    docusign: docusignReady_(c),
    dsEvent: p.ds ? String(p.event || 'unknown') : ''
  });
  return t.evaluate()
    .setTitle(c.AGENCY_NAME || 'Your insurance card')
    .addMetaTag('viewport', 'width=device-width, initial-scale=1');
}

function messagePage_(title, text) {
  var t = HtmlService.createTemplateFromFile('Client');
  t.stateJson = safeJson_({ message: { title: title, text: text } });
  return t.evaluate().setTitle(title).addMetaTag('viewport', 'width=device-width, initial-scale=1');
}

/** JSON that is safe to drop inside a <script> tag. */
function safeJson_(obj) {
  return JSON.stringify(obj).replace(/</g, '\\u003c').replace(/>/g, '\\u003e').replace(/&/g, '\\u0026');
}

function openLink_(id) {
  var link = getLink_(id);
  if (!link) throw new Error('This link isn’t working.');
  if (isExpired_(link)) throw new Error('This link has expired.');
  return link;
}

/* ---------- client API (called with google.script.run from Client.html) ---------- */

/** Lets the caller's panel show which screen the client is looking at. */
function clientScreen(id, screen) {
  openLink_(id);
  if (SCREENS.indexOf(screen) < 0) return;
  CacheService.getScriptCache().put('screen_' + id, screen, 21600);
}

/** Saves one card photo (already shrunk on the phone) into the client's Drive folder. */
function clientUpload(id, side, dataUrl) {
  if (side !== 'front' && side !== 'back') throw new Error('Unknown photo.');
  var m = /^data:(image\/(?:jpeg|png|webp));base64,([A-Za-z0-9+/=]+)$/.exec(String(dataUrl || ''));
  if (!m) throw new Error('That picture didn’t come through. Please try again.');
  if (m[2].length > 12 * 1024 * 1024) throw new Error('That picture is too large. Please try again.');

  return withLock_(function () {
    var link = openLink_(id);
    if (link.signedAt) throw new Error('This form is already finished.');
    var folder = ensureFolder_(link);
    var ext = m[1] === 'image/png' ? 'png' : m[1] === 'image/webp' ? 'webp' : 'jpg';
    var blob = Utilities.newBlob(Utilities.base64Decode(m[2]), m[1], 'Card ' + side + '.' + ext);
    var file = folder.createFile(blob);

    var oldId = link[side + 'FileId'];
    if (oldId) { try { DriveApp.getFileById(oldId).setTrashed(true); } catch (e) { /* already gone */ } }
    link[side + 'FileId'] = file.getId();
    link[side + 'At'] = Date.now();
    saveLink_(link);
    updateCallsRow_(link);
    return true;
  });
}

/** A fresh DocuSign signing address. Valid for 5 minutes and one use. */
function clientSigningUrl(id) {
  if (!docusignReady_(cfg_())) throw new Error('Signing isn’t available right now.');
  var envelopeId = withLock_(function () {
    var link = openLink_(id);
    if (link.signedAt) throw new Error('This form is already signed.');
    if (!link.envelopeId) {
      try {
        link.envelopeId = dsEnsureEnvelope_(link);
        link.lastError = '';
      } catch (e) {
        link.lastError = e.message;
        saveLink_(link);
        throw new Error('We couldn’t open your form. Please tell ' + (link.caller || 'us') + '.');
      }
      saveLink_(link);
    }
    return link.envelopeId;
  });
  var link = getLink_(id);
  link.envelopeId = envelopeId;
  return dsSigningUrl_(link);
}

/** Called when DocuSign sends the client back to us. */
function clientConfirmSigning(id) {
  var link = openLink_(id);
  if (link.signedAt) return { signed: true };
  if (!link.envelopeId) return { signed: false };
  if (!dsSignerCompleted_(link)) return { signed: false };
  finishSigned_(id);
  return { signed: true };
}

/* ---------- shared ---------- */

function ensureFolder_(link) {
  if (link.folderId) {
    try { return DriveApp.getFolderById(link.folderId); } catch (e) { /* deleted: make a new one */ }
  }
  var date = Utilities.formatDate(new Date(), Session.getScriptTimeZone(), 'yyyy-MM-dd');
  var folder = rootFolder_().createFolder(link.name + ' – ' + date);
  link.folderId = folder.getId();
  saveLink_(link);
  return folder;
}

/** Marks the link signed and saves the signed PDF next to the card photos. */
function finishSigned_(id) {
  withLock_(function () {
    var link = getLink_(id);
    if (!link || link.signedAt) return;
    link.signedAt = Date.now();
    try {
      var pdf = dsSignedPdf_(link).setName('Signed form.pdf');
      link.pdfFileId = ensureFolder_(link).createFile(pdf).getId();
      link.lastError = '';
    } catch (e) {
      link.lastError = 'Signed, but the PDF didn’t save to Drive: ' + e.message;
    }
    saveLink_(link);
    updateCallsRow_(link);
  });
}
