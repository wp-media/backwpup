<?php
class BackWPup_Destination_Ftp_Type_Ftp implements BackWPup_Destination_Ftp_Type {

	/**
	 * FTP connection instance.
	 *
	 * @var resource
	 */
	private $ftp_conn_id;

	/**
	 * FTP connection URL.
	 *
	 * @var string
	 */
	public string $connection_url = '';

	/**
	 * Logger
	 *
	 * @var callable
	 */
	private $logger;

	/**
	 * FTP return code for ftp_nb_fput(), ftp_nb_continue().
	 *
	 * @var int
	 */
	private $ret = FTP_FAILED;

	/**
	 * FTP server features
	 *
	 * @var array
	 */
	private $feat = [];

	/**
	 * BackWPup_Destination_Ftp_Type_Ftp constructor.
	 *
	 * @param callable|null $logger
	 */
	public function __construct( $logger = null ) {
		$this->logger = $logger;
	}

	/**
	 * Log message.
	 *
	 * @param string $message Message.
	 * @param int    $error_level Error level.
	 */
	private function log( string $message, int $error_level = E_USER_NOTICE ) {
		if ( $this->logger ) {
			call_user_func( $this->logger, $message, $error_level );
		}
	}

	/**
	 * Connect to the FTP server.
	 *
	 * @param string $user Username.
	 * @param string $password Password.
	 * @param string $host Hostname.
	 * @param array  $args Arguments.
	 *
	 * @return bool connected and logged in.
	 * @throws BackWPup_Destination_Ftp_Type_Exception On connect error.
	 */
	public function connect( string $user, string $password, string $host, array $args = [] ): bool {

		if ( ! isset( $args['port'] ) ) {
			$args['port'] = 21;
		}

		if ( ! isset( $args['timeout'] ) ) {
			$args['timeout'] = 90;
		}

		if ( ! isset( $args['pasv'] ) ) {
			$args['pasv'] = true;
		}

		if ( ! empty( $args['ssl'] ) ) {
			if ( ! function_exists( 'ftp_ssl_connect' ) ) {
				throw new BackWPup_Destination_Ftp_Type_Exception(
					esc_html__( 'PHP function to connect with explicit SSL-FTP to server does not exist!', 'backwpup' )
				);
			}
			$this->ftp_conn_id = ftp_ssl_connect(
				$host,
				$args['port'],
				$args['timeout']
			);
			if ( $this->ftp_conn_id ) {
				$this->log(
					sprintf(
						// translators: %1$s: FTP server host, %2$d: FTP server port.
						__( 'Connected via explicit SSL-FTP to server: %1$s:%2$d', 'backwpup' ),
						esc_html( $host ),
						esc_html( $args['port'] )
					)
				);
			} else {
				throw new BackWPup_Destination_Ftp_Type_Exception(
					sprintf(
						// translators: %1$s: FTP server host, %2$d: FTP server port.
						esc_html__( 'Cannot connect via explicit SSL-FTP to %1$s:%2$d', 'backwpup' ),
						esc_html( $host ),
						esc_html( $args['port'] )
					),
				);
			}
			$this->connection_url = 'ftps://' . rawurlencode( $host ) . ':' . $args['port'];
		} else {
			$this->ftp_conn_id = ftp_connect(
				$host,
				$args['port'],
				$args['timeout']
			);
			if ( $this->ftp_conn_id ) {
				$this->log(
					sprintf(
						// translators: %1$s: FTP server host, %2$d: FTP server port.
							__( 'Connected to FTP server: %1$s:%2$d', 'backwpup' ),
							esc_html( $host ),
							esc_html( $args['port'] )
						)
				);
			} else {
				throw new BackWPup_Destination_Ftp_Type_Exception(
					sprintf(
						// translators: %1$s: FTP server host, %2$d: FTP server port.
						esc_html__( 'Cannot connect to %1$s:%2$d', 'backwpup' ),
						esc_html( $host ),
						esc_html( $args['port'] )
					)
				);
			}
			$this->connection_url = 'ftp://' . rawurlencode( $host ) . ':' . $args['port'];
		}

		// translators: %s: FTP client command.
		$this->log( sprintf( __( 'FTP client command: %s', 'backwpup' ), 'HELP' ) );
		$response     = ftp_raw( $this->ftp_conn_id, 'HELP' );
		$this->feat[] = 'CWD';
		$features     = explode( ' ', implode( ' ', $response ) );
		foreach ( $features as $feature ) {
			if ( 4 === strlen( $feature ) ) {
				$this->feat[] = strtoupper( $feature );
			}
		}
		// translators: %s: FTP server response.
		$this->log( sprintf( __( 'FTP server response: %s', 'backwpup' ), implode( ' ', $response ) ) );

		// translators: %s: FTP client command.
		$this->log( sprintf( __( 'FTP client command: %s', 'backwpup' ), 'USER ' . esc_html( $user ) ) );

		$login = @ftp_login( $this->ftp_conn_id, $user, $password ); //phpcs:ignore
		if ( $login ) {
			$this->log(
				sprintf(
					// translators: %s: FTP server response.
					__( 'FTP server response: %s', 'backwpup' ),
					'User ' . $user . ' logged in.'
				)
			);
		} else {
			// if PHP ftp login don't work use raw login.
			$return = ftp_raw( $this->ftp_conn_id, 'USER ' . $user );
			// translators: %s: FTP server response.
			$this->log( sprintf( __( 'FTP server reply: %s', 'backwpup' ), $return[0] ) );
			if ( substr( trim( $return[0] ), 0, 3 ) <= 400 ) {
				// translators: %s: FTP server response.
				$this->log( sprintf( __( 'FTP client command: %s', 'backwpup' ), 'PASS *******' ) );
				$return = ftp_raw( $this->ftp_conn_id, 'PASS ' . $password );
				if ( substr( trim( $return[0] ), 0, 3 ) <= 400 ) {
					// translators: %s: FTP server response.
					$this->log( sprintf( __( 'FTP server reply: %s', 'backwpup' ), $return[0] ) );
				}
				throw new BackWPup_Destination_Ftp_Type_Exception( esc_html( $return[0] ) );
			}
		}

		// The system type identifier of the remote FTP server.
		if ( in_array( 'SYST', $this->feat, true ) ) {
			// translators: %s: FTP client command.
			$this->log( sprintf( __( 'FTP client command: %s', 'backwpup' ), 'SYST' ) );
			$sys_type = ftp_systype( $this->ftp_conn_id );
			if ( $sys_type ) {
				// translators: %s: FTP server response.
				$this->log( sprintf( __( 'FTP server reply: %s', 'backwpup' ), $sys_type ) );
			} else {
				throw new BackWPup_Destination_Ftp_Type_Exception(
					esc_html__( 'Error getting SYSTYPE', 'backwpup' )
				);
			}
		}

		// Set passive mode.
		// translators: %s: FTP client command.
		$this->log( sprintf( __( 'FTP client command: %s', 'backwpup' ), 'PASV' ) );
		if ( $args['pasv'] ) {
			ftp_set_option( $this->ftp_conn_id, FTP_USEPASVADDRESS, wpm_apply_filters_typed( 'boolean', 'backwpup_ftp_use_passive_address', true ) );
			if ( ftp_pasv( $this->ftp_conn_id, true ) ) {
				$this->log(
					// translators: %s: FTP server reply.
					sprintf( __( 'FTP server reply: %s', 'backwpup' ), __( 'Entering passive mode', 'backwpup' ) ),
				);
			} else {
				$this->log(
				// translators: %s: FTP server reply.
					sprintf( __( 'FTP server reply: %s', 'backwpup' ), __( 'Cannot enter passive mode', 'backwpup' ) ),
					E_USER_WARNING
				);
			}
		} elseif ( ftp_pasv( $this->ftp_conn_id, false ) ) {
				$this->log(
					// translators: %s: FTP server reply.
					sprintf( __( 'FTP server reply: %s', 'backwpup' ), __( 'Entering normal mode', 'backwpup' ) )
				);
		} else {
			$this->log(
				// translators: %s: FTP server reply.
				sprintf( __( 'FTP server reply: %s', 'backwpup' ), __( 'Cannot enter normal mode', 'backwpup' ) ),
				E_USER_WARNING
			);
		}

		return true;
	}

	/**
	 * Disconnect from the FTP server.
	 *
	 * @return void
	 */
	public function disconnect() {
		if ( $this->ftp_conn_id ) {
			ftp_close( $this->ftp_conn_id );
		}
	}

	/**
	 * Upload a file chunk to the FTP server.
	 *
	 * @param string   $remote_filename Remote filename.
	 * @param resource $local_file Local file resource pointer.
	 *
	 * @return bool more to upload.
	 * @throws BackWPup_Destination_Ftp_Type_Exception On upload error.
	 */
	public function upload( string $remote_filename, $local_file ): bool {

		if ( feof( $local_file ) ) {
			return false;
		}
		if ( FTP_MOREDATA === $this->ret ) {
			$this->ret = ftp_nb_continue( $this->ftp_conn_id );
		} else {
			$this->ret = ftp_nb_fput( $this->ftp_conn_id, $remote_filename, $local_file, FTP_BINARY, ftell( $local_file ) );
		}
		if ( FTP_FINISHED === $this->ret ) {
			return false;
		}
		if ( FTP_MOREDATA === $this->ret ) {
			return true;
		}
		if ( FTP_FAILED === $this->ret ) {
			throw new BackWPup_Destination_Ftp_Type_Exception(
				esc_html__( 'FTP server response: Failed to upload file.', 'backwpup' ),
			);
		}
		return false;
	}

	/**
	 * Download a file chunk from the FTP server.
	 *
	 * @param string $remote_filename Remote filename.
	 *
	 * @return int file size.
	 */
	public function size( string $remote_filename ): int {
		$size = ftp_size( $this->ftp_conn_id, $remote_filename );
		if ( $size < 0 ) {
			return 0;
		}
		return $size;
	}

	/**
	 * Get the current working directory.
	 *
	 * @param string $path Directory path.
	 *
	 * @return string Current working directory.
	 */
	public function chdir( string $path ): string {

		if ( ! $path || substr( $path, 0, 1 ) !== '/' ) {
			$current_dir = trailingslashit( ftp_pwd( $this->ftp_conn_id ) );
			$path        = trailingslashit( $current_dir . $path );
		}

		if ( '/' !== $path ) {
			@ftp_chdir( $this->ftp_conn_id, '/' ); //phpcs:ignore
			$ftpdirs = explode( '/', trim( $path, '/' ) );

			foreach ( $ftpdirs as $ftpdir ) {
				if ( empty( $ftpdir ) ) {
					continue;
				}

				if ( ! @ftp_chdir( $this->ftp_conn_id, $ftpdir ) ) {  //phpcs:ignore
					if ( ! $this->mkdir( $ftpdir ) ) {
						return false;
					}

					ftp_chdir( $this->ftp_conn_id, $ftpdir );
				}
			}
		}

		return ftp_pwd( $this->ftp_conn_id );
	}

	/**
	 * Create a directory on the FTP server.
	 *
	 * @param string $dir Directory path.
	 *
	 * @return bool success
	 */
	public function mkdir( string $dir ): bool {
		// Try to create the directory.
		$response = (bool) ftp_mkdir( $this->ftp_conn_id, $dir );

		if ( ! $response ) {
			// Trying to set the parent directory permissions.
			$response = (bool) ftp_chmod( $this->ftp_conn_id, 0775, './' );

			if ( ! $response ) {
				$this->log(
					sprintf(
						// translators: %s: FTP Folder.
						esc_html__(
							'FTP Folder "%s" cannot be created! Parent directory may be not writable.',
							'backwpup'
						),
						$dir
					),
					E_USER_ERROR
				);

				return $response;
			}

			// Try to create the directory for the second time.
			$response = (bool) ftp_mkdir( $this->ftp_conn_id, $dir );

			if ( ! $response ) {
				$this->log(
					sprintf(
						// translators: %s: FTP Folder.
						esc_html__( 'FTP Folder "%s" cannot be created!', 'backwpup' ),
						$dir
					),
					E_USER_ERROR
				);

				return $response;
			}
		}

		$this->log(
			sprintf(
				// translators: %s: FTP Folder.
				esc_html__( 'FTP Folder "%s" created!', 'backwpup' ),
				$dir
			)
		);

		return $response;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param string $path
	 *
	 * @return bool
	 */
	public function delete( string $path ): bool {
		return ftp_delete( $this->ftp_conn_id, $path );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param string $path
	 *
	 * @return array
	 */
	public function list_files( string $path ): array {
		$files = [];
		if ( in_array( 'MLSD', $this->feat, true ) ) {
			$list = ftp_mlsd( $this->ftp_conn_id, $path );
		} else {
			$nlist = ftp_nlist( $this->ftp_conn_id, '.' );
			$list  = [];
			foreach ( $nlist as $file ) {
				if ( '.' === $file || '..' === $file ) {
					continue;
				}
				$time = ftp_mdtm( $this->ftp_conn_id, trailingslashit( $path ) . $file );
				if ( -1 === $time ) {
					continue;
				}
				$size = ftp_size( $this->ftp_conn_id, trailingslashit( $path ) . $file );
				if ( 0 >= $size ) {
					continue;
				}
				$list[] = [
					'name'   => $file,
					'type'   => 'file',
					'size'   => $size,
					'modify' => $time,
				];
			}
		}

		if ( ! $list ) {
			return $files;
		}
		foreach ( $list as $file ) {
			if ( 'file' !== $file['type'] ) {
				continue;
			}
			$index = $file['modify'];
			if ( $index < 0 ) {
				$index = $file['name'];
			}
			$files[ $index ]['file']     = trailingslashit( $path ) . $file['name'];
			$files[ $index ]['filename'] = $file['name'];
			$files[ $index ]['filesize'] = (int) $file['size'];
			$files[ $index ]['time']     = is_int( $file['modify'] ) ? $file['modify'] : strtotime( $file['modify'] );
		}
		return $files;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param string   $remote_filename Remote filename.
	 * @param resource $local_file Local file resource pointer.
	 * @param int      $offset Offset.
	 * @param int      $length Length.
	 *
	 * @throws BackWPup_Destination_Ftp_Type_Exception On download error.
	 */
	public function download( string $remote_filename, $local_file, int $offset = 0, int $length = 2097152 ): void {
		if ( FTP_FINISHED === $this->ret ) {
			return;
		}

		if ( FTP_MOREDATA !== $this->ret ) {
			$this->ret = ftp_nb_fget( $this->ftp_conn_id, $local_file, $remote_filename, FTP_BINARY, ftell( $local_file ) );
		}

		while ( FTP_MOREDATA === $this->ret ) {
			$this->ret         = ftp_nb_continue( $this->ftp_conn_id );
			$dl_current_offset = ftell( $local_file );
			if ( $dl_current_offset >= $offset + $length ) {
				break;
			}
		}

		if ( FTP_FAILED === $this->ret ) {
			throw new BackWPup_Destination_Ftp_Type_Exception(
				esc_html__( 'FTP server response: Failed to download file.', 'backwpup' ),
			);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function supports_appending(): bool {
		return in_array( 'RANG', $this->feat, true );
	}
}
