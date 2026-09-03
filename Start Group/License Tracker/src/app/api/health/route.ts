import { NextResponse } from "next/server";
import { db } from "@/lib/db";

/**
 * GET /api/health → lightweight liveness/readiness probe for Docker, k8s, or a
 * load balancer. Verifies the DB is reachable. Returns NO sensitive details.
 */
export const dynamic = "force-dynamic";

export async function GET() {
  try {
    await db.$queryRaw`SELECT 1`;
    return NextResponse.json({ status: "ok" });
  } catch {
    return NextResponse.json({ status: "degraded" }, { status: 503 });
  }
}
