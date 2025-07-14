<?php
declare(strict_types=1);

namespace WPMedia\Mixpanel;

use Mixpanel;

class Tracking {
	/**
	 * Mixpanel instance
	 *
	 * @var Mixpanel
	 */
	private $mixpanel;

	/**
	 * Constructor
	 *
	 * @param string $mixpanel_token Mixpanel token.
	 */
	public function __construct( string $mixpanel_token ) {
		$this->mixpanel = Mixpanel::getInstance(
			$mixpanel_token,
			[
				'host'            => 'api-eu.mixpanel.com',
				'events_endpoint' => '/track/?ip=0',
			]
		);
	}

	/**
	 * Track an event in Mixpanel
	 *
	 * @param string  $event Event name.
	 * @param mixed[] $properties Event properties.
	 */
	public function track( string $event, array $properties ): void {
		$this->mixpanel->track( $event, $properties );
	}

	/**
	 * Identify a user in Mixpanel
	 *
	 * @param string $user_id User ID.
	 *
	 * @return void
	 */
	public function identify( string $user_id ): void {
		$this->mixpanel->identify( hash( 'sha3-224', $user_id ) );
	}

	/**
	 * Set a user property in Mixpanel
	 *
	 * @param string $user_id User ID.
	 * @param string $property Property name.
	 * @param mixed  $value Property value.
	 */
	public function set_user_property( string $user_id, string $property, $value ): void {
		$this->mixpanel->people->set(
			$user_id,
			[
				$property => $value,
			],
			'0'
		);
	}

	/**
	 * Hash a value using SHA3-224
	 *
	 * @param string $value Value to hash.
	 *
	 * @return string
	 */
	public function hash( string $value ): string {
		return hash( 'sha3-224', $value );
	}

	/**
	 * Get the WordPress version
	 *
	 * @return string
	 */
	public function get_wp_version(): string {
		$version = preg_replace( '@^(\d\.\d+).*@', '\1', get_bloginfo( 'version' ) );

		if ( null === $version ) {
			$version = '0.0';
		}

		return $version;
	}

	/**
	 * Get the PHP version
	 *
	 * @return string
	 */
	public function get_php_version(): string {
		$version = preg_replace( '@^(\d\.\d+).*@', '\1', phpversion() );

		if ( null === $version ) {
			$version = '0.0';
		}

		return $version;
	}

	/**
	 * Get the active theme
	 *
	 * @return string
	 */
	public function get_current_theme(): string {
		$theme = wp_get_theme();

		return $theme->get( 'Name' );
	}

	/**
	 * Get list of active plugins names
	 *
	 * @return string[]
	 */
	public function get_active_plugins(): array {
		$plugins        = [];
		$active_plugins = (array) get_option( 'active_plugins', [] );
		$all_plugins    = get_plugins();

		foreach ( $active_plugins as $plugin_path ) {
			if ( ! isset( $all_plugins[ $plugin_path ] ) ) {
				continue;
			}

			$plugins[] = $all_plugins[ $plugin_path ]['Name'];
		}

		return $plugins;
	}
}
