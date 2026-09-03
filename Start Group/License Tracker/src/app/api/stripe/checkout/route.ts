import { NextResponse, type NextRequest } from "next/server";
import { z } from "zod";
import { stripe, stripeEnabled } from "@/lib/stripe";
import { env } from "@/env";
import { db } from "@/lib/db";
import { requireRole, AuthError } from "@/lib/tenant";
import { PLANS } from "@/lib/plans";

/**
 * POST /api/stripe/checkout  → returns a Checkout Session URL.
 * Admin-only. The accountId comes from the session (never the request body), and
 * is stamped as client_reference_id so the webhook can map back to the tenant.
 */
const bodySchema = z.object({ plan: z.enum(["PRO", "TEAM"]), seats: z.number().int().min(1).max(500).optional() });

export async function POST(req: NextRequest) {
  if (!stripeEnabled) {
    return NextResponse.json({ error: "Billing not configured" }, { status: 503 });
  }
  try {
    const ctx = await requireRole("ADMIN");
    const body = bodySchema.parse(await req.json());
    const planDef = PLANS[body.plan];
    if (!planDef.stripePriceId) {
      return NextResponse.json({ error: "Plan not available" }, { status: 400 });
    }

    const account = await db.account.findUniqueOrThrow({ where: { id: ctx.accountId } });

    // Reuse or lazily create the Stripe customer for this tenant.
    let customerId = account.stripeCustomerId;
    if (!customerId) {
      const customer = await stripe.customers.create({
        email: ctx.email,
        name: account.name,
        metadata: { accountId: account.id },
      });
      customerId = customer.id;
      await db.account.update({
        where: { id: account.id },
        data: { stripeCustomerId: customerId },
      });
    }

    const lineItems: { price: string; quantity?: number }[] = [
      { price: planDef.stripePriceId, quantity: 1 },
    ];
    // Team: add metered/extra seats line item.
    if (body.plan === "TEAM" && planDef.stripePerSeatPriceId && body.seats) {
      lineItems.push({ price: planDef.stripePerSeatPriceId, quantity: body.seats });
    }

    const session = await stripe.checkout.sessions.create({
      mode: "subscription",
      customer: customerId,
      client_reference_id: account.id,
      metadata: { accountId: account.id },
      line_items: lineItems,
      allow_promotion_codes: true,
      success_url: `${env.NEXT_PUBLIC_APP_URL}/settings/billing?status=success`,
      cancel_url: `${env.NEXT_PUBLIC_APP_URL}/settings/billing?status=cancel`,
    });

    return NextResponse.json({ url: session.url });
  } catch (err) {
    if (err instanceof AuthError) {
      return NextResponse.json({ error: err.message }, { status: err.code === "FORBIDDEN" ? 403 : 401 });
    }
    console.error("[stripe] checkout error", err);
    return NextResponse.json({ error: "Could not start checkout" }, { status: 500 });
  }
}
