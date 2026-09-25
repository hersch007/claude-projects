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
