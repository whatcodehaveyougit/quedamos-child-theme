<?php
/**
 * The article author bar — who wrote the post, and where to follow the school.
 *
 * Rendered at the head of the article card by [quedamos_article_author]. It is
 * separate from the header byline in article-meta.php on purpose: that row is
 * the post's metadata (category, date, read-time) sitting on the hero scrim,
 * this one is the person, sitting on the white card.
 *
 * Who the person *is* — name, role, photo, profile page, social — lives in
 * inc/helpers/author-identity.php, because inc/schema/ has to render the same
 * facts as machine-readable JSON-LD and the two must never disagree. This file
 * owns only the markup.
 *
 * @package Quedamos
 */

defined( 'ABSPATH' ) || exit;

/**
 * The avatar image for a blog byline.
 *
 * Two tiers: the photo this theme ships, then the site icon — square already,
 * and the school's own mark, so a missing file shows branding rather than a
 * broken image. Never the Gravatar silhouette, which reads as broken rather
 * than neutral.
 *
 * alt is empty in both cases: the author's name sits immediately beside the
 * image, so a text alternative here only makes a screen reader say it twice.
 *
 * @param int    $size  Intrinsic size in px, requested and declared on the tag.
 *                      Pass 2x the CSS slot so the image is sharp on a 2x display.
 * @param string $class The img class, named by the component doing the calling.
 * @return string The rendered <img>, or '' when there is nothing to show.
 */
function quedamos_author_avatar( $size, $class ) {
	$size = (int) $size;

	// quedamos_author_photo_url() returns '' when the committed file is missing,
	// so a shipped photo that never made it into the build falls through to the
	// site icon rather than rendering a broken image.
	$src = quedamos_author_photo_url();

	if ( ! $src ) {
		$src = get_site_icon_url( $size );
	}

	if ( ! $src ) {
		return '';
	}

	return sprintf(
		'<img class="%s" src="%s" alt="" width="%d" height="%d" decoding="async" />',
		esc_attr( $class ),
		esc_url( $src ),
		$size,
		$size
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
 * The avatar comes from quedamos_author_avatar(), which ships the photo with the
 * theme. Returns nothing at all when the author cannot be resolved, rather than
 * an empty bar with a placeholder silhouette in it.
 *
 * 96px for a 48px slot, so the image is sharp on a 2x display.
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

	$avatar = quedamos_author_avatar( 96, 'article-author__avatar' );

	$name = '<strong class="article-author__name">' . esc_html( $author_name ) . '</strong>';

	// The byline links to the profile page when it exists. rel="author" is the
	// markup that says this link points at who wrote the page, which is what
	// makes the name attributable rather than decorative — a reader gets her
	// credentials in one click, and the link is the human-readable twin of the
	// Person entity inc/schema/person.php emits alongside it.
	$profile_url = quedamos_author_page_url();

	if ( $profile_url ) {
		$name = sprintf(
			'<a class="article-author__link" href="%s" rel="author">%s</a>',
			esc_url( $profile_url ),
			$name
		);
	}

	$written_by = sprintf(
		/* translators: %s: the post author's name, already marked up. */
		esc_html__( 'Written by %s', 'quedamos' ),
		$name
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
