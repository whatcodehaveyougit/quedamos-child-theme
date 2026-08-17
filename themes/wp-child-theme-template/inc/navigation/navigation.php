<?php
/**
 * Site navigation.
 *
 * The header's navigation is the theme's own block, quedamos/site-navigation,
 * rather than core/navigation plus a filter on its rendered output. Owning the
 * markup means the desktop row and the mobile panel are one component with one
 * source of links, and the CSS stops fighting core's overlay.
 *
 * @package Quedamos
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/site-navigation-block.php';
