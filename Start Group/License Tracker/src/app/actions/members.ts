"use server";

import { headers } from "next/headers";
import { revalidatePath } from "next/cache";
import { db } from "@/lib/db";
import { requireRole, AuthError } from "@/lib/tenant";
import { inviteSchema } from "@/lib/validations";
import { generateToken, sha256Hex } from "@/lib/crypto/encryption";
import { sendEmail } from "@/lib/email";
import { audit } from "@/lib/audit";
import { clientIp } from "@/lib/rate-limit";
import { env } from "@/env";

type ActionResult = { ok: true } | { ok: false; error: string };

/**
 * Invite a member. ADMIN-only. Enforces the plan seat limit by counting current
 * users + pending invites. The raw invite token is emailed but only its SHA-256
 * is stored, so a DB leak cannot be used to accept invites.
 */
export async function inviteMember(
  _prev: unknown,
  formData: FormData
): Promise<ActionResult> {
  const ctx = await requireRole("ADMIN");
  const parsed = inviteSchema.safeParse({
    email: formData.get("email"),
    role: formData.get("role") ?? "MEMBER",
  });
  if (!parsed.success) {
    return { ok: false, error: parsed.error.issues[0]?.message ?? "Invalid input" };
  }

  const account = await db.account.findUniqueOrThrow({ where: { id: ctx.accountId } });

  // Seat enforcement: active users + pending invites must stay within seatLimit.
  const [userCount, pendingInvites] = await Promise.all([
    db.user.count({ where: { accountId: ctx.accountId } }),
    db.invitation.count({ where: { accountId: ctx.accountId, status: "PENDING" } }),
  ]);
  if (userCount + pendingInvites >= account.seatLimit) {
    return {
      ok: false,
      error: `Seat limit reached (${account.seatLimit}). Upgrade your plan to add members.`,
    };
  }

  // Prevent duplicate invites / existing members.
  const existingUser = await db.user.findUnique({ where: { email: parsed.data.email } });
  if (existingUser) return { ok: false, error: "That email already has an account." };

  const rawToken = generateToken(32);
  const tokenHash = sha256Hex(rawToken);
  await db.invitation.create({
    data: {
      accountId: ctx.accountId,
      email: parsed.data.email,
      role: parsed.data.role,
      tokenHash,
      invitedById: ctx.userId,
      expiresAt: new Date(Date.now() + 7 * 86_400_000), // 7 days
    },
  });

  const acceptUrl = `${env.NEXT_PUBLIC_APP_URL}/invite/${rawToken}`;
  await sendEmail({
    to: parsed.data.email,
    subject: `You're invited to ${account.name} on License Tracker`,
    html: `<p>You've been invited to join <strong>${account.name}</strong>.</p>
           <p><a href="${acceptUrl}">Accept your invitation</a> (expires in 7 days).</p>`,
    text: `Accept your invitation: ${acceptUrl}`,
  });

  const h = await headers();
  await audit({
    accountId: ctx.accountId,
    userId: ctx.userId,
    action: "member.invite",
    metadata: { email: parsed.data.email, role: parsed.data.role },
    ipAddress: clientIp(h),
    userAgent: h.get("user-agent"),
  });

  revalidatePath("/settings");
  return { ok: true };
}

/** Remove a member (ADMIN-only). Cannot remove yourself or the last admin. */
export async function removeMember(userId: string): Promise<ActionResult> {
  try {
    const ctx = await requireRole("ADMIN");
    if (userId === ctx.userId) return { ok: false, error: "You can't remove yourself." };

    // Tenant-scoped lookup — ensures the target belongs to the caller's account.
    const target = await db.user.findFirst({
      where: { id: userId, accountId: ctx.accountId },
      select: { id: true, role: true, email: true },
    });
    if (!target) return { ok: false, error: "Member not found." };

    if (target.role === "ADMIN") {
      const adminCount = await db.user.count({
        where: { accountId: ctx.accountId, role: "ADMIN" },
      });
      if (adminCount <= 1) return { ok: false, error: "Can't remove the last admin." };
    }

    await db.user.delete({ where: { id: target.id } });

    const h = await headers();
    await audit({
      accountId: ctx.accountId,
      userId: ctx.userId,
      action: "member.remove",
      targetType: "User",
      targetId: userId,
      metadata: { email: target.email },
      ipAddress: clientIp(h),
      userAgent: h.get("user-agent"),
    });

    revalidatePath("/settings");
    return { ok: true };
  } catch (err) {
    if (err instanceof AuthError) return { ok: false, error: err.message };
    return { ok: false, error: "Failed to remove member." };
  }
}
