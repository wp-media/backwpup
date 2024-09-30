<?php

namespace BackWPup\ThirdParty;

class HummingbirdPerformance implements ThirdPartyInterface {

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
		$humming_bird_filesystem = \Hummingbird\Core\Filesystem::instance();
		if ( ! in_array( $humming_bird_filesystem->basedir, $excluded_folders, true ) ) {
			$excluded_folders[] = $humming_bird_filesystem->basedir;
		}
		if (
			defined( 'WPHB_DIR_PATH' ) &&
			! in_array( WPHB_DIR_PATH, $excluded_folders, true )
		) {
			$excluded_folders[] = WPHB_DIR_PATH;
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
		$humming_bird_filesystem = \Hummingbird\Core\Filesystem::instance();
		if ( ! in_array( $humming_bird_filesystem->cache_dir, $excluded_cache_folders, true ) ) {
			$excluded_cache_folders[] = $humming_bird_filesystem->cache_dir;
		}
		if ( ! in_array( $humming_bird_filesystem->gravatar_dir, $excluded_cache_folders, true ) ) {
			$excluded_cache_folders[] = $humming_bird_filesystem->gravatar_dir;
		}

		return $excluded_cache_folders;
	}

	/**
	 * {@inheritdoc}.
	 *
	 * @return bool
	 */
	public static function is_active(): bool {
		return ( class_exists( 'Hummingbird\\WP_Hummingbird' ) && defined( 'WPHB_VERSION' ) );
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
