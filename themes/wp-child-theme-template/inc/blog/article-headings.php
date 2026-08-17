<?php
/**
 * Article heading scan and anchor-id injection.
 *
 * Single source of truth for the table of contents. ONE helper —
 * quedamos_extract_headings() — parses a post's content and returns each
 * h2/h3/h4 as { level, text, id } with a deterministic slug. Two consumers call
 * it against the *same* content, so the ids provably match:
 *
 *   1. quedamos_inject_heading_ids() — a `the_content` filter that stamps those
 *      ids onto the rendered headings, so the #anchors actually exist.
 *   2. quedamos_article_toc_shortcode(), which builds the link list.
 *
 * Because both derive ids from this one helper, every href="#id" in the TOC
 * resolves to a real heading.
 *
 * @package Quedamos
 */

defined( 'ABSPATH' ) || exit;

/**
 * Parse post-content HTML into an ordered list of its h2/h3/h4 headings.
 *
 * Each item is an array of:
 *   'level' (int)    — 2, 3 or 4, used for TOC indentation.
 *   'text'  (string) — the heading's plain text, unescaped.
 *   'id'    (string) — a slug derived from the text, deduplicated within the
 *                      document (a repeated heading gets -2, -3, and so on).
 *
 * @param string $html Post content HTML.
 * @return array<int, array{level: int, text: string, id: string}>
 */
function quedamos_extract_headings( $html ) {
	if ( '' === trim( (string) $html ) ) {
		return array();
	}

	$dom = quedamos_load_content_dom( $html );

	if ( ! $dom ) {
		return array();
	}

	$headings = array();
	$used_ids = array();

	foreach ( $dom->getElementsByTagName( '*' ) as $node ) {
		if ( ! preg_match( '/^h([2-4])$/', $node->nodeName, $match ) ) {
			continue;
		}

		$text = trim( $node->textContent );

		if ( '' === $text ) {
			continue;
		}

		$headings[] = array(
			'level' => (int) $match[1],
			'text'  => $text,
			'id'    => quedamos_unique_heading_id( $text, $used_ids ),
		);
	}

	return $headings;
}

/**
 * `the_content` filter — stamp matching ids onto the rendered h2/h3/h4.
 *
 * Runs only on a single post, the one surface with a TOC, and leaves every
 * other context untouched.
 *
 * Note the guard deliberately omits in_the_loop(): the core/post-content block
 * calls setup_postdata() rather than the_post(), so that flag is not reliably
 * set inside a block template and using it would stop the ids being stamped at
 * all.
 *
 * @param string $content Rendered post content HTML.
 * @return string Content with ids added to its headings.
 */
function quedamos_inject_heading_ids( $content ) {
	if ( is_admin() || ! is_singular( 'post' ) || ! is_main_query() ) {
		return $content;
	}

	if ( '' === trim( (string) $content ) ) {
		return $content;
	}

	$dom = quedamos_load_content_dom( $content );

	if ( ! $dom ) {
		return $content;
	}

	$used_ids = array();
	$changed  = false;

	foreach ( $dom->getElementsByTagName( '*' ) as $node ) {
		if ( ! preg_match( '/^h[2-4]$/', $node->nodeName ) ) {
			continue;
		}

		$text = trim( $node->textContent );

		if ( '' === $text ) {
			continue;
		}

		// An author-set id wins, but it still takes the dedupe slot so the
		// extractor (which only sees text) and this filter agree on later ids.
		if ( $node->hasAttribute( 'id' ) && '' !== $node->getAttribute( 'id' ) ) {
			$used_ids[] = $node->getAttribute( 'id' );
			continue;
		}

		$node->setAttribute( 'id', quedamos_unique_heading_id( $text, $used_ids ) );
		$changed = true;
	}

	if ( ! $changed ) {
		return $content;
	}

	return quedamos_save_content_dom( $dom );
}
add_filter( 'the_content', 'quedamos_inject_heading_ids', 20 );

/**
 * Slugify heading text and guarantee uniqueness within one document.
 *
 * @param string             $text     Heading text.
 * @param array<int, string> $used_ids Ids already taken; the chosen id is appended.
 * @return string The unique slug id.
 */
function quedamos_unique_heading_id( $text, array &$used_ids ) {
	$base = sanitize_title( $text );

	if ( '' === $base ) {
		$base = 'section';
	}

	$id    = $base;
	$index = 2;

	while ( in_array( $id, $used_ids, true ) ) {
		$id = $base . '-' . $index;
		++$index;
	}

	$used_ids[] = $id;

	return $id;
}

/**
 * Load a content fragment into a DOMDocument with UTF-8 preserved.
 *
 * The meta charset matters here more than most themes: these posts mix English
 * and Spanish, and without it DOMDocument mangles every accented character.
 *
 * @param string $html Content HTML fragment.
 * @return DOMDocument|null The loaded document, or null if it could not parse.
 */
function quedamos_load_content_dom( $html ) {
	if ( ! class_exists( 'DOMDocument' ) ) {
		return null;
	}

	$dom      = new DOMDocument();
	$previous = libxml_use_internal_errors( true );

	$loaded = $dom->loadHTML(
		'<?xml encoding="utf-8"?><html><head><meta charset="utf-8"></head><body>' . $html . '</body></html>',
		LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
	);

	libxml_clear_errors();
	libxml_use_internal_errors( $previous );

	return $loaded ? $dom : null;
}

/**
 * Serialise the <body> inner HTML back out, undoing the wrapper added on load.
 *
 * @param DOMDocument $dom The mutated document.
 * @return string The content HTML fragment.
 */
function quedamos_save_content_dom( DOMDocument $dom ) {
	$body = $dom->getElementsByTagName( 'body' )->item( 0 );

	if ( ! $body ) {
		return $dom->saveHTML();
	}

	$html = '';

	foreach ( $body->childNodes as $child ) {
		$html .= $dom->saveHTML( $child );
	}

	return $html;
}
