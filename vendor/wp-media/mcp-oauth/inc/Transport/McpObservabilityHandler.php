<?php
/**
 * MCP Observability Handler.
 *
 * Bridges the MCP adapter's observability interface with this library's
 * McpLogger, providing structured request/event metrics without external
 * dependencies. Implements the single-method McpObservabilityHandlerInterface.
 */

declare( strict_types=1 );

namespace WPMedia\MCP\OAuth\Transport;

use WP\MCP\Infrastructure\Observability\Contracts\McpObservabilityHandlerInterface;
use WP\MCP\Infrastructure\Observability\McpObservabilityHelperTrait;
use WPMedia\MCP\OAuth\Logging\McpLogger;

/**
 * Observability handler that writes MCP events to the MCP OAuth log.
 */
class McpObservabilityHandler implements McpObservabilityHandlerInterface {
	use McpObservabilityHelperTrait;

	/**
	 * Record an MCP event, optionally with timing data.
	 *
	 * Events are written via McpLogger under the 'OBSERVABILITY' scope.
	 * All events, including error/failure events, are only written when
	 * both WP_DEBUG_LOG and WP_DEBUG are enabled.
	 *
	 * @param string     $event       Event name (e.g. 'mcp.request', 'mcp.component.registration').
	 * @param array      $tags        Key-value context tags (status, method, tool_name, etc.).
	 * @param float|null $duration_ms Optional request duration in milliseconds.
	 * @return void
	 */
	public function record_event( string $event, array $tags = [], ?float $duration_ms = null ): void {
		$formatted_event = self::format_metric_name( $event );
		$merged_tags     = self::merge_tags( $tags );

		if ( null !== $duration_ms ) {
			$merged_tags['duration_ms'] = round( $duration_ms, 2 );
		}

		McpLogger::log( 'OBSERVABILITY', $formatted_event, $merged_tags );
	}
}
