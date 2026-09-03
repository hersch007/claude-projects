"use client";
import { useMemo, useState, useEffect } from "react";
import { useRouter } from "next/navigation";
import { Search, Pencil, Trash2, CalendarPlus } from "lucide-react";
import type { LicenseDTO, LicenseStatusComputed } from "@/lib/license-service";
import { deleteLicenseAction } from "@/app/actions/licenses";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import {
  Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter,
} from "@/components/ui/dialog";
import { RevealKey } from "./reveal-key";
import { LicenseFormDialog } from "./license-form-dialog";
import { formatCurrency, formatDate, daysUntil } from "@/lib/utils";

const STATUS_STYLES: Record<LicenseStatusComputed, { label: string; variant: "success" | "warning" | "destructive" | "secondary" }> = {
  ACTIVE: { label: "Active", variant: "success" },
  EXPIRING_SOON: { label: "Expiring", variant: "warning" },
  EXPIRED: { label: "Expired", variant: "destructive" },
  ARCHIVED: { label: "Archived", variant: "secondary" },
};

export function LicenseTable({
  licenses,
  autoOpenNew,
}: {
  licenses: LicenseDTO[];
  autoOpenNew?: boolean;
}) {
  const router = useRouter();
  const [query, setQuery] = useState("");
  const [status, setStatus] = useState<string>("ALL");
  const [editing, setEditing] = useState<LicenseDTO | null>(null);
  const [deleting, setDeleting] = useState<LicenseDTO | null>(null);
  const [newOpen, setNewOpen] = useState(false);
  const [busy, setBusy] = useState(false);

  useEffect(() => { if (autoOpenNew) setNewOpen(true); }, [autoOpenNew]);

  const filtered = useMemo(() => {
    const q = query.toLowerCase().trim();
    return licenses.filter((l) => {
      if (status !== "ALL" && l.status !== status) return false;
      if (!q) return true;
      return (
        l.productName.toLowerCase().includes(q) ||
        (l.vendor ?? "").toLowerCase().includes(q) ||
        l.tags.some((t) => t.toLowerCase().includes(q))
      );
    });
  }, [licenses, query, status]);

  async function confirmDelete() {
    if (!deleting) return;
    setBusy(true);
    const res = await deleteLicenseAction(deleting.id);
    setBusy(false);
    setDeleting(null);
    if (res.ok) router.refresh();
  }

  return (
    <div className="space-y-4">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
        <div className="relative flex-1">
          <Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
          <Input
            placeholder="Search product, vendor, tag…  (press /)"
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            className="pl-9"
            data-search
          />
        </div>
        <select value={status} onChange={(e) => setStatus(e.target.value)} className="h-10 rounded-md border border-input bg-background px-3 text-sm">
          <option value="ALL">All statuses</option>
          <option value="ACTIVE">Active</option>
          <option value="EXPIRING_SOON">Expiring</option>
          <option value="EXPIRED">Expired</option>
          <option value="ARCHIVED">Archived</option>
        </select>
        <LicenseFormDialog open={newOpen} onOpenChange={setNewOpen} />
      </div>

      <div className="overflow-x-auto rounded-lg border">
        <table className="w-full text-sm">
          <thead className="bg-muted/50 text-left text-xs uppercase text-muted-foreground">
            <tr>
              <th className="p-3">Product</th>
              <th className="p-3">Key</th>
              <th className="p-3">Status</th>
              <th className="p-3">Expires</th>
              <th className="p-3">Cost</th>
              <th className="p-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            {filtered.length === 0 && (
              <tr><td colSpan={6} className="p-8 text-center text-muted-foreground">No licenses found.</td></tr>
            )}
            {filtered.map((l) => {
              const s = STATUS_STYLES[l.status];
              const days = daysUntil(l.expirationDate);
              return (
                <tr key={l.id} className="border-t hover:bg-muted/30">
                  <td className="p-3">
                    <div className="font-medium">{l.productName}</div>
                    <div className="text-xs text-muted-foreground">{l.vendor ?? "—"}</div>
                    {l.tags.length > 0 && (
                      <div className="mt-1 flex flex-wrap gap-1">
                        {l.tags.slice(0, 3).map((t) => <Badge key={t} variant="outline" className="text-[10px]">{t}</Badge>)}
                      </div>
                    )}
                  </td>
                  <td className="p-3"><RevealKey licenseId={l.id} hint={l.keyHint} hasKey={l.hasKey} /></td>
                  <td className="p-3"><Badge variant={s.variant}>{s.label}</Badge></td>
                  <td className="p-3">
                    {formatDate(l.expirationDate)}
                    {days != null && days >= 0 && days <= 90 && (
                      <div className="text-xs text-muted-foreground">{days}d left</div>
                    )}
                  </td>
                  <td className="p-3">{formatCurrency(l.costCents, l.currency)}</td>
                  <td className="p-3">
                    <div className="flex items-center justify-end gap-1">
                      {(l.nextRenewalDate || l.expirationDate) && (
                        <a href={`/api/licenses/${l.id}/ics`} title="Add renewal reminder (.ics)">
                          <Button size="icon" variant="ghost" className="size-8"><CalendarPlus className="size-4" /></Button>
                        </a>
                      )}
                      <Button size="icon" variant="ghost" className="size-8" onClick={() => setEditing(l)} title="Edit"><Pencil className="size-4" /></Button>
                      <Button size="icon" variant="ghost" className="size-8 text-destructive" onClick={() => setDeleting(l)} title="Delete"><Trash2 className="size-4" /></Button>
                    </div>
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>

      {/* Edit modal */}
      {editing && (
        <LicenseFormDialog
          license={editing}
          trigger={false}
          open={!!editing}
          onOpenChange={(o) => !o && setEditing(null)}
        />
      )}

      {/* Delete confirmation */}
      <Dialog open={!!deleting} onOpenChange={(o) => !o && setDeleting(null)}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Delete license?</DialogTitle>
            <DialogDescription>
              “{deleting?.productName}” will be moved to trash (soft-deleted). The
              encrypted key is retained until permanent deletion. This is auditable.
            </DialogDescription>
          </DialogHeader>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDeleting(null)}>Cancel</Button>
            <Button variant="destructive" onClick={confirmDelete} disabled={busy}>
              {busy ? "Deleting…" : "Delete"}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
