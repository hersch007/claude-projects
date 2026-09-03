# Auto-deploy the pages (optional, saves the most time)

This script creates all 6 WordPress pages for you via the REST API, so you don't
hand-paste them. **Your password stays on your computer** - it goes only in
`wp.config.json`, which you never share.

## 1. Make a fresh Application Password
In WordPress: **Users -> Profile -> Application Passwords** ->
name it (e.g. `deploy`) -> **Add** -> copy the password it shows.
*(If you shared one earlier, revoke that old one here first.)*

## 2. Create your config file
- Copy `wp.config.example.json` to a new file named **`wp.config.json`** (same folder).
- Fill it in:
  - `username` = your WordPress **login username** (not the app-password name)
  - `appPassword` = the password from step 1 (spaces are fine)
  - `status` = `draft` (recommended - you review before publishing) or `publish`

## 3. Run it
Open a terminal in this folder and run:
```
node deploy.js
```
You'll see each page created. It will also set your homepage to "Home".

## 4. Finish in WP admin (the parts the API can't do)
- Upload the **`rh-assets`** folder to your web root (`public_html/rh-assets/`).
- Add the two **WPCode** snippets (`00-HEAD-CODE.html`, `00-FOOTER-CODE.html`).
- Add the **header/footer** GeneratePress Elements (`header.html`, `footer.html`).
- Set each new page to **Full-Width + hide title**, then **Publish**.
- (See `BUILD-GUIDE.md` for the click-by-click on these.)

## Notes
- The script only **creates/updates** those 6 pages and sets the homepage. It never deletes anything.
- Re-running it updates the same pages (matched by slug) instead of making duplicates.
- Requires Node.js (you have it) and that your site's REST API (`/wp-json`) is reachable.
- **Never commit or share `wp.config.json`.** Keep your application password private; revoke any that leak.
