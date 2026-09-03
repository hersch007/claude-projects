import { NextResponse, type NextRequest } from "next/server";

/**
 * Edge middleware: applies hardened security headers to EVERY response and does
 * a lightweight auth gate (presence of the session cookie) for app routes.
 *
 * IMPORTANT: This is a coarse gate only — it redirects anonymous users away from
 * protected pages for UX. Real authorization (session validity, role, tenant,
 * vault-unlock) is enforced server-side in every server action / route handler
 * via src/lib/tenant.ts. Never rely on middleware alone for access control.
 */

// Routes that require an authenticated session.
const PROTECTED_PREFIXES = ["/dashboard", "/licenses", "/settings", "/unlock"];
// Routes only for anonymous users.
const AUTH_PAGES = ["/login", "/signup"];

export function middleware(req: NextRequest) {
  const { pathname } = req.nextUrl;

  // better-auth session cookie (prefix "lt"). Presence != validity, but absence
  // definitely means "not logged in".
  const hasSession =
    req.cookies.has("lt.session_token") ||
    req.cookies.has("__Secure-lt.session_token");

  const isProtected = PROTECTED_PREFIXES.some((p) => pathname.startsWith(p));
  const isAuthPage = AUTH_PAGES.some((p) => pathname.startsWith(p));

  if (isProtected && !hasSession) {
    const url = req.nextUrl.clone();
    url.pathname = "/login";
    url.searchParams.set("next", pathname);
    return applySecurityHeaders(NextResponse.redirect(url), req);
  }

  if (isAuthPage && hasSession) {
    const url = req.nextUrl.clone();
    url.pathname = "/dashboard";
    return applySecurityHeaders(NextResponse.redirect(url), req);
  }

  return applySecurityHeaders(NextResponse.next(), req);
}

function applySecurityHeaders(res: NextResponse, _req: NextRequest): NextResponse {
  const isProd = process.env.NODE_ENV === "production";

  // Per-request CSP nonce. Next injects this nonce into its own scripts when it
  // sees the `Content-Security-Policy` header with a nonce + strict-dynamic.
  const nonce = crypto.randomUUID().replace(/-/g, "");

  const csp = [
    `default-src 'self'`,
    // strict-dynamic lets Next's nonce'd loader pull in chunks; no unsafe-inline.
    `script-src 'self' 'nonce-${nonce}' 'strict-dynamic' https://js.stripe.com`,
    // Tailwind/shadcn inject runtime styles; allow inline styles only.
    `style-src 'self' 'unsafe-inline'`,
    `img-src 'self' data: blob: https:`,
    `font-src 'self' data:`,
    `connect-src 'self' https://api.stripe.com`,
    `frame-src https://js.stripe.com https://hooks.stripe.com`,
    `object-src 'none'`,
    `base-uri 'self'`,
    `form-action 'self'`,
    `frame-ancestors 'none'`,
    isProd ? `upgrade-insecure-requests` : ``,
  ]
    .filter(Boolean)
    .join("; ");

  res.headers.set("Content-Security-Policy", csp);
  res.headers.set("x-nonce", nonce);
  res.headers.set("X-Content-Type-Options", "nosniff");
  res.headers.set("X-Frame-Options", "DENY");
  res.headers.set("Referrer-Policy", "strict-origin-when-cross-origin");
  res.headers.set(
    "Permissions-Policy",
    "camera=(), microphone=(), geolocation=(), payment=(self)"
  );
  res.headers.set("Cross-Origin-Opener-Policy", "same-origin");
  res.headers.set("X-DNS-Prefetch-Control", "off");

  // HSTS only over HTTPS in production (avoid locking out local http).
  if (isProd) {
    res.headers.set(
      "Strict-Transport-Security",
      "max-age=63072000; includeSubDomains; preload"
    );
  }

  return res;
}

export const config = {
  // Run on everything except static assets and the Next internals.
  matcher: ["/((?!_next/static|_next/image|favicon.ico|manifest.webmanifest|icons/).*)"],
};
