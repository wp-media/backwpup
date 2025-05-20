<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Adapters;

class BackWPupAdapter {
	/**
	 * Get information about the Plugin.
	 *
	 * @param string $name Name of info to get or NULL to get all.
	 *
	 * @return string|array
	 */
	public function get_plugin_data( ?string $name = null ) {
		return \BackWPup::get_plugin_data( $name );
	}

	/**
	 * Get a array of registered Destination's for Backups.
	 *
	 * @return array BackWPup_Destinations
	 */
	public static function get_registered_destinations(): array {
		return \BackWPup::get_registered_destinations();
	}

	/**
	 * Get a array of instances for Backup Destination's.
	 *
	 * @param string $key Key of Destination where get class instance from.
	 *
	 * @return array|object BackWPup_Destinations
	 */
	public function get_destination( string $key ) {
		return \BackWPup::get_destination( $key );
	}

	/**
	 * Retrieves the list of job types supported by BackWPup.
	 *
	 * @return array An array of job types.
	 */
	public function get_job_types(): array {
		return \BackWPup::get_job_types();
	}

	/**
	 * Determines if the current instance is using the Pro version of BackWPup.
	 *
	 * @return bool True if the Pro version is active, false otherwise.
	 */
	public function is_pro(): bool {
		return \BackWPup::is_pro();
	}
}
