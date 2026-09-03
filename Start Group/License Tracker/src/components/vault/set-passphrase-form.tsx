"use client";
import { useActionState, useEffect } from "react";
import { useRouter } from "next/navigation";
import { ShieldAlert } from "lucide-react";
import { setMasterPassphrase } from "@/app/actions/vault";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";

/** Admin onboarding step: set the account master passphrase (one-time). */
export function SetPassphraseForm() {
  const router = useRouter();
  const [state, action, pending] = useActionState(setMasterPassphrase, null);

  useEffect(() => {
    if (state?.ok) {
      router.push("/dashboard");
      router.refresh();
    }
  }, [state, router]);

  return (
    <Card className="w-full max-w-md">
      <CardHeader>
        <CardTitle>Set your master passphrase</CardTitle>
        <CardDescription>
          This passphrase encrypts every license key and sensitive note in your account.
          All members must enter it to unlock the vault each session.
        </CardDescription>
      </CardHeader>
      <CardContent>
        <div className="mb-4 flex gap-2 rounded-md border border-warning/40 bg-warning/10 p-3 text-sm">
          <ShieldAlert className="size-5 shrink-0 text-warning" />
          <span>
            <strong>Store it safely.</strong> There is no recovery — if every member forgets
            it, encrypted data cannot be decrypted. This is by design (zero-knowledge).
          </span>
        </div>
        <form action={action} className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="passphrase">Master passphrase</Label>
            <Input id="passphrase" name="passphrase" type="password" autoComplete="new-password" required minLength={12} />
            <p className="text-xs text-muted-foreground">At least 12 characters. Longer is stronger.</p>
          </div>
          <div className="space-y-2">
            <Label htmlFor="confirm">Confirm passphrase</Label>
            <Input id="confirm" name="confirm" type="password" autoComplete="new-password" required />
          </div>
          {state && !state.ok && <p className="text-sm text-destructive">{state.error}</p>}
          <Button type="submit" className="w-full" disabled={pending}>
            {pending ? "Securing…" : "Set passphrase & continue"}
          </Button>
        </form>
      </CardContent>
    </Card>
  );
}
