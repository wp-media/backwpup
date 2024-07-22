<?php

namespace BackWPup\ThirdParty;

class WPSuperCache implements ThirdPartyInterface {

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
			defined( 'WPCACHEHOME' ) &&
			! in_array( WPCACHEHOME, $excluded_folders, true )
		) {
			$excluded_folders[] = WPCACHEHOME;
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
		global $cache_path;
		if ( ! in_array( $cache_path, $excluded_cache_folders, true ) ) {
			$excluded_cache_folders[] = $cache_path;
		}
		return $excluded_cache_folders;
	}

	/**
	 * {@inheritdoc}.
	 *
	 * @return bool
	 */
	public static function is_active(): bool {
		return defined( 'WPSC_VERSION' );
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
