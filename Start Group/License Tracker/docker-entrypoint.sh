#!/bin/sh
# =============================================================================
#  Container entrypoint.
#  1) Apply DB migrations (idempotent — safe to run on every boot).
#  2) Start the Next.js standalone server.
#  `prisma migrate deploy` only applies already-generated migrations; it never
#  prompts or generates new ones, so it is safe for production.
# =============================================================================
set -e

echo "▶ Applying database migrations…"
npx prisma migrate deploy

echo "▶ Starting License Tracker…"
exec node server.js
