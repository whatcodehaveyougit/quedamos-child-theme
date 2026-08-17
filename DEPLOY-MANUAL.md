# Deploying by hand — the browser-only route

There is no CI on this project. `main` is code, not the live site: merging ships nothing. This file is the
**how**, for an operator with only a wp-admin login and a local checkout — no SSH, no WP-CLI on live.

[DEPLOY-LIST.md](DEPLOY-LIST.md) stays the **what**: it is the checklist of outstanding live steps and the
source of truth for which release needs which step. Read it first, then use this file to carry each one out.
Every WP-CLI snippet in that file has a wp-admin equivalent here.

## Run the steps in this order

The order is not cosmetic. Between the code landing (step 2) and the header swap (step 4), **live has no
mobile menu** — the old overlay's PHP filter was deleted along with the code it replaced. Keep that window
to minutes, in one sitting.

| # | Step | Where | Skippable? |
|---|---|---|---|
| 1 | Take the backups | wp-admin | No — steps 3 and 4 destroy DB records |
| 2 | Build and upload the theme | local + wp-admin | No |
| 3 | Reset the customized templates | Site Editor | Only if no row says `Customized` |
| 4 | Point the Header part at the mobile-menu block | Site Editor | No, in the same sitting as step 2 |
| 5 | Purge LiteSpeed | admin bar | No |
| 6 | Content and user edits | block editor + Users | Per [DEPLOY-LIST.md](DEPLOY-LIST.md) |
| 7 | Verify on the front end | browser | No |

---

## 1. Take the backups

Steps 3 and 4 delete or overwrite database records, and neither has an undo. Copy the current markup into a
text file on your machine before you touch anything.

- **Appearance → Editor → Patterns → Template Parts → Header** → open it → **⋮ (Options) → Code editor** →
  select all, copy, save as `header-part-backup.txt`. This is the browser equivalent of
  `wp post get <id> --field=post_content > header-part-backup.txt`.
- Same again for any template you are about to reset in step 3 — open it, **Code editor**, copy.

Keep these until the deploy is verified. Restoring means pasting the text back into the same code editor.

## 2. Build the bundle and upload the theme

`dist/` is gitignored ([.gitignore](.gitignore)), so the built CSS and JS never travel with the repo — and
without a way to pull on the server, neither does the theme code. So the theme goes up as a zip.

Locally:

```bash
cd "themes/wp-child-theme-template"
npm run build
ls dist/styles/style.css dist/scripts/scripts.js   # both must exist before you zip
cd ..
zip -r ~/Downloads/wp-child-theme-template.zip wp-child-theme-template \
  -x "*/node_modules/*" -x "*/.parcel-cache/*" -x "*.DS_Store"
```

`wp-child-theme-template` must stay the **top-level folder inside the zip** — that folder name is the theme's
identity, and a renamed one uploads as a second, inactive theme instead of replacing the live one.

Then in wp-admin: **Appearance → Themes → Add New → Upload Theme** → choose the zip → **Install Now** →
WordPress shows a side-by-side comparison of the current and uploaded versions → **Replace current with
uploaded**.

Uploading replaces the whole theme directory, so anything living only on live inside
`themes/wp-child-theme-template/` is lost. Nothing should — the theme holds no uploads or generated content
beyond `dist/`, which the zip carries.

Cache-busting needs no thought: `quedamos_asset_version()` in
[inc/assets/assets.php](themes/wp-child-theme-template/inc/assets/assets.php) versions each asset by
`filemtime()`, so a fresh upload changes the `?ver=` automatically. The **page** cache is a different matter —
that is step 5.

## 3. Reset the customized templates

The trap in [CLAUDE.md](CLAUDE.md): a `wp_template` record in the database **wins over** the matching
`.html` file in the theme. Live can therefore have the new code and still render the old page.

**Appearance → Editor → Templates → Manage all templates.** Any row flagged `Customized` is a database record
overriding the repo. For each one that the theme now ships, open **⋮ → Reset**.

Reset deletes the `wp_template` post — exactly what `wp post delete <id> --force` does — and hands rendering
back to the theme file. If a row shows as a user-created template instead, the action reads **Delete**; either
one removes the record.

Identify rows by what they render, never by ID — live's IDs differ from local's.

The templates that ship in the repo, and so must not be overridden:

| Repo file | Renders | Live row is usually titled |
|---|---|---|
| [templates/home.html](themes/wp-child-theme-template/templates/home.html) | `/blog` | Blog Home |
| [templates/category.html](themes/wp-child-theme-template/templates/category.html) | category archives | Category / Categories |
| [templates/single.html](themes/wp-child-theme-template/templates/single.html) | single posts | Single Post |

Leave every other `Customized` row alone — those are real customizations of parent-theme templates, not
overrides of ours.

Done when: no row in the table above says `Customized`, and each reads as coming from the theme.

## 4. Point the Header part at the mobile-menu block

The mobile navigation is the theme's own block
([inc/navigation/mobile-menu/block.json](themes/wp-child-theme-template/inc/navigation/mobile-menu/block.json)),
and it renders only once the **Header** template part references it. That part lives in the database.

**Appearance → Editor → Patterns → Template Parts → Header** → open → **⋮ (Options) → Code editor**
(`⌘⇧⌥M`). Find the mobile navigation block comment — the one carrying
`"className":"q-navigation-mobile"` — and replace the whole comment with:

```
<!-- wp:quedamos/mobile-menu {"ref":N} /-->
```

**You do not need to look up live's menu ID.** `N` is the `"ref"` already sitting in the
`q-navigation-mobile` comment you are replacing — copy it across unchanged. (`ref` is a database ID; live's is
not the local `5`. Get it wrong and the block falls back to the site's most recent `wp_navigation` post, so a
mistake degrades to "possibly the wrong menu" rather than an empty header.)

Leave the **desktop** navigation block (`className: q-navigation-desktop`, `overlayMenu: never`) exactly as
it is — it keeps its own `ref`.

Save.

Two traps:

- The block has **no editor script**, so switching back to visual mode shows it as an unsupported block. That
  is expected and it renders correctly on the front end. Do **not** click *Attempt block recovery*, and do not
  re-save the part from visual mode.
- `header .q-navigation-mobile { display: none }` is still in the CSS on purpose, so the old overlay cannot
  appear beside the desktop nav in the window between step 2 and this step. Don't remove it until every
  environment is swapped.

## 5. Purge LiteSpeed

Admin bar → **LiteSpeed Cache → Purge All** (or **LiteSpeed Cache → Toolbox → Purge All**). The browser
equivalent of `wp litespeed-purge all`.

A cached header hides step 4 completely. Purge after the header swap, not before.

## 6. Content and user edits

These are per-release and listed in [DEPLOY-LIST.md](DEPLOY-LIST.md) under **Pending** — check it rather than
this file for what is outstanding. Two that recur:

**Page content in the block editor.** Editing the page is always the alternative to a `wp eval-file` script.
Where a step asks for a CSS class, it goes in the sidebar under **Advanced → Additional CSS class(es)** on the
specific block named — for the homepage numbers band, `count-up` on each **number** paragraph, not the label.

**A user's display name** (Users → All Users → the user). The gotcha is that it takes two saves: WordPress only
offers display-name options built from fields it already has, so set **First name** and **Last name**, click
**Update Profile**, then reload and pick the full name from **Display name publicly as** and update again.
Names matter beyond the byline — `quedamos_author_photos()` in
[inc/blog/article-author.php](themes/wp-child-theme-template/inc/blog/article-author.php) keys the author photo
off the display name, so a near-miss falls back to the site icon.

## 7. Verify on the front end

Logged out, or in a private window — an admin session bypasses the page cache and will show you a healthier
site than your visitors get.

- [ ] Homepage and one course page load with styling intact
- [ ] `view-source:` shows `dist/styles/style.css?ver=` followed by a 10-digit number — a missing `?ver=`
      means `dist/` did not make it up, and the site is running unstyled or on a stale bundle
- [ ] `/blog` shows the title, intro and filter pills; a pill click filters the list and changes the URL, and
      loading that URL directly gives the same page
- [ ] One single post renders, with the author bar and photo
- [ ] At 390px wide: the hamburger shows and the panel opens with every link
- [ ] At 1280px: the desktop nav is unchanged, and no width shows both navs at once
- [ ] Every box ticked for this release in [DEPLOY-LIST.md](DEPLOY-LIST.md)

Then move the finished items to the **Done** section of [DEPLOY-LIST.md](DEPLOY-LIST.md) and commit that, so
the next deploy starts from an honest list.

## If something looks wrong

In this order, because each is cheaper than the next:

1. **Purge LiteSpeed again** and recheck logged out. Most "the deploy didn't work" is a cached page.
2. **Check for a `Customized` template row** you missed in step 3 — the symptom is a page rendering the old
   design perfectly while the new code is definitely on the server.
3. **Check `dist/` uploaded** — the `?ver=` check in step 7. A theme zip built before `npm run build` installs
   cleanly and looks broken.
4. **Restore from step 1's backups** — paste the saved markup back into the same code editor.

`theme.json` has the same database-wins problem as templates: the matching `wp_global_styles` record overrides
it, so a global style change may need clearing in the Site Editor before it shows. See
[CLAUDE.md](CLAUDE.md) — verify in the browser, never assume.
