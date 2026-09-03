"use client";
import { useActionState, useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { Trash2 } from "lucide-react";
import { inviteMember, removeMember } from "@/app/actions/members";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";

interface Member { id: string; email: string; name: string | null; role: string; emailVerified: boolean }
interface Invite { id: string; email: string; role: string }

export function MembersSection({
  members, invites, seatLimit, isAdmin, selfId,
}: {
  members: Member[];
  invites: Invite[];
  seatLimit: number;
  isAdmin: boolean;
  selfId: string;
}) {
  const router = useRouter();
  const [state, action, pending] = useActionState(inviteMember, null);
  const [busy, setBusy] = useState<string | null>(null);
  const used = members.length + invites.length;

  useEffect(() => { if (state?.ok) router.refresh(); }, [state, router]);

  async function onRemove(id: string) {
    if (!confirm("Remove this member?")) return;
    setBusy(id);
    await removeMember(id);
    setBusy(null);
    router.refresh();
  }

  return (
    <div className="space-y-4">
      <p className="text-sm text-muted-foreground">{used} of {seatLimit} seats used</p>

      <div className="divide-y rounded-lg border">
        {members.map((m) => (
          <div key={m.id} className="flex items-center justify-between p-3">
            <div>
              <div className="font-medium">{m.name ?? m.email}</div>
              <div className="text-xs text-muted-foreground">{m.email}</div>
            </div>
            <div className="flex items-center gap-2">
              {!m.emailVerified && <Badge variant="warning">Unverified</Badge>}
              <Badge variant={m.role === "ADMIN" ? "default" : "secondary"}>{m.role}</Badge>
              {isAdmin && m.id !== selfId && (
                <Button size="icon" variant="ghost" className="size-8 text-destructive" disabled={busy === m.id} onClick={() => onRemove(m.id)}>
                  <Trash2 className="size-4" />
                </Button>
              )}
            </div>
          </div>
        ))}
        {invites.map((i) => (
          <div key={i.id} className="flex items-center justify-between p-3 opacity-70">
            <div className="text-sm">{i.email}</div>
            <Badge variant="outline">Pending invite</Badge>
          </div>
        ))}
      </div>

      {isAdmin && (
        <form action={action} className="flex flex-col gap-2 sm:flex-row">
          <Input name="email" type="email" placeholder="teammate@company.com" required className="flex-1" />
          <select name="role" className="h-10 rounded-md border border-input bg-background px-3 text-sm">
            <option value="MEMBER">Member</option>
            <option value="ADMIN">Admin</option>
          </select>
          <Button type="submit" disabled={pending || used >= seatLimit}>Invite</Button>
        </form>
      )}
      {state && !state.ok && <p className="text-sm text-destructive">{state.error}</p>}
    </div>
  );
}
