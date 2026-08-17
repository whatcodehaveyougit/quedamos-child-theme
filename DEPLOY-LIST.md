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

### 2. Article author bar — the author's display name

The single-post template now opens with an author bar reading "Written by *&lt;display name&gt;*". That name
comes from the WordPress user record, which lives in the database and does **not** travel with the repo —
on live the bar will say whatever `display_name` says today, most likely just "Sara".

The name is also the **key to the author's photo**: `quedamos_author_photos()` in
`inc/blog/article-author.php` maps `sara carrillo` to `images/sara-carrillo.webp`, which ships with the
theme. Until the display name matches, the bar falls back to the site icon — so this step decides whether
live shows Sara's face or the school's mark.

That same map now also feeds `get_avatar()` through a `pre_get_avatar_data` filter, so the display name
additionally decides what the **blog listing and related-post card bylines** show. Without it they fall back
to gravatar.com, which has no account for this address and returns the silhouette placeholder — so a wrong
display name is now visible on `/blog` as well as on a single post.

The role line ("Spanish Language Educator") is hardcoded in the same file and needs nothing on live.

Run on live, or set it in Users → Profile:

```
wp user update <id> --display_name="Sara Carrillo" --first_name="Sara" --last_name="Carrillo"
```

- [ ] Display name set to `Sara Carrillo` (exactly — the photo lookup is on this string, case-insensitive)
- [ ] Load a live post and confirm the bar reads "Written by Sara Carrillo · Spanish Language Educator",
      with Sara's photo beside it rather than the site icon

### 3. Header template part — point it at the `quedamos/mobile-menu` block · **BLOCKING**

**Do this in the same release as the code, not after it.** The mobile navigation is now the theme's own
block instead of a filter on `core/navigation`. The block renders only once the **Header** template part
references it, and that part lives in the **database**, so it does not travel with the repo. Until this step
runs, **live has no mobile menu at all** — the old overlay's PHP filter was deleted along with the code that
replaced it.

Already done locally, so this is live-only. The safe way is WP-CLI rather than the Site Editor, because the
part is stored as block markup and the editor will show the new block as unrecognised:

```bash
# 1. Find live's menu ID — it is NOT 5, that is the local post.
wp post list --post_type=wp_navigation --fields=ID,post_title

# 2. Find the Header template part's post ID.
wp post list --post_type=wp_template_part --fields=ID,post_title

# 3. Back the current markup up before touching it.
wp post get <header-id> --field=post_content > header-part-backup.txt

# 4. Swap the block, substituting live's menu ID for <live-menu-id>.
wp eval '
$id  = <header-id>;
$old = get_post_field( "post_content", $id );
$new = preg_replace(
    "#<!-- wp:navigation \{[^}]*\"className\":\"q-navigation-mobile\"[^}]*\} /-->#",
    "<!-- wp:quedamos/mobile-menu {\"ref\":<live-menu-id>} /-->",
    $old
);
if ( $new === $old ) { WP_CLI::error( "Nothing matched — check the markup by hand." ); }
wp_update_post( array( "ID" => $id, "post_content" => wp_slash( $new ) ) );
WP_CLI::success( "Header updated." );
'
```

Then purge LiteSpeed (`wp litespeed-purge all`) — a cached header will hide the change completely.

- **`ref` is a database ID and live's is not `5`.** If it is wrong or omitted the block falls back to the
  site's most recent `wp_navigation` post, so a mistake degrades to "possibly the wrong menu" rather than an
  empty header. Still worth getting right.
- Leave the **desktop** navigation block (`className: q-navigation-desktop`, `overlayMenu: never`)
  untouched — it keeps its own `ref`.
- The block has no editor script, so the Site Editor shows it as an unrecognised block. That is expected;
  it renders correctly on the front end. Don't "fix" it.
- `header .q-navigation-mobile { display: none }` is still in the CSS on purpose, so the old overlay can't
  appear beside the desktop nav in the window between the code deploy and this step. Once every environment
  is swapped, that rule can go.

- [ ] Header template part updated with live's own `ref`
- [ ] LiteSpeed purged
- [ ] Hamburger shows on live at 390px and the panel opens with all six links
- [ ] Desktop nav unchanged at 1280px, and no width shows both navs at once

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

Category archives live at `/blog/category/<slug>/`, not `/category/<slug>/`: the `/blog/%postname%/`
permalink front prefixes the category base. Nothing in the code hardcodes that — it comes from
`get_category_link()` — but it is the URL to test.

- [ ] `wp post list --post_type=wp_template --name=home` returns nothing
- [ ] LiteSpeed purged
- [ ] `/blog` on live shows "Our Blog", the intro and the pill row
- [ ] A pill click filters the list and changes the URL; loading that URL directly gives the same page
- [ ] Appearance → Editor → Templates shows "Blog Home" as coming from the theme, not "Customized"

Categories themselves need nothing: the five live categories are already assigned, and no code reads their
names, slugs or order.

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
