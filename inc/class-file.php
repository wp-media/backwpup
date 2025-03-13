<?php
/**
 * Class for methods for file/folder related things.
 *
 * @todo Please split this logic into two separated classes. One for File and another for dir.
 */
class BackWPup_File
{
    /**
     * Get the folder for blog uploads.
     *
     * @return string
     */
    public static function get_upload_dir()
    {
        if (is_multisite()) {
            if (defined('UPLOADBLOGSDIR')) {
                return trailingslashit(str_replace('\\', '/', ABSPATH . UPLOADBLOGSDIR));
            }
            if (is_dir(trailingslashit(WP_CONTENT_DIR) . 'uploads/sites')) {
                return str_replace('\\', '/', trailingslashit(WP_CONTENT_DIR) . 'uploads/sites/');
            }
            if (is_dir(trailingslashit(WP_CONTENT_DIR) . 'uploads')) {
                return str_replace('\\', '/', trailingslashit(WP_CONTENT_DIR) . 'uploads/');
            }

            return trailingslashit(str_replace('\\', '/', (string) WP_CONTENT_DIR));
        }
        $upload_dir = wp_upload_dir(null, false, true);

        return trailingslashit(str_replace('\\', '/', $upload_dir['basedir']));
    }

    /**
     * check if path in open basedir.
     *
     * @param string $file the file path to check
     *
     * @return bool is it in open basedir
     */
    public static function is_in_open_basedir($file)
    {
        $ini_open_basedir = ini_get('open_basedir');

        if (empty($ini_open_basedir)) {
            return true;
        }

        $open_base_dirs = explode(PATH_SEPARATOR, $ini_open_basedir);
        $file = trailingslashit(strtolower(BackWPup_Path_Fixer::slashify($file)));

        foreach ($open_base_dirs as $open_base_dir) {
            if (empty($open_base_dir) || !realpath($open_base_dir)) {
                continue;
            }

            $open_base_dir = realpath($open_base_dir);
            $open_base_dir = strtolower(BackWPup_Path_Fixer::slashify($open_base_dir));
            $part = substr($file, 0, strlen($open_base_dir));
            if ($part === $open_base_dir) {
                return true;
            }
        }

        return false;
    }

	/**
	 * Get size of files in folder if enabled
	 *
	 * @param string $folder the folder to calculate.
	 *
	 * @return string folder size formated in human readable format
	 */
	public static function get_folder_size( $folder ) {

		/**
		 * Filter whether BackWPup will show the folder size.
		 *
		 * @param bool $show_folder_size whether BackWPup will show the folder size or not.
		 */
		$show_folder_size = wpm_apply_filters_typed( 'boolean', 'backwpup_show_folder_size', (bool) get_site_option( 'backwpup_cfg_showfoldersize' ) );

		if ( ! $show_folder_size ) {
			return '';
		}

		$files_size = 0;

		if ( ! is_readable( $folder ) ) {
			return self::format_size( $files_size );
		}

        $iterator = new RecursiveIteratorIterator(new BackWPup_Recursive_Directory($folder, FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if (!$file->isLink()) {
                $files_size += $file->getSize();
            }
        }

		return self::format_size( $files_size );
	}

	/**
	 * Format size in human readable format.
	 *
	 * @param int $size
	 *
	 * @return string
	 */
	protected static function format_size( $size ): string {
		return ' (' . size_format( $size, 2 ) . ')';
	}

    /**
     * Get an absolute path if it is relative.
     *
     * @param string $path
     *
     * @return string
     */
    public static function get_absolute_path($path = '/')
    {
        $path = BackWPup_Path_Fixer::slashify($path);
        $content_path = trailingslashit(BackWPup_Path_Fixer::slashify((string) WP_CONTENT_DIR));

        //use WP_CONTENT_DIR as root folder
        if (empty($path) || $path === '/') {
            $path = $content_path;
        }

        //make relative path to absolute
        if (substr($path, 0, 1) !== '/' && !preg_match('#^[a-zA-Z]+:/#', $path)) {
            $path = $content_path . $path;
        }

        return self::resolve_path($path);
    }

    /**
     * Check is folder readable and exists create it if not
     * add .htaccess or index.html file in folder to prevent directory listing.
     *
     * @param string $folder      the folder to check
     * @param bool   $donotbackup Create a file that the folder will not backuped
     *
     * @return string with error message if one
     */
    public static function check_folder(string $folder, bool $donotbackup = false): string
    {
        $folder = self::get_absolute_path($folder);
        $folder = untrailingslashit($folder);

        //check that is not home of WP
        $uploads = self::get_upload_dir();
        if ($folder === untrailingslashit(BackWPup_Path_Fixer::slashify(ABSPATH))
            || $folder === untrailingslashit(BackWPup_Path_Fixer::slashify(dirname(ABSPATH)))
            || $folder === untrailingslashit(BackWPup_Path_Fixer::slashify(WP_PLUGIN_DIR))
            || $folder === untrailingslashit(BackWPup_Path_Fixer::slashify(WP_CONTENT_DIR))
            || $folder === untrailingslashit(BackWPup_Path_Fixer::slashify($uploads))
        ) {
            return sprintf(__('Folder %1$s not allowed, please use another folder.', 'backwpup'), $folder);
        }

        //open base dir check
        if (!self::is_in_open_basedir($folder)) {
            return sprintf(__('Folder %1$s is not in open basedir, please use another folder.', 'backwpup'), $folder);
        }

        // We always want to at least process `$folder`
        $foldersToProcess = [$folder];
        $parentFolder = dirname($folder);

        while (!file_exists($parentFolder)) {
            array_unshift($foldersToProcess, $parentFolder);
            $parentFolder = dirname($parentFolder);
        }

        // Process each child folder separately
        foreach ($foldersToProcess as $childFolder) {
            if (!is_dir($childFolder) && !wp_mkdir_p($childFolder)) {
                return sprintf(__('Cannot create folder: %1$s', 'backwpup'), $childFolder);
            }

            if (!is_writable($childFolder)) {
                return sprintf(__('Folder "%1$s" is not writable', 'backwpup'), $childFolder);
            }

			// create files for securing folder.
			/**
			 * Filter whether BackWPup will protect the folders.
			 *
			 * @param bool $protect_folders Whether the folder will be protect or not.
			 */
			$protect_folders = wpm_apply_filters_typed( 'boolean', 'backwpup_protect_folders', (bool) get_site_option( 'backwpup_cfg_protectfolders' ) );
			if ( $protect_folders ) {
				self::protect_folder( $childFolder ); // phpcs:ignore
			}

            //Create do not backup file for this folder
            if ($donotbackup) {
                self::write_do_not_backup_file($childFolder);
            }
        }

        return '';
    }

    /**
     * @throws InvalidArgumentException If path is absolute or attempts to navigate above root
     */
    public static function normalize_path(string $path): string
    {
        if (strpos($path, '/') === 0) {
            throw new InvalidArgumentException('Absolute paths are not allowed.');
        }

        $parts = explode('/', $path);
        $normalized = [];

        foreach ($parts as $part) {
            if ($part === '..') {
                if (empty($normalized)) {
                    throw new InvalidArgumentException(
                        'Invalid path: Attempting to navigate above the root directory.'
                    );
                }
                array_pop($normalized);
            } elseif ($part !== '.' && $part !== '') {
                $normalized[] = $part;
            }
        }

        if (empty($normalized)) {
            throw new InvalidArgumentException('The path resolves to an empty path.');
        }

        return implode('/', $normalized);
    }

    /**
     * Resolve internal .. within a path.
     *
     * @param string $path The path to resolve
     *
     * @return string The resolved path
     */
    protected static function resolve_path($path): string
    {
        $parts = explode('/', $path);
        $resolvedParts = [];

        foreach ($parts as $part) {
            if ($part === '..') {
                if (!empty($resolvedParts)) {
                    array_pop($resolvedParts);
                }
            } elseif ($part === '.') {
                continue;
            } else {
                $resolvedParts[] = $part;
            }
        }

        return implode('/', $resolvedParts);
    }

    private static function protect_folder(string $folder): void
    {
        $server_software = strtolower((string) $_SERVER['SERVER_SOFTWARE']);

        if (strstr($server_software, 'microsoft-iis')) {
            if (!file_exists($folder . '/Web.config')) {
                file_put_contents(
                    $folder . '/Web.config',
                    '<configuration>' . PHP_EOL .
                    "\t<system.webServer>" . PHP_EOL .
                    "\t\t<authorization>" . PHP_EOL .
                    "\t\t\t<deny users=\"*\" />" . PHP_EOL .
                    "\t\t</authorization>" . PHP_EOL .
                    "\t</system.webServer>" . PHP_EOL .
                    '</configuration>'
                );
            }
        } elseif (strstr($server_software, 'nginx')) {
            if (!file_exists($folder . '/index.php')) {
                file_put_contents(
                    $folder . '/index.php',
                    '<?php' . PHP_EOL . "header( \$_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );" . PHP_EOL . "header( 'Status: 404 Not Found' );" . PHP_EOL
                );
            }
        } else {
            if (!file_exists($folder . '/.htaccess')) {
                file_put_contents(
                    $folder . '/.htaccess',
                    '<Files "*">' . PHP_EOL . '<IfModule mod_access.c>' . PHP_EOL . 'Deny from all' . PHP_EOL . '</IfModule>' . PHP_EOL . '<IfModule !mod_access_compat>' . PHP_EOL . '<IfModule mod_authz_host.c>' . PHP_EOL . 'Deny from all' . PHP_EOL . '</IfModule>' . PHP_EOL . '</IfModule>' . PHP_EOL . '<IfModule mod_access_compat>' . PHP_EOL . 'Deny from all' . PHP_EOL . '</IfModule>' . PHP_EOL . '</Files>'
                );
            }
            if (!file_exists($folder . '/index.php')) {
                file_put_contents(
                    $folder . '/index.php',
                    '<?php' . PHP_EOL . "header( \$_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );" . PHP_EOL . "header( 'Status: 404 Not Found' );" . PHP_EOL
                );
            }
        }
    }

    private static function write_do_not_backup_file(string $folder): void
    {
        $doNotBackupFile = "{$folder}/.donotbackup";

        if (!file_exists($doNotBackupFile)) {
            file_put_contents(
                $doNotBackupFile,
                __(
                    'BackWPup will not backup folders and its sub folders when this file is inside.',
                    'backwpup'
                )
            );
        }
    }
}
