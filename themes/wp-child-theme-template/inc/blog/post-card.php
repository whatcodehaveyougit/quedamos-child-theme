<?php
/**
 * The listing card's per-post bits that no core block can express on its own.
 *
 * Two render_block filters, both keyed on a className that only the blog
 * listing's card carries, so the "Latest Articles" band's grid is untouched:
 *
 *   - the primary-category chip, which is one term rather than the post's whole
 *     term list,
 *   - the READ MORE button's destination and its arrow.
 *
 * Both are filters rather than shortcodes for the same reason the card byline is
 * (see article-meta.php): a shortcode in a block template is expanded after the
 * query loop has finished, so it only ever sees the post being viewed.
 *
 * @package Quedamos
 */

defined( 'ABSPATH' ) || exit;

/**
 * Replace the card's post-terms block with a single primary-category chip.
 *
 * core/post-terms renders every category a post is in; the comp shows one. The
 * block stays in the template rather than being dropped for a shortcode because
 * it is what carries the query loop's postId context — and because the raw block
 * is a sane fallback if this filter is ever removed.
 *
 * @param string   $block_content The block's rendered HTML.
 * @param array    $block         The parsed block.
 * @param WP_Block $instance      The block instance, carrying query-loop context.
 * @return string The chip HTML, or '' when the post has no category to show.
 */
function quedamos_post_card_category( $block_content, $block, $instance ) {
	if ( 'core/post-terms' !== ( $block['blockName'] ?? '' ) ) {
		return $block_content;
	}

	$class = $block['attrs']['className'] ?? '';

	if ( false === strpos( (string) $class, 'post-card__category' ) ) {
		return $block_content;
	}

	$post_id  = (int) ( $instance->context['postId'] ?? 0 );
	$category = $post_id ? quedamos_get_primary_category( $post_id ) : null;

	if ( ! $category ) {
		return '';
	}

	return sprintf(
		'<a class="post-card__category" href="%1$s">%2$s</a>',
		esc_url( get_category_link( $category->term_id ) ),
		esc_html( $category->name )
	);
}
add_filter( 'render_block', 'quedamos_post_card_category', 10, 3 );

/**
 * Point the card's READ MORE button at the post, and give it its arrow.
 *
 * The button is a real core/button so it inherits the system button entirely
 * from theme.json — background, radius, padding and the hover swap are never
 * restated here. What a block template cannot do is know which post it is
 * rendering, so the href is filled in from the loop; core/button declares no
 * context, but core's post-template calls the_post() before rendering its inner
 * blocks, so the global post is the looped one.
 *
 * The anchor is rewritten with WP_HTML_Tag_Processor rather than a string
 * replace, so it does not depend on core's attribute order.
 *
 * @param string $block_content The block's rendered HTML.
 * @param array  $block         The parsed block.
 * @return string The filtered block HTML.
 */
function quedamos_post_card_read_more( $block_content, $block ) {
	if ( 'core/buttons' !== ( $block['blockName'] ?? '' ) ) {
		return $block_content;
	}

	$class = $block['attrs']['className'] ?? '';

	if ( false === strpos( (string) $class, 'post-card__read-more' ) ) {
		return $block_content;
	}

	$permalink = get_permalink();

	if ( ! $permalink ) {
		return '';
	}

	$processor = new WP_HTML_Tag_Processor( $block_content );

	if ( ! $processor->next_tag( array( 'tag_name' => 'a' ) ) ) {
		return $block_content;
	}

	$processor->set_attribute( 'href', $permalink );

	// "Read more" on its own is the same label on every card in the list, which
	// is useless out of context to anyone tabbing or using a screen reader.
	$processor->set_attribute(
		'aria-label',
		/* translators: %s: post title. */
		sprintf( __( 'Read more: %s', 'quedamos' ), wp_strip_all_tags( get_the_title() ) )
	);

	$arrow = quedamos_inline_svg( 'svgs/arrow-right.svg' );

	return str_replace( '</a>', $arrow . '</a>', $processor->get_updated_html() );
}
add_filter( 'render_block', 'quedamos_post_card_read_more', 10, 2 );
