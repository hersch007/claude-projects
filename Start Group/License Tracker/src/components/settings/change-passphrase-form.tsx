"use client";
import { useActionState, useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { changeMasterPassphrase } from "@/app/actions/vault";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

/**
 * Change the master passphrase. Because we use envelope encryption, this only
 * re-wraps the data key — it does NOT re-encrypt every license. After success,
 * all sessions (including this one) are forced to re-unlock.
 */
export function ChangePassphraseForm() {
  const router = useRouter();
  const [state, action, pending] = useActionState(changeMasterPassphrase, null);
  const [done, setDone] = useState(false);

  useEffect(() => {
    if (state?.ok) {
      setDone(true);
      // keyVersion bumped server-side → next navigation hits the unlock gate.
      setTimeout(() => router.refresh(), 1200);
    }
  }, [state, router]);

  return (
    <form action={action} className="space-y-4">
      <div className="space-y-2">
        <Label htmlFor="current">Current passphrase</Label>
        <Input id="current" name="current" type="password" autoComplete="off" required />
      </div>
      <div className="space-y-2">
        <Label htmlFor="next">New passphrase</Label>
        <Input id="next" name="next" type="password" autoComplete="new-password" required minLength={12} />
      </div>
      <div className="space-y-2">
        <Label htmlFor="confirm">Confirm new passphrase</Label>
        <Input id="confirm" name="confirm" type="password" autoComplete="new-password" required />
      </div>
      {state && !state.ok && <p className="text-sm text-destructive">{state.error}</p>}
      {done && <p className="text-sm text-success">Passphrase changed. Re-unlocking…</p>}
      <Button type="submit" disabled={pending || done}>
        {pending ? "Updating…" : "Change passphrase"}
      </Button>
    </form>
  );
}
