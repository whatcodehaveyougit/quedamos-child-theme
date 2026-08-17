<?php
/**
 * Google Analytics and Search Console verification.
 *
 * Both outputs are production-only. They are gated on quedamos_is_live_site()
 * rather than left unconditional so local and staging traffic never lands in the
 * live GA property — the analytics tag ran only on live for months before it was
 * version-controlled, and that behaviour is preserved here deliberately.
 *
 * @package Quedamos
 */

defined( 'ABSPATH' ) || exit;

/**
 * The GA4 measurement ID for quedamoslanguages.com.
 */
const QUEDAMOS_GA_MEASUREMENT_ID = 'G-84EL3ML0D2';

/**
 * The Google Search Console site-verification token.
 */
const QUEDAMOS_GOOGLE_SITE_VERIFICATION = 'M0ai-YlNd-1QADH-_SSVnMMBmiSgPCzEz8ZjAUqgZho';

/**
 * Output the Search Console verification meta tag.
 *
 * @return void
 */
function quedamos_google_site_verification_meta() {
	if ( ! quedamos_is_live_site() ) {
		return;
	}

	printf(
		'<meta name="google-site-verification" content="%s" />' . "\n",
		esc_attr( QUEDAMOS_GOOGLE_SITE_VERIFICATION )
	);
}
add_action( 'wp_head', 'quedamos_google_site_verification_meta' );

/**
 * Register and load the GA4 gtag.js tag.
 *
 * Goes through wp_enqueue_script() + wp_add_inline_script() rather than a raw
 * <script> block in wp_head, so the config snippet is guaranteed to run after
 * the library has loaded and the whole thing is dequeueable by a consent plugin
 * later without editing the theme.
 *
 * @return void
 */
function quedamos_enqueue_analytics() {
	if ( ! quedamos_is_live_site() ) {
		return;
	}

	wp_enqueue_script(
		'google-gtag',
		'https://www.googletagmanager.com/gtag/js?id=' . rawurlencode( QUEDAMOS_GA_MEASUREMENT_ID ),
		array(),
		null,
		false
	);

	wp_add_inline_script(
		'google-gtag',
		sprintf(
			'window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag(\'js\', new Date());
gtag(\'config\', %s);',
			wp_json_encode( QUEDAMOS_GA_MEASUREMENT_ID )
		),
		'after'
	);
}
add_action( 'wp_enqueue_scripts', 'quedamos_enqueue_analytics' );
