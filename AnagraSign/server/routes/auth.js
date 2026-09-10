import express from 'express';
import { checkPassword, makeSessionToken, setSessionCookie, clearSessionCookie, isAdmin } from '../auth.js';

export const authRouter = express.Router();

const attempts = new Map(); // ip -> { count, until }

authRouter.post('/login', (req, res) => {
  const key = req.ip;
  const a = attempts.get(key) || { count: 0, until: 0 };
  if (a.until > Date.now()) return res.status(429).json({ error: 'Too many attempts. Try again in a minute.' });
  if (!checkPassword(req.body?.password)) {
    a.count += 1;
    if (a.count >= 5) { a.until = Date.now() + 60_000; a.count = 0; }
    attempts.set(key, a);
    return res.status(401).json({ error: 'Incorrect password' });
  }
  attempts.delete(key);
  setSessionCookie(res, makeSessionToken());
  res.json({ ok: true });
});

authRouter.post('/logout', (req, res) => {
  clearSessionCookie(res);
  res.json({ ok: true });
});

authRouter.get('/me', (req, res) => {
  res.json({ admin: isAdmin(req) });
});
