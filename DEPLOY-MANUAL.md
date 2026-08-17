# Deploying by hand — the browser-only route

There is no CI on this project. `main` is code, not the live site: merging ships nothing. This file is the
**how**, for an operator with only a wp-admin login and a local checkout — no SSH, no WP-CLI on live.

[DEPLOY-LIST.md](DEPLOY-LIST.md) stays the **what**: it is the checklist of outstanding live steps and the
source of truth for which release needs which step. Read it first, then use this file to carry each one out.
Every WP-CLI snippet in that file has a wp-admin equivalent here.

## Run the steps in this order

The order is not cosmetic. Between the code landing (step 2) and the header swap (step 4), live keeps its
old `core/navigation` header. That still works — the transitional CSS shipped with this release exists
precisely to hold that window together — but live gets **none** of the navigation change until step 4,
including the **About and FAQs links that 404 on live's desktop nav today**. Keep the window to one sitting.

| # | Step | Where | Skippable? |
|---|---|---|---|
| 1 | Take the backups | wp-admin | No — steps 3 and 4 destroy DB records |
| 2 | Build and upload the theme | local + wp-admin | No |
| 3 | Reset the customized templates | Site Editor | Only if no row says `Customized` |
| 3b | Check global styles are not overriding `theme.json` | browser + Site Editor | Only if the width check already reads `1100px` |
| 4 | Point the Header part at the site-navigation block | Site Editor | No, in the same sitting as step 2 |
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

## 3b. Check global styles are not overriding `theme.json`

Same trap as step 3, different record. `theme.json` is a **Site Editor export**, and WordPress merges it with
the `wp_global_styles` row in the database — and **the database wins**. So the theme can carry a change that
never appears on the front end.

Two settings in this release live in that file, and both fail silently if overridden:

| Setting | Value the theme ships |
|---|---|
| Content / wide width | `1100px` (was `1280px`) |
| h2 size | `clamp(2rem, 3.5vw, 3.5rem)` (was capped at `4rem`, the same as h1) |

### Check it — no console needed

Open any live page, then **View source** (`⌘⌥U` in Chrome/Safari, `Ctrl+U` on Windows) and search the source
with `⌘F` for:

```
content-size
```

You will find a line like `--wp--style--global--content-size: 1100px;`.

- **`1100px`** → the theme file is winning. Nothing to do; skip the rest of this step.
- **`1280px`** → the database is overriding `theme.json`. Fix it below.

If you would rather use the console: right-click → **Inspect** → **Console**, paste this and press Enter:

```js
getComputedStyle(document.documentElement).getPropertyValue('--wp--style--global--content-size')
```

Do this on the **front end**, not inside wp-admin — the admin screens do not load the site's global styles.

### Fix it — Site Editor

**Appearance → Editor → Styles**, then the **⋮ (options)** menu at the top right → **Reset styles** (the label
varies by WordPress version — "Revert to theme defaults" or "Reset to defaults"). If that item is greyed out
or missing, there are no customizations and the override is not what is wrong.

**Read this before clicking it.** Reset clears **every** global style customization for the theme, not just the
width — colours, typography, spacing, the lot. Before resetting, open **Styles → Revisions** to see what is in
there. If the record holds styling that is genuinely wanted, do **not** reset; use the surgical route instead.

### Surgical alternative — set the values in the Site Editor instead

If resetting would lose wanted styling, set the two values by hand and leave the record in place:

- **Styles → Layout → Content width** → `1100`, and **Wide width** → `1100`
- **Styles → Typography → Headings → H2** → size `clamp(2rem, 3.5vw, 3.5rem)`, or the custom value the size
  control allows

This works, but understand the cost: the database keeps overriding `theme.json` for those settings **forever**,
so a future change to the file will not show up either, and the two sources drift. Prefer the reset when you
can afford it, and note in [DEPLOY-LIST.md](DEPLOY-LIST.md) if you took this route.

Done when: view source shows `--wp--style--global--content-size: 1100px`, and an h2 on a live post measures
about 50px on desktop rather than about 58px.

## 4. Point the Header part at the site-navigation block

The header navigation — **desktop row and mobile panel both** — is the theme's own block
([inc/navigation/site-navigation/block.json](themes/wp-child-theme-template/inc/navigation/site-navigation/block.json)),
and it renders only once the **Header** template part references it. That part lives in the database.

One block replaces the **two** `core/navigation` blocks the header carries today.

**Appearance → Editor → Patterns → Template Parts → Header** → open → **⋮ (Options) → Code editor**
(`⌘⇧⌥M`). You are looking for two comments inside the same group:

- the desktop nav — `"className":"q-navigation-desktop"`, `"overlayMenu":"never"`
- the mobile nav — `"className":"q-navigation-mobile"`

**Delete both**, and put this single comment in their place:

```
<!-- wp:quedamos/site-navigation {"ref":N} /-->
```

**You do not need to look up live's menu ID.** `N` is the `"ref"` already sitting in the two comments you
are deleting — they share it. Copy it across unchanged. (`ref` is a database ID; live's is not the local
`5`. Get it wrong and the block falls back to the site's most recent `wp_navigation` post, so a mistake
degrades to "possibly the wrong menu" rather than an empty header.)

Save.

Then switch back to visual mode and check it: the block **does** have an editor script, so it renders as a
proper **Site navigation** block with a **Menu** picker in the right-hand sidebar. If instead you see
"unsupported block", the theme code has not deployed — go back and fix step 2 rather than clicking
*Attempt block recovery*.

Two notes:

- Adding the block from scratch is fine too — it is in the inserter under **Design → Site navigation**. Set
  the menu from the sidebar rather than typing a `ref`.
- The transitional `.q-navigation-*` rules are still in the CSS on purpose, so an un-migrated header cannot
  show both navs at once in the window between step 2 and this step. Don't remove them until every
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

**A page's SEO title** (edit the page → **Rank Math** in the sidebar → **Edit Snippet** → Title). Worth knowing
what one field moves: the snippet title feeds `<title>`, `og:title`, `twitter:title` **and** the page's name in
the JSON-LD graph, so a typo there is a typo in four places and is why DEPLOY-LIST step 2 exists. The
description field beneath it is separate and does not follow — check both read the same way before saving.

**Not the author's display name.** Users → Profile no longer changes anything a visitor sees: the byline and
the schema both read `QUEDAMOS_AUTHOR_NAME` from
[inc/helpers/author-identity.php](themes/wp-child-theme-template/inc/helpers/author-identity.php), and the
photo reads `QUEDAMOS_AUTHOR_PHOTO` from the same file. That is deliberate — the name is the key that has to
match across the byline, the schema and the page title, and a database string drifts away from the code that
must agree with it. Live's record still says "Sara Carrillo Carrillo" and is now inert; leave it. To change how
she is credited, edit the constant.

## 7. Verify on the front end

Logged out, or in a private window — an admin session bypasses the page cache and will show you a healthier
site than your visitors get.

- [ ] Homepage and one course page load with styling intact
- [ ] `view-source:` shows `dist/styles/style.css?ver=` followed by a 10-digit number — a missing `?ver=`
      means `dist/` did not make it up, and the site is running unstyled or on a stale bundle
- [ ] `/blog` shows the title, intro and filter pills; a pill click filters the list and changes the URL, and
      loading that URL directly gives the same page
- [ ] A category archive loads — on live that is `https://quedamoslanguages.com/category/<slug>/`, e.g.
      [`/category/events/`](https://quedamoslanguages.com/category/events/). See the URL warning below
      before deciding it is broken
- [ ] One single post renders, with the author bar and photo, the name reading **Sara Carrillo** and linking
      to the About page
- [ ] `view-source:` on the About page shows no `Carillo` with one `r`, and a `Person` node whose `@id` ends
      `#sara`
- [ ] At 390px wide: the hamburger shows and the panel opens with every link
- [ ] At 1280px: the desktop nav is unchanged, and no width shows both navs at once
- [ ] **About** and **FAQs** in the desktop nav no longer 404 — the point of step 4
- [ ] Every box ticked for this release in [DEPLOY-LIST.md](DEPLOY-LIST.md)

### Type live's URLs, not local's

Live and your local checkout **do not share a permalink structure**, so a URL that works locally can 404 on
live while the page itself is perfectly healthy. Verified against live on 2026-08-17:

| | Local | Live |
|---|---|---|
| Blog listing | `/blog/` | `/blog/` |
| A single post | `/blog/<slug>/` | `/<slug>/` |
| A category archive | `/blog/category/<slug>/` | `/category/<slug>/` |

On live, `/blog/<anything>` is read as a **single post slug** — which is why `/blog/events` 404s even though
the Events archive exists at `/category/events/`. A 404 there is a wrong guess at the URL, not a missing page
and not a failed deploy.

The safe move is never to type these by hand: open `/blog` and **copy the link off a pill or a card**, which
WordPress generated for whatever structure live is actually on. To read that structure directly, go to
**Settings → Permalinks** — the *Custom structure* field is the browser equivalent of
`wp option get permalink_structure`. If it no longer matches the table above, correct the table in this file
and in [DEPLOY-LIST.md](DEPLOY-LIST.md) as part of the same visit.

Nothing in the theme hardcodes either shape — pills come from `get_category_link()`, the All pill from the
posts-page setting, post links from `get_permalink()` — so there is nothing to change in the code when the
two environments differ. Only the URL you check by hand.

Then move the finished items to the **Done** section of [DEPLOY-LIST.md](DEPLOY-LIST.md) and commit that, so
the next deploy starts from an honest list.

## If something looks wrong

In this order, because each is cheaper than the next:

1. **If it is a 404, check the URL before anything else** — live's permalink structure is not local's, so
   `/blog/<slug>` and `/blog/category/<slug>/` 404 on live by design. See *Type live's URLs, not local's* in
   step 7, and reach the page by clicking through from `/blog` instead.
2. **Purge LiteSpeed again** and recheck logged out. Most "the deploy didn't work" is a cached page.
3. **Check for a `Customized` template row** you missed in step 3 — the symptom is a page rendering the old
   design perfectly while the new code is definitely on the server.
4. **Check `dist/` uploaded** — the `?ver=` check in step 7. A theme zip built before `npm run build` installs
   cleanly and looks broken.
5. **Restore from step 1's backups** — paste the saved markup back into the same code editor.

`theme.json` has the same database-wins problem as templates: the matching `wp_global_styles` record overrides
it, so a global style change may need clearing in the Site Editor before it shows. See
[CLAUDE.md](CLAUDE.md) — verify in the browser, never assume.
