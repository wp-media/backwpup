<?php

namespace BackWPup\ThirdParty;

class WPRocket implements ThirdPartyInterface {

	/**
	 * {@inheritdoc}
	 *
	 * @param array $excluded_folders
	 *
	 * @return mixed
	 */
	public function exclude_folders( $excluded_folders ) {
		if (
			! is_array( $excluded_folders ) ||
			! self::is_active()
		) {
			return $excluded_folders;
		}
		if (
			defined( 'WP_ROCKET_PATH' ) &&
			! in_array( WP_ROCKET_PATH, $excluded_folders, true )
		) {
			$excluded_folders[] = WP_ROCKET_PATH;
		}
		if (
			defined( 'WP_ROCKET_CONFIG_PATH' ) &&
			! in_array( WP_ROCKET_CONFIG_PATH, $excluded_folders, true )
		) {
			$excluded_folders[] = WP_ROCKET_CONFIG_PATH;
		}
		return $excluded_folders;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param array $excluded_cache_folders
	 *
	 * @return mixed
	 */
	public function exclude_cache_folders( $excluded_cache_folders ) {
		if (
			! is_array( $excluded_cache_folders ) ||
			! self::is_active()
		) {
			return $excluded_cache_folders;
		}
		if (
			defined( 'WP_ROCKET_CACHE_ROOT_PATH' ) &&
			! in_array( WP_ROCKET_CACHE_ROOT_PATH, $excluded_cache_folders, true )
		) {
			$excluded_cache_folders[] = WP_ROCKET_CACHE_ROOT_PATH;
		}
		return $excluded_cache_folders;
	}

	/**
	 * {@inheritdoc}.
	 *
	 * @return bool
	 */
	public static function is_active(): bool {
		return defined( 'WP_ROCKET_VERSION' );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return void
	 */
	public function init(): void {
		if ( self::is_active() ) {
			add_filter( 'backwpup_exclusion_plugins_folders', [ $this, 'exclude_folders' ] );
			add_filter( 'backwpup_exclusion_plugins_cache_folders', [ $this, 'exclude_cache_folders' ] );
		}
	}
}
