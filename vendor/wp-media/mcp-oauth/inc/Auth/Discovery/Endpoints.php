<?php
/**
 * OAuth 2.0 Discovery Endpoints.
 *
 * Registers the two RFC-mandated well-known documents so MCP clients can
 * auto-discover the authorization server metadata without hard-coding URLs.
 *
 * Paths served:
 *   GET /.well-known/oauth-protected-resource  (RFC 9728)
 *   GET /.well-known/oauth-authorization-server (RFC 8414)
 */

declare(strict_types=1);

namespace WPMedia\MCP\OAuth\Auth\Discovery;

use WPMedia\MCP\OAuth\Auth\Http404Trait;
use WPMedia\MCP\OAuth\Context;
use WPMedia\MCP\OAuth\Logging\McpLogger;

class Endpoints {

	use Http404Trait;

	/**
	 * Query var name used to route discovery requests.
	 */
	const QUERY_VAR = 'mcp_oauth_discovery';

	/**
	 * OAuth server context.
	 *
	 * @var Context
	 */
	private Context $context;

	/**
	 * Constructor.
	 *
	 * @param Context $context OAuth server context.
	 */
	public function __construct( Context $context ) {
		$this->context = $context;
	}

	/**
	 * Register rewrite rules for the .well-known paths.
	 *
	 * Called on the 'init' action.
	 *
	 * @return void
	 */
	public function add_rewrite_rules(): void {
		if ( ! $this->context->is_enabled() ) {
			return;
		}

		add_rewrite_rule(
			'^\\.well-known/oauth-protected-resource$',
			'index.php?' . self::QUERY_VAR . '=protected-resource',
			'top'
		);
		add_rewrite_rule(
			'^\\.well-known/oauth-authorization-server$',
			'index.php?' . self::QUERY_VAR . '=authorization-server',
			'top'
		);
	}

	/**
	 * Add the OAuth query var to WordPress's list of recognised vars.
	 *
	 * @param string[] $vars Existing query vars.
	 * @return string[] Modified list.
	 */
	public function add_query_vars( array $vars ): array {
		$vars[] = self::QUERY_VAR;

		return $vars;
	}

	/**
	 * Serve the discovery document if the request matches.
	 *
	 * @return void
	 */
	public function handle_request(): void {
		$discovery = (string) get_query_var( self::QUERY_VAR, '' );

		if ( '' === $discovery ) {
			return;
		}

		if ( ! $this->context->is_enabled() ) {
			$this->force_404();
			return;
		}

		// Every OAuth endpoint and both .well-known documents are served through
		// rewrite rules, which resolve against home_url() (the Site Address) — the
		// same base get_rest_url() uses for the resource/audience below. Advertising
		// them under get_site_url() (the WordPress Address) would point clients at
		// the wrong location on installs where WordPress lives in its own directory
		// (siteurl !== home).
		$base_url = home_url();

		McpLogger::log(
			'DISCOVERY',
			'request received',
			[
				'document'    => $discovery,
				'remote_addr' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
				'user_agent'  => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
			]
		);

		if ( 'protected-resource' === $discovery ) {
			$body = [
				'resource'                 => get_rest_url( null, 'mcp/mcp-oauth-server' ),
				'authorization_servers'    => [ $base_url ],
				'bearer_methods_supported' => [ 'header' ],
				'scopes_supported'         => [ 'mcp' ],
			];
			McpLogger::log( 'DISCOVERY', 'serving protected-resource document', $body );
			wp_send_json( $body );
		} elseif ( 'authorization-server' === $discovery ) {
			$body = [
				'issuer'                                => $base_url,
				'authorization_endpoint'                => $base_url . '/oauth/authorize',
				'token_endpoint'                        => $base_url . '/oauth/token',
				'revocation_endpoint'                   => $base_url . '/oauth/revoke',
				'response_types_supported'              => [ 'code' ],
				'grant_types_supported'                 => [ 'authorization_code', 'refresh_token' ],
				'code_challenge_methods_supported'      => [ 'S256' ],
				'scopes_supported'                      => [ 'mcp' ],
				'token_endpoint_auth_methods_supported' => [ 'none' ],
				'client_id_metadata_document_supported' => true,
				'authorization_response_iss_parameter_supported' => true,
			];
			McpLogger::log( 'DISCOVERY', 'serving authorization-server document', $body );
			wp_send_json( $body );
		}
	}
}
