<?php
/**
 * Theme bootstrap.
 *
 * This file is a registry and nothing else: it loads each module under inc/ and
 * gets out of the way. Behaviour belongs in a module, so that removing a line
 * below removes that feature entirely — no orphan hooks left registered
 * elsewhere. See .claude/skills/writing-php/SKILL.md for the module conventions.
 *
 * @package Quedamos
 */

defined( 'ABSPATH' ) || exit;

/**
 * Modules to load, in order.
 *
 * Each entry is a folder under inc/ containing an index file of the same name.
 * helpers comes first because the other modules call into it.
 */
$quedamos_modules = array(
	'helpers',
	'assets',
	'analytics',
	'redirects',
	'navigation',
	'blog',
	'booking',
	'courses',
	'schema',
);

foreach ( $quedamos_modules as $quedamos_module ) {
	$quedamos_module_file = get_stylesheet_directory() . "/inc/{$quedamos_module}/{$quedamos_module}.php";

	if ( file_exists( $quedamos_module_file ) ) {
		require_once $quedamos_module_file;
	}
}

unset( $quedamos_module, $quedamos_module_file );
