"use server";

import { headers } from "next/headers";
import { revalidatePath } from "next/cache";
import { getTenantContext, AuthError } from "@/lib/tenant";
import { clientIp } from "@/lib/rate-limit";
import { licenseInputSchema } from "@/lib/validations";
import {
  createLicense,
  updateLicense,
  deleteLicense,
} from "@/lib/license-service";

type ActionResult = { ok: true; id?: string } | { ok: false; error: string };

async function meta() {
  const h = await headers();
  return { ip: clientIp(h), ua: h.get("user-agent") };
}

/**
 * Parse a license form payload (FormData) into the validated input shape.
 * Numbers/booleans/arrays arrive as strings/JSON from the form and are coerced.
 */
function parseLicenseForm(formData: FormData) {
  const num = (k: string) => {
    const v = formData.get(k);
    return v === null || v === "" ? undefined : Number(v);
  };
  const json = (k: string, fallback: unknown) => {
    const v = formData.get(k);
    if (typeof v !== "string" || v === "") return fallback;
    try {
      return JSON.parse(v);
    } catch {
      return fallback;
    }
  };

  return licenseInputSchema.safeParse({
    productName: formData.get("productName"),
    vendor: formData.get("vendor") || null,
    licenseType: formData.get("licenseType") || undefined,
    licenseKey: formData.get("licenseKey") || undefined,
    purchaseDate: formData.get("purchaseDate") || "",
    startDate: formData.get("startDate") || "",
    expirationDate: formData.get("expirationDate") || "",
    nextRenewalDate: formData.get("nextRenewalDate") || "",
    cost: num("cost") ?? null,
    currency: formData.get("currency") || "USD",
    quantity: num("quantity") ?? 1,
    billingCycle: formData.get("billingCycle") || undefined,
    autoRenew: formData.get("autoRenew") === "on" || formData.get("autoRenew") === "true",
    renewalUrl: formData.get("renewalUrl") || null,
    assignedTo: json("assignedTo", []),
    tags: json("tags", []),
    notes: formData.get("notes") || null,
    notesSensitive: formData.get("notesSensitive") === "on" || formData.get("notesSensitive") === "true",
    invoiceReference: formData.get("invoiceReference") || null,
    vendorSupportContact: formData.get("vendorSupportContact") || null,
    customFields: json("customFields", {}),
  });
}

function handleError(err: unknown): ActionResult {
  if (err instanceof AuthError && err.code === "LOCKED") {
    return { ok: false, error: "Vault is locked. Enter your master passphrase first." };
  }
  return { ok: false, error: err instanceof Error ? err.message : "Something went wrong." };
}

export async function createLicenseAction(
  _prev: unknown,
  formData: FormData
): Promise<ActionResult> {
  try {
    const ctx = await getTenantContext();
    const parsed = parseLicenseForm(formData);
    if (!parsed.success) {
      return { ok: false, error: parsed.error.issues[0]?.message ?? "Invalid input" };
    }
    const dto = await createLicense(ctx, parsed.data, await meta());
    revalidatePath("/licenses");
    revalidatePath("/dashboard");
    return { ok: true, id: dto.id };
  } catch (err) {
    return handleError(err);
  }
}

export async function updateLicenseAction(
  id: string,
  _prev: unknown,
  formData: FormData
): Promise<ActionResult> {
  try {
    const ctx = await getTenantContext();
    const parsed = parseLicenseForm(formData);
    if (!parsed.success) {
      return { ok: false, error: parsed.error.issues[0]?.message ?? "Invalid input" };
    }
    const dto = await updateLicense(ctx, id, parsed.data, await meta());
    revalidatePath("/licenses");
    revalidatePath(`/licenses/${id}`);
    revalidatePath("/dashboard");
    return { ok: true, id: dto.id };
  } catch (err) {
    return handleError(err);
  }
}

export async function deleteLicenseAction(id: string): Promise<ActionResult> {
  try {
    const ctx = await getTenantContext();
    await deleteLicense(ctx, id, await meta());
    revalidatePath("/licenses");
    revalidatePath("/dashboard");
    return { ok: true };
  } catch (err) {
    return handleError(err);
  }
}
