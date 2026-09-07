<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Admin\Frontend;

use WPMedia\BackWPup\Common\Domains;

/**
 * Helpers shared by the bwu_redirect handler and the views that build its links.
 *
 * Keeping the nonce action and the destination check in one place makes sure a
 * link minted in a view is verified against the exact same rules when it comes
 * back, and that the allowed_redirect_hosts filter is never left registered.
 */
final class Redirect {

	/**
	 * Builds the nonce action for a redirect target.
	 *
	 * The action is bound to the destination, so a nonce minted for one URL
	 * cannot authorize a redirect to another one.
	 *
	 * @param string $url Destination the link points to.
	 *
	 * @return string
	 */
	public static function nonce_action( string $url ): string {
		return 'backwpup_redirect_' . md5( sanitize_url( $url ) );
	}

	/**
	 * Resolves a requested destination to a URL we accept, or an empty string.
	 *
	 * This is the single place deciding where bwu_redirect may send a user.
	 *
	 * @param string $url Destination taken from the request.
	 *
	 * @return string Validated destination, empty when the URL is malformed or off site.
	 */
	public static function validate( string $url ): string {
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return '';
		}

		$host = (string) wp_parse_url( $url, PHP_URL_HOST );
		if ( ! Domains::is_owned( $host ) ) {
			return '';
		}

		// wp_validate_redirect() still performs the scheme and parsing checks.
		return (string) self::with_host_allowed(
			$host,
			static function () use ( $url ) {
				return wp_validate_redirect( $url, '' );
			}
		);
	}

	/**
	 * Sends the user to an already validated destination.
	 *
	 * @param string $destination Result of a validate() call, or a local URL.
	 *
	 * @return void
	 */
	public static function to( string $destination ): void {
		$host = (string) wp_parse_url( $destination, PHP_URL_HOST );

		self::with_host_allowed(
			$host,
			static function () use ( $destination ) {
				return wp_safe_redirect( $destination );
			}
		);
	}

	/**
	 * Runs a callback with one extra host accepted by wp_safe_redirect().
	 *
	 * The filter is always removed again, so the widened list cannot affect a
	 * redirect performed anywhere else.
	 *
	 * @param string   $host     Host to accept for the duration of the callback.
	 * @param callable $callback Code to run.
	 *
	 * @return mixed Whatever the callback returned.
	 */
	private static function with_host_allowed( string $host, callable $callback ) {
		$allow = static function ( $hosts ) use ( $host ) {
			$hosts[] = $host;

			return $hosts;
		};

		add_filter( 'allowed_redirect_hosts', $allow );

		try {
			return $callback();
		} finally {
			remove_filter( 'allowed_redirect_hosts', $allow );
		}
	}
}
