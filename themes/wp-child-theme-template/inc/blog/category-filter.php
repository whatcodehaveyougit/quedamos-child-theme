<?php
/**
 * The blog listing's category filter row.
 *
 * A row of pills above the listing on templates/home.html and
 * templates/category.html. Every pill is a real link to a real category archive
 * URL, so the row works with JavaScript disabled; assets/scripts/js/blog-filter.js
 * upgrades a click into a fetch-and-swap of the same URL.
 *
 * Nothing here knows the names, slugs, order or number of the categories — they
 * all come from get_categories(), so live and local can differ freely.
 *
 * Each pill carries its post count. WordPress has no native "primary category",
 * and it needs none here: every published post on this site carries exactly one
 * category, so a term's own count IS its primary-category count and the category
 * counts sum to the All total. File a post under two categories and it would be
 * counted under both — the sum would then exceed All, which is the visible sign
 * that this site has outgrown the assumption and wants a real primary-category
 * field (Rank Math already stores one) behind these numbers.
 *
 * @package Quedamos
 */

defined( 'ABSPATH' ) || exit;

/**
 * The URL of the blog listing — the "All" pill's destination.
 *
 * Resolved from the posts page rather than a literal path, so a rename of the
 * page does not leave a dead pill behind. Falls back to the site root, which is
 * where WordPress puts the posts index when no posts page is set.
 *
 * @return string The listing URL.
 */
function quedamos_blog_listing_url() {
	$posts_page = (int) get_option( 'page_for_posts' );
	$permalink  = $posts_page ? get_permalink( $posts_page ) : '';

	return $permalink ? $permalink : home_url( '/' );
}

/**
 * How many published posts the listing holds — the "All" pill's count.
 *
 * Counted from the post type rather than by summing the category counts below,
 * so a post filed under no category still shows up in the total. The two agree
 * today; if they ever diverge, the total is the honest number.
 *
 * @return int Published post count.
 */
function quedamos_blog_published_post_count() {
	$counts = wp_count_posts( 'post' );

	return isset( $counts->publish ) ? (int) $counts->publish : 0;
}

/**
 * One filter pill.
 *
 * The current pill carries both a modifier class and aria-current: the class is
 * what makes it visibly distinct on a touch device, where the hover cue in the
 * comp does not exist at all.
 *
 * The count is baked into the label rather than wrapped in its own element:
 * nothing styles it separately yet, and inventing a span the stylesheet has no
 * opinion about would be markup with nobody to answer for it. Wrap it the day
 * the design asks the number to look different from the name.
 *
 * @param string $url        Destination URL, unescaped.
 * @param string $label      Pill label, unescaped.
 * @param bool   $is_current Whether this pill is the archive being viewed.
 * @param int    $count      Posts behind this pill.
 * @return string The pill HTML.
 */
function quedamos_category_filter_pill( $url, $label, $is_current, $count ) {
	$label_with_count = sprintf(
		/* translators: 1: category name, 2: number of posts in that category. */
		__( '%1$s (%2$s)', 'quedamos' ),
		$label,
		number_format_i18n( $count )
	);

	return sprintf(
		'<a class="category-filter__pill%1$s" href="%2$s"%3$s>%4$s</a>',
		$is_current ? ' category-filter__pill--current' : '',
		esc_url( $url ),
		$is_current ? ' aria-current="page"' : '',
		esc_html( $label_with_count )
	);
}

/**
 * [quedamos_category_filter] — the row of category pills above the blog listing.
 *
 * Emits nothing at all when the site has no non-empty categories, rather than an
 * empty bar with a lone "All" pill in it.
 *
 * Built as a single-line string on purpose: wpautop() turns newlines in
 * shortcode output into stray <br> tags. The pills are inline <a> elements for
 * the same reason — a nested block-level tag makes wpautop close a paragraph
 * early. See .claude/skills/writing-php/SKILL.md §1.
 *
 * @return string The rendered filter row, or '' when there are no categories.
 */
function quedamos_category_filter_shortcode() {
	$categories = get_categories(
		array(
			'hide_empty' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);

	if ( empty( $categories ) || is_wp_error( $categories ) ) {
		return '';
	}

	// 0 means "not on a category archive", which is the state the All pill owns.
	$current_id = is_category() ? (int) get_queried_object_id() : 0;

	$pills = quedamos_category_filter_pill(
		quedamos_blog_listing_url(),
		__( 'All', 'quedamos' ),
		0 === $current_id,
		quedamos_blog_published_post_count()
	);

	foreach ( $categories as $category ) {
		$pills .= quedamos_category_filter_pill(
			get_category_link( $category->term_id ),
			$category->name,
			(int) $category->term_id === $current_id,
			(int) $category->count
		);
	}

	$html = sprintf(
		'<nav class="category-filter" aria-label="%1$s">%2$s</nav>',
		esc_attr__( 'Filter articles by category', 'quedamos' ),
		$pills
	);

	return preg_replace( '/\s*\R\s*/', '', $html );
}
add_shortcode( 'quedamos_category_filter', 'quedamos_category_filter_shortcode' );
