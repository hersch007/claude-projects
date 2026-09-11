import express from 'express';
import {
  ROLES, checkRolePassword, roleEnabled, setRolePassword,
  makeSessionToken, setSessionCookie, clearSessionCookie, getRole, requireAuth,
} from '../auth.js';

export const authRouter = express.Router();

const attempts = new Map(); // ip -> { count, until }

authRouter.post('/login', (req, res) => {
  const role = String(req.body?.role || 'admin');
  const password = req.body?.password;
  if (!ROLES.includes(role)) return res.status(400).json({ error: 'Unknown role' });

  const key = `${req.ip}:${role}`;
  const a = attempts.get(key) || { count: 0, until: 0 };
  if (a.until > Date.now()) return res.status(429).json({ error: 'Too many attempts. Try again in a minute.' });

  if (!roleEnabled(role)) {
    return res.status(401).json({ error: role === 'user' ? 'User login has not been set up yet. Ask an admin to set a user password.' : 'Login is not set up yet.' });
  }
  if (!checkRolePassword(role, password)) {
    a.count += 1;
    if (a.count >= 5) { a.until = Date.now() + 60_000; a.count = 0; }
    attempts.set(key, a);
    return res.status(401).json({ error: 'Incorrect password' });
  }
  attempts.delete(key);
  setSessionCookie(res, makeSessionToken(role));
  res.json({ ok: true, role });
});

authRouter.post('/logout', (req, res) => {
  clearSessionCookie(res);
  res.json({ ok: true });
});

authRouter.get('/me', (req, res) => {
  res.json({ role: getRole(req), userEnabled: roleEnabled('user') });
});

// Changing a password requires being signed in as SOME role. Rules:
//  - You can always change your OWN role's password, but must supply its current value.
//  - Only an admin can change a DIFFERENT role's password (a reset — no current value needed),
//    which is how the "user" role gets created/enabled in the first place.
authRouter.post('/change-password', requireAuth, (req, res) => {
  const targetRole = String(req.body?.target_role || req.role);
  const newPassword = String(req.body?.new_password || '');
  const currentPassword = req.body?.current_password;

  if (!ROLES.includes(targetRole)) return res.status(400).json({ error: 'Unknown role' });
  if (newPassword.length < 8) return res.status(400).json({ error: 'New password must be at least 8 characters' });

  if (targetRole === req.role) {
    if (!checkRolePassword(targetRole, currentPassword)) {
      return res.status(401).json({ error: 'Current password is incorrect' });
    }
  } else if (req.role !== 'admin') {
    return res.status(403).json({ error: 'Only an admin can change another role’s password' });
  }

  setRolePassword(targetRole, newPassword);
  console.log(`[auth] ${targetRole} password changed by ${req.role} session`);
  res.json({ ok: true });
});
