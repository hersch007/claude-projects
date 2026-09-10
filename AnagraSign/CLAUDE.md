# AnagraSign – notes for Claude

Self-hosted e-signature app (Adobe Sign style). Node 22.13+ / Express 5 / `node:sqlite` / pdf-lib.
No build step: browser code is plain ES modules in `public/`, vendor libs (pdf.js, signature_pad)
are served straight from `node_modules` at `/vendor/*`.

## Run

- `npm start` (or `npm run dev` for auto-reload), then http://localhost:3900
- `.env` is required (copy from `.env.example`); `ADMIN_PASSWORD` + `SESSION_SECRET` must be set
- `npm run sample` writes `docs/sample-agreement.pdf`; `npm run smoke` runs the end-to-end API test
- SMTP unset → emails land in `storage/outbox/*.txt`; signing links are also on the dashboard

## Key conventions

- Field geometry: `x,y,w,h` are fractions (0–1) of page width/height with top-left origin.
  Convert to PDF points in `server/services/pdf.js` (`y_pdf = height - (y+h)*height`).
- Signer status: pending → viewed → signed | declined. Envelope: draft → sent → completed | declined | voided.
- Sequential order: a signer may act only when every signer with a lower `order_index` is `signed`
  (`isSignersTurn` in `services/workflow.js`). Parallel: everyone at once.
- `date` and `name` fields are filled server-side at submit; signers cannot edit them.
- Signer verification (`envelopes.auth_method`: none | email_code | access_code) gates every
  `/api/sign/:token/*` route except the info GET, `request-code` and `verify`. Passing sets a
  per-signer HMAC cookie (`sv_<id>`, 2 h) checked by `requireVerified` in `routes/sign.js`.
  One-time codes: 6 digits, 15 min TTL, 60 s resend limit, 5 attempts then 15 min lock.
  Access codes are stored plain in `signers.access_code` so the sender can re-share them.
- Signature/initials values are PNG data URLs (transparent background), size-capped by `maxSignatureBytes`.
- Drafts are edited with a full replace of signers+fields (`PUT /api/envelopes/:id`, fields reference `signer_index`).
- Never edit a non-draft envelope's fields; the audit trail depends on it.
- `storage/` is git-ignored and holds the DB, originals and signed PDFs. Deleting an envelope removes both files.
- Production targets: two separate SiteGround accounts, `sign.grouprb.com` and
  `sign.partsofpractice.com` (independent instances, no shared data). See
  [docs/deploy-siteground.md](docs/deploy-siteground.md). Per-site secrets are in `deploy/*.env`
  (git-ignored, generated Sep 9 2026) — don't regenerate SESSION_SECRET once a site is live, it
  invalidates every signer's in-progress session.

## When changing the certificate page

Only Helvetica (WinAnsi) is embedded, so run text through `safeText()` before drawing.
Keep the SHA-256 lines: the completed hash is stored in the DB after saving, so it is not printed
inside the PDF itself (printing it would change the hash).
