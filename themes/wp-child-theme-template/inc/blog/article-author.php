<?php
/**
 * The article author bar — who wrote the post, and where to follow the school.
 *
 * Rendered at the head of the article card by [quedamos_article_author]. It is
 * separate from the header byline in article-meta.php on purpose: that row is
 * the post's metadata (category, date, read-time) sitting on the hero scrim,
 * this one is the person, sitting on the white card.
 *
 * @package Quedamos
 */

defined( 'ABSPATH' ) || exit;

/**
 * The role line shown beside the author's name.
 *
 * Hardcoded by decision (2026-08-17). The blog has a single author and the
 * WordPress user profile carries no job-title field, so there is nowhere for
 * this to be edited from. The moment a second author publishes, this has to
 * become per-user data — every author would otherwise be labelled a Spanish
 * Language Educator.
 */
const QUEDAMOS_AUTHOR_ROLE = 'Spanish Language Educator';

/**
 * The school's social profiles, as icon links.
 *
 * Instagram is the only one the site has — the footer links the same profile.
 * Kept as an array so a second network is one entry plus an SVG in svgs/, not a
 * markup rewrite. Promote this to inc/helpers/ if a module other than the blog
 * ever needs it.
 *
 * @return array<string, array<string, string>> Keyed by slug; url, label and icon filename.
 */
function quedamos_social_profiles() {
	return array(
		'instagram' => array(
			'url'   => 'https://www.instagram.com/quedamos_languages/',
			/* translators: the accessible name of the Instagram icon link. */
			'label' => __( 'Quedamos Languages on Instagram', 'quedamos' ),
			'icon'  => 'instagram.svg',
		),
	);
}

/**
 * The social icon links rendered on the author bar.
 *
 * These open the school's profile — they are not share buttons. Instagram
 * accepts no shared URL from the web (there is no equivalent of Facebook's
 * sharer endpoint), so a "share this post to Instagram" link cannot be built;
 * the icon says "follow us" instead, which is a promise the link can keep.
 *
 * @return string The rendered links, or '' when no profile is configured.
 */
function quedamos_social_links() {
	$links = '';

	foreach ( quedamos_social_profiles() as $slug => $profile ) {
		$icon = quedamos_inline_svg( 'svgs/' . $profile['icon'] );

		if ( '' === $icon ) {
			continue;
		}

		$links .= sprintf(
			'<a class="article-author__action" href="%s" target="_blank" rel="noopener noreferrer" aria-label="%s" data-network="%s">%s</a>',
			esc_url( $profile['url'] ),
			esc_attr( $profile['label'] ),
			esc_attr( $slug ),
			$icon
		);
	}

	return $links;
}

/**
 * The copy-link button.
 *
 * The URL is carried on the button rather than read from window.location by the
 * script, so the value is WordPress's own canonical permalink — the same string
 * whether the visitor arrived with tracking parameters on the URL or through a
 * redirect.
 *
 * @param string $url The permalink to copy.
 * @return string The rendered button, or '' with no URL to copy.
 */
function quedamos_copy_link_button( $url ) {
	if ( ! $url ) {
		return '';
	}

	$icon = quedamos_inline_svg( 'svgs/copy.svg' );

	if ( '' === $icon ) {
		return '';
	}

	return sprintf(
		'<button type="button" class="article-author__action article-author__copy" data-copy-url="%s" aria-label="%s">%s<span class="article-author__copy-feedback" aria-live="polite"></span></button>',
		esc_url( $url ),
		esc_attr__( 'Copy a link to this article', 'quedamos' ),
		$icon
	);
}

/**
 * The row of actions at the end of the author bar.
 *
 * A span, not a div: this is nested inside shortcode output, and wpautop treats
 * a block-level tag as a paragraph boundary — it injected a stray </p> ahead of
 * the row when this was a div. Laid out with flex in the SCSS.
 *
 * @param string $url The permalink, for the copy button.
 * @return string The rendered row, or '' when it would be empty.
 */
function quedamos_article_author_actions( $url ) {
	$actions = quedamos_social_links() . quedamos_copy_link_button( $url );

	if ( '' === $actions ) {
		return '';
	}

	return '<span class="article-author__actions">' . $actions . '</span>';
}

/**
 * [quedamos_article_author] — the avatar, "Written by", role and social row.
 *
 * The avatar comes from get_avatar(), so it follows the author's Gravatar and
 * needs no field of its own. Returns nothing at all when the author cannot be
 * resolved, rather than an empty bar with a placeholder silhouette in it.
 *
 * Built as a single-line string: wpautop() turns newlines in shortcode output
 * into stray <br> tags.
 *
 * @return string The rendered author bar HTML.
 */
function quedamos_article_author_shortcode() {
	$post_id     = get_the_ID();
	$author_name = quedamos_author_name( $post_id );

	if ( ! $author_name ) {
		return '';
	}

	$author_id = (int) get_post_field( 'post_author', $post_id );

	// 96px for a 48px slot, so the image is sharp on a 2x display.
	$avatar = get_avatar( $author_id, 96, '', $author_name, array( 'class' => 'article-author__avatar' ) );

	$written_by = sprintf(
		/* translators: %s: the post author's name, already marked up. */
		esc_html__( 'Written by %s', 'quedamos' ),
		'<strong class="article-author__name">' . esc_html( $author_name ) . '</strong>'
	);

	$html = sprintf(
		'<div class="article-author">%s<span class="article-author__identity"><span class="article-author__written">%s</span><span class="article-author__role">%s</span></span>%s</div>',
		$avatar,
		$written_by,
		esc_html( QUEDAMOS_AUTHOR_ROLE ),
		quedamos_article_author_actions( get_permalink( $post_id ) )
	);

	return preg_replace( '/\s*\R\s*/', '', $html );
}
add_shortcode( 'quedamos_article_author', 'quedamos_article_author_shortcode' );
