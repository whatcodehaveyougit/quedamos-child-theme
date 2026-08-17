<?php
/**
 * Global theme asset enqueue.
 *
 * The parent and child stylesheets plus the Parcel bundle (dist/). Only
 * theme-wide assets belong here — anything needed by a single feature is
 * enqueued from that feature's own module.
 *
 * @package Quedamos
 */

defined( 'ABSPATH' ) || exit;

/**
 * Enqueue the theme stylesheets and the Parcel bundle.
 *
 * Versions come from filemtime() on the built files so a deploy busts the cache
 * without anyone remembering to bump a version string. The dist/ files are
 * generated (`npm run build`) and gitignored, so each is guarded — a missing
 * bundle degrades to an unversioned enqueue rather than a fatal error.
 *
 * @return void
 */
function quedamos_enqueue_assets() {
	$theme_dir = get_stylesheet_directory();
	$theme_uri = get_stylesheet_directory_uri();

	wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css', array(), null );

	wp_enqueue_style(
		'child-style',
		$theme_uri . '/style.css',
		array( 'parent-style' ),
		quedamos_asset_version( $theme_dir . '/style.css' )
	);

	wp_enqueue_style(
		'parcel',
		$theme_uri . '/dist/styles/style.css',
		array(),
		quedamos_asset_version( $theme_dir . '/dist/styles/style.css' )
	);

	wp_enqueue_script(
		'parcel-js',
		$theme_uri . '/dist/scripts/scripts.js',
		array(),
		quedamos_asset_version( $theme_dir . '/dist/scripts/scripts.js' ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'quedamos_enqueue_assets' );

/**
 * A cache-busting version string for a theme asset.
 *
 * filemtime() emits a warning and returns false for a missing file, which is a
 * real possibility for anything under dist/ before the first build — so the
 * existence check comes first and null is returned instead, letting WordPress
 * fall back to the WP version.
 *
 * @param string $path Absolute path to the asset on disk.
 * @return string|null The file's modification time, or null when it is missing.
 */
function quedamos_asset_version( $path ) {
	return file_exists( $path ) ? (string) filemtime( $path ) : null;
}
