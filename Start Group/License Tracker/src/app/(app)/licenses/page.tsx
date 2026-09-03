import { getTenantContext, tenantDb } from "@/lib/tenant";
import { toDTO } from "@/lib/license-service";
import { LicenseTable } from "@/components/licenses/license-table";
import { Button } from "@/components/ui/button";
import { Download } from "lucide-react";

export const metadata = { title: "Licenses" };

/**
 * Licenses index. Server component fetches the tenant-scoped, MASKED list and
 * hands it to the interactive client table. Plaintext keys are never included.
 */
export default async function LicensesPage({
  searchParams,
}: {
  searchParams: Promise<{ new?: string }>;
}) {
  const ctx = await getTenantContext();
  const sp = await searchParams;
  const licenses = await tenantDb(ctx).license.findManyActive({
    orderBy: { expirationDate: "asc" },
  });

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">Licenses</h1>
          <p className="text-muted-foreground">{licenses.length} tracked</p>
        </div>
        <div className="flex gap-2">
          <a href="/api/export?format=csv"><Button variant="outline"><Download className="size-4" /> CSV</Button></a>
          <a href="/api/export?format=json"><Button variant="outline"><Download className="size-4" /> JSON</Button></a>
        </div>
      </div>

      <LicenseTable licenses={licenses.map(toDTO)} autoOpenNew={sp.new === "1"} />
    </div>
  );
}
