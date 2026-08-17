<?php
/**
 * Who the site's author is.
 *
 * One record of Sara's identity, consumed by two modules that must never
 * disagree about it: inc/blog/ renders it as the visible byline, inc/schema/
 * emits it as the Person entity. It lives in helpers rather than in either of
 * them because a module must not reach into a sibling.
 *
 * Why a code constant rather than the WordPress user record: the name is the
 * entity key. Search engines and AI assistants resolve an author by matching the
 * name in the visible byline against the name in the schema against the name in
 * the page title, and a value that lives in the database drifts independently of
 * the code that has to agree with it. Live's `display_name` reads "Sara Carrillo
 * Carrillo" today; the SEO title on the About page reads "Sara Carillo" with one
 * r. Three spellings of one person is precisely the failure this file exists to
 * end — see the citability audit noted in DEPLOY-LIST.md.
 *
 * @package Quedamos
 */

defined( 'ABSPATH' ) || exit;

/**
 * The name, in its parts.
 *
 * Split because schema wants givenName and familyName separately, and a full
 * name written out a second time alongside them is a second place for the
 * spelling to drift — which is the whole defect this file closes.
 */
const QUEDAMOS_AUTHOR_GIVEN_NAME  = 'Sara';
const QUEDAMOS_AUTHOR_FAMILY_NAME = 'Carrillo';

/**
 * The canonical name, used for every byline and every schema node.
 *
 * Composed from the parts above rather than typed out, so the full name can
 * never disagree with them. Chosen by Sigurd on 2026-08-17 over the two-surname
 * "Sara Carrillo Carrillo" that live's user record carries. Nothing reads
 * `display_name` any more, so this is the only place the name is decided.
 */
const QUEDAMOS_AUTHOR_NAME = QUEDAMOS_AUTHOR_GIVEN_NAME . ' ' . QUEDAMOS_AUTHOR_FAMILY_NAME;

/**
 * The role line shown beside the author's name, and her schema jobTitle.
 *
 * Hardcoded by decision (2026-08-17). The blog has a single author and the
 * WordPress user profile carries no job-title field, so there is nowhere for
 * this to be edited from. The moment a second author publishes, this has to
 * become per-user data — every author would otherwise be labelled a Spanish
 * Language Educator.
 */
const QUEDAMOS_AUTHOR_ROLE = 'Spanish Language Educator';

/**
 * The author photo that ships with the theme.
 *
 * Replaces Gravatar, which needed a photo attached to the author's email address
 * at gravatar.com — a setup step outside both the repo and the database, and one
 * nobody would think to repeat. This file is committed, so the photo is on live
 * the moment the code is.
 *
 * A single constant rather than a lookup because Sara is the blog's only author
 * (confirmed 2026-08-17): every byline on the site is hers, so there is nothing
 * to key on. It previously was a map keyed by lowercased display_name, which
 * made the photo depend on a database string matching the code character for
 * character — live read "Sara Carrillo Carrillo" against a key of "sara
 * carrillo", so every byline silently fell back to the site icon. The moment a
 * second author publishes, this has to become per-user data again.
 *
 * Path is relative to the theme root, alongside svgs/.
 */
const QUEDAMOS_AUTHOR_PHOTO = 'images/sara-carrillo.webp';

/**
 * The slug of the page that serves as the author's public profile.
 *
 * The About page, rather than a purpose-built author page: it has carried the
 * bio since 2024, it is indexed, and a second profile page would split the
 * entity in two and compete with it.
 *
 * **Renaming that page is a three-part change.** The slug lives in the database,
 * so it can be changed from wp-admin by somebody who will never see this file:
 * update this constant, add the old path to `quedamos_redirect_map()`, and check
 * both environments agree. It was renamed from 'about-spanish-tutor-edinburgh'
 * on 2026-08-17 and none of that happened — the old URL 404'd, and because a
 * missing page makes quedamos_author_page_url() return '', the byline link
 * vanished and the Person entity lost its home in the same move.
 *
 * Verified identical on local and live 2026-08-17.
 */
const QUEDAMOS_AUTHOR_PAGE_SLUG = 'about-spanish-tutor-teaching-edinburgh';

/**
 * The permalink of the author's profile page.
 *
 * Resolved from the slug rather than hardcoded so the URL is correct on both
 * localhost and live — see writing-php §7.
 *
 * @return string The permalink, or '' when the page cannot be found.
 */
function quedamos_author_page_url() {
	$page = get_page_by_path( QUEDAMOS_AUTHOR_PAGE_SLUG );

	if ( ! $page ) {
		return '';
	}

	return (string) get_permalink( $page );
}

/**
 * The schema @id that identifies Sara across the whole site.
 *
 * A fragment on the profile page, not the WordPress author archive: the archive
 * is a bare list of posts with nothing on it that credentials anybody, whereas
 * the About page states her qualification and experience in prose a reader — or
 * a language model — can actually cite. Every author reference on every post
 * points here, so the site describes one person rather than one per template.
 *
 * Falls back to a fragment on the home URL so the @id is always a stable
 * absolute URL, even if the profile page is missing.
 *
 * @return string The absolute @id.
 */
function quedamos_author_entity_id() {
	$url = quedamos_author_page_url();

	return ( $url ? $url : home_url( '/' ) ) . '#sara';
}

/**
 * The absolute URL of the shipped author photo.
 *
 * Empty when the committed file is missing, so a caller can fall through rather
 * than publish a URL that 404s.
 *
 * @return string The photo URL, or '' when the file is not present.
 */
function quedamos_author_photo_url() {
	if ( ! file_exists( get_stylesheet_directory() . '/' . QUEDAMOS_AUTHOR_PHOTO ) ) {
		return '';
	}

	return get_stylesheet_directory_uri() . '/' . QUEDAMOS_AUTHOR_PHOTO;
}

/**
 * The school's social profiles, as data.
 *
 * Instagram is the only one the site has — the footer links the same profile.
 * Kept as an array so a second network is one entry plus an SVG in svgs/, not a
 * markup rewrite. The blog module renders these as icon links; the schema module
 * emits them as the author's sameAs, because the school's public presence and
 * the sole tutor's are the same account.
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
