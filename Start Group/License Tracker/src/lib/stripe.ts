import "server-only";
import Stripe from "stripe";
import { env, stripeEnabled } from "@/env";

/**
 * Stripe SDK singleton. Guarded so the app still boots (and the dashboard still
 * works) when Stripe keys aren't configured for local/self-hosted use without
 * billing. Call sites should check `stripeEnabled` before invoking billing.
 */
export const stripe = stripeEnabled
  ? new Stripe(env.STRIPE_SECRET_KEY!, {
      // Pin to the SDK's expected API version. Update this together with the
      // `stripe` package so request/response types stay in sync.
      apiVersion: "2025-02-24.acacia",
      typescript: true,
      appInfo: { name: "License Tracker" },
    })
  : (null as unknown as Stripe);

export { stripeEnabled };
