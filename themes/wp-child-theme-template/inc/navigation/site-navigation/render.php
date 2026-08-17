<?php
/**
 * The quedamos/site-navigation block's markup.
 *
 * Renders the whole header navigation: an inline row of links for desktop, and a
 * hamburger toggle with an inset panel for narrow viewports. Both are emitted on
 * every request and CSS decides which one is visible — there is no width at which
 * both show, and no user-agent sniffing. site-navigation.scss owns that swap.
 *
 * Emitting both halves is what lets one block replace the core/navigation +
 * mobile-menu pair the header used to carry. The two lists come from the same
 * array, so they cannot drift apart.
 *
 * The whole thing bails out before emitting anything when the menu has no links:
 * an empty panel is worse than no panel, and a toggle that opens onto nothing is
 * worse still.
 *
 * The data lookups live in inc/navigation/site-navigation-block.php — this file
 * fetches, loops and echoes escaped values.
 *
 * Behaviour (open, close, focus trap, Esc, scroll lock) is in
 * assets/scripts/js/site-navigation.js and hooks onto the data-site-navigation-*
 * attributes below. Nothing is inlined as script markup from here.
 *
 * @package Quedamos
 *
 * @var array    $attributes The block attributes.
 * @var string   $content    The block's inner content (unused — the block is void).
 * @var WP_Block $block      The block instance.
 */

defined( 'ABSPATH' ) || exit;

$quedamos_nav_items = quedamos_site_navigation_items( quedamos_site_navigation_ref( $attributes ) );

if ( ! $quedamos_nav_items ) {
	return;
}

$quedamos_nav_current = quedamos_site_navigation_current_id();
$quedamos_nav_id      = wp_unique_id( 'quedamos-site-navigation-' );
?>
<nav
	<?php echo get_block_wrapper_attributes( array( 'class' => 'site-navigation' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by core. ?>
	aria-label="<?php esc_attr_e( 'Primary', 'quedamos' ); ?>"
>
	<?php // The desktop row. Hidden below the breakpoint, which takes it out of the accessibility tree with it, so the panel's copy of the same links is not announced twice. ?>
	<ul class="site-navigation__desktop">
		<?php foreach ( $quedamos_nav_items as $quedamos_nav_item ) : ?>
			<?php $quedamos_nav_is_current = quedamos_site_navigation_is_current( $quedamos_nav_item, $quedamos_nav_current ); ?>
			<li class="site-navigation__desktop-item">
				<a
					class="site-navigation__desktop-link<?php echo $quedamos_nav_is_current ? ' site-navigation__desktop-link--current' : ''; ?>"
					href="<?php echo esc_url( $quedamos_nav_item['url'] ); ?>"
					<?php echo $quedamos_nav_is_current ? 'aria-current="page"' : ''; ?>
				><?php echo esc_html( $quedamos_nav_item['label'] ); ?></a>
			</li>
		<?php endforeach; ?>
	</ul>

	<button
		class="site-navigation__toggle"
		type="button"
		aria-expanded="false"
		aria-controls="<?php echo esc_attr( $quedamos_nav_id ); ?>"
		aria-label="<?php esc_attr_e( 'Open menu', 'quedamos' ); ?>"
		data-site-navigation-toggle
	>
		<?php echo quedamos_inline_svg( 'svgs/menu.svg' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- run through wp_kses by the helper. ?>
	</button>

	<div class="site-navigation__overlay" id="<?php echo esc_attr( $quedamos_nav_id ); ?>" data-site-navigation-overlay>
		<div class="site-navigation__scrim" data-site-navigation-scrim></div>

		<div
			class="site-navigation__panel"
			role="dialog"
			aria-modal="true"
			aria-label="<?php esc_attr_e( 'Site menu', 'quedamos' ); ?>"
			data-site-navigation-panel
		>
			<div class="site-navigation__top-bar">
				<div class="site-navigation__logo">
					<?php echo get_custom_logo(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by core. ?>
				</div>

				<button
					class="site-navigation__close"
					type="button"
					aria-label="<?php esc_attr_e( 'Close menu', 'quedamos' ); ?>"
					data-site-navigation-close
				>
					<?php echo quedamos_inline_svg( 'svgs/close.svg' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- run through wp_kses by the helper. ?>
				</button>
			</div>

			<ul class="site-navigation__panel-list">
				<?php foreach ( $quedamos_nav_items as $quedamos_nav_item ) : ?>
					<?php $quedamos_nav_is_current = quedamos_site_navigation_is_current( $quedamos_nav_item, $quedamos_nav_current ); ?>
					<li class="site-navigation__panel-item">
						<a
							class="site-navigation__panel-link<?php echo $quedamos_nav_is_current ? ' site-navigation__panel-link--current' : ''; ?>"
							href="<?php echo esc_url( $quedamos_nav_item['url'] ); ?>"
							<?php echo $quedamos_nav_is_current ? 'aria-current="page"' : ''; ?>
						><?php echo esc_html( $quedamos_nav_item['label'] ); ?></a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
</nav>
