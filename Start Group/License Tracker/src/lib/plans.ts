import type { Plan } from "@prisma/client";
import { env } from "@/env";

/**
 * Single source of truth for plan capabilities. Limits are enforced server-side
 * (seat invites, license creation, feature gates) — the UI only reflects them.
 */
export interface PlanDefinition {
  id: Plan;
  name: string;
  priceLabel: string;
  seatLimit: number; // base seats included
  perSeatExtra: boolean; // Team allows buying extra seats
  licenseLimit: number | null; // null = unlimited
  features: {
    attachments: boolean;
    exports: boolean;
    apiAccess: boolean;
    advancedReporting: boolean;
    prioritySupport: boolean;
  };
  // Stripe price id(s) — undefined for the free plan.
  stripePriceId?: string;
  stripePerSeatPriceId?: string;
}

export const PLANS: Record<Plan, PlanDefinition> = {
  FREE: {
    id: "FREE",
    name: "Free",
    priceLabel: "$0",
    seatLimit: 1,
    perSeatExtra: false,
    licenseLimit: 50,
    features: {
      attachments: false,
      exports: false,
      apiAccess: false,
      advancedReporting: false,
      prioritySupport: false,
    },
  },
  PRO: {
    id: "PRO",
    name: "Pro",
    priceLabel: "$12/mo",
    seatLimit: 5,
    perSeatExtra: false,
    licenseLimit: null,
    features: {
      attachments: true,
      exports: true,
      apiAccess: false,
      advancedReporting: false,
      prioritySupport: true,
    },
    stripePriceId: env.STRIPE_PRICE_PRO,
  },
  TEAM: {
    id: "TEAM",
    name: "Team",
    priceLabel: "$39/mo + $6/seat",
    seatLimit: 10,
    perSeatExtra: true,
    licenseLimit: null,
    features: {
      attachments: true,
      exports: true,
      apiAccess: true,
      advancedReporting: true,
      prioritySupport: true,
    },
    stripePriceId: env.STRIPE_PRICE_TEAM_BASE,
    stripePerSeatPriceId: env.STRIPE_PRICE_TEAM_PER_SEAT,
  },
};

export function planForPriceId(priceId: string | null | undefined): Plan {
  if (!priceId) return "FREE";
  if (priceId === env.STRIPE_PRICE_PRO) return "PRO";
  if (priceId === env.STRIPE_PRICE_TEAM_BASE) return "TEAM";
  return "FREE";
}

export function seatLimitForPlan(plan: Plan): number {
  return PLANS[plan].seatLimit;
}
