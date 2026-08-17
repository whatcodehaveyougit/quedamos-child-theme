<?php
/**
 * The quedamos/mobile-menu block — registration and its data lookups.
 *
 * Registration reads mobile-menu/block.json, so the metadata (name, attributes,
 * render file) has one home. The lookups below turn the block's `ref` attribute
 * into a plain list of links for mobile-menu/render.php to echo; keeping them
 * here leaves that file reading as markup, per writing-php §8.
 *
 * @package Quedamos
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the mobile menu block from its block.json.
 *
 * block.json's `render` property points at render.php, so there is no
 * render_callback argument here and no editor script — the block is
 * server-rendered only, which leaves the Parcel bundle untouched.
 *
 * @return void
 */
function quedamos_register_mobile_menu_block() {
	register_block_type( __DIR__ . '/mobile-menu' );
}
add_action( 'init', 'quedamos_register_mobile_menu_block' );

/**
 * Resolve the wp_navigation post the menu should render.
 *
 * The `ref` attribute mirrors core/navigation's own, so the desktop nav and this
 * block can share one menu. It is a database ID, though, and live's differs from
 * local's — so a ref that is absent or points at a post that has gone falls back
 * to the site's most recent menu rather than rendering nothing. That way a
 * forgotten template-part edit after a deploy costs the right menu, not the
 * whole mobile navigation.
 *
 * @param array $attributes The block's attributes.
 * @return int The wp_navigation post ID, or 0 when the site has no menu at all.
 */
function quedamos_mobile_menu_ref( $attributes ) {
	$ref = isset( $attributes['ref'] ) ? (int) $attributes['ref'] : 0;

	if ( $ref > 0 ) {
		$menu = get_post( $ref );

		if ( $menu instanceof WP_Post && 'wp_navigation' === $menu->post_type && 'publish' === $menu->post_status ) {
			return $ref;
		}
	}

	$menus = get_posts(
		array(
			'post_type'              => 'wp_navigation',
			'post_status'            => 'publish',
			'posts_per_page'         => 1,
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	return $menus ? (int) $menus[0]->ID : 0;
}

/**
 * The menu's links, as a flat list.
 *
 * Reads the wp_navigation post's own block markup with parse_blocks() and keeps
 * the core/navigation-link entries. core/navigation-submenu is deliberately
 * ignored: menu ref 5 is flat and submenus are confirmed as not wanted, so a
 * parent with children would contribute its own link and nothing else.
 *
 * Links with no label or no url are dropped — an unlabelled row is a dead row.
 *
 * @param int $ref The wp_navigation post ID.
 * @return array List of arrays with 'label', 'url' and 'id' keys.
 */
function quedamos_mobile_menu_items( $ref ) {
	if ( ! $ref ) {
		return array();
	}

	$menu = get_post( $ref );

	if ( ! $menu instanceof WP_Post ) {
		return array();
	}

	$items = array();

	foreach ( parse_blocks( $menu->post_content ) as $block ) {
		if ( 'core/navigation-link' !== ( $block['blockName'] ?? '' ) ) {
			continue;
		}

		$label = $block['attrs']['label'] ?? '';
		$url   = $block['attrs']['url'] ?? '';

		if ( '' === $label || '' === $url ) {
			continue;
		}

		$items[] = array(
			'label' => $label,
			'url'   => $url,
			'id'    => isset( $block['attrs']['id'] ) ? (int) $block['attrs']['id'] : 0,
		);
	}

	return $items;
}

/**
 * The post ID the current request is showing, for marking the current menu row.
 *
 * Matching on ID rather than on URL strings avoids the usual trailing-slash and
 * scheme mismatches. The blog index is the one case the queried object cannot
 * answer: on a posts page that is not the front page there is no queried object,
 * so page_for_posts is read directly.
 *
 * @return int The post ID of the page being viewed, or 0 when there isn't one.
 */
function quedamos_mobile_menu_current_id() {
	if ( is_home() && ! is_front_page() ) {
		return (int) get_option( 'page_for_posts' );
	}

	return (int) get_queried_object_id();
}
