"use client";
import { useEffect, useRef, useState } from "react";
import { Eye, EyeOff, Copy, Check } from "lucide-react";
import { Button } from "@/components/ui/button";

/**
 * Masked license key with a temporary reveal.
 *
 * SECURITY:
 *  - The plaintext is fetched on demand from the rate-limited /reveal endpoint
 *    and is NEVER part of the initial page payload.
 *  - It auto-hides after a timeout and is cleared from component state.
 *  - The fetch is no-store; we also avoid logging the value.
 */
const AUTO_HIDE_MS = 20_000;

export function RevealKey({
  licenseId,
  hint,
  hasKey,
}: {
  licenseId: string;
  hint: string | null;
  hasKey: boolean;
}) {
  const [value, setValue] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const [copied, setCopied] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const timer = useRef<ReturnType<typeof setTimeout> | null>(null);

  useEffect(() => () => { if (timer.current) clearTimeout(timer.current); }, []);

  function hide() {
    setValue(null);
    setCopied(false);
    if (timer.current) clearTimeout(timer.current);
  }

  async function reveal() {
    setError(null);
    setLoading(true);
    try {
      const res = await fetch(`/api/licenses/${licenseId}/reveal`, {
        method: "POST",
        cache: "no-store",
      });
      if (res.status === 423) { setError("Vault locked"); return; }
      if (res.status === 429) { setError("Slow down"); return; }
      if (!res.ok) { setError("Failed"); return; }
      const data = (await res.json()) as { key: string };
      setValue(data.key);
      timer.current = setTimeout(hide, AUTO_HIDE_MS); // auto-hide
    } finally {
      setLoading(false);
    }
  }

  async function copy() {
    if (!value) return;
    await navigator.clipboard.writeText(value);
    setCopied(true);
    setTimeout(() => setCopied(false), 1500);
  }

  if (!hasKey) return <span className="text-sm text-muted-foreground">—</span>;

  return (
    <div className="flex items-center gap-2">
      <code className="rounded bg-muted px-2 py-1 font-mono text-xs">
        {value ?? hint ?? "••••••••••"}
      </code>
      {value ? (
        <>
          <Button size="icon" variant="ghost" className="size-7" onClick={copy} title="Copy">
            {copied ? <Check className="size-3.5 text-success" /> : <Copy className="size-3.5" />}
          </Button>
          <Button size="icon" variant="ghost" className="size-7" onClick={hide} title="Hide">
            <EyeOff className="size-3.5" />
          </Button>
        </>
      ) : (
        <Button size="icon" variant="ghost" className="size-7" onClick={reveal} disabled={loading} title="Reveal">
          <Eye className="size-3.5" />
        </Button>
      )}
      {error && <span className="text-xs text-destructive">{error}</span>}
    </div>
  );
}
