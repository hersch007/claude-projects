import Link from "next/link";
import { ShieldCheck, KeyRound, Lock, BarChart3, FileLock2, Bell } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { PLANS } from "@/lib/plans";

/**
 * Public marketing / landing page. No auth required. Server component.
 */
export default function LandingPage() {
  const features = [
    { icon: Lock, title: "Encrypted at rest", body: "License keys & files are sealed with AES-256-GCM. The database only ever sees ciphertext." },
    { icon: KeyRound, title: "Master passphrase", body: "A per-account passphrase derives the encryption key in memory only — never stored, never logged." },
    { icon: ShieldCheck, title: "Strict tenant isolation", body: "Every query is scoped to your account. No cross-tenant data access, ever." },
    { icon: BarChart3, title: "Spend insights", body: "See total spend, upcoming renewals at 30/60/90 days, and cost by vendor at a glance." },
    { icon: FileLock2, title: "Encrypted attachments", body: "Store invoices & PDFs encrypted, tied to each license." },
    { icon: Bell, title: "Renewal reminders", body: "In-app reminders and one-click .ics calendar export so you never miss a renewal." },
  ];

  return (
    <main className="min-h-screen">
      {/* Nav */}
      <header className="border-b">
        <div className="container flex h-16 items-center justify-between">
          <div className="flex items-center gap-2 font-semibold">
            <ShieldCheck className="size-5 text-primary" />
            License Tracker
          </div>
          <nav className="flex items-center gap-3">
            <Button asChild variant="ghost"><Link href="/login">Sign in</Link></Button>
            <Button asChild><Link href="/signup">Get started</Link></Button>
          </nav>
        </div>
      </header>

      {/* Hero */}
      <section className="container py-24 text-center">
        <div className="mx-auto max-w-3xl">
          <span className="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs text-muted-foreground">
            <Lock className="size-3" /> Zero-knowledge-style encryption
          </span>
          <h1 className="mt-6 text-4xl font-bold tracking-tight sm:text-6xl">
            Track every software license — <span className="text-primary">securely</span>.
          </h1>
          <p className="mt-6 text-lg text-muted-foreground">
            A multi-tenant vault for your licenses and subscriptions. Encrypted at rest,
            protected by a master passphrase, and built for teams of 1 to 1,000+.
          </p>
          <div className="mt-8 flex justify-center gap-4">
            <Button asChild size="lg"><Link href="/signup">Start free</Link></Button>
            <Button asChild size="lg" variant="outline"><Link href="/login">Sign in</Link></Button>
          </div>
        </div>
      </section>

      {/* Features */}
      <section className="container pb-24">
        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
          {features.map((f) => (
            <Card key={f.title}>
              <CardContent className="pt-6">
                <f.icon className="size-6 text-primary" />
                <h3 className="mt-4 font-semibold">{f.title}</h3>
                <p className="mt-2 text-sm text-muted-foreground">{f.body}</p>
              </CardContent>
            </Card>
          ))}
        </div>
      </section>

      {/* Pricing */}
      <section className="container pb-24">
        <h2 className="text-center text-3xl font-bold">Simple, transparent pricing</h2>
        <div className="mt-10 grid gap-6 md:grid-cols-3">
          {Object.values(PLANS).map((plan) => (
            <Card key={plan.id} className={plan.id === "PRO" ? "border-primary shadow-md" : ""}>
              <CardContent className="pt-6">
                <h3 className="text-lg font-semibold">{plan.name}</h3>
                <p className="mt-2 text-3xl font-bold">{plan.priceLabel}</p>
                <ul className="mt-6 space-y-2 text-sm text-muted-foreground">
                  <li>{plan.seatLimit} seat{plan.seatLimit > 1 ? "s" : ""}{plan.perSeatExtra ? " + extras" : ""}</li>
                  <li>{plan.licenseLimit ? `${plan.licenseLimit} licenses` : "Unlimited licenses"}</li>
                  {plan.features.attachments && <li>Encrypted attachments</li>}
                  {plan.features.exports && <li>CSV / JSON / backup export</li>}
                  {plan.features.apiAccess && <li>REST API access</li>}
                  {plan.features.advancedReporting && <li>Advanced reporting</li>}
                </ul>
                <Button asChild className="mt-6 w-full" variant={plan.id === "PRO" ? "default" : "outline"}>
                  <Link href="/signup">Choose {plan.name}</Link>
                </Button>
              </CardContent>
            </Card>
          ))}
        </div>
      </section>

      <footer className="border-t py-8 text-center text-sm text-muted-foreground">
        © {new Date().getFullYear()} License Tracker · Self-hostable · Open security model
      </footer>
    </main>
  );
}
