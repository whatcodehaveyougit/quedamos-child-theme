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
 * Reads the course, price and location from the query string. These
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
			return __( 'McDonald Road Library', 'quedamos' );
		default:
			return $location;
	}
}
