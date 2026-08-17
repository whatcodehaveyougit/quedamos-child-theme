<?php
/**
 * The quedamos/mobile-menu block's markup.
 *
 * Rendered on every request the block appears on, so the whole thing bails out
 * before emitting anything when the menu has no links: an empty panel is worse
 * than no panel, and a toggle that opens onto nothing is worse still.
 *
 * The data lookups live in inc/navigation/mobile-menu-block.php — this file
 * fetches, loops and echoes escaped values.
 *
 * Behaviour (open, close, focus trap, Esc, scroll lock) is in
 * assets/scripts/js/mobile-menu.js and hooks onto the data-mobile-menu-*
 * attributes below. No <script> is emitted from here.
 *
 * @package Quedamos
 *
 * @var array    $attributes The block attributes.
 * @var string   $content    The block's inner content (unused — the block is void).
 * @var WP_Block $block      The block instance.
 */

defined( 'ABSPATH' ) || exit;

$quedamos_menu_items = quedamos_mobile_menu_items( quedamos_mobile_menu_ref( $attributes ) );

if ( ! $quedamos_menu_items ) {
	return;
}

$quedamos_menu_current = quedamos_mobile_menu_current_id();
$quedamos_menu_id      = wp_unique_id( 'quedamos-mobile-menu-' );
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'mobile-menu' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by core. ?>>
	<button
		class="mobile-menu__toggle"
		type="button"
		aria-expanded="false"
		aria-controls="<?php echo esc_attr( $quedamos_menu_id ); ?>"
		aria-label="<?php esc_attr_e( 'Open menu', 'quedamos' ); ?>"
		data-mobile-menu-toggle
	>
		<?php echo quedamos_inline_svg( 'svgs/menu.svg' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- run through wp_kses by the helper. ?>
	</button>

	<div class="mobile-menu__overlay" id="<?php echo esc_attr( $quedamos_menu_id ); ?>" data-mobile-menu-overlay>
		<div class="mobile-menu__scrim" data-mobile-menu-scrim></div>

		<div
			class="mobile-menu__panel"
			role="dialog"
			aria-modal="true"
			aria-label="<?php esc_attr_e( 'Site menu', 'quedamos' ); ?>"
			data-mobile-menu-panel
		>
			<div class="mobile-menu__top-bar">
				<div class="mobile-menu__logo">
					<?php echo get_custom_logo(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by core. ?>
				</div>

				<button
					class="mobile-menu__close"
					type="button"
					aria-label="<?php esc_attr_e( 'Close menu', 'quedamos' ); ?>"
					data-mobile-menu-close
				>
					<?php echo quedamos_inline_svg( 'svgs/close.svg' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- run through wp_kses by the helper. ?>
				</button>
			</div>

			<ul class="mobile-menu__list">
				<?php foreach ( $quedamos_menu_items as $quedamos_menu_item ) : ?>
					<?php $quedamos_menu_is_current = $quedamos_menu_item['id'] > 0 && $quedamos_menu_item['id'] === $quedamos_menu_current; ?>
					<li class="mobile-menu__item">
						<a
							class="mobile-menu__link<?php echo $quedamos_menu_is_current ? ' mobile-menu__link--current' : ''; ?>"
							href="<?php echo esc_url( $quedamos_menu_item['url'] ); ?>"
							<?php echo $quedamos_menu_is_current ? 'aria-current="page"' : ''; ?>
						><?php echo esc_html( $quedamos_menu_item['label'] ); ?></a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
</div>
