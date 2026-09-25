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
