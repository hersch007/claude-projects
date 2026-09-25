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
