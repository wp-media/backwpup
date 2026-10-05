<?php
declare(strict_types=1);

namespace WPMedia\MCP\OAuth;

use WP\MCP\Infrastructure\Observability\Contracts\McpObservabilityHandlerInterface;
use WP\MCP\Infrastructure\Observability\NullMcpObservabilityHandler;

class Context {
	/**
	 * Determines whether the MCP OAuth server is enabled.
	 *
	 * @return bool True when the OAuth server (rewrite rules, endpoints,
	 *              discovery documents, transport) should be registered; false otherwise.
	 */
	public function is_enabled(): bool {
		$enabled = apply_filters_deprecated( 'rocket_mcp_oauth_server_enabled', [ true ], '1.0.1', 'wpmedia_mcp_oauth_server_enabled' );

		/**
		 * Filters whether the MCP OAuth server is enabled.
		 *
		 * When `false`, the host plugin does not register the OAuth rewrite rules
		 * or respond to any /oauth/* endpoint or /.well-known discovery request,
		 * and does not register the MCP OAuth transport server.
		 *
		 * @param bool $enabled Whether the MCP OAuth server is enabled. Default true.
		 */
		return wpm_apply_filters_typed( 'boolean', 'wpmedia_mcp_oauth_server_enabled', $enabled );
	}

	/**
	 * Observability handler class used by the MCP server.
	 *
	 * Defaults to the mcp-adapter's null handler, so no observability event is
	 * written unless a site opts in. The verbose handler
	 * (`WPMedia\MCP\OAuth\Transport\McpObservabilityHandler`) records one event per
	 * server creation, i.e. once per REST request, which floods debug.log on sites
	 * running with WP_DEBUG and WP_DEBUG_LOG enabled.
	 *
	 * @return class-string<McpObservabilityHandlerInterface> Observability handler class name.
	 */
	public function observability_handler(): string {
		/**
		 * Filters the observability handler class used by the MCP server.
		 *
		 * The value must be a class implementing the mcp-adapter's
		 * McpObservabilityHandlerInterface; any other value is ignored and the
		 * null handler is used instead.
		 *
		 * @param string $handler Observability handler class name. Default NullMcpObservabilityHandler.
		 */
		$handler = wpm_apply_filters_typed( 'string', 'wpmedia_mcp_oauth_observability_handler', NullMcpObservabilityHandler::class );

		if ( ! is_a( $handler, McpObservabilityHandlerInterface::class, true ) ) {
			return NullMcpObservabilityHandler::class;
		}

		return $handler;
	}
}
