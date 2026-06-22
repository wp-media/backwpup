<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pro_providers = [];
if ( BackWPup::is_pro() ) {
	$pro_providers = [
		'WPMedia\BackWPup\Backup\ServiceProviderPro',
		'WPMedia\BackWPup\License\ServiceProvider',
		'WPMedia\BackWPup\Cli\ServiceProviderPro',
	];
}

$providers = [
	'WPMedia\BackWPup\Adapters\ServiceProvider',
	'WPMedia\BackWPup\StorageProviders\ServiceProvider',
	'WPMedia\BackWPup\Tracking\ServiceProvider',
	'WPMedia\BackWPup\Admin\ServiceProvider',
	'WPMedia\BackWPup\Backup\ServiceProvider',
	'WPMedia\BackWPup\Jobs\ServiceProvider',
	'WPMedia\BackWPup\Backups\ServiceProvider',
	'WPMedia\BackWPup\Frontend\ServiceProvider',
	'WPMedia\BackWPup\Beta\ServiceProvider',
	'WPMedia\BackWPup\Cli\ServiceProvider',
	'WPMedia\BackWPup\Hosting\ServiceProvider',
	'WPMedia\BackWPup\Log\ServiceProvider',
];

$mcp_providers = [];
/**
 * Filter whether the BackWPup MCP server is enabled or not.
 *
 * When this resolves to false, the MCP service provider is not registered,
 * so the MCP REST endpoint, server configuration, and abilities are never loaded.
 *
 * @since 5.7.3
 *
 * @param bool $enabled Whether the MCP server is enabled or not. Default true.
 */
if ( wpm_apply_filters_typed( 'boolean', 'backwpup_mcp_server_enabled', true ) ) {
	$mcp_providers = [ 'WPMedia\BackWPup\MCP\ServiceProvider' ];
}

return array_merge( $providers, $pro_providers, $mcp_providers );
