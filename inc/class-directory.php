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
	 * Flag to check if auto exclusion folders are initialized
	 *
	 * @var bool
	 */
	private static $auto_exclusion_initialized = false;

	/**
	 * Creates the iterator.
	 *
	 * Fixes the path before calling the parent constructor.
	 *
	 * @param string $path
	 */
	public function __construct( $path ) {
		parent::__construct( BackWPup_Path_Fixer::fix_path( $path ) );
	}

	/**
	 * Override next to skip auto excluded folders.
	 *
	 * @return void
	 */
	public function next(): void {
		parent::next();
		while ( $this->valid() && $this->should_skip() ) {
			parent::next();
		}
	}

	/**
	 * Override rewind to skip auto excluded folders.
	 *
	 * @return void
	 */
	public function rewind(): void {
		parent::rewind();
		while ( $this->valid() && $this->should_skip() ) {
			parent::next();
		}
	}

	/**
	 * Check if the current item should be skipped.
	 *
	 * @return bool
	 */
	private function should_skip(): bool {
		$item = $this->current();

		if ( $item->isDot() || ! $item->isDir() ) {
			return false;
		}

		$pathname = self::sanitize_path( $item->getPathname() );

		return isset( self::$auto_exclusion_plugin_cache_folders[ $pathname ] );
	}

	/**
	 * Get the folders of the excluded plugins
	 *
	 * @return array
	 */
	public static function get_auto_exclusion_plugins_folders(): array {
		self::init_auto_exclusion_folders();

		return array_keys( self::$auto_exclusion_plugins_folders );
	}

	/**
	 * Get the cache folders of the excluded plugins
	 *
	 * @return array
	 */
	public static function get_auto_exclusion_plugin_cache_folders(): array {
		self::init_auto_exclusion_folders();

		return array_keys( self::$auto_exclusion_plugin_cache_folders );
	}

	/**
	 * Init the excluded folders
	 *
	 * @return void
	 */
	private static function init_auto_exclusion_folders() {
		if ( self::$auto_exclusion_initialized ) {
			return;
		}

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

		foreach ( array_unique( $auto_exclusion_plugins_folders ) as $folder ) {
			self::$auto_exclusion_plugins_folders[ trailingslashit( self::sanitize_path( $folder ) ) ] = true;
		}

		foreach ( array_unique( $auto_exclusion_plugins_cache_folders ) as $folder ) {
			self::$auto_exclusion_plugin_cache_folders[ trailingslashit( self::sanitize_path( $folder ) ) ] = true;
		}

		self::$auto_exclusion_initialized = true;
	}

	/**
	 * Get the list of folders with the exclude option.
	 *
	 * @param string $id_path The id of the path.
	 * @param string $path The path to get the folders to exclude.
	 * @param string $id_job The id of the job.
	 * @param bool   $size Whether to include the size of the folder (performance intensive).
	 *
	 * @return array
	 */
	public static function get_folder_list_to_exclude( $id_path, $path, $id_job, $size = true ) {
		$folder = realpath( BackWPup_Path_Fixer::fix_path( $path ) );

		if ( ! $folder || ! is_dir( $folder ) ) {
			return [];
		}

		$folder = trailingslashit( BackWPup_Path_Fixer::slashify( $folder ) );

		// Prepare variables once.
		$folders_to_exclude = [];
		$excludes           = BackWPup_Option::get( $id_job, 'backup' . $id_path . 'excludedirs' );

		if ( ! is_array( $excludes ) ) {
			$excludes = [];
		}

		$excludes_flip = array_flip( $excludes );

		// Initialize auto-exclusion folders.
		self::init_auto_exclusion_folders();
		$auto_excludes = self::get_exclude_dirs( $folder, self::$auto_exclusion_plugins_folders );

		// use faster opendir() to get the list of folders.
		$dir = opendir( $folder );
		if ( ! $dir ) {
			return [];
		}
		$file = readdir( $dir );
		while ( false !== $file ) {
			if ( '.' === $file || '..' === $file || ! is_dir( $folder . $file ) ) {
				$file = readdir( $dir );
				continue;
			}

			$pathname       = $folder . $file;
			$sanitized_path = self::sanitize_path( $pathname );

			// Skip auto-excluded folders.
			if ( isset( $auto_excludes[ $sanitized_path ] ) ) {
				$file = readdir( $dir );
				continue;
			}

			// Check for .donotbackup.
			if ( is_file( $pathname . '/.donotbackup' ) ) {
				$excludes_flip[ $file ] = true;
			}

			$folders_to_exclude[] = [
				'name'     => $file,
				'path'     => $folder,
				'size'     => $size ? BackWPup_File::get_folder_size( $folder ) : '',
				'excluded' => isset( $excludes_flip[ $file ] ),
			];
			$file                 = readdir( $dir );
		}
		closedir( $dir );

		// sort files alphabetically.
		usort(
			$folders_to_exclude,
			static function ( $a, $b ) {
				return strcmp( $a['name'], $b['name'] );
			}
		);

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

		$dirs_to_check = [
			WP_CONTENT_DIR,
			WP_PLUGIN_DIR,
			get_theme_root(),
			BackWPup_File::get_upload_dir(),
		];

		foreach ( $dirs_to_check as $dir ) {
			$sanitized_dir = self::sanitize_path( $dir );
			if ( false !== strpos( $sanitized_dir, $folder ) && $sanitized_dir !== $folder ) {
				$excludedir[ $sanitized_dir ] = true;
			}
		}

		return $excludedir;
	}

	/**
	 * Sanitize a path.
	 *
	 * @param string $path The path to sanitize.
	 *
	 * @return string
	 */
	private static function sanitize_path( $path ) {
		return trailingslashit(
			BackWPup_Path_Fixer::slashify( $path )
		);
	}
}
