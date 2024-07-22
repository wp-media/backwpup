<?php

namespace BackWPup\ThirdParty;

class WPFastestCache implements ThirdPartyInterface {

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
			defined( 'WPFC_MAIN_PATH' ) &&
			! in_array( trailingslashit( WPFC_MAIN_PATH ), $excluded_folders, true )
		) {
			$excluded_folders[] = trailingslashit( WPFC_MAIN_PATH );
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
		$wpfc_cache_path = get_option( 'WpFastestCachePathSettings' );
		if ( ! is_array( $wpfc_cache_path ) ) {
			$wpfc_cache_path = [
				'cachepath' => 'cache',
			];
		}
		if (
			defined( 'WPFC_WP_CONTENT_DIR' ) &&
			! in_array( trailingslashit( WPFC_WP_CONTENT_DIR . '/' . $wpfc_cache_path['cachepath'] ), $excluded_cache_folders, true )
		) {
			$excluded_cache_folders[] = trailingslashit( WPFC_WP_CONTENT_DIR . '/' . $wpfc_cache_path['cachepath'] );
		}
		return $excluded_cache_folders;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return bool
	 */
	public static function is_active(): bool {
		return defined( 'WPFC_WP_CONTENT_BASENAME' );
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
