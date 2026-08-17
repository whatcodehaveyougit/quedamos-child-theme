---
name: dispatch-task
description: Dispatch a single staged coding task to a git-worktree background agent for the Quedamos wp-content repo (WordPress FSE, twentytwentyfour parent + wp-child-theme-template child, Parcel build), then verify the branch and auto-fix failures. The user drops a task into `tasks/dispatch/` — a plain `.md` file, or a folder containing the task `.md` plus design files the implementation must match. Lists the staged tasks (files AND folders), asks which ONE to dispatch and whether it's a prototype, moves the chosen task into `tasks/in-progress/`, spawns a background Agent (`isolation: "worktree"` off `main`, or in-place for a prototype), opens a draft PR up front (for push+PR tasks) so progress is visible as commits land, waits for it to commit, then runs a verify+fix stage against the task's acceptance criteria (deterministic checks always; browser smoke when the task calls for it; capped at 5 review rounds). ONE task per run. Not for tasks needing DB writes, plugin/ACF field changes or wp-admin configuration; the user does the final review + merge. Trigger phrases: "dispatch this task", "dispatch this work", "work this task in a worktree", "/dispatch-task".
---

# Dispatch a task (worktree agent — Quedamos wp-content)

Run this skill when the user says "dispatch this task", "dispatch this work", "dispatch-work", "take this
task", "work this task in a worktree", "run this task", or equivalent. It hands **one** staged coding task
to a background agent isolated in its own git worktree on its own branch. After the agent commits, a
**verify+fix stage** runs the task's deterministic acceptance checks (and, when the task calls for it,
brings the branch live in the docroot and smokes it in a headless browser), auto-fixing failures before
reporting — so a dispatched task ends as *code written → built → committed → verified*, not just
committed.

> **This skill dispatches exactly one task per run.** Ask which one task, dispatch it, verify it, done.

**A task's folder tells you its state, and this skill moves it twice:** `tasks/dispatch/` (staged, nothing
running) → **`tasks/in-progress/`** (dispatched — an agent is working it, Step 2b) → `tasks/in-review/`
(the branch is committed + verified and waiting on the user's review, Step 7). A task must never sit in
`dispatch/` while an agent is working it — that's how the same task gets dispatched twice.

This repo is **Quedamos wp-content: WordPress FSE, `twentytwentyfour` parent + `wp-child-theme-template`
child, assets through Parcel**, base branch **`main`**. The brief comes from
[create-task](../create-task/SKILL.md); the conventions the spawned agent must follow live in
[writing-php](../writing-php/SKILL.md), [writing-scss](../writing-scss/SKILL.md) and
[writing-js](../writing-js/SKILL.md).

## The two things that make this repo different

Read both before dispatching anything — they shape every later step.

1. **The repo root is `wp-content/`, not the WordPress root.** `wp-config.php` and core live *above* it and
   aren't version-controlled. On top of that, `themes/twentytwentyfour/*` (the parent theme),
   `plugins/*` (except `theme-customizations`), `uploads/` and `mu-plugins/` are all gitignored. So **a git
   worktree of this repo is not a runnable WordPress site** — it is a code-only checkout. The spawned agent
   can edit, build with Parcel, and lint PHP; it cannot load a page. Anything browser-shaped happens later,
   in the real docroot (Step 5).
2. **`main` is protected.** The PR targets `main` and is never merged by this skill, and neither the agent
   nor the orchestrator ever pushes to `main`. Pulling `main` locally to refresh the base is fine; pushing
   to it is not.

## When to use vs. when not to

**Use for:** pure coding tasks — PHP under `themes/wp-child-theme-template/` (`inc/` modules, shortcodes,
schema generators, hook handlers, render callbacks), SCSS under `assets/styles/`, JS under
`assets/scripts/`, block templates under `templates/`. Anything where "code written + built + committed" is
a complete unit of work.

**Do not use for:** tasks whose *completion* requires writes to the database, new or changed ACF field
groups, plugin installs/settings, `wp_global_styles` / Site Editor changes, page or post content edits, or
media uploads — those are wp-admin work against the live install, not code in this repo. (Plain browser
**smoke** of a code change is fine and handled by Step 5; it's DB/state mutation that's out of scope.)

Note the `theme.json` trap from `CLAUDE.md`: the file is a Site Editor export and the `wp_global_styles`
database record **overrides it**, so a task that edits `theme.json` cannot be verified by reading the file
— it needs the browser pass, and even then a stale DB copy may mask the change. Flag that in the summary
rather than declaring it verified.

## How the run touches your machine

- **Build phase (Steps 3–4): safe to run alongside the user's own local work.** The agent is isolated in
  its own git worktree (separate directory, branch, `node_modules/`, `dist/`), so it never touches the
  user's working tree or the running local site.
- **Verify phase (Step 5): the deterministic checks (5b-i) are safe; the browser smoke pass (5b-ii) is NOT
  safe alongside local work.** The browser pass takes over the shared docroot — there is one WordPress
  install, one database, and one `wp-content/`, so only one branch can be "live" at a time. **Step 5
  therefore announces itself and waits for the user before starting the browser pass.** If the task needs
  no browser pass, say so and skip the takeover entirely.

---

## Step 1 — List the staged tasks and ask which to dispatch

The staging folder is `tasks/dispatch/` at the repo root.

**A staged task is one of two shapes:**

- **A plain `.md` file** — e.g. `tasks/dispatch/escape-article-hero-output-p-2.md`. The task is the whole
  brief.
- **A task folder** — a directory (sometimes named with a misleading `.md` suffix, e.g.
  `tasks/dispatch/redesign-course-card.md/`) containing **the task `.md` file plus design files** (a
  design-handoff sub-folder with HTML/CSS mockups, a README, screenshots, etc.). The folder shape is used
  precisely *because there are design files the implementation must match.* When a task is a folder, the
  design files are the direction the implementation follows — but **the design system outranks them**
  (`writing-scss`): where a comp disagrees with an existing component or a token in `variables.scss`, the
  system wins and the mismatch is *reported*, not overridden with a hardcoded value.

So:

1. **List the entries** in `tasks/dispatch/` — both files and sub-folders (`ls` it; don't glob `*.md` only,
   or you'll miss folder tasks). Ignore the `.gitkeep`.
2. **Show their names** with their `-p-N` priority.
3. **Ask the user which ONE** they want to dispatch — do NOT read any contents yet, and wait for their
   answer.

This skill dispatches one task per run — if the user names several, dispatch the first and tell them the
rest stay queued for a later run.

- If the folder is empty (and the user named no files), stop and tell the user — do not spawn anything.
- If the user explicitly named a specific task at invocation time, treat that as their selection and skip
  the question — but still confirm it exists before reading it.

Once the user has chosen, resolve the task to its **task `.md` file**:

- Plain-file task → that file is the task `.md`.
- Folder task → the `.md` file *inside* the folder is the task `.md`; **also note every design file in the
  folder** (list the folder recursively) — they get passed to the agent in Step 3 and the agent MUST study
  them.

**Read only the selected task `.md` in full** (and, for a folder task, skim the design README so your Step
3 instructions point at the right files). The other staged tasks stay in `tasks/dispatch/` untouched.

---

## Step 2 — Ask before spawning

Before spawning the agent, ask the user two things:

1. **Is this a prototype, or not?**
   - **Prototype** → do **not** create a new branch. The work is a standalone mockup that isn't being
     integrated, so there's no branch to review/merge. Skip the worktree isolation entirely: the agent
     works in place on the current branch, and the commit/push+PR question below does **not** apply. The
     Step 5 verify+restore dance is unnecessary (there's no branch to bring live and restore). Just build
     the prototype and report back.
   - **Not a prototype** → the normal flow: an isolated git worktree + branch for the task (Step 3),
     verify+fix (Step 5), the user reviews and merges.
2. **(Only if NOT a prototype)** "Stop at **commit on branch** only, or also **push + open a PR** into
   `main`?" Default if the user doesn't answer or says "default": **push + open a PR**. **The PR targets
   `main`** — the repo's only long-lived branch — **and is never auto-merged;** `main` is protected, so the
   merge is always the user's own action. On the push+PR path the PR is opened **up front, as a draft, the
   moment the branch is renamed — before any code is written** (Step 3, instruction step 2), so the user
   can watch commits land in it as the task progresses. **The PR always stays a draft** — the agent never
   marks it ready for review, and neither does the orchestrator.

Ask both up front. For a prototype, only the first question matters.

**Refresh the base first (non-prototype).** Before spawning, make local `main` current so the worktree
bases off fresh code. From the main repo:

```bash
git checkout main && git pull origin main
```

That is a pull, not a push — allowed on protected `main`. If the working tree is dirty (uncommitted edits
beyond untracked files) and the pull would fail, tell the user and stop rather than forcing it. Return to
the user's original branch after the pull if they weren't on `main`.

---

## Step 2b — Move the chosen task into `tasks/in-progress/`

**Do this after the base refresh and before spawning the agent** — the moment a task is handed to an agent
it stops being "staged", so it leaves `tasks/dispatch/`. This applies to prototypes too.

- Create `tasks/in-progress/` if it's missing.
- **Plain-file task** → move the `.md`: `tasks/dispatch/<name>.md` → `tasks/in-progress/<name>.md`.
- **Folder task** → move the **whole folder** as a unit (task `.md` + design files + screenshots):
  `tasks/dispatch/<name>/` → `tasks/in-progress/<name>/`. Never split the brief from its design handoff.
- Use a plain `mv` — `tasks/` is gitignored apart from the `.gitkeep` skeleton, so there is nothing to
  `git mv`. Then `ls`/`find` the destination to confirm every file — including the `.md` — actually
  arrived.
- Leave the un-dispatched tasks in `tasks/dispatch/` untouched.

**Every later step uses the new `tasks/in-progress/…` path** — the absolute paths you pass the agent in
Step 3 (TASK FILE, DESIGN FILES) and any fix agent in Step 5d must point at `tasks/in-progress/`, not the
old `tasks/dispatch/` location. Do the move *before* building those paths so you never hand an agent a
path that no longer exists.

Branch naming is unaffected — it still derives from the task `.md` filename (see **Branch naming**), not
from which folder the task currently sits in.

If the run aborts before the agent is spawned (e.g. a stale branch name, a dirty tree), move the task back
to `tasks/dispatch/` so it's staged again rather than stranded in `in-progress/`.

---

## Step 3 — Spawn the background Agent

**Prototype path (Step 2 = prototype):** do NOT use `isolation: "worktree"` and do NOT create a branch.
Spawn the agent in place (still `run_in_background: true`), tell it to build the prototype on the current
branch and report back — skip the branch/base/commit instructions (2, 7, 8) below. There's no branch to
verify+restore, so the run ends after the agent reports (skip Step 5; go straight to the summary and Step
7).

**Normal path (Step 2 = not a prototype):** spawn a background `Agent` for the chosen task with
`isolation: "worktree"` and `run_in_background: true`. Pass it the instruction block below (fill in the
placeholders).

```
You are implementing one task for the Quedamos wp-content repo (WordPress FSE, twentytwentyfour parent
+ wp-child-theme-template child, Parcel build), isolated in your own git worktree.

TASK FILE: <absolute path to task .md file>
DESIGN FILES: <absolute path to the design-handoff folder/files, OR "none — this task has no design files">
BASE: origin/main  (you MUST be on top of the latest main — see step 2)
BRANCH: worktree/<task-filename-without-extension>  (rename your branch to exactly this in step 2)

IMPORTANT — your worktree is NOT a working WordPress site. The repo root is wp-content/, and
wp-config.php, WordPress core, the twentytwentyfour parent theme, plugins/, uploads/ and mu-plugins/
all live outside it or are gitignored. You cannot load a page, run WP-CLI, or query the database. Do
not try. Your job ends at a clean, built, committed change — the orchestrator smokes it in the real
docroot afterward (Step 5).

Instructions:
1. Read the task file in full before touching any code. IF DESIGN FILES are listed above, open and
   study every one closely (HTML/CSS mockups, README, screenshots) BEFORE writing code. Match the
   design THROUGH the design system: pull the real spacing, type, colour, radius, hover/focus and
   layout values from the handoff and express them with the theme's own tokens
   (assets/styles/scss/variables.scss) and existing components in assets/styles/scss/components/, per
   the writing-scss skill — the design files tell you the target, the design system tells you the
   token/component to say it with. Where a handoff value maps to an existing token, use the token.
   THE DESIGN SYSTEM OUTRANKS THE COMP: where the comp disagrees with an existing component (casing,
   underline, colour, radius, size), ship the component's own rendering and REPORT the difference —
   never a hardcoded hex or a page-scoped override to force a match. Where a value conflicts with the
   system or has no token, STOP and note it in your summary rather than inventing one. If a comp
   detail could only be built by adding behaviour and the task is a visual one, do not build it —
   flag it instead.
2. CRITICAL — fix your base. The harness creates this worktree off the repo's default branch and the
   auto-named branch you are on (`worktree-agent-<id>`) may be behind the latest pushed `main`.
   Before touching anything, fetch and re-point your branch to main's HEAD:
      git fetch origin main
      git reset --hard origin/main
   Then confirm you are on top of main:
      git merge-base --is-ancestor origin/main HEAD && echo "ON MAIN" || echo "NOT ON MAIN — STOP"
      git log -1 --oneline origin/main && git log -1 --oneline HEAD
   If it does not print "ON MAIN", STOP and report — do not proceed on a stale base.
   Then RENAME your branch from the auto-generated `worktree-agent-<id>` to the clean name given as
   BRANCH above, so the local branch, the remote branch, and the PR head all share ONE readable name:
      git branch -m worktree/<task-filename-without-extension>
   (`git branch -m` renames the branch you're on — fine inside a worktree; the worktree directory keeps
   its own `agent-<id>` name, only the branch is renamed.) Confirm with `git branch --show-current`.
   If that name already exists (a stale earlier run), STOP and report rather than guessing a variant.
   NEVER push to `main` — you only ever push your own `worktree/…` branch.
   <IF push+PR: NOW, before writing a single line of code, open the PR so the user can watch progress
    land in it live. Create an empty initial commit, push the branch, and open a DRAFT PR into main:
       git commit --allow-empty -m "wip: <task title>

    Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>"
       git push -u origin HEAD
       gh pr create --draft --base main --title "<task title>" --body "<1-2 sentence what/why>

    🤖 Generated with [Claude Code](https://claude.com/claude-code)"
    Note the PR URL. From here on, every commit you push updates this open PR — so commit and push in
    meaningful increments as you work (step 4), NOT once at the very end. Leave the PR a DRAFT for the
    entire run — never mark it ready for review; the user does that when they merge.
   | IF commit only: do NOT push and do NOT open a PR here — you stop at a local commit (step 8).>
3. This repo has project skills in `.claude/skills/`, each a folder containing a `SKILL.md`. Discover
   them dynamically — do NOT assume a fixed list:
      ls .claude/skills/
   Read the YAML frontmatter `description` of each `SKILL.md` (it states when that skill applies),
   then read in full and FOLLOW the body of every skill relevant to the files this task touches. Also
   read `CLAUDE.md` at the repo root — it records known state that will mislead you otherwise
   (theme.json is a Site Editor export overridden by the database; `templates/` is for block templates
   only and PHP partials live in `inc/`; legacy unprefixed/unescaped code exists and must not be
   imitated).
   In particular:
     - ANY PHP work MUST follow `.claude/skills/writing-php/` — escape all output (esc_html, esc_attr,
       esc_url, wp_json_encode), the `quedamos_*` function prefix, the `inc/<module>/` layout plus its
       loader registry in functions.php, WordPress Coding Standards formatting (tabs, `array()`,
       spaces inside parens), no inline `<script>`, no hardcoded URLs.
     - ANY SCSS work MUST follow `.claude/skills/writing-scss/` — no hardcoded hex or raw rem; tokens
       from variables.scss; the three-tier naming system (`.{slug}-page` → `.{name}-section` → BEM
       component); reusable widgets live in `components/`, never re-declared in a page partial; max
       nesting depth 3; wire new partials into style.scss.
     - ANY JS work MUST follow `.claude/skills/writing-js/` — the single `scripts.js` entry, modules
       under `assets/scripts/js/`, element-presence guards, named exports, data from PHP via
       `wp_localize_script` (never hidden DOM elements or inline script).
   "The system is king — never invent." Before picking any style value, function name, class name,
   pattern, or file location, find the existing answer in the codebase and mirror it. If no precedent
   exists, STOP and note it in your summary rather than inventing one.
4. Implement the task. <IF push+PR: as you finish each meaningful chunk, commit and `git push` so the
   open draft PR reflects progress live — don't batch the whole task into one final push. Use the same
   commit-message convention as step 7 for these in-progress commits.>
5. Verify what you can from a code-only checkout:
     - Build the assets with Parcel:
          cd themes/wp-child-theme-template && npm install && npm run build
       `node_modules/`, `.parcel-cache/` and `dist/` are gitignored, so the worktree needs its own
       `npm install`. The build must finish with no errors; Sass errors surface here.
     - Lint every PHP file you touched:
          php -l <file>
     - Re-read your own diff for output-safety and prefix mistakes (the two things writing-php is
       strictest about) before you commit.
   Do NOT commit `node_modules/`, `.parcel-cache/`, or `dist/` (.gitignore already excludes them —
   confirm `git status` is clean of them). Do NOT leave `npm run watch` running.
6. (folder task only) Self-check against the design files before committing — open them again and
   confirm spacing/type/colour/layout match. The orchestrator will compare again in Step 5; don't make
   it do your matching for you.
7. Commit any remaining changes on your branch. Commit message: short imperative sentence describing
   the task, then a blank line and:
      Co-Authored-By: Claude Opus 5 (1M context) <noreply@anthropic.com>
8. <IF push+PR: the branch is already pushed and the draft PR is already open (step 2) — just
    `git push` the remaining commits; the open PR updates automatically. Do NOT open a second PR, and
    do NOT mark the PR ready for review — it stays a draft.
    | IF commit only: stop at the commit. Do not push.>
9. Report back: the clean `worktree/<...>` branch name you renamed to, a 2-4 sentence summary of what
   you changed, the files touched, the Parcel build result and `php -l` results, the PR URL (if
   opened), and any decisions you made that weren't covered by the task file or the project skills (so
   the user can review them).
10. STOP. Do not merge. Do not push to main. Do not touch the database, ACF field groups, plugins, or
    wp-admin settings. Do not try to load a page or run WP-CLI — your worktree is not a WordPress
    install; browser smoke is handled afterward by the orchestrator's verify+fix stage (Step 5). Your
    job ends at a clean, built commit on your branch.
```

---

## Step 4 — Collect the result

The background agent notifies on completion. Do NOT poll or sleep waiting on it. When it reports back,
collect: the branch name, summary of changes, files touched, build/lint result, the PR URL (if opened), and
any open decisions/blockers it flagged.

---

## Step 5 — Review → fix → re-review (the convergence loop)

Once the agent reports its branch committed, **do not check it once and hand it over.** Run a loop that
*drives the branch to done* — not one that merely lists what's wrong.

**The loop in one line:**

> **review** the live branch → if anything's wrong, **fix** it on that same branch and rebuild →
> **re-review** → repeat — until **done** or **provably stuck**.

5b and 5b-design below are a *single review pass*. 5d is the *loop* that re-runs them after each fix.

---

### What "done" means — read this before you loop

The branch is done when **all three** hold:

1. every deterministic acceptance check (**5b-i**) passes,
2. the browser smoke (**5b-ii**, if the task calls for it) is clean, and
3. the design defect list (**5b-design**, for a folder task) is **empty by your eye**.

> **Done is NOT a zero pixel diff.** A live page never pixel-matches a comp exactly (font hinting,
> anti-aliasing, real vs sample content), so a numeric pixel target never terminates. Screenshots are
> *evidence feeding the defect list*; the empty **defect list** is the gate. "Pixel-perfect" here means *no
> genuine template defect remains*, not a zero diff.

---

### The review pass — two checks

**5b-i — Deterministic acceptance checks (ALWAYS).**

Parse the task's `## How do we know it's fixed / Testing notes` section and run every runnable assertion.
Typically:

- `grep` / `grep -rn` assertions (e.g. "`grep -rn 'echo \$' themes/wp-child-theme-template/inc/blog`
  returns nothing"),
- `npm run build` in `themes/wp-child-theme-template/` succeeds and refreshes `dist/styles/style.css` +
  `dist/scripts/scripts.js`,
- `php -l` clean on every PHP file the diff touches (`git diff --name-only main...HEAD -- '*.php'`),
- output-safety and prefix spot-checks over the diff, per `writing-php`.

Run these without the live site where possible, so they don't disturb the user's working tree. Record
pass/fail per assertion. Inherently visual bullets ("the hero sits flush with the content column", "check
the mobile breakpoint") feed the browser pass.

**5b-ii — Browser smoke pass (only if the task's notes call for it).**

Most tasks here change something a visitor sees, so this pass is usually needed: load the pages the task
names, at the viewports it names, and confirm they render as described with no PHP notices and no console
errors. That needs the branch *live* in the docroot.

If the task is purely non-visual (a refactor with a grep assertion, a build-config change) → **skip 5.0's
takeover and 5b-ii**; the deterministic checks are enough.

---

### 5.0 — Announce the takeover and wait (only if the task needs 5b-ii)

The build phase and the deterministic checks are safe alongside the user's own work; the browser pass is
**not** — it commandeers the one docroot the local site serves from. So before bringing the branch live:

1. Post an unmissable message:

   > **VERIFY STAGE STARTING** — I'm about to take over the working tree and the local site (stash your
   > changes, switch branches, rebuild Parcel, load pages). Stop any local edits and branch switches now,
   > and tell me when you're clear.

2. **Wait for the user to confirm they've stepped away** before proceeding to 5a. Do not stash or check
   anything out until they give the go-ahead.

3. After the branch is smoked and the working tree restored (5e), post:

   > **VERIFY STAGE COMPLETE** — working tree back on `<branch>`, restored to how you left it. Safe to
   > resume local work.

---

### Environment (this machine)

- **Site URL.** The local site runs under Local by Flywheel and **the port drifts** — never assume
  `localhost:10033`. Read it from WP-CLI, which needs Local's per-site MySQL socket:

  ```bash
  SOCK="$HOME/Library/Application Support/Local/run/p9tqzUp97/mysql/mysqld.sock"
  WP="$HOME/Local Sites/quedamos/app/public"
  php -d mysqli.default_socket="$SOCK" /usr/local/bin/wp --path="$WP" option get home
  ```

  If the socket path has moved, find it by reading `mysqli.default_socket` out of the site's
  `conf/php/php.ini` under `~/Library/Application Support/Local/run/`. The Local site must be running for
  the socket to exist. If it isn't, STOP and ask the user to start it — do not guess a URL.
- **Browser.** There is no Playwright dependency in this repo and no E2E suite, but Playwright resolves
  through `npx` and Chromium is already cached at `~/Library/Caches/ms-playwright`. So write a **small
  throwaway script** that imports `{ chromium } from 'playwright'`, visits the pages the task names at the
  viewports it names, asserts they render with no console errors, and screenshots to
  `tasks/in-review/<task-name>/verify-artifacts/`. Keep the script under that folder and delete it after.
  Do not add Playwright to the child theme's `package.json` — it is not a theme dependency.
- **Assets.** `dist/` is gitignored, so a branch's built CSS/JS is never committed. Making a branch "live"
  means checking it out **and rebuilding**: `cd themes/wp-child-theme-template && npm install && npm run
  build`. Restoring means checking the old branch back out **and building again**. Enqueue versions come
  from `filemtime` on the `dist/` files (`quedamos_asset_version`), so a rebuild busts the browser cache by
  itself.
- **Page caching.** LiteSpeed Cache, Perfmatters and the WebP converter are all active plugins — a cached
  HTML page can hide a change completely. Before trusting a page, purge LiteSpeed
  (`… wp litespeed-purge all`, socket-prefixed as above) or load the URL with a cache-busting query string,
  and confirm the response isn't served from cache (`curl -sI` and check for `x-litespeed-cache`).
- **PHP notices.** Read `wp-content/debug.log` (if `WP_DEBUG_LOG` is on) before and after the pass, and
  treat any new notice or warning from a file the diff touched as a failure.
- **Auth.** Almost everything worth smoking here is public, so smoke anonymously. If the task needs a
  logged-in view or a wp-admin screen, do NOT guess credentials — ask the user how to log in locally.

---

### 5a — Bring the branch live in the docroot (only if the task needs 5b-ii)

Work in the main repo (the docroot at `~/Local Sites/quedamos/app/public/wp-content`), **not** the
worktree:

1. Resolve the site URL (see Environment) and confirm it's reachable:
   `curl -s -o /dev/null -w "%{http_code}" "$HOME_URL"`. If it's not `200`, STOP and ask the user to start
   the Local site — do not guess.
2. Note the current branch (`git branch --show-current`) so you can restore it.
3. Stash any uncommitted working-tree changes: `git stash push -u -m dispatch-verify`.
4. If a worktree still holds the branch, remove it first (you can't check the same branch out twice):
   `git worktree list`, then `git worktree remove <path> --force`.
5. `git checkout worktree/<task-filename-without-extension>` — the clean name the agent renamed to (also in
   its report). If the rename didn't happen, fall back to the auto-named `worktree-agent-<id>` from the
   report or `git worktree list`.
6. Rebuild assets into the docroot: `cd themes/wp-child-theme-template && npm install && npm run build`.
7. Purge the page cache (see Environment). The live site now reflects the branch.

---

### 5b — Run the review pass

Run 5b-i (deterministic) and, if applicable, 5b-ii (browser smoke).

Treat a page that never loads, a console error, a new PHP notice, or a failed assertion as a **real
failure**, not a flake. If the task's notes are empty or unrunnable, run a read-only page-load smoke of any
pages it mentions, screenshot, and **flag the thin acceptance criteria** in the summary rather than
silently passing.

---

### 5b-design — Design-match review (folder tasks with design files only)

A folder task carries design files *because the build must match them*. So it is never "verified" on the
functional checks alone — those confirm a feature *works*, not that the page *looks right*. After 5b
passes, do a **section-by-section visual comparison** of the live page against the design files, and fold
every genuine discrepancy into the 5d loop. A plain-file task with no design files skips this step.

The design reference comes in **two shapes** — handle whichever the folder ships (a folder may have both):

- **A static image comp** — a PNG/JPG screenshot or mockup. There's nothing to render: the image *is* the
  `design-*.png` reference. Note the width it was drawn at (or the viewport the brief specifies) so you can
  capture the live page to match.
- **An HTML/CSS handoff** — a renderable `*.html` mockup. Render it to a PNG yourself (next step).

**1. Get a `design-*.png` and a matching `live-*.png`** in
`tasks/in-review/<task-name>/verify-artifacts/`:

- **Live page** — screenshot it with the throwaway Playwright script at the comp's width / the brief's
  viewport → `live-*.png`.
- **Design** —
  - *static image comp* → copy/use the supplied PNG/JPG directly as `design-*.png` (no rendering);
  - *HTML handoff* → render the `file://` URL to the handoff HTML with Playwright at the same viewport →
    `design-*.png`.

**2. Read every screenshot** (design and live) and walk the page top to bottom — header, hero, each body
section, sidebars, CTAs, footer — listing each way the live build diverges from the design. Back visual
hunches with DOM facts when useful (a throwaway Playwright probe to count elements / read computed
styles). **Reading the screenshots is the call** — there is no pixel-diff helper in this repo, and adding
one would mean pulling `pixelmatch` + `pngjs` into the theme's build deps, which is not this skill's to
decide. Raise it as its own task if the eyeball pass keeps missing things.

**3. Rule out two false-positive classes before logging a defect** — only genuine *template* defects get
fixed:

- **Site-wide pre-existing behaviour.** Capture the same thing on a sibling/parent page. If it behaves
  identically there, it predates this task — note it, don't "fix" it. This repo has legacy code that
  breaks its own conventions; a task is not a licence to sweep it.
- **Content-specific differences.** The mock's sample copy/images won't match the real page's content.
  "Different words/images/section count because the live page differs" is not a defect; differences in the
  **template's chrome, layout, spacing, styling, element duplication, or data-mapping/counts vs what the
  brief specified** are.

**Output:** a **defect list** (each item: what's wrong, where, design-vs-live evidence).

- Empty list → the build matches the design; record `design-verified` and go to **5c**.
- Non-empty → go to **5d** and fix every item.

---

### 5c — On PASS

Record `verified` (and, for a folder task, `design-verified` + the screenshot paths) and any browser-smoke
screenshot path, then go to **5e**.

A folder task only reaches PASS once 5b-design's defect list is empty.

---

### 5d — The fix loop (runs whenever 5b / 5b-design found anything)

This is the body of the convergence loop. Each iteration is **fix → rebuild → re-review**:

**1. Fix.** Spawn an `Agent` **in the docroot, on the checked-out branch** (NOT a new worktree — the branch
must stay live so re-review hits the same site). Give it:

- the task file path,
- the design files path (folder task — the fix must match the same handoff),
- **the full set of outstanding findings** — for an assertion failure, the assertion + actual-vs-expected;
  for design discrepancies, the **defect list** plus the `design-*.png` / `live-*.png` paths.

Tell it to:

- pull target values (spacing, colour, size, position, counts) from the design, reconciled against the
  tokens in `assets/styles/scss/variables.scss` per `writing-scss` — **never hardcode a hex or a raw rem to
  chase pixels**;
- discover and follow the relevant `.claude/skills/` (same dynamic discovery as Step 3) + "system is king";
- fix every item it can, then rebuild (`npm run build` in the child theme) and re-run the failed
  deterministic checks locally;
- commit the fix on the same branch (message `fix: <what> — verify`) — these commits push to the same
  branch, so the open draft PR updates automatically (leave it a draft — never mark it ready for review,
  and never push to `main`);
- if an item needs a product decision the brief/design doesn't settle, fix what's unambiguous and **flag
  the rest** rather than inventing.

**2. Re-review.** Re-run 5b for the checks that failed, and **re-run 5b-design** — purge the page cache,
capture fresh `live-*.png` each round and **rebuild the defect list from scratch** (don't assume an item is
fixed; re-observe it). Note how many items closed vs the previous round.

**3. Decide — loop, or exit.** Evaluate against the "done" definition at the top of Step 5:

- **DONE** (all acceptance checks pass AND the defect list is empty) → record `verified` /
  `design-verified` (matched first try) / `fixed (N iterations)` and go to **5e**. This is the only happy
  exit.
- **Otherwise loop again from step 1** — UNLESS one of these stop conditions trips, in which case stop and
  leave the branch committed for manual review:
  - **No progress** — a round closed **zero** items vs the previous round's list (the fix agent is stuck) →
    record `FAILED — needs human` for the remaining items.
  - **Blocked on a decision** — the only items left need a product call the brief/design doesn't settle, or
    they need wp-admin/DB work this skill doesn't do (an ACF field, a `wp_global_styles` override, page
    content) → record `partially fixed — N need human` and surface those items.
  - **Hard cap** — a safety backstop of **5 review rounds**, so an oscillating loop can't run forever or
    burn tokens → record `FAILED — needs human` with the remaining list.

Track the round count and per-round defect list so you can tell "still closing items, keep going" from
"stuck, stop". Pixel-perfect is the *goal*; these exits stop the loop short of it when the goal isn't
reachable unattended.

---

### 5e — Restore the docroot (only if you checked out the branch in 5a)

1. `git checkout <original branch>`,
2. rebuild so `dist/` matches the restored source (`npm run build` in the child theme),
3. `git stash pop` if you stashed in 5a,
4. purge the page cache once more so the user isn't served the branch's HTML.

Confirm `git status` matches the pre-verify state. **Always restore, even if verification failed.**

---

## Step 6 — Present the summary

When the branch has been verified, report the outcome:

| Task file | Branch | Verify | Notes |
|-----------|--------|--------|-------|
| `tasks/in-review/escape-article-hero-output-p-2/…` | `worktree/escape-article-hero-output-p-2` | verified | PR: <url> |

Verify-status values: `verified` / `design-verified` (matched the design with no fixes) · `fixed (N)`
(self-fixed after N iterations) · `partially fixed — N need human` (most fixed; the rest stuck or blocked
on a decision) · `FAILED — needs human` (assertion never met / no progress — review the branch).

Then remind the user:

> "The task is committed and verified (or flagged), on a draft PR into `main` (or stopped at a commit). It
> is not merged — `main` is protected, so review it, then run `/merge-task` to squash it into `main` and
> file it under `tasks/done/`."

---

## Step 7 — Move the task to in-review and scaffold the to-fix folder

Move the dispatched task from `tasks/in-progress/` (where Step 2b put it) to
`tasks/in-review/<task-name>/`, where `<task-name>` is:

- the task `.md` filename without its extension (e.g.
  `tasks/in-review/escape-article-hero-output-p-2/`), or
- for a **folder task**, the folder's own name.

Rules for the move:

- Create `tasks/in-review/` if missing. **Do not** nest under a `dispatch/` or `in-progress/` subfolder.
- For a folder task, move the **whole folder** (task `.md` + design files) as a unit — don't split the
  brief from its design handoff.
- Leave the un-dispatched tasks where they are, and leave `tasks/in-progress/` empty for this task once the
  move is done — nothing may be left behind in it.

The destination `tasks/in-review/<task-name>/` folder may **already exist** — Step 5 wrote
`verify-artifacts/` into it during verification. So move the brief + design files *into* the existing
folder, alongside `verify-artifacts/` — don't `mv` the source folder on top of it (that nests it as
`tasks/in-review/<task-name>/<task-name>/`).

> **Move caution:** `tasks/` is gitignored, so use a plain `mv` throughout — a `git mv` will fail or move
> nothing. After the move, `ls`/`find` the destination to confirm the task `.md` arrived.

Then **scaffold a `to-fix/` folder inside the moved task** holding a single blank `to-fix.md` for the user
to fill in after their manual review:

- Path: `tasks/in-review/<task-name>/to-fix/to-fix.md`.
- Seed it with exactly the blank template below — a heading (use the task's title) and the one-line
  instruction, then an empty numbered list. Nothing else; the user writes the punch list.

  ```markdown
  # To fix — <task title>

  > Manually review the branch, then list each issue below as a numbered item with a clear,
  > self-contained description. Drop any screenshots into this same `to-fix/` folder and reference them in
  > the matching item. Leave the list empty if nothing needs fixing.

  1.
  ```

A dispatched task lands in `in-review`, **not** `done`: the branch is never merged by this skill, and the
run often surfaces flagged items (wp-admin/ACF work, decisions to confirm) that need the user's follow-up.
After the manual review the user either fills in `to-fix/to-fix.md` to drive a fix pass, or — if nothing
needs fixing — runs [merge-task](../merge-task/SKILL.md), which squash-merges the branch into `main` and
moves the task on to `tasks/done/`.

---

## Branch naming

**One clean name everywhere.** The harness *creates* the worktree branch as `worktree-agent-<id>` — you
can't choose the name at creation time — but the agent immediately **renames** it (Step 3 instruction, step
2) to a clean, human-readable name derived from the task file:
`worktree/<task-filename-without-extension>`. From that point the local branch, the remote branch, and the
PR head all share that single name. The orchestrator already knows the name (it derives it from the task
filename), so it uses `worktree/<task-name>` directly for the Step 5 checkout and everywhere else.

The `worktree/` prefix is deliberate: the repo's hand-authored branches are `feature/*`, so the prefix says
at a glance which branches came out of a dispatch run.

The clean name maps straight from the task file:
- `tasks/dispatch/escape-article-hero-output-p-2.md` → branch `worktree/escape-article-hero-output-p-2`
- folder task `tasks/dispatch/redesign-course-card/` → branch `worktree/redesign-course-card` (use the task
  `.md` filename inside the folder, sans extension)

The worktree *directory* keeps its harness-given `agent-<id>` name — only the branch is renamed; the two
are independent.

---

## What not to do

- Do not dispatch more than one task per run. List the staged files, dispatch the one the user picks, and
  leave the rest queued.
- Do not read the staged task files' contents before the user has chosen which to dispatch (Step 1) — list
  names only, ask, then read just the chosen one.
- Do not glob `*.md` when listing staged tasks (Step 1) — a task can be a **folder** (sometimes named with
  a misleading `.md` suffix). `ls` the directory so folder tasks show up.
- Do not ignore or skim a folder task's design files — they exist *because* the implementation must match
  them. Pass them to the spawned agent (Step 3) and any fix agent (Step 5d), and run the 5b-design
  comparison.
- Do not assume the worktree base is current — the agent's first action MUST be
  `git fetch origin main && git reset --hard origin/main` (Step 3 instruction block). A branch left behind
  `main` produces stale paths and an unmergeable PR.
- Do not push to `main` or merge anything — `main` is protected and the merge is the user's decision after
  the Step 5 verify+fix stage. The agent pushes only its own `worktree/…` branch.
- Do not expect the worktree to be a working WordPress install — no `wp-config.php`, no core, no parent
  theme, no plugins, no uploads. The spawned agent builds and lints; it never loads a page or runs WP-CLI.
- Do not defer the PR to the end of a push+PR run — open it as a **draft** the moment the branch is renamed
  (Step 3, instruction step 2), before any code is written, so the user can watch commits land; the
  implementation, the final push, and the Step 5 fixes all push to that same open PR.
- Do not mark the PR ready for review, ever — it stays a draft for the whole run (agent AND fix loop).
- Do not open a second PR in step 8 — the draft opened in step 2 is the only PR; step 8 just pushes the
  remaining commits onto it.
- The spawned coding agent must not touch the database, ACF field groups, plugin settings, Site Editor /
  `wp_global_styles`, or page content. (The orchestrator's Step 5b-ii browser pass *does* require the Local
  site up — it checks first and asks the user to start it if down.)
- Do not trust a page you haven't cache-busted — LiteSpeed and Perfmatters will happily serve the old HTML
  and make a working change look broken (or a broken one look fine).
- Do not assume `localhost:10033` — Local reassigns the port. Read it from `wp option get home` with the
  socket prefix.
- Do not skip the Step 5e restore — leaving the docroot on the worktree branch (or with a stale `dist/`)
  corrupts the user's working state and the site they're looking at.
- Do not run the Step 5 fix loop in a fresh worktree — fixes happen on the branch currently live in the
  docroot so re-verify hits the same site.
- Do not stop the Step 5 loop with defects merely *listed* — its job is to fix → rebuild → re-review until
  **done** or a real stop condition trips (no-progress / blocked-on-decision / the 5-round cap). Handing
  back a known-imperfect build with a punch list is not "done".
- Do not define done as a zero pixel diff — a live page never pixel-matches a comp exactly, so that target
  never terminates. Done is an **empty defect list by eye**.
- Do not add Playwright, `pixelmatch` or `pngjs` to the child theme's `package.json` to serve a verify run
  — Playwright comes through `npx`, and a pixel-diff helper is its own task to propose.
- Do not invent style values, function names, class names, or file locations inside a spawned agent —
  `writing-php`, `writing-scss` and `writing-js` apply inside worktrees exactly as on `main`. No hardcoded
  hex, no unescaped output, no unprefixed functions, no inline `<script>`.
- Do not leave a dispatched task sitting in `tasks/dispatch/` — move it to `tasks/in-progress/` before
  spawning (Step 2b), or the next run will offer a task that's already being worked.
- Do not hand an agent a `tasks/dispatch/…` path after the Step 2b move — the task file and its design
  files now live under `tasks/in-progress/<task-name>/`.
- Do not split a folder task when moving it (Step 2b or Step 7) — the design files and screenshots travel
  with the brief.
- Do not leave the task in `tasks/in-progress/` at the end of the run — Step 7 moves it on to
  `tasks/in-review/`.
- Do not skip scaffolding the blank `to-fix/to-fix.md` in Step 7 — the user fills it in to drive a
  follow-up fix pass.
- Do not poll/sleep waiting for the agent — it notifies on completion.
- Do not skip the pre-spawn questions: (1) prototype or not, and (2) — for non-prototypes — commit vs
  push+PR.
- Do not create a branch or use a worktree for a prototype task — build it in place on the current branch.
- Do not start running if `tasks/dispatch/` is empty (and no files named) — tell the user first.
