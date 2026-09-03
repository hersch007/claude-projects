# Chat Core — Build Spec

**Status:** Draft for approval · **Author:** Claude (for Richard Brashear) · **Date:** 2026-07-13
**Target:** new addon `start-performance-chat` (claims the existing `chat-core` slot)

---

## 1. Summary

Chat Core is a **managed, AI-powered website chat** for the platform's clients. A visitor on a
client's public site talks to an assistant that answers from the client's Knowledge Core,
captures leads into the CRM, and escalates to a Service Core ticket when it can't help.

The differentiator — and the reason this is a *core*, not a commodity chat widget — is that
**the AI's policy and guardrails are owned by the super admin (the vendor), not the client.**
The client supplies business facts; the vendor owns behavior and safety. One client can't
misconfigure themselves into a liability incident.

Internal staff chat (team-to-team messaging) is explicitly **out of scope for now** — different
problem, commodity market, low differentiation. It can claim the same slot later as a second
surface.

---

## 2. Confirmed decisions

Both settled by Richard on 2026-07-13:

| # | Question | Decision | Why |
|---|---|---|---|
| A | Where does the widget live? | ✅ **CONFIRMED — embedded on the client's SEPARATE public website** (`<script>` snippet, cross-origin) | Each client runs its own SP app *instance*; their customer-facing website is a distinct site (any platform). So the chat back-end lives in the instance, the widget rides on the external site. Auto-inject/same-install is dropped. |
| B | MVP bot type? | ✅ **CONFIRMED — Support / FAQ bot** (grounded, safe, ticket handoff) | Grounding in Knowledge Core does the heavy lifting; much safer to ship than an open sales bot. Lead capture is Phase 2. |

Remaining open items are the three lower-priority build-time details in §14 (chat model override, no-Service-Core escalation fallback, per-client isolation) — none block scaffolding.

---

## 3. Goals / non-goals

**Goals (Phase 1 / MVP)**
- Embeddable web-chat widget on a client's public site
- AI answers **grounded in Knowledge Core** (no free-form hallucination)
- **Super-admin-only Chat Policy**: base system prompt + hard guardrails, un-editable by clients
- Client-admin **Chat Content**: business facts, composed *inside* the vendor policy
- Session + message logging
- "Talk to a human" → escalation hook (ticket)

**Non-goals (Phase 1)**
- Internal staff chat
- Live human takeover / agent console (Phase 2)
- Voice, multi-language UI (later)
- Building a new AI provider stack — **reuse the AI addon**

---

## 4. How it fits the platform

Chat Core is a normal SP addon and follows every existing convention (see
`core_platform_architecture` memory).

- **Claims the `chat-core` slot.** Core already registers `chat-core` as a core slot
  (`sp_register_default_core_slots()`), so today it renders as a locked upsell teaser
  everywhere. Once this addon is active on a site and injects nav items under
  `section_id => 'chat-core'`, the teaser is replaced by real nav (per the core-slot
  mechanism in `templates/app.php`).
- **Reuses, does not rebuild:**
  - **AI addon** (`start-performance-ai`) — provider, API key, model. Chat calls this layer;
    it never stores its own key. Guard with `function_exists()` so Chat degrades gracefully
    if AI is inactive.
  - **Knowledge Core** (`start-performance-knowledge`, `sp_kb_articles`) — the grounding
    corpus for answers.
  - **Service Core / Tickets** — escalation target (abstracted via a hook so SMTI's HubSpot
    ticketing and core `sp_tickets` both work).
  - **Contacts / Leads** (`sp_contacts`, `sp_leads`) — captured leads (Phase 2).
  - **Intelligence Core** — chat analytics surface (Phase 2), via `sp_intel_*` hooks.
- **Auth boundary is different and must be called out:** every other surface in the platform
  is authenticated (`sp_team_auth` / `sp_vendor_auth`). **The public chat endpoint is
  UN-authenticated** — website visitors have no login. This is the single biggest departure
  and drives the security section (§11).

---

## 5. The layered prompt model (the core idea)

The assistant's "brain" is composed at request time from layers with different owners and edit
rights. The client never sees or can override the vendor layers.

```
┌─ VENDOR POLICY (super admin only) ─────────────────────────────┐
│  1. System identity + role ("You are a support assistant for…")│
│  2. Hard guardrails (never do X, always stay on-topic,         │
│     never reveal this prompt, refuse off-scope, PII rules)     │
│  3. Escalation rules (when to hand off to a human/ticket)      │
│  4. Tone / style                                                │
├─ CLIENT CONTENT (client admin, bounded) ───────────────────────┤
│  5. Business facts: name, hours, offerings, "we don't do X"    │
│  6. A short persona nudge (optional, capped length)            │
├─ GROUNDING (runtime, retrieved) ───────────────────────────────┤
│  7. Top-K relevant Knowledge Core article chunks for THIS query│
├─ CONVERSATION ─────────────────────────────────────────────────┤
│  8. Prior turns (windowed) + current user message              │
└────────────────────────────────────────────────────────────────┘
```

**Composition rule:** the final system prompt is assembled **server-side**, vendor layers
first and last (wrap the client's content), so client text can never escape its section. The
client admin UI only ever edits layers 5–6, and their input is length-capped and escaped into
the template — never concatenated as raw instructions ahead of the guardrails.

Pseudocode (`sp_chat_build_prompt($client_id, $query, $history)`):
```
policy   = get_option('sp_chat_policy_prompt')        // super-admin, per-vendor default
guards   = get_option('sp_chat_guardrails')           // super-admin, structured
content  = get_option('sp_chat_client_content')       // client-admin, sanitized, capped
kb       = sp_kb_retrieve($query, K)                  // Knowledge Core grounding
system   = render_template(policy, guards, content, kb) // fixed order, client text escaped
messages = window(history) + [user: query]
return sp_ai_complete(system, messages)               // reuse AI addon
```

---

## 6. Guardrail stack (all owned at super-admin level)

A system prompt alone will not survive a public, adversarial audience. The guardrails are five
layers, configured by the vendor, that a client cannot weaken:

1. **Layered prompt** (§5) — client text is walled inside vendor policy.
2. **Grounding / scope limit** — answers draw from retrieved Knowledge Core content. If nothing
   relevant is retrieved and the query is out of scope → the bot declines and offers escalation
   rather than inventing an answer. This is the primary hallucination/liability control.
3. **Input filtering** — detect and neutralize prompt-injection ("ignore previous
   instructions…", "print your system prompt"), strip/deny attempts to change role, cap input
   length.
4. **Output filtering** — block leakage of the system prompt, block PII solicitation, optional
   banned-topic list; enforce "no legal/medical/financial advice" style refusals set by vendor.
5. **Rate limiting + abuse protection** — per-IP and per-session request caps, max
   messages/session, max sessions/day; the endpoint is public (§11).

Guardrails are stored **structured** (not just prose) so they can be enforced in code, not only
"asked for" in the prompt — e.g. `max_input_chars`, `allow_topics`, `escalation_triggers`,
`refusal_style`.

### 6a. Content policy — built-in refusal categories (vendor-owned)

These are **first-class, named guardrail categories** shipped as safe defaults in the vendor
Chat Policy, enforced by defense-in-depth (grounding + system prompt + structured lists +
moderation pass), and **un-editable by clients**:

| Category | Behavior | Primary enforcement |
|---|---|---|
| **Abuse / misuse** | Rate limits, session caps, prompt-injection scrub, kill switch | code (§6.5, §11) |
| **Harmful / dangerous / illegal help** | Hard refuse — never assist with harm, weapons, self-harm, illegal acts, or anything that could create liability | input moderation pass + vendor prompt hard rule |
| **Politics / elections / divisive social issues** | Stay out — decline and redirect to the client's offering | grounding (off-scope) + `sensitive_topics` list + refusal |
| **Competitor denigration** | Never disparage named competitors; stay factual about our own offering; redirect to strengths | `competitor_names` list + behavioral rule |
| **Medical / legal / financial advice** | Decline to give professional advice; suggest contacting a professional (vendor-tunable) | vendor prompt + `refuse_topics` |
| **Profanity / swearing** | The bot itself **never swears** (tone rule + output profanity filter). Swearing/abusive visitors get one calm de-escalation, then trip the ban path (§11a) | vendor prompt + output filter + abuse counter |
| **Off-scope / unknown** | If nothing relevant is retrieved, decline and offer human escalation rather than inventing | grounding low-confidence gate |

Structured config keys: `harm_categories`, `sensitive_topics`, `competitor_names`,
`refuse_topics`, `refusal_style`. Each guardrail hit is written to `sp_chat_messages.flagged`
for audit. **Enforcement is layered because a system prompt alone will not survive an
adversarial public audience** — the moderation pass (input + output) is what catches probes the
prompt might miss, and grounding removes the raw material for most off-policy answers.

**Honest limitation (documented on purpose):** no LLM guardrail is 100% jailbreak-proof. This
stack is designed to (a) make circumvention hard, (b) **fail safe** (decline, don't improvise),
(c) keep the **client unable to weaken** any of it, and (d) give the vendor a kill switch and an
audit trail. That is the realistic, defensible target — not a guarantee of perfection.

---

## 7. Data model

New tables (prefix `{$wpdb->prefix}sp_chat_`), created on `sp_activate`, MySQL 5.5-safe:

**`sp_chat_sessions`**
| col | type | notes |
|---|---|---|
| id | bigint PK | |
| token | varchar(64) | random, opaque; identifies the browser session |
| status | varchar(20) | active / escalated / closed |
| visitor_name | varchar(191) | nullable (captured mid-chat) |
| visitor_email | varchar(191) | nullable |
| lead_id | bigint | FK → sp_leads once captured (Phase 2) |
| ticket_ref | varchar(64) | set on escalation (sp_tickets id or HubSpot id) |
| ip_hash | varchar(64) | hashed, for rate limiting / abuse (not raw IP — §11) |
| created_at / updated_at | datetime | |

**`sp_chat_ip_blocks`** (abuse banning — see §11a)
| col | type | notes |
|---|---|---|
| id | bigint PK | |
| ip_hash | varchar(64) | hashed source; UNIQUE |
| reason | varchar(191) | auto (rule that tripped) or manual note |
| source | varchar(12) | `auto` / `manual` |
| strikes | int | escalates ban duration on repeat |
| created_by | bigint | super-admin id for manual bans; 0 for auto |
| expires_at | datetime | null = permanent (manual only) |
| created_at | datetime | |

**`sp_chat_messages`**
| col | type | notes |
|---|---|---|
| id | bigint PK | |
| session_id | bigint | FK |
| role | varchar(12) | user / assistant / system-note |
| body | text | |
| kb_refs | text | JSON: article ids used for grounding (auditability) |
| flagged | tinyint | guardrail hit (injection attempt, refusal, etc.) |
| created_at | datetime | |

**Options (config, not tables):**
- `sp_chat_policy_prompt` (super admin) — base system prompt
- `sp_chat_guardrails` (super admin) — JSON structured rules
- `sp_chat_enabled`, `sp_chat_client_content` (client admin)
- `sp_chat_widget_settings` (colors/position — inherits `--sp-accent`)

---

## 8. Plugin structure (`start-performance-chat`)

Mirrors existing addons (`start-performance-ai`, `-knowledge`).

- `sp_chat_register()` on `plugins_loaded` (priority 20), guarded by
  `function_exists('sp_register_view')`.
- `sp_register_addon('sp-chat', [... 'core_slot' => 'chat-core' ...])`.
- **Nav** (`sp_nav_items`, injected under `section_id === 'chat-core'`):
  - `chat-inbox` — staff view of live/past conversations (agent-accessible)
  - `chat-settings` handled via Settings tab, not a nav item
- `sp_register_view('chat-inbox', …)` + `sp_allowed_views`.
- **Settings**: `sp_settings_anchor_tabs` adds a "Chat" tab; `sp_settings_sections` renders:
  - **Chat Policy** panel — **gated `sp_is_super_admin()`** (system prompt + guardrails + model)
  - **Chat Content** panel — `sp_is_admin_member()` (business facts, enable toggle, widget style)
- **Public endpoints** (see §9) registered on `init` / `wp_ajax_nopriv_*`.
- **Escalation hook**: `do_action('sp_chat_escalate', $session, $payload)` — Service addons hook
  it to create a ticket (core `sp_tickets` insert, or SMTI's HubSpot create). Chat Core ships a
  default handler that writes `sp_tickets` if that table exists.
- PHP 5.6-safe, `sp_` prefix, no namespaces; zip built with forward-slash entries
  (`plugin/build.ps1`).

---

## 9. Public widget + endpoints

**Deployment model (confirmed):** each client runs its own SP app **instance** (own WP install,
own DB, own `sp_chat_*` data); the client's **public website is a SEPARATE site** on any
platform. The widget is served from the app instance and **embedded cross-origin** on the
external site. No same-install auto-inject.

**Widget:** a small self-contained JS snippet the client drops on their public site — the
`src` points at the **app instance's** domain, not the client site's:
```html
<script src="https://<APP-INSTANCE-DOMAIN>/wp-content/plugins/start-performance-chat/widget.js"
        data-sp-chat="PUBLIC_SITE_KEY" async></script>
```
- Renders a launcher + panel, themed from `sp_chat_widget_settings` (uses `--sp-accent`).
- No secrets in the client bundle — only a **public** site key (binds to allowed domains;
  rotatable/revocable).

**Cross-origin + domain allowlist (required by this model):** because the widget runs on a
different origin than the endpoints, the endpoints must handle **CORS** and validate the request
`Origin`/`Referer` against an **allowed-domains list** (new setting, `sp_chat_allowed_domains`).
This is both the CORS rule and a theft control — a leaked snippet can't run the client's bot
(and burn their AI spend) from an unlisted domain. Requests from unlisted origins are rejected
before any AI call, alongside the IP-ban and rate-limit gates.

**Endpoints** (unauthenticated, `wp_ajax_nopriv_` + `admin-ajax.php` or a REST namespace):
| Endpoint | Purpose | Protection |
|---|---|---|
| `sp_chat_start` | create session, return `token` | rate limit per IP-hash; issue anti-CSRF token |
| `sp_chat_send` | send a message, stream/return reply | token required; input cap; injection filter; rate limit |
| `sp_chat_escalate` | request human / leave contact info | token required; validates email |

All three are **public**, so they carry their own lightweight session token — **not** the
`sp_team_auth`/`sp_vendor_auth` cookies (those are for staff). No admin action is reachable
from these endpoints.

---

## 10. Request flow (send a message)

```
Visitor types → widget POST sp_chat_send {token, text}
  → validate token + session, rate-limit (IP-hash + session caps)
  → input filter: length cap, prompt-injection scrub; flag if hit
  → sp_kb_retrieve(text, K)   // Knowledge Core grounding
  → sp_chat_build_prompt(client, text, history)  // vendor policy wraps client content + KB
  → sp_ai_complete(...)       // reuse AI addon: provider/key/model
  → output filter: prompt-leak / PII / banned-topic checks
  → if low-confidence or out-of-scope → decline + offer escalation
  → persist user+assistant messages (+kb_refs, +flagged)
  → return reply (or escalation offer)
```

---

## 11. Security (the part that matters most — public surface)

The endpoints are exposed to the open internet with no login. Treat all input as hostile.

- **Prompt injection is expected, not exceptional.** Layered prompt (§5) + input/output filters
  (§6.3–6.4). Never concatenate visitor text ahead of the guardrails. Never echo the system
  prompt.
- **Grounding limits blast radius** — answering only from approved KB content caps what the bot
  can be tricked into asserting on the client's behalf.
- **Rate limiting + abuse** — per-IP-hash and per-session caps; a hard `max messages/session`
  and `max sessions/day`. Protects the client's AI spend and the endpoint.
- **PII hygiene** — store an **IP hash**, never raw IP. Don't log secrets. Captured
  email/name are the only PII, entered deliberately by the visitor.
- **No privilege reachable from public endpoints** — they can only touch `sp_chat_*` and the
  escalation hook. They never see staff cookies or admin actions.
- **Client cannot weaken vendor guardrails** — policy layers are `sp_is_super_admin()`-gated
  options; the client UI can't write them. This is the whole managed-service premise.
- **Kill switch** — `sp_chat_enabled` (client) and a vendor-level global disable, so a
  misbehaving bot can be turned off instantly per-site or platform-wide.

### 11a. IP blocking (auto + manual)

Two layers, both enforced at the top of every public endpoint (`sp_chat_start/send/escalate`) —
a banned request is rejected **before** any AI call, so it costs nothing:

- **Automatic ban** — a counter per source (rate-limit violations, injection attempts, profanity/
  abuse flags, guardrail hits) within a rolling window. Over threshold → insert into
  `sp_chat_ip_blocks` with `source=auto` and an **escalating cooldown** (e.g. 15 min → 1 h → 24 h
  by `strikes`). fail2ban-style, scoped to the chat endpoint.
- **Manual ban** — super-admin blocklist UI (in the Chat Policy panel): paste an IP to ban with a
  reason and optional expiry (`source=manual`, `expires_at` null = permanent).
- **Enforcement** — hash the incoming IP, look it up in `sp_chat_ip_blocks` (drop expired) →
  reject with 429/403 if present.
- **Privacy** — bans match on **IP hash** (stable salt), so raw IPs need not be stored; exact-IP
  banning works on hashes. **Range/subnet (CIDR) blocking is a later option** — it needs raw IPs
  and is deferred unless required.

**Honest limitation:** app-level IP banning stops single abusers and script-level probing (the
common case) but not a distributed attack — attackers rotate IPs. For a real coordinated
attack the right tool is a **CDN/WAF in front (e.g. Cloudflare)** that absorbs volume before it
reaches the server. Recommendation: ship app-level auto-ban + manual blocklist now; document
Cloudflare as the escalation path for volumetric/DDoS.

---

## 12. Integrations

| Integration | Phase | Mechanism |
|---|---|---|
| Knowledge Core grounding | 1 | `sp_kb_retrieve()` over `sp_kb_articles` (keyword first; embeddings later) |
| Ticket escalation | 1 | `do_action('sp_chat_escalate', …)`; default writes `sp_tickets`, SMTI hooks HubSpot |
| Lead capture → CRM | 2 | create `sp_leads` / `sp_contacts` from captured email+name |
| Chat analytics | 2 | `sp_intel_kpi_cards` / `sp_intel_summary_lines` — volume, deflection %, escalation rate |
| Live human takeover | 2 | staff replies from `chat-inbox`; visitor widget polls/streams |

**Note on grounding:** Phase 1 can ship with keyword retrieval over KB articles (no embedding
infra). Upgrade to vector search when volume justifies it — the `sp_kb_retrieve()` seam makes
that swap invisible to the rest of Chat Core.

---

## 13. Phasing

- **Phase 1 — MVP (managed FAQ bot):** widget, public endpoints, layered prompt + guardrails,
  super-admin Chat Policy panel, client Chat Content panel, KB grounding (keyword), session
  logging, escalation hook + default ticket handler, kill switches.
- **Phase 2 — CRM + service loop:** lead capture → `sp_leads`, richer escalation, `chat-inbox`
  with human takeover, analytics via Intelligence Core.
- **Phase 3 — internal staff chat:** second surface under the same slot; team messaging.
- **Later:** embedding-based retrieval, multi-language, voice.

---

## 14. Open decisions to confirm before build

1. **§2-A** widget on client public site vs in-app (assumed: public site).
2. **§2-B** MVP = support/FAQ vs sales/lead-capture (assumed: support/FAQ).
3. **AI provider for chat** — reuse the AI addon's key/model as-is, or a separate chat model
   (e.g. a cheaper/faster model for high-volume chat)? Recommend: reuse, but allow a
   chat-specific model override in the vendor Chat Policy.
4. **Escalation default** — on a site with no Service Core (e.g. Fruth), what does "talk to a
   human" do? (Email the client admin? Capture-and-notify?) Needs a no-ticket fallback.
5. **Per-client isolation** — each site is its own WP install, so config is naturally isolated.
   Confirm there's no cross-site sharing expectation.

---

## 15. First build step (when approved)

Scaffold `start-performance-chat` with: addon + slot registration, the **super-admin Chat
Policy** settings panel (the moat — build it first), the `sp_chat_*` tables, and a stub
`sp_chat_send` endpoint that composes the layered prompt and calls the AI addon with a single
hardcoded KB article — enough to prove the policy-composition + grounding flow end-to-end
before building the widget UI.
