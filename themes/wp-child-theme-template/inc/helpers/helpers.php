<?php
/**
 * Cross-cutting helpers.
 *
 * Utilities used by more than one module. Anything specific to a single module
 * belongs in that module, not here.
 *
 * @package Quedamos
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/author-identity.php';
require_once __DIR__ . '/synced-block.php';

/**
 * Whether the current request is running on the live site.
 *
 * Matches on the host of home_url() rather than a WP_ENVIRONMENT_TYPE constant:
 * that constant is unset in this install and defaults to 'production', which
 * would report true on Local as well. Matching the live domain positively means
 * local, staging and any future clone all fall through to false by default.
 *
 * @return bool True only on the production domain.
 */
function quedamos_is_live_site() {
	$host = wp_parse_url( home_url(), PHP_URL_HOST );

	return in_array( $host, array( 'quedamoslanguages.com', 'www.quedamoslanguages.com' ), true );
}

/**
 * Inline an SVG file from the theme's svgs/ folder.
 *
 * Reads the file, adds an `inline-svg` class so it can be targeted and
 * recoloured via CSS (`currentColor`), and runs the markup through wp_kses with
 * an SVG-aware allowlist. Returns an empty string when the file is missing, so
 * a typo'd filename degrades to nothing rather than a fatal error.
 *
 * Use this instead of pasting raw <svg> markup into a template — one source of
 * truth per icon, greppable by filename.
 *
 * @param string $relative_path Path relative to the theme root, e.g. 'svgs/close.svg'.
 * @return string Sanitised inline SVG markup, or '' when the file is missing.
 */
function quedamos_inline_svg( $relative_path ) {
	$path = get_stylesheet_directory() . '/' . ltrim( $relative_path, '/' );

	if ( ! file_exists( $path ) ) {
		return '';
	}

	$svg = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

	if ( false === $svg ) {
		return '';
	}

	$svg = preg_replace( '/<svg\b/', '<svg class="inline-svg"', $svg, 1 );

	// Collapse the file's own newlines: when an icon is inlined into shortcode
	// output, wpautop() turns each one into a stray <br> inside the markup.
	$svg = preg_replace( '/\s*\R\s*/', '', $svg );

	return wp_kses( trim( $svg ), quedamos_svg_allowed_html() );
}

/**
 * The wp_kses allowlist for inline SVG markup.
 *
 * Kept separate from quedamos_inline_svg() so the allowlist has one home and can
 * be extended without touching the reader.
 *
 * @return array Allowed tags and attributes for SVG output.
 */
function quedamos_svg_allowed_html() {
	$shared = array(
		'fill'            => true,
		'stroke'          => true,
		'stroke-width'    => true,
		'stroke-linecap'  => true,
		'stroke-linejoin' => true,
		'opacity'         => true,
		'transform'       => true,
	);

	return array(
		'svg'      => array_merge(
			$shared,
			array(
				'class'       => true,
				'xmlns'       => true,
				'viewbox'     => true,
				'width'       => true,
				'height'      => true,
				'aria-hidden' => true,
				'role'        => true,
				'focusable'   => true,
			)
		),
		'title'    => array(),
		'g'        => $shared,
		'path'     => array_merge( $shared, array( 'd' => true ) ),
		'circle'   => array_merge( $shared, array( 'cx' => true, 'cy' => true, 'r' => true ) ),
		'rect'     => array_merge( $shared, array( 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true ) ),
		'line'     => array_merge( $shared, array( 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true ) ),
		'polyline' => array_merge( $shared, array( 'points' => true ) ),
		'polygon'  => array_merge( $shared, array( 'points' => true ) ),
	);
}
