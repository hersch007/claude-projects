import { api, esc, fmtDate, toast, copyText } from './api.js';

const listEl = document.getElementById('list');
const detailEl = document.getElementById('detail');
const uploadModal = document.getElementById('uploadModal');
const fileInput = document.getElementById('file');
const drop = document.getElementById('drop');
let envelopes = [];
let currentId = new URLSearchParams(location.search).get('envelope');
let myRole = null;

// ---------- role ----------
async function loadRole() {
  const { role, userEnabled } = await api('/api/auth/me');
  myRole = role;
  if (!role) return; // not signed in — the next api() call (loadList) will redirect to /login.html
  const badge = document.getElementById('roleBadge');
  badge.textContent = role;
  badge.className = `badge ${role === 'admin' ? 'completed' : 'sent'}`;
  badge.style.display = '';
  document.getElementById('userPasswordSection').hidden = role !== 'admin';
  const status = document.getElementById('userPasswordStatus');
  if (status) status.textContent = userEnabled ? 'A user password is already set — this replaces it.' : 'Not set yet — signing in as User won’t work until you set one.';
}

// ---------- list ----------
async function loadList() {
  const { envelopes: rows } = await api('/api/envelopes');
  envelopes = rows;
  if (!rows.length) {
    listEl.innerHTML = '<p class="muted">No documents yet. Click <b>New document</b> to upload a PDF.</p>';
  } else {
    listEl.innerHTML = `<table><thead><tr><th>Title</th><th>Status</th><th>Signers</th><th>Created</th></tr></thead><tbody>${rows.map((e) => `
      <tr class="clickable" data-id="${e.id}">
        <td><b>${esc(e.title)}</b><br><span class="small muted">${esc(e.original_name)} · ${e.page_count} pg</span></td>
        <td><span class="badge ${e.status}">${e.status}</span></td>
        <td>${e.status === 'imported' ? '<span class="small muted">record only</span>' : `${e.signed_count}/${e.signer_count} signed`}</td>
        <td class="small">${fmtDate(e.created_at)}</td>
      </tr>`).join('')}</tbody></table>`;
    listEl.querySelectorAll('tr[data-id]').forEach((tr) => tr.addEventListener('click', () => openDetail(tr.dataset.id)));
  }
  if (currentId) openDetail(currentId);
}

// ---------- detail ----------
async function openDetail(id) {
  currentId = id;
  history.replaceState(null, '', `/?envelope=${id}`);
  let env;
  try { ({ envelope: env } = await api(`/api/envelopes/${id}`)); }
  catch (e) { toast(e.message, 'error'); detailEl.hidden = true; return; }

  const canEdit = env.status === 'draft';
  const isImported = env.status === 'imported';
  detailEl.hidden = false;
  detailEl.innerHTML = `
    <div class="row" style="justify-content:space-between;align-items:flex-start">
      <div><h2 style="margin-bottom:2px">${esc(env.title)}</h2><span class="badge ${env.status}">${env.status}</span></div>
      <button class="btn sm" id="closeDetail">✕</button>
    </div>
    <p class="small muted" style="margin-top:8px">${esc(env.original_name)} · ${env.page_count} page(s)${isImported ? '' : ` · ${env.signing_order} signing · verification: ${{ email_code: 'email code', access_code: 'access code' }[env.auth_method] || 'none'}`}<br>
      ${isImported ? `Imported ${fmtDate(env.created_at)}` : `Created ${fmtDate(env.created_at)}${env.sent_at ? ` · Sent ${fmtDate(env.sent_at)}` : ''}${env.completed_at ? ` · Completed ${fmtDate(env.completed_at)}` : ''}`}</p>
    <div class="row" style="flex-wrap:wrap;gap:6px;margin:12px 0">
      ${canEdit ? `<a class="btn primary sm" href="/prepare.html?id=${env.id}">Prepare &amp; send</a>` : ''}
      <a class="btn sm" href="/api/envelopes/${env.id}/pdf" target="_blank">${isImported ? 'Original file, as uploaded' : 'Original PDF'}</a>
      ${env.status === 'completed' ? `<a class="btn primary sm" href="/api/envelopes/${env.id}/completed?download=1">Download signed PDF</a>` : ''}
      ${isImported ? `<a class="btn primary sm" href="/api/envelopes/${env.id}/completed?download=1">Download with import record</a>` : ''}
      ${env.status === 'sent' ? '<button class="btn sm" id="remindBtn">Send reminder</button>' : ''}
      ${myRole === 'admin' && ['draft', 'sent'].includes(env.status) ? '<button class="btn sm danger" id="voidBtn">Void</button>' : ''}
      ${myRole === 'admin' ? '<button class="btn sm danger" id="deleteBtn">Delete</button>' : ''}
    </div>
    ${env.completed_sha256 ? `<p class="small muted">${isImported ? 'Record PDF' : 'Completed'} SHA-256: <code>${env.completed_sha256}</code></p>` : ''}

    ${isImported ? `
    <div class="card" style="background:#f7f8fb;box-shadow:none;padding:12px">
      <p class="small" style="margin:0 0 4px"><b>Imported for record-keeping.</b> AnagraSign did not witness this signing.</p>
      <p class="small muted" style="margin:0">Originally signed via: ${esc(env.imported_source) || '<i>not specified</i>'}<br>
      Signed on, as reported: ${esc(env.imported_signed_date) || '<i>not specified</i>'}
      ${env.imported_note ? `<br>Notes: ${esc(env.imported_note)}` : ''}</p>
    </div>
    ` : `
    <h3>Signers</h3>
    ${env.signers.length ? env.signers.map((s, i) => `
      <div class="signer-row">
        <div class="row" style="justify-content:space-between">
          <div><b>${i + 1}. ${esc(s.name) || '<i>unnamed</i>'}</b> <span class="small muted">${esc(s.email)}</span></div>
          <span class="badge ${s.status}">${s.status}</span>
        </div>
        <div class="small muted">
          ${env.auth_method === 'access_code' ? `Access code: <code>${esc(s.access_code || '(not set)')}</code> <button class="btn link sm" data-copy="${esc(s.access_code || '')}">copy</button><br>` : ''}
          ${s.verified_at ? `Verified ${fmtDate(s.verified_at)} · ` : ''}${s.viewed_at ? `Viewed ${fmtDate(s.viewed_at)} · ` : ''}${s.signed_at ? `Signed ${fmtDate(s.signed_at)}` : ''}${s.decline_reason ? `Declined: ${esc(s.decline_reason)}` : ''}
          ${s.link && env.status === 'sent' && s.status !== 'signed' ? `<div class="row" style="margin-top:4px"><button class="btn link sm" data-copy="${esc(s.link)}">Copy signing link</button>${s.is_turn ? '' : '<span class="small">(waiting on earlier signer)</span>'}</div>` : ''}
        </div>
      </div>`).join('') : '<p class="muted small">No signers yet.</p>'}
    `}

    <h3 style="margin-top:16px">Activity</h3>
    <div class="events">${env.events.slice().reverse().map((e) => `<div><b>${esc(e.type)}</b> <span class="muted">${fmtDate(e.created_at)}</span>${e.ip ? ` <span class="muted">· ${esc(e.ip)}</span>` : ''}${e.detail ? `<br><span class="muted">${esc(e.detail)}</span>` : ''}</div>`).join('')}</div>
  `;

  detailEl.querySelector('#closeDetail').onclick = () => { detailEl.hidden = true; currentId = null; history.replaceState(null, '', '/'); };
  detailEl.querySelectorAll('[data-copy]').forEach((b) => (b.onclick = () => copyText(b.dataset.copy)));
  detailEl.querySelector('#remindBtn')?.addEventListener('click', async () => {
    try { const r = await api(`/api/envelopes/${id}/remind`, { method: 'POST' }); toast(`Reminder queued for ${r.mail.length} signer(s)`); openDetail(id); }
    catch (e) { toast(e.message, 'error'); }
  });
  detailEl.querySelector('#voidBtn')?.addEventListener('click', async () => {
    if (!confirm('Void this document? Signing links will stop working.')) return;
    try { await api(`/api/envelopes/${id}/void`, { method: 'POST', body: {} }); toast('Voided'); loadList(); }
    catch (e) { toast(e.message, 'error'); }
  });
  detailEl.querySelector('#deleteBtn')?.addEventListener('click', async () => {
    if (!confirm('Permanently delete this document, its signatures and audit trail?')) return;
    try { await api(`/api/envelopes/${id}`, { method: 'DELETE' }); toast('Deleted'); detailEl.hidden = true; currentId = null; history.replaceState(null, '', '/'); loadList(); }
    catch (e) { toast(e.message, 'error'); }
  });
}

// ---------- upload ----------
document.getElementById('newBtn').onclick = () => { uploadModal.hidden = false; document.getElementById('title').focus(); };
document.getElementById('cancelUpload').onclick = () => { uploadModal.hidden = true; };
drop.onclick = () => fileInput.click();
fileInput.onchange = () => { document.getElementById('fileName').textContent = fileInput.files[0]?.name || ''; };
drop.addEventListener('dragover', (e) => { e.preventDefault(); drop.classList.add('over'); });
drop.addEventListener('dragleave', () => drop.classList.remove('over'));
drop.addEventListener('drop', (e) => {
  e.preventDefault(); drop.classList.remove('over');
  if (e.dataTransfer.files[0]) { fileInput.files = e.dataTransfer.files; fileInput.onchange(); }
});
document.getElementById('uploadForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const err = document.getElementById('uploadErr');
  err.hidden = true;
  if (!fileInput.files[0]) { err.textContent = 'Choose a PDF file.'; err.hidden = false; return; }
  const form = new FormData();
  form.append('title', document.getElementById('title').value);
  form.append('pdf', fileInput.files[0]);
  const btn = document.getElementById('uploadBtn');
  btn.disabled = true; btn.textContent = 'Uploading…';
  try {
    const { envelope } = await api('/api/envelopes', { method: 'POST', form });
    location.href = `/prepare.html?id=${envelope.id}`;
  } catch (ex) {
    err.textContent = ex.message; err.hidden = false;
    btn.disabled = false; btn.textContent = 'Upload & prepare';
  }
});

// ---------- import (already-signed elsewhere) ----------
const importModal = document.getElementById('importModal');
const importFileInput = document.getElementById('importFile');
const importDrop = document.getElementById('importDrop');
document.getElementById('importBtn').onclick = () => { importModal.hidden = false; document.getElementById('importTitle').focus(); };
document.getElementById('cancelImport').onclick = () => { importModal.hidden = true; };
importDrop.onclick = () => importFileInput.click();
importFileInput.onchange = () => { document.getElementById('importFileName').textContent = importFileInput.files[0]?.name || ''; };
importDrop.addEventListener('dragover', (e) => { e.preventDefault(); importDrop.classList.add('over'); });
importDrop.addEventListener('dragleave', () => importDrop.classList.remove('over'));
importDrop.addEventListener('drop', (e) => {
  e.preventDefault(); importDrop.classList.remove('over');
  if (e.dataTransfer.files[0]) { importFileInput.files = e.dataTransfer.files; importFileInput.onchange(); }
});
document.getElementById('importForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const err = document.getElementById('importErr');
  err.hidden = true;
  if (!importFileInput.files[0]) { err.textContent = 'Choose the already-signed PDF.'; err.hidden = false; return; }
  const form = new FormData();
  form.append('title', document.getElementById('importTitle').value);
  form.append('pdf', importFileInput.files[0]);
  form.append('imported_source', document.getElementById('importSource').value);
  form.append('imported_signed_date', document.getElementById('importSignedDate').value);
  form.append('imported_note', document.getElementById('importNote').value);
  const btn = document.getElementById('importSubmitBtn');
  btn.disabled = true; btn.textContent = 'Importing…';
  try {
    const { envelope } = await api('/api/envelopes/import', { method: 'POST', form });
    importModal.hidden = true;
    document.getElementById('importForm').reset();
    document.getElementById('importFileName').textContent = '';
    toast('Imported for record-keeping');
    await loadList();
    openDetail(envelope.id);
  } catch (ex) {
    err.textContent = ex.message; err.hidden = false;
  } finally {
    btn.disabled = false; btn.textContent = 'Import';
  }
});

document.getElementById('logoutBtn').onclick = async () => { await api('/api/auth/logout', { method: 'POST' }); location.href = '/login.html'; };

// ---------- settings (change passwords) ----------
const settingsModal = document.getElementById('settingsModal');
document.getElementById('settingsBtn').onclick = () => { settingsModal.hidden = false; };
document.getElementById('closeSettings').onclick = () => { settingsModal.hidden = true; };

document.getElementById('ownPasswordForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const err = document.getElementById('ownPasswordErr');
  err.hidden = true;
  const current_password = document.getElementById('currentPassword').value;
  const new_password = document.getElementById('newOwnPassword').value;
  try {
    await api('/api/auth/change-password', { method: 'POST', body: { target_role: myRole, current_password, new_password } });
    toast('Password updated');
    e.target.reset();
  } catch (ex) {
    err.textContent = ex.message; err.hidden = false;
  }
});

document.getElementById('userPasswordForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const err = document.getElementById('userPasswordErr');
  err.hidden = true;
  const new_password = document.getElementById('newUserPassword').value;
  try {
    await api('/api/auth/change-password', { method: 'POST', body: { target_role: 'user', new_password } });
    toast('User password set');
    e.target.reset();
    loadRole();
  } catch (ex) {
    err.textContent = ex.message; err.hidden = false;
  }
});

await loadRole();
loadList().catch((e) => { listEl.innerHTML = `<p class="error">${esc(e.message)}</p>`; });
