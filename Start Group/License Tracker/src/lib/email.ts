import "server-only";
import { env } from "@/env";

/**
 * Email placeholder (Resend-ready).
 *
 * In development (or whenever RESEND_API_KEY is unset) emails are logged to the
 * console instead of being sent — so you can complete signup/verification flows
 * locally without an email provider. Swap the `else` branch for a real Resend
 * call in production.
 *
 * SECURITY: never log full verification/reset URLs in production; here we only
 * do so in dev for convenience.
 */
export interface SendEmailInput {
  to: string;
  subject: string;
  html: string;
  text?: string;
}

export async function sendEmail(input: SendEmailInput): Promise<void> {
  if (!env.RESEND_API_KEY) {
    console.info("📧 [email:dev] (not sent — RESEND_API_KEY unset)", {
      to: input.to,
      subject: input.subject,
      preview: input.text ?? input.html.replace(/<[^>]+>/g, "").slice(0, 300),
    });
    return;
  }

  // Production path (uncomment once `resend` is installed):
  //   import { Resend } from "resend";
  //   const resend = new Resend(env.RESEND_API_KEY);
  //   await resend.emails.send({ from: env.EMAIL_FROM!, ...input });
  const res = await fetch("https://api.resend.com/emails", {
    method: "POST",
    headers: {
      Authorization: `Bearer ${env.RESEND_API_KEY}`,
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      from: env.EMAIL_FROM ?? "License Tracker <noreply@example.com>",
      to: input.to,
      subject: input.subject,
      html: input.html,
      text: input.text,
    }),
  });
  if (!res.ok) {
    console.error("[email] send failed", res.status, await res.text());
  }
}
