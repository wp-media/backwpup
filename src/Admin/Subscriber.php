<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Admin;

use WPMedia\BackWPup\EventManagement\SubscriberInterface;

class Subscriber implements SubscriberInterface {
	/**
	 * Backwpup options instance
	 *
	 * @var OptionData
	 */
	private $options;

	/**
	 * Instantiate the class
	 *
	 * @param OptionData $options Backwpup options instance.
	 */
	public function __construct( OptionData $options ) {
		$this->options = $options;
	}


	/**
	 * Returns the events this subscriber wants to listen to.
	 *
	 * @return array
	 */
	public static function get_subscribed_events() {
		return [
			'admin_init' => 'backwpup_migrate_options',
		];
	}

	/**
	 * Migrate backwpup global settings to a single option data
	 *
	 * @return void
	 */
	public function backwpup_migrate_options() {
		// If this migrate is done, bail early.
		if ( get_option( 'backwpup_settings_migration_done' ) ) {
			return;
		}

		$option_keys = [
			'backwpup_archiveformat',
			'backwpup_version',
			'backwpup_cfg_loglevel',
			'backwpup_cfg_logfolder',
			'backwpup_previous_version',
			'backwpup_cfg_hash',
			'backwpup_activation_time',
			'backwpup_cfg_showadminbar',
			'backwpup_cfg_showfoldersize',
			'backwpup_cfg_protectfolders',
			'backwpup_cfg_keepplugindata',
			'backwpup_cfg_jobmaxexecutiontime',
			'backwpup_cfg_jobstepretry',
			'backwpup_cfg_jobrunauthkey',
			'backwpup_cfg_jobwaittimems',
		];

		foreach ( $option_keys as $key ) {
			$value = get_option( $key );
			if ( false !== $value ) {
				$this->options->set( $key, $value );
			}
		}

		// Set to true to avoid running multiple times.
		update_option( 'backwpup_settings_migration_done', 1 );
	}
}
