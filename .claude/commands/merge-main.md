---
description: Push the current branch, open/update a PR, and merge it into main
---

Merge the current feature branch into `main` via a GitHub PR, then clean up the branch. This is meant to be the single command run from any computer to land work on `main`.

1. Determine the current branch with `git rev-parse --abbrev-ref HEAD`. If it is already `main`, report that there's nothing to merge and stop.
2. Run `git status`. If there are uncommitted changes, stop and tell the user to run `/sync-push` first (or commit manually) — do not commit on their behalf here.
3. Push the current branch to origin: `git push -u origin <branch>`.
4. Look for an existing open PR for `<branch>` → `main` (via `gh pr list --head <branch>` if the `gh` CLI is available, otherwise the GitHub MCP `list_pull_requests`/`search_pull_requests` tools).
   - If one exists, use it.
   - If not, create one. Check for a PR template (`.github/pull_request_template.md`, `.github/PULL_REQUEST_TEMPLATE.md`, root `PULL_REQUEST_TEMPLATE.md`, or `docs/PULL_REQUEST_TEMPLATE.md`) and mirror its structure if found; otherwise write a concise summary of the commits being merged.
5. Check the PR's mergeability: no merge conflicts, and any required CI checks are green (not just pending). If it's blocked (conflicts, failing checks, or CI still running), report exactly what's blocking it and stop — do not force through.
6. Merge the PR with **squash merge**, and delete the source branch as part of the merge (`gh pr merge --squash --delete-branch`, or the GitHub MCP `merge_pull_request` tool with branch deletion, or equivalent).
7. Switch the local repo to `main` and pull the merged changes: `git checkout main && git pull origin main`.
8. If the local feature branch still exists, delete it: `git branch -d <branch>` (only after confirming it merged cleanly — never `-D` unless the user says so).
9. Report a short summary: the PR that was merged (with URL), confirmation `main` is up to date locally, and that the feature branch was deleted.

Never force-push, never bypass failing CI or unresolved conflicts to get a merge through, and never merge a PR that has unresolved review threads requesting changes without checking with the user first.
