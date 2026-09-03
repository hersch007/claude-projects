import { NextResponse } from "next/server";
import { stripe, stripeEnabled } from "@/lib/stripe";
import { env } from "@/env";
import { db } from "@/lib/db";
import { requireRole, AuthError } from "@/lib/tenant";

/**
 * POST /api/stripe/portal → returns a Billing Portal URL so the tenant admin can
 * manage / cancel their subscription, update card, download invoices. Admin-only.
 */
export async function POST() {
  if (!stripeEnabled) {
    return NextResponse.json({ error: "Billing not configured" }, { status: 503 });
  }
  try {
    const ctx = await requireRole("ADMIN");
    const account = await db.account.findUniqueOrThrow({ where: { id: ctx.accountId } });
    if (!account.stripeCustomerId) {
      return NextResponse.json({ error: "No billing account yet" }, { status: 400 });
    }

    const portal = await stripe.billingPortal.sessions.create({
      customer: account.stripeCustomerId,
      return_url: `${env.NEXT_PUBLIC_APP_URL}/settings/billing`,
    });
    return NextResponse.json({ url: portal.url });
  } catch (err) {
    if (err instanceof AuthError) {
      return NextResponse.json({ error: err.message }, { status: 403 });
    }
    return NextResponse.json({ error: "Could not open billing portal" }, { status: 500 });
  }
}
