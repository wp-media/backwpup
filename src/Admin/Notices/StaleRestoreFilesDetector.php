<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Admin\Notices;

/**
 * Detects whether stale restore working-directory files are present.
 *
 * Resolves the restore base directory the same way commons.php does, then
 * checks for the presence of non-empty uploads/ or extract/ subdirectories,
 * or the restore.dat.bkp credential file.
 */
class StaleRestoreFilesDetector {

	/**
	 * Returns the absolute path to the restore base directory.
	 *
	 * @return string
	 */
	public function base_dir(): string {
		$upload_dir = wp_upload_dir( null, true, false );
		return untrailingslashit(
			\BackWPup_File::get_absolute_path( $upload_dir['basedir'] )
		) . '/backwpup-restore';
	}

	/**
	 * Returns true when stale restore files are detected.
	 *
	 * Returns true if:
	 *   - uploads/ OR extract/ exists AND has at least one non-dot child entry, OR
	 *   - restore.dat.bkp exists (contains plaintext DB credentials).
	 *
	 * Returns false if the base directory does not exist.
	 *
	 * @return bool
	 */
	public function has_files(): bool {
		$base_dir = $this->base_dir();

		if ( ! is_dir( $base_dir ) ) {
			return false;
		}

		// Check for plaintext credential file.
		if ( file_exists( $base_dir . '/restore.dat.bkp' ) ) {
			return true;
		}

		// Check uploads/ and extract/ for at least one non-dot child entry.
		foreach ( [ 'uploads', 'extract' ] as $subdir ) {
			$path = $base_dir . '/' . $subdir;
			if ( ! is_dir( $path ) ) {
				continue;
			}

			try {
				$iterator = new \FilesystemIterator( $path, \FilesystemIterator::SKIP_DOTS );
				if ( $iterator->valid() ) {
					return true;
				}
			} catch ( \UnexpectedValueException $e ) {
				// Directory not readable — treat as no files.
				continue;
			}
		}

		return false;
	}
}
