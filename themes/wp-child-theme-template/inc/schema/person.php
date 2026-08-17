<?php
/**
 * The author Person entity.
 *
 * Repairs and completes the JSON-LD Rank Math emits, so that every page which
 * mentions Sara points at one resolvable, credentialed Person rather than a bare
 * name. Filters the plugin's graph instead of printing a second <script> block:
 * two Article or Person nodes describing the same thing on one page is worse
 * than one thin node, because nothing tells a consumer which to believe.
 *
 * What Rank Math gets wrong on its own, all verified against live 2026-08-17:
 *
 * - The author Person is keyed to /author/admin/, a bare list of posts, and
 *   carries no jobTitle, no description and no link to the page that actually
 *   credentials her.
 * - Its image is the Gravatar `d=mm` placeholder silhouette — the theme ships
 *   her real photo, but the schema never saw it.
 * - The About page, the one page that describes her, has no Person node at all.
 * - The site entity is typed ["Organization","Person"] and named "Quedamos
 *   Languages", which tells a consumer the school is a human being.
 *
 * The article schema itself is left alone: Rank Math already emits a complete
 * BlogPosting per post (headline, dates, articleSection, keywords, image,
 * publisher, inLanguage, mainEntityOfPage) and it needs no help.
 *
 * @package Quedamos
 */

defined( 'ABSPATH' ) || exit;

/**
 * The priority the graph is filtered at.
 *
 * Above Rank Math's own last contributor — the schema module connects its
 * entities at 99 — so this runs on the finished graph and is authoritative.
 */
const QUEDAMOS_PERSON_SCHEMA_PRIORITY = 100;

/**
 * Sara's bio, as prose a consumer can quote.
 *
 * Drawn only from what the About page already states — certified ELE tutor, from
 * Murcia, teaching in Edinburgh, background in language and literature, seven
 * years across Scotland, Ireland and England. Nothing here is asserted that the
 * visible page does not also say: schema that outruns the page is a liability,
 * not a signal.
 *
 * @return string The description.
 */
function quedamos_author_description() {
	return sprintf(
		/* translators: %s: the author's name. */
		__( '%s is a certified Spanish as a Foreign Language (ELE) tutor from Murcia, Spain, teaching Spanish in Edinburgh. She has a background in language and literature, and has lived in Scotland, Ireland and England for over seven years.', 'quedamos' ),
		QUEDAMOS_AUTHOR_NAME
	);
}

/**
 * The subjects the author is qualified to write about.
 *
 * knowsAbout is the property that lets a consumer decide an author is a credible
 * source on a topic rather than merely the person whose name is on the page.
 * Kept to what her qualification and the blog's own categories support.
 *
 * @return string[] The topics.
 */
function quedamos_author_knows_about() {
	return array(
		__( 'Spanish language', 'quedamos' ),
		__( 'Spanish as a Foreign Language (ELE)', 'quedamos' ),
		__( 'Spanish grammar', 'quedamos' ),
		__( 'Spanish literature', 'quedamos' ),
		__( 'Spanish culture', 'quedamos' ),
		__( 'Language teaching', 'quedamos' ),
	);
}

/**
 * The author's sameAs URLs.
 *
 * sameAs is how an entity is resolved across the web: it is the difference
 * between "a person called Sara Carrillo" and "this specific person". The
 * school's Instagram is the only public profile that exists, and with a single
 * tutor it is hers.
 *
 * @return string[] Absolute profile URLs.
 */
function quedamos_author_same_as() {
	$urls = array();

	foreach ( quedamos_social_profiles() as $profile ) {
		$urls[] = $profile['url'];
	}

	return $urls;
}

/**
 * The canonical Person node.
 *
 * @param string $publisher_id The @id of the site's Organization node, so the
 *                             person can be tied to the school. '' to omit.
 * @return array<string, mixed> The Person entity.
 */
function quedamos_person_entity( $publisher_id = '' ) {
	$page_url = quedamos_author_page_url();
	$photo    = quedamos_author_photo_url();

	$person = array(
		'@type'         => 'Person',
		'@id'           => quedamos_author_entity_id(),
		'name'          => QUEDAMOS_AUTHOR_NAME,
		'givenName'     => QUEDAMOS_AUTHOR_GIVEN_NAME,
		'familyName'    => QUEDAMOS_AUTHOR_FAMILY_NAME,
		'jobTitle'      => QUEDAMOS_AUTHOR_ROLE,
		'description'   => quedamos_author_description(),
		'knowsAbout'    => quedamos_author_knows_about(),
		'knowsLanguage' => array( 'Spanish', 'English' ),
	);

	if ( $page_url ) {
		$person['url']              = $page_url;
		$person['mainEntityOfPage'] = array( '@id' => $page_url . '#webpage' );
	}

	if ( $photo ) {
		$person['image'] = array(
			'@type'   => 'ImageObject',
			'@id'     => quedamos_author_entity_id() . '-photo',
			'url'     => $photo,
			'caption' => QUEDAMOS_AUTHOR_NAME,
		);
	}

	$same_as = quedamos_author_same_as();

	if ( $same_as ) {
		$person['sameAs'] = $same_as;
	}

	if ( $publisher_id ) {
		$person['worksFor'] = array( '@id' => $publisher_id );
	}

	return $person;
}

/**
 * Point every author reference in the graph at the canonical Person.
 *
 * Rank Math writes the author as an inline `['@id' => …, 'name' => …]` pair on
 * the Article node, with the name read from the database `display_name`. Both
 * halves are rewritten: the @id so the reference resolves to the profile rather
 * than the author archive, the name so live's "Sara Carrillo Carrillo" cannot
 * contradict the byline the reader is looking at.
 *
 * @param array  $data      The JSON-LD graph, keyed by node name.
 * @param string $entity_id The canonical Person @id.
 * @return array The graph, with author references rewritten.
 */
function quedamos_repoint_schema_authors( $data, $entity_id ) {
	foreach ( $data as $key => $node ) {
		if ( ! is_array( $node ) || ! isset( $node['author'] ) || ! is_array( $node['author'] ) ) {
			continue;
		}

		$data[ $key ]['author'] = array(
			'@id'  => $entity_id,
			'name' => QUEDAMOS_AUTHOR_NAME,
		);
	}

	return $data;
}

/**
 * Separate the school from the person in the site entity.
 *
 * Rank Math's "represent as Person" setting types the site node
 * ["Organization","Person"] and names it after the school, which asserts that a
 * language school is a human. Retyping it Organization and naming Sara as its
 * founder says the true thing instead, and gives the Person a second inbound
 * reference from an entity that appears on every page.
 *
 * @param array  $data      The JSON-LD graph.
 * @param string $entity_id The canonical Person @id.
 * @return array The graph, with the site entity corrected.
 */
function quedamos_separate_schema_organization( $data, $entity_id ) {
	if ( empty( $data['publisher'] ) || ! is_array( $data['publisher'] ) ) {
		return $data;
	}

	$types = (array) ( $data['publisher']['@type'] ?? array() );

	if ( ! in_array( 'Organization', $types, true ) ) {
		return $data;
	}

	$data['publisher']['@type']   = 'Organization';
	$data['publisher']['founder'] = array( '@id' => $entity_id );

	return $data;
}

/**
 * Inject and repair the author entity in Rank Math's JSON-LD graph.
 *
 * Runs on every page so the Organization/Person split is fixed sitewide, but
 * only adds the Person where it belongs: on the profile page, and on anything
 * carrying an author node. A Person entity on the contact page would be noise.
 *
 * @param array $data The JSON-LD graph, keyed by node name.
 * @return array The filtered graph.
 */
function quedamos_filter_person_schema( $data ) {
	if ( ! is_array( $data ) ) {
		return $data;
	}

	$entity_id    = quedamos_author_entity_id();
	$publisher_id = isset( $data['publisher']['@id'] ) ? (string) $data['publisher']['@id'] : '';

	$data = quedamos_separate_schema_organization( $data, $entity_id );

	$is_profile_page = is_page( QUEDAMOS_AUTHOR_PAGE_SLUG );
	$has_author      = ! empty( $data['ProfilePage'] );

	if ( ! $is_profile_page && ! $has_author ) {
		return $data;
	}

	$person = quedamos_person_entity( $publisher_id );

	// Replace Rank Math's author node outright rather than merging into it: its
	// @id is the author archive and its image is the Gravatar placeholder, so
	// every field it contributes is one this entity is here to correct. Reusing
	// its own key keeps a single Person in the graph either way — on a post it
	// supersedes the thin node, on the profile page there was none to begin with.
	$data['ProfilePage'] = $person;

	$data = quedamos_repoint_schema_authors( $data, $entity_id );

	// On the profile page, say in schema what the page says in prose: this page
	// is about this person. Without it the page is merely a page that happens to
	// carry a Person node.
	if ( $is_profile_page && ! empty( $data['WebPage'] ) && is_array( $data['WebPage'] ) ) {
		$data['WebPage']['mainEntity'] = array( '@id' => $entity_id );
		$data['WebPage']['about']      = array( '@id' => $entity_id );
	}

	return $data;
}
add_filter( 'rank_math/json_ld', 'quedamos_filter_person_schema', QUEDAMOS_PERSON_SCHEMA_PRIORITY );
