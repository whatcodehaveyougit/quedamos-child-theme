<?php
/**
 * Synced block (reusable block) rendering by name.
 *
 * A file-based block template can reference a synced block with
 * <!-- wp:block {"ref":1141} /--> , but that hardcodes a database ID into a file
 * that gets deployed to another environment, where the same ID may point at a
 * different block or at nothing at all. This resolves the block by title
 * instead, so the reference survives the trip from local to live.
 *
 * @package Quedamos
 */

defined( 'ABSPATH' ) || exit;

/**
 * Find a published synced block by its title.
 *
 * The result is cached in a transient because this runs on every render of any
 * template using the shortcode, and the ID only changes if the block is deleted
 * and recreated. quedamos_flush_synced_block_cache() clears it on save.
 *
 * @param string $title The synced block's title, e.g. 'MyMission'.
 * @return int The block's post ID, or 0 when no published block matches.
 */
function quedamos_get_synced_block_id( $title ) {
	$cache_key = 'quedamos_synced_block_' . md5( $title );
	$cached    = get_transient( $cache_key );

	if ( false !== $cached ) {
		return (int) $cached;
	}

	$blocks = get_posts(
		array(
			'post_type'              => 'wp_block',
			'post_status'            => 'publish',
			'title'                  => $title,
			'posts_per_page'         => 1,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	$block_id = $blocks ? (int) $blocks[0]->ID : 0;

	set_transient( $cache_key, $block_id, DAY_IN_SECONDS );

	return $block_id;
}

/**
 * [quedamos_synced_block title="MyMission"] — render a synced block by title.
 *
 * Returns an empty string when the block is missing, so a renamed or deleted
 * block leaves a gap rather than a fatal error or a stray "block not found".
 *
 * @param array $atts Shortcode attributes: title.
 * @return string The rendered block HTML, or '' when it cannot be found.
 */
function quedamos_synced_block_shortcode( $atts ) {
	$atts = shortcode_atts( array( 'title' => '' ), $atts, 'quedamos_synced_block' );

	if ( '' === $atts['title'] ) {
		return '';
	}

	$block_id = quedamos_get_synced_block_id( $atts['title'] );

	if ( ! $block_id ) {
		return '';
	}

	$block = get_post( $block_id );

	if ( ! $block || 'publish' !== $block->post_status ) {
		return '';
	}

	return do_blocks( $block->post_content );
}
add_shortcode( 'quedamos_synced_block', 'quedamos_synced_block_shortcode' );

/**
 * Clear the cached lookup when a synced block is saved or deleted.
 *
 * @param int $post_id The post being saved or deleted.
 * @return void
 */
function quedamos_flush_synced_block_cache( $post_id ) {
	if ( 'wp_block' !== get_post_type( $post_id ) ) {
		return;
	}

	$title = get_the_title( $post_id );

	if ( $title ) {
		delete_transient( 'quedamos_synced_block_' . md5( $title ) );
	}
}
add_action( 'save_post', 'quedamos_flush_synced_block_cache' );
add_action( 'before_delete_post', 'quedamos_flush_synced_block_cache' );
