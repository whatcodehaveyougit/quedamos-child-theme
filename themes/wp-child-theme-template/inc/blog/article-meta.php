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
 * Rank Math stores an editor's explicit choice of primary category in post meta;
 * that answer wins whenever it is set and still points at a live term. Otherwise
 * the first assigned term is used, skipping the catch-all "Uncategorised" where a
 * real category also exists.
 *
 * One function rather than two on purpose: the article header's pill and the
 * listing card's chip must not be able to disagree about which category a post
 * belongs to.
 *
 * @param int $post_id Post ID. Defaults to the current post.
 * @return WP_Term|null The primary category, or null when the post has none.
 */
function quedamos_get_primary_category( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : get_the_ID();

	$chosen = quedamos_rank_math_primary_category( $post_id );

	if ( $chosen ) {
		return $chosen;
	}

	$terms = get_the_terms( $post_id, 'category' );

	if ( ! $terms || is_wp_error( $terms ) ) {
		return null;
	}

	foreach ( $terms as $term ) {
		if ( 'uncategorized' !== $term->slug ) {
			return $term;
		}
	}

	// Deliberately no fallback to $terms[0]: if the only category is the
	// catch-all, there is nothing worth showing. Callers treat null as "hide
	// the pill" rather than printing "Uncategorized" at the top of an article.
	return null;
}

/**
 * The category an editor picked as primary in Rank Math, when there is one.
 *
 * The meta holds a term ID, and it goes stale the moment that term is deleted or
 * unassigned from the post — so it is validated against the post's own terms
 * before being trusted, not just fetched. Returns null when Rank Math is not
 * installed, which is the same as "no choice was made".
 *
 * @param int $post_id Post ID.
 * @return WP_Term|null The chosen category, or null when there isn't a valid one.
 */
function quedamos_rank_math_primary_category( $post_id ) {
	$term_id = (int) get_post_meta( (int) $post_id, 'rank_math_primary_category', true );

	if ( ! $term_id ) {
		return null;
	}

	$term = get_term( $term_id, 'category' );

	if ( ! $term || is_wp_error( $term ) ) {
		return null;
	}

	return has_term( $term_id, 'category', (int) $post_id ) ? $term : null;
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
 * The read-time label for a post, e.g. "6 min read".
 *
 * @param int $post_id Post ID. Defaults to the current post.
 * @return string The read-time label, unescaped — callers escape at output.
 */
function quedamos_read_time_label( $post_id = 0 ) {
	$minutes = quedamos_estimate_read_time( $post_id );

	/* translators: %d: estimated reading time in minutes. */
	return sprintf( _n( '%d min read', '%d min read', $minutes, 'quedamos' ), $minutes );
}

/**
 * One meta item — an icon paired with its label.
 *
 * The class is a parameter because two components render this row: the article
 * header byline and the post card. A card must not wear `article-byline__*`
 * classes — each component owns its own names.
 *
 * @param string $icon  Icon filename within svgs/, e.g. 'clock.svg'.
 * @param string $label The already-escaped label text.
 * @param string $class Wrapper class. Defaults to the article header's.
 * @return string The meta item HTML.
 */
function quedamos_article_meta_item( $icon, $label, $class = 'article-byline__item' ) {
	return sprintf(
		'<span class="%s">%s<span>%s</span></span>',
		esc_attr( $class ),
		quedamos_inline_svg( 'svgs/' . $icon ),
		$label
	);
}

/**
 * The compact publish date for a post card, e.g. "Aug 3" or "Aug 29, 2025".
 *
 * A card has room for a short date, so the year is dropped for anything published
 * this year. It is kept on everything older, because "Aug 29" on a post from last
 * year reads as if it went up a fortnight ago — the blog spans several years, so
 * that matters here. The article header keeps the full "3 August 2026" form; it
 * has the width for it and a reader who has committed to the post wants the date
 * in full.
 *
 * @param int $post_id Post ID. Defaults to the current post.
 * @return string The formatted date, unescaped — callers escape at output.
 */
function quedamos_card_date_label( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : get_the_ID();
	$year    = get_the_date( 'Y', $post_id );

	$format = ( current_time( 'Y' ) === $year ) ? 'M j' : 'M j, Y';

	return (string) get_the_date( $format, $post_id );
}

/**
 * The author's display name for a post.
 *
 * get_the_author() reads the $authordata global, which a block template never
 * populates — inside a query loop it returns an empty string. Resolving from the
 * post's own author ID is the only reliable route in FSE.
 *
 * @param int $post_id Post ID. Defaults to the current post.
 * @return string The display name, or '' when it cannot be resolved.
 */
function quedamos_author_name( $post_id = 0 ) {
	$post_id   = $post_id ? (int) $post_id : get_the_ID();
	$author_id = (int) get_post_field( 'post_author', $post_id );

	return $author_id ? (string) get_the_author_meta( 'display_name', $author_id ) : '';
}

/**
 * The avatar, author, date and read-time row shown on a post card.
 *
 * Deliberately not a shortcode. A shortcode has no access to query-loop context:
 * inside the related-posts grid `get_the_ID()` returns the post being *viewed*,
 * so all three cards rendered the same author and date. The block filter below
 * passes the looped post's ID in explicitly.
 *
 * The read-time is derived from the post's word count by
 * quedamos_estimate_read_time() — there is no field for an author to fill in, and
 * nothing to go stale when a post is edited.
 *
 * @param int $post_id Post ID to build the byline for.
 * @return string The rendered byline HTML, or '' when there is no author to show.
 */
function quedamos_post_card_byline( $post_id ) {
	$post_id = (int) $post_id;

	if ( ! $post_id ) {
		return '';
	}

	$author_id   = (int) get_post_field( 'post_author', $post_id );
	$author_name = quedamos_author_name( $post_id );

	if ( ! $author_name ) {
		return '';
	}

	// 80px for a 40px slot, so the image is sharp on a 2x display. Gravatar
	// serves its own placeholder when the author has no account, which is the
	// grey silhouette rather than a broken image.
	$avatar = $author_id ? get_avatar( $author_id, 80, '', $author_name, array( 'class' => 'post-card__byline-avatar' ) ) : '';

	$meta = quedamos_article_meta_item( 'calendar.svg', esc_html( quedamos_card_date_label( $post_id ) ), 'post-card__meta-item' )
		. quedamos_article_meta_item( 'clock.svg', esc_html( quedamos_read_time_label( $post_id ) ), 'post-card__meta-item' );

	$html = sprintf(
		'<div class="post-card__byline">%s<span class="post-card__byline-text"><span class="post-card__byline-author">%s</span><span class="post-card__byline-meta">%s</span></span></div>',
		$avatar,
		esc_html( $author_name ),
		$meta
	);

	return preg_replace( '/\s*\R\s*/', '', $html );
}

/**
 * Prepend the card byline to a post card's title block.
 *
 * Hooks the card's own title block rather than the group, because a block inside
 * a query loop receives the looped post in its context — which is the only place
 * the correct post ID is available. `$instance->context['postId']` is what makes
 * each of the three cards show its own author, date and read-time.
 *
 * @param string   $block_content The block's rendered HTML.
 * @param array    $block         The parsed block.
 * @param WP_Block $instance      The block instance, carrying query-loop context.
 * @return string The filtered block HTML.
 */
function quedamos_prepend_post_card_byline( $block_content, $block, $instance ) {
	if ( 'core/post-title' !== ( $block['blockName'] ?? '' ) ) {
		return $block_content;
	}

	// Guarded rather than accessed directly: most post-title blocks carry no
	// className at all.
	$class = $block['attrs']['className'] ?? '';

	if ( false === strpos( (string) $class, 'post-card__title' ) ) {
		return $block_content;
	}

	$post_id = $instance->context['postId'] ?? 0;

	return quedamos_post_card_byline( $post_id ) . $block_content;
}
add_filter( 'render_block', 'quedamos_prepend_post_card_byline', 10, 3 );

/**
 * [quedamos_article_byline] — the category pill, date and read-time row.
 *
 * The author is deliberately absent: they are introduced properly on the author
 * bar at the head of the card (inc/blog/article-author.php), with an avatar and
 * a role, so repeating the name on the hero says the same thing twice.
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

	$meta = quedamos_article_meta_item( 'calendar.svg', esc_html( get_the_date( 'j F Y' ) ) )
		. quedamos_article_meta_item( 'clock.svg', esc_html( quedamos_read_time_label() ) );

	$html = sprintf(
		'<div class="article-byline">%s<span class="article-byline__meta">%s</span></div>',
		$pill,
		$meta
	);

	// Shortcode output goes through wpautop(), which turns any newline in here
	// into a stray <br>. Return it as one line.
	return preg_replace( '/\s*\R\s*/', '', $html );
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
 * Pin the "Latest Articles" query loop to the three newest posts.
 *
 * The filter fires with the *post-template* block instance (core builds the
 * query vars there), not the outer wp:query block, and the query block does not
 * pass its className down as context — so this keys on the post-template's own
 * `article-related-grid` className, which is the identifier actually present on
 * the block instance received.
 *
 * Deliberately not scoped to the post's category: the section promises the
 * latest three articles site-wide, and a category-scoped query renders fewer
 * than three cards whenever the category is thin. Only the post being read is
 * excluded, so the count is three wherever there are four or more posts.
 *
 * @param array    $query Query vars the block will run.
 * @param WP_Block $block The block instance (the post-template).
 * @return array Adjusted query vars.
 */
function quedamos_latest_articles_query_vars( $query, $block ) {
	$class = $block->parsed_block['attrs']['className'] ?? '';

	if ( false === strpos( (string) $class, 'article-related-grid' ) || ! is_singular( 'post' ) ) {
		return $query;
	}

	$query['post__not_in']        = array_merge( $query['post__not_in'] ?? array(), array( get_the_ID() ) );
	$query['posts_per_page']      = 3;
	$query['orderby']             = 'date';
	$query['order']               = 'DESC';
	$query['ignore_sticky_posts'] = true;

	return $query;
}
add_filter( 'query_loop_block_query_vars', 'quedamos_latest_articles_query_vars', 10, 2 );
