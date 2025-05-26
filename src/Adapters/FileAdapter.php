<?php

namespace WPMedia\BackWPup\Adapters;

class FileAdapter {

	/**
	 * Check is folder readable and exists create it if not
	 * add .htaccess or index.html file in folder to prevent directory listing.
	 *
	 * @param string $folder      the folder to check.
	 * @param bool   $donotbackup Create a file that the folder will not backuped.
	 *
	 * @return string with error message if one
	 */
	public function check_folder( string $folder, bool $donotbackup = false ): string {
		return \BackWPup_File::check_folder( $folder, $donotbackup );
	}

	/**
	 * Get an absolute path if it is relative.
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	public function get_absolute_path( $path = '/' ) {
		return \BackWPup_File::get_absolute_path( $path );
	}
}
