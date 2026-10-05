<?php
/**
 * Shared 404 helper for OAuth request handlers.
 */

declare( strict_types=1 );

namespace WPMedia\MCP\OAuth\Auth;

/**
 * Provides a clean 404 response for stale OAuth/discovery rewrite rules.
 */
trait Http404Trait {

	/**
	 * Force a clean 404 response.
	 *
	 * Used when a stale rewrite rule still routes a request to an OAuth or
	 * discovery endpoint after the OAuth server has been disabled, before
	 * rewrite rules have been flushed. Without this, WordPress's main query
	 * would fall through to the homepage instead of returning a 404.
	 *
	 * @return void
	 */
	private function force_404(): void {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
	}
}
