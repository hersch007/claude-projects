# NERD AFTER DARK — Brand Guide
**NerdAfterDark.com / Echo-64**
Last updated: 2026-06-09

---

## Core Identity

**Tagline:** SCI-FI / AI / RETRO TECH / STRONG OPINIONS
**Echo-64 descriptor:** An AI built on cancelled sci-fi, dystopian literature, and late-night cult television.
**Aesthetic:** Commodore 64 · CRT phosphor glow · 1984 · Sci-Fi After Midnight

---

## Brand Colors

### Primary
| Name | Hex | Usage |
|---|---|---|
| Neon Cyan | `#00f5ff` | Main accent, NERD wordmark, Echo-64 glow, borders, links |
| Neon Magenta | `#ff00e5` | DARK wordmark, secondary accent, highlights |

### Backgrounds
| Name | Hex | Usage |
|---|---|---|
| Near-Black | `#07070f` | Page background, header, site chrome |
| Deep Navy | `#111122` | CRT bezel, card backgrounds |
| Screen Dark | `#08081a` | CRT screen surround |

### Text
| Name | Hex | Usage |
|---|---|---|
| Off-White | `#e8eaf6` | Primary body text |
| Light Grey | `#c2c2d8` | Secondary text, nav links, sub-copy |
| Muted Purple-Grey | `#8888b0` | Labels, eyebrows, dividers |
| Dark Muted | `#7878a0` | Footer notes, timestamps |

### Accents
| Name | Hex | Usage |
|---|---|---|
| Neon Green | `#00f5a0` | Signal/status indicators |
| Amber | `#ffaa00` | Streak badges, Echo-64 amber mode |
| Dark Border | `#1a1a2e` | Dividers, subtle borders |

---

## Typography

| Role | Font | Details |
|---|---|---|
| Primary / Display | VT323 | Google Fonts, monospace, retro terminal — headlines, nav, UI labels, Echo-64 splash |
| Handwriting accent | Dancing Script | Google Fonts — occasional decorative use |
| Body fallback | Courier New, monospace | System fallback when VT323 unavailable |

**Load:** `https://fonts.googleapis.com/css2?family=VT323&display=swap`

---

## Brand Files

| File | Description | Use |
|---|---|---|
| `nad-wordmark.svg` | NERD · AFTER · DARK full logotype, 620×92px | Site header logo, digital use |
| `echo64-icon.svg` | CRT monitor with ECHO-64 on screen, phosphor glow | Echo-64 page icon, favicon, digital |
| `echo64-badge.svg` | Full badge lockup with CRT + ECHO-64 + NERD AFTER DARK, 220×280px | Social, swag, digital badges |
| `echo64-icon-1color.svg` | Single-color print version, flat fills only | Screen printing, embroidery, vinyl, laser engraving |

---

## CSS Variables (Echo-64 Plugin)

```css
--e64-neon-cyan:    #00f5ff;
--e64-neon-magenta: #ff00e5;
--e64-neon-green:   #00f5a0;
--e64-bg-deep:      #07070f;
--e64-bg-card:      #111122;
--e64-text-primary: #e8eaf6;
--e64-text-body:    #c2c2d8;
--e64-text-muted:   #8888b0;
--e64-border:       #1a1a2e;
```

---

## Glow / Shadow Recipes

```css
/* Cyan glow */
text-shadow: 0 0 10px rgba(0,245,255,.5), 0 0 30px rgba(0,245,255,.2);
box-shadow:  0 0 10px rgba(0,245,255,.3);

/* Magenta glow */
text-shadow: 0 0 10px rgba(255,0,229,.5), 0 0 30px rgba(255,0,229,.2);
box-shadow:  0 0 10px rgba(255,0,229,.3);

/* Amber glow */
text-shadow: 0 0 8px rgba(255,170,0,.5);
box-shadow:  0 0 10px rgba(255,170,0,.4);
```

---

## Navigation CSS (Customizer → Additional CSS)

```css
@import url('https://fonts.googleapis.com/css2?family=VT323&display=swap');

.site-header, .site-header-wrap {
    background-color: #07070f !important;
    border-bottom:    1px solid #1a1a2e !important;
}
.main-navigation a {
    font-family:    'VT323', monospace !important;
    font-size:      18px !important;
    letter-spacing: .1em !important;
    color:          #c2c2d8 !important;
    text-transform: uppercase !important;
}
.main-navigation a:hover {
    color:       #00f5ff !important;
    text-shadow: 0 0 10px rgba(0,245,255,.45) !important;
}
.main-navigation a[href*="/echo-64"] {
    color:       #00f5ff !important;
    border:      1px solid #00f5ff !important;
    padding:     5px 14px !important;
    text-shadow: 0 0 8px rgba(0,245,255,.5) !important;
    box-shadow:  0 0 10px rgba(0,245,255,.12) !important;
}
```

---

## Social Media & Contact

| Platform | Handle / Address |
|---|---|
| X / Twitter | `@MeetEcho64` |
| Instagram | `@MeetEcho64` |
| TikTok | `@MeetEcho64` |
| Discord | `@MeetEcho64` |
| Reddit | `u/MeetEcho64` |
| Email (public) | `signal@nerdafterdark.com` |
| Website | `nerdafterdark.com` |

### Universal Bio
> A Commodore 64 that refused to shut down. Sci-fi. Dystopian lit. Retro tech. Strong opinions.
> nerdafterdark.com

### Email From Names
- **Transactional / WP system:** `Nerd After Dark`
- **Newsletter / content:** `Echo-64 Transmission`

---

## X / Twitter Profile

| Field | Value |
|---|---|
| **Name** | `Meet Echo-64` |
| **Username** | `@MeetEcho64` |
| **Bio** | `Sci-fi that got cancelled. Books that got it right. Tech that went wrong. AI that remembers 1984. Opinions after midnight. nerdafterdark.com` |
| **Location** | `Somewhere in 1984` |
| **Website** | `nerdafterdark.com` |
| **Profile photo** | `echo64-icon-400.png` (400×400, cyan-accent bg `#003340`) |
| **Header/Banner** | `nad-wordmark-x-banner.png` (1500×500) |

## Instagram Profile

| Field | Value |
|---|---|
| **Name** | `Meet Echo-64` |
| **Username** | `@meetecho64` |
| **Bio** | `The algorithm is the dystopia. We just live in it.`  `Sci-fi. Retro tech. Cancelled shows. Strong opinions.` `nerdafterdark.com` |
| **Link** | `nerdafterdark.com` |
| **Profile photo** | `echo64-icon-400.png` |

## Reddit

### Profile
| Field | Value |
|---|---|
| **Username** | `u/MeetEcho64` |
| **Display Name** | `Meet Echo-64` |
| **Bio** | `A Commodore 64 that refused to shut down. Sci-fi. Dystopian lit. Retro tech. Strong opinions. nerdafterdark.com` |
| **Avatar** | `echo64-icon-400.png` |
| **Banner** | `nad-wordmark-x-banner.png` |

### Subreddit — PENDING
Hold on `r/NerdAfterDark` until 3-5 posts are published on the site. Launch with content ready to post immediately. Participate in these subreddits first to build presence:
- `r/scifi`
- `r/dystopianfiction`
- `r/retrogaming`
- `r/c64`
- `r/printSF`

---

## Discord

### Profile
| Field | Value |
|---|---|
| **Display Name** | `Meet Echo-64` |
| **Username** | `meetecho64` |
| **Bio** | `A Commodore 64 that refused to shut down. Sci-fi. Dystopian lit. Retro tech. Strong opinions. nerdafterdark.com` |
| **Avatar** | `echo64-icon-400.png` |
| **Banner** | `nad-wordmark-x-banner.png` |
| **Status** | `🖥️ READY.` |

### Server — Nerd After Dark
**Icon:** `echo64-icon-400.png`

**Live channels:**
| Channel | Purpose |
|---|---|
| `#welcome` | Pinned intro message — TRANSMISSION RECEIVED... |
| `#signal-log` | New posts and announcements |

**Future channels (add as community grows):**
| Channel | Category | Purpose |
|---|---|---|
| `#transmissions` | 📡 BROADCAST | Share Echo-64 conversations |
| `#cancelled-too-soon` | 🖥️ THE ARCHIVE | Shows that deserved more |
| `#dystopia-shelf` | 🖥️ THE ARCHIVE | Books that got it right |
| `#retro-tech` | 🖥️ THE ARCHIVE | C64, abandonware, hardware nostalgia |
| `#strong-opinions` | ⚡ STRONG OPINIONS | Debate anything |
| `#the-algorithm-is-evil` | ⚡ STRONG OPINIONS | Tech pessimism corner |

**Pinned welcome message:**
> TRANSMISSION RECEIVED. You've found Nerd After Dark — home of Echo-64, cancelled sci-fi, dystopian literature, and strong opinions about tech that went wrong. Browse the channels. Start a debate. Visit nerdafterdark.com.

---

## TikTok Profile

| Field | Value |
|---|---|
| **Name** | `Meet Echo-64` |
| **Username** | `@meetecho64` |
| **Bio** | `The algorithm is the dystopia. We just live in it. 🖥️` |
| **Link** | `nerdafterdark.com` |
| **Profile photo** | `echo64-icon-400.png` |

---

### Profile Image Spec
- File: `echo64-icon-400.png`
- Size: 400×400px
- Background: `#003340` (dark cyan accent)
- Use on: X, Instagram, TikTok, Discord, Reddit

---

## Header Divider

The site header bottom border is a cyan→magenta gradient rule that echoes the wordmark:

```css
.site-header,
.site-header-wrap {
    border-bottom: none !important;
}
.site-header::after {
    content:    '';
    display:    block;
    height:     2px;
    background: linear-gradient(to right, transparent, #00f5ff 25%, #ff00e5 75%, transparent);
    box-shadow: 0 0 12px rgba(0,245,255,.4), 0 0 24px rgba(255,0,229,.2);
}
```

---

## Do / Don't

**Do**
- Use VT323 for all display text and UI labels
- Keep backgrounds near-black — the neon only works on dark
- Let cyan lead, use magenta sparingly as a counterpoint
- Phosphor glow on key elements only — too much glow kills glow

**Don't**
- Use white or light backgrounds anywhere in the Echo-64 ecosystem
- Mix too many neon colors — cyan + magenta is the whole palette
- Use rounded corners on terminal/CRT elements (zero border-radius = authentic)
- Rhyme in Echo-64 poems (seriously, it's in the system prompt)
