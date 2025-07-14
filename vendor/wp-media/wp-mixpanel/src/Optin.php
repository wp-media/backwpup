<?php
declare(strict_types=1);

namespace WPMedia\Mixpanel;

class Optin {
	/**
	 * The plugin slug.
	 *
	 * @var string
	 */
	private $plugin_slug;

	/**
	 * The capability required to enable/disable the opt-in.
	 *
	 * @var string
	 */
	private $capability;

	/**
	 * Constructor.
	 *
	 * @param string $plugin_slug The plugin slug.
	 * @param string $capability  The capability required to enable/disable the opt-in.
	 */
	public function __construct( string $plugin_slug, string $capability ) {
		$this->plugin_slug = $plugin_slug;
		$this->capability  = $capability;
	}

	/**
	 * Check if the opt-in is enabled.
	 *
	 * @return bool True if the opt-in is enabled, false otherwise.
	 */
	public function is_enabled(): bool {
		if ( ! current_user_can( $this->capability ) ) {
			return false;
		}

		$optin = get_option( $this->plugin_slug . '_mixpanel_optin', false );

		if ( ! $optin ) {
			return false;
		}

		return true;
	}

	/**
	 * Enable the opt-in.
	 *
	 * @return void
	 */
	public function enable(): void {
		if ( $this->is_enabled() ) {
			return;
		}

		update_option( $this->plugin_slug . '_mixpanel_optin', true );
	}

	/**
	 * Disable the opt-in.
	 *
	 * @return void
	 */
	public function disable(): void {
		if ( ! $this->is_enabled() ) {
			return;
		}

		delete_option( $this->plugin_slug . '_mixpanel_optin' );
	}
}
