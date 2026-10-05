<?php
declare( strict_types=1 );

namespace WPMedia\MCP\OAuth\Transport;

use WP\MCP\Core\McpAdapter;
use WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler;
use WPMedia\MCP\OAuth\Context;

class Server {
	/**
	 * OAuth server context.
	 *
	 * @var Context
	 */
	private $context;

	/**
	 * Constructor.
	 *
	 * @param Context $context OAuth server context.
	 */
	public function __construct( Context $context ) {
		$this->context = $context;
	}

	/**
	 * Register the MCP server with the adapter.
	 *
	 * Creates an isolated server at /wp-json/mcp/mcp-oauth-server using the custom
	 * OAuthHttpTransport for JWT Bearer authentication.
	 *
	 * @return void
	 */
	public function register_server(): void {
		if ( ! class_exists( McpAdapter::class ) ) {
			return;
		}

		$adapter = McpAdapter::instance();

		$adapter->create_server(
			'mcp-oauth-server',
			'mcp',
			'mcp-oauth-server',
			'MCP OAuth Server',
			'MCP Server with OAuth 2.1 authentication',
			'v1.0.0',
			[ OAuthHttpTransport::class ],
			ErrorLogMcpErrorHandler::class,
			$this->context->observability_handler(),
			[
				'mcp-adapter/discover-abilities',
				'mcp-adapter/get-ability-info',
				'mcp-adapter/execute-ability',
			]
		);
	}
}
