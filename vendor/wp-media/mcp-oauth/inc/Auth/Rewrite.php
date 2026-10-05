<?php
/**
 * OAuth 2.1 Rewrite Rules.
 *
 * Registers the WordPress rewrite rules and query var used to route the
 * five OAuth endpoints (authorize, authorize-callback, token, consent,
 * revoke).
 */

declare( strict_types=1 );

namespace WPMedia\MCP\OAuth\Auth;

/**
 * Registers OAuth endpoint rewrite rules and the query var used to route them.
 */
class Rewrite {

	/**
	 * WordPress query var used to route OAuth endpoint requests.
	 */
	const OAUTH_QUERY_VAR = 'mcp_oauth_endpoint';

	/**
	 * Canary rule key used to detect whether the OAuth rules are persisted.
	 */
	const AUTHORIZE_RULE = '^oauth/authorize$';

	/**
	 * Register WordPress rewrite rules for all five OAuth endpoints.
	 *
	 * Called on the 'init' action (normal page load).
	 *
	 * @return void
	 */
	public function register_oauth_rewrite_rules(): void {
		add_rewrite_rule( self::AUTHORIZE_RULE, 'index.php?' . self::OAUTH_QUERY_VAR . '=authorize', 'top' );
		add_rewrite_rule( '^oauth/authorize-callback$', 'index.php?' . self::OAUTH_QUERY_VAR . '=authorize-callback', 'top' );
		add_rewrite_rule( '^oauth/token$', 'index.php?' . self::OAUTH_QUERY_VAR . '=token', 'top' );
		add_rewrite_rule( '^oauth/consent$', 'index.php?' . self::OAUTH_QUERY_VAR . '=consent', 'top' );
		add_rewrite_rule( '^oauth/revoke$', 'index.php?' . self::OAUTH_QUERY_VAR . '=revoke', 'top' );
	}

	/**
	 * Add the OAuth query var to WordPress's list of recognised vars.
	 *
	 * @param string[] $vars Existing query vars.
	 * @return string[] Modified list.
	 */
	public function add_oauth_query_vars( array $vars ): array {
		$vars[] = self::OAUTH_QUERY_VAR;

		return $vars;
	}
}
