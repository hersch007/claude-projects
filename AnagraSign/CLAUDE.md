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

- **Two shared-password roles, not per-person accounts:** `admin` and `user`, one login screen
  with a role toggle (`public/login.html`). Passwords live in the `app_auth` table (scrypt-hashed),
  changeable from the Settings modal — `ADMIN_PASSWORD` only seeds the admin row on first startup
  (or force-resets it if `RESET_ADMIN_PASSWORD=1`); there is no env var for the user password, it
  starts disabled until an admin sets one. `requireAuth` (either role) gates everyday document
  routes; `requireAdmin` additionally gates void/delete and changing the *other* role's password.
  Changing your *own* password requires the current one; an admin resetting the *other* role's
  password does not. See `server/auth.js`.
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
- `imported` is a fourth terminal envelope status (alongside completed/declined/voided), for
  already-signed PDFs uploaded from elsewhere purely for record-keeping (`POST /api/envelopes/import`).
  It skips draft/sent/signing entirely, has no signers/fields rows, and its "completed" file is the
  original PDF plus an **Import Record** page (`buildImportRecordPdf` in `services/pdf.js`) — a
  disclosure of what the importer typed (source, claimed signed date, note), explicitly NOT a
  signature certificate, since AnagraSign never witnessed that signing. Never reuse
  `buildCompletedPdf`'s certificate language for imports; the two must stay visually and textually
  distinct so nobody mistakes an import for a real AnagraSign-witnessed signature.
- Production targets: `sign.grouprb.com` and `sign.partsofpractice.com`, same SiteGround account
  (GoGeek), separate Node.js Projects. See [docs/deploy-siteground.md](docs/deploy-siteground.md).
  Per-site secrets are in `deploy/*.env` (git-ignored, generated Sep 9 2026) — don't regenerate
  SESSION_SECRET once a site is live, it invalidates every signer's in-progress session.
- **`STORAGE_DIR` is mandatory on SiteGround.** Confirmed by diagnostic testing Sep 11 2026: every
  "Save and Deploy" extracts into a brand-new timestamped `app_source` folder and discards the
  old one, so anything under `rootDir` (the default `storage/`) is wiped on every single redeploy
  — database included. `config.js` reads `STORAGE_DIR` as an absolute-path override for exactly
  this reason; it must point outside the versioned deploy folder (one level above `public_html`
  works and isn't even web-servable). Never remove that env var check or assume `storage/` persists
  on a host you haven't verified — confirm with a write-then-redeploy-then-read probe first, the
  way this one was found, rather than assuming.
- SiteGround's edge caches responses by URL fairly aggressively, including JSON API responses and
  even on what should be one-off mutations (saw a `DELETE` keep returning a stale cached 404 for
  several calls after it had already actually succeeded). When manually poking the live API to
  verify something, append a cache-busting query string (`?cb=<timestamp>`) rather than trusting a
  repeated identical request.

## When changing the certificate page

Only Helvetica (WinAnsi) is embedded, so run text through `safeText()` before drawing.
Keep the SHA-256 lines: the completed hash is stored in the DB after saving, so it is not printed
inside the PDF itself (printing it would change the hash).
