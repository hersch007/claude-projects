# Start Performance — Lifeward Landing

Customer-facing landing page for Lifeward: visitors are greeted by "Rachel," a
warm, nurse-toned assistant persona, and guided through:

- **Yes, I'm interested. Please call now.** → talk right now or schedule a callback (Mon–Fri, 9am–5pm Eastern)
- **Yes, send me more information.** → collects an email, sends the info package
- **No, I'm not interested.** → logs a lost lead

Every branch writes into the Core System's own tables — `sp_contacts`,
`sp_leads`, `sp_tasks`, `sp_notes`, `sp_activity` — the same tables every other
Core (Sales, Service, Intelligence) already reads from. Nothing here uses a
bespoke leads table.

## What's real vs. stubbed

The request that produced this plugin assumed a working Salesforce
integration and an established "Rachel" call-handling system already existed
in the Start Performance platform. **Neither did** — a full audit of
`start-performance-platform/` found zero Salesforce references anywhere (the
only CRM integration in the codebase is HubSpot, for one client), and no
call-handling/voice/persona code beyond a slide deck. So:

| Piece | Status |
|---|---|
| Landing page, Rachel persona copy, guided funnel | **Real** — fully working |
| Contacts / Leads / Tasks / Notes / Activity log wiring | **Real** — writes to the Core System's actual tables |
| Admin "Lifeward Leads" dashboard view, KPI cards, CSV export | **Real** |
| Call-center notification | **Stub** — emails a fallback address today; set a webhook URL in Settings → Lifeward to POST to a real dialer/ticketing system instead. See `includes/call-center.php`. |
| Salesforce sync | **Mock** — logs what it would send to `sp_activity` and returns a synthetic `SF-MOCK-...` id. See the header comment in `includes/class-salesforce-client.php` for exactly what's needed to make it real (Connected App credentials, instance URL, field mapping). |

Do not represent the call-center or Salesforce pieces as "connected" until
those two files are updated with real credentials/endpoints.

## Files

```
start-performance-lifeward.php       Main plugin: registration, dashboard bridge, shortcode, CSV export
includes/rest-endpoints.php          sp-lifeward/v1/step — the whole funnel state machine
includes/class-salesforce-client.php Salesforce sync — MOCK, see header comment to go live
includes/call-center.php             Call-center notify — webhook if configured, else fallback email
includes/settings.php                Settings → Lifeward tab
templates/landing.php                [sp_lifeward_landing] shortcode markup
templates/views/lifeward-leads.php   Admin "Lifeward Leads" dashboard view
assets/lifeward-widget.js            Thin frontend renderer — all copy/branching is server-side
assets/lifeward-widget.css           Styling
```

## Install

1. Copy this folder into `start-performance-platform/plugin/` alongside the
   other Core plugins.
2. Deploy like any other Core: add an entry to `deploy.ps1`'s `$PLUGINS` map,
   and add a `lifeward` entry to `$SITES` with the correct cPanel path once
   the Lifeward WordPress site's hosting path is known (it wasn't in scope
   here — this is a new client site, not one of the existing named sites).
3. Activate the plugin. It requires the Start Performance core plugin to be
   active first (same soft-dependency pattern as every other Core addon).
4. Create a page with the `[sp_lifeward_landing]` shortcode as its content —
   that's the "main page" the customer lands on.
5. In the app, go to Settings → Lifeward to set the call-center webhook URL
   (or leave it blank to use the fallback email), and edit Rachel's greeting
   and the info-package email content.
6. Leads show up under Sales Core → Lifeward Leads.

## Not yet wired up

- **Real Salesforce credentials.** Nothing will happen in Salesforce itself
  until `includes/class-salesforce-client.php` is filled in per its header
  comment.
- **Real call-center endpoint.** Until a webhook URL is set, "immediately
  notify the call center" sends an email instead — real, but not a call
  center integration.
- **Deployment target for the Lifeward site itself** — `deploy.ps1` only
  knows about `smti`, `sp`, `fruth`, `wts-app`, and `fiber`. A `lifeward`
  entry needs the site's actual server path before this can ship the same
  way the others do.
