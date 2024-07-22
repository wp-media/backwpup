<?php

namespace BackWPup\ThirdParty;

class Autoptimize implements ThirdPartyInterface {

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
			defined( 'AUTOPTIMIZE_PLUGIN_DIR' ) &&
			! in_array( AUTOPTIMIZE_PLUGIN_DIR, $excluded_folders, true )
		) {
			$excluded_folders[] = AUTOPTIMIZE_PLUGIN_DIR;
		}
		$excluded_folders[] = AUTOPTIMIZE_PLUGIN_DIR;
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
			defined( 'AUTOPTIMIZE_CACHE_DIR' ) &&
			! in_array( AUTOPTIMIZE_CACHE_DIR, $excluded_cache_folders, true )
		) {
			$excluded_cache_folders[] = AUTOPTIMIZE_CACHE_DIR;
		}
		return $excluded_cache_folders;
	}

	/**
	 * {@inheritdoc}.
	 *
	 * @return bool
	 */
	public static function is_active(): bool {
		return defined( 'AUTOPTIMIZE_PLUGIN_VERSION' );
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
