<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Beta;

use WPMedia\BackWPup\Adapters\BackWPupAdapter;
use WPMedia\BackWPup\EventManagement\SubscriberInterface;
use WPMedia\Beta\{Beta, Optin};

class Subscriber implements SubscriberInterface {
	/**
	 * The beta opt-in instance.
	 *
	 * @var Optin
	 */
	private Optin $optin;

	/**
	 * The beta instance.
	 *
	 * @var Beta
	 */
	private Beta $beta;

	/**
	 * The BackWPup adapter instance.
	 *
	 * @var BackWPupAdapter
	 */
	private $adapter;

	/**
	 * Constructor.
	 *
	 * @param Optin           $optin The beta opt-in instance.
	 * @param Beta            $beta  The beta instance.
	 * @param BackWPupAdapter $adapter The BackWPup adapter instance.
	 */
	public function __construct( Optin $optin, Beta $beta, BackWPupAdapter $adapter ) {
		$this->optin   = $optin;
		$this->beta    = $beta;
		$this->adapter = $adapter;
	}

	/**
	 * Returns an array of events this subscriber wants to listen to.
	 *
	 * @return array
	 */
	public static function get_subscribed_events(): array {
		return [
			'backwpup_page_settings_save' => 'update_setting',
			'init'                        => 'init',
		];
	}

	/**
	 * Update the beta opt-in setting
	 *
	 * @return void
	 */
	public function update_setting(): void {
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'backwpup_page' ) ) {
			return;
		}

		if ( $this->adapter->is_pro() ) {
			if ( $this->optin->is_enabled() ) {
				$this->optin->disable();
			}

			return;
		}

		if ( get_site_option( 'backwpup_onboarding', false ) ) {
			return;
		}

		$value = isset( $_POST['beta'] ) ? 1 : 0;

		/**
		 * Fires when the beta opt-in setting is changed.
		 *
		 * @param int $value The new value of the beta opt-in setting.
		 */
		do_action( 'backwpup_beta_optin_change', $value );

		if ( 0 === $value ) {
			if ( $this->optin->is_enabled() ) {
				$this->optin->disable();
			}

			return;
		}

		if ( 1 === $value ) {
			$this->optin->enable();
		}
	}

	/**
	 * Initialize the beta functionality
	 *
	 * @return void
	 */
	public function init(): void {
		if ( $this->adapter->is_pro() ) {
			return;
		}

		$this->beta->set_update_message( __( 'This update is a beta version.', 'backwpup' ) );
		$this->beta->init();
	}
}
