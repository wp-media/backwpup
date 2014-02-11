<?php
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
	 * @var string Method off compression Methods are ZipArchive, PclZip, Tar, TarGz, TarBz2, gz, bz2
	 */
	private $method = '';

	/**
	 * Open handel for files.
	 */
	private $filehandel = '';

	/**
	 * class handel for ZipArchive.
	 *
	 * @var ZipArchive
	 */
	private $ziparchive = NULL;

	/**
	 * class handel for PclZip.
	 *
	 * @var PclZip
	 */
	private $pclzip = NULL;

	/**
	 * class handel for PclZip.
	 *
	 * @var array()
	 */
	private $pclzip_file_list = array();

	/**
	 * Saved encoding will restored on __destruct
	 *
	 * @var string
	 */
	private $previous_encoding = '';

	/**
	 * File cont off added files to handel somethings that depends on it
	 *
	 * @var int number of files added
	 */
	private $file_count = 0;

	/**
	 * Set archive Parameter
	 *
	 * @param $file string File with full path of the archive
	 * @throws BackWPup_Create_Archive_Exception
	 */
	public function __construct( $file ) {

		//check param
		if ( empty( $file ) ) {
			throw new BackWPup_Create_Archive_Exception(  __( 'The file name of an archive cannot be empty.', 'backwpup' ) );
		}

		//set file
		$this->file = trim( $file );

		//check folder can used
		if ( ! is_dir( dirname( $this->file ) ) ||! is_writable( dirname( $this->file ) ) ) {
			throw new BackWPup_Create_Archive_Exception( sprintf( _x( 'Folder %s for archive not found','%s = Folder name', 'backwpup' ), dirname( $this->file ) ) );
		}

		//set and check method and get open handle
		if ( strtolower( substr( $this->file, -7 ) ) == '.tar.gz' ) {
			if ( ! function_exists( 'gzencode' ) ) {
				throw new BackWPup_Create_Archive_Exception( __( 'Functions for gz compression not available', 'backwpup' ) );
			}
			$this->method = 'TarGz';
			$this->filehandel = fopen( substr( $this->file, 0, -3 ), 'ab' );
		}
		elseif ( strtolower( substr( $this->file, -8 ) ) == '.tar.bz2' ) {
			if ( ! function_exists( 'bzcompress' ) ) {
				throw new BackWPup_Create_Archive_Exception( __( 'Functions for bz2 compression not available', 'backwpup' ) );
			}
			$this->method = 'TarBz2';
			$this->filehandel = fopen( substr( $this->file, 0, -4 ), 'ab');
		}
		elseif ( strtolower( substr( $this->file, -4 ) ) == '.tar' ) {
			$this->method = 'Tar';
			$this->filehandel = fopen( $this->file, 'ab');
		}
		elseif ( strtolower( substr( $this->file, -4 ) ) == '.zip' ) {
			$this->method = get_site_option( 'backwpup_cfg_jobziparchivemethod');
			//check and set method
			if ( empty( $this->method ) || ( $this->method != 'ZipArchive' && $this->method != 'PclZip' ) ) {
				$this->method = 'ZipArchive';
			}
			if ( ! class_exists( 'ZipArchive' ) ) {
				$this->method = 'PclZip';
			}
			//open classes
			if ( $this->get_method() == 'ZipArchive' ) {
				$this->ziparchive = new ZipArchive();
				$ziparchive_open = $this->ziparchive->open( $this->file, ZipArchive::CREATE );
				if ( $ziparchive_open !== TRUE ) {
					$this->ziparchive_status( $ziparchive_open );
					throw new BackWPup_Create_Archive_Exception( sprintf( _x( 'Cannot create zip archive: %d','ZipArchive open() result', 'backwpup' ), $ziparchive_open ) );
				}
			}
			if ( $this->get_method() == 'PclZip' && ! function_exists( 'gzencode' ) ) {
				throw new BackWPup_Create_Archive_Exception( __( 'Functions for gz compression not available', 'backwpup' ) );
			}
			if( $this->get_method() == 'PclZip' ) {
				$this->method = 'PclZip';
				if ( ini_get( 'mbstring.func_overload' ) && function_exists( 'mb_internal_encoding' ) ) {
					$this->previous_encoding = mb_internal_encoding();
					mb_internal_encoding( 'ISO-8859-1' );
				}
				if ( ! defined('PCLZIP_TEMPORARY_DIR') ) {
					define( 'PCLZIP_TEMPORARY_DIR', BackWPup::get_plugin_data( 'TEMP' ) );
				}
				require_once ABSPATH . 'wp-admin/includes/class-pclzip.php';
				$this->pclzip = new PclZip( $this->file );
			}
		}
		elseif ( strtolower( substr( $this->file, -3 ) ) == '.gz' ) {
			if ( ! function_exists( 'gzencode' ) )
				throw new BackWPup_Create_Archive_Exception( __( 'Functions for gz compression not available', 'backwpup' ) );
			$this->method = 'gz';
			$this->filehandel = fopen( 'compress.zlib://' . $this->file, 'wb');
		}
		elseif ( strtolower( substr( $this->file, -4 ) ) == '.bz2' ) {
			if ( ! function_exists( 'bzcompress' ) ) {
				throw new BackWPup_Create_Archive_Exception( __( 'Functions for bz2 compression not available', 'backwpup' ) );
			}
			$this->method = 'bz2';
			$this->filehandel = fopen( 'compress.bzip2://' . $this->file, 'w');
		}
		else {
			throw new BackWPup_Create_Archive_Exception( sprintf( _x( 'Method to archive file %s not detected','%s = file name', 'backwpup' ), basename( $this->file ) ) );
		}

		//check file handle
		if ( ! empty( $this->filehandel ) && ! is_resource( $this->filehandel ) ) {
			throw new BackWPup_Create_Archive_Exception( __( 'Cannot open archive file', 'backwpup' ) );
		}

	}


	/**
	 * Closes open archive on shutdown.
	 */
	public function __destruct() {

		//set encoding back
		if ( ! empty( $this->previous_encoding ) ) {
			mb_internal_encoding( $this->previous_encoding );
		}

		//close PclZip Class
		if ( is_object( $this->pclzip ) ) {
			if ( count( $this->pclzip_file_list ) > 0 ) {
				if ( 0 == $this->pclzip->add( $this->pclzip_file_list ) ) {
					trigger_error( sprintf( __( 'PclZip archive add error: %s', 'backwpup' ), $this->pclzip->errorInfo( TRUE ) ), E_USER_ERROR );
				}
			}
			unset( $this->pclzip );
		}

		//close ZipArchive Class
		if ( is_object( $this->ziparchive ) ) {
			$this->ziparchive_status( $this->ziparchive->status );
			$this->ziparchive->close();
			unset( $this->ziparchive );
			$this->ziparchive_delete_temp_files();
		}

		//close file if open
		if ( is_resource( $this->filehandel ) ) {
			fclose( $this->filehandel );
		}
	}

	/*
	 * Closing the archive
	 */
	public function close() {

		//write tar file end
		if ( in_array( $this->get_method(), array( 'Tar', 'TarGz', 'TarBz2' ) ) ) {
			fwrite( $this->filehandel, pack( "a1024", "" ) );
		}

		if ( $this->get_method() == 'TarGz' ) {
			fclose( $this->filehandel );
			$this->filehandel = fopen( 'compress.zlib://' . $this->file, 'wb' );
			$fd = fopen( substr( $this->file, 0, -3 ), 'rb' );
			while ( ! feof( $fd ) ) {
				fwrite( $this->filehandel, fread( $fd, 8192 ) );
			}
			fclose( $fd );
			unlink( substr( $this->file, 0, -3 ) );
		}

		if ( $this->get_method() == 'TarBz2' ) {
			fclose( $this->filehandel );
			$this->filehandel = fopen( 'compress.bzip2://' . $this->file, 'wb' );
			$fd = fopen( substr( $this->file, 0, -4 ), 'rb' );
			while ( ! feof( $fd ) ) {
				fwrite( $this->filehandel, fread( $fd, 8192 ) );
			}
			fclose( $fd );
			unlink( substr( $this->file, 0, -4 ) );
		}

	}

	/**
	 * Get method that the archive uses
	 *
	 * @return string of compression method
	 */
	public function get_method() {

		return $this->method;
	}


	/**
	 * Adds a file to Archive
	 *
	 * @param $file_name       string
	 * @param $name_in_archive string
	 * @return bool Add worked or not
	 * @throws BackWPup_Create_Archive_Exception
	 */
	public function add_file( $file_name, $name_in_archive = '' ) {

		$file_name = trim( $file_name );

	    //check param
		if ( empty( $file_name ) ) {
			trigger_error( __( 'File name cannot be empty', 'backwpup' ), E_USER_WARNING );
			return FALSE;
		}

		if ( ! is_readable( $file_name ) ) {
			trigger_error( sprintf( _x( 'File %s does not exist or is not readable', 'File to add to archive', 'backwpup' ), $file_name ), E_USER_WARNING );
			return FALSE;
		}

		if ( empty( $name_in_archive ) )
			$name_in_archive = $file_name;

		//remove reserved chars
		$name_in_archive = str_replace( array("?", "[", "]", "\\", "=", "<", ">", ":", ";", ",", "'", "\"", "&", "$", "#", "*", "(", ")", "|", "~", "`", "!", "{", "}", chr(0)) , '', $name_in_archive );

		switch ( $this->get_method() ) {
			case 'gz':
				if ( $this->file_count > 0 ) {
					trigger_error( __( 'This archive method can only add one file', 'backwpup' ), E_USER_WARNING );
					return FALSE;
				}
				//add file to archive
				if ( ! ( $fd = fopen( $file_name, 'rb' ) ) ) {
					trigger_error( sprintf( __( 'Cannot open source file %s to archive', 'backwpup' ), $file_name ), E_USER_WARNING );
					return FALSE;
				}
				while ( ! feof( $fd ) ) {
					fwrite( $this->filehandel, fread( $fd, 8192 ) );
				}
				fclose( $fd );
				break;
			case 'bz':
				if ( $this->file_count > 0 ) {
					trigger_error( __( 'This archive method can only add one file', 'backwpup' ), E_USER_WARNING );
					return FALSE;
				}
				//add file to archive
				if ( ! ( $fd = fopen( $file_name, 'rb' ) ) ) {
					trigger_error( sprintf( __( 'Cannot open source file %s to archive', 'backwpup' ), $file_name ), E_USER_WARNING );
					return FALSE;
				}
				while ( ! feof( $fd ) ) {
					fwrite( $this->filehandel, bzcompress( fread( $fd, 8192 ) ) );
				}
				fclose( $fd );
				break;
			case 'Tar':
			case 'TarGz':
			case 'TarBz2':
				if ( ! $this->tar_file( $file_name, $name_in_archive ) ) {
					return FALSE;
				}
				break;
			case 'ZipArchive':
				//close and reopen, all added files are open on fs
				if ( $this->file_count >= 20 ) { //35 works with PHP 5.2.4 on win
					$this->ziparchive_status( $this->ziparchive->status );
					$this->ziparchive->close();
					$this->ziparchive_delete_temp_files();
					$ziparchive_open = $this->ziparchive->open( $this->file, ZipArchive::CREATE );
					if ( $ziparchive_open !== TRUE ) {
						$this->ziparchive_status( $ziparchive_open );
						return FALSE;
					}
					$this->file_count = 0;
				}
				if ( ! $this->ziparchive->addFile( $file_name, $name_in_archive ) ) {
					trigger_error( sprintf( __( 'Cannot add "%s" to zip archive!', 'backwpup' ), $name_in_archive ), E_USER_ERROR );
					return FALSE;
				}
				break;
			case 'PclZip':
				$this->pclzip_file_list[] = array( PCLZIP_ATT_FILE_NAME => $file_name, PCLZIP_ATT_FILE_NEW_FULL_NAME => $name_in_archive );
				if ( count( $this->pclzip_file_list ) >= 100 ) {
					if ( 0 == $this->pclzip->add( $this->pclzip_file_list ) ) {
						trigger_error( sprintf( __( 'PclZip archive add error: %s', 'backwpup' ), $this->pclzip->errorInfo( TRUE ) ), E_USER_ERROR );
						return FALSE;
					}
					$this->pclzip_file_list = array();
				}
				break;
		}

		$this->file_count++;

		return TRUE;
	}

	/**
	 * Add a empty Folder to archive
	 *
	 * @param        $folder_name string Name of folder to add to archive
	 * @param string $name_in_archive
	 * @throws BackWPup_Create_Archive_Exception
	 * @return bool
	 */
	public function add_empty_folder( $folder_name, $name_in_archive = '' ) {

		$folder_name = trim( $folder_name );

		//check param
		if ( empty( $folder_name ) ) {
			trigger_error( __( 'Folder name cannot be empty', 'backwpup' ), E_USER_WARNING );
			return FALSE;
		}

		if ( ! is_dir( $folder_name ) || ! is_readable( $folder_name ) ) {
			trigger_error( sprintf( _x( 'Folder %s does not exist or is not readable', 'Folder path to add to archive', 'backwpup' ), $folder_name ), E_USER_WARNING );
			return FALSE;
		}

		if ( empty( $name_in_archive ) ) {
			return FALSE;
		}

		//remove reserved chars
		$name_in_archive = str_replace( array("?", "[", "]", "\\", "=", "<", ">", ":", ";", ",", "'", "\"", "&", "$", "#", "*", "(", ")", "|", "~", "`", "!", "{", "}", chr(0)) , '', $name_in_archive );

		switch ( $this->get_method() ) {
			case 'gz':
				trigger_error( __( 'This archive method can only add one file', 'backwpup' ), E_USER_ERROR );
				return FALSE;
				break;
			case 'bz':
				trigger_error( __( 'This archive method can only add one file', 'backwpup' ), E_USER_ERROR );
				return FALSE;
				break;
			case 'Tar':
			case 'TarGz':
			case 'TarBz2':
				if ( ! $this->tar_empty_folder( $folder_name, $name_in_archive ) );
					return FALSE;
				break;
			case 'ZipArchive':
				if ( ! $this->ziparchive->addEmptyDir( $name_in_archive ) ) {
					trigger_error( sprintf( __( 'Cannot add "%s" to zip archive!', 'backwpup' ), $name_in_archive ), E_USER_WARNING );
					return FALSE;
				}
				break;
			case 'PclZip':
				return TRUE;
				break;
		}

		return TRUE;
	}

	/**
	 * Output status of ZipArchive
	 *
	 * @param $code int ZipArchive Error code
	 * @return bool
	 */
	private function ziparchive_status( $code ) {

		if ( $code == 0 )
			return TRUE;

		//define error messages
		$zip_errors[ ZipArchive::ER_MULTIDISK ] =  __( '(ER_MULTIDISK) Multi-disk zip archives not supported', 'backwpup' );
		$zip_errors[ ZipArchive::ER_RENAME ] =  __( '(ER_RENAME) Renaming temporary file failed', 'backwpup' );
		$zip_errors[ ZipArchive::ER_CLOSE ] =  __( '(ER_CLOSE) Closing zip archive failed', 'backwpup' );
		$zip_errors[ ZipArchive::ER_SEEK ] =  __( '(ER_SEEK) Seek error', 'backwpup' );
		$zip_errors[ ZipArchive::ER_READ ] = __( '(ER_READ) Read error', 'backwpup' );
		$zip_errors[ ZipArchive::ER_WRITE ] = __( '(ER_WRITE) Write error', 'backwpup' );
		$zip_errors[ ZipArchive::ER_CRC ] = __( '(ER_CRC) CRC error', 'backwpup' );
		$zip_errors[ ZipArchive::ER_ZIPCLOSED ] = __( '(ER_ZIPCLOSED) Containing zip archive was closed', 'backwpup' );
		$zip_errors[ ZipArchive::ER_NOENT ] = __( '(ER_NOENT) No such file', 'backwpup' );
		$zip_errors[ ZipArchive::ER_EXISTS ] = __( '(ER_EXISTS) File already exists', 'backwpup' );
		$zip_errors[ ZipArchive::ER_OPEN ] = __( '(ER_OPEN) Can\'t open file', 'backwpup' );
		$zip_errors[ ZipArchive::ER_TMPOPEN ] = __( '(ER_TMPOPEN) Failure to create temporary file', 'backwpup' );
		$zip_errors[ ZipArchive::ER_ZLIB ] = __( '(ER_ZLIB) Zlib error', 'backwpup' );
		$zip_errors[ ZipArchive::ER_MEMORY ] = __( '(ER_MEMORY) Malloc failure', 'backwpup' );
		$zip_errors[ ZipArchive::ER_MULTIDISK ] = __( '(ER_CHANGED) Entry has been changed', 'backwpup' );
		$zip_errors[ ZipArchive::ER_CHANGED ] = __( '(ER_COMPNOTSUPP) Compression method not supported', 'backwpup' );
		$zip_errors[ ZipArchive::ER_EOF ] = __( '(ER_EOF) Premature EOF', 'backwpup' );
		$zip_errors[ ZipArchive::ER_INVAL ] = __( '(ER_INVAL) Invalid argument', 'backwpup' );
		$zip_errors[ ZipArchive::ER_NOZIP ] = __( '(ER_NOZIP) Not a zip archive', 'backwpup' );
		$zip_errors[ ZipArchive::ER_INTERNAL ] = __( '(ER_INTERNAL) Internal error', 'backwpup' );
		$zip_errors[ ZipArchive::ER_INCONS ] = __( '(ER_INCONS) Zip archive inconsistent', 'backwpup' );
		$zip_errors[ ZipArchive::ER_REMOVE ] = __( '(ER_REMOVE) Can\'t remove file', 'backwpup' );
		$zip_errors[ ZipArchive::ER_DELETED ] = __( '(ER_DELETED) Entry has been deleted', 'backwpup' );

		//ste error message
		$zip_error = $code;
		if ( isset( $zip_errors[ $zip_error ] ) ) {
			$zip_error = $zip_errors[ $zip_error ];
		}

		trigger_error( sprintf( _x( 'ZipArchive returns status: %s','Text of ZipArchive status Message', 'backwpup' ), $zip_error ), E_USER_ERROR );
		return FALSE;
	}

	/**
	 * Tar a file to archive
	 */
	private function tar_file( $file_name, $name_in_archive ) {

		//split filename larger than 100 chars
		if ( strlen( $name_in_archive ) <= 100 ) {
			$filename        = $name_in_archive;
			$filename_prefix = "";
		}
		else {
			$filename_offset = strlen( $name_in_archive ) - 100;
			$split_pos       = strpos( $name_in_archive, '/', $filename_offset );
			$filename        = substr( $name_in_archive, $split_pos + 1 );
			$filename_prefix = substr( $name_in_archive, 0, $split_pos );
			if ( strlen( $filename ) > 100 )
				trigger_error( sprintf( __( 'File name "%1$s" is too long to be saved correctly in %2$s archive!', 'backwpup' ), $name_in_archive, $this->get_method() ), E_USER_WARNING );
			if ( strlen( $filename_prefix ) > 155 )
				trigger_error( sprintf( __( 'File path "%1$s" is too long to be saved correctly in %2$s archive!', 'backwpup' ), $name_in_archive, $this->get_method() ), E_USER_WARNING );
		}
		//get file stat
		$file_stat = @stat( $file_name );
		//open file
		if ( ! ( $fd = fopen( $file_name, 'rb' ) ) ) {
			trigger_error( sprintf( __( 'Cannot open source file %s to archive', 'backwpup' ), $file_name ), E_USER_WARNING );
			return FALSE;
		}
		//Set file user/group name if linux
		$fileowner = __( "Unknown", "backwpup" );
		$filegroup = __( "Unknown", "backwpup" );
		if ( function_exists( 'posix_getpwuid' ) ) {
			$info      = posix_getpwuid( $file_stat[ 'uid' ] );
			$fileowner = $info[ 'name' ];
			$info      = posix_getgrgid( $file_stat[ 'gid' ] );
			$filegroup = $info[ 'name' ];
		}
		// Generate the TAR header for this file
		$header = pack( "a100a8a8a8a12a12a8a1a100a6a2a32a32a8a8a155a12",
			$filename, //name of file  100
			sprintf( "%07o", $file_stat[ 'mode' ] ), //file mode  8
			sprintf( "%07o", $file_stat[ 'uid' ] ), //owner user ID  8
			sprintf( "%07o", $file_stat[ 'gid' ] ), //owner group ID  8
			sprintf( "%011o", $file_stat[ 'size' ] ), //length of file in bytes  12
			sprintf( "%011o", $file_stat[ 'mtime' ] ), //modify time of file  12
			"        ", //checksum for header  8
			0, //type of file  0 or null = File, 5=Dir
			"", //name of linked file  100
			"ustar", //USTAR indicator  6
			"00", //USTAR version  2
			$fileowner, //owner user name 32
			$filegroup, //owner group name 32
			"", //device major number 8
			"", //device minor number 8
			$filename_prefix, //prefix for file name 155
			"" ); //fill block 12

		// Computes the unsigned Checksum of a file's header
		$checksum = 0;
		for ( $i = 0; $i < 512; $i ++ )
			$checksum += ord( substr( $header, $i, 1 ) );

		$checksum = pack( "a8", sprintf( "%07o", $checksum ) );
		$header   = substr_replace( $header, $checksum, 148, 8 );
		//write header
		fwrite( $this->filehandel, $header );

		// read/write files in 512 bite Blocks
		while ( ! feof( $fd ) ) {
			$file_data = fread( $fd, 512 );
			if ( strlen( $file_data ) > 0 ) {
				fwrite( $this->filehandel, pack( "a512", $file_data ) );
			}
		}
		fclose( $fd );

		return TRUE;
	}


	/**
	 * Tar a empty Folder to archive
	 */
	private function tar_empty_folder( $folder_name, $name_in_archive ) {

		$name_in_archive = trailingslashit( $name_in_archive );

		//split filename larger than 100 chars
		if ( strlen( $name_in_archive ) <= 100 ) {
			$tar_filename        = $name_in_archive;
			$tar_filename_prefix = "";
		}
		else {
			$filename_offset = strlen( $name_in_archive ) - 100;
			$split_pos       = strpos( $name_in_archive, '/', $filename_offset );
			$tar_filename        = substr( $name_in_archive, $split_pos + 1 );
			$tar_filename_prefix = substr( $name_in_archive, 0, $split_pos );
			if ( strlen( $tar_filename ) > 100 )
				trigger_error( sprintf( __( 'Folder name "%1$s" is too long to be saved correctly in %2$s archive!', 'backwpup' ), $name_in_archive, $this->get_method() ), E_USER_WARNING );
			if ( strlen( $tar_filename_prefix ) > 155 )
				trigger_error( sprintf( __( 'Folder path "%1$s" is too long to be saved correctly in %2$s archive!', 'backwpup' ), $name_in_archive, $this->get_method() ), E_USER_WARNING );
		}
		//get file stat
		$file_stat = @stat( $folder_name );
		//Set file user/group name if linux
		$fileowner = __( "Unknown", "backwpup" );
		$filegroup = __( "Unknown", "backwpup" );
		if ( function_exists( 'posix_getpwuid' ) ) {
			$info      = posix_getpwuid( $file_stat[ 'uid' ] );
			$fileowner = $info[ 'name' ];
			$info      = posix_getgrgid( $file_stat[ 'gid' ] );
			$filegroup = $info[ 'name' ];
		}
		// Generate the TAR header for this file
		$header = pack( "a100a8a8a8a12a12a8a1a100a6a2a32a32a8a8a155a12",
			$tar_filename, //name of file  100
			sprintf( "%07o", $file_stat[ 'mode' ] ), //file mode  8
			sprintf( "%07o", $file_stat[ 'uid' ] ), //owner user ID  8
			sprintf( "%07o", $file_stat[ 'gid' ] ), //owner group ID  8
			sprintf( "%011o", 0 ), //length of file in bytes  12
			sprintf( "%011o", $file_stat[ 'mtime' ] ), //modify time of file  12
			"        ", //checksum for header  8
			5, //type of file  0 or null = File, 5=Dir
			"", //name of linked file  100
			"ustar", //USTAR indicator  6
			"00", //USTAR version  2
			$fileowner, //owner user name 32
			$filegroup, //owner group name 32
			"", //device major number 8
			"", //device minor number 8
			$tar_filename_prefix, //prefix for file name 155
			"" ); //fill block 12

		// Computes the unsigned Checksum of a file's header
		$checksum = 0;
		for ( $i = 0; $i < 512; $i ++ ) {
			$checksum += ord( substr( $header, $i, 1 ) );
		}

		$checksum = pack( "a8", sprintf( "%07o", $checksum ) );
		$header   = substr_replace( $header, $checksum, 148, 8 );
		//write header
		fwrite( $this->filehandel, $header );

		return TRUE;
	}

	/**
	 * Deleting Temporary files after Zip file generation with zipArchive
	 */
	private function ziparchive_delete_temp_files() {

		if ( $this->get_method() != 'ZipArchive' ) {
			return;
		}

		usleep( 250000 );

		$temp_files = glob( $this->file . '.*' );

		if ( empty( $temp_files ) ) {
			return;
		}

		foreach( $temp_files AS $temp_file ) {
			@unlink( $temp_file );
		}
	}
}

/**
 * Exception Handler
 */
class BackWPup_Create_Archive_Exception extends Exception { }