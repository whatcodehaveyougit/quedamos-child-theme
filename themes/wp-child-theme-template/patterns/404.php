<?php
/**
 * Title: 404 page
 * Slug: quedamos/404
 * Inserter: no
 *
 * The body of templates/404.html.
 *
 * This is a pattern rather than markup in the template because a block template
 * is static `.html` and cannot run PHP, and the photo behind this page has to
 * come from PHP: it lives in the theme at images/, so its URL is
 * get_stylesheet_directory_uri() and hardcoding it would break local
 * development (writing-php §7). It is the same mechanism the parent theme's own
 * 404 uses, and the same reason the author photo is a theme file rather than a
 * media library ID — an uploads URL or an attachment ID is a per-environment
 * value, and this page has to render identically everywhere.
 *
 * The overlay is core/cover's own dim at 50% over the palette's `contrast`
 * black, which is exactly $scrim — the token the design system already names for
 * "the layer behind is dimmed". Don't swap it for a hand-rolled gradient.
 *
 * @package Quedamos
 */

defined( 'ABSPATH' ) || exit;

$quedamos_404_image = get_stylesheet_directory_uri() . '/images/beach-sunset.webp';
?>

<!-- wp:cover {"url":"<?php echo esc_url( $quedamos_404_image ); ?>","dimRatio":50,"overlayColor":"contrast","minHeight":70,"minHeightUnit":"vh","align":"full","className":"error-404-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-cover alignfull error-404-hero" style="min-height:70vh"><span aria-hidden="true" class="wp-block-cover__background has-contrast-background-color has-background-dim"></span><img class="wp-block-cover__image-background" alt="" src="<?php echo esc_url( $quedamos_404_image ); ?>" data-object-fit="cover"/><div class="wp-block-cover__inner-container">

	<!-- wp:paragraph {"className":"error-404-eyebrow"} -->
	<p class="error-404-eyebrow">Error 404</p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"level":1,"className":"error-404-title"} -->
	<h1 class="wp-block-heading error-404-title">¿Dónde está esta página?</h1>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"className":"error-404-lede"} -->
	<p class="error-404-lede"><strong>Where is this page?</strong> We're not sure either. It seems to have wandered off to the beach without telling anyone. Try one of these instead.</p>
	<!-- /wp:paragraph -->

	<!-- wp:buttons {"className":"error-404-actions","layout":{"type":"flex","justifyContent":"center"}} -->
	<div class="wp-block-buttons error-404-actions">

		<!-- wp:button -->
		<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/' ) ); ?>">Volver al inicio</a></div>
		<!-- /wp:button -->

		<!-- wp:button {"className":"is-style-outline"} -->
		<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/courses/' ) ); ?>">Ver los cursos</a></div>
		<!-- /wp:button -->

	</div>
	<!-- /wp:buttons -->

</div></div>
<!-- /wp:cover -->
