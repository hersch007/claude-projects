import { redirect } from "next/navigation";
import { getTenantContext, isUnlocked } from "@/lib/tenant";
import { db } from "@/lib/db";
import { UnlockForm } from "@/components/vault/unlock-form";
import { AppNav } from "@/components/app-nav";

/**
 * Authenticated app shell. This server component is the second, authoritative
 * layer of the vault gate (middleware is only a coarse cookie check):
 *
 *   1. No session            → redirect to /login (getTenantContext throws).
 *   2. Passphrase not set yet → redirect to /onboarding (admin sets it).
 *   3. Vault locked           → render the unlock gate (no app content leaks).
 *   4. Unlocked               → render the app.
 */
export default async function AppLayout({ children }: { children: React.ReactNode }) {
  let ctx;
  try {
    ctx = await getTenantContext();
  } catch {
    redirect("/login");
  }

  const account = await db.account.findUniqueOrThrow({
    where: { id: ctx.accountId },
    select: { name: true, plan: true, passphraseSet: true },
  });

  if (!account.passphraseSet) redirect("/onboarding");

  // Gate: if the session has no in-memory DEK, force re-entry of the passphrase.
  if (!isUnlocked(ctx)) {
    return <UnlockForm />;
  }

  return (
    <div className="flex min-h-screen flex-col">
      <AppNav
        orgName={account.name}
        plan={account.plan}
        userEmail={ctx.email}
      />
      <main className="container flex-1 py-8">{children}</main>
    </div>
  );
}
