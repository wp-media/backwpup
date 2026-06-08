<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\MCP;

use WPMedia\BackWPup\EventManagement\SubscriberInterface;

/**
 * MCP Configuration Subscriber
 *
 * Customizes the MCP server configuration for BackWPup.
 */
class ConfigSubscriber implements SubscriberInterface {

	/**
	 * Get the events to subscribe to.
	 *
	 * @return array
	 */
	public static function get_subscribed_events(): array {
		return [
			'mcp_adapter_default_server_config' => 'customize_mcp_server',
		];
	}

	/**
	 * Customize MCP server configuration
	 *
	 * @param array $config Default server configuration.
	 *
	 * @return array Modified configuration.
	 */
	public function customize_mcp_server( array $config ): array {
		// Override server name to match issue requirements.
		$config['server_name'] = 'groupone-backwpup-plugin';

		// Set instructions that reference the documentation resource.
		$config['server_description'] = __(
			'This is the BackWPup MCP server. For context and quick-start guidance, read the resource at groupone-backwpup-plugin://docs/overview before calling any tools.',
			'backwpup'
		);

		return $config;
	}
}
