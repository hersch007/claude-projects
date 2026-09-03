import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  // `output: "standalone"` produces a minimal, self-contained server bundle that the
  // Docker image copies. This keeps the runtime image small and avoids shipping
  // dev dependencies / the full node_modules tree into production.
  output: "standalone",

  // We never want the framework to silently leak the powered-by header (fingerprinting).
  poweredByHeader: false,

  // Strict mode surfaces unsafe lifecycle patterns during development.
  reactStrictMode: true,

  // argon2 ships native bindings; mark it external so Next/webpack does not try to bundle it.
  serverExternalPackages: ["@node-rs/argon2"],

  // NOTE: The primary, security-critical headers (CSP, HSTS, etc.) are applied in
  // `src/middleware.ts` so they cover every response including dynamic routes and
  // can use a per-request CSP nonce. The few below are static belt-and-suspenders.
  async headers() {
    return [
      {
        source: "/:path*",
        headers: [
          { key: "X-Content-Type-Options", value: "nosniff" },
          { key: "X-Frame-Options", value: "DENY" },
          { key: "Referrer-Policy", value: "strict-origin-when-cross-origin" },
        ],
      },
    ];
  },
};

export default nextConfig;
