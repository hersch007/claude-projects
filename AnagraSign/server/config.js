import 'dotenv/config';
import path from 'node:path';
import fs from 'node:fs';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
export const rootDir = path.resolve(__dirname, '..');
const storageDir = path.join(rootDir, 'storage');

const bool = (v, d = false) => (v == null || v === '' ? d : ['1', 'true', 'yes'].includes(String(v).toLowerCase()));

export const config = {
  port: Number(process.env.PORT || 3900),
  baseUrl: (process.env.BASE_URL || `http://localhost:${process.env.PORT || 3900}`).replace(/\/$/, ''),
  adminPassword: process.env.ADMIN_PASSWORD || '',
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
  if (problems.length) {
    console.error('Configuration error:\n  - ' + problems.join('\n  - ') + '\nCopy .env.example to .env and fill in the values.');
    process.exit(1);
  }
}
