<?php

namespace BackWPup\ThirdParty;

class WPOptimize implements ThirdPartyInterface {

	/**
	 * {@inheritDoc}
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
			defined( 'WPO_CACHE_FILES_DIR' ) &&
			! in_array( WPO_CACHE_FILES_DIR, $excluded_folders, true )
		) {
			$excluded_folders[] = WPO_CACHE_FILES_DIR;
		}
		if (
			defined( 'WPO_PLUGIN_MAIN_PATH' ) &&
			! in_array( WPO_PLUGIN_MAIN_PATH, $excluded_folders, true )
		) {
			$excluded_folders[] = WPO_PLUGIN_MAIN_PATH;
		}
		return $excluded_folders;
	}

	/**
	 * {@inheritDoc}
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
			defined( 'WPO_CACHE_FILES_DIR' ) &&
			! in_array( WPO_CACHE_FILES_DIR, $excluded_cache_folders, true )
		) {
			$excluded_cache_folders[] = WPO_CACHE_FILES_DIR;
		}
		if (
			defined( 'WPO_CACHE_MIN_FILES_DIR' ) &&
			! in_array( WPO_CACHE_MIN_FILES_DIR, $excluded_cache_folders, true )
		) {
			$excluded_cache_folders[] = WPO_CACHE_MIN_FILES_DIR;
		}
		return $excluded_cache_folders;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return bool
	 */
	public static function is_active(): bool {
		return defined( 'WPO_VERSION' );
	}

	/**
	 * {@inheritDoc}
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
