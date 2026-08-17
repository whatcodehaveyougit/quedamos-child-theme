<?php
/**
 * Title: Latest articles
 * Slug: quedamos/latest-articles
 * Categories: posts, query, featured
 * Keywords: latest, articles, blog, posts, news, recent
 * Viewport width: 1200
 * Description: A three-up grid of the newest blog posts under a centred heading — the band that closes a single post, insertable on any page.
 *
 * Lifted verbatim out of templates/single.html, where this section used to be
 * written inline as .article-related-*. It is a pattern now because the same
 * band is wanted on the home page, and a section that appears in two places
 * cannot have one of them as its home: the markup, the class hooks and the query
 * live here, and both callers reference this file.
 *
 * templates/single.html renders it through `wp:pattern`, which core resolves at
 * render time — so the single post always shows whatever this file currently
 * says. A page inserted through the block inserter gets a *copy* instead, which
 * is the standing trade-off of an unsynced pattern (see patterns/tutor-intro.php)
 * and the reason every node below carries its class hook already: a class left
 * off here can never be added later by editing the theme.
 *
 * Composed from core blocks only. The three cards are .post-card, the same
 * component the blog listing uses — assets/styles/scss/components/post-card.scss
 * owns their look, and assets/styles/scss/components/latest-articles.scss owns
 * only the band around them.
 *
 * The query is pinned to the three newest posts by
 * quedamos_latest_articles_query_vars() in inc/blog/article-meta.php, which keys
 * on the `latest-articles__grid` className below — RENAME BOTH TOGETHER. That
 * filter is also what drops the post being read from the list on a single post,
 * so the band never offers the reader the article they are already on.
 *
 * @package Quedamos
 */

defined( 'ABSPATH' ) || exit;
?>

<!-- wp:group {"tagName":"section","className":"latest-articles","align":"full","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull latest-articles">

	<!-- wp:heading {"level":2,"className":"latest-articles__title"} -->
	<h2 class="wp-block-heading latest-articles__title">Latest Articles</h2>
	<!-- /wp:heading -->

	<!-- The perPage, order and orderBy here are the defaults the block editor
	     needs to preview something sensible; the filter named above is what the
	     front end actually runs on. inherit:false so the band shows the latest
	     posts wherever it is placed, rather than repeating whatever the page's
	     main query returned. -->
	<!-- wp:query {"queryId":0,"query":{"perPage":3,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","inherit":false},"className":"latest-articles__query"} -->
	<div class="wp-block-query latest-articles__query">

		<!-- wp:post-template {"className":"latest-articles__grid","layout":{"type":"grid","columnCount":3}} -->

			<!-- wp:group {"className":"post-card","layout":{"type":"default"}} -->
			<div class="wp-block-group post-card">

				<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/10","className":"post-card__image"} /-->

				<!-- The avatar, author, date and read-time row is prepended to
				     the title block below by quedamos_prepend_post_card_byline()
				     in inc/blog/article-meta.php. It is not a block here because
				     the read-time is derived from the post's word count, which no
				     core block can express, and a shortcode cannot see which post
				     the query loop is on. -->
				<!-- wp:post-title {"isLink":true,"level":3,"className":"post-card__title"} /-->

				<!-- wp:post-excerpt {"showMoreOnNewLine":false,"excerptLength":18,"className":"post-card__excerpt"} /-->

			</div>
			<!-- /wp:group -->

		<!-- /wp:post-template -->

		<!-- wp:query-no-results -->
			<!-- wp:paragraph -->
			<p>No articles yet — check back soon.</p>
			<!-- /wp:paragraph -->
		<!-- /wp:query-no-results -->

	</div>
	<!-- /wp:query -->

</section>
<!-- /wp:group -->
