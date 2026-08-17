<?php
/**
 * Mobile navigation.
 *
 * The mobile menu is the theme's own block, quedamos/mobile-menu, rather than a
 * filter on core/navigation's rendered output. Owning the markup means the panel
 * chrome (top bar, close button, link rows) is written once in a template
 * instead of being spliced into a string, and the CSS stops fighting core's
 * overlay.
 *
 * @package Quedamos
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/mobile-menu-block.php';
