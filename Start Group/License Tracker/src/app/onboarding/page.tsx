import { redirect } from "next/navigation";
import { getTenantContext } from "@/lib/tenant";
import { db } from "@/lib/db";
import { SetPassphraseForm } from "@/components/vault/set-passphrase-form";

/**
 * Onboarding gate for setting the master passphrase.
 *  - Admin without a passphrase: shows the set-passphrase form.
 *  - Member without a passphrase: must wait for an admin (cannot set it).
 *  - Already set: bounce to dashboard.
 */
export default async function OnboardingPage() {
  let ctx;
  try {
    ctx = await getTenantContext();
  } catch {
    redirect("/login");
  }

  const account = await db.account.findUniqueOrThrow({
    where: { id: ctx.accountId },
    select: { passphraseSet: true },
  });
  if (account.passphraseSet) redirect("/dashboard");

  return (
    <div className="flex min-h-screen items-center justify-center bg-muted/30 px-4">
      {ctx.role === "ADMIN" ? (
        <SetPassphraseForm />
      ) : (
        <div className="max-w-md text-center">
          <h1 className="text-xl font-semibold">Waiting for setup</h1>
          <p className="mt-2 text-muted-foreground">
            Your account admin hasn&apos;t set the master passphrase yet. Once they do,
            you&apos;ll be able to unlock the vault with it.
          </p>
        </div>
      )}
    </div>
  );
}
