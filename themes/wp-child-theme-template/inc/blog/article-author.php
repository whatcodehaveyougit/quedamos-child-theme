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
 * Author photos that ship with the theme, keyed by display name.
 *
 * Replaces Gravatar, which needed a photo attached to the author's email address
 * at gravatar.com — a setup step outside both the repo and the database, and one
 * nobody would think to repeat. These files are committed, so the photo is on
 * live the moment the code is.
 *
 * Keyed by the lowercased display_name rather than a user ID or login, because
 * the display name is the very string rendered beside the photo ("Written by
 * Sara Carrillo") — the two therefore cannot drift apart. IDs and logins differ
 * between installs (the author is `admin`, ID 1, locally); the display name is
 * set on both by DEPLOY-LIST.md step 2.
 *
 * Paths are relative to the theme root, alongside svgs/.
 *
 * @return array<string, string> Lowercased display name => theme-relative image path.
 */
function quedamos_author_photos() {
	return array(
		'sara carrillo' => 'images/sara-carrillo.jpg',
	);
}

/**
 * The avatar image for the author bar.
 *
 * Three tiers, in order: the photo this theme ships for a known author, then the
 * site icon — square already, and the school's own mark, so an unknown author
 * gets branding rather than a stranger's face — then nothing at all. Never the
 * Gravatar silhouette, which reads as a broken image rather than a neutral one.
 *
 * alt is empty in both cases: the author's name sits immediately beside the
 * image, so a text alternative here only makes a screen reader say it twice.
 *
 * @param string $author_name The post author's display name.
 * @return string The rendered <img>, or '' when there is nothing to show.
 */
function quedamos_author_avatar( $author_name ) {
	$photos = quedamos_author_photos();
	$key    = strtolower( trim( $author_name ) );
	$photo  = isset( $photos[ $key ] ) ? $photos[ $key ] : '';

	// The map naming a file is not proof the file is there — a missing photo
	// falls through to the site icon rather than rendering a broken image.
	if ( $photo && file_exists( get_stylesheet_directory() . '/' . $photo ) ) {
		$src = get_stylesheet_directory_uri() . '/' . $photo;
	} else {
		// 96px for a 48px slot, so the image is sharp on a 2x display.
		$src = get_site_icon_url( 96 );
	}

	if ( ! $src ) {
		return '';
	}

	return sprintf(
		'<img class="article-author__avatar" src="%s" alt="" width="96" height="96" decoding="async" />',
		esc_url( $src )
	);
}

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
 * The avatar comes from quedamos_author_avatar(), which ships the photo with the
 * theme. Returns nothing at all when the author cannot be resolved, rather than
 * an empty bar with a placeholder silhouette in it.
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

	$avatar = quedamos_author_avatar( $author_name );

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
