<?php
/**
 * How many posts the blog listing shows per page.
 *
 * Both templates/home.html and templates/category.html run their core/query with
 * `inherit: true`, so the main query — not the block's own `perPage` attribute —
 * decides how many cards render. Left alone that number comes from Settings →
 * Reading, which is database state: it would not travel with the repo, it would
 * need a manual step on every environment, and it would change every archive on
 * the site rather than the two templates this task owns.
 *
 * Setting it here instead keeps the listing's page size version controlled and
 * scoped to the blog index and category archives. Search, tag, author and date
 * archives keep the Reading setting.
 *
 * @package Quedamos
 */

defined( 'ABSPATH' ) || exit;

/**
 * Posts shown per page on the blog listing and its category archives.
 */
const QUEDAMOS_BLOG_POSTS_PER_PAGE = 20;

/**
 * `pre_get_posts` — set the listing's page size.
 *
 * Guarded to the front end and the main query so neither the dashboard's post
 * list nor any secondary query (the related-posts loop, the mobile menu lookup)
 * can be caught by it.
 *
 * @param WP_Query $query The query about to run.
 * @return void
 */
function quedamos_set_blog_posts_per_page( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( ! $query->is_home() && ! $query->is_category() ) {
		return;
	}

	$query->set( 'posts_per_page', QUEDAMOS_BLOG_POSTS_PER_PAGE );
}
add_action( 'pre_get_posts', 'quedamos_set_blog_posts_per_page' );
