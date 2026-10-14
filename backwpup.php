<?php
/**
 * Plugin Name: BackWPup
 * Plugin URI: https://backwpup.com/
 * Description: WordPress Backup Plugin
 * Author: BackWPup – WordPress Backup & Restore Plugin
 * Author URI: https://backwpup.com
 * Version: 5.7.8
 * Requires at least: 5.3
 * Requires PHP: 7.4
 * Text Domain: backwpup
 * Domain Path: /languages
 * Network: true
 * License: GPLv2+
 */

use WPMedia\BackWPup\Dependencies\League\Container\Container;
use WPMedia\BackWPup\Plugin\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'BACKWPUP_PLUGIN_LOADED' ) || class_exists( \BackWPup::class, false ) ) {
	return;
}

define( 'BACKWPUP_PLUGIN_FILE', __FILE__ );
define( 'BACKWPUP_PLUGIN_LOADED', true );

// Include the Composer autoload file.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}


/**
 * Boot the MCP OAuth server once the MCP Adapter (standalone plugin or, on older
 * installs, the now-removed bundled copy) and the Abilities API are available.
 *
 * Runs on `plugins_loaded` (not at file-include time) so it doesn't depend on
 * plugin load order — e.g. BackWPup network-activated and the MCP Adapter
 * plugin site-activated later in the same `plugins_loaded` pass. The default
 * MCP server itself is booted by the MCP Adapter plugin on its own
 * `plugins_loaded` hook; BackWPup only needs to boot the OAuth server here.
 *
 * Runs at priority 10, before `$backwpup_plugin->init()` at priority 11.
 */
add_action(
	'plugins_loaded',
	static function () {
		$can_boot_mcp_adapter =
			class_exists( 'WP\MCP\Core\McpAdapter' )
			&& version_compare( $GLOBALS['wp_version'] ?? '0', '6.9', '>=' )
			&& function_exists( 'wp_register_ability' )
			&& function_exists( 'wp_get_ability' )
			&& function_exists( 'wp_get_abilities' )
			&& function_exists( 'wp_register_ability_category' );

		if ( ! $can_boot_mcp_adapter ) {
			return;
		}

		/**
		 * Filter whether the BackWPup MCP server is enabled or not.
		 *
		 * When this resolves to false, the MCP OAuth server is not instantiated.
		 *
		 * @since 5.7.3
		 *
		 * @param bool $enabled Whether the MCP server is enabled or not. Default true.
		 */
		if ( ! wpm_apply_filters_typed( 'boolean', 'backwpup_mcp_server_enabled', true ) ) {
			return;
		}

		if ( class_exists( \WPMedia\MCP\OAuth\Bootstrap::class ) ) {
			try {
				\WPMedia\MCP\OAuth\Bootstrap::instance();
			} catch ( \Throwable $throwable ) {
				// Never let a failure in the shared OAuth library take down the whole site —
				// the default MCP server booted by the MCP Adapter plugin must keep working regardless.
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- WP_DEBUG-gated diagnostic only.
					error_log( sprintf( 'BackWPup: MCP OAuth bootstrap failed: %s', $throwable->getMessage() ) );
				}
			}
		}
	},
	10
);

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

add_action( 'plugins_loaded', [ $backwpup_plugin, 'init' ], 11 );
