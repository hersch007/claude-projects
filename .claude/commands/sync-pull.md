---
description: Pull the latest changes down from GitHub for the current branch
---

Sync the local working copy with GitHub by pulling the latest changes down.

1. Run `git status` first. If there are uncommitted changes, run `git stash push -u -m "sync-pull autostash"` to save them before pulling.
2. Determine the current branch with `git rev-parse --abbrev-ref HEAD`.
3. Run `git fetch origin <branch>` then `git pull origin <branch>`.
4. If a stash was created in step 1, run `git stash pop` to restore the local changes on top of the pulled commits. If this produces conflicts, stop and report them clearly instead of resolving automatically.
5. Report a short summary: what branch was pulled, how many commits came in (if any), and whether local changes were restored.

If the pull fails (e.g. diverged history, merge conflicts), stop and explain the conflict to the user rather than force-resolving it.
