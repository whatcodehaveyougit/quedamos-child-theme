<?php
/**
 * Article display meta — the derived values the single-post template shows.
 *
 * The per-post bits core blocks can't express on their own:
 *   - the primary category, for the header pill,
 *   - a computed read-time,
 *   - the byline (category, author, date, read-time), surfaced as a shortcode so
 *     the FSE template can drop it into the article header,
 *   - the hero background layer built from the featured image,
 *   - the "more articles" query, scoped to the current post's category.
 *
 * @package Quedamos
 */

defined( 'ABSPATH' ) || exit;

/**
 * Average adult reading speed, in words per minute.
 *
 * Used to derive the read-time label. 200wpm is the conventional figure for
 * general-audience prose.
 */
const QUEDAMOS_WORDS_PER_MINUTE = 200;

/**
 * Return the primary `category` term for a post.
 *
 * Skips the catch-all "Uncategorised" where a real category also exists, and
 * falls back to the first assigned term otherwise.
 *
 * @param int $post_id Post ID. Defaults to the current post.
 * @return WP_Term|null The primary category, or null when the post has none.
 */
function quedamos_get_primary_category( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : get_the_ID();
	$terms   = get_the_terms( $post_id, 'category' );

	if ( ! $terms || is_wp_error( $terms ) ) {
		return null;
	}

	foreach ( $terms as $term ) {
		if ( 'uncategorized' !== $term->slug ) {
			return $term;
		}
	}

	return $terms[0];
}

/**
 * Estimate a post's read-time in whole minutes.
 *
 * Always returns at least 1, so a very short post still reads "1 min read".
 * str_word_count() is ASCII-oriented and undercounts accented Spanish words, so
 * the split is done on whitespace instead — this blog mixes English and Spanish
 * in most posts.
 *
 * @param int $post_id Post ID. Defaults to the current post.
 * @return int Read-time in minutes (>= 1).
 */
function quedamos_estimate_read_time( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : get_the_ID();
	$content = get_post_field( 'post_content', $post_id );
	$plain   = trim( wp_strip_all_tags( strip_shortcodes( (string) $content ) ) );

	if ( '' === $plain ) {
		return 1;
	}

	$words = count( preg_split( '/\s+/u', $plain, -1, PREG_SPLIT_NO_EMPTY ) );

	return max( 1, (int) ceil( $words / QUEDAMOS_WORDS_PER_MINUTE ) );
}

/**
 * The read-time label for the current post, e.g. "6 min read".
 *
 * @return string The read-time label, unescaped — callers escape at output.
 */
function quedamos_read_time_label() {
	$minutes = quedamos_estimate_read_time();

	/* translators: %d: estimated reading time in minutes. */
	return sprintf( _n( '%d min read', '%d min read', $minutes, 'quedamos' ), $minutes );
}

/**
 * One meta item — an icon paired with its label.
 *
 * @param string $icon  Icon filename within svgs/, e.g. 'clock.svg'.
 * @param string $label The already-escaped label text.
 * @return string The meta item HTML.
 */
function quedamos_article_meta_item( $icon, $label ) {
	return sprintf(
		'<span class="article-byline__item">%s<span>%s</span></span>',
		quedamos_inline_svg( 'svgs/' . $icon ),
		$label
	);
}

/**
 * [quedamos_article_byline] — the category pill, author, date and read-time row.
 *
 * Built as a single-line string on purpose: wpautop() turns newlines in
 * shortcode output into stray <br> tags, so the row carries no line breaks.
 *
 * @return string The rendered byline HTML.
 */
function quedamos_article_byline_shortcode() {
	$category = quedamos_get_primary_category();

	$pill = '';
	if ( $category ) {
		$pill = sprintf(
			'<a class="article-byline__category" href="%s">%s</a>',
			esc_url( get_category_link( $category->term_id ) ),
			esc_html( $category->name )
		);
	}

	$author = sprintf(
		'<span class="article-byline__author">%s</span>',
		esc_html( get_the_author() )
	);

	$meta = quedamos_article_meta_item( 'calendar.svg', esc_html( get_the_date( 'j F Y' ) ) )
		. quedamos_article_meta_item( 'clock.svg', esc_html( quedamos_read_time_label() ) );

	return sprintf(
		'<div class="article-byline">%s%s<span class="article-byline__meta">%s</span></div>',
		$pill,
		$author,
		$meta
	);
}
add_shortcode( 'quedamos_article_byline', 'quedamos_article_byline_shortcode' );

/**
 * [quedamos_article_hero_bg] — the featured-image layer behind the article header.
 *
 * The constrained header content sits on top of this. Returns an empty string
 * when the post has no featured image; the header then falls back to its flat
 * brand background, which is handled in SCSS rather than with a stand-in image.
 *
 * @return string The background layer HTML, or '' when there is no image.
 */
function quedamos_article_hero_bg_shortcode() {
	$image_url = get_the_post_thumbnail_url( get_the_ID(), 'full' );

	if ( ! $image_url ) {
		return '';
	}

	return sprintf(
		'<div class="article-header__bg" aria-hidden="true" style="background-image:url(%s);"></div>',
		esc_url( $image_url )
	);
}
add_shortcode( 'quedamos_article_hero_bg', 'quedamos_article_hero_bg_shortcode' );

/**
 * Scope the "More articles" query loop to the current post's category.
 *
 * The filter fires with the *post-template* block instance (core builds the
 * query vars there), not the outer wp:query block, and the query block does not
 * pass its className down as context — so this keys on the post-template's own
 * `article-related-grid` className, which is the identifier actually present on
 * the block instance received. The related cards stay derived from the content
 * rather than hardcoded, using a core query block rather than a bespoke render.
 *
 * Falls back to latest-across-all-categories when the post has no category, so
 * the section is never empty.
 *
 * @param array    $query Query vars the block will run.
 * @param WP_Block $block The block instance (the post-template).
 * @return array Adjusted query vars.
 */
function quedamos_related_posts_query_vars( $query, $block ) {
	$class = $block->parsed_block['attrs']['className'] ?? '';

	if ( false === strpos( (string) $class, 'article-related-grid' ) || ! is_singular( 'post' ) ) {
		return $query;
	}

	$post_id  = get_the_ID();
	$category = quedamos_get_primary_category( $post_id );

	$query['post__not_in']        = array_merge( $query['post__not_in'] ?? array(), array( $post_id ) );
	$query['posts_per_page']      = 3;
	$query['ignore_sticky_posts'] = true;

	if ( $category ) {
		$query['category__in'] = array( $category->term_id );
	}

	return $query;
}
add_filter( 'query_loop_block_query_vars', 'quedamos_related_posts_query_vars', 10, 2 );
