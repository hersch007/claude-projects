import { api, esc, toast } from './api.js';
import { renderPdf, FIELD_META, signerColor, placeEl, clamp } from './pdfview.js';

const id = new URLSearchParams(location.search).get('id');
const $ = (s) => document.querySelector(s);
const pagesEl = $('#pages');

let env;            // envelope from server
let view;           // { pages }
let state = { title: '', message: '', signing_order: 'sequential', auth_method: 'none', signers: [], fields: [] };
const AUTH_HELP = {
  none: 'Anyone with the emailed link can open and sign.',
  email_code: 'Signers must enter a 6-digit code emailed to them before they can open the document. Recorded on the certificate.',
  access_code: 'You set a code per signer and share it by phone or text. The signer must enter it to open the document. Strongest option.',
};
let tool = null;    // field type currently being placed
let activeSigner = 0;
let selected = null; // field uid
let dirty = false;
let uidSeq = 1;

const setDirty = (v = true) => { dirty = v; $('#saveState').textContent = v ? 'Unsaved changes' : 'Saved'; };
window.addEventListener('beforeunload', (e) => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });

// ---------- load ----------
async function init() {
  if (!id) { location.href = '/'; return; }
  ({ envelope: env } = await api(`/api/envelopes/${id}`));
  if (env.status !== 'draft') { location.href = `/?envelope=${id}`; return; }

  state.title = env.title;
  state.message = env.message;
  state.signing_order = env.signing_order;
  state.auth_method = env.auth_method || 'none';
  state.signers = env.signers.map((s) => ({ name: s.name, email: s.email, access_code: s.access_code || '' }));
  const idx = new Map(env.signers.map((s, i) => [s.id, i]));
  state.fields = env.fields.map((f) => ({ uid: uidSeq++, signer_index: idx.get(f.signer_id) ?? 0, type: f.type, page: f.page, x: f.x, y: f.y, w: f.w, h: f.h, required: !!f.required, label: f.label || '' }));
  if (!state.signers.length) state.signers.push({ name: '', email: '', access_code: '' });

  $('#title').value = state.title;
  $('#topTitle').textContent = state.title;
  $('#message').value = state.message;
  $('#order').value = state.signing_order;
  $('#auth').value = state.auth_method;
  $('#authHelp').textContent = AUTH_HELP[state.auth_method];

  renderSigners();
  renderPalette();
  view = await renderPdf(`/api/envelopes/${id}/pdf`, pagesEl);
  for (const p of view.pages) {
    p.overlay.addEventListener('pointerdown', (e) => onOverlayDown(e, p));
  }
  renderFields();
  setDirty(false);
}

// ---------- signers ----------
function renderSigners() {
  const wrap = $('#signers');
  wrap.innerHTML = state.signers.map((s, i) => `
    <div class="signer ${i === activeSigner ? 'active' : ''}" data-i="${i}">
      <span class="dot" style="background:${signerColor(i)}"></span>
      <input type="text" placeholder="Name" value="${esc(s.name)}" data-k="name">
      <input type="email" placeholder="Email" value="${esc(s.email)}" data-k="email">
      <button class="x" title="Remove signer" ${state.signers.length === 1 ? 'disabled' : ''}>×</button>
      ${state.auth_method === 'access_code' ? `<input class="code" type="text" placeholder="Access code for this signer (4+ characters)" maxlength="40" value="${esc(s.access_code || '')}" data-k="access_code">` : ''}
    </div>`).join('');
  wrap.querySelectorAll('.signer').forEach((row) => {
    const i = Number(row.dataset.i);
    row.addEventListener('click', () => { if (activeSigner !== i) { activeSigner = i; renderSigners(); } });
    row.querySelectorAll('input').forEach((inp) => inp.addEventListener('input', () => { state.signers[i][inp.dataset.k] = inp.value; setDirty(); renderFields(); }));
    row.querySelector('.x').addEventListener('click', (e) => {
      e.stopPropagation();
      const n = state.fields.filter((f) => f.signer_index === i).length;
      if (n && !confirm(`Remove this signer and their ${n} field(s)?`)) return;
      state.signers.splice(i, 1);
      state.fields = state.fields.filter((f) => f.signer_index !== i).map((f) => ({ ...f, signer_index: f.signer_index > i ? f.signer_index - 1 : f.signer_index }));
      activeSigner = Math.min(activeSigner, state.signers.length - 1);
      selected = null; setDirty(); renderSigners(); renderFields(); renderProps();
    });
  });
}
$('#addSigner').onclick = () => {
  if (state.signers.length >= 20) return toast('Maximum 20 signers', 'error');
  state.signers.push({ name: '', email: '', access_code: '' });
  activeSigner = state.signers.length - 1;
  setDirty(); renderSigners();
  $('#signers .signer.active input')?.focus();
};

// ---------- palette ----------
function renderPalette() {
  $('#palette').innerHTML = Object.entries(FIELD_META).map(([t, m]) => `<button class="btn sm ${tool === t ? 'active' : ''}" data-t="${t}">${m.label}</button>`).join('');
  $('#palette').querySelectorAll('button').forEach((b) => (b.onclick = () => setTool(tool === b.dataset.t ? null : b.dataset.t)));
}
function setTool(t) {
  tool = t;
  renderPalette();
  view?.pages.forEach((p) => p.overlay.classList.toggle('placing', !!tool));
}
document.addEventListener('keydown', (e) => {
  if (e.target.matches('input, textarea, select')) return;
  if (e.key === 'Escape') { setTool(null); select(null); }
  if ((e.key === 'Delete' || e.key === 'Backspace') && selected) { removeField(selected); }
});

// ---------- fields ----------
const signerName = (i) => state.signers[i]?.name?.trim() || `Signer ${i + 1}`;

function renderFields() {
  for (const p of view?.pages || []) p.overlay.querySelectorAll('.fld').forEach((el) => el.remove());
  for (const f of state.fields) {
    const p = view?.pages[f.page - 1];
    if (!p) continue;
    const el = document.createElement('div');
    el.className = `fld editable ${f.uid === selected ? 'selected' : ''}`;
    el.dataset.uid = f.uid;
    el.style.setProperty('--c', signerColor(f.signer_index));
    placeEl(el, f);
    el.innerHTML = `<span class="tag">${esc(FIELD_META[f.type].label)}${f.type === 'checkbox' ? '' : ` · ${esc(signerName(f.signer_index))}`}</span><span class="rz"></span>`;
    el.addEventListener('pointerdown', (e) => onFieldDown(e, f, p));
    p.overlay.append(el);
  }
}

function select(uid) {
  selected = uid;
  document.querySelectorAll('.fld').forEach((el) => el.classList.toggle('selected', Number(el.dataset.uid) === uid));
  renderProps();
}

function removeField(uid) {
  state.fields = state.fields.filter((f) => f.uid !== uid);
  selected = null; setDirty(); renderFields(); renderProps();
}

function onOverlayDown(e, p) {
  if (!tool || e.target !== p.overlay) return;
  const r = p.overlay.getBoundingClientRect();
  const meta = FIELD_META[tool];
  const f = {
    uid: uidSeq++, signer_index: activeSigner, type: tool, page: p.n,
    x: clamp((e.clientX - r.left) / r.width - meta.w / 2, 0, 1 - meta.w),
    y: clamp((e.clientY - r.top) / r.height - meta.h / 2, 0, 1 - meta.h),
    w: meta.w, h: meta.h, required: true, label: '',
  };
  state.fields.push(f);
  setDirty(); renderFields(); select(f.uid);
}

function onFieldDown(e, f, p) {
  e.stopPropagation();
  e.preventDefault();
  select(f.uid);
  const el = e.currentTarget;
  const resizing = e.target.classList.contains('rz');
  const start = { x: e.clientX, y: e.clientY, fx: f.x, fy: f.y, fw: f.w, fh: f.h };
  const minW = 0.02, minH = 0.012;
  el.setPointerCapture(e.pointerId);
  const move = (ev) => {
    const dx = (ev.clientX - start.x) / p.width;
    const dy = (ev.clientY - start.y) / p.height;
    if (resizing) {
      f.w = clamp(start.fw + dx, minW, 1 - f.x);
      f.h = clamp(start.fh + dy, minH, 1 - f.y);
    } else {
      f.x = clamp(start.fx + dx, 0, 1 - f.w);
      f.y = clamp(start.fy + dy, 0, 1 - f.h);
    }
    placeEl(el, f);
  };
  const up = () => {
    el.removeEventListener('pointermove', move);
    el.removeEventListener('pointerup', up);
    if (f.x !== start.fx || f.y !== start.fy || f.w !== start.fw || f.h !== start.fh) setDirty();
    renderProps();
  };
  el.addEventListener('pointermove', move);
  el.addEventListener('pointerup', up);
}

// ---------- properties panel ----------
function renderProps() {
  const f = state.fields.find((x) => x.uid === selected);
  $('#propsSection').hidden = !f;
  if (!f) return;
  $('#propType').textContent = FIELD_META[f.type].label;
  $('#propPage').textContent = `page ${f.page}`;
  $('#propSigner').innerHTML = state.signers.map((s, i) => `<option value="${i}" ${i === f.signer_index ? 'selected' : ''}>${esc(signerName(i))}</option>`).join('');
  $('#propSigner').onchange = (e) => { f.signer_index = Number(e.target.value); setDirty(); renderFields(); };
  $('#propLabelWrap').hidden = !['text', 'checkbox'].includes(f.type);
  $('#propLabel').value = f.label;
  $('#propLabel').oninput = (e) => { f.label = e.target.value; setDirty(); };
  $('#propRequired').checked = f.required;
  $('#propRequired').onchange = (e) => { f.required = e.target.checked; setDirty(); };
  $('#propDelete').onclick = () => removeField(f.uid);
}

// ---------- document settings ----------
$('#title').oninput = (e) => { state.title = e.target.value; $('#topTitle').textContent = state.title; setDirty(); };
$('#message').oninput = (e) => { state.message = e.target.value; setDirty(); };
$('#order').onchange = (e) => { state.signing_order = e.target.value; setDirty(); };
$('#auth').onchange = (e) => { state.auth_method = e.target.value; $('#authHelp').textContent = AUTH_HELP[state.auth_method]; setDirty(); renderSigners(); };

// ---------- save / send ----------
function payload() {
  return {
    title: state.title, message: state.message, signing_order: state.signing_order, auth_method: state.auth_method,
    signers: state.signers.map((s) => ({ name: s.name.trim(), email: s.email.trim(), access_code: (s.access_code || '').trim() })),
    fields: state.fields.map(({ uid, ...f }) => f),
  };
}
async function save() {
  const r = await api(`/api/envelopes/${id}`, { method: 'PUT', body: payload() });
  env = r.envelope;
  setDirty(false);
  return env;
}
$('#saveBtn').onclick = async () => {
  try { await save(); toast('Draft saved'); } catch (e) { toast(e.message, 'error'); }
};
$('#sendBtn').onclick = async () => {
  const problems = [];
  state.signers.forEach((s, i) => {
    if (!s.name.trim()) problems.push(`Signer ${i + 1} needs a name`);
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(s.email.trim())) problems.push(`Signer ${i + 1} needs a valid email`);
    if (!state.fields.some((f) => f.signer_index === i)) problems.push(`${signerName(i)} has no fields on the document`);
    if (state.auth_method === 'access_code' && (s.access_code || '').trim().length < 4) problems.push(`${signerName(i)} needs an access code of at least 4 characters`);
  });
  if (!state.fields.some((f) => f.type === 'signature')) problems.push('Place at least one Signature field');
  if (problems.length) return toast(problems[0], 'error');
  if (!confirm(`Send "${state.title}" to ${state.signers.length} signer(s) now?`)) return;
  const btn = $('#sendBtn');
  btn.disabled = true; btn.textContent = 'Sending…';
  try {
    await save();
    const r = await api(`/api/envelopes/${id}/send`, { method: 'POST' });
    const unsent = (r.mail || []).filter((m) => !m.sent);
    toast(unsent.length ? 'Sent. SMTP not configured, copy signing links from the dashboard.' : 'Sent for signature');
    setTimeout(() => { location.href = `/?envelope=${id}`; }, 900);
  } catch (e) {
    toast(e.message, 'error');
    btn.disabled = false; btn.textContent = 'Send for signature';
  }
};

init().catch((e) => { pagesEl.innerHTML = `<p class="error" style="padding:40px">${esc(e.message)}</p>`; });
