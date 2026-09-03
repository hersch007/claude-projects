# Rock Hill Pickleball — Navigation Menu Structure

*Rebuild these in **Appearance → Menus**. Menus can't be imported reliably, but they take ~2 minutes each: create the menu, check the pages in the "Pages" panel to add them, then drag child items slightly to the right to nest them under their parent.*

---

## MENU 1 — Primary Menu (main top navigation)

Keep top-level items to 5 so it stays clean on mobile. Indented items are dropdown children.

```
🆕 Start Here            → /start
      Skill Levels        → /start/skill-levels
      FAQ                 → /start/faq

🏓 Courts                → /courts
      Boyd Hill           → /courts/boyd-hill
      Bleachery Fieldhouse→ /courts/bleachery-fieldhouse
      Shield of Faith     → /courts/shield-of-faith
      Peachtree – Fort Mill → /courts/peachtree-fort-mill

👥 The Scene             → /scene
      Find Players & Groups → /scene/find-players
      Coaches & Clinics   → /scene/coaches-clinics
      Gear Guide          → /scene/gear-guide

🏆 Scheduler             → /scheduler   (the tournament tool page)

ℹ️ About                 → /about
```

**Notes**
- **Home** doesn't need a menu item — clicking your logo returns to the homepage (standard behavior).
- Emojis are optional; they add friendliness and help scanning on mobile. Remove if your theme looks cleaner without them.
- Each **parent** (Start Here, Courts, The Scene) is *both* a clickable page **and** a dropdown. On desktop, hovering opens the dropdown. On mobile, most themes add a little arrow to expand the dropdown separately from the link — test that tapping the word still goes to the page.
- Assign this menu to your theme's **Primary / Header** location (checkbox at the bottom of the Menus screen, "Display location").

---

## MENU 2 — Footer Menu (simple, flat)

```
About                    → /about
Contact / Suggest an Update → /contact
FAQ                      → /start/faq
Courts Guide             → /courts
Tournament Scheduler     → /scheduler
```

Assign to the **Footer** display location.

---

## Optional — a highlighted call-to-action button

Many themes let you style one menu item as a button. If yours does, make **"🆕 Start Here"** the button (a bright green or yellow pill) so newcomers have an obvious front door. Otherwise the homepage's two big buttons already cover this.

---

## How to build a menu (step by step)

1. **Appearance → Menus**.
2. Click **create a new menu**, name it `Primary` → **Create Menu**.
3. In the left **Pages** panel, check the boxes for the pages you want, click **Add to Menu**. (Click "View All" to see every page, including drafts.)
4. In the menu list on the right, **drag each child item slightly to the right**, underneath its parent, so it becomes a sub-item (indented). E.g., drag "Skill Levels" and "FAQ" under "Start Here."
5. Drag the top-level items into the order shown above.
6. Under **Menu Settings → Display location**, check **Primary** (name varies by theme).
7. **Save Menu.**
8. Repeat for the **Footer** menu (flat, no nesting) and assign it to the Footer location.

---

## Reminder about drafts
The imported pages start as **drafts**. A menu item pointing to a draft page will 404 for visitors until you **publish** that page. Publish pages as you finish filling in their `[FILL IN]` details, and the menu links light up automatically.

---

## Quick sanity check before launch
- [ ] Every top-level menu item goes to a real, **published** page.
- [ ] Dropdowns open on desktop and expand on mobile.
- [ ] Tapping a parent word (e.g., "Courts") still loads the Courts Guide page.
- [ ] The logo returns to the homepage.
- [ ] Footer menu shows and links work.
