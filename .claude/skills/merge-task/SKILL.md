---
name: merge-task
description: Land a reviewed task — squash its branch into `main` as ONE commit, then move the task folder to `tasks/done/`. Run this after `/dispatch-task` has produced a verified branch and the user has reviewed it. RUNS END TO END WITHOUT ASKING FOR CONFIRMATION — invoking it IS the express per-action permission the protected-`main` rule requires. Works out WHICH task from the conversation itself — the current `worktree/…` branch, or the task this session dispatched/verified/reviewed — and only asks when that's genuinely ambiguous. Refuses to merge a task whose `to-fix.md` still has items, brings the branch up to date with `main` first, squash-merges the draft PR (marking it ready), deletes the remote and local branch, pulls `main`, rebuilds the Parcel bundle so the local site matches, confirms the change actually landed, and files the task under `tasks/done/`. ONE task per run. Trigger phrases: "merge this task", "land this task", "squash and merge it", "finish this task off", "/merge-task".
---

# Land a task (squash → `main` → done)

Run this skill when the user says "merge this task", "land this task", "squash and merge it", "merge it and
mark it done", "finish this task off", or equivalent. It is the **closing half of
[dispatch-task](../dispatch-task/SKILL.md)**: that skill ends with a verified branch sitting in
`tasks/in-review/` awaiting review; this one lands it.

> **One task per run.** Work out which one, land it, file it, done.

> **Run it end to end — do NOT ask the user to confirm anything.** `main` is protected and needs express
> permission *for the specific change*; invoking `/merge-task` on a named, reviewed task **is** that
> permission. So never pause on "shall I merge?", "is this the right task?", "does this message look
> right?" or "ready to proceed?" — decide, act, and report at the end. The only two things that stop you
> are (a) a genuine blocker in Step 2 (outstanding `to-fix` items, dirty tree, conflicts, no branch) and
> (b) genuinely ambiguous task inference in Step 1 where the signals conflict — everything else you resolve
> yourself and carry on. A global PreToolUse hook may still prompt on the push to `main`; that prompt is
> expected, not an error.

**The task folder moves once:** `tasks/in-review/` (reviewed, waiting to land) → `tasks/done/` (merged into
`main`).

This repo is **Quedamos wp-content: WordPress FSE, `twentytwentyfour` parent + `wp-child-theme-template`
child, Parcel build**, and `main` is its only long-lived branch — so `main` is the target here, not a
staging branch. **There is no CI in this repo:** merging publishes the code to GitHub, it does **not** touch
quedamoslanguages.com. Deploying to live is a separate act outside this skill — say so when you report, and
never imply the merge shipped anything to visitors.

---

## Step 1 — Work out which task this conversation is about

`ls tasks/in-review/` first — entries are **folders** (`/dispatch-task` always files a task as a folder
containing the task `.md`, any design files, `to-fix/`, and possibly `verify-artifacts/`). Ignore
`.gitkeep`. If it's empty, stop and tell the user there is nothing waiting to land.

**Then infer the task from the conversation instead of asking.** This skill is nearly always run in the same
session that just dispatched, verified, or reviewed one specific task — that context already names it. Work
down these signals and take the first that resolves:

1. **The user named it at invocation** (`/merge-task escape-article-hero-output`, or "merge the article hero
   one") — match it against `tasks/in-review/`, fuzzily if needed.
2. **The current branch.** `git branch --show-current` — if it is `worktree/<something>`, that is the task's
   branch (the session may be sitting in the task's worktree; `git worktree list` says which). Map it back
   to its folder via the rule below.
3. **What this conversation has been doing.** The task brief you read, the branch you checked out, the PR
   number you opened or verified, the `to-fix.md` you went through, the files you edited — any of these
   identify the task. Recent commits on the current branch (`git log --oneline -5`) usually spell out the
   task's subject too.
4. **Exactly one entry in `tasks/in-review/`** → select it.
5. **Nothing above resolves** → list the entries and ask which ONE. If the user names several, land the
   first and say the rest stay queued.

Then sanity-check the inference before acting on it:

- **Signals disagree** — the branch points at one task, the conversation was about another — **ask.** Don't
  pick a winner silently.
- **The inferred branch has no folder in `tasks/in-review/`** — say so rather than inventing one. The task
  may still be in `tasks/in-progress/` (not reviewed yet), or already in `tasks/done/`; check both and
  report which, instead of merging something that hasn't been through review.
- Whatever you inferred, **state it plainly before you merge** — the folder, the task `.md` and the branch,
  and in one clause how you inferred it — then carry straight on. That's a statement of what you're doing,
  **not** a request for approval: don't wait for a reply.

### Folder name vs branch name

These are **not always the same** and the distinction matters — the branch comes from the task **`.md`
filename**, not the folder:

- folder `tasks/in-review/escape-article-hero-output-p-2/` containing `escape-article-hero-output-p-2.md` →
  branch `worktree/escape-article-hero-output-p-2` (the common case, names match)
- folder `tasks/in-review/redesign-course-card/` containing `rebuild-course-card-layout-p-2.md` → branch
  `worktree/rebuild-course-card-layout-p-2` (folder named for the theme, task named for the work)

So: **`ls tasks/in-review/<folder>/*.md` to get the real task name**, and derive the branch from that. Going
the other way (branch → folder), grep for the `.md`:

```
find tasks/in-review -name "<task-name>.md"
```

Throughout the rest of this skill, `<task-name>` means the task `.md` filename sans extension (which is the
branch), and `<folder>` means the folder it lives in (which is what moves in Step 6).

---

## Step 2 — Pre-flight checks (all of these, before anything is merged)

Run these and stop on any failure rather than merging something half-ready:

1. **Outstanding fixes.** Read `tasks/in-review/<folder>/to-fix/to-fix.md`. If it has any item with real
   text after the numbered marker (i.e. the user filled the punch list in), **STOP**: the task has known
   problems and should go back through a fix pass, not into `main`. Show the items and ask whether to land
   it anyway — only proceed on an explicit yes.
2. **The branch exists.** `git rev-parse --verify worktree/<task-name>` locally, or
   `git ls-remote --heads origin worktree/<task-name>`. If neither, stop and say so.
3. **The user's working tree is clean.** `git status --porcelain`. If dirty, stop and ask — do not stash
   someone else's in-flight work to land a branch. (Remember `tasks/` is gitignored, so task files never
   show up here.)
4. **Find the PR**, if there is one:
   `gh pr list --head worktree/<task-name> --state open --json number,isDraft,mergeable,mergeStateStatus,baseRefName`.
   - Confirm `baseRefName` is `main`. If it points at anything else, stop and report — `/dispatch-task`
     always bases on `main`, so a different base means something is off.
   - A task dispatched "commit only" has no PR; that's the local path in Step 4b.
5. **The branch is current with `main`.** `main` moves while a task is being worked, so a branch verified
   hours ago may no longer be what merges:
   ```
   git fetch origin main
   git merge-base --is-ancestor origin/main worktree/<task-name>
   ```
   If it is behind, merge `main` **into the branch** first (never rebase a pushed branch), push, and
   re-check that the PR is `MERGEABLE`:
   ```
   git checkout worktree/<task-name> && git merge origin/main --no-edit && git push
   ```
   If that merge conflicts, **STOP** and hand the conflicts back to the user — do not guess a resolution.
   If bringing it up to date pulled in meaningful changes, say so: the verification `/dispatch-task` did was
   against the older base, and an SCSS or `inc/` module change on `main` can silently change what the
   branch renders.

---

## Step 3 — State the squash commit, then merge (no confirmation)

The squash commit is what lands in `main`'s history, so get it right once — **you** get it right, on your
own judgement. Write it out for the record and go straight into Step 4. Show the user:

- the task you inferred and **how** you inferred it (current branch, the task this conversation has been
  about, or the only entry in `tasks/in-review/`) — one clause is enough,
- the task `.md`, its folder, and the branch,
- `git diff --stat origin/main...worktree/<task-name>`,
- the **proposed squash subject and body.**

Subject: a short imperative sentence describing the task, **with no PR-number suffix** — that's this repo's
actual history style, e.g. `Add the redirects module`, `Only reserve the TOC column when there is a TOC`,
`Drop a leading content image that repeats the featured image`. Body: 1–3 sentences of what and why, the PR
link if there is one, plus anything knowingly left uncovered or deferred (pull this from the task `.md` and
the dispatch run's findings). No `Co-Authored-By` trailer on the squash — the individual commits already
carry it.

Then **merge**. Do not ask the user to approve the task, the message, or the merge — the preview is a record
of what you're about to do, printed on the way past. Write the best subject and body you can from the task
`.md` and the dispatch findings, and use them.

---

## Step 4 — Squash-merge into `main`

### 4a — With a PR (the normal path)

The PR from `/dispatch-task` is a **draft**, so mark it ready, then squash-merge:

```
gh pr ready <number>
gh pr merge <number> --squash --delete-branch --subject "<subject>" --body "<body>"
```

`gh pr merge` sometimes returns no output on success — **always confirm** rather than assuming:

```
gh pr view <number> --json state,mergedAt,mergeCommit --jq '{state,mergedAt,mergeCommit:.mergeCommit.oid}'
```

`state` must be `MERGED`. If it is not, report what `gh` said and stop.

### 4b — No PR (a "commit only" task)

```
git checkout main
git merge --squash worktree/<task-name>
git commit -m "<subject>" -m "<body>"
git push origin main
```

This path pushes straight to protected `main` with no PR gate. The `/merge-task` invocation on a reviewed
task is the permission for it, so don't stop to re-ask — but expect the global hook to prompt, and if the
user declines at that prompt, leave the squash commit local and say so plainly rather than retrying.

---

## Step 5 — Update local `main`, rebuild, and prove it landed

```
git checkout main
git pull origin main
git log -2 --oneline
```

**Then rebuild the bundle.** `dist/` is gitignored, so the docroot is now on `main`'s source with the
*previous* branch's compiled CSS/JS — the local site will be a lie until you rebuild:

```
cd themes/wp-child-theme-template && npm run build
```

Enqueue versions come from `filemtime` on the `dist/` files, so the rebuild busts the browser cache by
itself. If the task touched anything a visitor sees, purge the page cache too (LiteSpeed and Perfmatters are
both active) — `wp litespeed-purge all`, socket-prefixed as `/dispatch-task` documents.

Then **verify the change is actually present** — don't take the merge at its word. Pick 1–2 concrete markers
from the task (a new file, a deleted file, a distinctive added string) and check them on `main`:

```
ls <a file the task added>                 # exists
find … -name "<a file the task deleted>"   # gone
grep -c "<a string the task added>" <file>
```

Report what you checked. Then clean up:

- `git branch -d worktree/<task-name>` (already gone if `--delete-branch` ran; a "not found" here is
  success, not an error).
- If a worktree still holds the branch: `git worktree list`, then `git worktree remove <path> --force`.

---

## Step 6 — File the task under `tasks/done/`

- Create `tasks/done/` if missing.
- Move the **whole folder** as a unit, keeping its existing name — task `.md`, design files, `to-fix/`,
  `verify-artifacts/`:
  `tasks/in-review/<folder>/` → `tasks/done/<folder>/`
  Do **not** rename the folder to the task/branch name on the way — the folder keeps whatever it was called.
- Use a plain `mv` — `tasks/` is gitignored apart from the `.gitkeep` skeleton, so `git mv` would fail or
  move nothing. Then `find` the destination to confirm every file arrived — **especially the task `.md`.**
- Leave `tasks/in-review/` free of that task; leave every other task where it is.

---

## Step 7 — Report

| Task | Branch | Merged as | Filed |
|------|--------|-----------|-------|
| `<task-name>` | `worktree/<task-name>` | `<squash commit sha>` (PR #N) | `tasks/done/<folder>/` |

Then tell the user:

> "Merged into `main` as one commit and rebuilt the bundle, so your local site reflects it. Nothing is
> deployed — quedamoslanguages.com is unchanged until the theme is shipped there separately."

Surface anything the user still needs to act on: items the dispatch run flagged as needing a product
decision or wp-admin work (an ACF field, a Site Editor / `wp_global_styles` change, page content),
verification the run couldn't do unattended, or follow-up tasks the brief names.

---

## What not to do

- Do not land more than one task per run.
- **Do not ask for confirmation before merging** — not "shall I go ahead?", not "does this commit message
  look right?", not "ready to merge?". The user ran the skill on a reviewed task; that is the per-action
  permission `main` requires. Stop only for a Step 2 blocker or conflicting task-inference signals.
- Do not ask "which task?" when the conversation already answers it — the current `worktree/…` branch or the
  task this session just dispatched/verified/reviewed is the task. Ask only when the signals conflict or
  none resolve.
- Do not guess when signals conflict, and do not merge a task that isn't in `tasks/in-review/` because a
  branch of that name exists — an unreviewed task has no business landing on `main`.
- Do not assume the folder name is the branch name — the branch comes from the task `.md` filename inside
  the folder.
- Do not treat this invocation as standing permission for the *next* merge — permission on `main` is
  per-change, so a follow-up task needs its own `/merge-task` run.
- Do not merge a task whose `to-fix.md` has real items without an explicit yes from the user — that punch
  list exists because review found problems.
- Do not merge a branch that is behind `main` — bring it up to date first, or the verification that
  justified landing it was against a base that no longer exists.
- Do not rebase or force-push a branch that is already pushed — merge `main` into it.
- Do not resolve merge conflicts yourself — hand them back.
- Do not stash or discard the user's uncommitted work to free the tree for a merge.
- Do not assume `gh pr merge` worked because it printed nothing — read back `state: MERGED`.
- Do not assume the merge contents landed — verify a concrete marker on `main`.
- Do not leave the docroot on `main` with a stale `dist/` — rebuild after the pull, or the local site shows
  the old branch's CSS/JS and the next person debugs a ghost.
- Do not tell the user the merge deployed anything — there is no CI here; `main` is code, not the live site.
- Do not leave the task in `tasks/in-review/`, and do not split its folder when moving it to `done/`.
- Do not use this on a prototype run — a prototype has no branch to land.
