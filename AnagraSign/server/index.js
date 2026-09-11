import crypto from 'node:crypto';
import path from 'node:path';
import express from 'express';
import { config, rootDir, assertConfig } from './config.js';
import { seedAuthFromEnv } from './auth.js';
import { authRouter } from './routes/auth.js';
import { envelopesRouter } from './routes/envelopes.js';
import { signRouter } from './routes/sign.js';
import { mailEnabled } from './services/mail.js';

assertConfig();
seedAuthFromEnv();

const app = express();
app.disable('x-powered-by');
app.set('trust proxy', config.trustProxy);
app.use(express.json({ limit: '30mb' }));

// Security headers (basic)
app.use((req, res, next) => {
  res.setHeader('X-Content-Type-Options', 'nosniff');
  res.setHeader('X-Frame-Options', 'SAMEORIGIN');
  res.setHeader('Referrer-Policy', 'same-origin');
  next();
});

// Optional HTTP-level password wall in front of the admin area (defense in depth on top of
// ADMIN_PASSWORD). Signer-facing pages and shared assets stay open, since signers never have
// this credential — only the emailed link (plus optional per-signer verification) protects them.
if (config.basicAuth.enabled) {
  const OPEN_PREFIXES = ['/sign.html', '/api/sign/', '/css/', '/js/', '/vendor/', '/favicon'];
  const safeEqual = (a, b) => {
    const bufA = Buffer.from(String(a));
    const bufB = Buffer.from(String(b));
    return bufA.length === bufB.length && crypto.timingSafeEqual(bufA, bufB);
  };
  app.use((req, res, next) => {
    if (OPEN_PREFIXES.some((p) => req.path.startsWith(p))) return next();
    const [scheme, encoded] = String(req.headers.authorization || '').split(' ');
    if (scheme === 'Basic' && encoded) {
      const [user, pass] = Buffer.from(encoded, 'base64').toString('utf8').split(':');
      if (safeEqual(user || '', config.basicAuth.user) && safeEqual(pass || '', config.basicAuth.pass)) {
        return next();
      }
    }
    res.set('WWW-Authenticate', 'Basic realm="AnagraSign"');
    res.status(401).send('Authentication required.');
  });
}

// API
app.use('/api/auth', authRouter);
app.use('/api/envelopes', envelopesRouter);
app.use('/api/sign', signRouter);

// Vendor libraries served straight from node_modules (no build step)
app.use('/vendor/pdfjs', express.static(path.join(rootDir, 'node_modules/pdfjs-dist/build')));
app.use('/vendor/signature_pad', express.static(path.join(rootDir, 'node_modules/signature_pad/dist')));

// Front-end. Signer links use /sign.html?t=<token> (a real static file, not a dynamic path
// segment) — see the comment on signingLink() in services/workflow.js for why that matters.
const pub = path.join(rootDir, 'public');
app.use(express.static(pub, { extensions: ['html'] }));

// 404 for unknown API routes
app.use('/api', (req, res) => res.status(404).json({ error: 'Not found' }));

// Error handler
// eslint-disable-next-line no-unused-vars
app.use((err, req, res, next) => {
  const status = err.status || (err.code === 'LIMIT_FILE_SIZE' ? 413 : 500);
  const message = err.code === 'LIMIT_FILE_SIZE' ? `File is too large (max ${Math.round(config.maxUploadBytes / 1048576)} MB)` : err.message || 'Server error';
  if (status >= 500) console.error(err);
  res.status(status).json({ error: message });
});

app.listen(config.port, () => {
  console.log(`AnagraSign running at ${config.baseUrl}  (port ${config.port})`);
  console.log(`Mail: ${mailEnabled ? `SMTP ${config.smtp.host}` : 'not configured - emails are written to storage/outbox'}`);
});
