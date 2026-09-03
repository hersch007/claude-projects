import { NextResponse, type NextRequest } from "next/server";
import { getTenantContext, tenantDb, AuthError } from "@/lib/tenant";
import { RateLimits } from "@/lib/rate-limit";
import { licenseQuerySchema, licenseInputSchema } from "@/lib/validations";
import { createLicense, toDTO } from "@/lib/license-service";
import { clientIp } from "@/lib/rate-limit";
import type { Prisma } from "@prisma/client";

/**
 * REST surface for licenses (prepared for future programmatic / API-key access).
 * Today it is session-authenticated; a Team-plan API-key guard can be layered in
 * front of getTenantContext without changing the handlers.
 *
 * GET  /api/licenses   → paginated, tenant-scoped list of MASKED licenses.
 * POST /api/licenses   → create (requires unlocked vault).
 */

export async function GET(req: NextRequest) {
  try {
    const ctx = await getTenantContext();
    if (!RateLimits.api(ctx.accountId).success) {
      return NextResponse.json({ error: "Rate limit exceeded" }, { status: 429 });
    }

    const params = Object.fromEntries(req.nextUrl.searchParams);
    const q = licenseQuerySchema.parse(params);

    // Build a tenant-safe where clause. accountId is injected by tenantDb.
    const where: Prisma.LicenseWhereInput = {};
    if (q.status) where.status = q.status;
    if (q.vendor) where.vendor = q.vendor;
    if (q.tag) where.tags = { has: q.tag };
    if (q.q) {
      // Search across non-secret fields only.
      where.OR = [
        { productName: { contains: q.q, mode: "insensitive" } },
        { vendor: { contains: q.q, mode: "insensitive" } },
        { invoiceReference: { contains: q.q, mode: "insensitive" } },
      ];
    }

    const [items, total] = await Promise.all([
      tenantDb(ctx).license.findManyActive({
        where,
        orderBy: { [q.sort]: q.dir },
        skip: (q.page - 1) * q.pageSize,
        take: q.pageSize,
      }),
      tenantDb(ctx).license.count(where),
    ]);

    return NextResponse.json({
      data: items.map(toDTO),
      page: q.page,
      pageSize: q.pageSize,
      total,
    });
  } catch (err) {
    return errToResponse(err);
  }
}

export async function POST(req: NextRequest) {
  try {
    const ctx = await getTenantContext();
    if (!RateLimits.api(ctx.accountId).success) {
      return NextResponse.json({ error: "Rate limit exceeded" }, { status: 429 });
    }
    const input = licenseInputSchema.parse(await req.json());
    const dto = await createLicense(ctx, input, {
      ip: clientIp(req.headers),
      ua: req.headers.get("user-agent"),
    });
    return NextResponse.json({ data: dto }, { status: 201 });
  } catch (err) {
    return errToResponse(err);
  }
}

function errToResponse(err: unknown) {
  if (err instanceof AuthError) {
    const status =
      err.code === "LOCKED" ? 423 : err.code === "UNAUTHENTICATED" ? 401 : 403;
    return NextResponse.json({ error: err.message }, { status });
  }
  return NextResponse.json(
    { error: err instanceof Error ? err.message : "Bad request" },
    { status: 400 }
  );
}
