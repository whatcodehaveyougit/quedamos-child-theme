<?php
/**
 * Booking form — carry the link's course details into the submission.
 *
 * The summary card shows the course, price and currency the visitor clicked
 * through with, but the WPForms form beside it knows none of them: it collects a
 * name, an email and a manual "Online / In Person" choice, so two bookings for
 * two different courses at two different prices arrive as identical emails.
 *
 * Rather than add fields to the form — which lives in the database and so would
 * have to be rebuilt by hand on every environment — the same three values are
 * printed as hidden inputs inside the form and appended to the notification
 * email. Both halves live here, so the whole feature travels with the repo.
 *
 * This is deliberately kept *outside* WPForms' own field pipeline. Injecting
 * fields into the form definition would make them look native in the email, but
 * it would also mean inventing field IDs that must not collide with the ones the
 * form builder hands out, and a plugin update could drop them without a word.
 * A hidden input and an appended block fail visibly instead.
 *
 * @package Quedamos
 */

defined( 'ABSPATH' ) || exit;

/**
 * The POST key the hidden inputs are nested under.
 *
 * Namespaced so nothing here can be mistaken for one of WPForms' own values,
 * which all arrive under `wpforms`.
 */
const QUEDAMOS_BOOKING_POST_KEY = 'quedamos_booking';

/**
 * The details carried from the booking link into the submission.
 *
 * Keyed by POST key, valued by the label the notification email prints.
 *
 * @return array The hidden detail keys and their email labels.
 */
function quedamos_booking_form_details() {
	return array(
		'course'   => __( 'Course', 'quedamos' ),
		'price'    => __( 'Price', 'quedamos' ),
		'currency' => __( 'Currency', 'quedamos' ),
	);
}

/**
 * Print the booking link's details as hidden inputs inside the form.
 *
 * `wpforms_frontend_output` fires between the opening and closing `<form>` tags,
 * so these submit with the form under either the AJAX or the classic path.
 *
 * The values are the raw query parameters rather than the card's formatted
 * output: the email is a record of what was booked, and '149' answers "what do
 * we invoice" where '€149' has already made a decision about presentation.
 *
 * @return void
 */
function quedamos_booking_form_hidden_inputs() {
	if ( ! quedamos_is_booking_summary_page() ) {
		return;
	}

	$data = quedamos_booking_summary_data();

	$values = array(
		'course'   => $data['course'],
		'price'    => $data['course_price'],
		'currency' => $data['currency'],
	);

	foreach ( $values as $key => $value ) {
		printf(
			'<input type="hidden" name="%1$s[%2$s]" value="%3$s">',
			esc_attr( QUEDAMOS_BOOKING_POST_KEY ),
			esc_attr( $key ),
			esc_attr( $value )
		);
	}
}
add_action( 'wpforms_frontend_output', 'quedamos_booking_form_hidden_inputs', 30 );

/**
 * Append the booking details to the notification email.
 *
 * Every form's notification runs through this filter, so the absent POST key on
 * any other form is the gate — no form ID is hardcoded, and none can be, because
 * a form ID is a database ID that differs between local and live.
 *
 * The values are visitor-submitted and land in an email body, so each is escaped
 * on the way in whichever template is in use.
 *
 * @param string $message  The notification body, with {all_fields} already expanded.
 * @param string $template The email template name.
 * @return string The message, with the booking details appended.
 */
function quedamos_booking_append_details_to_email( $message, $template ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WPForms verifies its own submission; these are passenger values on it.
	$submitted = isset( $_POST[ QUEDAMOS_BOOKING_POST_KEY ] ) ? wp_unslash( $_POST[ QUEDAMOS_BOOKING_POST_KEY ] ) : array();

	if ( ! is_array( $submitted ) || empty( $submitted ) ) {
		return $message;
	}

	$is_plain = class_exists( '\WPForms\Emails\Helpers' ) && \WPForms\Emails\Helpers::is_plain_text_template( $template );
	$lines    = array();

	foreach ( quedamos_booking_form_details() as $key => $label ) {
		if ( ! isset( $submitted[ $key ] ) || '' === $submitted[ $key ] ) {
			continue;
		}

		$value = sanitize_text_field( $submitted[ $key ] );

		$lines[] = $is_plain
			? esc_html( $label ) . ': ' . esc_html( $value )
			: '<p><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( $value ) . '</p>';
	}

	if ( empty( $lines ) ) {
		return $message;
	}

	$heading = __( 'Booked from', 'quedamos' );

	return $is_plain
		? $message . "\n\n" . esc_html( $heading ) . "\n" . implode( "\n", $lines )
		: $message . '<h3>' . esc_html( $heading ) . '</h3>' . implode( '', $lines );
}
add_filter( 'wpforms_emails_notifications_message', 'quedamos_booking_append_details_to_email', 10, 2 );
