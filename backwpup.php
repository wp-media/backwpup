<?php
/*
 * Plugin Name: BackWPup 
 * Plugin URI: https://backwpup.com/
 * Description: WordPress Backup Plugin
 * Author: BackWPup – WordPress Backup & Restore Plugin
 * Author URI: https://backwpup.com
 * Version: 5.4.2-beta1
 * Requires at least: 4.9
 * Requires PHP: 7.4
 * Text Domain: backwpup
 * Domain Path: /languages
 * Network: true
 * License: GPLv2+
 */

use WPMedia\BackWPup\Dependencies\League\Container\Container;
use WPMedia\BackWPup\Plugin\Plugin;

if ( defined( 'BACKWPUP_PLUGIN_LOADED' ) || class_exists( \BackWPup::class, false ) ) {
	return;
}

define( 'BACKWPUP_PLUGIN_FILE', __FILE__ );
define( 'BACKWPUP_PLUGIN_LOADED', true );

// Include the Composer autoload file.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

require_once __DIR__ . '/inc/functions.php';
require_once __DIR__ . '/src/compat.php';

$restore_commons = untrailingslashit( BackWPup::get_plugin_data( 'plugindir' ) ) . '/src/Infrastructure/Restore/commons.php';

if ( $restore_commons ) {
	require_once $restore_commons;
}

require_once __DIR__ . '/inc/class-system-requirements.php';
require_once __DIR__ . '/inc/class-system-tests.php';

$system_requirements = new BackWPup_System_Requirements();
$system_tests        = new BackWPup_System_Tests( $system_requirements );

// Don't activate on anything less than PHP 7.4 or WordPress 4.9.
if ( ! $system_tests->is_php_version_compatible() || ! $system_tests->is_wp_version_compatible() ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php'; // @phpstan-ignore-line
	deactivate_plugins( __FILE__ );

	exit(
		sprintf(
			// translators: %1$s is the minimum PHP version, %2$s is the minimum WordPress version.
			esc_html__(
				'BackWPup requires PHP version %1$s with spl extension or greater and WordPress %2$s or greater.',
				'backwpup'
			),
			esc_html( $system_requirements->php_minimum_version() ),
			esc_html( $system_requirements->wp_minimum_version() )
		)
	);
}

// Deactivation hook.
register_deactivation_hook( __FILE__, [ BackWPup_Install::class, 'deactivate' ] );

$backwpup_plugin = new Plugin( new Container(), __FILE__ );

add_action( 'init', [ $backwpup_plugin, 'load_plugin_textdomain' ] );
add_action( 'plugins_loaded', [ $backwpup_plugin, 'init' ], 11 );
