# Test Checklist — Start Performance Role/Auth Integration (sp-fruth)

## Why this checklist, not the `wp_sqs_users` one

`docs/TODO.md` has a long-standing open item to test the individual **User Accounts** feature (`wp_sqs_users`, `[sqs_user_admin]`, Reset Logins, `can_manage_users` — shipped in Core 8.7.0 and 8.8.0). That checklist assumes the **standalone** Fruth site (`startwebservicesbackup.com/fruth/`).

**It does not apply to sp-fruth.** Per `docs/CHANGELOG.md` 8.9.0: when Core runs embedded in Start Performance, its auth check gets an additional OR-branch onto Start Performance's own `sp_get_current_team_member()` / `sp_is_admin_member()` — and Fruth's own accounts system, migration, and `[sqs_user_admin]` page are **entirely unused** in that mode. Start Performance's own team-member screen is what adds a person and assigns a role there.

What actually needs verifying on sp-fruth — and per the 8.9.0 changelog entry, was flagged as never tested end-to-end — is the **role mapping**:
- Start Performance **Admin** → gets Sales (Fruth Quotes) **and** Data Editor (Fruth Pricing Data) access.
- Start Performance **Agent** → gets Sales (Fruth Quotes) **only**.

If you're ever asked to verify the standalone site's individual-accounts feature instead, see "Standalone-site checklist" at the bottom — different feature, different site, do not conflate the two.

## sp-fruth: Role-Mapping Checklist

Setup:
- [ ] Have (or create) one Start Performance team member with **Agent** role and one with **Admin** role, neither a WordPress admin (`manage_options`) account — the point is to test SP's own role, not a WP superuser bypass.

Agent-role team member:
- [ ] Logs into Start Performance normally (no separate Fruth login screen — SP's own session covers it).
- [ ] Sidebar shows **"Fruth Quotes"** under the Sales Core section.
- [ ] Sidebar does **not** show "Fruth Pricing Data".
- [ ] Can open the Quote Builder, create a Tubing/In-Line/Zipper quote, and see live pricing.
- [ ] Can Save a new quote and confirm it persists (reload the view, Load Quote shows it).
- [ ] Can create a New Revision on an existing quote.
- [ ] Can Print a quote (formatted card, not raw UI).
- [ ] Directly navigating to the Fruth Pricing Data view URL (if guessable) is blocked/hidden, not just absent from the sidebar — confirm this isn't a client-side-only hide.

Admin-role team member:
- [ ] Sidebar shows **both** "Fruth Quotes" and "Fruth Pricing Data".
- [ ] Can do everything an Agent can (quotes, save, revision, print).
- [ ] Can open Fruth Pricing Data, edit a table value, Save, and confirm the change persists on reload.
- [ ] Data Editor's Add Row / Advanced View buttons are legible (this was a real bug historically — 8.6.3 — confirm the fix still holds in the embedded view).

Cross-cutting:
- [ ] "Prepared By" auto-fills from the logged-in SP team member's display name on a new quote (per 8.9.0's `sqs_pricing_calculator_current_user_display_name()`), and does not overwrite a value already typed or restored from a loaded quote.
- [ ] Aging-quotes banner/KPI card (8.9.8/8.9.9) reflects real open quotes 30+ days untouched, if any exist.
- [ ] No PHP notices/errors in the site's debug log when loading either Fruth view (the historical `#productLabel`-null crash from 8.9.0→8.9.1 is exactly the class of bug to watch for after any future embedded-mode change).
- [ ] Confirm a demoted/deactivated SP team member's access changes take effect on their **next page load**, not just next login — SP's own session model governs this, but worth a quick check since Fruth's own equivalent guard (re-checking `can_manage_users` fresh from the DB every AJAX call) was a deliberate design choice in 8.8.0 and the same expectation should hold here.

## What this checklist does NOT cover

The Fruth-specific `wp_sqs_users` accounts system (add/edit/deactivate individual accounts, `can_manage_users`, `[sqs_user_admin]`, Reset Logins break-glass, the two independent lockout guards) is inert in SP-embedded mode — none of those code paths run when `sp_register_addon` mode is active. Skip them here.

---

## Standalone-site checklist (for reference — NOT sp-fruth)

Applies only to `startwebservicesbackup.com/fruth/`, where Core runs standalone (no Start Performance) and the real `wp_sqs_users` system is what's authenticating people. Keep for whenever that site's individual-accounts feature actually needs verifying:

- [ ] Upload confirms migration runs cleanly, creating two `(legacy)` rows in `wp_sqs_users`.
- [ ] Both existing shared logins (old Sales / old Editor credentials) still work unchanged after migration.
- [ ] Customer Database module's session-based access still works with no change to that plugin.
- [ ] Add one brand-new individual account via wp-admin Login & Branding UI; confirm role-appropriate access (`sales` / `admin` / `both`).
- [ ] Deliberately test the "Reset Logins" recovery path once — confirm the emergency `sales_admin_reset` account is created/reset and actually logs in (8.8.1 fixed a real bug here: the account was reported as "Done!" but silently failed to save).
- [ ] Give a test account `can_manage_users=1`; confirm it can log into `[sqs_user_admin]` (reached via the Portal's "User Management" tile) and manage other accounts with zero WordPress login involved.
- [ ] Confirm the two lockout guards independently block removing (a) the last active admin/both-role account and (b) the last active `can_manage_users` account.
- [ ] Log into the Sales Portal in one browser tab and User Management in another (same browser session) — confirm no cross-contamination of the Quote Builder's "Prepared By" auto-fill (the two login flows use deliberately separate session keys).
