---
name: create-task
description: Capture a task by asking the user just enough questions to produce a clean, concise, developer-readable brief — title, what + why, acceptance criteria — then write it to a chosen folder under `tasks/backlog/` and optionally open a GitHub issue. Previews the file + path before writing. One task per invocation. For the Quedamos wp-content repo (WordPress FSE, twentytwentyfour parent + wp-child-theme-template child, Parcel build). Trigger phrases: "create a task", "capture this as a task", "/create-task".
---

# Create a task (Quedamos wp-content)

Use this skill to capture a single task that a developer can pick up cold — read it, understand it, and
have everything they need to start. Ask the user **enough questions** to get there, then write a clean,
concise markdown brief into `tasks/backlog/`, and offer to mirror it onto GitHub.

Tone throughout: terse, developer-readable, concrete over abstract, no fluff, no emoji (unless the user
already uses them). "Escape the `href` in `inc/blog/article-hero.php`" beats "improve output safety." The
brief this writes is what [dispatch-task](../dispatch-task/SKILL.md) later picks up.

Create **one task per invocation.**

## Step 1 — Ask enough questions

Ask conversationally in **free text** (these are intent questions — do **not** offer multiple-choice
options for them). Group related questions into one message rather than asking one at a time. Pull
whatever you can from the current conversation so the user doesn't retype context — but confirm anything
you inferred.

Gather:

1. **Title** — short, imperative. Becomes the filename slug and the H1.
2. **What + why** — what needs to happen and the reason. Push for enough that a developer who hasn't seen
   this chat can act: which block template, `inc/` module, SCSS partial, script module, shortcode or hook?
   Current behaviour vs. desired? If the user is terse, ask the follow-ups that close the gaps — don't
   write a vague task.
3. **Acceptance criteria** — how we'll know it's done. A visible state on a named page, a Parcel build
   that passes (`npm run build` in `themes/wp-child-theme-template/`), `php -l` clean on the files
   touched, a `grep` that returns nothing, a clean `debug.log` with `WP_DEBUG` on, a smoke-test checklist
   of pages and breakpoints. **When the task changes something a visitor sees, name the pages and the
   viewports to smoke** — that list is what `/dispatch-task`'s browser pass runs.
4. *(optional)* **Scope pointers / out of scope** — files or areas the work touches (e.g.
   `themes/wp-child-theme-template/inc/…`, `assets/styles/…`, `templates/*.html`), a sibling module to
   mirror, or anything to leave alone. Capture if it surfaces naturally; don't force it.

**Priority:** default `p-2`. Only ask if the user signals it isn't normal ("urgent", "blocker", "low
priority", "nice to have").

Don't over-interrogate — ask the smallest set of questions that yields a brief a developer could start
from. When you have that, stop and move to the preview.

## Step 2 — Preview before writing

In one message show:

- The full proposed file content (the shape below).
- A note that you'll ask where it goes once they're happy with the content.

Wait for the user to confirm or adjust the content. Only proceed once they're happy.

### File shape

```markdown
# {Task title — short, restates what to do}

## Description

{What + why, in 2–6 sentences. Link to relevant code with markdown links (`[path](path)` or
`[path:line](path#Lline)`) so the task is actionable cold. Name the template / `inc/` module / SCSS
partial / script module explicitly. State current vs. desired behaviour. Note any traps or out-of-scope
constraints surfaced in the conversation.}

## How do we know it's fixed / Testing notes

{Concrete acceptance criteria. A grep/command that returns nothing, `php -l` clean on the files touched,
`npm run build` passing in the child theme, a smoke-test checklist of pages and viewports, a UI state
that should appear, a clean `debug.log`. Lead with the "done" definition; a checklist is fine for
multi-step work.}
```

No "Status", "Owner", or "Created on" metadata — git tracks that. Don't add a checklist for a single-step
task. (Note: this repo's house heading is **`## How do we know it's fixed / Testing notes`** — there is
no separate `## Verification` heading. `/dispatch-task` parses that section as the acceptance criteria.)

### Design files (screenshots, mockups)

When the task ships with screenshots or a comp, frame them in the brief as **direction, not gospel** — the
design system is always king ([writing-scss](../writing-scss/SKILL.md)). Where a comp disagrees with an
existing component or a token in `variables.scss` (colour, radius, spacing, type size, hover state), the
system wins and the difference is *reported*, not overridden with a hardcoded value or a page-scoped
override. Say that in the brief, and name any place the comp would require changing a shared component in
`assets/styles/scss/components/` so it lands as an explicit decision rather than a silent override. Don't
write "the design files are the spec — match them" — that licenses exactly the invented values the
never-invent rule forbids.

Check the comp for details that can only be built by adding behaviour — a filter, a count, a toggle,
anything live. On a task the user has called visual, say plainly that those are not to be built, and list
them for a follow-up decision.

## Step 3 — Ask where it goes

Once the content is approved, ask which folder under `tasks/backlog/` it should live in. Existing buckets
(offer these, and allow a new one):

- `architecture` · `frontend` · `security` · `perf` · `tech-debt` · `bugs`

This is a routing choice with known options, so AskUserQuestion (with an "Other" for a new folder) is
appropriate here.

**Filename:** `<descriptive-kebab-name>-p-<N>.md` — 3–7 kebab-case words, `-p-<N>` priority suffix
(1 = highest, 5 = lowest), default `p-2`. No `task-1.md` / `cleanup.md`. Write the file to
`tasks/backlog/<folder>/<name>.md`.

`tasks/` is gitignored (only the `.gitkeep` skeleton is tracked) — a task brief is local working state,
so there is nothing to commit after writing one.

## Step 4 — Offer GitHub

After writing the file, ask whether they also want it as a GitHub issue.

- If **no**, you're done — it stays a local backlog task.
- If **yes**, open a plain issue against this repo's `origin` remote with `gh`. Infer the repo from
  `git remote get-url origin`; use the task's `## Description` and
  `## How do we know it's fixed / Testing notes` as the issue body and the H1 as the title. If the repo
  uses labels, mirror the priority (`p-N` → the nearest label tier) only when a matching label already
  exists — never invent labels.

Opening an issue is outward-facing — confirm the exact repo, title, and any labels with the user before
running `gh`.

## After creating

Tell the user the file path in one sentence (and the issue URL if one was created). Don't dump the file
contents back at them — the file is the artifact.

## What not to do

- Don't write a vague task — if the answers don't yet let a developer start cold, ask one more question.
- Don't offer multiple-choice options for the intent questions in Step 1 — those are free-text. (The
  folder choice in Step 3 and the GitHub choice in Step 4 are the exceptions — those have known options.)
- Don't ask about priority unless the user signals it isn't normal — default `p-2`.
- Don't open a GitHub issue without explicit confirmation, and never invent labels that don't already
  exist.
- Don't lump two tasks into one file, and don't create more than one task per invocation.
- Don't write to `tasks/dispatch/` — that folder is the staging area `/dispatch-task` reads from. Backlog
  tasks live under `tasks/backlog/`; move one into `tasks/dispatch/` only when it's ready to be worked.
