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
		$auto_exclusion_plugins_folders = wpm_apply_filters_typed(
			'array',
			'backwpup_exclusion_plugins_folders',
			[]
		);
		/**
		 * Filter whether BackWPup will list the cache folders to include in the backup.
		 *
		 * @param array $excluded_folders List of excluded paths.
		 */
		$auto_exclusion_plugins_cache_folders = wpm_apply_filters_typed(
			'array',
			'backwpup_exclusion_plugins_cache_folders',
			[]
		);
		$auto_exclusion_plugins_folders       = ( ! is_array( $auto_exclusion_plugins_folders ) ? [] : $auto_exclusion_plugins_folders );
		$auto_exclusion_plugins_cache_folders = ( ! is_array( $auto_exclusion_plugins_cache_folders ) ? [] : $auto_exclusion_plugins_cache_folders );

		self::$auto_exclusion_plugins_folders      = array_unique( array_map( 'trailingslashit', $auto_exclusion_plugins_folders ) );
		self::$auto_exclusion_plugin_cache_folders = array_unique( array_map( 'trailingslashit', $auto_exclusion_plugins_cache_folders ) );
	}

	/**
	 * Get the list of folders with the exclude option.
	 *
	 * @param string $id_path The id of the path.
	 * @param string $path The path to get the folders to exclude.
	 * @param string $id_job The id of the job.
	 *
	 * @return array
	 */
	public static function get_folder_list_to_exclude( $id_path, $path, $id_job ) {
		$folder = realpath( BackWPup_Path_Fixer::fix_path( $path ) );

		if ( ! $folder ) {
			return [];
		}

		$folder = untrailingslashit( str_replace( '\\', '/', $folder ) );

		// Prepare variables once.
		$folders_to_exclude = [];
		$excludes           = BackWPup_Option::get( $id_job, 'backup' . $id_path . 'excludedirs' );

		if ( ! is_array( $excludes ) ) {
			$excludes = [];
		}

		$dir           = new BackWPup_Directory( $folder );
		$auto_excludes = array_map( 'trailingslashit', self::get_exclude_dirs( $folder, $dir::get_auto_exclusion_plugins_folders() ) );

		try {
			foreach ( $dir as $file ) {
				if ( $file->isDot() || ! $file->isDir() ) {
					continue;
				}

				$pathname = $file->getPathname();

				// Skip auto-excluded folders.
				if ( in_array( trailingslashit( $pathname ), $auto_excludes, true ) ) {
					continue;
				}

				$filename = $file->getFilename();

				// Check for .donotbackup.
				if ( file_exists( $pathname . '/.donotbackup' ) ) {
					$excludes[] = $pathname;
				}

				$folders_to_exclude[] = [
					'name'     => $filename,
					'path'     => $pathname,
					'size'     => BackWPup_File::get_folder_size( $pathname ),
					'excluded' => in_array( $filename, $excludes, true ),
				];
			}
		} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Do nothing.
		}

		return $folders_to_exclude;
	}

	/**
	 * Get folder to exclude from a given folder for file backups.
	 *
	 * @param string $folder Folder to check for excludes.
	 * @param array  $excludedir
	 *
	 * @return array of folder to exclude
	 */
	private static function get_exclude_dirs( $folder, $excludedir = [] ) {
		$folder = self::sanitize_path( BackWPup_Path_Fixer::fix_path( $folder ) );

		if ( false !== strpos( self::sanitize_path( WP_CONTENT_DIR ), $folder ) && self::sanitize_path( WP_CONTENT_DIR ) !== $folder ) {
			$excludedir[] = self::sanitize_path( WP_CONTENT_DIR );
		}
		if ( false !== strpos( self::sanitize_path( WP_PLUGIN_DIR ), $folder ) && self::sanitize_path( WP_PLUGIN_DIR ) !== $folder ) {
			$excludedir[] = self::sanitize_path( WP_PLUGIN_DIR );
		}
		if ( false !== strpos( self::sanitize_path( get_theme_root() ), $folder ) && self::sanitize_path( get_theme_root() ) !== $folder ) {
			$excludedir[] = self::sanitize_path( get_theme_root() );
		}
		if ( false !== strpos( self::sanitize_path( BackWPup_File::get_upload_dir() ), $folder ) && self::sanitize_path( BackWPup_File::get_upload_dir() ) !== $folder ) {
			$excludedir[] = self::sanitize_path( BackWPup_File::get_upload_dir() );
		}

		return array_unique( $excludedir );
	}

	/**
	 * Sanitize a path.
	 *
	 * @param string $path The path to sanitize.
	 *
	 * @return string
	 */
	private static function sanitize_path( $path ) {
		$path = trailingslashit(
			str_replace(
				'\\',
				'/',
				realpath( $path )
			)
		);
		return $path;
	}
}
