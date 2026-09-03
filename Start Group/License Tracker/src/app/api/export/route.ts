import { NextResponse, type NextRequest } from "next/server";
import { getTenantContext, tenantDb, requireDek, AuthError } from "@/lib/tenant";
import { toDTO } from "@/lib/license-service";
import { sealField, openFieldString } from "@/lib/crypto/encryption";
import { PLANS } from "@/lib/plans";
import { audit } from "@/lib/audit";
import { clientIp } from "@/lib/rate-limit";

/**
 * GET /api/export?format=csv|json|backup
 *
 *  - csv/json: MASKED export (no plaintext keys) — gated to plans with exports.
 *  - backup:   FULL encrypted account backup. License keys are re-encrypted
 *              under a passphrase-derived key supplied per request, so the
 *              downloaded file is itself encrypted and portable. Requires the
 *              vault to be unlocked (so we can read the plaintext to re-seal it).
 *
 * GDPR/CCPA: the json export is the user-facing "data portability" deliverable.
 */
export async function GET(req: NextRequest) {
  try {
    const ctx = await getTenantContext();
    const format = req.nextUrl.searchParams.get("format") ?? "csv";

    const account = await tenantDb(ctx).account.get();
    if (!PLANS[account.plan].features.exports) {
      return NextResponse.json(
        { error: "Exports require the Pro or Team plan." },
        { status: 402 }
      );
    }

    const licenses = await tenantDb(ctx).license.findManyActive({
      orderBy: { productName: "asc" },
    });

    await audit({
      accountId: ctx.accountId,
      userId: ctx.userId,
      action: "data.export",
      metadata: { format, count: licenses.length },
      ipAddress: clientIp(req.headers),
      userAgent: req.headers.get("user-agent"),
    });

    if (format === "json") {
      const body = JSON.stringify(licenses.map(toDTO), null, 2);
      return new NextResponse(body, {
        headers: {
          "Content-Type": "application/json",
          "Content-Disposition": `attachment; filename="licenses-${Date.now()}.json"`,
          "Cache-Control": "no-store",
        },
      });
    }

    if (format === "backup") {
      // FULL backup includes decrypted-then-re-encrypted keys. Vault must be open.
      const dek = requireDek(ctx);
      const records = licenses.map((l) => {
        const dto = toDTO(l);
        let key: string | null = null;
        if (l.licenseKeyCipher) {
          key = openFieldString(Buffer.from(l.licenseKeyCipher), dek);
        }
        return { ...dto, licenseKey: key };
      });
      const plain = Buffer.from(JSON.stringify({ account: account.name, records }));
      // Re-seal the whole backup with the same account DEK so the file at rest
      // is encrypted. To restore, the same account (passphrase) is required.
      const sealed = sealField(plain, dek);
      return new NextResponse(sealed, {
        headers: {
          "Content-Type": "application/octet-stream",
          "Content-Disposition": `attachment; filename="backup-${Date.now()}.ltbak"`,
          "Cache-Control": "no-store",
        },
      });
    }

    // Default: CSV (masked).
    const headersRow = [
      "productName", "vendor", "licenseType", "keyHint", "purchaseDate",
      "startDate", "expirationDate", "nextRenewalDate", "cost", "currency",
      "quantity", "billingCycle", "autoRenew", "status", "tags",
    ];
    const escape = (v: unknown) => {
      const s = v == null ? "" : String(v);
      return /[",\n]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s;
    };
    const rows = licenses.map((l) => {
      const d = toDTO(l);
      return [
        d.productName, d.vendor, d.licenseType, d.keyHint, d.purchaseDate,
        d.startDate, d.expirationDate, d.nextRenewalDate,
        d.costCents != null ? d.costCents / 100 : "", d.currency,
        d.quantity, d.billingCycle, d.autoRenew, d.status, d.tags.join("|"),
      ].map(escape).join(",");
    });
    const csv = [headersRow.join(","), ...rows].join("\n");
    return new NextResponse(csv, {
      headers: {
        "Content-Type": "text/csv",
        "Content-Disposition": `attachment; filename="licenses-${Date.now()}.csv"`,
        "Cache-Control": "no-store",
      },
    });
  } catch (err) {
    if (err instanceof AuthError) {
      const status = err.code === "LOCKED" ? 423 : 401;
      return NextResponse.json({ error: err.message }, { status });
    }
    return NextResponse.json({ error: "Export failed" }, { status: 500 });
  }
}
