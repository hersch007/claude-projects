# =============================================================================
#  Multi-stage build for a minimal, secure production image.
#  Final image runs as a non-root user and contains only the standalone server.
# =============================================================================

# ---- deps: install dependencies (cached layer) ------------------------------
FROM node:22-alpine AS deps
# libc6-compat helps some native modules (e.g. argon2 prebuilts) on Alpine.
RUN apk add --no-cache libc6-compat openssl
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm ci

# ---- builder: generate Prisma client + build Next ---------------------------
FROM node:22-alpine AS builder
RUN apk add --no-cache libc6-compat openssl
WORKDIR /app
COPY --from=deps /app/node_modules ./node_modules
COPY . .
# Prisma client must be generated before the Next build.
RUN npx prisma generate
# Telemetry off; build the standalone server bundle.
ENV NEXT_TELEMETRY_DISABLED=1
RUN npm run build

# ---- runner: tiny runtime image --------------------------------------------
FROM node:22-alpine AS runner
RUN apk add --no-cache openssl
WORKDIR /app
ENV NODE_ENV=production
ENV NEXT_TELEMETRY_DISABLED=1

# Run as a dedicated non-root user (defense in depth).
RUN addgroup --system --gid 1001 nodejs \
  && adduser --system --uid 1001 nextjs

# Copy the standalone server, static assets, public dir, and Prisma runtime.
COPY --from=builder /app/public ./public
COPY --from=builder --chown=nextjs:nodejs /app/.next/standalone ./
COPY --from=builder --chown=nextjs:nodejs /app/.next/static ./.next/static
# Prisma engine + schema needed at runtime for migrate deploy on boot.
COPY --from=builder /app/node_modules/.prisma ./node_modules/.prisma
COPY --from=builder /app/node_modules/@prisma ./node_modules/@prisma
COPY --from=builder /app/prisma ./prisma
COPY --from=builder /app/node_modules/prisma ./node_modules/prisma
COPY --from=builder /app/docker-entrypoint.sh ./docker-entrypoint.sh
RUN chmod +x ./docker-entrypoint.sh

USER nextjs
EXPOSE 3000
ENV PORT=3000 HOSTNAME=0.0.0.0

# Entrypoint runs migrations then starts the server.
ENTRYPOINT ["./docker-entrypoint.sh"]
