import { auth } from "@/lib/auth";
import { toNextJsHandler } from "better-auth/next-js";

/**
 * Catch-all better-auth endpoint (login, signup, verify-email, reset, signout…).
 * better-auth handles CSRF, secure cookie issuance, and password hashing.
 */
export const { POST, GET } = toNextJsHandler(auth);
