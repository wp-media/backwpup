<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Admin\Beacon;

use WPMedia\BackWPup\EventManagement\SubscriberInterface;
use WPMedia\Mixpanel\Optin;
use WPMedia\Mixpanel\TrackingPlugin;

class Subscriber implements SubscriberInterface {

	/**
	 * Mixpanel Tracking instance.
	 *
	 * @var TrackingPlugin
	 */
	private $tracking;

	/**
	 * Mixpanel Optin instance.
	 *
	 * @var Optin
	 */
	private $optin;

	/**
	 * Subscriber constructor.
	 *
	 * @param TrackingPlugin $tracking Mixpanel Tracking instance.
	 * @param Optin          $optin Mixpanel Optin instance.
	 */
	public function __construct( TrackingPlugin $tracking, Optin $optin ) {
		$this->tracking = $tracking;
		$this->optin    = $optin;
	}

	/**
	 * Return an array of events that this subscriber wants to listen to.
	 *
	 * @return array
	 */
	public static function get_subscribed_events(): array {
		return [
			'backwpup_url_add_hash' => 'add_hash_to_url',
		];
	}

	/**
	 * Add a unique hash to URLs for tracking purposes, but only for users who have opted in to tracking and only for backwpup websites.
	 *
	 * @param string $url The original URL to potentially modify.
	 *
	 * @return string The modified URL with a hash parameter if conditions are met, or the original URL otherwise.
	 */
	public function add_hash_to_url( string $url ): string {
		// Only add hash if user has opted in to tracking.
		if ( ! $this->optin->is_enabled() ) {
			return $url;
		}

		// Parse host and ensure URL belongs to backwpup.com or backwpup.de (or their subdomains).
		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( empty( $host ) ) {
			return $url;
		}
		$host              = strtolower( $host );
		$allowed_domains   = [
			'backwpup.com',
			'backwpup.de',
		];
		$is_allowed_domain = false;
		foreach ( $allowed_domains as $domain ) {
			if ( $host === $domain || ( strlen( $host ) > strlen( $domain ) && substr( $host, -strlen( '.' . $domain ) ) === '.' . $domain ) ) {
				$is_allowed_domain = true;
				break;
			}
		}
		if ( ! $is_allowed_domain ) {
			return $url;
		}
		$user = wp_get_current_user();
		// Do not add a hash if the current user email is empty.
		if ( empty( $user->user_email ) ) {
			return $url;
		}
		$email_hash = $this->tracking->hash( $user->user_email );
		$url        = add_query_arg( 'bwu_hash', $email_hash, $url );
		return $url;
	}
}
