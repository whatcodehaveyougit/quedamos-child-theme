<?php
/**
 * Schema.org output.
 *
 * Two sources, deliberately separate: the [acf_schema] shortcode emits whatever
 * JSON-LD has been hand-authored into a post's ACF `schema` field, while
 * person.php generates the author entity in code and repairs Rank Math's graph
 * around it. Anything that should hold for every post belongs in the generated
 * half — a field somebody has to remember to fill in is not a sitewide guarantee.
 *
 * @package Quedamos
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/person.php';

/**
 * [acf_schema] — emit the current post's hand-authored JSON-LD.
 *
 * Returns an empty string when the field is unset or ACF is inactive, so the
 * shortcode is a no-op rather than an empty <script> tag.
 *
 * @return string The JSON-LD script tag, or '' when no schema is set.
 */
function quedamos_acf_schema_shortcode() {
	if ( ! function_exists( 'get_field' ) ) {
		return '';
	}

	$schema = get_field( 'schema' );

	if ( ! $schema ) {
		return '';
	}

	return '<script type="application/ld+json">' . wp_kses_post( $schema ) . '</script>';
}
add_shortcode( 'acf_schema', 'quedamos_acf_schema_shortcode' );
