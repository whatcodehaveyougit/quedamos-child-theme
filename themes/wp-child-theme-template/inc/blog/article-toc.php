<?php
/**
 * The article table of contents.
 *
 * [quedamos_article_toc] — a navigator listing the current post's h2/h3/h4
 * headings, built from quedamos_extract_headings() so every href="#id" matches
 * an id stamped onto the rendered content by the same helper.
 *
 * The markup is a <details> so one element serves both layouts: a sticky,
 * always-open side column on desktop, and a tappable accordion on mobile. That
 * is pure CSS — see components/article-toc.scss. Scroll-to is CSS as well
 * (scroll-behavior plus scroll-margin-top on the headings); the only JavaScript
 * is the active-link highlight in assets/scripts/js/article-toc.js.
 *
 * Renders nothing when a post has no headings, so short posts get no empty box.
 *
 * @package Quedamos
 */

defined( 'ABSPATH' ) || exit;

/**
 * [quedamos_article_toc] — render the table of contents for the current post.
 *
 * @param array $atts Shortcode attributes: title.
 * @return string The rendered TOC HTML, or '' when the post has no headings.
 */
function quedamos_article_toc_shortcode( $atts ) {
	$atts = shortcode_atts(
		array( 'title' => __( 'In this article', 'quedamos' ) ),
		$atts,
		'quedamos_article_toc'
	);

	$headings = quedamos_extract_headings( get_the_content() );

	if ( empty( $headings ) ) {
		return '';
	}

	$links = '';

	foreach ( $headings as $heading ) {
		$links .= sprintf(
			'<a class="article-toc__link article-toc__link--h%1$d" href="#%2$s" data-toc-link="%2$s">%3$s</a>',
			(int) $heading['level'],
			esc_attr( $heading['id'] ),
			esc_html( $heading['text'] )
		);
	}

	$html = sprintf(
		'<details class="article-toc" open>
			<summary class="article-toc__heading">
				<span class="article-toc__heading-text">%1$s</span>%2$s
			</summary>
			<nav class="article-toc__nav" aria-label="%3$s">%4$s</nav>
		</details>',
		esc_html( $atts['title'] ),
		quedamos_inline_svg( 'svgs/chevron-down.svg' ),
		esc_attr( $atts['title'] ),
		$links
	);

	// Shortcode output passes through wpautop(), which turns every newline in
	// here into a stray <br>. Return it as one line.
	return preg_replace( '/\s*\R\s*/', '', $html );
}
add_shortcode( 'quedamos_article_toc', 'quedamos_article_toc_shortcode' );
