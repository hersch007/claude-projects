"use client";
import { useActionState } from "react";
import { useRouter } from "next/navigation";
import { useEffect } from "react";
import { Lock } from "lucide-react";
import { unlockVault } from "@/app/actions/vault";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";

/** Full-screen gate shown when the vault is locked. Derives the in-memory DEK. */
export function UnlockForm() {
  const router = useRouter();
  const [state, action, pending] = useActionState(unlockVault, null);

  useEffect(() => {
    if (state?.ok) router.refresh();
  }, [state, router]);

  return (
    <div className="flex min-h-screen items-center justify-center bg-muted/30 px-4">
      <Card className="w-full max-w-md">
        <CardHeader className="text-center">
          <div className="mx-auto mb-2 flex size-12 items-center justify-center rounded-full bg-primary/10">
            <Lock className="size-6 text-primary" />
          </div>
          <CardTitle>Vault locked</CardTitle>
          <CardDescription>
            Enter your account master passphrase to decrypt your licenses for this session.
            It is held only in server memory and never stored.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form action={action} className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="passphrase">Master passphrase</Label>
              <Input
                id="passphrase"
                name="passphrase"
                type="password"
                autoComplete="off"
                autoFocus
                required
              />
            </div>
            {state && !state.ok && <p className="text-sm text-destructive">{state.error}</p>}
            <Button type="submit" className="w-full" disabled={pending}>
              {pending ? "Unlocking…" : "Unlock vault"}
            </Button>
          </form>
        </CardContent>
      </Card>
    </div>
  );
}
