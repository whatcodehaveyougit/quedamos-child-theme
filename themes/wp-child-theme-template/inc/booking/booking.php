<?php
/**
 * Booking summary.
 *
 * The [booking_summary] shortcode, which renders a read-only summary card
 * alongside the booking form. Its values arrive as query parameters from the
 * course page's "Book now" link, so everything here is display-only — no state
 * is written and nothing is trusted.
 *
 * @package Quedamos
 */

defined( 'ABSPATH' ) || exit;

/**
 * [booking_summary] — render the booking summary card.
 *
 * Reads the course, price, start date and location from the query string. These
 * are unauthenticated display values, so each is sanitised on the way in and
 * escaped again at output in the view; no nonce is required because nothing is
 * mutated.
 *
 * @return string The rendered summary card HTML.
 */
function quedamos_booking_summary_shortcode() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only display of link parameters.
	$course       = isset( $_GET['qd_course'] ) ? sanitize_text_field( wp_unslash( $_GET['qd_course'] ) ) : __( 'Course Name', 'quedamos' );
	$course_price = isset( $_GET['qd_price'] ) ? (float) wp_unslash( $_GET['qd_price'] ) : 0.0;
	$start_date   = isset( $_GET['qd_start_date'] ) ? sanitize_text_field( wp_unslash( $_GET['qd_start_date'] ) ) : '';
	$location     = isset( $_GET['qd_location'] ) ? sanitize_text_field( wp_unslash( $_GET['qd_location'] ) ) : '';
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	$participants = 1;
	$subtotal     = $course_price * $participants;

	ob_start();
	include get_stylesheet_directory() . '/inc/booking/parts/summary-card.php';

	return ob_get_clean();
}
add_shortcode( 'booking_summary', 'quedamos_booking_summary_shortcode' );

/**
 * Format a course start date for display, e.g. "3rd March 2025".
 *
 * Only reformats a strict Y-m-d value; anything else (including an empty string
 * or a date already in display form) is passed through untouched, which is what
 * the summary card relied on before this was extracted from the view.
 *
 * @param string $start_date The raw start date from the query string.
 * @return string A human-readable date, or the input unchanged.
 */
function quedamos_format_course_date( $start_date ) {
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start_date ) ) {
		return $start_date;
	}

	$timestamp = strtotime( $start_date );

	if ( false === $timestamp ) {
		return $start_date;
	}

	return wp_date( 'jS F Y', $timestamp );
}

/**
 * Map a booking location code to its display name.
 *
 * @param string $location The raw location value from the query string.
 * @return string The venue name, or the input unchanged when it isn't a known code.
 */
function quedamos_booking_location_label( $location ) {
	switch ( strtolower( $location ) ) {
		case 'online':
			return __( 'Online', 'quedamos' );
		case 'inperson':
			return __( 'MacDonald Road Library', 'quedamos' );
		default:
			return $location;
	}
}
