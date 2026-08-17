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
 * One filter pill.
 *
 * The current pill carries both a modifier class and aria-current: the class is
 * what makes it visibly distinct on a touch device, where the hover cue in the
 * comp does not exist at all.
 *
 * @param string $url        Destination URL, unescaped.
 * @param string $label      Pill label, unescaped.
 * @param bool   $is_current Whether this pill is the archive being viewed.
 * @return string The pill HTML.
 */
function quedamos_category_filter_pill( $url, $label, $is_current ) {
	return sprintf(
		'<a class="category-filter__pill%1$s" href="%2$s"%3$s>%4$s</a>',
		$is_current ? ' category-filter__pill--current' : '',
		esc_url( $url ),
		$is_current ? ' aria-current="page"' : '',
		esc_html( $label )
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
		0 === $current_id
	);

	foreach ( $categories as $category ) {
		$pills .= quedamos_category_filter_pill(
			get_category_link( $category->term_id ),
			$category->name,
			(int) $category->term_id === $current_id
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
