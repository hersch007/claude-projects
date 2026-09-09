---
description: Commit local changes and push them up to GitHub
---

Sync the local working copy up to GitHub by committing and pushing current changes.

1. Run `git status` to see what has changed, and `git diff` to review it.
2. If there are no changes (working tree clean and nothing to push), report that and stop.
3. Stage the relevant files (avoid `git add -A`/`git add .` if it would sweep in unrelated or sensitive files — add specific paths when in doubt). Double-check nothing that looks like a secret or credential is being staged.
4. Commit with a concise message describing what changed and why.
5. Determine the current branch with `git rev-parse --abbrev-ref HEAD` and run `git push -u origin <branch>`.
6. If the push is rejected because the remote has new commits, do not force-push. Instead run `git pull origin <branch>` to merge them in (resolving any conflicts), then push again.
7. Report a short summary: what was committed and confirmation the push succeeded, with the branch name.
