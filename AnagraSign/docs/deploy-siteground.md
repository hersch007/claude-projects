# Deploying AnagraSign to SiteGround (grouprb.com + partsofpractice.com)

Two separate instances, one per SiteGround account, each fully independent (own database, own
signers, own storage). SiteGround uses **Site Tools**, not cPanel, and its Node.js hosting runs on
Phusion Passenger. `server/index.js` already listens on `process.env.PORT`, which is exactly what
Passenger requires, so no code changes are needed to deploy it.

Repeat every step below **twice** — once per SiteGround account. The two accounts are unrelated to
each other; nothing is shared.

## 0. Confirm the plan supports it

In Site Tools, look for **Devs** in the left menu. You need both **Node.js** and **SSH Keys
Manager** to appear there. Both require GrowBig or GoGeek — you've confirmed both accounts are on
one of those.

## 1. Create the Node.js application

Site Tools > **Devs > Node.js** > **Create New Application**.

| Field | Value |
|---|---|
| Node.js version | The highest available (need 22.5 or newer — AnagraSign uses `node:sqlite`). If the dropdown tops out below 22.5, stop and tell me; we'd need a fallback database driver. |
| Environment | Production |
| Application root | e.g. `nodeapps/sign` (SiteGround puts this under your account's home directory, separate from `public_html`) |
| Application URL | `sign.grouprb.com` (or `sign.partsofpractice.com`) — if the subdomain doesn't exist yet, SiteGround lets you create it right here |
| Application startup file | `server/index.js` |

Click **Create**. SiteGround scaffolds the folder and gives you a screen with **Run NPM Install**,
an **Environment Variables** section, and Start/Stop/Restart controls. Leave it here for now.

## 2. Set environment variables

In that same app's **Environment Variables** section, add every line from the matching file in
`AnagraSign/deploy/` on your machine (`grouprb.env` or `partsofpractice.env`) as its own KEY=VALUE
entry. Those files already have `SESSION_SECRET` and `ADMIN_PASSWORD` generated for you — do not
reuse the ones from your local `.env`.

Before adding them, fill in the two `TODO` lines in that file:

1. Site Tools > **Email Accounts** > create a mailbox (suggested: `sign@grouprb.com` or
   `sign@partsofpractice.com`).
2. Put that mailbox's password into `SMTP_PASS`, and confirm `SMTP_HOST` matches what SiteGround
   shows for that mailbox (usually `mail.<yourdomain>`).

Without SMTP filled in, the app still works — signing emails just get written to
`storage/outbox/` on the server instead of sent, which is fine for testing but not for real use.

## 3. Get SSH access

Site Tools > **Devs > SSH Keys Manager**. Two options:

- **Reuse your existing key** — upload the public half of the key you already use for other
  deploys (`C:\Users\richa\.ssh\id_ed25519.pub`). Paste its contents into "Import Key".
- **Generate a new key** there and download the private half.

Either way, note the **connection details** SiteGround shows (hostname, port, username) — they're
specific to each account and usually look like `ssh username@yourdomain.com -p <port>`. You need
this for step 5.

## 4. First upload

The very first deploy, do it manually so you can watch for errors:

1. SFTP or SSH into the account (FileZilla, WinSCP, or the `scp` commands in step 5 below) and
   upload the entire `AnagraSign` folder **except** `node_modules/`, `storage/`, `.env`, and
   `deploy/` into the Application root you created in step 1.
2. Back in Site Tools > Devs > Node.js, click **Run NPM Install**. Wait for it to finish — this
   installs `express`, `pdf-lib`, `nodemailer`, etc. on the server.
3. Click **Restart**.
4. Visit `https://sign.grouprb.com` (or the partsofpractice URL). You should see the AnagraSign
   login page. Log in with the `ADMIN_PASSWORD` from that site's env file.
5. Upload the sample PDF from `docs/sample-agreement.pdf`, add yourself as a signer, send it, and
   confirm you receive the email (or find it in `storage/outbox/` via File Manager if SMTP isn't
   set up yet).

If Node.js version was capped below 22.5 and the app fails on startup mentioning `node:sqlite`,
tell me — we'll swap the database layer rather than fight the platform.

## 5. Later deploys (script)

Once SSH is working, use `scripts/deploy-siteground.ps1` from your machine instead of repeating
step 4 by hand. Fill in the connection details it asks for (or edit the placeholders at the top of
the script once and keep them there). It:

1. Copies the changed files up via `scp` (skips `node_modules`, `storage`, `.env`, `deploy`).
2. Runs `npm install --omit=dev` over SSH.
3. Touches `tmp/restart.txt` in the app root, which tells Passenger to reload the app on the next
   request — no manual "Restart" click needed.

```powershell
./scripts/deploy-siteground.ps1 -Site grouprb
./scripts/deploy-siteground.ps1 -Site partsofpractice
```

## 6. After both are live

- Back up `storage/anagrasign.db` on each server on a schedule — it's the only copy of every
  contract's signatures and audit trail. SiteGround's own backup tool (Site Tools > Security >
  Backups) covers this automatically if it includes the Node app's directory; confirm it does.
- Change `ADMIN_PASSWORD` again yourself once you've logged in, if you'd rather pick your own than
  keep the generated one.
- The two instances do not talk to each other or share anything. If you ever want one
  password/login for both, that's a bigger change — ask if you want it.

## Known unknowns

I don't have SiteGround login or SSH access, so none of the panel steps above have been tested
against the live UI — field names may differ slightly from what's described. The Passenger
behavior (listens on `process.env.PORT`, reloads on `tmp/restart.txt`) is standard across every
Phusion Passenger host (this is the same mechanism cPanel's "Setup Node.js App" uses), so that part
is solid; the Site Tools screens are the part worth double-checking as you go.
