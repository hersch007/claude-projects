"use client";
import { useState } from "react";
import { Button } from "@/components/ui/button";

/**
 * Billing controls. Calls our server routes which create Stripe Checkout /
 * Portal sessions (admin-only, tenant derived server-side) and redirect.
 */
export function BillingActions({ plan, hasCustomer }: { plan: string; hasCustomer: boolean }) {
  const [loading, setLoading] = useState<string | null>(null);

  async function checkout(target: "PRO" | "TEAM") {
    setLoading(target);
    const res = await fetch("/api/stripe/checkout", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ plan: target, seats: target === "TEAM" ? 1 : undefined }),
    });
    const data = await res.json();
    setLoading(null);
    if (data.url) window.location.href = data.url;
    else alert(data.error ?? "Could not start checkout");
  }

  async function portal() {
    setLoading("portal");
    const res = await fetch("/api/stripe/portal", { method: "POST" });
    const data = await res.json();
    setLoading(null);
    if (data.url) window.location.href = data.url;
    else alert(data.error ?? "Could not open billing portal");
  }

  return (
    <div className="flex flex-wrap gap-3">
      {plan !== "PRO" && (
        <Button onClick={() => checkout("PRO")} disabled={!!loading}>
          {loading === "PRO" ? "…" : "Upgrade to Pro"}
        </Button>
      )}
      {plan !== "TEAM" && (
        <Button variant="outline" onClick={() => checkout("TEAM")} disabled={!!loading}>
          {loading === "TEAM" ? "…" : "Upgrade to Team"}
        </Button>
      )}
      {hasCustomer && (
        <Button variant="secondary" onClick={portal} disabled={!!loading}>
          {loading === "portal" ? "…" : "Manage billing"}
        </Button>
      )}
    </div>
  );
}
