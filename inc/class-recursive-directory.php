<?php

/**
 * Wraps RecursiveDirectoryIterator to fix paths.
 *
 * @since 3.4.0
 */
class BackWPup_Recursive_Directory extends RecursiveDirectoryIterator {

	/**
	 * Creates the iterator.
	 *
	 * Fixes the path before calling the parent constructor.
	 *
	 * @param string $path
	 */
	public function __construct( $path, $flags = null ) {
		if ( $flags === null ) {
			$flags = FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::CURRENT_AS_FILEINFO;
		}
		parent::__construct( BackWPup_Path_Fixer::fix_path( $path ), $flags );
	}

}
