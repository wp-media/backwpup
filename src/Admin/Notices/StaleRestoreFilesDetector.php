<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Admin\Notices;

use function Inpsyde\BackWPup\Infrastructure\Restore\restore_dir_candidates;

/**
 * Detects whether stale restore working-directory files are present.
 *
 * Checks every restore working directory commons.php knows about (current,
 * legacy and orphaned ones) for a non-empty extract/ subdirectory, an uploads/
 * subdirectory holding any entry other than BackWPup's own folder-protection files,
 * or the restore.dat.bkp credential file.
 */
class StaleRestoreFilesDetector {

	/**
	 * Names of the protection files skipped in uploads/.
	 *
	 * @var string[]
	 */
	private array $protection_file_names;

	/**
	 * Constructor.
	 *
	 * @param string[]|null $protection_file_names Names of the protection files skipped in
	 *                                             uploads/. Defaults to the files
	 *                                             BackWPup_File::check_folder() writes.
	 *                                             Injectable for unit tests only; production
	 *                                             code always relies on the default.
	 */
	public function __construct( ?array $protection_file_names = null ) {
		$this->protection_file_names = $protection_file_names ?? \BackWPup_File::protection_file_names();
	}

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
	 *   - extract/ exists AND has at least one non-dot child entry, OR
	 *   - uploads/ exists AND has at least one child entry that is not a protection
	 *     file (see is_protection_file()), OR
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

		// Subfolder => names of the protection files expected (and skipped) there. Only
		// uploads/ is protected via BackWPup_File::check_folder()
		// (ensure_uploads_folder_protected()); nothing protects extract/.
		$ignored_names = [
			'uploads' => $this->protection_file_names,
			'extract' => [],
		];

		foreach ( $ignored_names as $subdir => $ignored ) {
			$path = $base_dir . '/' . $subdir;
			if ( ! is_dir( $path ) ) {
				continue;
			}

			try {
				$iterator = new \FilesystemIterator( $path, \FilesystemIterator::SKIP_DOTS );
				foreach ( $iterator as $entry ) {
					if ( $this->is_protection_file( $entry, $ignored ) ) {
						continue;
					}

					return true;
				}
			} catch ( \UnexpectedValueException $e ) {
				// Directory not readable — treat as no files.
				continue;
			}
		}

		return false;
	}

	/**
	 * Tells whether an entry is one of BackWPup's own protection files.
	 *
	 * The restore boot recreates them on every admin-ajax request (Heartbeat included),
	 * so they are scaffolding, not leftovers. Names come from BackWPup_File, the class
	 * that writes them (see the constructor).
	 *
	 * Only the exact entries BackWPup_File writes are skipped: regular files, matched
	 * case-sensitively, never symlinks or directories. Everything else in uploads/ is
	 * what the notice exists for: a symlink named `.htaccess` can point at a backup
	 * archive or wp-config.php outside the folder, and a directory named `index.php`
	 * (or an `INDEX.PHP` on a case-insensitive filesystem) can hold files. Reporting
	 * them keeps the "delete restore files" notice from hiding real content.
	 *
	 * @param \SplFileInfo $entry Directory entry.
	 * @param string[]     $names Protection file names expected in the entry's folder.
	 *
	 * @return bool
	 */
	private function is_protection_file( \SplFileInfo $entry, array $names ): bool {
		return ! $entry->isLink()
			&& $entry->isFile()
			&& in_array( $entry->getFilename(), $names, true );
	}
}
