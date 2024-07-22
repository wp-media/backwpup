<?php

namespace BackWPup\ThirdParty;

class W3TotalCache implements ThirdPartyInterface {

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
			defined( 'W3TC_DIR' ) &&
			! in_array( W3TC_DIR, $excluded_folders, true )
		) {
			$excluded_folders[] = W3TC_DIR;
		}
		if (
			defined( 'W3TC_CONFIG_DIR' ) &&
			! in_array( W3TC_CONFIG_DIR, $excluded_folders, true )
		) {
			$excluded_folders[] = W3TC_CONFIG_DIR;
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
			defined( 'W3TC_CACHE_DIR' ) &&
			! in_array( W3TC_CACHE_DIR, $excluded_cache_folders, true )
		) {
			$excluded_cache_folders[] = W3TC_CACHE_DIR;
		}
		return $excluded_cache_folders;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return bool
	 */
	public static function is_active(): bool {
		return defined( 'W3TC' );
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
