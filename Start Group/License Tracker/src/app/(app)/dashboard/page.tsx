import Link from "next/link";
import {
  DollarSign, AlarmClock, CheckCircle2, AlertTriangle, Plus,
} from "lucide-react";
import { getTenantContext, tenantDb } from "@/lib/tenant";
import { computeStatus } from "@/lib/license-service";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { formatCurrency, formatDate, daysUntil } from "@/lib/utils";

export const metadata = { title: "Dashboard" };

/** Annualize a cost to compare spend fairly across billing cycles. */
function annualized(costCents: number | null, cycle: string): number {
  if (!costCents) return 0;
  switch (cycle) {
    case "MONTHLY": return costCents * 12;
    case "QUARTERLY": return costCents * 4;
    case "BIENNIAL": return costCents / 2;
    case "ANNUAL":
    default: return costCents;
  }
}

export default async function DashboardPage() {
  const ctx = await getTenantContext();
  const t = tenantDb(ctx);

  // Single tenant-scoped fetch; aggregate in memory (fine up to thousands of
  // rows per tenant; switch to SQL aggregates if a tenant grows beyond that).
  const licenses = await t.license.findManyActive({ orderBy: { expirationDate: "asc" } });

  const now = Date.now();
  const within = (d: Date | null, days: number) =>
    d != null && d.getTime() >= now && d.getTime() <= now + days * 86_400_000;

  const activeCount = licenses.filter(
    (l) => computeStatus(l.expirationDate, l.status === "ARCHIVED") !== "EXPIRED" && l.status !== "ARCHIVED"
  ).length;

  const expiring30 = licenses.filter((l) => within(l.expirationDate, 30));
  const expiring60 = licenses.filter((l) => within(l.expirationDate, 60));
  const expiring90 = licenses.filter((l) => within(l.expirationDate, 90));
  const expired = licenses.filter(
    (l) => l.expirationDate && l.expirationDate.getTime() < now && l.status !== "ARCHIVED"
  );

  const annualSpend = licenses.reduce(
    (sum, l) => sum + annualized(l.costCents, l.billingCycle),
    0
  );

  // Cost by vendor (annualized), top 6.
  const byVendor = new Map<string, number>();
  for (const l of licenses) {
    const v = l.vendor || "Unknown";
    byVendor.set(v, (byVendor.get(v) ?? 0) + annualized(l.costCents, l.billingCycle));
  }
  const topVendors = [...byVendor.entries()].sort((a, b) => b[1] - a[1]).slice(0, 6);
  const maxVendor = topVendors[0]?.[1] ?? 1;

  const stats = [
    { label: "Annualized spend", value: formatCurrency(annualSpend), icon: DollarSign },
    { label: "Active licenses", value: String(activeCount), icon: CheckCircle2 },
    { label: "Expiring ≤ 30d", value: String(expiring30.length), icon: AlarmClock },
    { label: "Expired", value: String(expired.length), icon: AlertTriangle },
  ];

  return (
    <div className="space-y-8">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">Dashboard</h1>
          <p className="text-muted-foreground">Your license portfolio at a glance</p>
        </div>
        <Button asChild><Link href="/licenses?new=1"><Plus className="size-4" /> Add license</Link></Button>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {stats.map((s) => (
          <Card key={s.label}>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium text-muted-foreground">{s.label}</CardTitle>
              <s.icon className="size-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{s.value}</div>
            </CardContent>
          </Card>
        ))}
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        {/* Upcoming renewals */}
        <Card>
          <CardHeader>
            <CardTitle>Upcoming renewals (90 days)</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            {expiring90.length === 0 && (
              <p className="text-sm text-muted-foreground">Nothing due in the next 90 days. 🎉</p>
            )}
            {expiring90.slice(0, 8).map((l) => {
              const days = daysUntil(l.expirationDate);
              const variant = days != null && days <= 30 ? "destructive" : days != null && days <= 60 ? "warning" : "secondary";
              return (
                <div key={l.id} className="flex items-center justify-between gap-4">
                  <div className="min-w-0">
                    <Link href={`/licenses/${l.id}`} className="truncate font-medium hover:underline">{l.productName}</Link>
                    <p className="truncate text-xs text-muted-foreground">{l.vendor ?? "—"} · {formatDate(l.expirationDate)}</p>
                  </div>
                  <Badge variant={variant}>{days}d</Badge>
                </div>
              );
            })}
          </CardContent>
        </Card>

        {/* Cost by vendor */}
        <Card>
          <CardHeader>
            <CardTitle>Annualized cost by vendor</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            {topVendors.length === 0 && <p className="text-sm text-muted-foreground">No cost data yet.</p>}
            {topVendors.map(([vendor, cost]) => (
              <div key={vendor}>
                <div className="flex justify-between text-sm">
                  <span className="truncate">{vendor}</span>
                  <span className="font-medium">{formatCurrency(cost)}</span>
                </div>
                <div className="mt-1 h-2 w-full overflow-hidden rounded-full bg-secondary">
                  <div className="h-full bg-primary" style={{ width: `${Math.max(4, (cost / maxVendor) * 100)}%` }} />
                </div>
              </div>
            ))}
          </CardContent>
        </Card>
      </div>

      <div className="grid gap-4 sm:grid-cols-3">
        <MiniStat label="Expiring 31–60 days" value={expiring60.length - expiring30.length} />
        <MiniStat label="Expiring 61–90 days" value={expiring90.length - expiring60.length} />
        <MiniStat label="Total tracked" value={licenses.length} />
      </div>
    </div>
  );
}

function MiniStat({ label, value }: { label: string; value: number }) {
  return (
    <Card>
      <CardContent className="pt-6">
        <div className="text-2xl font-bold">{Math.max(0, value)}</div>
        <p className="text-sm text-muted-foreground">{label}</p>
      </CardContent>
    </Card>
  );
}
