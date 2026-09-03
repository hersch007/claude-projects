# Start Performance Platform — Architecture Review
**Date:** 2026-06-11
**Prepared for:** Richard Brashear / Start Performance
**Based on:** PROJECT_VISION.md, ROADMAP.md, DATABASE_NOTES.md, PRICING.md, FIRST_BUILD_TARGET.md, CLAUDE_START_PROMPT.md, current-plugins inventory, screenshots

> **Decision (2026-06-11):** No migration. Existing plugins remain in production and are not part of this build. The platform is being built from scratch. Sections 1–4 below are reference context only. Sections 5–7 (architecture, database, module design) and section 10 (build order) are the active plan.

---

## 1. Asset Inventory

### Documentation
| File | Purpose |
|---|---|
| PROJECT_VISION.md | Source of truth — defines all Cores, AI Layer, philosophy |
| ROADMAP.md | 8-phase build order, migration strategy |
| FIRST_BUILD_TARGET.md | Immediate milestone: Core Platform plugin shell |
| CLAUDE_START_PROMPT.md | Session context and target architecture summary |
| PRICING.md | Commercial structure for all Cores and AI add-ons |

### Existing Plugins (current-plugins/)
| File | System | Version | Type |
|---|---|---|---|
| quote-builder-core-8-2.zip | Quote / Estimate / Proposal | v8.2 | Core logic |
| quote-builder-print-1-6.zip | Quote print/PDF rendering | v1.6 | Output module |
| smti-service-tickets-v1.1.7.8.zip | Service ticket system | v1.1.7.8 | Service module |
| lawnace-chatbot-v3.17.0.zip | AI chatbot (Lawnace client) | v3.17.0 | White-label chatbot |
| FiberCo-AI-Chatbot-Plugin-v1.3.3.zip | AI chatbot (FiberCo client) | v1.3.3 | White-label chatbot |
| fiberco quote render.txt | Quote wizard shortcode | v4.22 | Client-specific frontend |

### Screenshots
| File | Shows |
|---|---|
| Dashboard login pin.png | PIN-based admin login flow |
| dashboard screen.png | Existing admin dashboard UI |
| quote builder.png | Existing quote builder interface |
| customer service request form.png | Service request intake form |
| chat image.png | Chatbot interface |

### Observable Code Patterns (fiberco quote render.txt — 1011 lines)
- Single PHP shortcode function (~1000 lines) — no separation of concerns
- Hardcoded client branding (FiberCo, colors, phone, URLs)
- Hardcoded plan data array inside PHP
- JavaScript state machine embedded in PHP output buffer
- Chat-to-quote handoff via `fibercoQuoteStartFromChat()` — demonstrates the integration pattern needed platform-wide
- No REST API, no database writes, no admin interface
- Multi-step wizard: Address → Plan → Info → Payment → Confirmation (5 steps)
- Responsive CSS (~330 lines inline) with Plus Jakarta Sans font

---

## 2. Current Features Found

### Quote / Proposal System (quote-builder-core v8.2)
- Multi-step quote wizard
- Plan selection
- Customer info capture
- Upsell flow (insurance / protection add-on)
- Summary and confirmation
- Print/PDF output (separate plugin v1.6)
- Chat-to-quote handoff integration (visible in FiberCo implementation)

### Service Ticket System (smti-service-tickets v1.1.7.8)
- Ticket pipeline
- Confirmation email overwrite (per version tag)
- Service request form (visible in screenshots)
- Status tracking (implied by pipeline)

### AI Chatbot (lawnace v3.17.0 / FiberCo v1.3.3)
- Website-embedded chat widget
- Rate limiting (FiberCo version: 300 limit per tag)
- Assistant routing (implied by multi-client implementations)
- Lead capture (implied by chat-to-quote handoff)
- Likely: conversation history, prompt management

### Dashboard (referenced in screenshots and docs)
- Admin dashboard with PIN-based login
- Navigation shell
- Module views

### Known but not inspectable (zip files)
- HubSpot integration details unknown
- Knowledge base and Training center — referenced in docs, not present as code

---

## 3. Reusable Components

### High reuse potential
| Component | Current Location | Target Core |
|---|---|---|
| Multi-step wizard UI pattern | FiberCo quote render | Sales Core (Quotes), Service Core (Requests), Chat Core (Lead capture) |
| Chat-to-form handoff (`fibercoQuoteStartFromChat`) | FiberCo quote render | AI Layer ↔ Sales Core bridge |
| Service request form | smti-service-tickets | Service Core |
| Ticket pipeline | smti-service-tickets | Service Core |
| Chatbot widget shell | lawnace / FiberCo chatbots | Chat Core |
| Rate limiting logic | FiberCo chatbot | AI Layer shared services |
| PIN login flow | Dashboard | Core System auth |
| Admin dashboard nav shell | Dashboard | Core System (Unified Admin Dashboard) |
| Confirmation email system | smti-service-tickets | Core System notifications |

### CSS / Design System (extractable from FiberCo render)
- Button variants: primary, outline, success, ghost
- Form field pattern with inline validation
- Card / panel layout
- Progress stepper
- Trust badge row
- Color palette: #0057FF primary, #10b981 success, #ef4444 error
- Typography: Plus Jakarta Sans

---

## 4. Duplicate / Conflicting Functionality

| Conflict | Detail | Resolution |
|---|---|---|
| Two chatbot plugins (lawnace + FiberCo) | Both are white-label wrappers around presumably the same core chatbot engine | Consolidate into single Chat Core plugin with per-tenant branding config |
| Quote render (txt) vs quote-builder-core (zip) | FiberCo render is a client-specific shortcode; quote-builder-core is the generalized system | quote-builder-core is the migration target; FiberCo render is a reference implementation, not a source of truth |
| Dashboard (existing) vs Core System dashboard (planned) | Existing dashboard has its own navigation and login shell | Migrate/replace with Core System unified admin dashboard |
| Notification emails in tickets vs future Core System notifications | smti ticket confirmation emails are module-level | Move to Core System notification layer with templates |
| Rate limiting in chatbot plugin vs AI Layer shared services | Rate limiting embedded in client plugin | Extract to AI Layer rate limiter service |

---

## 5. Recommended Final Platform Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     WordPress Shell                          │
│            (admin panel, routing, auth, REST)                │
└───────────────────┬─────────────────────────────────────────┘
                    │
┌───────────────────▼─────────────────────────────────────────┐
│                   CORE SYSTEM PLUGIN                         │
│  Users · Roles · Contacts · Companies · Files                │
│  Notifications · Settings · Activity Log · Integrations      │
│  Module Registry · Global Navigation · REST API Framework    │
└──┬────┬────┬────┬────┬────┬────────────────────────────────┘
   │    │    │    │    │    │
   ▼    ▼    ▼    ▼    ▼    ▼
[Chat] [Know] [Sales] [Svc] [Ops] [Intel]   ← Core Modules
   │    │    │    │    │    │
   └────┴────┴────┴────┴────┘
                    │
         ┌──────────▼──────────┐
         │      AI LAYER        │
         │  AI Sales · AI Svc   │
         │  AI Ops · AI Intel   │
         │  Shared AI Services  │
         └─────────────────────┘
```

### Plugin Structure (WordPress)
```
start-performance/           ← Core System plugin (required)
start-performance-chat/      ← Chat Core module
start-performance-knowledge/ ← Knowledge Core module
start-performance-sales/     ← Sales Core module
start-performance-service/   ← Service Core module
start-performance-ops/       ← Operations Core module
start-performance-intel/     ← Intelligence Core module
start-performance-ai/        ← AI Layer (shared services + per-Core AI)
```

Each module plugin:
- Registers itself with Core System's Module Registry on activation
- Declares its dependencies (e.g., Sales Core requires Core System)
- Adds its navigation items to the global nav
- Creates its own database tables on activation
- Exposes its own REST API namespace (`/wp-json/sp/v1/sales/...`)
- Respects Core System's roles and permissions

---

## 6. Recommended Database Architecture

### Core System Tables
```sql
sp_contacts          — id, first_name, last_name, email, phone, company_id, owner_id, source, status, created_at, updated_at
sp_companies         — id, name, industry, website, phone, address, created_at, updated_at
sp_contact_meta      — id, contact_id, meta_key, meta_value
sp_relationships     — id, object_type_a, object_id_a, object_type_b, object_id_b, relationship_type
sp_activity_log      — id, user_id, object_type, object_id, action, details (JSON), ip_address, created_at
sp_notifications     — id, user_id, type, subject, body, read_at, created_at
sp_files             — id, owner_type, owner_id, filename, file_path, file_type, file_size, created_at
sp_integrations      — id, name, type, credentials (encrypted JSON), status, created_at
sp_settings          — id, module, setting_key, setting_value, is_global
sp_modules           — id, slug, name, version, status, activated_at
```

### Chat Core Tables
```sql
sp_conversations     — id, contact_id, channel (website|internal), status, assigned_to, started_at, ended_at
sp_messages          — id, conversation_id, sender_type (user|ai|agent), sender_id, body, metadata (JSON), created_at
sp_chat_leads        — id, conversation_id, contact_id, capture_data (JSON), created_at
sp_chat_routing      — id, rule_name, conditions (JSON), destination_type, destination_id, priority
```

### Sales Core Tables
```sql
sp_leads             — id, contact_id, company_id, source, status, score, owner_id, created_at
sp_opportunities     — id, lead_id, name, stage, value, close_date, probability, owner_id
sp_quotes            — id, contact_id, company_id, opportunity_id, status, line_items (JSON), total, valid_until, created_by
sp_quote_items       — id, quote_id, name, description, qty, unit_price, total
sp_proposals         — id, quote_id, title, body, status, sent_at, viewed_at, accepted_at
```

### Service Core Tables
```sql
sp_tickets           — id, contact_id, company_id, subject, status, priority, type, assigned_to, created_at, resolved_at
sp_ticket_messages   — id, ticket_id, sender_type, sender_id, body, is_internal, created_at
sp_service_requests  — id, contact_id, company_id, form_data (JSON), status, linked_ticket_id, created_at
sp_service_history   — id, contact_id, ticket_id, summary, created_at
```

### Operations Core Tables
```sql
sp_tasks             — id, title, description, assignee_id, related_type, related_id, status, due_date, created_at
sp_workflows         — id, name, trigger_type, trigger_conditions (JSON), steps (JSON), status
sp_workflow_runs     — id, workflow_id, trigger_data (JSON), status, started_at, completed_at
sp_onboarding        — id, contact_id, company_id, checklist_id, progress (JSON), started_at, completed_at
sp_schedules         — id, title, assignee_id, related_type, related_id, start_time, end_time, status
```

### Intelligence Core Tables
```sql
sp_kpis              — id, name, module, formula, target_value, display_format
sp_kpi_snapshots     — id, kpi_id, value, period_start, period_end, captured_at
sp_reports           — id, name, type, config (JSON), created_by, created_at
sp_dashboards        — id, name, user_id, layout (JSON), is_default
sp_alerts            — id, name, condition (JSON), threshold, last_triggered, status
```

### AI Layer Tables
```sql
sp_ai_prompts        — id, module, prompt_key, system_prompt, user_template, model, version, active
sp_ai_logs           — id, module, feature, user_id, prompt_tokens, completion_tokens, cost, response (JSON), created_at
sp_ai_settings       — id, module, setting_key, setting_value
sp_ai_approvals      — id, module, feature, generated_content, status (pending|approved|rejected), reviewed_by, created_at
```

### Design Principles
- All tables prefixed `sp_` to avoid WordPress conflicts
- Use `JSON` columns for flexible metadata, form data, config — avoid premature normalization
- All tables include `created_at`, most include `updated_at`
- Soft deletes via `deleted_at` on primary objects (contacts, tickets, leads)
- Encrypt sensitive columns at the application layer (integration credentials, payment tokens)
- Never store PII in activity logs — store object references only

---

## 7. Recommended Plugin / Module Architecture

### Module Registration Pattern
Each Core module registers with the Core System on activation:
```
Module declares: slug, name, version, dependencies[], nav_items[], db_tables[], rest_namespace
Core System validates dependencies are active before allowing activation
Core System merges nav_items into global navigation
```

### Shared Services (Core System provides to all modules)
- `SP_Auth` — current user, role checks, capability gates
- `SP_Contacts` — CRUD for contacts and companies
- `SP_Notifications` — send notification to user(s)
- `SP_Files` — upload/retrieve files
- `SP_ActivityLog` — write activity events
- `SP_REST` — base REST controller with auth middleware
- `SP_Settings` — read/write module settings

### AI Layer Architecture
The AI Layer is a module that other modules call — it does not call them.

```
AI Layer exposes:
  SP_AI::analyze($module, $feature, $context_array)   → returns suggestion object
  SP_AI::draft($module, $feature, $context_array)     → returns draft text
  SP_AI::summarize($module, $feature, $context_array) → returns summary text

Each call:
  1. Loads prompt template from sp_ai_prompts
  2. Builds context using provided data
  3. Calls Claude API (claude-sonnet-4-6 or claude-haiku-4-5 depending on complexity)
  4. Logs usage to sp_ai_logs
  5. Returns structured response
  6. If human approval is required, writes to sp_ai_approvals before returning
```

### Permissions Structure
Core System defines capability constants:
```
sp_view_contacts, sp_edit_contacts
sp_view_leads, sp_edit_leads, sp_manage_pipeline
sp_view_tickets, sp_edit_tickets, sp_close_tickets
sp_use_ai, sp_approve_ai, sp_manage_ai_prompts
sp_view_reports, sp_manage_settings
```

Roles map to capability sets. Custom roles are stored in Core System settings.

---

## 8. Recommended Migration Strategy

### Phase 0 — Inventory and Freeze (Now)
- Unzip all plugin files and document every function, table, and API call
- Screenshot all existing UIs for design reference
- Identify which plugins have active clients on them (do not break production)
- Establish a staging environment for all migration work

### Phase 1 — Build Foundation First (FIRST_BUILD_TARGET.md)
- Build `start-performance` core plugin fresh
- Implement: Module Registry, global nav, settings, DB installer, Contacts, Companies, Leads, Activity Log
- Do NOT migrate old functionality yet — prove the foundation works standalone
- This is the only deliverable before migrating anything

### Phase 2 — Migrate Chat (chatbots → Chat Core)
- Extract shared chatbot engine from lawnace/FiberCo plugins
- Build Chat Core as a module registered with Core System
- Migrate conversation history into `sp_conversations` / `sp_messages`
- Implement branding config (replaces client-specific hardcoding)
- Retire both client chatbot plugins in favor of Chat Core + branding skin

### Phase 3 — Migrate Knowledge
- Identify where knowledge base and training center live (likely WP posts/custom post types)
- Build Knowledge Core module
- Migrate existing content into `sp_knowledge_articles`, `sp_training_items`
- Connect to AI Layer for context retrieval

### Phase 4 — Migrate Sales (Quote Builder → Sales Core)
- Migrate quote-builder-core v8.2 into Sales Core module
- Map existing quote data into `sp_quotes` / `sp_quote_items`
- Port print plugin into Sales Core's PDF output feature
- Retire quote-builder-core and quote-builder-print as standalone plugins

### Phase 5 — Migrate Service (SMTI → Service Core)
- Migrate smti-service-tickets v1.1.7.8 into Service Core module
- Map existing ticket data into `sp_tickets` / `sp_ticket_messages`
- Move email confirmation logic into Core System Notifications
- Retire smti-service-tickets as standalone plugin

### Phase 6 — Migrate Dashboard → Intelligence Core
- Existing dashboard becomes Intelligence Core
- KPI widgets and reporting become managed data (not hardcoded views)
- PIN login migrates into Core System auth

### Phase 7 — Build AI Layer
- Build `start-performance-ai` plugin
- Implement shared AI services: prompt management, logging, approval queue
- Wire AI Sales to Sales Core events
- Wire AI Service to Service Core events
- Add AI to Chat Core (existing chatbot AI logic centralizes here)

### Parallel — HubSpot Integration
- Move HubSpot sync into Core System Integration Framework
- Expose sync as a service other Cores can call (e.g., Sales Core syncs leads to HubSpot)

---

## 9. Risks and Technical Debt

### Risk 1 — Client Plugins in Production (HIGH)
The chatbot plugins (lawnace, FiberCo) and possibly the ticket and quote systems are running on client sites. Migration must not disrupt them. Mitigation: build new platform in parallel, migrate clients one at a time with a switchover window.

### Risk 2 — No Shared Data Model Yet (HIGH)
All existing plugins almost certainly use their own tables or WordPress post types with no shared contact/company record. Cross-module relationships (a contact who has quotes AND tickets) will require a data migration plan and possibly a merge/dedup pass on contacts. Mitigation: design sp_contacts as the master record from day one and build import scripts from each old system.

### Risk 3 — Single-File Architecture in Existing Plugins (MEDIUM)
The FiberCo quote render is 1000+ lines of a single PHP function. The actual quote-builder-core zip is likely similar. These are not architected for extension. Migration will require a full rewrite into the module pattern — reuse logic concepts, not the code structure.

### Risk 4 — WordPress as the Platform Shell (MEDIUM)
WordPress is well-suited as a starting shell, but its data model (posts/meta) and auth system will conflict with the platform's custom tables as complexity grows. The platform is designed with this exit in mind (custom tables + REST APIs), but every shortcut taken toward WP conventions (custom post types, WP_User meta) makes the eventual SaaS migration harder. Mitigation: strict policy — all platform data lives in `sp_*` tables, never in WordPress core tables except for auth bootstrap.

### Risk 5 — AI Cost Without Visibility (MEDIUM)
Multiple chatbot plugins already call AI APIs. Without the `sp_ai_logs` table and cost tracking from the start, usage costs will be invisible until they are painful. Mitigation: AI Layer must be built before or alongside the first AI-consuming feature, not after.

### Risk 6 — Branding Hardcoded in Client Plugins (LOW-MEDIUM)
FiberCo quote render has hardcoded colors, phone numbers, logo, URLs, and plan data. The chatbot plugins are similarly client-specific. The reusable design system and config layer must abstract all of this before any new client goes live. Mitigation: branding config (colors, logo, contact info) stored in Core System settings per-tenant.

### Risk 7 — No REST API Layer Exists (LOW)
All existing functionality appears to be WordPress shortcode/admin-page driven with no REST API. Building REST from day one (as planned) is the right call. Existing shortcodes can be shimmed as REST calls during migration.

### Risk 8 — Duplicate Contact Records (LOW)
Each plugin likely has its own customer table. A contact may appear in the chatbot conversation log, the quote system, and the ticket system as three separate records. A contact resolution / deduplication strategy is needed during migration.

---

## 10. Summary Recommendation

Build in this exact order:

1. **Core System plugin** — foundation only (Contacts, Companies, Leads, Settings, Nav, Module Registry, DB Installer). No migrated features yet. Prove the shell works.
2. **Chat Core** — migrate both chatbots into one module with branding config.
3. **Knowledge Core** — migrate knowledge base and training center.
4. **Sales Core** — migrate quote builder system.
5. **Service Core** — migrate ticket system.
6. **AI Layer** — after at least two Cores are live, build the shared AI service.
7. **Intelligence Core + Operations Core** — data is now rich enough to build dashboards on.

The most important architectural decision is **the shared contact record**. Everything connects to contacts. Build that table and its API correctly in Phase 1, and the rest of the platform assembles cleanly. Cut corners there, and every subsequent module will require retroactive reconciliation.

The second most important decision is **never writing platform data to WordPress core tables**. Custom `sp_*` tables from day one keeps the SaaS exit path viable.
