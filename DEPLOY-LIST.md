# Deploy list

Steps to run on the **live site** when releasing. A push ships theme code only — content, plugin/ACF
settings, media and `dist/` do not travel with the repo.

Tick items off once verified on live. Move done items to the bottom.

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

### 2. Header template part — point it at the `quedamos/mobile-menu` block

The mobile navigation is now the theme's own block instead of a filter on `core/navigation`. The block only
renders once the **Header** template part references it, and that part lives in the **database**, so it does
not travel with the repo. **Until this step runs, live has no mobile menu at all** — the old overlay's PHP
filter is gone with the code that replaced it.

In the Site Editor → Patterns → Template Parts → **Header**, edit as code and replace:

```
<!-- wp:navigation {"ref":5,"overlayMenu":"always","className":"q-navigation-mobile"} /-->
```

with:

```
<!-- wp:quedamos/mobile-menu {"ref":<live menu id>} /-->
```

- **`ref` is a database ID and live's is not `5`.** `5` is the local `wp_navigation` post. Get live's with
  `wp post list --post_type=wp_navigation --fields=ID,post_title` and use that number.
- Leave the **desktop** navigation block (`className: q-navigation-desktop`, `overlayMenu: never`)
  untouched — it keeps its own `ref`.
- If the `ref` is wrong or omitted the block falls back to the site's most recent `wp_navigation` post, so a
  mistake here degrades to "possibly the wrong menu" rather than an empty header. Still worth getting right.
- The block has no editor script, so the Site Editor shows it as an unrecognised block. That is expected —
  it renders correctly on the front end.

- [ ] Header template part updated with live's own `ref`
- [ ] Hamburger shows on live at 390px and the panel opens with all six links
- [ ] Desktop nav unchanged at 1280px, and no width shows both navs at once

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
