<?php

namespace BackWPup\ThirdParty;

class Breeze implements ThirdPartyInterface {
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
			defined( 'BREEZE_PLUGIN_DIR' ) &&
			! in_array( BREEZE_PLUGIN_DIR, $excluded_folders, true )
		) {
			$excluded_folders[] = BREEZE_PLUGIN_DIR;
		}
		if (
			defined( 'WP_CONTENT_DIR' ) &&
			! in_array( WP_CONTENT_DIR . '/breeze-config', $excluded_folders, true )
		) {
			$excluded_folders[] = WP_CONTENT_DIR . '/breeze-config';
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
			defined( 'BREEZE_MINIFICATION_CACHE' ) &&
			! in_array( BREEZE_MINIFICATION_CACHE, $excluded_cache_folders, true )
		) {
			$excluded_cache_folders[] = BREEZE_MINIFICATION_CACHE;
		}
		if (
			defined( 'BREEZE_MINIFICATION_EXTRA' ) &&
			! in_array( BREEZE_MINIFICATION_EXTRA, $excluded_cache_folders, true )
		) {
			$excluded_cache_folders[] = BREEZE_MINIFICATION_EXTRA;
		}
		return $excluded_cache_folders;
	}

	/**
	 * {@inheritdoc}.
	 *
	 * @return bool
	 */
	public static function is_active(): bool {
		return defined( 'BREEZE_VERSION' );
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
