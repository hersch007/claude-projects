import { api, esc, fmtDate, toast } from './api.js';
import { renderPdf, FIELD_META, placeEl } from './pdfview.js';

// Query string, not a path segment: some hosts (e.g. SiteGround's Node.js hosting) only proxy
// /api/* and literal static files to the app, so a dynamic path like /sign/<token> 404s at the
// edge before ever reaching this code. /sign.html is a real file, always served correctly.
const token = new URLSearchParams(location.search).get('t');
const $ = (s) => document.querySelector(s);
const base = `/api/sign/${token}`;

let data;               // response of GET /api/sign/:token
let view;               // rendered pages
let values = {};        // fieldId -> value (my fields only)
let consented = false;
let sigTarget = null;   // field currently being signed
let pad;                // SignaturePad instance

const today = () => { const d = new Date(); return `${String(d.getMonth() + 1).padStart(2, '0')}/${String(d.getDate()).padStart(2, '0')}/${d.getFullYear()}`; };
const mine = () => data.fields.filter((f) => f.mine);
const isFilled = (f) => f.type === 'date' || f.type === 'name' || (f.type === 'checkbox' ? !!values[f.id] : !!values[f.id]);
const incomplete = () => mine().filter((f) => f.required && !isFilled(f));

function showStatus(icon, title, body) {
  $('#pages').hidden = true;
  $('#status').hidden = false;
  $('#status').innerHTML = `<div class="card status-card"><div class="big">${icon}</div><h1>${esc(title)}</h1>${body}</div>`;
}

async function init() {
  try { data = await api(base); }
  catch (e) { return showStatus('⚠️', 'Link not valid', `<p class="muted">${esc(e.message)}</p>`); }

  const { envelope, signer } = data;
  $('#topTitle').textContent = envelope.title;
  document.title = `${envelope.title} · AnagraSign`;
  $('#verify').hidden = true;

  if (envelope.status === 'voided') return showStatus('🚫', 'Document voided', '<p class="muted">The sender cancelled this document. Contact them if you have questions.</p>');
  if (data.auth && !data.auth.verified) return showVerify();

  if (envelope.status === 'completed') {
    return showStatus('✅', 'Document completed', `<p>Everyone has signed <b>${esc(envelope.title)}</b>.</p>
      <p><a class="btn primary" href="${base}/completed?download=1">Download signed PDF</a> <a class="btn" href="${base}/completed" target="_blank">View</a></p>
      <p class="small muted">SHA-256: <code>${esc(envelope.completed_sha256 || '')}</code></p>`);
  }
  if (envelope.status === 'declined') return showStatus('🚫', 'Document declined', `<p class="muted">${signer.status === 'declined' ? 'You declined to sign this document.' : 'Another signer declined this document, so it can no longer be signed.'}</p>`);
  if (signer.status === 'signed') return showStatus('✅', 'Thanks, you have signed', `<p class="muted">Signed ${fmtDate(signer.signed_at)}. You will receive the completed document by email once everyone has signed.</p>`);
  if (!data.turn) return showStatus('⏳', 'Not your turn yet', `<p class="muted">Waiting on ${esc(data.waiting_on.join(', '))} to sign first. You will get an email when the document is ready for you.</p>`);

  // Ready to sign
  consented = !!signer.consent_at;
  $('#consentIntro').innerHTML = `Hello <b>${esc(signer.name)}</b>. Before you review and sign <b>${esc(envelope.title)}</b>, please confirm the following.`;
  if (!consented) $('#consentModal').hidden = false;

  $('#pages').hidden = false;
  view = await renderPdf(`${base}/pdf`, $('#pages'));
  renderFields();
  $('#finishBtn').hidden = false;
  $('#declineBtn').hidden = false;
  $('#nextBtn').hidden = false;
  updateProgress();
}

// ---------- identity verification ----------
let resendTimer;
async function requestCode() {
  const btn = $('#resendBtn');
  btn.disabled = true;
  try {
    const r = await api(`${base}/request-code`, { method: 'POST', body: {} });
    $('#verifyText').innerHTML = `We emailed a 6-digit code to <b>${esc(r.sent_to)}</b>. Enter it below to open <b>${esc(data.envelope.title)}</b>. The code expires in 15 minutes.`;
    let left = 60;
    btn.textContent = `Resend code (${left}s)`;
    clearInterval(resendTimer);
    resendTimer = setInterval(() => { left -= 1; if (left <= 0) { clearInterval(resendTimer); btn.disabled = false; btn.textContent = 'Resend code'; } else btn.textContent = `Resend code (${left}s)`; }, 1000);
  } catch (e) {
    btn.disabled = false;
    $('#verifyErr').textContent = e.message; $('#verifyErr').hidden = false;
  }
}

function showVerify() {
  const { auth, signer, envelope } = data;
  $('#pages').hidden = true;
  $('#status').hidden = true;
  $('#verify').hidden = false;
  $('#verifyErr').hidden = true;
  $('#verifyCode').value = '';
  if (auth.locked_until) {
    $('#verifyText').textContent = `Too many incorrect attempts. Try again after ${new Date(auth.locked_until).toLocaleTimeString()}.`;
    $('#verifyBtn').disabled = true;
    return;
  }
  $('#verifyBtn').disabled = false;
  if (auth.method === 'access_code') {
    $('#verifyTitle').textContent = `Hello ${signer.name}`;
    $('#verifyText').innerHTML = `The sender of <b>${esc(envelope.title)}</b> set an access code for you. Enter it to open the document. Contact the sender if you do not have it.`;
    $('#resendBtn').hidden = true;
    $('#verifyCode').placeholder = 'Access code';
  } else {
    $('#verifyTitle').textContent = `Hello ${signer.name}`;
    $('#resendBtn').hidden = false;
    $('#verifyCode').placeholder = '6-digit code';
    const recent = auth.code_sent_at && Date.now() - new Date(auth.code_sent_at).getTime() < 60_000;
    if (recent) $('#verifyText').innerHTML = `A 6-digit code was emailed to <b>${esc(auth.email_masked)}</b>. Enter it below.`;
    else requestCode();
  }
  $('#verifyCode').focus();
}
$('#resendBtn').onclick = requestCode;
$('#verifyCode').addEventListener('keydown', (e) => { if (e.key === 'Enter') $('#verifyBtn').click(); });
$('#verifyBtn').onclick = async () => {
  const code = $('#verifyCode').value.trim();
  if (!code) return;
  $('#verifyBtn').disabled = true;
  $('#verifyErr').hidden = true;
  try {
    await api(`${base}/verify`, { method: 'POST', body: { code } });
    clearInterval(resendTimer);
    await init();
  } catch (e) {
    $('#verifyErr').textContent = e.message; $('#verifyErr').hidden = false;
    $('#verifyBtn').disabled = false;
    $('#verifyCode').select();
  }
};

// ---------- consent ----------
$('#consentChk').onchange = (e) => { $('#consentContinue').disabled = !e.target.checked; };
$('#consentContinue').onclick = async () => {
  try { await api(`${base}/consent`, { method: 'POST', body: {} }); consented = true; $('#consentModal').hidden = true; }
  catch (e) { toast(e.message, 'error'); }
};
$('#consentDecline').onclick = () => decline();

// ---------- fields ----------
function renderFields() {
  for (const p of view.pages) p.overlay.querySelectorAll('.fld').forEach((el) => el.remove());
  for (const f of data.fields) {
    const p = view.pages[f.page - 1];
    if (!p) continue;
    const el = document.createElement('div');
    el.className = 'fld';
    el.dataset.id = f.id;
    el.style.setProperty('--c', f.mine ? '#1d4ed8' : '#9ca3af');
    placeEl(el, f);
    if (f.mine) {
      el.classList.add('mine');
      renderMine(el, f);
    } else {
      el.classList.add('theirs');
      if (f.value) {
        el.classList.add('done');
        if (f.type === 'signature' || f.type === 'initials') el.innerHTML = `<img src="${f.value}" alt="">`;
        else if (f.type === 'checkbox') el.innerHTML = f.value === '1' ? '<span class="chk">X</span>' : '';
        else el.innerHTML = `<span class="txt">${esc(f.value)}</span>`;
      } else {
        el.innerHTML = `<span class="txt">${esc(f.signer_name)}</span>`;
        el.title = `${FIELD_META[f.type].label} for ${f.signer_name}`;
      }
    }
    p.overlay.append(el);
  }
  markNext();
}

function renderMine(el, f) {
  el.classList.toggle('done', isFilled(f));
  el.onclick = null;
  switch (f.type) {
    case 'signature':
    case 'initials':
      el.innerHTML = values[f.id] ? `<img src="${values[f.id]}" alt="">` : `<span class="placeholder">${f.type === 'signature' ? 'Sign here' : 'Initial here'}</span>`;
      el.onclick = () => openSigModal(f);
      break;
    case 'checkbox':
      el.innerHTML = values[f.id] ? '<span class="chk">X</span>' : '';
      el.title = f.label || 'Click to check';
      el.onclick = () => { values[f.id] = !values[f.id]; renderMine(el, f); updateProgress(); };
      break;
    case 'text': {
      el.innerHTML = `<input class="inline" type="text" maxlength="500" placeholder="${esc(f.label || 'Enter text')}" value="${esc(values[f.id] || '')}">`;
      const inp = el.querySelector('input');
      inp.oninput = () => { values[f.id] = inp.value; el.classList.toggle('done', !!inp.value.trim()); updateProgress(); };
      break;
    }
    case 'date':
      el.classList.add('auto', 'done');
      el.innerHTML = `<span class="txt">${today()}</span>`;
      el.title = 'Date is filled automatically when you finish';
      break;
    case 'name':
      el.classList.add('auto', 'done');
      el.innerHTML = `<span class="txt">${esc(data.signer.name)}</span>`;
      break;
  }
}

function markNext() {
  document.querySelectorAll('.fld.next').forEach((el) => el.classList.remove('next'));
  const nf = incomplete()[0];
  if (nf) document.querySelector(`.fld[data-id="${nf.id}"]`)?.classList.add('next');
}

function updateProgress() {
  const req = mine().filter((f) => f.required);
  const done = req.filter(isFilled).length;
  $('#progress').textContent = req.length ? `${done} of ${req.length} required fields complete` : '';
  markNext();
}

$('#nextBtn').onclick = () => {
  const nf = incomplete()[0];
  const el = nf && document.querySelector(`.fld[data-id="${nf.id}"]`);
  if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
  else toast('All required fields are complete. Click Finish.');
};

// ---------- signature modal ----------
function setupPad() {
  const canvas = $('#pad');
  const ratio = Math.max(window.devicePixelRatio || 1, 1);
  const rect = canvas.getBoundingClientRect();
  canvas.width = rect.width * ratio;
  canvas.height = rect.height * ratio;
  canvas.getContext('2d').scale(ratio, ratio);
  if (!pad) pad = new SignaturePad(canvas, { penColor: '#0f1a3a', minWidth: 0.8, maxWidth: 2.2 });
  else pad.clear();
}

function openSigModal(f) {
  sigTarget = f;
  $('#sigTitle').textContent = f.type === 'signature' ? 'Add your signature' : 'Add your initials';
  $('#applyAllLabel').textContent = f.type === 'signature' ? 'Use this for all my signature fields' : 'Use this for all my initials fields';
  $('#typedName').value = f.type === 'signature' ? data.signer.name : initialsOf(data.signer.name);
  $('#typedPreview').textContent = $('#typedName').value;
  $('#sigModal').hidden = false;
  switchTab('draw');
  requestAnimationFrame(setupPad);
}

const initialsOf = (name) => name.split(/\s+/).filter(Boolean).map((w) => w[0].toUpperCase()).join('');

function switchTab(tab) {
  document.querySelectorAll('.tabs button').forEach((b) => b.classList.toggle('active', b.dataset.tab === tab));
  $('#tabDraw').hidden = tab !== 'draw';
  $('#tabType').hidden = tab !== 'type';
  if (tab === 'type') $('#typedName').focus();
}
document.querySelectorAll('.tabs button').forEach((b) => (b.onclick = () => switchTab(b.dataset.tab)));
$('#typedName').oninput = (e) => { $('#typedPreview').textContent = e.target.value; };
$('#clearPad').onclick = () => pad?.clear();
$('#sigCancel').onclick = () => { $('#sigModal').hidden = true; sigTarget = null; };
window.addEventListener('resize', () => { if (!$('#sigModal').hidden) setupPad(); });

/** Crop transparent margins from a canvas and return a PNG data URL. */
function trimmedPng(canvas) {
  const ctx = canvas.getContext('2d');
  const { width, height } = canvas;
  const px = ctx.getImageData(0, 0, width, height).data;
  let minX = width, minY = height, maxX = -1, maxY = -1;
  for (let y = 0; y < height; y++) for (let x = 0; x < width; x++) {
    if (px[(y * width + x) * 4 + 3] > 10) { if (x < minX) minX = x; if (x > maxX) maxX = x; if (y < minY) minY = y; if (y > maxY) maxY = y; }
  }
  if (maxX < 0) return null;
  const pad = 6;
  minX = Math.max(0, minX - pad); minY = Math.max(0, minY - pad);
  maxX = Math.min(width - 1, maxX + pad); maxY = Math.min(height - 1, maxY + pad);
  const out = document.createElement('canvas');
  out.width = maxX - minX + 1; out.height = maxY - minY + 1;
  out.getContext('2d').drawImage(canvas, minX, minY, out.width, out.height, 0, 0, out.width, out.height);
  return out.toDataURL('image/png');
}

function typedPng(text) {
  const c = $('#typedCanvas');
  const ctx = c.getContext('2d');
  ctx.clearRect(0, 0, c.width, c.height);
  let size = 120;
  ctx.fillStyle = '#0f1a3a';
  do { ctx.font = `${size}px "Brush Script MT", "Segoe Script", "Snell Roundhand", cursive`; size -= 4; }
  while (ctx.measureText(text).width > c.width - 40 && size > 20);
  ctx.textBaseline = 'middle';
  ctx.fillText(text, 20, c.height / 2);
  return trimmedPng(c);
}

$('#sigApply').onclick = () => {
  if (!sigTarget) return;
  let png = null;
  if ($('#tabDraw').hidden) {
    const t = $('#typedName').value.trim();
    if (!t) return toast('Type your name first', 'error');
    png = typedPng(t);
  } else {
    if (pad.isEmpty()) return toast('Draw your signature first', 'error');
    png = trimmedPng($('#pad'));
  }
  if (!png) return toast('Could not capture the signature', 'error');
  const targets = $('#applyAll').checked ? mine().filter((f) => f.type === sigTarget.type) : [sigTarget];
  for (const f of targets) values[f.id] = png;
  $('#sigModal').hidden = true;
  sigTarget = null;
  renderFields();
  updateProgress();
};

// ---------- finish / decline ----------
$('#finishBtn').onclick = async () => {
  if (!consented) { $('#consentModal').hidden = false; return; }
  const missing = incomplete();
  if (missing.length) {
    toast(`Please complete ${missing.length} required field(s)`, 'error');
    document.querySelector(`.fld[data-id="${missing[0].id}"]`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    return;
  }
  if (!confirm('Finish signing? Your signature will be applied to the document.')) return;
  const btn = $('#finishBtn');
  btn.disabled = true; btn.textContent = 'Submitting…';
  try {
    const fields = mine().map((f) => ({ id: f.id, value: f.type === 'checkbox' ? !!values[f.id] : values[f.id] ?? null }));
    const r = await api(`${base}/submit`, { method: 'POST', body: { consent: true, fields } });
    $('#finishBtn').hidden = $('#declineBtn').hidden = $('#nextBtn').hidden = true;
    $('#progress').textContent = '';
    if (r.completed) {
      showStatus('✅', 'All done!', `<p>Everyone has signed <b>${esc(data.envelope.title)}</b>.</p>
        <p><a class="btn primary" href="${base}/completed?download=1">Download signed PDF</a> <a class="btn" href="${base}/completed" target="_blank">View</a></p>`);
    } else {
      showStatus('✅', 'Thanks, you have signed', '<p class="muted">Other signers still need to sign. You will receive the completed document by email when everyone is done.</p>');
    }
  } catch (e) {
    toast(e.message, 'error');
    btn.disabled = false; btn.textContent = 'Finish';
  }
};

async function decline() {
  const reason = prompt('Optionally tell the sender why you are declining:');
  if (reason === null) return;
  try {
    await api(`${base}/decline`, { method: 'POST', body: { reason } });
    $('#consentModal').hidden = true;
    $('#finishBtn').hidden = $('#declineBtn').hidden = $('#nextBtn').hidden = true;
    showStatus('🚫', 'You declined to sign', '<p class="muted">The sender has been notified.</p>');
  } catch (e) { toast(e.message, 'error'); }
}
$('#declineBtn').onclick = decline;

init();
