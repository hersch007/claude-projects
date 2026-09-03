import { getTenantContext, tenantDb } from "@/lib/tenant";
import { db } from "@/lib/db";
import { PLANS } from "@/lib/plans";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { MembersSection } from "@/components/settings/members-section";
import { ChangePassphraseForm } from "@/components/settings/change-passphrase-form";
import { BillingActions } from "@/components/settings/billing-actions";
import { formatDate } from "@/lib/utils";

export const metadata = { title: "Settings" };

export default async function SettingsPage() {
  const ctx = await getTenantContext();
  const account = await tenantDb(ctx).account.get();
  const isAdmin = ctx.role === "ADMIN";

  const [members, invites] = await Promise.all([
    db.user.findMany({
      where: { accountId: ctx.accountId },
      select: { id: true, email: true, name: true, role: true, emailVerified: true },
      orderBy: { createdAt: "asc" },
    }),
    db.invitation.findMany({
      where: { accountId: ctx.accountId, status: "PENDING" },
      select: { id: true, email: true, role: true },
    }),
  ]);

  const planDef = PLANS[account.plan];

  return (
    <div className="mx-auto max-w-3xl space-y-6">
      <h1 className="text-2xl font-bold">Settings</h1>

      {/* Plan / billing */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            Plan & billing <Badge>{account.plan}</Badge>
          </CardTitle>
          <CardDescription>
            {planDef.priceLabel} ·{" "}
            {account.subscriptionStatus ? `Status: ${account.subscriptionStatus}` : "No active subscription"}
            {account.currentPeriodEnd ? ` · Renews ${formatDate(account.currentPeriodEnd)}` : ""}
          </CardDescription>
        </CardHeader>
        <CardContent>
          {isAdmin ? (
            <BillingActions plan={account.plan} hasCustomer={!!account.stripeCustomerId} />
          ) : (
            <p className="text-sm text-muted-foreground">Only admins can manage billing.</p>
          )}
        </CardContent>
      </Card>

      {/* Members */}
      <Card>
        <CardHeader>
          <CardTitle>Members</CardTitle>
          <CardDescription>Manage who can access this account ({planDef.seatLimit} seats).</CardDescription>
        </CardHeader>
        <CardContent>
          <MembersSection
            members={members}
            invites={invites}
            seatLimit={account.seatLimit}
            isAdmin={isAdmin}
            selfId={ctx.userId}
          />
        </CardContent>
      </Card>

      {/* Security */}
      <Card>
        <CardHeader>
          <CardTitle>Security — master passphrase</CardTitle>
          <CardDescription>
            Changing it re-wraps your data key (no data re-encryption needed) and signs
            everyone out of the vault. There is no recovery if the passphrase is lost.
          </CardDescription>
        </CardHeader>
        <CardContent>
          {isAdmin ? (
            <ChangePassphraseForm />
          ) : (
            <p className="text-sm text-muted-foreground">Only admins can change the master passphrase.</p>
          )}
        </CardContent>
      </Card>

      {/* Data / GDPR */}
      <Card>
        <CardHeader>
          <CardTitle>Your data (GDPR / CCPA)</CardTitle>
          <CardDescription>Export or request deletion of your account data.</CardDescription>
        </CardHeader>
        <CardContent className="flex flex-wrap gap-3">
          <a href="/api/export?format=json" className="text-sm text-primary hover:underline">Export all data (JSON)</a>
          <a href="/api/export?format=backup" className="text-sm text-primary hover:underline">Encrypted full backup</a>
        </CardContent>
      </Card>
    </div>
  );
}
