"use client";
import { createAuthClient } from "better-auth/react";

/**
 * Browser-side better-auth client. Used for sign-in / sign-out from client
 * components. Sign-UP goes through our server action (src/app/actions/auth.ts)
 * so we can create the tenant Account atomically.
 */
export const authClient = createAuthClient({
  // Same-origin; baseURL inferred from window in the browser.
});

export const { signIn, signOut, useSession } = authClient;
