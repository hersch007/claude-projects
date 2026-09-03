# Pickleball Tournament Scheduler 2

A free, self-contained pickleball tournament scheduler. Runs entirely in the
browser — **no WordPress, no server, no database, no accounts, nothing collected.**
Works on phones and laptops. Scores save automatically on the device that entered them.

## Features
- **Round Robin** — everyone plays everyone once (4–12 teams). Automatic byes for odd counts.
- **Pool Play → Playoffs** — split into pools, play mini round-robins, then auto-seed a
  single-elimination bracket from the pool standings (top N per pool advance).
- **1–4 players per team** (singles, doubles, or bigger squads) — up to 48 players.
- **Court assignment** across the courts you have, with "Wave 2" labels when there are
  more matches than courts.
- **Live standings** — enter scores, rankings update instantly (Wins → point differential → points for).
- **Tap-to-advance bracket** with a champion banner.
- **Print / Save-PDF** button for posting or handing out.

## Files
```
pickleball-tournament-scheduler-2/
├── index.html            ← open this
└── assets/
    ├── scheduler.css      ← styles (namespaced, won't collide with a host page)
    └── scheduler.js       ← all the logic
```

## How to use it
Just open `index.html` in any browser. Pick your format, teams, and courts, hit
**Generate**, and go.

## How to put it on a website (including WordPress)
You do **not** need a plugin. Two easy ways:

**A. Iframe (simplest, fully isolated — recommended).**
Upload this whole folder to your web host, then embed it anywhere with:
```html
<iframe src="/path/to/pickleball-tournament-scheduler-2/index.html"
        style="width:100%;height:1400px;border:0" title="Tournament Scheduler"></iframe>
```
In WordPress, paste that into a **Custom HTML block**. (An iframe can't clash with your
theme's styles, which is why it's the safe choice.)

**B. Inline.** Copy the contents of `index.html` (the `<div id="pbts-root">…</div>` block)
into a Custom HTML block, and load `scheduler.css` and `scheduler.js` on the page. All the
CSS/IDs are prefixed `pbts-`, so they're built not to collide with your theme.

## Rebrand it for another club
Open `index.html` and change the two lines inside `<div class="pbts-header">` — the club
name and the subtitle. That's the only change needed. Share the folder freely.

## Good to know
- Scores are stored in **one browser on one device** (via localStorage). Perfect for a
  single scorekeeper running the event off one laptop or phone. It is **not** a shared,
  multi-device live scoreboard — that would require a server/database (a possible future add-on).
- No internet connection is required once the files are on the device.

## License
Released under the **MIT License** (see the `LICENSE` file) — free for anyone to use, copy,
modify, rebrand, and share, with no strings attached. The only condition is that the short
copyright notice stays in the `LICENSE` file. To credit a different person or club, change the
`Copyright (c) 2026 …` line in `LICENSE`.

## Works offline
Once the files are on a device, no internet connection is needed — the whole tool runs locally
in the browser and saves scores on that device. (Internet would only be needed for a future
shared, multi-device live-scoreboard version.)
