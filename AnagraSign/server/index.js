import path from 'node:path';
import express from 'express';
import { config, rootDir, assertConfig } from './config.js';
import { authRouter } from './routes/auth.js';
import { envelopesRouter } from './routes/envelopes.js';
import { signRouter } from './routes/sign.js';
import { mailEnabled } from './services/mail.js';

assertConfig();

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

// API
app.use('/api/auth', authRouter);
app.use('/api/envelopes', envelopesRouter);
app.use('/api/sign', signRouter);

// Vendor libraries served straight from node_modules (no build step)
app.use('/vendor/pdfjs', express.static(path.join(rootDir, 'node_modules/pdfjs-dist/build')));
app.use('/vendor/signature_pad', express.static(path.join(rootDir, 'node_modules/signature_pad/dist')));

// Front-end
const pub = path.join(rootDir, 'public');
app.use(express.static(pub, { extensions: ['html'] }));
app.get('/sign/:token', (req, res) => res.sendFile(path.join(pub, 'sign.html')));

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
