import { NextResponse, type NextRequest } from "next/server";
import type Stripe from "stripe";
import { stripe, stripeEnabled } from "@/lib/stripe";
import { env } from "@/env";
import { db } from "@/lib/db";
import { planForPriceId, seatLimitForPlan } from "@/lib/plans";
import { audit } from "@/lib/audit";
import type { SubscriptionStatus } from "@prisma/client";

/**
 * Stripe webhook handler.
 *
 * SECURITY:
 *  - The signature is verified against STRIPE_WEBHOOK_SECRET using the RAW body
 *    (we read req.text(), never req.json(), so the bytes are unmodified).
 *  - Handlers are idempotent: they upsert state keyed by Stripe IDs, so Stripe's
 *    at-least-once delivery cannot corrupt our records.
 *  - We map Stripe state -> our Plan/seatLimit, the source of truth for gating.
 */

// Stripe needs the raw, unparsed body — disable any implicit parsing.
export const dynamic = "force-dynamic";

const STATUS_MAP: Record<string, SubscriptionStatus> = {
  active: "ACTIVE",
  trialing: "TRIALING",
  past_due: "PAST_DUE",
  canceled: "CANCELED",
  incomplete: "INCOMPLETE",
  incomplete_expired: "CANCELED",
  unpaid: "UNPAID",
};

export async function POST(req: NextRequest) {
  if (!stripeEnabled) {
    return NextResponse.json({ error: "Billing not configured" }, { status: 503 });
  }

  const sig = req.headers.get("stripe-signature");
  if (!sig) return NextResponse.json({ error: "Missing signature" }, { status: 400 });

  const rawBody = await req.text();
  let event: Stripe.Event;
  try {
    event = stripe.webhooks.constructEvent(rawBody, sig, env.STRIPE_WEBHOOK_SECRET!);
  } catch (err) {
    console.error("[stripe] signature verification failed", err);
    return NextResponse.json({ error: "Invalid signature" }, { status: 400 });
  }

  try {
    switch (event.type) {
      case "checkout.session.completed": {
        const session = event.data.object as Stripe.Checkout.Session;
        const accountId = session.client_reference_id ?? session.metadata?.accountId;
        const customerId = session.customer as string | null;
        const subId = session.subscription as string | null;
        if (accountId && customerId) {
          await db.account.update({
            where: { id: accountId },
            data: {
              stripeCustomerId: customerId,
              stripeSubscriptionId: subId ?? undefined,
            },
          });
        }
        if (subId) await syncSubscription(subId);
        break;
      }

      case "customer.subscription.created":
      case "customer.subscription.updated":
      case "customer.subscription.deleted": {
        const sub = event.data.object as Stripe.Subscription;
        await syncSubscription(sub.id, sub);
        break;
      }

      case "invoice.payment_failed": {
        const invoice = event.data.object as Stripe.Invoice;
        const customerId = invoice.customer as string;
        const account = await db.account.findUnique({
          where: { stripeCustomerId: customerId },
          select: { id: true },
        });
        if (account) {
          await db.account.update({
            where: { id: account.id },
            data: { subscriptionStatus: "PAST_DUE" },
          });
          await audit({
            accountId: account.id,
            action: "billing.payment_failed",
            metadata: { invoiceId: invoice.id },
          });
        }
        break;
      }

      default:
        // Unhandled events are acknowledged (200) so Stripe stops retrying.
        break;
    }
  } catch (err) {
    console.error("[stripe] handler error", event.type, err);
    // 500 → Stripe will retry. Safe because handlers are idempotent.
    return NextResponse.json({ error: "Handler error" }, { status: 500 });
  }

  return NextResponse.json({ received: true });
}

/** Fetch the subscription (if not provided) and project it onto the Account. */
async function syncSubscription(subId: string, provided?: Stripe.Subscription) {
  const sub = provided ?? (await stripe.subscriptions.retrieve(subId));
  const customerId = sub.customer as string;

  const account = await db.account.findUnique({
    where: { stripeCustomerId: customerId },
    select: { id: true },
  });
  if (!account) {
    console.warn("[stripe] no account for customer", customerId);
    return;
  }

  const priceId = sub.items.data[0]?.price.id ?? null;
  const plan = sub.status === "canceled" ? "FREE" : planForPriceId(priceId);
  // Team allows extra seats: base seats + purchased quantity beyond the base.
  const quantity = sub.items.data[0]?.quantity ?? 1;
  const seatLimit = Math.max(seatLimitForPlan(plan), quantity);

  await db.account.update({
    where: { id: account.id },
    data: {
      plan,
      seatLimit,
      stripeSubscriptionId: sub.id,
      stripePriceId: priceId,
      subscriptionStatus: STATUS_MAP[sub.status] ?? "INCOMPLETE",
      currentPeriodEnd: sub.current_period_end
        ? new Date(sub.current_period_end * 1000)
        : null,
    },
  });

  await audit({
    accountId: account.id,
    action: "billing.subscription_synced",
    metadata: { plan, status: sub.status, seatLimit },
  });
}
