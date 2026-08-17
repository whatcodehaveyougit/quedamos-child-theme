<?php
/**
 * Schema.org output.
 *
 * The [acf_schema] shortcode, which emits the JSON-LD block authored in the
 * post's ACF `schema` field.
 *
 * @package Quedamos
 */

defined( 'ABSPATH' ) || exit;

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
