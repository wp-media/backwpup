<?php

/**
 * Wraps directory functions in PHP.
 *
 * @since 3.4.0
 */
class BackWPup_Directory extends DirectoryIterator {


	/**
	 * The folders list of the plugins to auto exclude
	 *
	 * @var array
	 */
	private static $auto_exclusion_plugins_folders = [];

	/**
	 * The cache folder list of the plugins to auto exclude
	 *
	 * @var array
	 */
	private static $auto_exclusion_plugin_cache_folders = [];

	/**
	 * Creates the iterator.
	 *
	 * Fixes the path before calling the parent constructor.
	 *
	 * @param string $path
	 */
	public function __construct($path)
	{
		parent::__construct(BackWPup_Path_Fixer::fix_path($path));
	}

	/**
	 * Override the current function to avoid the backup of auto exclude plugins listed in self::$_auto_exclusion_plugins
	 *
	 * @return object
	 */
	public function current(): object {
		$item = parent::current();
		if ( ! $item->isDot() && $item->isDir() && in_array( trailingslashit( $item->getPathname() ), self::get_auto_exclusion_plugin_cache_folders(), true ) ) {
			$this->next();
			return $this->current();
		}
		return $item;
	}

	/**
	 * Get the folders of the excluded plugins
	 *
	 * @return array
	 */
	public static function get_auto_exclusion_plugins_folders(): array {
		if ( 0 === count( self::$auto_exclusion_plugins_folders ) ) {
			self::init_auto_exclusion_folders();

		}
		return self::$auto_exclusion_plugins_folders;
	}

	/**
	 * Get the cache folders of the excluded plugins
	 *
	 * @return array
	 */
	public static function get_auto_exclusion_plugin_cache_folders(): array {
		if ( 0 === count( self::$auto_exclusion_plugin_cache_folders ) ) {
			self::init_auto_exclusion_folders();
		}
		return self::$auto_exclusion_plugin_cache_folders;
	}

	/**
	 * Init the excluded folders
	 *
	 * @return void
	 */
	private static function init_auto_exclusion_folders() {
		/**
		 * Filter whether BackWPup will list the plugins in the excluded plugins list.
		 *
		 * @param array $excluded_folders List of excluded paths.
		 */
		$auto_exclusion_plugins_folders = apply_filters( 'backwpup_exclusion_plugins_folders', [] );
		/**
		 * Filter whether BackWPup will list the cache folders to include in the backup.
		 *
		 * @param array $excluded_folders List of excluded paths.
		 */
		$auto_exclusion_plugins_cache_folders = apply_filters( 'backwpup_exclusion_plugins_cache_folders', [] );
		$auto_exclusion_plugins_folders       = ( ! is_array( $auto_exclusion_plugins_folders ) ? [] : $auto_exclusion_plugins_folders );
		$auto_exclusion_plugins_cache_folders = ( ! is_array( $auto_exclusion_plugins_cache_folders ) ? [] : $auto_exclusion_plugins_cache_folders );

		self::$auto_exclusion_plugins_folders      = array_unique( array_map( 'trailingslashit', $auto_exclusion_plugins_folders ) );
		self::$auto_exclusion_plugin_cache_folders = array_unique( array_map( 'trailingslashit', $auto_exclusion_plugins_cache_folders ) );
	}
}
