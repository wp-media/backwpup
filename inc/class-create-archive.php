<?php
/**
 * Create Archive
 */

/**
 * Class for creating File Archives
 */
class BackWPup_Create_Archive {

	/**
	 * Achieve file with full path
	 *
	 * @var string
	 */
	private $file = '';

	/**
	 * Compression method
	 *
	 * @var string Method off compression Methods are ZipArchive, PclZip, Tar, TarGz, gz
	 */
	private $method = '';

	/**
	 * File Handel
	 *
	 * Open handel for files.
	 */
	private $filehandler = null;

	/**
	 * Handler Type
	 *
	 * @var string Can be 'bz', 'gz' or empty string.
	 */
	private $handlertype = '';

	/**
	 * ZipArchive
	 *
	 * @var ZipArchive
	 */
	private $ziparchive = null;

	/**
	 * PclZip
	 *
	 * @var PclZip
	 */
	private $pclzip = null;

	/**
	 * PclZip File List
	 *
	 * @var array()
	 */
	private $pclzip_file_list = array();

	/**
	 * File Count
	 *
	 * File cont off added files to handel somethings that depends on it
	 *
	 * @var int number of files added
	 */
	private $file_count = 0;

	/**
	 * BackWPup_Create_Archive constructor
	 *
	 * @param string $file File with full path of the archive.
	 *
	 * @throws BackWPup_Create_Archive_Exception If the file is empty or not a valid string.
	 */
	public function __construct( $file ) {

		if ( ! is_string( $file ) || empty( $file ) ) {
			throw new BackWPup_Create_Archive_Exception(
				__( 'The file name of an archive cannot be empty.', 'backwpup' )
			);
		}

		// Check folder can used.
		if ( ! is_dir( dirname( $file ) ) || ! is_writable( dirname( $file ) ) ) {
			throw new BackWPup_Create_Archive_Exception(
				sprintf(
				/* translators: $1 is the file path */
					esc_html_x( 'Folder %s for archive not found', '%s = Folder name', 'backwpup' ),
					dirname( $file )
				)
			);
		}

		$this->file = trim( $file );

		// TAR.GZ
		if (
			(! $this->filehandler && '.tar.gz' === strtolower( substr( $this->file, - 7 ) ))
		    || ( ! $this->filehandler && '.tar.bz2' === strtolower( substr( $this->file, - 8 ) ) )
		) {
			if ( ! function_exists( 'gzencode' ) ) {
				throw new BackWPup_Create_Archive_Exception(
					__( 'Functions for gz compression not available', 'backwpup' )
				);
			}

			$this->method      = 'TarGz';
			$this->handlertype = 'gz';
			$this->filehandler = $this->fopen( $this->file, 'ab' );
		}

		// .TAR
		if ( ! $this->filehandler && '.tar' === strtolower( substr( $this->file, - 4 ) ) ) {
			$this->method      = 'Tar';
			$this->filehandler = $this->fopen( $this->file, 'ab' ); // phpcs:ignore
		}

		// .ZIP
		if ( ! $this->filehandler && '.zip' === strtolower( substr( $this->file, - 4 ) ) ) {
			$this->method = 'ZipArchive';

			// Switch to PclZip if ZipArchive isn't supported.
			if ( ! class_exists( 'ZipArchive' ) ) {
				$this->method = 'PclZip';
			}

			// GzEncode supported?
			if ( 'PclZip' === $this->method && ! function_exists( 'gzencode' ) ) {
				throw new BackWPup_Create_Archive_Exception(
					esc_html__( 'Functions for gz compression not available', 'backwpup' )
				);
			}

			if ( 'ZipArchive' === $this->method ) {
				$this->ziparchive = new ZipArchive();
				$ziparchive_open  = $this->ziparchive->open( $this->file, ZipArchive::CREATE );

				if ( $ziparchive_open !== true ) {
					$this->ziparchive_status();

					throw new BackWPup_Create_Archive_Exception(
						sprintf(
						/* translators: $1 is a directory name */
							esc_html_x( 'Cannot create zip archive: %d', 'ZipArchive open() result', 'backwpup' ),
							$ziparchive_open
						)
					);
				}
			}

			if ( 'PclZip' === $this->method ) {
				$this->method = 'PclZip';

				if ( ! defined( 'PCLZIP_TEMPORARY_DIR' ) ) {
					define( 'PCLZIP_TEMPORARY_DIR', BackWPup::get_plugin_data( 'TEMP' ) );
				}

				require_once ABSPATH . 'wp-admin/includes/class-pclzip.php';

				$this->pclzip = new PclZip( $this->file );
			}

			// Must be set to true to prevent issues. Monkey patch.
			$this->filehandler = true;
		}

		// .GZ
		if (
		    ( ! $this->filehandler && '.gz' === strtolower( substr( $this->file, - 3 ) ) )
		    || ( ! $this->filehandler && '.bz2' === strtolower( substr( $this->file, - 4 ) ) )
		) {
			if ( ! function_exists( 'gzencode' ) ) {
				throw new BackWPup_Create_Archive_Exception(
					__( 'Functions for gz compression not available', 'backwpup' )
				);
			}

			$this->method      = 'gz';
			$this->handlertype = 'gz';
			$this->filehandler = $this->fopen( $this->file, 'w' );
		}

		if ( '' === $this->method ) {
			throw new BackWPup_Create_Archive_Exception(
				sprintf(
				/* translators: the $1 is the type of the archive file */
					esc_html_x( 'Method to archive file %s not detected', '%s = file name', 'backwpup' ),
					basename( $this->file )
				)
			);
		}

		if ( null === $this->filehandler ) {
			throw new BackWPup_Create_Archive_Exception( __( 'Cannot open archive file', 'backwpup' ) );
		}
	}

	/**
	 * Destruct
	 *
	 * Closes open archive on shutdown.
	 */
	public function __destruct() {

		// Close PclZip.
		if ( is_object( $this->pclzip ) ) {
			if ( count( $this->pclzip_file_list ) > 0 ) {
				if ( 0 == $this->pclzip->add( $this->pclzip_file_list ) ) {
					trigger_error(
						sprintf(
						/* translatores: $1 is the error string */
							esc_html__( 'PclZip archive add error: %s', 'backwpup' ),
							$this->pclzip->errorInfo( true )
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
	 * Close
	 *
	 * Closing the archive
	 *
	 * @return void
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
		if ( in_array( $this->method, array( 'Tar', 'TarGz' ), true ) ) {
			$this->fwrite( pack( 'a1024', '' ) );
		}

		$this->fclose();
	}

	/**
	 * Get Method
	 *
	 * Get method that the archive uses
	 *
	 * @return string The compression method
	 */
	public function get_method() {

		return $this->method;
	}

	/**
	 * Adds a file to Archive
	 *
	 * @param string $file_name       The file name path.
	 * @param string $name_in_archive The name of the file to use within the archive.
	 *
	 * @return bool True on success, false on error.
	 */
	public function add_file( $file_name, $name_in_archive = '' ) {

		$file_name = trim( $file_name );

		if ( ! is_string( $file_name ) || empty( $file_name ) ) {
			trigger_error(
				esc_html__( 'File name cannot be empty.', 'backwpup' ),
				E_USER_WARNING
			);

			return false;
		}

		if ( version_compare( PHP_VERSION, '5.3', '>=' ) ) {
			clearstatcache( true, $file_name );
		}

		if ( ! is_readable( $file_name ) ) {
			trigger_error(
				sprintf(
				/* translators: The $1 is the name of the file to add to the archive. */
					esc_html_x( 'File %s does not exist or is not readable', 'File to add to archive', 'backwpup' ),
					$file_name
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
					$this->fwrite( fread( $fd, 8192 ) );  // phpcs:ignore
				}
				fclose( $fd ); // phpcs:ignore

				$this->file_count ++;
				break;

			case 'Tar':
			case 'TarGz':
				// Convert chars for archives file names
				if ( function_exists( 'iconv' ) && stripos( PHP_OS, 'win' ) === 0 ) {
					$test = @iconv( 'ISO-8859-1', 'UTF-8', $name_in_archive );
					if ( $test ) {
						$name_in_archive = $test;
					}
				}

				return $this->tar_file( $file_name, $name_in_archive );
				break;

			case 'ZipArchive':
				// Convert chars for archives file names.
				if ( function_exists( 'iconv' ) && stripos( PHP_OS, 'win' ) === 0 ) {
					$test = @iconv( 'UTF-8', 'CP437', $name_in_archive );
					if ( $test ) {
						$name_in_archive = $test;
					}
				}

				$file_size = filesize( $file_name );
				if ( false === $file_size ) {
					return false;
				}

				$zip_file_stat = $this->ziparchive->statName( $name_in_archive );
				// If the file is allready in the archive doing anything else.
				if ( isset( $zip_file_stat['size'] ) && $zip_file_stat['size'] === $file_size ) {
					return true;
				}

				// The file is in the archive but the size is different than the one we
				// want to store. So delete the old and store the new one.
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

					if ( $ziparchive_open !== true ) {
						$this->ziparchive_status();

						return false;
					}

					$this->file_count = 0;
				}

				if ( $file_size < ( 1024 * 1024 * 2 ) ) {
					if ( ! $this->ziparchive->addFromString( $name_in_archive, file_get_contents( $file_name ) ) ) {
						$this->ziparchive_status();
						trigger_error(
							sprintf(
							/* translators: the $1 is the name of the archive. */
								esc_html__( 'Cannot add "%s" to zip archive!', 'backwpup' ),
								$name_in_archive
							),
							E_USER_ERROR
						);

						return false;
					} else {
						$file_factor      = round( $file_size / ( 1024 * 1024 ), 4 ) * 2;
						$this->file_count = $this->file_count + $file_factor;
					}
				} else {
					if ( ! $this->ziparchive->addFile( $file_name, $name_in_archive ) ) {
						$this->ziparchive_status();
						trigger_error(
							sprintf(
							/* translators: the $1 is the name of the archive. */
								esc_html__( 'Cannot add "%s" to zip archive!', 'backwpup' ),
								$name_in_archive
							),
							E_USER_ERROR
						);

						return false;
					} else {
						$this->file_count ++;
					}
				}
				break;

			case 'PclZip':
				$this->pclzip_file_list[] = array(
					PCLZIP_ATT_FILE_NAME          => $file_name,
					PCLZIP_ATT_FILE_NEW_FULL_NAME => $name_in_archive,
				);

				if ( count( $this->pclzip_file_list ) >= 100 ) {
					if ( 0 == $this->pclzip->add( $this->pclzip_file_list ) ) {
						trigger_error(
							sprintf(
							/* translators: The $1 is the tecnical error string from pclzip. */
								esc_html__( 'PclZip archive add error: %s', 'backwpup' ),
								$this->pclzip->errorInfo( true )
							),
							E_USER_ERROR
						);

						return false;
					}
					$this->pclzip_file_list = array();
				}
				break;
		}

		return true;
	}

	/**
	 * Add a empty Folder to archive
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
				/* translators: $1 is the folder name */
					esc_html_x(
						'Folder %s does not exist or is not readable',
						'Folder path to add to archive',
						'backwpup'
					),
					$folder_name
				),
				E_USER_WARNING
			);

			return false;
		}

		if ( empty( $name_in_archive ) ) {
			return false;
		}

		// Remove reserved chars.
		$name_in_archive = remove_invalid_characters_from_directory_name( $name_in_archive );

		switch ( $this->method ) {
			case 'gz':
				trigger_error(
					esc_html__( 'This archive method can only add one file', 'backwpup' ),
					E_USER_ERROR
				);

				return false;
				break;

			case 'Tar':
			case 'TarGz':
				$this->tar_empty_folder( $folder_name, $name_in_archive );

				return false;
				break;

			case 'ZipArchive':
				if ( ! $this->ziparchive->addEmptyDir( $name_in_archive ) ) {
					trigger_error(
						sprintf(
						/* translators: $1 is the name of the archive. */
							esc_html__( 'Cannot add "%s" to zip archive!', 'backwpup' ),
							$name_in_archive
						),
						E_USER_WARNING
					);

					return false;
				}
				break;

			case 'PclZip':
				return true;
				break;
		}

		return true;
	}

	/**
	 * Output status of ZipArchive
	 *
	 * @return bool
	 */
	private function ziparchive_status() {

		if ( $this->ziparchive->status === 0 ) {
			return true;
		}

		trigger_error(
			sprintf(
			/* translators. $1 is the status returned by a call to a ZipArchive method. */
				esc_html_x( 'ZipArchive returns status: %s', 'Text of ZipArchive status Message', 'backwpup' ),
				$this->ziparchive->getStatusString()
			),
			E_USER_ERROR
		);

		return false;
	}

	/**
	 * Tar a file to archive
	 *
	 * @param string $file_name       The file to store in the archive.
	 * @param string $name_in_archive The file name to use within the archive.
	 *
	 * @return bool True on success, false on failure
	 */
	private function tar_file( $file_name, $name_in_archive ) {

		if ( ! is_resource( $this->filehandler ) ) {
			return false;
		}

		if ( ! $this->check_archive_filesize( $file_name ) ) {
			return false;
		}

		$chunk_size      = 1024 * 1024 * 4;
		$filename        = $name_in_archive;
		$filename_prefix = '';

		// Split filename larger than 100 chars
		if ( 100 < strlen( $name_in_archive ) ) {
			$filename_offset = strlen( $name_in_archive ) - 100;
			$split_pos       = strpos( $name_in_archive, '/', $filename_offset );

			if ( $split_pos === false ) {
				$split_pos = strrpos( $name_in_archive, '/' );
			}

			$filename        = substr( $name_in_archive, $split_pos + 1 );
			$filename_prefix = substr( $name_in_archive, 0, $split_pos );

			if ( strlen( $filename ) > 100 ) {
				$filename = substr( $filename, - 100 );
				trigger_error(
					sprintf(
					/* translators: $1 is the file name. */
						esc_html__( 'File name "%1$s" is too long to be saved correctly in %2$s archive!', 'backwpup' ),
						$name_in_archive,
						$this->method
					),
					E_USER_WARNING
				);
			}

			if ( 155 < strlen( $filename_prefix ) ) {
				trigger_error(
					sprintf(
					/* translators: $1 is the file name to use in the archive. */
						esc_html__( 'File path "%1$s" is too long to be saved correctly in %2$s archive!', 'backwpup' ),
						$name_in_archive,
						$this->method
					),
					E_USER_WARNING
				);
			}
		}

		// Get file stat.
		$file_stat = stat( $file_name );
		if ( ! $file_stat ) {
			return true;
		}

		// Sanitize values.
		$file_stat['size'] = abs( (int) $file_stat['size'] );

		// Retrieve owner and group for the file.
		list( $owner, $group ) = $this->posix_getpwuid( $file_stat['uid'], $file_stat['gid'] );

		// Generate the TAR header for this file
		$chunk = $this->make_tar_headers(
			$filename,
			$file_stat['mode'],
			$file_stat['uid'],
			$file_stat['gid'],
			$file_stat['size'],
			$file_stat['mtime'],
			0,
			$owner,
			$group,
			$filename_prefix
		);

		$fd = false;
		if ( $file_stat['size'] > 0 ) {
			$fd = fopen( $file_name, 'rb' );

			if ( ! is_resource( $fd ) ) {
				trigger_error(
					sprintf(
						esc_html__( 'Cannot open source file %s for archiving. Writing an empty file.', 'backwpup' ),
						$file_name
					),
					E_USER_WARNING
				);
			}
		}

		if ( $fd ) {
			// Read/write files in 512 bit Blocks.
			while ( ( $content = fread( $fd, 512 ) ) != '' ) { // phpcs:ignore
				$chunk .= pack( 'a512', $content );

				if ( strlen( $chunk ) >= $chunk_size ) {
					$this->fwrite( $chunk );

					$chunk = '';
				}
			}
			fclose( $fd ); // phpcs:ignore
		}

		if ( ! empty( $chunk ) ) {
			$this->fwrite( $chunk );
		}

		return true;
	}

	/**
	 * Tar an empty Folder to archive
	 *
	 * @return bool True on success, false on failure.
	 */
	private function tar_empty_folder( $folder_name, $name_in_archive ) {

		if ( ! is_resource( $this->filehandler ) ) {
			return false;
		}

		$name_in_archive = trailingslashit( $name_in_archive );

		$tar_filename        = $name_in_archive;
		$tar_filename_prefix = '';

		// Split filename larger than 100 chars.
		if ( 100 < strlen( $name_in_archive ) ) {
			$filename_offset = strlen( $name_in_archive ) - 100;
			$split_pos       = strpos( $name_in_archive, '/', $filename_offset );

			if ( $split_pos === false ) {
				$split_pos = strrpos( untrailingslashit( $name_in_archive ), '/' );
			}

			$tar_filename        = substr( $name_in_archive, $split_pos + 1 );
			$tar_filename_prefix = substr( $name_in_archive, 0, $split_pos );

			if ( strlen( $tar_filename ) > 100 ) {
				$tar_filename = substr( $tar_filename, - 100 );
				trigger_error(
					sprintf(
					/* translators: $1 is the name of the folder. $2 is the archive name.*/
						esc_html__(
							'Folder name "%1$s" is too long to be saved correctly in %2$s archive!',
							'backwpup'
						),
						$name_in_archive,
						$this->method
					),
					E_USER_WARNING
				);
			}

			if ( strlen( $tar_filename_prefix ) > 155 ) {
				trigger_error(
					sprintf(
					/* translators: $1 is the name of the folder. $2 is the archive name.*/
						esc_html__(
							'Folder path "%1$s" is too long to be saved correctly in %2$s archive!',
							'backwpup'
						),
						$name_in_archive,
						$this->method
					),
					E_USER_WARNING
				);
			}
		}

		$file_stat = @stat( $folder_name );
		// Retrieve owner and group for the file.
		list( $owner, $group ) = $this->posix_getpwuid( $file_stat['uid'], $file_stat['gid'] );

		// Generate the TAR header for this file
		$header = $this->make_tar_headers(
			$tar_filename,
			$file_stat['mode'],
			$file_stat['uid'],
			$file_stat['gid'],
			$file_stat['size'],
			$file_stat['mtime'],
			5,
			$owner,
			$group,
			$tar_filename_prefix
		);

		$this->fwrite( $header );

		return true;
	}

	/**
	 * Check Archive File size
	 *
	 * @param string $file_to_add THe file to check
	 *
	 * @return bool True if the file size is less than PHP_INT_MAX false otherwise.
	 */
	public function check_archive_filesize( $file_to_add = '' ) {

		$file_to_add_size = 0;

		if ( ! empty( $file_to_add ) ) {
			$file_to_add_size = filesize( $file_to_add );

			if ( $file_to_add_size === false ) {
				$file_to_add_size = 0;
			}
		}

		if ( is_resource( $this->filehandler ) ) {
			$stats        = fstat( $this->filehandler );
			$archive_size = $stats['size'];
		} else {
			$archive_size = filesize( $this->file );
			if ( $archive_size === false ) {
				$archive_size = PHP_INT_MAX;
			}
		}

		$archive_size = $archive_size + $file_to_add_size;
		if ( $archive_size >= PHP_INT_MAX ) {
			trigger_error(
				sprintf(
					esc_html__(
						'If %s will be added to your backup archive, the archive will be too large for operations with this PHP Version. You might want to consider splitting the backup job in multiple jobs with less files each.',
						'backwpup'
					),
					$file_to_add
				),
				E_USER_ERROR
			);

			return false;
		}

		return true;
	}

	/**
	 * Make Tar Headers
	 *
	 * @param string  $name     The name of the file or directory. Known as Item.
	 * @param string  $mode     The permissions for the item.
	 * @param integer $uid      The owner ID.
	 * @param integer $gid      The group ID.
	 * @param integer $size     The size of the item.
	 * @param integer $mtime    The time of the last modification.
	 * @param integer $typeflag The type of the item. 0 for File and 5 for Directory.
	 * @param string  $owner    The owner Name.
	 * @param string  $group    The group Name.
	 * @param string  $prefix   The item prefix.
	 *
	 * @return mixed|string
	 */
	private function make_tar_headers( $name, $mode, $uid, $gid, $size, $mtime, $typeflag, $owner, $group, $prefix ) {

		// Generate the TAR header for this file
		$chunk = pack(
			"a100a8a8a8a12a12a8a1a100a6a2a32a32a8a8a155a12",
			$name, //name of file  100
			sprintf( "%07o", $mode ), //file mode  8
			sprintf( "%07o", $uid ), //owner user ID  8
			sprintf( "%07o", $gid ), //owner group ID  8
			sprintf( "%011o", $size ), //length of file in bytes  12
			sprintf( "%011o", $mtime ), //modify time of file  12
			"        ", //checksum for header  8
			$typeflag, //type of file  0 or null = File, 5=Dir
			"", //name of linked file  100
			"ustar", //USTAR indicator  6
			"00", //USTAR version  2
			$owner, //owner user name 32
			$group, //owner group name 32
			"", //device major number 8
			"", //device minor number 8
			$prefix, //prefix for file name 155
			""
		); //fill block 12

		// Computes the unsigned Checksum of a file's header
		$checksum = 0;
		for ( $i = 0; $i < 512; $i ++ ) {
			$checksum += ord( substr( $chunk, $i, 1 ) );
		}

		$checksum = pack( "a8", sprintf( "%07o", $checksum ) );
		$chunk    = substr_replace( $chunk, $checksum, 148, 8 );

		return $chunk;
	}

	/**
	 * Posix Get PW ID
	 *
	 * @param integer $uid The user ID.
	 * @param integer $gid The group ID.
	 *
	 * @return array The owner and group in posix format
	 */
	private function posix_getpwuid( $uid, $gid ) {

		// Set file user/group name if linux.
		$owner = esc_html__( 'Unknown', 'backwpup' );
		$group = esc_html__( 'Unknown', 'backwpup' );

		if ( function_exists( 'posix_getpwuid' ) ) {
			$info  = posix_getpwuid( $uid );
			$owner = $info['name'];
			$info  = posix_getgrgid( $gid );
			$group = $info['name'];
		}

		return array(
			$owner,
			$group,
		);
	}

	/**
	 * Fopen
	 *
	 * @param string $filename The file to open in mode.
	 * @param string $mode     The mode to open the file.
	 *
	 * @return bool|resource The resources or false if file cannot be opened.
	 */
	private function fopen( $filename, $mode ) {

		$fd = fopen( $filename, $mode );

		if ( ! $fd ) {
			trigger_error(
				sprintf(
				/* translators: $1 is the filename to add into the archive. */
					esc_html__( 'Cannot open source file %s.', 'backwpup' ),
					$filename
				),
				E_USER_WARNING
			);
		}

		return $fd;
	}

	/**
	 * Write Content in File
	 *
	 * @param string $content The content to write into the file.
	 *
	 * @return int The number of bit wrote into the file.
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

		return (int) fwrite( $this->filehandler, $content );
	}

	/**
	 * Close file handler
	 *
	 * @return void
	 */
	private function fclose() {

		fclose( $this->filehandler );
	}
}
