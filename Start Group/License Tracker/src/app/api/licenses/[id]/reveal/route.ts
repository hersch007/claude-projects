import { NextResponse, type NextRequest } from "next/server";
import { getTenantContext, AuthError } from "@/lib/tenant";
import { revealLicenseKey } from "@/lib/license-service";
import { RateLimits, clientIp } from "@/lib/rate-limit";

/**
 * POST /api/licenses/:id/reveal
 * Returns the decrypted license key for a one-time reveal in the UI.
 *
 * SECURITY:
 *  - Requires an unlocked vault (DEK in memory) — see requireDek inside service.
 *  - Strictly rate-limited per user to deter scraping every key.
 *  - Always audited.
 *  - Response is marked no-store so the secret is never cached by anything.
 */
export async function POST(
  req: NextRequest,
  { params }: { params: Promise<{ id: string }> }
) {
  try {
    const ctx = await getTenantContext();
    const { id } = await params;

    const rl = RateLimits.reveal(ctx.userId);
    if (!rl.success) {
      return NextResponse.json(
        { error: "Too many reveal requests. Slow down." },
        { status: 429, headers: { "Retry-After": String(Math.ceil(rl.resetMs / 1000)) } }
      );
    }

    const ip = clientIp(req.headers);
    const key = await revealLicenseKey(ctx, id, { ip, ua: req.headers.get("user-agent") });

    return NextResponse.json(
      { key },
      {
        // Never cache a secret anywhere.
        headers: {
          "Cache-Control": "no-store, no-cache, must-revalidate, private",
          Pragma: "no-cache",
        },
      }
    );
  } catch (err) {
    if (err instanceof AuthError) {
      const status =
        err.code === "LOCKED" ? 423 : err.code === "UNAUTHENTICATED" ? 401 : 403;
      return NextResponse.json({ error: err.message }, { status });
    }
    return NextResponse.json({ error: "Not found" }, { status: 404 });
  }
}
