<?php
/**
 * Create Archive.
 */

/**
 * Class for creating File Archives.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BackWPup_Create_Archive {

	/**
	 * Achieve file with full path.
	 *
	 * @var string
	 */
	private $file = '';

	/**
	 * Compression method.
	 *
	 * @var string Compression method (ZipArchive, PclZip, Tar, TarGz, gz).
	 */
	private $method = '';

	/**
	 * File handle.
	 *
	 * @var resource|bool|null File handle for archive writing.
	 */
	private $filehandler;

	/**
	 * Handler Type.
	 *
	 * @var string Handler type ('bz', 'gz', or empty string).
	 */
	private $handlertype = '';

	/**
	 * ZipArchive.
	 *
	 * @var ZipArchive
	 */
	private $ziparchive;

	/**
	 * PclZip.
	 *
	 * @var PclZip
	 */
	private $pclzip;

	/**
	 * PclZip File List.
	 *
	 * @var array()
	 */
	private $pclzip_file_list = [];

	/**
	 * File Count.
	 *
	 * File count of added files to handle size logic.
	 *
	 * @var int Number of files added.
	 */
	private $file_count = 0;

	/**
	 * Tar/TarGz duplicate-entry index.
	 *
	 * In-memory reconstructed state of every Tar/TarGz entry written so far in this
	 * archive, keyed by name_in_archive. Rebuilt on construction by replaying the
	 * NDJSON sidecar log when resuming an existing archive.
	 *
	 * @var array
	 */
	private $tar_index = [];

	/**
	 * Tar/TarGz duplicate-entry index sidecar log file path.
	 *
	 * Empty string for all non-Tar/TarGz methods.
	 *
	 * @var string
	 */
	private $tar_index_file = '';

	/**
	 * Tar/TarGz duplicate-entry index sidecar log file handle.
	 *
	 * Append-mode resource kept open for the lifetime of the object, distinct from
	 * $this->filehandler.
	 *
	 * @var resource|null
	 */
	private $tar_index_handle;

	/**
	 * BackWPup_Create_Archive constructor.
	 *
	 * @param string $file File with full path of the archive.
	 *
	 * @throws BackWPup_Create_Archive_Exception If the file is empty or not a valid string.
	 */
	public function __construct( $file ) {
		if ( ! is_string( $file ) || empty( $file ) ) {
			throw new BackWPup_Create_Archive_Exception(
				esc_html__( 'The file name of an archive cannot be empty.', 'backwpup' )
			);
		}

		// Check folder can be used.
		if ( ! is_dir( dirname( $file ) ) || ! is_writable( dirname( $file ) ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable
			throw new BackWPup_Create_Archive_Exception(
				sprintf(
				// translators: %s: Folder path.
						esc_html_x( 'Folder %s for archive not found', '%s = Folder name', 'backwpup' ),
						esc_html( dirname( $file ) )
					)
			);
		}

		$this->file = trim( $file );

		// TAR.GZ.
		if (
			( ! $this->filehandler && '.tar.gz' === strtolower( substr( $this->file, -7 ) ) )
			|| ( ! $this->filehandler && '.tar.bz2' === strtolower( substr( $this->file, -8 ) ) )
		) {
			if ( ! function_exists( 'gzencode' ) ) {
				throw new BackWPup_Create_Archive_Exception(
					esc_html__( 'Functions for gz compression not available', 'backwpup' )
				);
			}

			$this->method         = 'TarGz';
			$this->handlertype    = 'gz';
			$this->tar_index_file = $this->file . '.bwuidx';
			$is_fresh_start       = ! is_file( $this->file );
			$this->filehandler    = $this->fopen( $this->file, 'ab' );
			$this->tar_index_init( $is_fresh_start );
		}

		// .TAR.
		if ( ! $this->filehandler && '.tar' === strtolower( substr( $this->file, -4 ) ) ) {
			$this->method         = 'Tar';
			$this->tar_index_file = $this->file . '.bwuidx';
			$is_fresh_start       = ! is_file( $this->file );
			$this->filehandler    = $this->fopen( $this->file, 'ab' ); // phpcs:ignore
			$this->tar_index_init( $is_fresh_start );
		}

		// .ZIP.
		if ( ! $this->filehandler && '.zip' === strtolower( substr( $this->file, -4 ) ) ) {
			$this->method = \ZipArchive::class;

			// Switch to PclZip if ZipArchive isn't supported.
			if ( ! class_exists( \ZipArchive::class ) ) {
				$this->method = \PclZip::class;
			}

			// GzEncode supported?
			if ( \PclZip::class === $this->method && ! function_exists( 'gzencode' ) ) {
				throw new BackWPup_Create_Archive_Exception(
					esc_html__( 'Functions for gz compression not available', 'backwpup' )
				);
			}

			if ( \ZipArchive::class === $this->method ) {
				$this->ziparchive = new ZipArchive();
				$ziparchive_open  = $this->ziparchive->open( $this->file, ZipArchive::CREATE );

				if ( true !== $ziparchive_open ) {
					$this->ziparchive_status();

					throw new BackWPup_Create_Archive_Exception(
						sprintf(
						// translators: %d: ZipArchive open() result.
								esc_html_x( 'Cannot create zip archive: %d', 'ZipArchive open() result', 'backwpup' ),
								esc_html( (string) $ziparchive_open )
							)
					);
				}
			}

			if ( \PclZip::class === $this->method ) {
				$this->method = \PclZip::class;

				require_once ABSPATH . 'wp-admin/includes/class-pclzip.php'; // @phpstan-ignore-line

				$this->pclzip = new PclZip( $this->file );
			}

			// Must be set to true to prevent issues. Monkey patch.
			$this->filehandler = true;
		}

		// .GZ.
		if (
			( ! $this->filehandler && '.gz' === strtolower( substr( $this->file, -3 ) ) )
			|| ( ! $this->filehandler && '.bz2' === strtolower( substr( $this->file, -4 ) ) )
		) {
			if ( ! function_exists( 'gzencode' ) ) {
				throw new BackWPup_Create_Archive_Exception(
					esc_html__( 'Functions for gz compression not available', 'backwpup' )
				);
			}

			$this->method      = 'gz';
			$this->handlertype = 'gz';
			$this->filehandler = $this->fopen( $this->file, 'w' );
		}

		if ( '' === $this->method ) {
			throw new BackWPup_Create_Archive_Exception(
				sprintf(
				// translators: %s: Archive file name.
						esc_html_x( 'Method to archive file %s not detected', '%s = file name', 'backwpup' ),
						esc_html( basename( $this->file ) )
					)
			);
		}

		if ( null === $this->filehandler ) {
			throw new BackWPup_Create_Archive_Exception( esc_html__( 'Cannot open archive file', 'backwpup' ) );
		}
	}

	/**
	 * Destruct.
	 *
	 * Closes open archive on shutdown.
	 */
	public function __destruct() {
		// Close PclZip.
		if ( is_object( $this->pclzip ) ) {
			if ( count( $this->pclzip_file_list ) > 0 ) {
				if ( 0 === $this->pclzip->add( $this->pclzip_file_list ) ) {
					trigger_error(
						sprintf(
							// translators: %s: PclZip error message.
							esc_html__( 'PclZip archive add error: %s', 'backwpup' ),
							esc_html( (string) $this->pclzip->errorInfo( true ) )
						),
						E_USER_ERROR
					);
				}
			}
			unset( $this->pclzip );
		}

		// Close ZipArchive.
		if ( null !== $this->ziparchive ) {
			if ( ! $this->ziparchive->close() ) {
				$this->ziparchive_status();

				sleep( 1 );
			}
			$this->ziparchive = null;
		}

		// Close file if open.
		if ( is_resource( $this->filehandler ) ) {
			$this->fclose();
		}
	}

	/**
	 * Close.
	 *
	 * Closing the archive.
	 */
	public function close() {
		if ( $this->ziparchive instanceof \ZipArchive ) {
			$this->ziparchive->close();
			$this->ziparchive = null;
		}

		if ( ! is_resource( $this->filehandler ) ) {
			return;
		}

		// Write tar file end.
		if ( in_array( $this->method, [ 'Tar', 'TarGz' ], true ) ) {
			$this->fwrite( pack( 'a1024', '' ) );
		}

		$this->fclose();

		// The archive is complete and will not be resumed again: close and delete the sidecar.
		if ( is_resource( $this->tar_index_handle ) ) {
			fclose( $this->tar_index_handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			$this->tar_index_handle = null;
		}

		if ( '' !== $this->tar_index_file && file_exists( $this->tar_index_file ) ) {
			@unlink( $this->tar_index_file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.unlink_unlink
		}
	}

	/**
	 * Abort a partially-written archive.
	 *
	 * Unlike close(), this discards the in-progress archive: it is not a valid, complete
	 * backup and must not be left on disk, together with its Tar/TarGz duplicate-entry
	 * sidecar. Safe to call for any archive method (Tar, TarGz, Zip, PclZip, gz).
	 *
	 * @return void
	 */
	public function abort() {
		if ( $this->ziparchive instanceof \ZipArchive ) {
			$this->ziparchive->close();
			$this->ziparchive = null;
		}

		if ( is_resource( $this->filehandler ) ) {
			$this->fclose();
		}

		if ( is_resource( $this->tar_index_handle ) ) {
			fclose( $this->tar_index_handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			$this->tar_index_handle = null;
		}

		if ( '' !== $this->tar_index_file && file_exists( $this->tar_index_file ) ) {
			@unlink( $this->tar_index_file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.unlink_unlink
		}

		if ( '' !== $this->file && file_exists( $this->file ) ) {
			@unlink( $this->file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.unlink_unlink
		}
	}

	/**
	 * Get Method.
	 *
	 * Get method that the archive uses.
	 *
	 * @return string The compression method.
	 */
	public function get_method() {
		return $this->method;
	}

	/**
	 * Adds a file to Archive.
	 *
	 * @param string $file_name       The file name path.
	 * @param string $name_in_archive The name of the file to use within the archive.
	 *
	 * @return bool True on success, false on error.
	 */
	public function add_file( string $file_name, string $name_in_archive = '' ): bool {

		if ( empty( $file_name ) ) {
			trigger_error(
				esc_html__( 'File name cannot be empty.', 'backwpup' ),
				E_USER_WARNING
			);

			return true;
		}

		clearstatcache( true, $file_name );

		if ( ! is_readable( $file_name ) ) {
			trigger_error(
				sprintf(
				// translators: %s: File path.
						esc_html_x( 'File %s does not exist or is not readable', 'File to add to archive', 'backwpup' ),
						esc_html( $file_name )
					),
				E_USER_WARNING
			);

			return true;
		}

		if ( empty( $name_in_archive ) ) {
			$name_in_archive = $file_name;
		}

		switch ( $this->method ) {
			case 'gz':
				if ( ! is_resource( $this->filehandler ) ) {
					return false;
				}

				if ( $this->file_count > 0 ) {
					trigger_error(
						esc_html__( 'This archive method can only add one file', 'backwpup' ),
						E_USER_WARNING
					);

					return false;
				}

				$fd = $this->fopen( $file_name, 'rb' );
				if ( ! $fd ) {
					return false;
				}

				while ( ! feof( $fd ) ) {
					$this->fwrite( fread( $fd, 8192 ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
				}
				fclose( $fd ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

				++$this->file_count;
				break;

			case 'Tar':
			case 'TarGz':
				// Convert chars for archive file names.
				if ( function_exists( 'iconv' ) && 'Windows' === PHP_OS_FAMILY ) {
					$test = iconv( 'ISO-8859-1', 'UTF-8', $name_in_archive );
					if ( false !== $test ) {
						$name_in_archive = $test;
					}
				}

				return $this->tar_file( $file_name, $name_in_archive );

			case \ZipArchive::class:
				// Convert chars for archives file names.
				if ( function_exists( 'iconv' ) && 'Windows' === PHP_OS_FAMILY ) {
					$test = iconv( 'UTF-8', 'CP437', $name_in_archive );
					if ( false !== $test ) {
						$name_in_archive = $test;
					}
				}

				$file_size = filesize( $file_name );
				if ( false === $file_size ) {
					return false;
				}

				$zip_file_stat = $this->ziparchive->statName( $name_in_archive );
				// If the file is already in the archive, skip it.
				if ( isset( $zip_file_stat['size'] ) && $zip_file_stat['size'] === $file_size ) {
					return true;
				}

				// The file is in the archive but the size is different, so delete the old and store the new one.
				if ( $zip_file_stat ) {
					$this->ziparchive->deleteName( $name_in_archive );
					// Reopen on deletion.
					$this->file_count = 21;
				}

				// Close and reopen, all added files are open on fs.
				// 35 works with PHP 5.2.4 on win.
				if ( $this->file_count > 20 ) {
					if ( ! $this->ziparchive->close() ) {
						$this->ziparchive_status();
						trigger_error(
							esc_html__( 'ZIP archive cannot be closed correctly', 'backwpup' ),
							E_USER_ERROR
						);

						sleep( 1 );
					}

					$this->ziparchive = null;

					if ( ! $this->check_archive_filesize() ) {
						return false;
					}

					$this->ziparchive = new ZipArchive();
					$ziparchive_open  = $this->ziparchive->open( $this->file, ZipArchive::CREATE );

					if ( true !== $ziparchive_open ) {
						$this->ziparchive_status();

						return false;
					}

					$this->file_count = 0;
				}

				if ( ( 1024 * 1024 * 2 ) > $file_size ) {
					$filesystem    = backwpup_wpfilesystem();
					$file_contents = $filesystem->get_contents( $file_name );
					if ( false === $file_contents ) {
						$this->ziparchive_status();
						trigger_error(
							sprintf(
							// translators: %s: File name added to the archive.
								esc_html__( 'Cannot read "%s" for zip archive!', 'backwpup' ),
								esc_html( $name_in_archive )
							),
							E_USER_ERROR
						);

						return false;
					}
					if ( ! $this->ziparchive->addFromString( $name_in_archive, $file_contents ) ) {
						$this->ziparchive_status();
						trigger_error(
							sprintf(
							// translators: %s: File name added to the archive.
									esc_html__( 'Cannot add "%s" to zip archive!', 'backwpup' ),
									esc_html( $name_in_archive )
								),
							E_USER_ERROR
						);

						return false;
					}
					$file_factor      = round( $file_size / ( 1024 * 1024 ), 4 ) * 2;
					$this->file_count = $this->file_count + $file_factor;
				} else {
					if ( ! $this->ziparchive->addFile( $file_name, $name_in_archive ) ) {
						$this->ziparchive_status();
						trigger_error(
							sprintf(
							// translators: %s: File name added to the archive.
									esc_html__( 'Cannot add "%s" to zip archive!', 'backwpup' ),
									esc_html( $name_in_archive )
								),
							E_USER_ERROR
						);

						return false;
					}
					++$this->file_count;
				}
				break;

			case \PclZip::class:
				$this->pclzip_file_list[] = [
					PCLZIP_ATT_FILE_NAME          => $file_name,
					PCLZIP_ATT_FILE_NEW_FULL_NAME => $name_in_archive,
				];

				if ( count( $this->pclzip_file_list ) >= 100 ) {
					if ( 0 === $this->pclzip->add( $this->pclzip_file_list ) ) {
						trigger_error(
							sprintf(
								// translators: %s: PclZip error message.
								esc_html__( 'PclZip archive add error: %s', 'backwpup' ),
								esc_html( (string) $this->pclzip->errorInfo( true ) )
							),
							E_USER_ERROR
						);

						return false;
					}
					$this->pclzip_file_list = [];
				}
				break;
		}

		return true;
	}

	/**
	 * Add a empty Folder to archive.
	 *
	 * @param string $folder_name     Name of folder to add to archive.
	 * @param string $name_in_archive The name of archive to use within the archive.
	 *
	 * @return bool
	 */
	public function add_empty_folder( $folder_name, $name_in_archive ) {
		$folder_name = trim( $folder_name );

		if ( empty( $folder_name ) ) {
			trigger_error(
				esc_html__( 'Folder name cannot be empty', 'backwpup' ),
				E_USER_WARNING
			);

			return false;
		}

		if ( ! is_dir( $folder_name ) || ! is_readable( $folder_name ) ) {
			trigger_error(
				sprintf(
				// translators: %s: Folder path.
					esc_html_x(
						'Folder %s does not exist or is not readable',
						'Folder path to add to archive',
						'backwpup'
					),
					esc_html( $folder_name )
				),
				E_USER_WARNING
			);

			return false;
		}

		if ( empty( $name_in_archive ) ) {
			return false;
		}

		// Remove reserved chars.
		$name_in_archive = backwpup_remove_invalid_characters_from_directory_name( $name_in_archive );

		switch ( $this->method ) {
			case 'gz':
				trigger_error(
					esc_html__( 'This archive method can only add one file', 'backwpup' ),
					E_USER_ERROR
				);

				return false;

			case 'Tar':
			case 'TarGz':
				$this->tar_empty_folder( $folder_name, $name_in_archive );

				return false;

			case \ZipArchive::class:
				if ( ! $this->ziparchive->addEmptyDir( $name_in_archive ) ) {
					trigger_error(
						sprintf(
						// translators: %s: File name added to the archive.
								esc_html__( 'Cannot add "%s" to zip archive!', 'backwpup' ),
								esc_html( $name_in_archive )
							),
						E_USER_WARNING
					);

					return false;
				}
				break;

			case \PclZip::class:
				return true;
		}

		return true;
	}

	/**
	 * Output status of ZipArchive.
	 *
	 * @return bool
	 */
	private function ziparchive_status() {
		if ( 0 === $this->ziparchive->status ) {
			return true;
		}

		trigger_error(
			sprintf(
			// translators: %s: ZipArchive status message.
					esc_html_x( 'ZipArchive returns status: %s', 'Text of ZipArchive status Message', 'backwpup' ),
					esc_html( (string) $this->ziparchive->getStatusString() )
				),
			E_USER_ERROR
		);

		return false;
	}

	/**
	 * Tar a file to archive.
	 *
	 * @param string $file_name       The file to store in the archive.
	 * @param string $name_in_archive The file name to use within the archive.
	 *
	 * @return bool True on success, false on failure.
	 */
	private function tar_file( $file_name, $name_in_archive ) {
		if ( ! is_resource( $this->filehandler ) ) {
			return false;
		}

		$source_size = filesize( $file_name );
		$source_size = false === $source_size ? null : $source_size;

		$position = $this->tar_resolve_write_position( $name_in_archive, 'file', $source_size );
		if ( 'skip' === $position['action'] ) {
			return true;
		}

		if ( ! $this->check_archive_filesize( $file_name ) ) {
			return false;
		}

		$chunk_size = 1024 * 1024 * 4;
		$filename   = $name_in_archive;

		// Get file stat.
		$file_stat = stat( $file_name );
		if ( ! $file_stat ) {
			return true;
		}

		// Sanitize values.
		$file_stat['size'] = abs( (int) $file_stat['size'] );

		// Retrieve owner and group for the file.
		[$owner, $group] = $this->posix_getpwuid( $file_stat['uid'], $file_stat['gid'] );

		// Generate the TAR header for this file.
		$chunk = $this->make_tar_headers(
			$filename,
			$file_stat['mode'],
			$file_stat['uid'],
			$file_stat['gid'],
			$file_stat['size'],
			$file_stat['mtime'],
			0,
			$owner,
			$group
		);

		$fd = false;
		if ( $file_stat['size'] > 0 ) {
			$fd = fopen( $file_name, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

			if ( ! is_resource( $fd ) ) {
				trigger_error(
					sprintf(
						// translators: %s: File path.
							esc_html__( 'Cannot open source file %s for archiving. Writing an empty file.', 'backwpup' ),
							esc_html( $file_name )
						),
					E_USER_WARNING
				);
			}
		}

		if ( $fd ) {
			// Read/write files in 512-bit blocks.
			while ( ! feof( $fd ) ) {
				$content = fread( $fd, 512 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
				if ( '' === $content || false === $content ) {
					break;
				}

				$chunk .= pack( 'a512', $content );

				if ( strlen( $chunk ) >= $chunk_size ) {
					$this->fwrite( $chunk );

					$chunk = '';
				}
			}
			fclose( $fd ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		}

		if ( ! empty( $chunk ) ) {
			$this->fwrite( $chunk );
		}

		$end_offset = $this->tar_current_offset();
		$this->tar_index_mark_done( $name_in_archive, $end_offset, $file_stat['size'] );

		return true;
	}

	/**
	 * Tar an empty Folder to archive.
	 *
	 * @param string $folder_name     Folder name to add.
	 * @param string $name_in_archive Folder name to use within the archive.
	 *
	 * @return bool True on success, false on failure.
	 */
	private function tar_empty_folder( $folder_name, $name_in_archive ) {
		if ( ! is_resource( $this->filehandler ) ) {
			return false;
		}

		$name_in_archive = trailingslashit( $name_in_archive );

		$position = $this->tar_resolve_write_position( $name_in_archive, 'dir', 0 );
		if ( 'skip' === $position['action'] ) {
			return true;
		}

		$tar_filename = $name_in_archive;

		$file_stat = stat( $folder_name );
		if ( ! $file_stat ) {
			return false;
		}
		// Retrieve owner and group for the file.
		[$owner, $group] = $this->posix_getpwuid( $file_stat['uid'], $file_stat['gid'] );

		// Generate the TAR header for this file.
		$header = $this->make_tar_headers(
			$tar_filename,
			$file_stat['mode'],
			$file_stat['uid'],
			$file_stat['gid'],
			$file_stat['size'],
			$file_stat['mtime'],
			5,
			$owner,
			$group
		);

		$this->fwrite( $header );

		$end_offset = $this->tar_current_offset();
		$this->tar_index_mark_done( $name_in_archive, $end_offset, 0 );

		return true;
	}

	/**
	 * Check Archive File size.
	 *
	 * @param string $file_to_add The file to check.
	 *
	 * @return bool True if the file size is less than PHP_INT_MAX, false otherwise.
	 */
	public function check_archive_filesize( $file_to_add = '' ) {
		$file_to_add_size = 0;

		if ( ! empty( $file_to_add ) ) {
			$file_to_add_size = filesize( $file_to_add );

			if ( false === $file_to_add_size ) {
				$file_to_add_size = 0;
			}
		}

		if ( is_resource( $this->filehandler ) ) {
			$archive_size = $this->tar_current_offset();
		} else {
			$archive_size = filesize( $this->file );
			if ( false === $archive_size ) {
				$archive_size = PHP_INT_MAX;
			}
		}

		$archive_size = $archive_size + $file_to_add_size;
		if ( PHP_INT_MAX <= $archive_size ) {
			trigger_error(
				sprintf(
					// translators: %s: File path.
					esc_html__(
						'If %s will be added to your backup archive, the archive will be too large for operations with this PHP Version. You might want to consider splitting the backup job in multiple jobs with less files each.',
						'backwpup'
					),
						esc_html( $file_to_add )
				),
				E_USER_ERROR
			);

			return false;
		}

		return true;
	}

	/**
	 * Make Tar Headers.
	 *
	 * @param string $name       The name of the file or directory. Known as Item.
	 * @param string $mode       the permissions for the item.
	 * @param int    $uid        the owner ID.
	 * @param int    $gid        the group ID.
	 * @param int    $size       the size of the item.
	 * @param int    $mtime      the time of the last modification.
	 * @param int    $typeflag   The type of the item. 0 for File and 5 for Directory.
	 * @param string $owner      the owner Name.
	 * @param string $group      the group Name.
	 *
	 * @return mixed|string
	 */
	private function make_tar_headers( $name, $mode, $uid, $gid, $size, $mtime, $typeflag, $owner, $group ) {
		$headers   = '';
		$orig_name = $name;
		$prefix    = '';

		// Attempt to split filename larger than 100 chars.
		if ( 100 < strlen( $name ) ) {
			$filename_offset = strlen( $name ) - 100;
			$split_pos       = strpos( $name, '/', $filename_offset );

			if ( false === $split_pos ) {
				$split_pos = strrpos( $name, '/' );
			}

			$prefix = substr( $name, 0, $split_pos );
			$name   = substr( $name, $split_pos + 1 );
		}

		// Handle long filenames (GNU tar format).
		// If the name is longer than 100 or the prefix is longer than 155, encode @LongLink and truncate the header name.
		if ( strlen( $name ) > 100 || strlen( $prefix ) > 155 ) {
			$longlink_content = $orig_name . "\0";

			$longlink_header = $this->make_tar_headers(
				'@LongLink',
				'0000777',
				0,
				0,
				strlen( $longlink_content ),
				time(),
				'L',
				'root',
				'root'
			);

			// Add ending null byte, and pack into binary to a multiple of 512.
			$chunk_count    = ceil( strlen( $longlink_content ) / 512 );
			$packed_size    = $chunk_count * 512;
			$packed_content = pack( 'a' . $packed_size, $longlink_content );

			$headers .= $longlink_header . $packed_content;
			// Truncate name for actual header.
			$name   = substr( $orig_name, 0, 100 );
			$prefix = '';
		}

		// Generate the TAR header for this file.
		$chunk = pack(
			'a100a8a8a8a12a12a8a1a100a6a2a32a32a8a8a155a12',
			$name, // name of file  100.
			sprintf( '%07o', $mode ), // file mode  8.
			sprintf( '%07o', $uid ), // owner user ID  8.
			sprintf( '%07o', $gid ), // owner group ID  8.
			sprintf( '%011o', $size ), // length of file in bytes  12.
			sprintf( '%011o', $mtime ), // modify time of file  12.
			'        ', // checksum for header  8.
			$typeflag, // type of file  0 or null = File, 5=Dir.
			'', // name of linked file  100.
			'ustar', // USTAR indicator  6.
			'  ', // USTAR Version (00 for ustar, double-space for gnutar)  2.
			$owner, // owner user name 32.
			$group, // owner group name 32.
			'', // device major number 8.
			'', // device minor number 8.
			$prefix, // prefix for file name 155.
			''
		); // fill block 12.

		// Computes the unsigned Checksum of a file's header.
		$checksum = 0;

		for ( $i = 0; $i < 512; ++$i ) {
			$checksum += ord( substr( $chunk, $i, 1 ) );
		}

		$checksum = pack( 'a8', sprintf( '%07o', $checksum ) );
		$chunk    = substr_replace( $chunk, $checksum, 148, 8 );

		return $headers . $chunk;
	}

	/**
	 * Delete the Tar/TarGz duplicate-entry sidecar file for a given archive path, if any.
	 *
	 * The sidecar only ever exists next to a Tar/TarGz archive (suffix `.bwuidx`); for
	 * other archive methods this is a no-op. Callers should invoke this whenever a
	 * backup archive is deleted from disk (manual deletion, retention cleanup, …) so
	 * the sidecar does not linger as an orphan once its archive is gone.
	 *
	 * @param string $archive_file Full path of the backup archive file.
	 *
	 * @return void
	 */
	public static function delete_sidecar_for( $archive_file ) {
		if ( ! is_string( $archive_file ) || '' === $archive_file ) {
			return;
		}

		$sidecar_file = $archive_file . '.bwuidx';

		if ( file_exists( $sidecar_file ) ) {
			@unlink( $sidecar_file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.unlink_unlink
		}
	}

	/**
	 * Initialize the Tar/TarGz duplicate-entry sidecar index.
	 *
	 * Called once from the constructor, right after the archive filehandler is
	 * opened, for the Tar and TarGz branches only.
	 *
	 * @param bool $is_fresh_start Whether the archive file did not exist before this
	 *                             instance opened it (i.e. this is not a resume).
	 *
	 * @return void
	 */
	private function tar_index_init( $is_fresh_start ) {
		if ( $is_fresh_start ) {
			if ( file_exists( $this->tar_index_file ) ) {
				@unlink( $this->tar_index_file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.unlink_unlink
			}

			$this->tar_index = [];
		} else {
			$this->tar_index = $this->tar_index_load();
		}

		$this->tar_index_handle = $this->fopen( $this->tar_index_file, 'ab' );
	}

	/**
	 * Load and replay the NDJSON sidecar log into an in-memory index.
	 *
	 * Reads the log line by line (never the whole file at once) and replays each
	 * decoded line into the index keyed by name_in_archive, so the last line written
	 * for a given name always wins. Fails open (empty index, E_USER_WARNING) if the
	 * log is missing or unreadable; a single corrupt/undecodable line is skipped with
	 * its own warning rather than aborting the whole replay.
	 *
	 * @return array
	 */
	private function tar_index_load() {
		$index      = [];
		$last_entry = null;

		if ( ! is_file( $this->tar_index_file ) || ! is_readable( $this->tar_index_file ) ) {
			trigger_error(
				esc_html__( 'Tar duplicate-entry index sidecar file is missing or unreadable; duplicate-entry protection is disabled for this run.', 'backwpup' ),
				E_USER_WARNING
			);

			return $index;
		}

		$handle = fopen( $this->tar_index_file, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

		if ( ! is_resource( $handle ) ) {
			trigger_error(
				esc_html__( 'Tar duplicate-entry index sidecar file could not be opened; duplicate-entry protection is disabled for this run.', 'backwpup' ),
				E_USER_WARNING
			);

			return $index;
		}

		while ( ! feof( $handle ) ) {
			$line = fgets( $handle );

			if ( false === $line ) {
				break;
			}

			$line = trim( $line );

			if ( '' === $line ) {
				continue;
			}

			$decoded = json_decode( $line, true );

			// A truncated or otherwise corrupt trailing line (e.g. a hard kill mid-write of
			// the log line itself) fails to decode here and is conservatively dropped: the
			// name simply falls back to whatever earlier, fully-written line already exists
			// for it (or has no entry at all, treated as brand new).
			if ( ! is_array( $decoded ) || ! isset( $decoded['name'] ) ) {
				trigger_error(
					esc_html__( 'Skipping corrupt line in Tar duplicate-entry index sidecar file.', 'backwpup' ),
					E_USER_WARNING
				);

				continue;
			}

			$index[ $decoded['name'] ] = $decoded;
			$last_entry                = $decoded;
		}

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		// Size-only validation: reject the sidecar if the real archive file is smaller than
		// what its last recorded entry implies. A legitimate resume can only ever make the
		// real file at least as large as expected up to the last recorded entry (never
		// smaller), so the comparison is strictly "<", never "!==" — a real archive is allowed
		// to be larger (e.g. leftover unflushed bytes from a crash that
		// tar_resolve_write_position() will truncate later). This intentionally does not
		// detect a same-size-or-larger but different-content colliding archive: the goal is
		// eliminating silent data loss (files wrongly skipped because a stale sidecar claimed
		// they were already written), not full archive identity/content verification. No
		// mtime-based secondary guard is used: a crash mid-content-write of a large file
		// spanning multiple real seconds would advance the archive's real mtime past whatever
		// mtime was recorded when the entry was marked pending, which would misclassify a
		// legitimate resume as stale and cause tar_resolve_write_position() to append a fresh
		// header after the leftover garbage bytes of the aborted write — real tar corruption.
		if ( null !== $last_entry ) {
			$expected_min_size = 'done' === ( $last_entry['status'] ?? null )
				? ( $last_entry['end_offset'] ?? null )
				: ( $last_entry['start_offset'] ?? null );

			// A malformed/missing offset on the last entry is itself a reason not to trust the
			// sidecar: fail closed (treat as PHP_INT_MAX, guaranteeing a mismatch) rather than
			// silently trusting an entry we cannot actually validate.
			if ( ! is_int( $expected_min_size ) ) {
				$expected_min_size = PHP_INT_MAX;
			}

			$real_size = is_file( $this->file ) ? filesize( $this->file ) : false;

			if ( false === $real_size || $real_size < $expected_min_size ) {
				trigger_error(
					esc_html__( 'Tar duplicate-entry index sidecar does not match its archive file (the archive is smaller than the sidecar expects); discarding the stale sidecar instead of trusting it, so no file already marked done is silently skipped from the new archive.', 'backwpup' ),
					E_USER_WARNING
				);

				if ( file_exists( $this->tar_index_file ) ) {
					@unlink( $this->tar_index_file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.unlink_unlink
				}

				return [];
			}
		}

		return $index;
	}

	/**
	 * Append one entry as a single NDJSON line to the sidecar log.
	 *
	 * The only place that writes to the log file; never re-reads or rewrites prior
	 * lines, so the cost is O(1) per call regardless of how many entries already
	 * exist. No-op if the index handle is not a resource (non-Tar/TarGz methods).
	 *
	 * @param array $entry Entry to persist.
	 *
	 * @return void
	 */
	private function tar_index_append( array $entry ) {
		if ( ! is_resource( $this->tar_index_handle ) ) {
			return;
		}

		fwrite( $this->tar_index_handle, wp_json_encode( $entry ) . "\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
		fflush( $this->tar_index_handle );
	}

	/**
	 * Record that a Tar/TarGz entry write has started, before any header/content
	 * bytes are written for it.
	 *
	 * @param string $name         name_in_archive for the entry.
	 * @param string $type         'file'|'dir'.
	 * @param int    $start_offset Byte offset in $this->filehandler where the entry's
	 *                             header begins.
	 *
	 * @return void
	 */
	private function tar_index_mark_pending( $name, $type, $start_offset ) {
		$entry = [
			'name'         => $name,
			'type'         => $type,
			'status'       => 'pending',
			'start_offset' => $start_offset,
			'end_offset'   => null,
			'size'         => null,
		];

		$this->tar_index[ $name ] = $entry;

		$this->tar_index_append( $entry );
	}

	/**
	 * Record that a Tar/TarGz entry write has completed.
	 *
	 * @param string $name       name_in_archive for the entry.
	 * @param int    $end_offset Byte offset in $this->filehandler right after the
	 *                           entry's header/content have been fully written.
	 * @param int    $size       Source size recorded at completion; 0 for directories.
	 *
	 * @return void
	 */
	private function tar_index_mark_done( $name, $end_offset, $size ) {
		$existing = isset( $this->tar_index[ $name ] ) ? $this->tar_index[ $name ] : [];

		$entry = array_merge(
			$existing,
			[
				'name'       => $name,
				'status'     => 'done',
				'end_offset' => $end_offset,
				'size'       => $size,
			]
		);

		$this->tar_index[ $name ] = $entry;

		$this->tar_index_append( $entry );
	}

	/**
	 * Get the current, fully-flushed byte offset of the archive filehandler.
	 *
	 * Flushes PHP's userspace stream write buffer before reading fstat(), so this is
	 * the single, guaranteed-fresh source of truth for every offset-capturing read in
	 * this class (pending-start, done-end, pre-comparison size checks, and the
	 * pre-truncate/seek read), rather than each call site reading fstat() directly.
	 *
	 * @return int
	 */
	private function tar_current_offset() {
		if ( ! is_resource( $this->filehandler ) ) {
			return 0;
		}

		fflush( $this->filehandler );

		$stats = fstat( $this->filehandler );

		return isset( $stats['size'] ) ? (int) $stats['size'] : 0;
	}

	/**
	 * Resolve whether a Tar/TarGz entry write should proceed, be skipped, or overwrite
	 * the last-written entry in place. Shared by tar_file() and tar_empty_folder() so
	 * there is exactly one place that decides skip-vs-truncate-vs-write.
	 *
	 * Whenever the resolved action is 'write', the pending state (and, when a
	 * truncate/fseek was performed, the truncation itself) is persisted to the
	 * sidecar log by this method before it returns. This guarantees the on-disk
	 * log can never observe a physically truncated archive without also recording
	 * the corresponding 'pending' entry, even if the caller aborts on a later
	 * check (e.g. check_archive_filesize() or stat() failure) before writing any
	 * header/content bytes.
	 *
	 * @param string   $name_in_archive Name of entry within the archive.
	 * @param string   $type            'file'|'dir'.
	 * @param int|null $source_size     filesize() for files; 0 for directories; null if
	 *                                  the source size could not be determined.
	 *
	 * @return array{action:string,offset:int}
	 */
	private function tar_resolve_write_position( $name_in_archive, $type, $source_size ) {
		$current_offset = $this->tar_current_offset();
		$existing       = isset( $this->tar_index[ $name_in_archive ] ) ? $this->tar_index[ $name_in_archive ] : null;

		if ( null === $existing ) {
			// Persist the pending state immediately, before returning, so the on-disk
			// log always reflects the write about to start even if the caller aborts
			// (e.g. a filesize()/stat() failure) right after this method returns.
			$this->tar_index_mark_pending( $name_in_archive, $type, $current_offset );

			return [
				'action' => 'write',
				'offset' => $current_offset,
			];
		}

		$is_last_entry = 'pending' === $existing['status']
			|| ( isset( $existing['end_offset'] ) && $existing['end_offset'] === $current_offset );

		if (
			'done' === $existing['status']
			&& $existing['type'] === $type
			&& null !== $source_size
			&& $existing['size'] === $source_size
		) {
			// Identical entry already fully written — self-heal like the ZIP writer's statName() skip.
			return [
				'action' => 'skip',
				'offset' => $current_offset,
			];
		}

		if ( $is_last_entry ) {
			// Safe to discard and rewrite: nothing has been written after this entry.
			ftruncate( $this->filehandler, $existing['start_offset'] );
			fseek( $this->filehandler, $existing['start_offset'] );

			// Persist the truncation to the sidecar log immediately, in the same
			// call that performed it, so the on-disk log can never end up stale
			// relative to the physical archive even if the caller (tar_file()/
			// tar_empty_folder()) aborts on a check that runs right after this
			// method returns (e.g. check_archive_filesize() or stat() failure).
			$this->tar_index_mark_pending( $name_in_archive, $type, $existing['start_offset'] );

			return [
				'action' => 'write',
				'offset' => $existing['start_offset'],
			];
		}

		// Entry differs but is not the last one written — cannot safely rewrite mid-stream
		// without corrupting subsequent archive data. Skip, never duplicate.
		trigger_error(
			sprintf(
				/* translators: %s: file name in archive. */
				esc_html__( 'Skipping "%s": already present in archive at a different size and cannot be safely rewritten mid-stream.', 'backwpup' ),
				esc_html( $name_in_archive )
			),
			E_USER_WARNING
		);

		return [
			'action' => 'skip',
			'offset' => $current_offset,
		];
	}

	/**
	 * Posix Get PW ID.
	 *
	 * @param int $uid The user ID.
	 * @param int $gid The group ID.
	 *
	 * @return array The owner and group in posix format.
	 */
	private function posix_getpwuid( $uid, $gid ) {
		// Set file user/group name if linux.
		$owner = esc_html__( 'Unknown', 'backwpup' );
		$group = esc_html__( 'Unknown', 'backwpup' );

		if ( function_exists( 'posix_getpwuid' ) ) {
			$info = posix_getpwuid( $uid );
			if ( $info ) {
				$owner = $info['name'];
			}
			$info = posix_getgrgid( $gid );
			if ( $info ) {
				$group = $info['name'];
			}
		}

		return [
			$owner,
			$group,
		];
	}

	/**
	 * Fopen.
	 *
	 * @param string $filename The file to open in mode.
	 * @param string $mode     The mode to open the file.
	 *
	 * @return bool|resource The resource or false if file cannot be opened.
	 */
	private function fopen( $filename, $mode ) {
		$fd = fopen( $filename, $mode ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

		if ( ! $fd ) {
			trigger_error(
				sprintf(
					// translators: %s: Filename.
						esc_html__( 'Cannot open source file %s.', 'backwpup' ),
						esc_html( $filename )
					),
				E_USER_WARNING
			);
		}

		return $fd;
	}

	/**
	 * Write Content in File.
	 *
	 * @param string $content The content to write into the file.
	 *
	 * @return int The number of bytes written into the file.
	 */
	private function fwrite( $content ) {
		switch ( $this->handlertype ) {
			case 'bz':
				$content = bzcompress( $content );
				break;

			case 'gz':
				$content = gzencode( $content );
				break;

			default:
				break;
		}

		return (int) fwrite( $this->filehandler, $content ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
	}

	/**
	 * Close file handler.
	 */
	private function fclose() {
		fclose( $this->filehandler ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	}
}
