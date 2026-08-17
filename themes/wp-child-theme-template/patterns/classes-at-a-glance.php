<?php
/**
 * Title: Classes at a glance
 * Slug: quedamos/classes-at-a-glance
 * Categories: featured, columns, call-to-action
 * Keywords: classes, timetable, levels, prices, booking
 * Viewport width: 1200
 * Description: A comparison card for a course page — two level groups, each split into In Person and Online columns carrying day and time, venue, price and a Book Now button.
 *
 * The first block pattern in this theme. Core auto-registers every patterns/*.php
 * file for a block theme, so nothing here is wired up in functions.php.
 *
 * Composed from core blocks only and left entirely unlocked: the content differs
 * per course page, so this is a plain (unsynced) pattern that is inserted once
 * and then edited freely in Gutenberg. Every class name below is a styling hook
 * for assets/styles/scss/components/class-table.scss — the structure and the
 * look are versioned here, the words are not.
 *
 * The days, times, prices and availability notes are placeholders for an editor
 * to replace. The price deliberately appears twice — in the list text and in the
 * Book Now URL — which an editor has to keep in step by hand; the eventual fix
 * is an ACF repeater feeding a shortcode that reuses this markup.
 *
 * @package Quedamos
 */

defined( 'ABSPATH' ) || exit;

/**
 * Placeholder Book Now links.
 *
 * The booking page reads qd_course, qd_price, qd_location and qd_currency from
 * the query string (see inc/booking/booking.php), so the link has to carry them
 * for [booking_summary] to render anything but its defaults. The location values
 * are the codes quedamos_booking_location_label() maps, spelt the way the course
 * pages already spell them — 'inPerson' and 'online' — not free text. qd_currency
 * is stated rather than left to its 'pounds' fallback, because a link that does
 * not say quotes pounds silently: an editor moving one of these rows to a
 * euro-priced venue would otherwise ship the right figure with the wrong symbol.
 *
 * add_query_arg() does not URL-encode its values, so anything with a space is
 * encoded here before it goes in.
 */
$quedamos_booking_page = home_url( '/booking-form/' );

$quedamos_class_links = array(
	'a1-in-person' => add_query_arg(
		array(
			'qd_course'   => rawurlencode( 'Beginner Spanish (A1)' ),
			'qd_price'    => '120',
			'qd_location' => 'inPerson',
			'qd_currency' => 'pounds',
		),
		$quedamos_booking_page
	),
	'a1-online'    => add_query_arg(
		array(
			'qd_course'   => rawurlencode( 'Beginner Spanish (A1)' ),
			'qd_price'    => '120',
			'qd_location' => 'online',
			'qd_currency' => 'pounds',
		),
		$quedamos_booking_page
	),
	'a2-in-person' => add_query_arg(
		array(
			'qd_course'   => rawurlencode( 'Elementary Spanish (A2)' ),
			'qd_price'    => '120',
			'qd_location' => 'inPerson',
			'qd_currency' => 'pounds',
		),
		$quedamos_booking_page
	),
	'a2-online'    => add_query_arg(
		array(
			'qd_course'   => rawurlencode( 'Elementary Spanish (A2)' ),
			'qd_price'    => '120',
			'qd_location' => 'online',
			'qd_currency' => 'pounds',
		),
		$quedamos_booking_page
	),
);
?>

<!-- wp:group {"className":"class-table","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group class-table">
	<!-- wp:group {"className":"class-table__header","style":{"spacing":{"blockGap":"var:preset|spacing|10"}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"center"}} -->
	<div class="wp-block-group class-table__header">
		<!-- wp:heading {"className":"class-table__title"} -->
		<h2 class="wp-block-heading class-table__title">Classes at a glance</h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"className":"class-table__note"} -->
		<p class="class-table__note">Not sure of your level? Book a free assessment.</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:columns {"className":"class-table__levels","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|20","left":"var:preset|spacing|20"}}}} -->
	<div class="wp-block-columns class-table__levels">
		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"class-table__level","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"constrained"}} -->
			<div class="wp-block-group class-table__level">
				<!-- wp:group {"className":"class-table__bar","style":{"spacing":{"blockGap":"var:preset|spacing|10"}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"center"}} -->
				<div class="wp-block-group class-table__bar">
					<!-- wp:heading {"level":3,"className":"class-table__level-name"} -->
					<h3 class="wp-block-heading class-table__level-name">Beginner Spanish</h3>
					<!-- /wp:heading -->

					<!-- wp:paragraph {"className":"class-table__level-code"} -->
					<p class="class-table__level-code">A1</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->

				<!-- wp:columns {"className":"class-table__formats","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|10","left":"var:preset|spacing|10"}}}} -->
				<div class="wp-block-columns class-table__formats">
					<!-- wp:column -->
					<div class="wp-block-column">
						<!-- wp:group {"className":"class-table__col","style":{"spacing":{"blockGap":"var:preset|spacing|10"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch"}} -->
						<div class="wp-block-group class-table__col">
							<!-- wp:heading {"level":4,"className":"class-table__col-title"} -->
							<h4 class="wp-block-heading class-table__col-title">In Person</h4>
							<!-- /wp:heading -->

							<!-- wp:list {"className":"class-table__meta"} -->
							<ul class="wp-block-list class-table__meta">
								<!-- wp:list-item -->
								<li>📅 Tuesdays, 6.00–7.30pm</li>
								<!-- /wp:list-item -->

								<!-- wp:list-item -->
								<li>📍 McDonald Road Library</li>
								<!-- /wp:list-item -->

								<!-- wp:list-item -->
								<li>💷 £120 for a block of 5 classes</li>
								<!-- /wp:list-item -->
							</ul>
							<!-- /wp:list -->

							<!-- wp:paragraph {"className":"class-table__availability"} -->
							<p class="class-table__availability">Places available</p>
							<!-- /wp:paragraph -->

							<!-- wp:buttons {"className":"class-table__cta"} -->
							<div class="wp-block-buttons class-table__cta">
								<!-- wp:button {"width":100} -->
								<div class="wp-block-button has-custom-width wp-block-button__width-100"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( $quedamos_class_links['a1-in-person'] ); ?>">Book Now</a></div>
								<!-- /wp:button -->
							</div>
							<!-- /wp:buttons -->
						</div>
						<!-- /wp:group -->
					</div>
					<!-- /wp:column -->

					<!-- wp:column -->
					<div class="wp-block-column">
						<!-- wp:group {"className":"class-table__col","style":{"spacing":{"blockGap":"var:preset|spacing|10"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch"}} -->
						<div class="wp-block-group class-table__col">
							<!-- wp:heading {"level":4,"className":"class-table__col-title"} -->
							<h4 class="wp-block-heading class-table__col-title">Online</h4>
							<!-- /wp:heading -->

							<!-- wp:list {"className":"class-table__meta"} -->
							<ul class="wp-block-list class-table__meta">
								<!-- wp:list-item -->
								<li>📅 Thursdays, 6.00–7.30pm</li>
								<!-- /wp:list-item -->

								<!-- wp:list-item -->
								<li>📍 Online</li>
								<!-- /wp:list-item -->

								<!-- wp:list-item -->
								<li>💷 £120 for a block of 5 classes</li>
								<!-- /wp:list-item -->
							</ul>
							<!-- /wp:list -->

							<!-- wp:paragraph {"className":"class-table__availability"} -->
							<p class="class-table__availability">Places available</p>
							<!-- /wp:paragraph -->

							<!-- wp:buttons {"className":"class-table__cta"} -->
							<div class="wp-block-buttons class-table__cta">
								<!-- wp:button {"width":100} -->
								<div class="wp-block-button has-custom-width wp-block-button__width-100"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( $quedamos_class_links['a1-online'] ); ?>">Book Now</a></div>
								<!-- /wp:button -->
							</div>
							<!-- /wp:buttons -->
						</div>
						<!-- /wp:group -->
					</div>
					<!-- /wp:column -->
				</div>
				<!-- /wp:columns -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"class-table__level","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"constrained"}} -->
			<div class="wp-block-group class-table__level">
				<!-- wp:group {"className":"class-table__bar","style":{"spacing":{"blockGap":"var:preset|spacing|10"}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"center"}} -->
				<div class="wp-block-group class-table__bar">
					<!-- wp:heading {"level":3,"className":"class-table__level-name"} -->
					<h3 class="wp-block-heading class-table__level-name">Elementary Spanish</h3>
					<!-- /wp:heading -->

					<!-- wp:paragraph {"className":"class-table__level-code"} -->
					<p class="class-table__level-code">A2</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->

				<!-- wp:columns {"className":"class-table__formats","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|10","left":"var:preset|spacing|10"}}}} -->
				<div class="wp-block-columns class-table__formats">
					<!-- wp:column -->
					<div class="wp-block-column">
						<!-- wp:group {"className":"class-table__col","style":{"spacing":{"blockGap":"var:preset|spacing|10"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch"}} -->
						<div class="wp-block-group class-table__col">
							<!-- wp:heading {"level":4,"className":"class-table__col-title"} -->
							<h4 class="wp-block-heading class-table__col-title">In Person</h4>
							<!-- /wp:heading -->

							<!-- wp:list {"className":"class-table__meta"} -->
							<ul class="wp-block-list class-table__meta">
								<!-- wp:list-item -->
								<li>📅 Wednesdays, 6.00–7.30pm</li>
								<!-- /wp:list-item -->

								<!-- wp:list-item -->
								<li>📍 McDonald Road Library</li>
								<!-- /wp:list-item -->

								<!-- wp:list-item -->
								<li>💷 £120 for a block of 5 classes</li>
								<!-- /wp:list-item -->
							</ul>
							<!-- /wp:list -->

							<!-- wp:paragraph {"className":"class-table__availability"} -->
							<p class="class-table__availability">Places available</p>
							<!-- /wp:paragraph -->

							<!-- wp:buttons {"className":"class-table__cta"} -->
							<div class="wp-block-buttons class-table__cta">
								<!-- wp:button {"width":100} -->
								<div class="wp-block-button has-custom-width wp-block-button__width-100"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( $quedamos_class_links['a2-in-person'] ); ?>">Book Now</a></div>
								<!-- /wp:button -->
							</div>
							<!-- /wp:buttons -->
						</div>
						<!-- /wp:group -->
					</div>
					<!-- /wp:column -->

					<!-- wp:column -->
					<div class="wp-block-column">
						<!-- wp:group {"className":"class-table__col","style":{"spacing":{"blockGap":"var:preset|spacing|10"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch"}} -->
						<div class="wp-block-group class-table__col">
							<!-- wp:heading {"level":4,"className":"class-table__col-title"} -->
							<h4 class="wp-block-heading class-table__col-title">Online</h4>
							<!-- /wp:heading -->

							<!-- wp:list {"className":"class-table__meta"} -->
							<ul class="wp-block-list class-table__meta">
								<!-- wp:list-item -->
								<li>📅 Mondays, 6.00–7.30pm</li>
								<!-- /wp:list-item -->

								<!-- wp:list-item -->
								<li>📍 Online</li>
								<!-- /wp:list-item -->

								<!-- wp:list-item -->
								<li>💷 £120 for a block of 5 classes</li>
								<!-- /wp:list-item -->
							</ul>
							<!-- /wp:list -->

							<!-- wp:paragraph {"className":"class-table__availability"} -->
							<p class="class-table__availability">Places available</p>
							<!-- /wp:paragraph -->

							<!-- wp:buttons {"className":"class-table__cta"} -->
							<div class="wp-block-buttons class-table__cta">
								<!-- wp:button {"width":100} -->
								<div class="wp-block-button has-custom-width wp-block-button__width-100"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( $quedamos_class_links['a2-online'] ); ?>">Book Now</a></div>
								<!-- /wp:button -->
							</div>
							<!-- /wp:buttons -->
						</div>
						<!-- /wp:group -->
					</div>
					<!-- /wp:column -->
				</div>
				<!-- /wp:columns -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->

<?php
unset( $quedamos_booking_page, $quedamos_class_links );
