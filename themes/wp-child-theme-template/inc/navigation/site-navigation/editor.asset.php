<?php
/**
 * Script dependencies and version for site-navigation/editor.js.
 *
 * block.json's `editorScript: file:./editor.js` makes WordPress look for this
 * file beside the script. Builds that use @wordpress/scripts generate it; this
 * theme has no build step for editor JS (see editor.js), so it is written by
 * hand — which means the dependency list below has to be kept in step with the
 * wp.* globals editor.js actually reads. Miss one and the editor throws on an
 * undefined global before the block ever registers.
 *
 * The version is the script's own mtime rather than a literal, mirroring
 * quedamos_asset_version() in inc/assets/assets.php: editing editor.js busts the
 * cache on its own, with nobody remembering to bump a string.
 *
 * @package Quedamos
 */

defined( 'ABSPATH' ) || exit;

return array(
	'dependencies' => array(
		'wp-blocks',
		'wp-block-editor',
		'wp-components',
		'wp-element',
		'wp-i18n',
		'wp-data',
		'wp-server-side-render',
	),
	'version'      => function_exists( 'quedamos_asset_version' )
		? quedamos_asset_version( __DIR__ . '/editor.js' )
		: null,
);
