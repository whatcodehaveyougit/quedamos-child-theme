# Deploy list

Steps to run on the **live site** when releasing. A push ships theme code only — content, plugin/ACF
settings, media and `dist/` do not travel with the repo.

Tick items off once verified on live. Move done items to the bottom.

This file is the **what**. For the **how** without SSH or WP-CLI on live — the wp-admin equivalent of every
command below, in the order it must run — see [DEPLOY-MANUAL.md](DEPLOY-MANUAL.md).

## Every deploy

- [ ] Build assets: `cd themes/wp-child-theme-template && npm run build`, then get `dist/` onto live.
- [ ] Load the live homepage and one course page in a browser.

## Pending

### 1. Homepage numbers band — replace the zeros in live content

Live page content still has `0 STUDENTS / 0 YEARS EXPERIENCE / 0 CULTURAL EVENTS / 0 CORTADOS`. The code is
already deployed and reads these figures from the page, so **without this step live shows static zeros.**

Run on live:

```
wp eval-file fix-band.php --user=<admin-id>
```

Script is in the appendix. Refuses to run twice; delete it afterwards.

Or edit the four paragraphs in the block editor — set the number, and add `count-up` under Advanced →
Additional CSS class(es) on each **number** paragraph, not the label:

| Label | Number |
|---|---|
| STUDENTS | 31 |
| YEARS EXPERIENCE | 7 |
| CULTURAL EVENTS | 14 |
| CORTADOS | 1000 |

- [ ] Content updated
- [ ] `view-source:` on the homepage shows `<p class="count-up …">31</p>`
- [ ] Band counts up when scrolled into view

Note: these figures are carried over from 2024 — check `7 YEARS EXPERIENCE` is still right before publishing.

### 2. About page — the name, and saying it out loud

Prompted by a citability audit: *"With no visible bylines, an unresolvable author entity, and a name spelled
two ways, there is no credentialed human for an assistant to name."* Most of that is now fixed **in code** —
see `inc/helpers/author-identity.php` and `inc/schema/person.php`, which put one canonical Person entity
behind every byline and every post's `author`. Two halves cannot be, because they live in the database.

**a) The SEO title. — DONE on live 2026-08-17.** It read "Sara Car**i**llo…" with one `r`; live now reads
"Sara Carrillo: Edinburgh Spanish Tutor at Quedamos Languages" and the page returns zero occurrences of the
one-`r` spelling. Recorded because that one field feeds `<title>`, `og:title`, `twitter:title` *and* the
`AboutPage` name in the JSON-LD — a typo there was a typo in four places at once.

**⚠ The same edit renamed the page** — `about-spanish-tutor-edinburgh` → `about-spanish-tutor-**teaching**-edinburgh`.
That was a side effect of changing the SEO title in Rank Math, not a deliberate rename, and the slug is
load-bearing in two ways neither visible from wp-admin:

- The URL indexed since 2024 — **the one Search Console reports** — began returning a hard **404** on live.
- `QUEDAMOS_AUTHOR_PAGE_SLUG` resolves the profile page for both the byline link and the Person entity's
  `@id`. A slug it cannot find makes `quedamos_author_page_url()` return `''` — the byline link silently
  disappears and the Person loses its home.

### 7. Rename the About page back on live · **BLOCKING**

**Decided 2026-08-17: the short URL is canonical.** It is the one with the history and the one Search
Console reports, so the page moves back rather than the site redirecting to the accidental slug. The code
already assumes it — `QUEDAMOS_AUTHOR_PAGE_SLUG` is `about-spanish-tutor-edinburgh`, and the redirect map
now sends the *teaching* URL to the short one, catching anything that picked it up while it was live.

**Until live is renamed, the deploy makes things worse, not better:** the constant will resolve to nothing,
so the byline link vanishes from every post and the Person entity loses its `@id`. Do this in the same
sitting as the theme upload.

Live only — local was renamed back on 2026-08-17 and is already correct.

**Pages → About → open it →** in the sidebar under **Permalink**, set the URL slug to:

```
about-spanish-tutor-edinburgh
```

Then **Update**. If Rank Math offers to change the slug when you next edit the title, decline it — that is
exactly how this happened.

Do **not** add a WordPress-generated redirect if prompted: the theme's own map already handles the
teaching → short direction, and two mechanisms for one URL is what
[CLAUDE.md](CLAUDE.md) exists to prevent.

- [ ] `/about-spanish-tutor-edinburgh/` returns **200** on live
- [ ] `/about-spanish-tutor-teaching-edinburgh/` **301s** to it
- [ ] A live post's byline name is a link, and it points at the short URL
- [ ] The About page's JSON-LD `Person` node is `@id`'d `…/about-spanish-tutor-edinburgh/#sara`
- [ ] LiteSpeed purged afterwards

**b) Her surname appears nowhere in the visible text.** Still outstanding. The body prose only ever says
"Sara" — the full name exists solely in `<head>`. An assistant that will not read metadata as a claim about
authorship has nothing to cite, and the schema now asserts she is the subject of a page that never names her.
Add the full name to the opening line, e.g. *"Hello, my name is Sara Carrillo…"*, matching the meta
description that already says exactly that. Content edit, no code.

Then purge LiteSpeed — a cached `<head>` will keep serving the old markup.

- [x] `rank_math_title` reads "Sara Carrillo…" with two `r`s and no leading space
- [x] `view-source:` on the live About page shows **no** occurrence of `Carillo` (one `r`) anywhere
- [ ] The visible body text names her in full at least once
- [ ] `/about-spanish-tutor-edinburgh/` returns 200 rather than 404ing — needs the live rename in step 7,
      not just the theme upload
- [ ] The page's JSON-LD has a `Person` node `@id`'d `…/about-spanish-tutor-edinburgh/#sara`, and
      the `AboutPage` node carries `mainEntity` pointing at it
- [ ] A live post's `BlogPosting.author` resolves to that same `#sara` @id — not `/author/admin/`
- [ ] The site node is typed `Organization` only, with `founder` → Sara (it was `["Organization","Person"]`
      named "Quedamos Languages", i.e. the school asserted as a human)
- [ ] Load a live post: the bar reads "Written by **Sara Carrillo** · Spanish Language Educator", the name
      links to the About page, and her photo shows rather than the site icon
- [ ] Load `/blog` and confirm the card bylines read "Sara Carrillo" with the same photo

**The WordPress `display_name` no longer matters and should be left alone.** It reads "Sara Carrillo
Carrillo" on live; nothing renders it any more. Both the byline and the schema now read
`QUEDAMOS_AUTHOR_NAME` from code, precisely so the name cannot drift away from the spelling the rest of the
site uses. To change how she is credited, edit that constant — one line, and it moves everywhere at once.

### 3. Header template part — point it at the `quedamos/site-navigation` block · **BLOCKING**

**Do this in the same release as the code, not after it.** The header navigation — *both* the desktop row
and the mobile panel — is now one block the theme owns, `quedamos/site-navigation`. It replaces the
`core/navigation` pair the header carries today. The block renders only once the **Header** template part
references it, and that part lives in the **database**, so it does not travel with the repo.

Until this step runs, live keeps its old `core/navigation` header. That still works — the transitional CSS
at the foot of `site-navigation.scss` and in `navigation.scss` exists precisely to hold it together in this
window — but live gets none of the change, including the **About and FAQs links, which 404 on live's
desktop nav today** and are fixed by this block resolving hrefs from post IDs.

Already done locally, so this is live-only.

```bash
# 1. Find live's menu ID — it is NOT 5, that is the local post.
wp post list --post_type=wp_navigation --fields=ID,post_title

# 2. Find the Header template part's post ID.
wp post list --post_type=wp_template_part --fields=ID,post_title

# 3. Back the current markup up before touching it.
wp post get <header-id> --field=post_content > header-part-backup.txt

# 4. Swap the pair for the one block. The ref is READ from the existing desktop
#    nav rather than typed, so there is no live menu ID to look up by hand.
wp eval '
$id  = <header-id>;
$old = get_post_field( "post_content", $id );
if ( false !== strpos( $old, "wp:quedamos/site-navigation" ) ) { WP_CLI::success( "Already swapped." ); return; }
if ( ! preg_match( "#<!-- wp:navigation \{.*?\"ref\":(\d+).*?/-->#s", $old, $m ) ) { WP_CLI::error( "No core/navigation block found — check the markup by hand." ); }
$new = preg_replace( "#<!-- wp:navigation \{.*?/-->#s", "<!-- wp:quedamos/site-navigation {\"ref\":{$m[1]}} /-->", $old, 1 );
$new = preg_replace( "#\s*<!-- wp:quedamos/mobile-menu \{.*?/-->#s", "", $new, 1 );
if ( $new === $old ) { WP_CLI::error( "Nothing changed — check the markup by hand." ); }
wp_update_post( array( "ID" => $id, "post_content" => wp_slash( $new ) ) );
WP_CLI::success( "Header repointed with ref {$m[1]}." );
'
```

Then purge LiteSpeed (`wp litespeed-purge all`) — a cached header will hide the change completely.

- The script replaces **the first `wp:navigation` comment it finds**. Live's header has one (the desktop
  nav); if a second ever appears, do it by hand instead.
- The second `preg_replace` drops a `quedamos/mobile-menu` comment if one is present. Live has never had
  that block, so it is a no-op there — it is in the script so the same command works on any environment.
- **`ref` is a database ID and live's is not `5`** — which is why it is read from the existing markup
  rather than typed. If it were wrong or omitted, the block falls back to the site's most recent
  `wp_navigation` post, so a mistake degrades to "possibly the wrong menu" rather than an empty header.
- The block **does** have an editor script now, so it appears in the inserter as **Site navigation** and
  renders properly in the Site Editor, with a Menu picker in the sidebar. The old "expect an unrecognised
  block" warning no longer applies.
- The transitional `.q-navigation-*` and `.wp-block-navigation` rules stay in the CSS until every
  environment is swapped. Once live is done, delete them from both `site-navigation.scss` and
  `navigation.scss`, and the `781`/`782` paragraph from `handbook/DESIGN-SYSTEM.md`.

- [ ] Header template part repointed, carrying its own `ref`
- [ ] LiteSpeed purged
- [ ] Hamburger shows on live at 390px and the panel opens with all six links
- [ ] Desktop row shows at 1280px, looks unchanged, and no width shows both at once
- [ ] **About** and **FAQs** in the desktop row no longer 404
- [ ] Transitional CSS deleted once every environment is swapped

### 4. Blog listing — delete the database "Blog Home" template

`/blog` now renders from `templates/home.html` in the repo. Live still has a **database** template that
**wins over it**: a `wp_template` post with `post_name = home`, titled "Blog Home", holding the stock
`core/query-medium-posts` pattern. Same trap as `theme.json` vs `wp_global_styles` in
[CLAUDE.md](CLAUDE.md).

**Until that record is gone, live ships the new code and keeps rendering the old core pattern** — no title,
no intro, no filter pills, no cards.

Identify it by `post_name`, not by ID: the local record was ID 20 and live's will be a different number.

Run on live:

```
wp post list --post_type=wp_template --name=home --fields=ID,post_title,post_name
wp post delete <id> --force
```

The same applies to a `post_name = category` record if one exists — check for it before deciding the
category archive is broken.

Then purge LiteSpeed (`wp litespeed-purge all`) — a cached `/blog` will keep serving the old pattern and
make a correct deploy look broken.

**Live and local do not share a permalink structure — test live's URLs, not local's.** Verified against
live on 2026-08-17:

| | Local | Live |
|---|---|---|
| `permalink_structure` | `/blog/%postname%/` | `/%postname%/` |
| Blog listing (posts page) | `/blog/` | `/blog/` |
| A single post | `/blog/<slug>/` | `/<slug>/` |
| A category archive | `/blog/category/<slug>/` | `/category/<slug>/` |

So on live the Events archive is `/category/events/`. Both `/blog/category/events/` and `/blog/events`
**404 on live** — the second because live reads `/blog/<anything>` as a single post slug, and there is no
post called `events`.

Nothing in the theme hardcodes either shape: the pills come from `get_category_link()`, the All pill from
`get_option( 'page_for_posts' )`, and post links from `get_permalink()`. Both structures therefore render
the same pages — only the URL you type to check the deploy changes. Read a live URL off the page (copy a
pill's link) rather than assuming the local path.

- [ ] `wp post list --post_type=wp_template --name=home` returns nothing
- [ ] LiteSpeed purged
- [ ] `/blog` on live shows "Our Blog", the intro and the pill row
- [ ] A pill click filters the list and changes the URL; loading that URL directly gives the same page
- [ ] The URL a pill lands on is `/category/<slug>/` — if it reads `/blog/category/<slug>/` and 404s,
      live's permalink structure has changed and this table is stale
- [ ] Appearance → Editor → Templates shows "Blog Home" as coming from the theme, not "Customized"

Categories themselves need nothing: the five live categories are already assigned, and no code reads their
names, slugs or order.

### 5. theme.json changes — confirm the database is not overriding them

Two changes in this release live in `theme.json`:

- `settings.layout.contentSize` and `wideSize`: `1280px` → **`1100px`**
- `settings.typography.fontSizes` `heading-two-size`: `clamp(2.0rem, 4vw, 4rem)` → **`clamp(2rem, 3.5vw, 3.5rem)`**
  (h2 was capped at `4rem`, identical to h1, so the two read the same size on desktop. Mobile is unchanged —
  the `2rem` floor is untouched — and h2 now sits between h1's `4rem` and h3's `3rem`.)

Both are subject to the same trap, and one check covers them. Per
[CLAUDE.md](CLAUDE.md) that file is a **Site Editor export**, and WordPress merges it with the
`wp_global_styles` database record, which **wins**. Locally that record is empty, which is why editing the
file alone moved local to 1100.

Live's record cannot be read from outside. Live currently serves `1280px`, which is consistent with *either*
no override *or* an override that happens to say 1280 — so it has to be checked, not assumed. If live has a
`layout` override, the whole site will stay at 1280 after this deploy and nothing will look different.

Check after deploying, in the browser console on any live page:

```js
getComputedStyle(document.documentElement).getPropertyValue('--wp--style--global--content-size')
```

- `1100px` → nothing more to do.
- `1280px` → the database is overriding the file. Go to **Appearance → Editor → Styles → (⋮) Revert to
  theme defaults**, or clear the `wp_global_styles` record for the active theme, then re-check.

Reverting global styles clears **every** Site Editor style customisation for the theme, not just the width —
so look at what is in there before reverting, and note anything that would be lost.

- [ ] `--wp--style--global--content-size` reads `1100px` on live
- [ ] An h2 on a live post measures ~50px at desktop, not ~58px
- [ ] Home, Courses, About, Contact and a single post still look right at the narrower width

### 6. Blog listing — a 25px spacer sits above "Our Blog"

Live renders a `<div class="wp-block-spacer" style="height:25px">` between the site header and `<main>`,
adding 41px (the spacer plus a 16px block gap) above the page title. `templates/home.html` in the repo has
no spacer there, and local renders `main` flush against the header — so this is a leftover in **live's
database copy of the Blog Home template**, the same record step 4 is about.

**Appearance → Editor → Templates → Blog Home → (⋮) Clear customisations.** If the template still shows as
"Customized" after step 4, this is why.

- [ ] No `wp-block-spacer` between `</header>` and `<main class="blog-page">` in live's page source
- [ ] "Our Blog" sits 64px below the header, matching local

### 7. Booking links — add `qd_currency` to the euro-priced courses

The booking summary now reads a `qd_currency` parameter off the booking link and prints `£` or `€`
accordingly. It is **not** derived from the course or the location: the link says which, and a link that
does not say falls back to pounds.

The links live in **course post content**, so they do not travel with the repo. Until this step runs, live's
euro-priced courses keep quoting pounds — the same figure, the wrong symbol, on the page where the visitor
decides what they are about to pay.

Two courses carry booking links today (`Spanish Classes in Edinburgh`, `Spanish Classes in Mallorca`), each
with an in-person and an online link. Append `&qd_currency=euros` to every link on a course priced in euros;
leave pound-priced links alone, or append `&qd_currency=pounds` to make it explicit.

```
/booking-form/?qd_course=…&qd_price=149&qd_start_date=…&qd_location=inPerson&qd_currency=euros
```

Find them on live — IDs differ from local's 115 and 1560:

```
wp post list --post_type=course --fields=ID,post_title
wp post get <id> --field=post_content | grep -o '[^"]*qd_price[^"]*'
```

Then edit the buttons in the block editor. Only `euros` switches the symbol; anything else, including a
missing parameter, renders `£`.

- [ ] Confirm with Sigurd **which** courses are priced in euros before editing anything — the code assumes
      nothing, so this is the only place the answer is recorded
- [ ] Euro course booking buttons carry `&qd_currency=euros`
- [ ] Clicking one lands on `/booking-form/` showing `€` on Subtotal, the participants line and Total
- [ ] Changing the participant count keeps the `€` and multiplies correctly
- [ ] A pound course still shows `£` throughout

Also in this release: the summary card **omits the Location row entirely** when the link carries no
`qd_location`. Every current link has one, so nothing on live changes — but a link built without it now
renders a card with no empty "Location:" label.

- [ ] `dist/` rebuilt and shipped — the participant-count recalculation moved out of an inline `<script>`
      in the card into the Parcel bundle, so a stale `dist/` leaves the totals frozen at one participant

### 8. "Classes at a glance" — insert the pattern on each course page and fill it in

The theme now ships a **Classes at a glance** block pattern (`patterns/classes-at-a-glance.php`) and the
`.class-table` styles that dress it. A pattern is only a starting point in the inserter: the code ships, the
block does **not** appear on any page until somebody inserts it. Nothing on live changes until this runs.

It is a plain (unsynced) pattern, so it is inserted once per course page and then edited freely — later
edits to the theme file do not reach pages already using it.

On live, for each course page:

1. Edit the page → **+** → **Patterns** → search "Classes at a glance" (it is filed under Featured,
   Columns and Call to Action) → insert it where the class comparison belongs.
2. Replace the placeholder content: the two level names and CEFR codes, and for each of the four columns
   the day and time, the venue, the price line and the availability note.
3. Fix each **Book Now** button's link. The pattern ships placeholders pointing at
   `/booking-form/?qd_course=…&qd_price=120&qd_location=inPerson&qd_currency=pounds`. Update `qd_course`
   to the real course name, `qd_price` to the real figure, `qd_location` to `inPerson` or `online`, and
   `qd_currency` to `euros` on a euro-priced course (see Pending 7).

**The price is written twice** — in the list text and in the `qd_price` parameter — so changing one and not
the other quotes a visitor one figure on the course page and charges another on the booking page. Check both.

**Expect the inserted block to be structure-locked, and know the way out.** WordPress stamps the pattern's
root group with a `metadata` object naming the pattern, which puts the whole instance into **content-only
editing**: the text is editable, but List View shows only the headings, paragraphs, lists and buttons — no
Group or Columns rows, and no parent block to select, collapse or move. Nothing is broken and nothing is
missing from the markup; it is only how the editor presents a pattern instance. To get the structure back,
select any block inside it and click **Modify** in the toolbar. Note the same applies to the `CourseTemplate`
wrapper the course pages already sit in, which is why those pages behave this way before this pattern is
inserted at all.

**If the pattern is missing from the inserter after deploying, the theme's pattern cache is stale.**
WordPress scans `patterns/` once and caches the result in a site transient keyed by the theme version, so a
new pattern file added without a version bump stays invisible however many times the page is reloaded. This
bit on local: the pattern registered only after the cached scan was cleared. Bumping the theme version on
the merge (`style.css` + `package.json`, as every merge to `main` does) busts it by itself — so this is only
a manual step if a deploy ever ships the pattern without a version change, in which case clear the site
transients (`wp transient delete --all --network`) or bump the version.

- [ ] Pattern inserted on every live course page that needs it
- [ ] All placeholder days, times, venues, prices and availability notes replaced
- [ ] Each Book Now link's `qd_price` matches the price printed above it
- [ ] Clicking a Book Now button lands on `/booking-form/` with the right course, venue and total
- [ ] Checked at 1440px, 768px and 390px — two level groups side by side on desktop, stacked on mobile,
      no horizontal scroll
- [ ] `dist/` rebuilt and shipped — the `.class-table` styles are new, so a stale bundle renders the
      pattern unstyled

### 9. "Course detail with video" — insert it and paste the video URL

A second pattern (`patterns/course-detail-with-video.php`) pairs a course write-up with a video: editable
copy on the left, an embed on the right. It is designed to sit under the classes table, and like it, the
code ships but nothing appears on any page until somebody inserts it.

The embed ships **empty on purpose** — inserted, it shows the editor's "Paste a link…" field, so the video
is added by pasting a URL rather than by editing markup.

On live, for each course page that needs it:

1. Edit the page → **+** → **Patterns** → search "Course detail with video" → insert it below the classes
   table.
2. Replace the placeholder copy: the title, the two section headings, and both lists.
3. Click the embed block and paste the video URL.

**Check the URL's provider actually embeds.** YouTube and Vimeo resolve through WordPress's built-in
oEmbed list and need nothing. **Instagram and Facebook do not** — Meta requires an app access token, so a
reel URL pasted straight in will not render and the block falls back to a plain link. If the video is an
Instagram reel, it needs either a supported host or a plugin that carries a token, and that is a decision
to make before promising it on a page.

- [ ] Pattern inserted on every live course page that needs it
- [ ] Placeholder copy replaced
- [ ] Video URL pasted and **confirmed rendering on the front end**, not just in the editor
- [ ] Checked at 1440px and 390px — copy and video side by side on desktop, stacked on mobile
- [ ] `dist/` rebuilt and shipped — the `.course-detail` styles are new
### 10. About page — swap the tutor band for the `quedamos/tutor-intro` pattern

The "Meet your tutor" band now ships with the theme as `patterns/tutor-intro.php` ("Meet your tutor" in the
inserter). The band **on the page** is still the old core blocks saved in the database, and a pattern insert
is a content edit, so it does not travel with the repo — until this runs, the code is deployed and nothing
on the About page changes.

Nothing breaks if it is never done: the old band keeps rendering exactly as it does today, because the new
CSS is keyed on `.tutor-intro` classes the database markup does not carry. This is a tidy-up, not a blocker.

**Pages → About → open it →** delete the existing tutor band (the two-column group holding the eyebrow,
"¡Hola! I'm Sara", the three emoji lines, the button, and the portrait with the white card), then insert
**Meet your tutor** from the block inserter in its place and put the real copy back in. The pattern is
unsynced, so every word is editable once inserted.

Two things change on purpose when you do:

- **The card over the photo becomes one paragraph.** Today it is "7+ years" in large red type over a
  smaller grey line; the pattern replaces both with a single quote. Write one sentence rather than pasting
  the two tiers back in — reinstating them undoes the point of the change.
- **The portrait is a theme-file placeholder** (`images/sara-carrillo.webp`). Replace it from the media
  library with the photo the page uses today, or leave it — either renders on both environments.

Also set the button's link: the pattern ships pointing at `/contact/`, which is a placeholder.

Do this on **both** environments, or the two About pages diverge.

- [ ] Old band removed, **Meet your tutor** inserted, and the copy restored
- [ ] The card reads as one paragraph of quote text, not a figure plus a caption
- [ ] `view-source:` on the About page shows `class="wp-block-group alignfull tutor-intro"`
- [ ] At 1440px the card overlaps the portrait's lower-left; at 375px it tucks under the photo and the copy
      is centred
- [ ] Bands still meet edge to edge — no white stripe above or below the new one
- [ ] LiteSpeed purged

## Done

*Nothing yet.*

---

## Appendix — scripts

### `fix-band.php` (Pending 1)

```php
<?php
$post_id = (int) get_option( 'page_on_front' );

if ( ! $post_id ) {
	WP_CLI::error( 'No static front page is set (page_on_front is empty).' );
}

$content = get_post_field( 'post_content', $post_id );

if ( ! $content ) {
	WP_CLI::error( "No content for front page {$post_id}." );
}

if ( false === strpos( $content, 'big-numbers-container' ) ) {
	WP_CLI::error( "Front page {$post_id} has no big-numbers band." );
}

if ( false !== strpos( $content, '"className":"count-up"' ) ) {
	WP_CLI::error( "Front page {$post_id} has already been migrated — nothing to do." );
}

// Order matters: these pair with the labels already in the content.
$expected = array( 'STUDENTS', 'YEARS EXPERIENCE', 'CULTURAL EVENTS', 'CORTADOS' );
$numbers  = array( '31', '7', '14', '1000' );

$pattern = '#<!-- wp:paragraph -->\s*<p>0</p>\s*<!-- /wp:paragraph -->#';

preg_match_all( $pattern, $content, $matches, PREG_OFFSET_CAPTURE );

if ( count( $expected ) !== count( $matches[0] ) ) {
	WP_CLI::error( 'Expected ' . count( $expected ) . ' zero paragraphs, found ' . count( $matches[0] ) . ' — aborting.' );
}

foreach ( $matches[0] as $i => $match ) {
	$tail = substr( $content, $match[1] + strlen( $match[0] ), 300 );
	if ( false === strpos( $tail, $expected[ $i ] ) ) {
		WP_CLI::error( "Zero #{$i} is not followed by '{$expected[$i]}' — content has moved, aborting." );
	}
}

$index   = 0;
$content = preg_replace_callback(
	$pattern,
	function () use ( &$index, $numbers ) {
		$number = $numbers[ $index ];
		$index++;

		return '<!-- wp:paragraph {"className":"count-up"} -->' . "\n"
			. '<p class="count-up">' . $number . '</p>' . "\n"
			. '<!-- /wp:paragraph -->';
	},
	$content
);

$result = wp_update_post(
	array(
		'ID'           => $post_id,
		'post_content' => wp_slash( $content ),
	),
	true
);

if ( is_wp_error( $result ) ) {
	WP_CLI::error( $result->get_error_message() );
}

WP_CLI::success( "Updated front page {$post_id}: " . implode( ' / ', $numbers ) );
```
