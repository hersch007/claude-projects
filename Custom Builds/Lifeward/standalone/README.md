# Lifeward — Standalone (no WordPress)

Same funnel as before — Rachel greets the visitor, then:

- **Yes, I'm interested. Please call now.** → talk right now or schedule a callback (Mon–Fri, 9am–5pm Eastern)
- **Yes, send me more information.** → collects an email, sends the info package
- **No, I'm not interested.** → logs a lost lead

but with zero WordPress dependency. Plain PHP + SQLite, deployable as a plain
folder on any PHP host — no core plugin, no `sp_public_frontend` option, no
plugin activation, no wp-admin.

## Stack

- **Frontend**: `index.php` (renders Rachel + the 3 buttons) + `assets/app.js` (thin fetch-based renderer) + `assets/style.css`
- **Backend**: `api.php`, a single POST endpoint driving the whole funnel state machine (`inc/funnel.php`)
- **Storage**: SQLite via PDO (`inc/db.php`) — one file, `data/lifeward.sqlite`, created automatically on first request. No separate database server or credentials needed.
- **Settings**: `data/settings.json` (greeting text, call-center webhook/fallback email, info-package email content, admin password hash) — edited through `/admin/settings.php`, not by hand.
- **Admin**: `/admin/` — single shared password (set on first visit via `/admin/setup.php`), leads list + CSV export, settings form.

## AI chat ("ask me anything")

A floating chat launcher (bottom-right) sits alongside the guided funnel — free-form conversation via Claude (Anthropic), same API LAWN ACE uses. It's disabled until you set an API key at `/admin/settings.php` (get one at console.anthropic.com). The persona and safety rules (no medical advice, no diagnoses, no outcome guarantees, no claiming to be a clinician, no fabricated pricing) live in `inc/chat.php` — read `lw_chat_system_prompt()` before changing anything, this is the part that keeps the assistant honest for a regulated medical-device company.

It shares the same session as the button funnel (`sessionStorage` key `lw_session_token`), so a lead captured through chat and one captured through the buttons are the same record if a visitor uses both. Transcripts are viewable at `/admin/chats.php`.

## What's real vs. stubbed

Same honesty as the WordPress version — nothing here pretends to be more
connected than it is:

| Piece | Status |
|---|---|
| Landing page, Rachel persona copy, guided funnel | **Real** |
| Lead storage (SQLite), admin dashboard, CSV export | **Real** |
| Call-center notification | **Stub** — emails a fallback address until you set a webhook URL in `/admin/settings.php`. See `inc/call_center.php`. |
| Salesforce sync | **Mock** — logs what it would send, returns a synthetic `SF-MOCK-...` id. See the header comment in `inc/salesforce.php` for exactly what's needed to make it real. |

## Deploy (cPanel, plain PHP, no Node/WordPress required)

1. Upload the whole `standalone/` folder's contents to a plain directory under
   `public_html/` — e.g. `public_html/lifeward/` (via File Manager or FTP; a
   ready-to-upload zip is provided alongside this README).
2. Make sure the `data/` folder is writable by PHP (usually fine by default on
   cPanel; if not, `chmod 775 data/`).
3. Visit `https://yourdomain.com/lifeward/check.php` to confirm the host can
   run everything (PHP version, `pdo_sqlite`, writable `data/`). Delete
   `check.php` once it's all green — no need to leave a diagnostic page live.
4. Visit `/admin/setup.php` once to set the admin password.
5. Visit `/` — that's the live landing page.
6. Visit `/admin/` any time to see captured leads or change settings
   (greeting text, call-center webhook, info-package email).

## Requirements

- PHP 7.4+ with the `pdo_sqlite` extension (nearly universal on shared PHP
  hosting — `check.php` confirms it for your specific host)
- A writable `data/` directory
- `mail()` working on the host for the fallback call-center notification and
  info-package emails to actually send (also nearly universal on cPanel)

## Not yet wired up

- **Real Salesforce credentials** — see `inc/salesforce.php` header for exactly what to fill in.
- **Real call-center endpoint** — set the webhook URL in `/admin/settings.php`; until then it emails the fallback address instead.
