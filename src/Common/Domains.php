<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Common;

/**
 * The web properties BackWPup owns.
 *
 * Single source of truth for "is this one of our hosts". Used to decide where
 * an in product link may send a user and which links may carry a tracking hash.
 */
final class Domains {

	/**
	 * Domains we own, apex only. Subdomains are matched by is_owned().
	 *
	 * The .local entries are the development sites used to work on the
	 * marketing pages.
	 *
	 * @var string[]
	 */
	public const OWNED = [
		'backwpup.com',
		'backwpup.de',
		'com.bwu.local',
		'de.bwu.local',
	];

	/**
	 * Tells whether a host is one of ours, or a subdomain of one.
	 *
	 * @param string $host Host name, as returned by wp_parse_url().
	 *
	 * @return bool
	 */
	public static function is_owned( string $host ): bool {
		$host = strtolower( $host );

		if ( '' === $host ) {
			return false;
		}

		foreach ( self::OWNED as $domain ) {
			if ( $host === $domain ) {
				return true;
			}

			if ( strlen( $host ) > strlen( $domain ) && substr( $host, -strlen( '.' . $domain ) ) === '.' . $domain ) {
				return true;
			}
		}

		return false;
	}
}
