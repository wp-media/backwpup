<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Admin\Notices;

use function Inpsyde\BackWPup\Infrastructure\Restore\restore_dir_candidates;

/**
 * Detects whether stale restore working-directory files are present.
 *
 * Checks every restore working directory commons.php knows about (current,
 * legacy and orphaned ones) for non-empty uploads/ or extract/ subdirectories,
 * or the restore.dat.bkp credential file.
 */
class StaleRestoreFilesDetector {

	/**
	 * Returns the absolute paths of the restore working directories to check.
	 *
	 * Same list as the abandoned-restore sweep and the manual delete action, so the
	 * notice never hides leftovers those two would act on (e.g. legacy residue
	 * predating the tokenized directory name).
	 *
	 * @return string[]
	 */
	public function base_dirs(): array {
		return restore_dir_candidates();
	}

	/**
	 * Returns true when stale restore files are detected in any restore working directory.
	 *
	 * @return bool
	 */
	public function has_files(): bool {
		foreach ( $this->base_dirs() as $base_dir ) {
			if ( $this->dir_has_files( $base_dir ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns true when stale restore files are detected in one working directory.
	 *
	 * Returns true if:
	 *   - uploads/ OR extract/ exists AND has at least one non-dot child entry, OR
	 *   - restore.dat.bkp exists (contains plaintext DB credentials).
	 *
	 * Returns false if the base directory does not exist.
	 *
	 * @param string $base_dir Absolute path to a restore working directory.
	 *
	 * @return bool
	 */
	private function dir_has_files( string $base_dir ): bool {
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
