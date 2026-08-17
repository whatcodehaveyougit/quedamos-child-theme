<?php
/**
 * Title: Course detail with video
 * Slug: quedamos/course-detail-with-video
 * Categories: featured, columns
 * Keywords: course, detail, video, reel, embed, what's included
 * Viewport width: 1200
 * Description: A course write-up beside a video. Editable copy on the left — what the course covers and what is included — and an embed on the right that takes a video URL.
 *
 * Sits under the "Classes at a glance" table on a course page, and is a separate
 * pattern rather than part of it on purpose: the table is a fixed grid of four
 * fixed facts, while this is long-form copy that changes course by course.
 * Welding them together would mean editing the whole thing to change either.
 *
 * The right-hand column is a core/embed block shipping a default video, so the
 * pattern arrives looking like the finished thing rather than like a hole. To
 * change it, select the embed and edit the URL — still no markup to hand-edit.
 *
 * The URL is a YouTube one deliberately. Instagram and Facebook dropped out of
 * WordPress's built-in oEmbed list when Meta started requiring an app access
 * token, so a reel URL pasted in its place renders as a bare link rather than a
 * player. Anything on YouTube or Vimeo just works.
 *
 * Every class below is a styling hook for
 * assets/styles/scss/components/course-detail.scss. The words are placeholders;
 * the structure and the look are not.
 *
 * @package Quedamos
 */

defined( 'ABSPATH' ) || exit;

?>

<!-- wp:group {"className":"course-detail","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group course-detail">
	<!-- wp:columns {"verticalAlignment":"top","className":"course-detail__row","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|20","left":"var:preset|spacing|20"}}}} -->
	<div class="wp-block-columns are-vertically-aligned-top course-detail__row">
		<!-- wp:column {"verticalAlignment":"top","width":"58%","className":"course-detail__copy"} -->
		<div class="wp-block-column is-vertically-aligned-top course-detail__copy" style="flex-basis:58%">
			<!-- wp:heading {"className":"course-detail__title"} -->
			<h2 class="wp-block-heading course-detail__title">Why choose this course?</h2>
			<!-- /wp:heading -->

			<!-- wp:heading {"level":3,"className":"course-detail__subtitle"} -->
			<h3 class="wp-block-heading course-detail__subtitle">What you'll be doing</h3>
			<!-- /wp:heading -->

			<!-- wp:list {"className":"course-detail__list"} -->
			<ul class="wp-block-list course-detail__list">
				<!-- wp:list-item -->
				<li>Talking, from minute one</li>
				<!-- /wp:list-item -->

				<!-- wp:list-item -->
				<li>Where you'll meet, and how often</li>
				<!-- /wp:list-item -->

				<!-- wp:list-item -->
				<li>Practising real conversations you'd actually have in Spain</li>
				<!-- /wp:list-item -->

				<!-- wp:list-item -->
				<li>Building confidence to speak without overthinking</li>
				<!-- /wp:list-item -->
			</ul>
			<!-- /wp:list -->

			<!-- wp:heading {"level":3,"className":"course-detail__subtitle"} -->
			<h3 class="wp-block-heading course-detail__subtitle">What's included</h3>
			<!-- /wp:heading -->

			<!-- wp:list {"className":"course-detail__list"} -->
			<ul class="wp-block-list course-detail__list">
				<!-- wp:list-item -->
				<li>How many classes, and how long each one runs</li>
				<!-- /wp:list-item -->

				<!-- wp:list-item -->
				<li>Conversation-focused lessons</li>
				<!-- /wp:list-item -->

				<!-- wp:list-item -->
				<li>Small groups</li>
				<!-- /wp:list-item -->

				<!-- wp:list-item -->
				<li>All materials included</li>
				<!-- /wp:list-item -->
			</ul>
			<!-- /wp:list -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"top","width":"42%","className":"course-detail__media"} -->
		<div class="wp-block-column is-vertically-aligned-top course-detail__media" style="flex-basis:42%">
			<!-- wp:embed {"url":"https://www.youtube.com/watch?v=9o73srp7ffw","type":"video","providerNameSlug":"youtube","responsive":true,"className":"course-detail__video"} -->
			<figure class="wp-block-embed is-type-video is-provider-youtube wp-block-embed-youtube course-detail__video">
				<div class="wp-block-embed__wrapper">
					https://www.youtube.com/watch?v=9o73srp7ffw
				</div>
			</figure>
			<!-- /wp:embed -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
