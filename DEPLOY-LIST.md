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

### 2. Article author bar — the author's name and photo

The single-post template now opens with an author bar reading "Written by *&lt;display name&gt;*", with the
author's avatar beside it. Both come from the WordPress user record, which lives in the database and does
**not** travel with the repo — on live the bar will say whatever `display_name` says today, most likely
just "Sara", next to a grey placeholder silhouette.

The role line ("Spanish Language Educator") is hardcoded in `inc/blog/article-author.php` and needs
nothing on live.

Run on live, or set it in Users → Profile:

```
wp user update <id> --display_name="Sara Carrillo" --first_name="Sara" --last_name="Carrillo"
```

- [ ] Display name set to `Sara Carrillo`
- [ ] Avatar showing — the bar uses Gravatar, so the photo has to be attached to the author's email
      address at gravatar.com. Until then the silhouette shows on live *and* locally.
- [ ] Load a live post and confirm the bar reads "Written by Sara Carrillo · Spanish Language Educator"

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
