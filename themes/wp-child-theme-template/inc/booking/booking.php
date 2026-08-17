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
 * Read the summary card's display values out of the query string.
 *
 * One source of truth for both the card and the data handed to the bundle, so
 * the figure JavaScript multiplies cannot drift from the one PHP printed.
 *
 * These are unauthenticated display values, so each is sanitised on the way in
 * and escaped again at output in the view; no nonce is required because nothing
 * is mutated.
 *
 * @return array The course, price, location, currency symbol, participant count and subtotal.
 */
function quedamos_booking_summary_data() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only display of link parameters.
	$course       = isset( $_GET['qd_course'] ) ? sanitize_text_field( wp_unslash( $_GET['qd_course'] ) ) : __( 'Course Name', 'quedamos' );
	$course_price = isset( $_GET['qd_price'] ) ? (float) wp_unslash( $_GET['qd_price'] ) : 0.0;
	$location     = isset( $_GET['qd_location'] ) ? sanitize_text_field( wp_unslash( $_GET['qd_location'] ) ) : '';
	$currency     = isset( $_GET['qd_currency'] ) ? sanitize_text_field( wp_unslash( $_GET['qd_currency'] ) ) : '';
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	$participants = 1;

	return array(
		'course'          => $course,
		'course_price'    => $course_price,
		'location'        => $location,
		'currency_symbol' => quedamos_booking_currency_symbol( $currency ),
		'participants'    => $participants,
		'subtotal'        => $course_price * $participants,
	);
}

/**
 * [booking_summary] — render the booking summary card.
 *
 * @return string The rendered summary card HTML.
 */
function quedamos_booking_summary_shortcode() {
	$data = quedamos_booking_summary_data();

	$course          = $data['course'];
	$course_price    = $data['course_price'];
	$location        = $data['location'];
	$currency_symbol = $data['currency_symbol'];
	$participants    = $data['participants'];
	$subtotal        = $data['subtotal'];

	ob_start();
	include get_stylesheet_directory() . '/inc/booking/parts/summary-card.php';

	return ob_get_clean();
}
add_shortcode( 'booking_summary', 'quedamos_booking_summary_shortcode' );

/**
 * Hand the price and currency symbol to the bundle.
 *
 * This has to hook `wp_enqueue_scripts` and cannot be done from the shortcode:
 * this is a block theme, so the template — and with it the shortcode — renders
 * *before* `wp_enqueue_scripts` fires. At shortcode time `parcel-js` is not
 * registered yet, and `wp_localize_script()` against an unregistered handle
 * returns false without warning, so the data silently never reaches the page.
 *
 * Priority 20 puts this after the assets module registers the handle at 10.
 *
 * @return void
 */
function quedamos_booking_localize_summary() {
	if ( ! is_singular() ) {
		return;
	}

	$post = get_post();

	if ( ! $post || ! has_shortcode( $post->post_content, 'booking_summary' ) ) {
		return;
	}

	$data = quedamos_booking_summary_data();

	wp_localize_script(
		'parcel-js',
		'quedamosBooking',
		array(
			'coursePrice'    => $data['course_price'],
			'currencySymbol' => $data['currency_symbol'],
		)
	);
}
add_action( 'wp_enqueue_scripts', 'quedamos_booking_localize_summary', 20 );

/**
 * Map a booking currency to the symbol the summary card prints.
 *
 * The site sells in two currencies — Edinburgh courses in pounds, Mallorca ones
 * in euros — so the booking link says which. An unrecognised or missing value
 * falls back to pounds, the currency every existing booking link implies.
 *
 * @param string $currency The raw currency value from the query string, 'pounds' or 'euros'.
 * @return string The currency symbol.
 */
function quedamos_booking_currency_symbol( $currency ) {
	switch ( strtolower( $currency ) ) {
		case 'euros':
			return '€';
		case 'pounds':
		default:
			return '£';
	}
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
			return __( 'McDonald Road Library', 'quedamos' );
		default:
			return $location;
	}
}
