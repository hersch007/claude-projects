#!/usr/bin/env node
/* =========================================================
   Rock Hill Pickleball - WordPress page deployer
   Creates/updates the site's pages via the WordPress REST API.

   Your credentials are read from wp.config.json (which you create
   locally and NEVER share). This script only creates/updates the
   six pages listed below and optionally sets the homepage.
   It never deletes anything.

   Run:  node deploy.js
   ========================================================= */
const fs = require("fs");
const path = require("path");
const https = require("https");
const { URL } = require("url");

const cfgPath = path.join(__dirname, "wp.config.json");
if (!fs.existsSync(cfgPath)) {
  console.error("Missing wp.config.json.\nCopy wp.config.example.json to wp.config.json and fill in your details.");
  process.exit(1);
}
const cfg = JSON.parse(fs.readFileSync(cfgPath, "utf8"));
const siteUrl = String(cfg.siteUrl || "").replace(/\/+$/, "");
if (!siteUrl || !cfg.username || !cfg.appPassword) {
  console.error("wp.config.json needs siteUrl, username, and appPassword.");
  process.exit(1);
}
const auth = "Basic " + Buffer.from(cfg.username + ":" + String(cfg.appPassword).replace(/\s+/g, "")).toString("base64");
const status = cfg.status || "draft"; // "draft" (recommended) or "publish"

const PAGES = [
  { slug: "home",            title: "Home",            file: "content/home.html" },
  { slug: "courts",          title: "Courts",          file: "content/courts.html" },
  { slug: "find-players",    title: "Find Players",    file: "content/find-players.html" },
  { slug: "getting-started", title: "Getting Started", file: "content/getting-started.html" },
  { slug: "about",           title: "About",           file: "content/about.html" },
  { slug: "scheduler",       title: "Scheduler",       file: "content/scheduler.html" },
];

function api(method, endpoint, body) {
  return new Promise((resolve, reject) => {
    const u = new URL(siteUrl + "/wp-json" + endpoint);
    const data = body ? JSON.stringify(body) : null;
    const opts = {
      method,
      hostname: u.hostname,
      path: u.pathname + u.search,
      port: u.port || 443,
      headers: { "Authorization": auth, "Content-Type": "application/json", "Accept": "application/json" },
    };
    if (data) opts.headers["Content-Length"] = Buffer.byteLength(data);
    const req = https.request(opts, (res) => {
      let buf = "";
      res.on("data", (d) => (buf += d));
      res.on("end", () => {
        let json = null;
        try { json = buf ? JSON.parse(buf) : null; } catch (e) {}
        if (res.statusCode >= 200 && res.statusCode < 300) resolve(json);
        else reject(new Error("HTTP " + res.statusCode + " - " + (json && json.message ? json.message : buf.slice(0, 300))));
      });
    });
    req.on("error", reject);
    if (data) req.write(data);
    req.end();
  });
}

async function findPageBySlug(slug) {
  const res = await api("GET", "/wp/v2/pages?status=any&per_page=100&slug=" + encodeURIComponent(slug));
  return Array.isArray(res) && res.length ? res[0] : null;
}

async function upsertPage(p) {
  let content = fs.readFileSync(path.join(__dirname, p.file), "utf8");
  // Wrap as a Gutenberg HTML block so WordPress renders the markup verbatim (no auto-formatting).
  content = "<!-- wp:html -->\n" + content + "\n<!-- /wp:html -->";
  const payload = { title: p.title, slug: p.slug, content, status };
  const existing = await findPageBySlug(p.slug);
  if (existing) {
    const r = await api("POST", "/wp/v2/pages/" + existing.id, payload);
    console.log("  updated: " + p.title + "  (id " + existing.id + ") [" + status + "]");
    return existing.id;
  }
  const created = await api("POST", "/wp/v2/pages", payload);
  console.log("  created: " + p.title + "  (id " + created.id + ") [" + status + "]");
  return created.id;
}

(async () => {
  try {
    console.log("Connecting to " + siteUrl + " ...");
    const me = await api("GET", "/wp/v2/users/me");
    console.log("Authenticated as: " + (me.name || me.slug || cfg.username) + "\n");

    console.log("Creating / updating pages:");
    const ids = {};
    for (const p of PAGES) ids[p.slug] = await upsertPage(p);

    if (cfg.setHomepage !== false && ids.home) {
      console.log("\nSetting homepage...");
      try {
        await api("POST", "/wp/v2/settings", { show_on_front: "page", page_on_front: ids.home });
        console.log("  homepage -> Home (id " + ids.home + ")");
      } catch (e) {
        console.log("  couldn't set homepage automatically (" + e.message + ")");
        console.log("  do it manually: Settings -> Reading -> A static page -> Home");
      }
    }

    console.log("\nDone. Pages are '" + status + "'.");
    console.log("Next in WP admin: set each page to Full-Width + hide its title, then Publish.");
    console.log("(And finish the header/footer + CSS steps from BUILD-GUIDE.md.)");
  } catch (e) {
    console.error("\nERROR: " + e.message);
    console.error("Check wp.config.json and that /wp-json is reachable on your site.");
    process.exit(1);
  }
})();
