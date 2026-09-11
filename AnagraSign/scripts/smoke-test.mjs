// End-to-end smoke test: boots the server on a temp DB, runs the full envelope lifecycle via the API.
import { spawn } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import crypto from 'node:crypto';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const PORT = 3977;
const BASE = `http://127.0.0.1:${PORT}`;
const PASSWORD = 'smoke-pass';
const tmp = fs.mkdtempSync(path.join(os.tmpdir(), 'anagrasign-'));
const dbPath = path.join(tmp, 'test.db');

const server = spawn(process.execPath, ['--disable-warning=ExperimentalWarning', 'server/index.js'], {
  cwd: root,
  env: { ...process.env, PORT: String(PORT), BASE_URL: BASE, DB_PATH: dbPath, ADMIN_PASSWORD: PASSWORD, SESSION_SECRET: 'smoke-secret-'.repeat(3), SMTP_HOST: '', NOTIFY_EMAIL: 'owner@example.com' },
  stdio: ['ignore', 'pipe', 'pipe'],
});
let serverLog = '';
server.stdout.on('data', (d) => { serverLog += d; });
server.stderr.on('data', (d) => { serverLog += d; });

const tokenFromLink = (link) => new URL(link).searchParams.get('t');
const jar = new Map(); // cookie name -> value
let passed = 0;
const ok = (cond, msg) => { if (!cond) throw new Error(`FAIL: ${msg}`); passed++; console.log(`  ok  ${msg}`); };

async function call(p, { method = 'GET', body, form, raw = false } = {}) {
  const headers = { cookie: [...jar].map(([k, v]) => `${k}=${v}`).join('; ') };
  const opts = { method, headers };
  if (form) opts.body = form;
  else if (body !== undefined) { headers['content-type'] = 'application/json'; opts.body = JSON.stringify(body); }
  const r = await fetch(BASE + p, opts);
  for (const sc of r.headers.getSetCookie?.() || []) { const [k, v] = sc.split(';')[0].split('='); jar.set(k, v); }
  if (raw) return r;
  const data = await r.json().catch(() => null);
  if (!r.ok) throw new Error(`${method} ${p} -> ${r.status} ${data?.error || ''}`);
  return data;
}

async function waitForServer() {
  for (let i = 0; i < 60; i++) {
    try { await fetch(`${BASE}/api/auth/me`); return; } catch { await new Promise((r) => setTimeout(r, 250)); }
  }
  throw new Error('Server did not start:\n' + serverLog);
}

// 1x1 transparent PNG (stand-in for a drawn signature)
const PNG = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

let envId;
const extraIds = [];
try {
  await waitForServer();
  console.log('Server up');

  // auth
  const r401 = await call('/api/envelopes', { raw: true });
  ok(r401.status === 401, 'envelopes require login');
  await call('/api/auth/login', { method: 'POST', body: { password: PASSWORD } });
  ok(jar.has('ss_session'), 'login sets session cookie (role defaults to admin)');
  const me1 = await call('/api/auth/me');
  ok(me1.role === 'admin' && me1.userEnabled === false, 'admin role confirmed, user role not set up yet');

  // roles: user role doesn't exist until admin sets it; wrong role/password rejected properly
  const userLoginBefore = await call('/api/auth/login', { method: 'POST', body: { role: 'user', password: 'whatever' }, raw: true });
  ok(userLoginBefore.status === 401, 'user login rejected before a user password exists');
  const badRole = await call('/api/auth/login', { method: 'POST', body: { role: 'nope', password: PASSWORD }, raw: true });
  ok(badRole.status === 400, 'unknown role rejected');

  await call('/api/auth/change-password', { method: 'POST', body: { target_role: 'user', new_password: 'user-pass-123' } });
  const me2 = await call('/api/auth/me');
  ok(me2.userEnabled === true, 'admin can create the user password without knowing a prior one');

  jar.clear(); // log out of admin before testing the user role's own login
  const userLoginBad = await call('/api/auth/login', { method: 'POST', body: { role: 'user', password: 'wrong' }, raw: true });
  ok(userLoginBad.status === 401, 'wrong user password rejected');
  await call('/api/auth/login', { method: 'POST', body: { role: 'user', password: 'user-pass-123' } });
  const me3 = await call('/api/auth/me');
  ok(me3.role === 'user', 'user role logs in with its own password');

  const userDeleteAttempt = await call('/api/envelopes/nonexistent-id', { method: 'DELETE', raw: true });
  ok(userDeleteAttempt.status === 403, 'user role is blocked from admin-only delete route');
  const userChangeAdminAttempt = await call('/api/auth/change-password', { method: 'POST', body: { target_role: 'admin', new_password: 'whatever123' }, raw: true });
  ok(userChangeAdminAttempt.status === 403, 'user role cannot change the admin password');
  const userOwnPasswordWrongCurrent = await call('/api/auth/change-password', { method: 'POST', body: { target_role: 'user', current_password: 'wrong', new_password: 'newpass123' }, raw: true });
  ok(userOwnPasswordWrongCurrent.status === 401, 'changing own password requires the correct current password');
  await call('/api/auth/change-password', { method: 'POST', body: { target_role: 'user', current_password: 'user-pass-123', new_password: 'newpass123' } });
  jar.clear();
  await call('/api/auth/login', { method: 'POST', body: { role: 'user', password: 'newpass123' } });
  ok(jar.has('ss_session'), 'user can change their own password with the correct current one, then log in with the new one');

  // back to admin for the rest of the run
  jar.clear();
  await call('/api/auth/login', { method: 'POST', body: { password: PASSWORD } });

  // upload
  const pdfBytes = fs.readFileSync(path.join(root, 'docs', 'sample-agreement.pdf'));
  const form = new FormData();
  form.append('title', 'Smoke Test Agreement');
  form.append('pdf', new Blob([pdfBytes], { type: 'application/pdf' }), 'sample-agreement.pdf');
  const { envelope: created } = await call('/api/envelopes', { method: 'POST', form });
  envId = created.id;
  ok(created.status === 'draft' && created.page_count === 2, 'upload creates 2-page draft');

  // prepare
  const { envelope: prepared } = await call(`/api/envelopes/${envId}`, {
    method: 'PUT',
    body: {
      title: 'Smoke Test Agreement', message: 'Please sign.', signing_order: 'sequential',
      signers: [{ name: 'Casey Client', email: 'client@example.com' }, { name: 'Pat Provider', email: 'provider@example.com' }],
      fields: [
        { signer_index: 0, type: 'initials', page: 1, x: 0.22, y: 0.875, w: 0.09, h: 0.04 },
        { signer_index: 0, type: 'signature', page: 2, x: 0.21, y: 0.2, w: 0.36, h: 0.05 },
        { signer_index: 0, type: 'name', page: 2, x: 0.21, y: 0.24, w: 0.36, h: 0.03 },
        { signer_index: 0, type: 'date', page: 2, x: 0.7, y: 0.2, w: 0.2, h: 0.03 },
        { signer_index: 0, type: 'checkbox', page: 2, x: 0.09, y: 0.578, w: 0.02, h: 0.016, label: 'Agree to section 4' },
        { signer_index: 0, type: 'text', page: 2, x: 0.3, y: 0.62, w: 0.3, h: 0.03, label: 'Company', required: false },
        { signer_index: 1, type: 'signature', page: 2, x: 0.21, y: 0.39, w: 0.36, h: 0.05 },
        { signer_index: 1, type: 'name', page: 2, x: 0.21, y: 0.43, w: 0.36, h: 0.03 },
        { signer_index: 1, type: 'date', page: 2, x: 0.7, y: 0.39, w: 0.2, h: 0.03 },
      ],
    },
  });
  ok(prepared.signers.length === 2 && prepared.fields.length === 9, 'draft saved with 2 signers and 9 fields');

  // send
  const sent = await call(`/api/envelopes/${envId}/send`, { method: 'POST' });
  ok(sent.envelope.status === 'sent', 'envelope sent');
  ok(sent.mail.length === 1 && sent.mail[0].signer === 'client@example.com', 'only first signer notified (sequential)');
  ok(fs.existsSync(sent.mail[0].outbox), 'invitation written to outbox');
  const [s1, s2] = sent.envelope.signers;
  ok(s1.link && s2.link && s1.is_turn && !s2.is_turn, 'signer links issued; first signer is up');

  const t1 = tokenFromLink(s1.link);
  const t2 = tokenFromLink(s2.link);

  // signer 2 cannot go yet
  const info2 = await call(`/api/sign/${t2}`);
  ok(info2.turn === false && info2.waiting_on.includes('Casey Client'), 'signer 2 is waiting on signer 1');
  const early = await call(`/api/sign/${t2}/submit`, { method: 'POST', body: { consent: true, fields: [] }, raw: true });
  ok(early.status === 409, 'signer 2 submit rejected before their turn');

  // signer 1 flow
  const info1 = await call(`/api/sign/${t1}`);
  ok(info1.turn === true && info1.signer.status === 'viewed', 'signer 1 view recorded');
  const myFields = info1.fields.filter((f) => f.mine);
  ok(myFields.length === 6, 'signer 1 sees their 6 fields');
  const bad = await call(`/api/sign/${t1}/submit`, { method: 'POST', body: { consent: true, fields: [] }, raw: true });
  ok(bad.status === 400, 'missing required signature rejected');
  await call(`/api/sign/${t1}/consent`, { method: 'POST', body: {} });
  const sub1 = await call(`/api/sign/${t1}/submit`, {
    method: 'POST',
    body: { consent: true, fields: myFields.map((f) => ({ id: f.id, value: f.type === 'checkbox' ? true : f.type === 'text' ? 'Acme Co.' : ['signature', 'initials'].includes(f.type) ? PNG : null })) },
  });
  ok(sub1.ok && !sub1.completed && sub1.status === 'sent', 'signer 1 signed; envelope still out');
  const again = await call(`/api/sign/${t1}/submit`, { method: 'POST', body: { consent: true, fields: [] }, raw: true });
  ok(again.status === 409, 'double submit rejected');

  const mid = (await call(`/api/envelopes/${envId}`)).envelope;
  ok(mid.signers[0].status === 'signed' && mid.signers[1].is_turn && mid.signers[1].notified_at, 'signer 2 notified after signer 1 signed');
  const dateField = mid.fields.find((f) => f.type === 'date' && f.signer_id === mid.signers[0].id);
  ok(/^\d{2}\/\d{2}\/\d{4}$/.test(dateField.value), 'date field auto-filled');

  // signer 2 flow
  const inf2 = await call(`/api/sign/${t2}`);
  ok(inf2.turn === true, 'signer 2 now up');
  const sub2 = await call(`/api/sign/${t2}/submit`, {
    method: 'POST',
    body: { consent: true, fields: inf2.fields.filter((f) => f.mine).map((f) => ({ id: f.id, value: f.type === 'signature' ? PNG : null })) },
  });
  ok(sub2.completed === true, 'envelope completed after last signer');

  // completed document
  const done = (await call(`/api/envelopes/${envId}`)).envelope;
  ok(done.status === 'completed' && done.completed_sha256, 'completed status + hash stored');
  const pdfRes = await call(`/api/sign/${t2}/completed`, { raw: true });
  const bytes = Buffer.from(await pdfRes.arrayBuffer());
  ok(bytes.subarray(0, 5).toString() === '%PDF-', 'completed PDF served to signer');
  ok(bytes.length > pdfBytes.length, 'completed PDF larger than original (stamps + certificate)');
  ok(crypto.createHash('sha256').update(bytes).digest('hex') === done.completed_sha256, 'served file matches stored SHA-256');
  ok(done.events.map((e) => e.type).join(',').includes('created,sent,invitation_sent,viewed,consented,signed,invitation_sent,viewed,signed,completed'), 'audit trail complete');
  const outbox = fs.readdirSync(path.join(root, 'storage', 'outbox')).filter((f) => f.includes('completed-smoke-test'));
  ok(outbox.length >= 3, 'completion emails written for signers + owner');

  fs.writeFileSync(path.join(root, 'docs', 'smoke-completed.pdf'), bytes);
  console.log('  (completed PDF copied to docs/smoke-completed.pdf for inspection)');

  // ---------- signer verification: email one-time code ----------
  console.log('\nVerification: email code');
  const mk = async (title, extra) => {
    const fd = new FormData();
    fd.append('title', title);
    fd.append('pdf', new Blob([pdfBytes], { type: 'application/pdf' }), 'sample-agreement.pdf');
    const { envelope: e } = await call('/api/envelopes', { method: 'POST', form: fd });
    await call(`/api/envelopes/${e.id}`, {
      method: 'PUT',
      body: { signing_order: 'parallel', signers: [{ name: 'Val Verify', email: 'verify@example.com', access_code: extra.code || '' }], fields: [{ signer_index: 0, type: 'signature', page: 2, x: 0.21, y: 0.2, w: 0.36, h: 0.05 }], ...extra.body },
    });
    return e.id;
  };
  extraIds.push(await mk('Email Code Test', { body: { auth_method: 'email_code' } }));
  const e1 = (await call(`/api/envelopes/${extraIds[0]}/send`, { method: 'POST' })).envelope;
  const vt = tokenFromLink(e1.signers[0].link);
  const gated = await call(`/api/sign/${vt}`);
  ok(gated.auth?.method === 'email_code' && gated.auth.verified === false && !gated.fields, 'unverified signer gets no document details');
  ok((await call(`/api/sign/${vt}/pdf`, { raw: true })).status === 403, 'PDF blocked before verification');
  ok((await call(`/api/sign/${vt}/submit`, { method: 'POST', body: { consent: true, fields: [] }, raw: true })).status === 403, 'submit blocked before verification');
  const rc = await call(`/api/sign/${vt}/request-code`, { method: 'POST', body: {} });
  ok(rc.ok && rc.sent_to.includes('@example.com') && !rc.sent_to.startsWith('verify@'), 'one-time code sent to masked address');
  ok((await call(`/api/sign/${vt}/request-code`, { method: 'POST', body: {}, raw: true })).status === 429, 'resend rate-limited');
  const codeFile = fs.readdirSync(path.join(root, 'storage', 'outbox')).filter((f) => f.includes('verification-code')).sort().pop();
  const code = /code is: (\d{6})/.exec(fs.readFileSync(path.join(root, 'storage', 'outbox', codeFile), 'utf8'))?.[1];
  ok(code, 'code found in outbox email');
  const wrong = await call(`/api/sign/${vt}/verify`, { method: 'POST', body: { code: '000000' }, raw: true });
  ok(wrong.status === 401, 'wrong code rejected');
  const right = await call(`/api/sign/${vt}/verify`, { method: 'POST', body: { code } });
  ok(right.verified === true && [...jar.keys()].some((k) => k.startsWith('sv_')), 'correct code sets verification cookie');
  const open = await call(`/api/sign/${vt}`);
  ok(open.auth.verified === true && open.fields.length === 1 && open.turn === true, 'verified signer sees the document');
  ok((await call(`/api/sign/${vt}/pdf`, { raw: true })).status === 200, 'PDF served after verification');
  const done1 = await call(`/api/sign/${vt}/submit`, { method: 'POST', body: { consent: true, fields: [{ id: open.fields[0].id, value: PNG }] } });
  ok(done1.completed === true, 'verified signer completed the envelope');
  const ev1 = (await call(`/api/envelopes/${extraIds[0]}`)).envelope;
  ok(ev1.signers[0].verified_method === 'email_code' && ev1.events.some((e) => e.type === 'verification_failed') && ev1.events.some((e) => e.type === 'verified'), 'verification recorded in audit trail');

  // ---------- signer verification: sender access code ----------
  console.log('\nVerification: access code');
  extraIds.push(await mk('Access Code Test', { body: { auth_method: 'access_code' } }));
  const noCode = await call(`/api/envelopes/${extraIds[1]}/send`, { method: 'POST', raw: true });
  ok(noCode.status === 400, 'send refused when access code missing');
  await call(`/api/envelopes/${extraIds[1]}`, { method: 'PUT', body: { auth_method: 'access_code', signers: [{ name: 'Val Verify', email: 'verify@example.com', access_code: 'Blue-Sky-42' }], fields: [{ signer_index: 0, type: 'signature', page: 2, x: 0.21, y: 0.2, w: 0.36, h: 0.05 }] } });
  const e2 = (await call(`/api/envelopes/${extraIds[1]}/send`, { method: 'POST' })).envelope;
  ok(e2.signers[0].access_code === 'Blue-Sky-42', 'access code visible to sender on dashboard data');
  const at = tokenFromLink(e2.signers[0].link);
  ok((await call(`/api/sign/${at}`)).auth.method === 'access_code', 'access-code envelope is gated');
  for (let i = 0; i < 4; i++) await call(`/api/sign/${at}/verify`, { method: 'POST', body: { code: 'nope' }, raw: true });
  const fifth = await call(`/api/sign/${at}/verify`, { method: 'POST', body: { code: 'nope' }, raw: true });
  ok(fifth.status === 423, 'locked after 5 wrong access codes');
  ok((await call(`/api/sign/${at}/verify`, { method: 'POST', body: { code: 'blue-sky-42' }, raw: true })).status === 423, 'lockout blocks even the right code');
  const evLocked = (await call(`/api/envelopes/${extraIds[1]}`)).envelope;
  ok(evLocked.events.some((e) => e.type === 'verification_locked'), 'lockout logged');

  console.log(`\nPASS – ${passed} checks`);
} catch (err) {
  console.error('\n' + err.message);
  console.error('--- server log ---\n' + serverLog);
  process.exitCode = 1;
} finally {
  for (const id of [envId, ...extraIds].filter(Boolean)) { try { await call(`/api/envelopes/${id}`, { method: 'DELETE' }); } catch { /* ignore */ } }
  server.kill();
  setTimeout(() => { try { fs.rmSync(tmp, { recursive: true, force: true }); } catch { /* ignore */ } }, 300);
}
