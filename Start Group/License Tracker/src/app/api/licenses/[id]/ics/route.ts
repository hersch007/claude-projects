import { NextResponse, type NextRequest } from "next/server";
import { getTenantContext, tenantDb, AuthError } from "@/lib/tenant";

/**
 * GET /api/licenses/:id/ics → a one-click .ics calendar reminder for the
 * renewal/expiration date. No secrets are included.
 */
export async function GET(
  _req: NextRequest,
  { params }: { params: Promise<{ id: string }> }
) {
  try {
    const ctx = await getTenantContext();
    const { id } = await params;
    const license = await tenantDb(ctx).license.findByIdActive(id);
    if (!license) return NextResponse.json({ error: "Not found" }, { status: 404 });

    const when = license.nextRenewalDate ?? license.expirationDate;
    if (!when) {
      return NextResponse.json({ error: "No renewal/expiration date" }, { status: 400 });
    }

    const dt = (d: Date) =>
      d.toISOString().replace(/[-:]/g, "").replace(/\.\d{3}/, "");
    const esc = (s: string) => s.replace(/([,;\\])/g, "\\$1").replace(/\n/g, "\\n");

    const ics = [
      "BEGIN:VCALENDAR",
      "VERSION:2.0",
      "PRODID:-//License Tracker//EN",
      "CALSCALE:GREGORIAN",
      "BEGIN:VEVENT",
      `UID:license-${license.id}@license-tracker`,
      `DTSTAMP:${dt(new Date())}`,
      `DTSTART:${dt(when)}`,
      `SUMMARY:${esc(`Renew: ${license.productName}`)}`,
      `DESCRIPTION:${esc(
        `License "${license.productName}"${license.vendor ? ` from ${license.vendor}` : ""} is due for renewal.`
      )}`,
      "BEGIN:VALARM",
      "TRIGGER:-P7D",
      "ACTION:DISPLAY",
      "DESCRIPTION:License renewal in 7 days",
      "END:VALARM",
      "END:VEVENT",
      "END:VCALENDAR",
    ].join("\r\n");

    return new NextResponse(ics, {
      headers: {
        "Content-Type": "text/calendar; charset=utf-8",
        "Content-Disposition": `attachment; filename="renewal-${license.id}.ics"`,
      },
    });
  } catch (err) {
    if (err instanceof AuthError) {
      return NextResponse.json({ error: err.message }, { status: 401 });
    }
    return NextResponse.json({ error: "Failed" }, { status: 500 });
  }
}
