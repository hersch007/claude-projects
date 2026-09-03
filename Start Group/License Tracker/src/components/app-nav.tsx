"use client";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { useTheme } from "next-themes";
import {
  ShieldCheck, LayoutDashboard, KeySquare, Settings, Lock, LogOut, Moon, Sun,
} from "lucide-react";
import type { Plan } from "@prisma/client";
import { signOut } from "@/lib/auth-client";
import { lockVault } from "@/app/actions/vault";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { cn } from "@/lib/utils";

const NAV = [
  { href: "/dashboard", label: "Dashboard", icon: LayoutDashboard },
  { href: "/licenses", label: "Licenses", icon: KeySquare },
  { href: "/settings", label: "Settings", icon: Settings },
];

export function AppNav({
  orgName, plan, userEmail,
}: {
  orgName: string;
  plan: Plan;
  userEmail: string;
}) {
  const pathname = usePathname();
  const router = useRouter();
  const { theme, setTheme } = useTheme();

  async function handleLock() {
    await lockVault();
    router.refresh();
  }
  async function handleSignOut() {
    await lockVault(); // wipe the in-memory key on the way out
    await signOut();
    router.push("/login");
    router.refresh();
  }

  return (
    <header className="border-b bg-card">
      <div className="container flex h-16 items-center justify-between gap-4">
        <div className="flex items-center gap-6">
          <Link href="/dashboard" className="flex items-center gap-2 font-semibold">
            <ShieldCheck className="size-5 text-primary" />
            <span className="hidden sm:inline">{orgName}</span>
          </Link>
          <nav className="flex items-center gap-1">
            {NAV.map((item) => {
              const active = pathname.startsWith(item.href);
              return (
                <Link
                  key={item.href}
                  href={item.href}
                  className={cn(
                    "flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors",
                    active ? "bg-secondary text-secondary-foreground" : "text-muted-foreground hover:text-foreground"
                  )}
                >
                  <item.icon className="size-4" />
                  <span className="hidden md:inline">{item.label}</span>
                </Link>
              );
            })}
          </nav>
        </div>

        <div className="flex items-center gap-2">
          <Badge variant="outline" className="hidden sm:inline-flex">{plan}</Badge>
          <Button variant="ghost" size="icon" onClick={() => setTheme(theme === "dark" ? "light" : "dark")} title="Toggle theme">
            <Sun className="size-4 dark:hidden" />
            <Moon className="hidden size-4 dark:block" />
          </Button>
          <Button variant="ghost" size="icon" onClick={handleLock} title="Lock vault">
            <Lock className="size-4" />
          </Button>
          <Button variant="ghost" size="icon" onClick={handleSignOut} title={`Sign out (${userEmail})`}>
            <LogOut className="size-4" />
          </Button>
        </div>
      </div>
    </header>
  );
}
