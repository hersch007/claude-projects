# AnagraSign

A self-hosted e-signature app for contracts, in the spirit of Adobe Sign / DocuSign.

Upload a PDF, drag signature / initials / date / name / text / checkbox fields onto it, assign them to
signers, and send. Each signer gets a unique link, consents to electronic signing, draws or types a
signature, and submits. When everyone has signed, the app stamps every field into the PDF, appends a
**Signature Certificate** page (audit trail, IPs, timestamps, SHA-256 fingerprints), and emails the
completed document to all parties.

No build step. No external services required. Node.js only.

## Features

- Dashboard with upload, status tracking, activity log, reminders, void, delete
- Visual field editor: place, drag, resize, assign to signer, mark required
- Sequential ("one at a time") or parallel signing order
- Signer page: consent screen, draw or type signature, "apply to all", next-field navigation, decline
- Optional signer verification before the document can be opened: a one-time code emailed to the
  signer, or an access code you set per signer and share by phone/text. 5 wrong attempts locks the
  link for 15 minutes; every attempt and the successful verification appear on the certificate.
- Completed PDF with stamped fields + certificate page, SHA-256 of original and completed files
- Full audit trail: created, sent, invitation sent, viewed, consented, signed, declined, completed
- Email via SMTP (nodemailer). Without SMTP, emails are written to `storage/outbox/` so you can copy links.
- Single admin password login with rate limiting

## Quick start

```bash
npm install
cp .env.example .env      # then edit ADMIN_PASSWORD and SESSION_SECRET
npm run sample            # creates docs/sample-agreement.pdf for testing
npm start                 # http://localhost:3900
```

Log in at `/login.html` with `ADMIN_PASSWORD`, click **New document**, upload the sample PDF,
add signers, place fields, **Send for signature**. If SMTP is not configured, open the document on
the dashboard and use **Copy signing link** to test the signer flow yourself.

`npm run smoke` runs an automated end-to-end test against a temporary database (upload, prepare,
send, both signers sign, completed PDF produced).

## Configuration (`.env`)

| Variable | Purpose |
|---|---|
| `PORT` | Port to listen on (default 3900) |
| `BASE_URL` | Public URL used in emails, e.g. `https://sign.startperformance.com` |
| `ADMIN_PASSWORD` | Dashboard password |
| `SESSION_SECRET` | Random string (16+ chars) used to sign the login cookie |
| `MAIL_FROM` | Sender shown on emails |
| `NOTIFY_EMAIL` | Gets "completed" and "declined" notifications |
| `SMTP_HOST/PORT/SECURE/USER/PASS` | SMTP server. Leave `SMTP_HOST` blank to write emails to `storage/outbox/` |
| `TRUST_PROXY` | Set `1` behind nginx / cPanel / Cloudflare so signer IPs are recorded correctly |
| `SECURE_COOKIES` | Set `1` when served over HTTPS |

## Project layout

```
server/
  index.js              Express app, static + vendor serving, error handler
  config.js             Environment / paths
  db.js                 SQLite schema (node:sqlite, no native build) + helpers
  auth.js               HMAC-signed session cookie, admin password check
  routes/auth.js        POST /api/auth/login|logout, GET /api/auth/me
  routes/envelopes.js   Admin API: upload, edit draft, send, remind, void, delete, PDFs
  routes/sign.js        Public signer API by token: info, pdf, consent, submit, decline, completed
  services/pdf.js       pdf-lib: stamp fields into pages, build certificate page, SHA-256
  services/mail.js      nodemailer + outbox fallback + email templates
  services/workflow.js  send / notify / advance / finalize / decline / void logic
public/
  login.html            Admin login
  index.html + js/dashboard.js     Document list + detail panel
  prepare.html + js/prepare.js     Field placement editor
  sign.html + js/sign.js           Signer experience
  js/pdfview.js         pdf.js page rendering + field geometry helpers
  js/api.js             fetch wrapper, toast, helpers
  css/app.css
scripts/
  make-sample-pdf.mjs   Generates docs/sample-agreement.pdf
  smoke-test.mjs        End-to-end API test
storage/                uploads/, completed/, outbox/, anagrasign.db (git-ignored)
docs/                   Notes, sample PDF
```

Field geometry is stored as fractions of the page (0–1, top-left origin) so it is independent of
zoom level, and converted to PDF points (bottom-left origin) when stamping.

## Deploying

Any host that runs Node 22.5+ works: a VPS with nginx in front, cPanel "Setup Node.js App",
SiteGround's Node.js app tool, Railway, Render, Fly.io, etc. — anything using Phusion Passenger
works as-is, since `server/index.js` already listens on `process.env.PORT`. Set `BASE_URL`,
`TRUST_PROXY=1`, `SECURE_COOKIES=1`, SMTP values, and keep `storage/` on persistent disk (it holds
the database and every signed contract). Back it up.

Suggested `pm2` start: `pm2 start server/index.js --name anagrasign --node-args="--disable-warning=ExperimentalWarning"`

**Deploying to SiteGround** (two live instances, `sign.grouprb.com` and
`sign.partsofpractice.com`): see [docs/deploy-siteground.md](docs/deploy-siteground.md) for the
full walkthrough, and `scripts/deploy-siteground.ps1` for repeat deploys once each is set up.
Per-instance production env files with generated secrets live in `deploy/` (git-ignored).

## Legal notes (not legal advice)

In the US, the ESIGN Act and UETA make electronic signatures legally binding when there is
**intent to sign**, **consent to do business electronically**, **association of the signature with
the record**, and **record retention**. AnagraSign implements each: an explicit consent step, a
deliberate "Finish" action, stamping of signatures plus a certificate into the document, and
retained originals, completed files, and an audit trail with IP addresses and timestamps.

Signer verification strengthens the evidence that the right person signed. **Email code** proves the
signer controlled the mailbox at signing time. **Access code** is stronger because it travels over a
second channel (you read it to them by phone or text it), so a forwarded or intercepted email is not
enough. Access codes are stored in plain text in the database so you can re-share them from the
dashboard; keep `storage/` private.

What commercial products add that this app does not (yet):

- Cryptographic PDF signing (PAdES / digital certificates) that PDF readers verify visually
- Identity verification by SMS, knowledge-based questions, or ID document checks
- Compliance certifications (SOC 2, HIPAA BAA, 21 CFR Part 11) and long-term archival guarantees
- Templates, bulk send, reusable forms, in-person signing, API/webhooks

For routine business contracts (service agreements, proposals, NDAs) this is generally sufficient.
For high-stakes or regulated documents, have an attorney review the process.

## Roadmap ideas

- SMS delivery of one-time codes (Twilio)
- Templates and reusable field layouts
- Cryptographic signing of the completed PDF (e.g. with `@signpdf/signpdf` and a certificate)
- Multiple admin users with roles
- Webhooks / Zapier so a completed contract can create a job in the Start Performance platform
