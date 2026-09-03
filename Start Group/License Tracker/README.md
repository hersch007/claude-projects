# License Tracker

A **production-ready, security-first, multi-tenant** License & Subscription Management SaaS.

Track software licenses, subscriptions, renewals and spend across your whole
organization — with license keys **encrypted at rest** and protected by a
per-account **master passphrase** that never leaves server memory.

> Built with Next.js 15 (App Router), TypeScript, Prisma + PostgreSQL,
> better-auth, Stripe, Tailwind + shadcn/ui. Self-hostable via Docker.

---

## Table of contents

1. [Security model](#security-model)
2. [Architecture](#architecture)
3. [Local setup](#local-setup)
4. [Database & migrations](#database--migrations)
5. [Stripe setup](#stripe-setup)
6. [Self-hosting with Docker](#self-hosting-with-docker)
7. [Production recommendations](#production-recommendations)
8. [Security best practices](#security-best-practices)
9. [Project structure](#project-structure)
10. [Known limitations / next steps](#known-limitations--next-steps)

---

## Security model

This is the most important part of the project. Read it before deploying.

### Envelope encryption (master passphrase → data key)

Each **Account** (tenant) has a **master passphrase**, set by an admin during
onboarding. We do **not** derive the data encryption key directly from the
passphrase. Instead:

```
passphrase ──Argon2id(salt, server-pepper)──▶ master key (MK)
                                                │
                            ┌───────────────────┴───────────────────┐
                       HKDF "verifier"                          HKDF "kek"
                            │                                        │
                  stored as masterPassphraseHash            KEK (key-encryption key)
                  (confirms the passphrase)                          │
                                                            unwraps  ▼
                                                   DEK (random, per-account data key)
                                                                     │
                                                  AES-256-GCM ▼  encrypts every secret
```

**Why envelope encryption?**

- The **DEK is random and independent of the passphrase.** Changing the
  passphrase only **re-wraps** the DEK (O(1)) — it never re-encrypts your data.
- The DEK is **never persisted in plaintext** and **never leaves the server.**
  After you unlock, it lives only in an **in-memory store** (`src/lib/crypto/key-store.ts`)
  keyed by your session, with a 30-minute sliding TTL. Lock / logout / restart
  wipes it and forces re-entry.
- A server-side **`ENCRYPTION_PEPPER`** is folded into Argon2id. A stolen
  database alone is therefore **not enough** to brute-force passphrases offline —
  the attacker also needs the app environment secret.

> **There is no passphrase recovery.** This is intentional (zero-knowledge-style).
> If every member forgets the passphrase, encrypted data cannot be recovered.
> Communicate this clearly to admins.

### What is encrypted

| Field | At rest |
|---|---|
| `License.licenseKey` | **AES-256-GCM** (`licenseKeyCipher`) |
| Sensitive `License.notes` (when flagged) | **AES-256-GCM** (`notesCipher`) |
| `Attachment` file bytes | **AES-256-GCM** (`dataCipher`) |
| Account login password | Hashed by better-auth (scrypt) |
| Master passphrase | Not stored — only an HKDF **verifier** is stored |

GCM provides both confidentiality **and** integrity (tampering is detected on
decrypt). A fresh random 96-bit IV is used for every encryption.

### Multi-tenant isolation

- Every tenant-owned row carries an `accountId`. **Every** query is scoped to the
  authenticated user's `accountId`, which is **always derived from the server
  session — never from client input.** This defeats IDOR / cross-tenant access.
- `src/lib/tenant.ts` is the single chokepoint (`getTenantContext`, `tenantDb`,
  `requireRole`, `requireDek`). Prefer these helpers over the raw Prisma client.
- All composite DB indexes are `accountId`-first.

### Other controls

- **Auth:** better-auth, server-side sessions, httpOnly + Secure + SameSite
  cookies, email verification required, 12+ char passwords.
- **Reveal flow:** plaintext keys are never in the page payload; they are fetched
  on demand from a **rate-limited, audited, `no-store`** endpoint, shown
  temporarily, auto-hidden after 20s.
- **Rate limiting:** per-tenant + per-IP token buckets; strictest on passphrase
  unlock and key reveal (`src/lib/rate-limit.ts`).
- **Audit log:** append-only "who did what, when", with sensitive keys scrubbed
  (`src/lib/audit.ts`).
- **Validation:** Zod on every untrusted input (`src/lib/validations.ts`).
- **Headers:** strict CSP with per-request nonce, HSTS, `X-Frame-Options: DENY`,
  `nosniff`, `Referrer-Policy`, `Permissions-Policy` (`src/middleware.ts`).
- **No secrets in logs:** the audit scrubber and console usage avoid keys/DEKs.
- **GDPR/CCPA:** JSON data export + encrypted full backup; soft-delete with an
  auditable trail (extend with a hard-purge job).

---

## Architecture

- **Next.js 15 App Router** with Server Components + Server Actions for mutations.
- **Server-only crypto** (`server-only` import guard) so key material can never
  be bundled to the client.
- **Prisma + PostgreSQL** shared-schema multi-tenancy.
- **Stripe** subscriptions with webhooks as the source of truth for plan/seats.

---

## Local setup

**Prerequisites:** Node 20+, a PostgreSQL 14+ instance (or use Docker for just the DB).

```bash
# 1. Install dependencies
npm install

# 2. Configure environment
cp .env.example .env
#   Generate strong secrets:
#     openssl rand -base64 48   # for BETTER_AUTH_SECRET
#     openssl rand -base64 48   # for ENCRYPTION_PEPPER
#   Set DATABASE_URL to your Postgres instance.

# 3. (Optional) start just Postgres via Docker
docker compose up -d db

# 4. Create the DB schema
npm run prisma:migrate     # dev: creates + applies a migration

# 5. (Optional) seed demo data
npm run db:seed

# 6. Run the dev server
npm run dev
# → http://localhost:3000
```

Then: **Sign up → verify email → set master passphrase → unlock → add licenses.**

> In development, verification/invite emails are **printed to the console**
> (no email provider needed) unless you set `RESEND_API_KEY`.

---

## Database & migrations

```bash
npm run prisma:migrate          # dev — create & apply a new migration
npm run prisma:deploy           # prod — apply existing migrations (no prompts)
npm run prisma:studio           # browse data
npx prisma migrate reset        # DANGER: drops & recreates (dev only)
```

In Docker, migrations are applied automatically on container start via
`docker-entrypoint.sh` (`prisma migrate deploy`).

> First-time setup tip: run `npx prisma migrate dev --name init` once to generate
> the initial migration in `prisma/migrations/` before building the Docker image.

---

## Stripe setup

1. Create three recurring **Prices** in the Stripe dashboard:
   - Pro (`$12/mo`)
   - Team base (`$39/mo`)
   - Team per-seat (`$6/mo`, "per unit")
2. Put the price IDs in `.env` (`STRIPE_PRICE_PRO`, `STRIPE_PRICE_TEAM_BASE`,
   `STRIPE_PRICE_TEAM_PER_SEAT`).
3. Set `STRIPE_SECRET_KEY` and `NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY`.
4. **Webhooks:** point a Stripe webhook at `POST /api/stripe/webhook` for events:
   `checkout.session.completed`, `customer.subscription.*`,
   `invoice.payment_failed`. Put the signing secret in `STRIPE_WEBHOOK_SECRET`.

   Local testing:
   ```bash
   npm run stripe:listen      # stripe listen --forward-to localhost:3000/api/stripe/webhook
   ```

The webhook is the **source of truth**: it maps Stripe state → `plan`,
`seatLimit`, `subscriptionStatus`. Handlers are idempotent and signature-verified
against the **raw** request body.

Billing actions are **admin-only**; checkout/portal sessions are created with the
tenant id derived from the session (never the request body).

---

## Self-hosting with Docker

```bash
cp .env.example .env       # fill in secrets (use strong random values!)
docker compose up -d --build
# → http://localhost:3000
```

- `db` (Postgres) is **not** published to the host by default (least exposure).
- The app image runs as a **non-root** user and ships only the standalone server.
- Migrations run automatically on boot.

---

## Production recommendations

- **TLS / reverse proxy:** terminate HTTPS at a reverse proxy (Caddy, nginx,
  Traefik). Forward `X-Forwarded-For` so rate limiting sees real client IPs.
  HSTS is sent automatically in production.
- **Secrets:** store `ENCRYPTION_PEPPER`, `BETTER_AUTH_SECRET`, DB and Stripe
  secrets in a secrets manager (AWS Secrets Manager, Vault, Doppler) — not in a
  committed file. Consider a KMS/HSM for the pepper.
- **Backups:** schedule encrypted `pg_dump` backups of the Postgres volume.
  Test restores. The ciphertext is useless without the per-account DEKs, which
  are only recoverable via the master passphrases.
- **Scaling the in-memory key store:** the unlocked DEK lives in process memory.
  For multiple app instances use **sticky sessions**, or replace
  `src/lib/crypto/key-store.ts` with a KMS-backed per-node cache. **Never** put
  raw DEKs in Redis. For shared rate limiting across instances, back
  `src/lib/rate-limit.ts` with Redis (`REDIS_URL`).
- **Object storage for attachments:** for large files, store ciphertext in S3 and
  keep only a pointer in `Attachment` (the schema is ready for this).
- **Monitoring:** ship audit logs and app logs to your SIEM. Alert on repeated
  `vault.unlock_failed` and `license.reveal_key` spikes.
- **Rotate** `BETTER_AUTH_SECRET` (invalidates sessions) and the pepper (requires
  re-wrapping DEKs — script this) on a schedule or after an incident.

---

## Security best practices (operational)

- Enforce email verification (on by default) before granting access.
- Keep `minPasswordLength` ≥ 12 and encourage long master **passphrases**.
- Limit who has the **ADMIN** role; only admins set/rotate the passphrase, manage
  billing, and remove members.
- Review the **audit log** regularly.
- Keep dependencies patched (`npm audit`, Dependabot/Renovate).

---

## Project structure

```
prisma/
  schema.prisma            # data model, tenancy, encryption columns, audit
  seed.ts                  # dev seed (encrypted sample licenses)
src/
  env.ts                   # fail-fast env validation (Zod)
  middleware.ts            # CSP nonce, HSTS, security headers, coarse auth gate
  lib/
    crypto/
      encryption.ts        # ⭐ Argon2id + AES-256-GCM envelope encryption
      key-store.ts         # ⭐ in-memory, per-session DEK store (TTL, wipe)
    auth.ts                # better-auth config (sessions, cookies)
    auth-client.ts         # browser auth client
    tenant.ts              # ⭐ tenant context + isolation helpers + DEK gate
    db.ts                  # Prisma singleton
    audit.ts               # append-only audit logging (scrubbed)
    rate-limit.ts          # token-bucket limiter (per-tenant / per-ip)
    license-service.ts     # encrypt/decrypt, status, masked DTOs, reveal
    validations.ts         # Zod schemas (no accountId from clients!)
    plans.ts               # plan limits / Stripe price mapping
    stripe.ts              # Stripe SDK singleton
    email.ts               # Resend-ready email placeholder
  app/
    page.tsx               # public landing + pricing
    (auth)/login|signup    # auth pages
    onboarding/            # admin sets master passphrase
    (app)/                 # authenticated shell with vault gate
      dashboard/           # spend, expiring 30/60/90, cost by vendor
      licenses/            # CRUD table, search/filter, reveal, .ics, export
      settings/            # members, billing, change passphrase, GDPR export
    actions/               # server actions (vault, auth, licenses, members)
    api/
      auth/[...all]        # better-auth handler
      stripe/{webhook,checkout,portal}
      licenses/{route, [id]/reveal, [id]/ics}
      export/              # CSV / JSON / encrypted backup
  components/              # UI (shadcn-style), vault forms, license table, etc.
```

⭐ = security-critical; read these first.

---

## Known limitations / next steps

These are intentionally scaffolded for you to extend:

- **Invite acceptance page** (`/invite/[token]`) and member sign-up wiring.
- **Attachment upload UI** (encryption + schema + service are ready).
- **Bulk CSV/Excel import** with column mapping (validation layer is ready).
- **Email templates** via Resend (placeholder logs to console in dev).
- **Background job** for scheduled renewal reminder emails and GDPR hard-purge.
- **Redis-backed** rate limiter + key store for horizontal scaling.
- **API keys** for the Team-plan REST API (routes exist; add a key guard).

> The auth library surface (better-auth) evolves quickly — if you pin a different
> version, double-check the `auth.ts` adapter field names and the
> `signUpEmail`/`getSession` API shapes.
```
```

---

**License:** provide your own. **Status:** secure foundation — review and pen-test
before handling real customer data in production.
