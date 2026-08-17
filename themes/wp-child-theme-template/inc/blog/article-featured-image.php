<?php
/**
 * Drop a leading content image that repeats the featured image.
 *
 * The article header already shows the featured image as its background band,
 * so a post that also opens with that same image in its content shows it twice
 * in a row. Authors have been pasting it in as a normal image block, which
 * predates the header band and is not something to go back and edit out of
 * every post by hand.
 *
 * This only removes the image when it is BOTH the first thing in the content
 * AND provably the same attachment as the featured image. A post whose opening
 * image is a different picture keeps it, which is why this matches on identity
 * rather than simply hiding the first figure.
 *
 * @package Quedamos
 */

defined( 'ABSPATH' ) || exit;

/**
 * `the_content` filter — remove the opening image when it duplicates the
 * featured image.
 *
 * Runs at priority 15, ahead of the heading-id injection at 20, so the two
 * passes don't re-parse each other's output.
 *
 * @param string $content Rendered post content HTML.
 * @return string Content with the duplicate opening image removed.
 */
function quedamos_strip_duplicate_featured_image( $content ) {
	if ( is_admin() || ! is_singular( 'post' ) || ! is_main_query() ) {
		return $content;
	}

	$thumbnail_id = get_post_thumbnail_id();

	if ( ! $thumbnail_id || '' === trim( (string) $content ) ) {
		return $content;
	}

	$dom = quedamos_load_content_dom( $content );

	if ( ! $dom ) {
		return $content;
	}

	$body = $dom->getElementsByTagName( 'body' )->item( 0 );

	if ( ! $body ) {
		return $content;
	}

	$first = null;

	foreach ( $body->childNodes as $node ) {
		// Skip the whitespace text nodes between blocks.
		if ( XML_ELEMENT_NODE !== $node->nodeType ) {
			continue;
		}

		$first = $node;
		break;
	}

	if ( ! $first || ! quedamos_node_shows_attachment( $first, $thumbnail_id ) ) {
		return $content;
	}

	$first->parentNode->removeChild( $first );

	return quedamos_save_content_dom( $dom );
}
add_filter( 'the_content', 'quedamos_strip_duplicate_featured_image', 15 );

/**
 * Whether a content node displays a given attachment.
 *
 * Checks the `wp-image-{id}` class WordPress puts on image blocks first, then
 * falls back to comparing filenames — an image inserted by URL, or migrated
 * from another install, carries no id class. The filename comparison strips any
 * `-300x200` size suffix so a thumbnail-sized copy still matches its original.
 *
 * @param DOMElement $node          A top-level content node.
 * @param int        $attachment_id The attachment to test against.
 * @return bool True when the node shows that attachment.
 */
function quedamos_node_shows_attachment( $node, $attachment_id ) {
	$images = $node->getElementsByTagName( 'img' );

	// Only a node that is *just* an image counts: a paragraph that happens to
	// contain an inline image is content, not a duplicated hero.
	if ( 1 !== $images->length ) {
		return false;
	}

	$img = $images->item( 0 );

	if ( false !== strpos( $img->getAttribute( 'class' ), 'wp-image-' . $attachment_id ) ) {
		return true;
	}

	$source = wp_get_attachment_url( $attachment_id );

	if ( ! $source ) {
		return false;
	}

	return quedamos_image_basename( $img->getAttribute( 'src' ) ) === quedamos_image_basename( $source );
}

/**
 * The size-agnostic filename of an image URL — 'cup-300x200.jpg' -> 'cup.jpg'.
 *
 * @param string $url An image URL.
 * @return string The normalised filename, or '' when the URL is empty.
 */
function quedamos_image_basename( $url ) {
	if ( '' === (string) $url ) {
		return '';
	}

	$file = wp_basename( strtok( $url, '?' ) );

	return preg_replace( '/-\d+x\d+(?=\.[a-z0-9]+$)/i', '', $file );
}
