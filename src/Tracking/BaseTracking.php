<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Tracking;

use WPMedia\BackWPup\Dependencies\WPMedia\Mixpanel\Optin;
use WPMedia\BackWPup\Dependencies\WPMedia\Mixpanel\TrackingPlugin as MixpanelTracking;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Base class for tracking functionality.
 *
 * Provides common methods and properties for all tracking classes.
 */
abstract class BaseTracking {
	/**
	 * Optin instance.
	 *
	 * @var Optin
	 */
	protected Optin $optin;

	/**
	 * Mixpanel Tracking instance.
	 *
	 * @var MixpanelTracking
	 */
	protected MixpanelTracking $mixpanel;

	/**
	 * Constructor
	 *
	 * @param Optin            $optin    Optin instance.
	 * @param MixpanelTracking $mixpanel Mixpanel Tracking instance.
	 */
	public function __construct( Optin $optin, MixpanelTracking $mixpanel ) {
		$this->optin    = $optin;
		$this->mixpanel = $mixpanel;
	}

	/**
	 * Check if tracking is enabled and allowed.
	 *
	 * @return bool
	 */
	protected function can_track(): bool {
		return $this->optin->can_track();
	}

	/**
	 * Identify a user in Mixpanel.
	 *
	 * @param \WP_User $user User object.
	 *
	 * @return void
	 */
	protected function identify_user( \WP_User $user ): void {
		if ( $user->exists() ) {
			$this->mixpanel->identify( $user->user_email );
		}
	}

	/**
	 * Get user's primary role.
	 *
	 * @param \WP_User $user User object.
	 *
	 * @return string
	 */
	protected function get_user_primary_role( \WP_User $user ): string {
		if ( ! $user->exists() ) {
			return 'guest';
		}

		$roles = $user->roles;
		return ! empty( $roles ) ? $roles[0] : 'none';
	}

	/**
	 * Get the default event properties, including the license email hash if available.
	 *
	 * @param string $context Context identifier (e.g., 'wp_plugin', 'wp_plugin_mcp').
	 *
	 * @return array
	 */
	protected function get_default_event_properties( string $context = 'wp_plugin' ): array {
		$defaults = [
			'context' => $context,
		];

		if ( \BackWPup::is_pro() ) {
			// Use string value directly instead of LicenseManager::LICENSE_EMAIL constant
			// to avoid loading LicenseManager class (which doesn't exist in Free version).
			$license_email_hash = get_site_option( 'backwpup_license_email', '' );
			if ( '' !== $license_email_hash ) {
				$defaults['license_owner'] = $license_email_hash;
			}
		}

		return $defaults;
	}

	/**
	 * Sanitize input parameters for tracking.
	 *
	 * Removes sensitive data and limits array size to prevent tracking bloat.
	 *
	 * @param array $params Input parameters.
	 *
	 * @return array
	 */
	protected function sanitize_input_params( array $params ): array {
		// List of sensitive keys to exclude.
		$sensitive_keys = [
			'password',
			'api_key',
			'secret',
			'token',
			'auth',
			'credentials',
			'email',
		];

		$sanitized = [];

		foreach ( $params as $key => $value ) {
			// Skip sensitive keys.
			$key_lower = strtolower( (string) $key );
			foreach ( $sensitive_keys as $sensitive ) {
				if ( false !== strpos( $key_lower, $sensitive ) ) {
					continue 2;
				}
			}

			// Limit array depth.
			if ( is_array( $value ) ) {
				$sanitized[ $key ] = '[array:' . count( $value ) . ']';
			} elseif ( is_object( $value ) ) {
				$sanitized[ $key ] = '[object:' . get_class( $value ) . ']';
			} else {
				$sanitized[ $key ] = $value;
			}
		}

		return $sanitized;
	}
}
