<?php
/**
 * Redirects module.
 *
 * The single home for this site's permanent (301) redirects. When a page is
 * renamed, merged into another or deleted, its old path goes in the map below
 * — not in a plugin, not in .htaccess, not in a one-off hook somewhere else.
 * One map means one place to look when a URL behaves unexpectedly, and the
 * whole redirect table travels with the theme in git.
 *
 * A redirect here fires whether or not the old page still exists, so the entry
 * can be added before the page is deleted.
 *
 * @package Quedamos
 */

defined( 'ABSPATH' ) || exit;

/**
 * The redirect map: old path => new path.
 *
 * Both sides are site-relative paths with a leading and trailing slash. Keys
 * are matched case-insensitively and the incoming query string is carried
 * across, so '/old-page/?utm_source=x' lands on '/new-page/?utm_source=x'.
 *
 * Keep one comment line per entry saying why it exists — a redirect with no
 * stated reason is one nobody will ever dare remove.
 *
 * @return array<string, string> Old path => new path.
 */
function quedamos_redirect_map() {
	return array(
		// Duplicate of /booking-form/ created 2025-07-01: same title, no form on
		// it, no inbound internal links. Removed 2026-08-17 to stop the two pages
		// competing for the same search result.
		'/booking-form-2/'                => '/booking-form/',

		// The About page was renamed 2026-08-17 ('…-tutor-' → '…-tutor-teaching-'),
		// leaving the URL it had been indexed at since 2024 returning a hard 404.
		// It is also the author's profile page, so the dead URL took the site's
		// only credentialed Person entity with it.
		'/about-spanish-tutor-edinburgh/' => '/about-spanish-tutor-teaching-edinburgh/',
	);
}

/**
 * Normalise a URL or path for comparison against the redirect map.
 *
 * Strips the query string and fragment, lowercases, and forces exactly one
 * leading and trailing slash — so '/Booking-Form-2', '/booking-form-2/' and
 * '/booking-form-2/?ref=x' all reduce to the same key.
 *
 * @param string $path A path or full URL.
 * @return string The normalised path, always starting and ending in a slash.
 */
function quedamos_normalise_redirect_path( $path ) {
	$path = wp_parse_url( $path, PHP_URL_PATH );

	if ( ! is_string( $path ) || '' === trim( $path, '/' ) ) {
		return '/';
	}

	return strtolower( '/' . trim( $path, '/' ) . '/' );
}

/**
 * Send a 301 when the current request matches the redirect map.
 *
 * Hooked at priority 1 so it runs ahead of WordPress's own canonical redirect,
 * which would otherwise resolve the old URL first.
 *
 * @return void
 */
function quedamos_do_redirects() {
	if ( is_admin() || wp_doing_ajax() || empty( $_SERVER['REQUEST_URI'] ) ) {
		return;
	}

	$request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
	$requested   = quedamos_normalise_redirect_path( $request_uri );
	$map         = quedamos_redirect_map();

	if ( ! isset( $map[ $requested ] ) ) {
		return;
	}

	$destination = quedamos_normalise_redirect_path( $map[ $requested ] );

	// A map entry pointing at itself would loop the browser until it gave up.
	if ( $destination === $requested ) {
		return;
	}

	$query = wp_parse_url( $request_uri, PHP_URL_QUERY );

	if ( ! empty( $query ) ) {
		$destination .= '?' . $query;
	}

	wp_safe_redirect( home_url( $destination ), 301, 'Quedamos' );
	exit;
}
add_action( 'template_redirect', 'quedamos_do_redirects', 1 );
