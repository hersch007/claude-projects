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
