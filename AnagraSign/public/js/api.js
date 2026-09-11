export async function api(path, { method = 'GET', body, form } = {}) {
  const opts = { method, headers: {} };
  if (form) opts.body = form;
  else if (body !== undefined) { opts.headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(body); }
  const r = await fetch(path, opts);
  let data = null;
  try { data = await r.json(); } catch { /* non-JSON */ }
  if (r.status === 401 && !path.startsWith('/api/sign') && path !== '/api/auth/login') {
    location.href = '/login.html?next=' + encodeURIComponent(location.pathname + location.search);
    throw new Error('Not signed in');
  }
  if (!r.ok) throw new Error(data?.error || `Request failed (${r.status})`);
  return data;
}

export const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
export const fmtDate = (iso) => (iso ? new Date(iso).toLocaleString() : '—');

let toastTimer;
export function toast(msg, type = '') {
  let el = document.getElementById('toast');
  if (!el) { el = document.createElement('div'); el.id = 'toast'; document.body.append(el); }
  el.textContent = msg;
  el.className = `show ${type}`;
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => { el.className = ''; }, type === 'error' ? 5000 : 2600);
}

export function copyText(text) {
  return navigator.clipboard.writeText(text).then(() => toast('Copied to clipboard'), () => window.prompt('Copy this link:', text));
}
