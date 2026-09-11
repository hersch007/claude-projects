# Deploying AnagraSign to SiteGround

This reflects what was actually verified live against `sign.grouprb.com` on Sep 10–11 2026, not
the original guess (an earlier draft of this doc assumed SSH/git deploys — that was wrong and has
been replaced).

grouprb.com and partsofpractice.com are on the **same** SiteGround account (GoGeek plan), each as
its own separate **Node.js Project** — a distinct resource type from a regular WordPress site, with
its own Site Tools context and a SiteGround-assigned temporary hostname (e.g.
`richardb918.sg-host.com`) until you park a real domain onto it.

## 1. Create the Node.js Project

Site Tools > **Websites** has a **Node.js Projects** tab alongside the regular Websites list (or go
to `my.siteground.com/websites/nodejs`). **Create Node.js Project Now** opens a wizard:

- **Import Git repository** vs **Upload your files** — use **Upload your files** unless the app
  lives in its own dedicated GitHub repo (it doesn't here; it's a folder inside a much larger
  personal monorepo, and connecting that whole repo would be a mistake).
- Upload a `.tar.gz`/`.zip`/`.tgz` of the app (see step 2 for what to include).
- **Framework preset**: Express (auto-detected). **Node version**: highest available (24 as of
  this writing; needs 22.5+ for `node:sqlite`).
- **Build command**: change the default `npm run build` to `npm install` — there's no build step,
  and the default would fail deployment.

This creates the project and gives you **Site Tools > Node.js > Deployment Options**, which is
where you'll come back for every future update.

## 2. Package the app

No SSH, no git push. Every deploy is a fresh archive upload. From the `AnagraSign` folder:

```bash
tar -czf anagrasign-deploy.tar.gz \
  --exclude=node_modules --exclude=storage --exclude=.env --exclude=deploy \
  public server scripts docs .gitignore package.json package-lock.json README.md CLAUDE.md .env.example
```

(In practice this session builds it via a staging copy so `storage/` still ships with empty
`.gitkeep` placeholders — see any recent transcript for the exact commands. Either way: no
`node_modules`, no real `storage/` contents, no `.env`, no `deploy/`.)

## 3. Environment variables

Site Tools > **Node.js > Deployment Options**, scroll to **Environment Variables**. Added one at a
time through a Key/Value form + Create button — there's no bulk paste. **Two UI gotchas found the
hard way:**

- After a successful add, the form resets to empty inputs but the page keeps showing "Variable
  successfully added" with a **Back** button. Click **Back** and take a screenshot before typing
  the next one — reusing element references across adds can silently concatenate the previous
  key/value into the new one (hit this twice while adding `SMTP_SECURE`).
- Adding or editing variables does **not** take effect until the app is redeployed (see step 5) —
  it only updates what the *next* deploy will use.

Set every line from the matching file in `AnagraSign/deploy/` (`grouprb.env` or
`partsofpractice.env`) except `PORT` (the platform manages it) — **and see the `STORAGE_DIR`
section below, which is not optional.**

**SMTP:** if the domain's mail runs on Google Workspace (true for grouprb.com), don't bother with
a SiteGround mailbox — use Gmail's SMTP directly: `SMTP_HOST=smtp.gmail.com`, `SMTP_PORT=587`,
`SMTP_SECURE=0`, `SMTP_USER` = an existing Workspace mailbox, `SMTP_PASS` = an **app password**
for that mailbox (Google Account > Security > App Passwords, requires 2-Step Verification on that
account). `MAIL_FROM` must use that same mailbox's address — Gmail won't send as a different
address without a configured Workspace alias.

## 4. `STORAGE_DIR` is mandatory — this is the important part

**SiteGround extracts every "Save and Deploy" into a brand-new, timestamped folder
(`public_html/.nodeapp/<timestamp>-<hash>/app_source`) and discards the old one.** Confirmed by
writing a probe file, redeploying with no other changes, and reading it back: anything under the
app's own root — including the default `storage/` folder — is gone after every single redeploy.
That means the database and every uploaded or signed PDF, unless you fix this.

The fix: set `STORAGE_DIR` to an absolute path **outside** that versioned folder. Verified
persistent locations (found by testing 1 through 5 directories up from the app root and redeploying
in between): anywhere from the `.nodeapp` container folder up through the site's home directory.
Used in practice: one level above `public_html` — outside the web root entirely, so it's not even
reachable by URL:

```
STORAGE_DIR=/home/customer/www/<yoursite>.sg-host.com/anagrasign-storage
```

Find your own exact path by hitting a throwaway diagnostic route once (`process.cwd()` from inside
the running app) rather than assuming this one — the customer/site segment will differ per account.
**Do not skip this.** Without it, the app looks like it works right up until the next code update,
which silently deletes every real contract on the site.

## 5. Deploying (every time, including the first)

Site Tools > Node.js > Deployment Options > **Save and Deploy** → this saves your settings/env
vars and drops you into the same upload wizard as project creation. Upload the archive from step 2
again (yes, even if only env vars changed, not the code — the archive re-upload is what actually
triggers the new deploy) → **Continue** → **Deployed!**

There is no separate "restart" action; re-uploading and continuing through the wizard **is** the
restart.

## 6. Pointing your real domain at it

The project starts on a SiteGround-assigned hostname. To use your real subdomain:

1. **Don't** create the subdomain under the domain's own site first — that assigns it to that
   site's regular hosting and a later "park" attempt from the Node project's side will fail with
   an unhelpful untranslated error (`translate.core.form.new_domain.existing_web_app`).
2. Instead, go straight to the **Node.js Project's own** Site Tools > **Domain > Parked Domains**
   and add the subdomain there directly (e.g. `sign.grouprb.com`). SiteGround creates it cleanly
   since it's implicitly already on the account's own nameservers.
3. New parked domains have no SSL by default. Site Tools > **Security > SSL Manager**, select the
   new domain, install **Let's Encrypt** (free). Without this you'll get a browser privacy error,
   not a connection failure — easy to mistake for DNS not having propagated yet.

## 7. A platform routing quirk that broke real links once

SiteGround's edge only reliably proxies `/api/*` requests and literal static files to the Node
process. A dynamic path segment that isn't a real file on disk — the app used to generate signing
links as `/sign/<token>` — gets intercepted and 404'd **before it ever reaches the app**, even
though the exact same route works perfectly in local dev. The fix already shipped: signing links
are `/sign.html?t=<token>` (a real static file plus a query string) instead. If you ever add another
page that needs a dynamic path segment, either give it a real static entry point the same way, or
put its logic under `/api/*`.

## After it's live

- `storage/` (at whatever `STORAGE_DIR` points to) holds the database and every signed contract.
  Back it up on a schedule.
- Change `ADMIN_PASSWORD` yourself if you'd rather pick your own than keep a generated one.
- The two site instances share nothing — same SiteGround account, but fully independent apps,
  databases, and domains.
