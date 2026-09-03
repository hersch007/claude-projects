/**
 * Development seed.
 *
 * Creates a demo tenant with a known master passphrase and a couple of
 * encrypted licenses so you can explore the app immediately.
 *
 *   Login:        demo@example.com / demo-password-123
 *   Passphrase:   correct horse battery staple
 *
 * SECURITY: seeds are for DEV ONLY. Never run this against production.
 */
import { PrismaClient } from "@prisma/client";
import { initializeAccountKeys, sealField, unlockDek } from "../src/lib/crypto/encryption";

const db = new PrismaClient();

async function main() {
  if (process.env.NODE_ENV === "production") {
    throw new Error("Refusing to seed in production.");
  }

  const PASSPHRASE = "correct horse battery staple";
  const keys = await initializeAccountKeys(PASSPHRASE);

  const account = await db.account.create({
    data: {
      name: "Demo Co",
      plan: "PRO",
      seatLimit: 5,
      passphraseSet: true,
      masterPassphraseHash: keys.masterPassphraseHash,
      kdfSalt: keys.kdfSalt,
      dekWrapped: keys.dekWrapped,
      dekWrapIv: keys.dekWrapIv,
      dekWrapTag: keys.dekWrapTag,
    },
  });

  // NOTE: This seed creates the User row directly without a better-auth password
  // record, so use the signup flow for a fully working login. It exists mainly to
  // demonstrate encrypted license rows.
  const user = await db.user.create({
    data: {
      accountId: account.id,
      email: "demo@example.com",
      name: "Demo Admin",
      role: "ADMIN",
      emailVerified: true,
    },
  });

  // Unwrap the DEK to encrypt sample keys exactly as the app would.
  const dek = await unlockDek(PASSPHRASE, {
    masterPassphraseHash: keys.masterPassphraseHash,
    kdfSalt: keys.kdfSalt,
    dekWrapped: keys.dekWrapped,
    dekWrapIv: keys.dekWrapIv,
    dekWrapTag: keys.dekWrapTag,
  });
  if (!dek) throw new Error("seed: failed to unlock DEK");

  const soon = new Date(Date.now() + 20 * 86_400_000);
  const later = new Date(Date.now() + 200 * 86_400_000);

  await db.license.createMany({
    data: [
      {
        accountId: account.id,
        createdById: user.id,
        productName: "Adobe Creative Cloud",
        vendor: "Adobe",
        licenseType: "SUBSCRIPTION",
        licenseKeyCipher: sealField("ADOBE-XXXX-YYYY-1234", dek),
        licenseKeyHint: "••••1234",
        costCents: 5999,
        currency: "USD",
        billingCycle: "MONTHLY",
        expirationDate: soon,
        nextRenewalDate: soon,
        autoRenew: true,
        tags: ["design", "team"],
      },
      {
        accountId: account.id,
        createdById: user.id,
        productName: "JetBrains All Products",
        vendor: "JetBrains",
        licenseType: "SUBSCRIPTION",
        licenseKeyCipher: sealField("JB-ABCD-EFGH-5678", dek),
        licenseKeyHint: "••••5678",
        costCents: 28900,
        currency: "USD",
        billingCycle: "ANNUAL",
        expirationDate: later,
        nextRenewalDate: later,
        tags: ["dev"],
      },
    ],
  });

  console.log("✅ Seeded demo account:", account.name);
  console.log("   Passphrase:", PASSPHRASE);
}

main()
  .catch((e) => {
    console.error(e);
    process.exit(1);
  })
  .finally(() => db.$disconnect());
