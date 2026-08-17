<?php
/**
 * Mobile navigation.
 *
 * Core's navigation block has no hook for adding chrome inside the open mobile
 * overlay, so the rendered markup is filtered and a top bar (logo + close
 * button) is injected ahead of the menu's own <ul>.
 *
 * @package Quedamos
 */

defined( 'ABSPATH' ) || exit;

/**
 * The className the mobile navigation block is tagged with in the editor.
 */
const QUEDAMOS_MOBILE_NAV_CLASS = 'q-navigation-mobile';

/**
 * Inject the mobile menu top bar into the mobile navigation block.
 *
 * @param string $block_content The block's rendered HTML.
 * @param array  $block         The parsed block.
 * @return string The filtered block HTML.
 */
function quedamos_filter_mobile_navigation( $block_content, $block ) {
	if ( 'core/navigation' !== ( $block['blockName'] ?? '' ) ) {
		return $block_content;
	}

	// Guarded rather than accessed directly: most navigation blocks carry no
	// className at all, and the original code raised a notice on every one.
	if ( QUEDAMOS_MOBILE_NAV_CLASS !== ( $block['attrs']['className'] ?? '' ) ) {
		return $block_content;
	}

	$ul_position = strpos( $block_content, '<ul' );

	if ( false === $ul_position ) {
		return $block_content;
	}

	return substr_replace( $block_content, quedamos_mobile_menu_top_bar(), $ul_position, 0 );
}
add_filter( 'render_block', 'quedamos_filter_mobile_navigation', 10, 2 );

/**
 * The mobile menu top bar markup — site logo and close button.
 *
 * The logo comes from get_custom_logo() and the close icon from the theme's own
 * svgs/ folder, so neither carries a hardcoded production URL. The previous
 * version inlined https://quedamoslanguages.com/... for all three images, which
 * is why the local and live copies of this file drifted apart.
 *
 * @return string The top bar HTML.
 */
function quedamos_mobile_menu_top_bar() {
	$logo = get_custom_logo();

	$close_button = sprintf(
		'<button aria-label="%s" class="close-btn" data-wp-on--click="actions.closeMenuOnClick">%s</button>',
		esc_attr__( 'Close menu', 'quedamos' ),
		quedamos_inline_svg( 'svgs/close.svg' )
	);

	return sprintf(
		'<div class="mobile-menu-top-bar"><div class="wp-block-site-logo">%s</div><div>%s</div></div>',
		$logo,
		$close_button
	);
}
