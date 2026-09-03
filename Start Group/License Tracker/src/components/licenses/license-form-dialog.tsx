"use client";
import { useState } from "react";
import { useRouter } from "next/navigation";
import { Plus } from "lucide-react";
import type { LicenseDTO } from "@/lib/license-service";
import { createLicenseAction, updateLicenseAction } from "@/app/actions/licenses";
import {
  Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter, DialogTrigger,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

const LICENSE_TYPES = ["PERPETUAL", "SUBSCRIPTION", "VOLUME", "OEM", "TRIAL", "OPEN_SOURCE", "OTHER"];
const BILLING_CYCLES = ["NONE", "MONTHLY", "QUARTERLY", "ANNUAL", "BIENNIAL", "CUSTOM"];

/**
 * Quick-add / edit modal. When `license` is provided we edit; otherwise create.
 * For edits, the license key field is left blank and only updated if typed
 * (blank = keep existing encrypted key) — we never round-trip the plaintext key.
 */
export function LicenseFormDialog({
  license,
  open: controlledOpen,
  onOpenChange,
  trigger = true,
}: {
  license?: LicenseDTO;
  open?: boolean;
  onOpenChange?: (open: boolean) => void;
  trigger?: boolean;
}) {
  const router = useRouter();
  const [internalOpen, setInternalOpen] = useState(false);
  const open = controlledOpen ?? internalOpen;
  const setOpen = onOpenChange ?? setInternalOpen;
  const [error, setError] = useState<string | null>(null);
  const [pending, setPending] = useState(false);
  const isEdit = !!license;

  const dateVal = (iso: string | null) => (iso ? iso.slice(0, 10) : "");

  async function onSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setError(null);
    setPending(true);
    const fd = new FormData(e.currentTarget);
    const result = isEdit
      ? await updateLicenseAction(license!.id, null, fd)
      : await createLicenseAction(null, fd);
    setPending(false);
    if (!result.ok) { setError(result.error); return; }
    setOpen(false);
    router.refresh();
  }

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      {trigger && !isEdit && (
        <DialogTrigger asChild>
          <Button><Plus className="size-4" /> Add license</Button>
        </DialogTrigger>
      )}
      <DialogContent className="sm:max-w-2xl">
        <DialogHeader>
          <DialogTitle>{isEdit ? "Edit license" : "Add license"}</DialogTitle>
          <DialogDescription>
            {isEdit
              ? "Leave the license key blank to keep the existing one."
              : "The license key is encrypted before it is stored."}
          </DialogDescription>
        </DialogHeader>

        <form onSubmit={onSubmit} className="grid gap-4 sm:grid-cols-2">
          <Field label="Product name *" className="sm:col-span-2">
            <Input name="productName" required defaultValue={license?.productName} />
          </Field>
          <Field label="Vendor">
            <Input name="vendor" defaultValue={license?.vendor ?? ""} />
          </Field>
          <Field label="License type">
            <select name="licenseType" defaultValue={license?.licenseType ?? "SUBSCRIPTION"} className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
              {LICENSE_TYPES.map((t) => <option key={t} value={t}>{t}</option>)}
            </select>
          </Field>
          <Field label={isEdit ? "License key (leave blank to keep)" : "License key"} className="sm:col-span-2">
            <Input name="licenseKey" type="password" autoComplete="off" placeholder={isEdit ? "••••••••••" : ""} />
          </Field>

          <Field label="Purchase date">
            <Input name="purchaseDate" type="date" defaultValue={dateVal(license?.purchaseDate ?? null)} />
          </Field>
          <Field label="Start date">
            <Input name="startDate" type="date" defaultValue={dateVal(license?.startDate ?? null)} />
          </Field>
          <Field label="Expiration date">
            <Input name="expirationDate" type="date" defaultValue={dateVal(license?.expirationDate ?? null)} />
          </Field>
          <Field label="Next renewal date">
            <Input name="nextRenewalDate" type="date" defaultValue={dateVal(license?.nextRenewalDate ?? null)} />
          </Field>

          <Field label="Cost (per cycle)">
            <Input name="cost" type="number" step="0.01" min="0" defaultValue={license?.costCents != null ? license.costCents / 100 : ""} />
          </Field>
          <Field label="Currency">
            <Input name="currency" maxLength={3} defaultValue={license?.currency ?? "USD"} />
          </Field>
          <Field label="Quantity">
            <Input name="quantity" type="number" min="1" defaultValue={license?.quantity ?? 1} />
          </Field>
          <Field label="Billing cycle">
            <select name="billingCycle" defaultValue={license?.billingCycle ?? "NONE"} className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
              {BILLING_CYCLES.map((c) => <option key={c} value={c}>{c}</option>)}
            </select>
          </Field>

          <Field label="Renewal URL" className="sm:col-span-2">
            <Input name="renewalUrl" type="url" defaultValue={license?.renewalUrl ?? ""} placeholder="https://…" />
          </Field>
          <Field label="Tags (comma separated)" className="sm:col-span-2">
            <Input
              defaultValue={license?.tags?.join(", ") ?? ""}
              onChange={(e) => {
                const tags = e.target.value.split(",").map((t) => t.trim()).filter(Boolean);
                (e.target.form!.elements.namedItem("tags") as HTMLInputElement).value = JSON.stringify(tags);
              }}
            />
            <input type="hidden" name="tags" defaultValue={JSON.stringify(license?.tags ?? [])} />
          </Field>

          <Field label="Notes" className="sm:col-span-2">
            <textarea name="notes" rows={3} defaultValue={license && !license.notesSensitive ? license.notes ?? "" : ""} className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm" />
            <label className="mt-1 flex items-center gap-2 text-xs text-muted-foreground">
              <input type="checkbox" name="notesSensitive" defaultChecked={license?.notesSensitive} />
              Encrypt these notes (treat as sensitive)
            </label>
          </Field>

          <Field label="Invoice reference">
            <Input name="invoiceReference" defaultValue={license?.invoiceReference ?? ""} />
          </Field>
          <Field label="Vendor support contact">
            <Input name="vendorSupportContact" defaultValue={license?.vendorSupportContact ?? ""} />
          </Field>

          <label className="flex items-center gap-2 text-sm sm:col-span-2">
            <input type="checkbox" name="autoRenew" defaultChecked={license?.autoRenew} /> Auto-renew
          </label>

          {error && <p className="text-sm text-destructive sm:col-span-2">{error}</p>}

          <DialogFooter className="sm:col-span-2">
            <Button type="button" variant="outline" onClick={() => setOpen(false)}>Cancel</Button>
            <Button type="submit" disabled={pending}>{pending ? "Saving…" : isEdit ? "Save changes" : "Create license"}</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

function Field({ label, className, children }: { label: string; className?: string; children: React.ReactNode }) {
  return (
    <div className={`space-y-1.5 ${className ?? ""}`}>
      <Label>{label}</Label>
      {children}
    </div>
  );
}
