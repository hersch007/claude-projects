// Sends the weekly crawl summary email via Resend — chosen over Gmail API
// specifically to avoid a third Google OAuth flow (GSC, then Google Ads,
// already set up this project; see PHASE2-DESIGN.md §5.1). Falls back to
// logging-only if RESEND_API_KEY isn't configured, so weekly-crawl.js can
// still run (and update scores) even before email is wired up.
const DROP_THRESHOLD = 3; // points — see PHASE2-DESIGN.md §5.3, adjustable

function scoreRow(entry) {
  const delta = entry.newScore - entry.oldScore;
  const isDrop = entry.oldScore !== null && delta <= -DROP_THRESHOLD;
  const deltaLabel = entry.oldScore === null
    ? 'first run'
    : delta === 0 ? 'no change' : `${delta > 0 ? '+' : ''}${delta}`;
  const color = entry.oldScore === null ? '#64748b' : delta > 0 ? '#16a34a' : delta < 0 ? '#dc2626' : '#94a3b8';
  return { ...entry, delta, isDrop, deltaLabel, color };
}

function buildEmailHtml(results) {
  const rows = results.map(scoreRow);
  const drops = rows.filter(r => r.isDrop);

  const dropsSection = drops.length ? `
    <h2 style="color:#dc2626;font-size:16px;margin-top:24px">Score Drops (${DROP_THRESHOLD}+ points)</h2>
    <ul>
      ${drops.map(r => `<li><strong>${r.client}</strong>: ${r.oldScore} &rarr; ${r.newScore} (${r.deltaLabel})</li>`).join('')}
    </ul>
  ` : `<p style="color:#16a34a">No clients dropped ${DROP_THRESHOLD}+ points this week.</p>`;

  const tableRows = rows.map(r => `
    <tr>
      <td style="padding:6px 10px;border-bottom:1px solid #e2e8f0">${r.client}</td>
      <td style="padding:6px 10px;border-bottom:1px solid #e2e8f0;text-align:center">${r.oldScore ?? '—'}</td>
      <td style="padding:6px 10px;border-bottom:1px solid #e2e8f0;text-align:center">${r.newScore}</td>
      <td style="padding:6px 10px;border-bottom:1px solid #e2e8f0;text-align:center;color:${r.color};font-weight:600">${r.deltaLabel}</td>
    </tr>`).join('');

  return `
    <div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto">
      <h1 style="font-size:18px">Weekly SEO Crawl Summary</h1>
      <p style="color:#64748b;font-size:13px">${new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
      ${dropsSection}
      <h2 style="font-size:16px;margin-top:24px">All Clients</h2>
      <table style="width:100%;border-collapse:collapse;font-size:13px">
        <thead><tr>
          <th style="text-align:left;padding:6px 10px;border-bottom:2px solid #e2e8f0">Client</th>
          <th style="padding:6px 10px;border-bottom:2px solid #e2e8f0">Last Score</th>
          <th style="padding:6px 10px;border-bottom:2px solid #e2e8f0">New Score</th>
          <th style="padding:6px 10px;border-bottom:2px solid #e2e8f0">Change</th>
        </tr></thead>
        <tbody>${tableRows}</tbody>
      </table>
    </div>
  `;
}

async function sendSummaryEmail(results, { errors = [] } = {}) {
  const html = buildEmailHtml(results) + (errors.length
    ? `<h2 style="color:#dc2626;font-size:16px;margin-top:24px">Crawl Errors</h2><ul>${errors.map(e => `<li>${e}</li>`).join('')}</ul>`
    : '');

  const apiKey = process.env.RESEND_API_KEY;
  const from = process.env.EMAIL_FROM;
  const to = process.env.EMAIL_TO;

  if (!apiKey || !from || !to) {
    console.warn('RESEND_API_KEY / EMAIL_FROM / EMAIL_TO not fully configured — skipping email send, logging summary only.');
    console.log(html.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim());
    return { sent: false, reason: 'not configured' };
  }

  const { Resend } = require('resend');
  const resend = new Resend(apiKey);
  const drops = results.map(scoreRow).filter(r => r.isDrop);
  const subject = drops.length
    ? `SEO Weekly Summary — ${drops.length} client(s) dropped ${DROP_THRESHOLD}+ points`
    : 'SEO Weekly Summary — all clients stable';

  const { data, error } = await resend.emails.send({ from, to, subject, html });
  if (error) {
    console.error('Failed to send summary email:', error);
    return { sent: false, reason: error.message };
  }
  return { sent: true, id: data.id };
}

module.exports = { sendSummaryEmail, buildEmailHtml, DROP_THRESHOLD };
