import 'dotenv/config';
import path from 'node:path';
import fs from 'node:fs';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
export const rootDir = path.resolve(__dirname, '..');
// Some hosts (confirmed on SiteGround's Node.js Projects) extract every deploy into a fresh,
// timestamped directory and discard the old one — anything under rootDir is wiped on every
// redeploy. Set STORAGE_DIR to an absolute path OUTSIDE that versioned folder (e.g. the site's
// home directory, one level above public_html) so the database and every uploaded/signed PDF
// survive. Defaults to rootDir/storage, which is fine for local dev and any host that deploys
// in place rather than into a new folder each time.
const storageDir = process.env.STORAGE_DIR ? path.resolve(process.env.STORAGE_DIR) : path.join(rootDir, 'storage');

const bool = (v, d = false) => (v == null || v === '' ? d : ['1', 'true', 'yes'].includes(String(v).toLowerCase()));

export const config = {
  port: Number(process.env.PORT || 3900),
  baseUrl: (process.env.BASE_URL || `http://localhost:${process.env.PORT || 3900}`).replace(/\/$/, ''),
  adminPassword: process.env.ADMIN_PASSWORD || '',
  // Recovery escape hatch: if set truthy, the admin password is force-reset from ADMIN_PASSWORD
  // on every startup, even if it was already changed through the app. Remove this env var again
  // once you've logged back in — leaving it on means anyone who can edit env vars controls admin.
  resetAdminPassword: bool(process.env.RESET_ADMIN_PASSWORD),
  sessionSecret: process.env.SESSION_SECRET || '',
  sessionTtlMs: 12 * 60 * 60 * 1000,
  trustProxy: bool(process.env.TRUST_PROXY),
  secureCookies: bool(process.env.SECURE_COOKIES),
  mailFrom: process.env.MAIL_FROM || 'AnagraSign <no-reply@localhost>',
  notifyEmail: process.env.NOTIFY_EMAIL || '',
  smtp: {
    host: process.env.SMTP_HOST || '',
    port: Number(process.env.SMTP_PORT || 587),
    secure: bool(process.env.SMTP_SECURE),
    user: process.env.SMTP_USER || '',
    pass: process.env.SMTP_PASS || '',
  },
  basicAuth: {
    enabled: bool(process.env.BASIC_AUTH_ENABLED),
    user: process.env.BASIC_AUTH_USER || '',
    pass: process.env.BASIC_AUTH_PASS || '',
  },
  storageDir,
  uploadsDir: path.join(storageDir, 'uploads'),
  completedDir: path.join(storageDir, 'completed'),
  outboxDir: path.join(storageDir, 'outbox'),
  dbPath: process.env.DB_PATH || path.join(storageDir, 'anagrasign.db'),
  maxUploadBytes: 25 * 1024 * 1024,
  maxSignatureBytes: 600 * 1024,
};

for (const d of [config.uploadsDir, config.completedDir, config.outboxDir]) fs.mkdirSync(d, { recursive: true });

export function assertConfig() {
  const problems = [];
  if (!config.adminPassword) problems.push('ADMIN_PASSWORD is not set');
  if (!config.sessionSecret || config.sessionSecret.length < 16) problems.push('SESSION_SECRET must be at least 16 characters');
  if (config.basicAuth.enabled && (!config.basicAuth.user || !config.basicAuth.pass)) {
    problems.push('BASIC_AUTH_ENABLED is on but BASIC_AUTH_USER / BASIC_AUTH_PASS are not both set');
  }
  if (problems.length) {
    console.error('Configuration error:\n  - ' + problems.join('\n  - ') + '\nCopy .env.example to .env and fill in the values.');
    process.exit(1);
  }
}
