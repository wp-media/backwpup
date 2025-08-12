<?php

use Inpsyde\BackWPupShared\File\MimeTypeExtractor;

class BackWPup_Destination_SugarSync_API {

	/**
	 * Url for the sugarsync-api.
	 */
	public const API_URL = 'https://api.sugarsync.com';

	/**
	 * The folder to use for the API calls.
	 *
	 * @var string
	 */
	protected $folder = '';

	/**
	 * The encoding used for the XML.
	 *
	 * @var mixed|string
	 */
	protected $encoding = 'UTF-8';

	/**
	 * The refresh-token.
	 *
	 * @var string|null
	 */
	protected $refresh_token = '';

	/**
	 * The Auth-token.
	 *
	 * @var string
	 */
	protected $access_token = '';

	// class methods.

	/**
	 * Default constructor/Auth.
	 *
	 * @param string|null $refresh_token The refresh token to use for authentication.
	 */
	public function __construct( $refresh_token = null ) {
		// auth xml.
		$this->encoding = mb_internal_encoding();

		// get access token.
		if ( isset( $refresh_token ) && ! empty( $refresh_token ) ) {
			$this->refresh_token = $refresh_token;
			$this->get_access_token();
		}
	}

	/**
	 * Make the call.
	 *
	 * @param string $url the url to call.
	 * @param string $data the data to send, File on put, xml on post.
	 * @param string $method the method to use. Possible values are GET, POST, PUT, DELETE.
	 *
	 * @throws BackWPup_Destination_SugarSync_API_Exception When an error is thrown.
	 *
	 * @return string|SimpleXMLElement
	 *
	 * @internal param $string [optiona] $data            File on put, xml on post
	 * @internal param $string [optional] $method        The method to use. Possible values are GET, POST, PUT, DELETE.
	 */
	private function do_call( string $url, string $data = '', string $method = 'GET' ) {
		$datafilefd = null;
		// allowed methods.
		$allowed_methods = [ 'GET', 'POST', 'PUT', 'DELETE' ];

		// redefine.
		$url     = (string) $url;
		$method  = (string) $method;
		$headers = [];
		// validate method.
		if ( ! in_array( $method, $allowed_methods, true ) ) {
			throw new BackWPup_Destination_SugarSync_API_Exception( esc_html( 'Unknown method (' . $method . '). Allowed methods are: ' . implode( ', ', $allowed_methods ) ) );
		}

		// check auth token.
		if ( empty( $this->access_token ) ) {
			throw new BackWPup_Destination_SugarSync_API_Exception( esc_html( __( 'Auth Token not set correctly!', 'backwpup' ) ) );
		}
		$headers[] = 'Authorization: ' . $this->access_token;

		$headers[] = 'Expect:';

		// init.
		$curl = curl_init();
		// set options.
		curl_setopt( $curl, CURLOPT_URL, $url );
		curl_setopt( $curl, CURLOPT_USERAGENT, BackWPup::get_plugin_data( 'User-Agent' ) );
		if ( ini_get( 'open_basedir' ) === '' ) {
			curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, true );
		}
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		if ( BackWPup::get_plugin_data( 'cacert' ) ) {
			curl_setopt( $curl, CURLOPT_SSLVERSION, 1 );
			curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, true );
			curl_setopt( $curl, CURLOPT_CAINFO, BackWPup::get_plugin_data( 'cacert' ) );
			curl_setopt( $curl, CURLOPT_CAPATH, dirname( BackWPup::get_plugin_data( 'cacert' ) ) );
		} else {
			curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
		}

		if ( 'POST' === $method ) {
			$headers[] = 'Content-Type: application/xml; charset=UTF-8';
			curl_setopt( $curl, CURLOPT_POSTFIELDS, $data );
			curl_setopt( $curl, CURLOPT_POST, true );
			$headers[] = 'Content-Length: ' . strlen( $data );
		} elseif ( 'PUT' === $method ) {
			if ( is_readable( $data ) ) {
				$headers[]  = 'Content-Length: ' . filesize( $data );
				$datafilefd = fopen( $data, 'rb' ); //phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
				curl_setopt( $curl, CURLOPT_PUT, true );
				curl_setopt( $curl, CURLOPT_INFILE, $datafilefd );
				curl_setopt( $curl, CURLOPT_INFILESIZE, filesize( $data ) );
				curl_setopt( $curl, CURLOPT_READFUNCTION, [ BackWPup_Destination_SugarSync::$backwpup_job_object, 'curl_read_callback' ] );
			} else {
				throw new BackWPup_Destination_SugarSync_API_Exception( 'Is not a readable file:' . esc_html( $data ) );
			}
		} elseif ( 'DELETE' === $method ) {
			curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, 'DELETE' );
		} else {
			curl_setopt( $curl, CURLOPT_POST, false );
		}

		// set headers.
		curl_setopt( $curl, CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $curl, CURLINFO_HEADER_OUT, true );
		// execute.
		$response    = curl_exec( $curl );
		$curlgetinfo = curl_getinfo( $curl );

		// fetch curl errors.
		if ( curl_errno( $curl ) !== 0 ) { //phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_errno
			throw new BackWPup_Destination_SugarSync_API_Exception( 'cUrl Error: ' . esc_html( curl_error( $curl ) ) );
		}
		curl_close( $curl );
		if ( ! empty( $datafilefd ) && is_resource( $datafilefd ) ) {
			fclose( $datafilefd ); //phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		}

		if ( $curlgetinfo['http_code'] >= 200 && $curlgetinfo['http_code'] < 300 ) {
			if ( false !== stripos( $curlgetinfo['content_type'], 'xml' ) && ! empty( $response ) ) {
				return simplexml_load_string( $response );
			}

			return $response;
		}
		if ( 401 === $curlgetinfo['http_code'] ) {
			throw new BackWPup_Destination_SugarSync_API_Exception( 'Http Error: ' . esc_html( $curlgetinfo['http_code'] ) . ' Authorization required.' );
		}
		if ( 403 === $curlgetinfo['http_code'] ) {
			throw new BackWPup_Destination_SugarSync_API_Exception( 'Http Error: ' . esc_html( $curlgetinfo['http_code'] ) . ' (Forbidden)  Authentication failed.' );
		}
		if ( 404 === $curlgetinfo['http_code'] ) {
			throw new BackWPup_Destination_SugarSync_API_Exception( 'Http Error: ' . esc_html( $curlgetinfo['http_code'] ) . ' Not found' );
		}

		throw new BackWPup_Destination_SugarSync_API_Exception( 'Http Error: ' . esc_html( $curlgetinfo['http_code'] ) );
	}

	/**
	 * Get the access token.
	 *
	 * @return string The access token.
	 *
	 * @throws BackWPup_Destination_SugarSync_API_Exception When an error is thrown.
	 */
	private function get_access_token(): string {
		$auth  = '<?xml version="1.0" encoding="UTF-8" ?>';
		$auth .= '<tokenAuthRequest>';
		$auth .= '<accessKeyId>' . get_site_option( 'backwpup_cfg_sugarsynckey', base64_decode( 'TlRBek1EY3lOakV6TkRrMk1URXhNemM0TWpJ' ) ) . '</accessKeyId>'; //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$auth .= '<privateAccessKey>' . BackWPup_Encryption::decrypt( get_site_option( 'backwpup_cfg_sugarsyncsecret', base64_decode( 'TkRFd01UazRNVEpqTW1Ga05EaG1NR0k1TVRFNFpqa3lPR1V6WlRVMk1tTQ==' ) ) ) . '</privateAccessKey>'; //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$auth .= '<refreshToken>' . trim( $this->refresh_token ) . '</refreshToken>';
		$auth .= '</tokenAuthRequest>';
		// init.
		$curl = curl_init();
		// set options.
		curl_setopt( $curl, CURLOPT_URL, self::API_URL . '/authorization' );
		curl_setopt( $curl, CURLOPT_USERAGENT, BackWPup::get_plugin_data( 'User-Agent' ) );
		if ( ini_get( 'open_basedir' ) === '' ) {
			curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, true );
		}
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		if ( BackWPup::get_plugin_data( 'cacert' ) ) {
			curl_setopt( $curl, CURLOPT_SSLVERSION, 1 );
			curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, true );
			curl_setopt( $curl, CURLOPT_CAINFO, BackWPup::get_plugin_data( 'cacert' ) );
			curl_setopt( $curl, CURLOPT_CAPATH, dirname( BackWPup::get_plugin_data( 'cacert' ) ) );
		} else {
			curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
		}
		curl_setopt( $curl, CURLOPT_HEADER, true );
		curl_setopt( $curl, CURLOPT_HTTPHEADER, [ 'Content-Type: application/xml; charset=UTF-8', 'Content-Length: ' . strlen( $auth ) ] );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, $auth );
		curl_setopt( $curl, CURLOPT_POST, true );
		// execute.
		$response    = curl_exec( $curl );
		$curlgetinfo = curl_getinfo( $curl );
		// fetch curl errors.
		if ( curl_errno( $curl ) !== 0 ) { //phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_errno
			throw new BackWPup_Destination_SugarSync_API_Exception( 'cUrl Error: ' . esc_html( curl_error( $curl ) ) );
		}

		curl_close( $curl );

		if ( $curlgetinfo['http_code'] >= 200 && $curlgetinfo['http_code'] < 300 ) {
			if ( preg_match( '/Location:(.*?)\r/i', $response, $matches ) ) {
				$this->access_token = trim( $matches[1] );
			}

			return $this->access_token;
		}

		if ( 401 === $curlgetinfo['http_code'] ) {
			throw new BackWPup_Destination_SugarSync_API_Exception( 'Http Error: ' . esc_html( $curlgetinfo['http_code'] ) . ' Authorization required.' );
		}
		if ( 403 === $curlgetinfo['http_code'] ) {
			throw new BackWPup_Destination_SugarSync_API_Exception( 'Http Error: ' . esc_html( $curlgetinfo['http_code'] ) . ' (Forbidden)  Authentication failed.' );
		}
		if ( 404 === $curlgetinfo['http_code'] ) {
			throw new BackWPup_Destination_SugarSync_API_Exception( 'Http Error: ' . esc_html( $curlgetinfo['http_code'] ) . ' Not found' );
		}

		throw new BackWPup_Destination_SugarSync_API_Exception( 'Http Error: ' . esc_html( $curlgetinfo['http_code'] ) );
	}

	/**
	 * Get a new refresh token.
	 *
	 * @param string $email    The email address for the account.
	 * @param string $password The password for the account.
	 *
	 * @return string|null Returns the refresh token if successful, null otherwise.
	 *
	 * @throws BackWPup_Destination_SugarSync_API_Exception When an error is thrown.
	 */
	public function get_refresh_token( string $email, string $password ): ?string {
		$auth  = '<?xml version="1.0" encoding="UTF-8" ?>';
		$auth .= '<appAuthorization>';
		$auth .= '<username>' . mb_convert_encoding( $email, 'UTF-8', $this->encoding ) . '</username>';
		$auth .= '<password>' . mb_convert_encoding( $password, 'UTF-8', $this->encoding ) . '</password>';
		$auth .= '<application>' . get_site_option( 'backwpup_cfg_sugarsyncappid', '/sc/5030726/449_18207099' ) . '</application>';
		$auth .= '<accessKeyId>' . get_site_option( 'backwpup_cfg_sugarsynckey', base64_decode( 'TlRBek1EY3lOakV6TkRrMk1URXhNemM0TWpJ' ) ) . '</accessKeyId>'; //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$auth .= '<privateAccessKey>' . BackWPup_Encryption::decrypt( get_site_option( 'backwpup_cfg_sugarsyncsecret', base64_decode( 'TkRFd01UazRNVEpqTW1Ga05EaG1NR0k1TVRFNFpqa3lPR1V6WlRVMk1tTQ==' ) ) ) . '</privateAccessKey>'; //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$auth .= '</appAuthorization>';
		// init.
		$curl = curl_init();
		// set options.
		curl_setopt( $curl, CURLOPT_URL, self::API_URL . '/app-authorization' );
		curl_setopt( $curl, CURLOPT_USERAGENT, BackWPup::get_plugin_data( 'User-Agent' ) );
		if ( ini_get( 'open_basedir' ) === '' ) {
			curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, true );
		}
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		if ( BackWPup::get_plugin_data( 'cacert' ) ) {
			curl_setopt( $curl, CURLOPT_SSLVERSION, 1 );
			curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, true );
			curl_setopt( $curl, CURLOPT_CAINFO, BackWPup::get_plugin_data( 'cacert' ) );
			curl_setopt( $curl, CURLOPT_CAPATH, dirname( BackWPup::get_plugin_data( 'cacert' ) ) );
		} else {
			curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
		}
		curl_setopt( $curl, CURLOPT_HEADER, true );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, $auth );
		curl_setopt( $curl, CURLOPT_POST, true );
		curl_setopt( $curl, CURLOPT_HTTPHEADER, [ 'Content-Type: application/xml; charset=UTF-8', 'Content-Length: ' . strlen( $auth ) ] );
		// execute.
		$response    = curl_exec( $curl );
		$curlgetinfo = curl_getinfo( $curl );
		// fetch curl errors.
		if ( curl_errno( $curl ) !== 0 ) { //phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_errno
			throw new BackWPup_Destination_SugarSync_API_Exception( 'cUrl Error: ' . esc_html( curl_error( $curl ) ) );
		}

		curl_close( $curl );

		if ( $curlgetinfo['http_code'] >= 200 && $curlgetinfo['http_code'] < 300 ) {
			if ( preg_match( '/Location:(.*?)\r/i', $response, $matches ) ) {
				$this->refresh_token = trim( $matches[1] );
			}

			return $this->refresh_token;
		}

		if ( 401 === $curlgetinfo['http_code'] ) {
			throw new BackWPup_Destination_SugarSync_API_Exception( 'Http Error: ' . esc_html( $curlgetinfo['http_code'] ) . ' Authorization required.' );
		}
		if ( 403 === $curlgetinfo['http_code'] ) {
			throw new BackWPup_Destination_SugarSync_API_Exception( 'Http Error: ' . esc_html( $curlgetinfo['http_code'] ) . ' (Forbidden)  Authentication failed.' );
		}
		if ( 404 === $curlgetinfo['http_code'] ) {
			throw new BackWPup_Destination_SugarSync_API_Exception( 'Http Error: ' . esc_html( $curlgetinfo['http_code'] ) . ' Not found' );
		}

		throw new BackWPup_Destination_SugarSync_API_Exception( 'Http Error: ' . esc_html( $curlgetinfo['http_code'] ) );
	}

	/**
	 * Create a new account.
	 *
	 * @param string $email    The email address for the new account.
	 * @param string $password The password for the new account.
	 *
	 * @throws BackWPup_Destination_SugarSync_API_Exception When an error is thrown.
	 */
	public function create_account( string $email, string $password ): void {
		$auth  = '<?xml version="1.0" encoding="UTF-8" ?>';
		$auth .= '<user>';
		$auth .= '<email>' . mb_convert_encoding( $email, 'UTF-8', $this->encoding ) . '</email>';
		$auth .= '<password>' . mb_convert_encoding( $password, 'UTF-8', $this->encoding ) . '</password>';
		$auth .= '<accessKeyId>' . get_site_option( 'backwpup_cfg_sugarsynckey', base64_decode( 'TlRBek1EY3lOakV6TkRrMk1URXhNemM0TWpJ' ) ) . '</accessKeyId>'; //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$auth .= '<privateAccessKey>' . BackWPup_Encryption::decrypt( get_site_option( 'backwpup_cfg_sugarsyncsecret', base64_decode( 'TkRFd01UazRNVEpqTW1Ga05EaG1NR0k1TVRFNFpqa3lPR1V6WlRVMk1tTQ==' ) ) ) . '</privateAccessKey>'; //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$auth .= '</user>';
		// init.
		$curl = curl_init();
		// set options.
		curl_setopt( $curl, CURLOPT_URL, 'https://provisioning-api.sugarsync.com/users' );
		curl_setopt( $curl, CURLOPT_USERAGENT, BackWPup::get_plugin_data( 'User-Agent' ) );
		if ( ini_get( 'open_basedir' ) === '' ) {
			curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, true );
		}
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		if ( BackWPup::get_plugin_data( 'cacert' ) ) {
			curl_setopt( $curl, CURLOPT_SSLVERSION, 1 );
			curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, true );
			curl_setopt( $curl, CURLOPT_CAINFO, BackWPup::get_plugin_data( 'cacert' ) );
			curl_setopt( $curl, CURLOPT_CAPATH, dirname( BackWPup::get_plugin_data( 'cacert' ) ) );
		} else {
			curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
		}
		curl_setopt( $curl, CURLOPT_HEADER, true );
		curl_setopt( $curl, CURLOPT_HTTPHEADER, [ 'Content-Type: application/xml; charset=UTF-8', 'Content-Length: ' . strlen( $auth ) ] );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, $auth );
		curl_setopt( $curl, CURLOPT_POST, true );
		// execute.
		$response    = curl_exec( $curl );
		$curlgetinfo = curl_getinfo( $curl );
		// fetch curl errors.
		if ( curl_errno( $curl ) !== 0 ) { //phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_errno
			throw new BackWPup_Destination_SugarSync_API_Exception( 'cUrl Error: ' . esc_html( curl_error( $curl ) ) );
		}

		curl_close( $curl );

		if ( 201 === $curlgetinfo['http_code'] ) {
			throw new BackWPup_Destination_SugarSync_API_Exception( 'Account created.' );
		}

		if ( 400 === $curlgetinfo['http_code'] ) {
			throw new BackWPup_Destination_SugarSync_API_Exception( 'Http Error: ' . esc_html( $curlgetinfo['http_code'] . ' ' . substr( $response, $curlgetinfo['header_size'] ) ) );
		}
		if ( 401 === $curlgetinfo['http_code'] ) {
			throw new BackWPup_Destination_SugarSync_API_Exception( 'Http Error: ' . esc_html( $curlgetinfo['http_code'] . ' Developer credentials cannot be verified. Either a developer with the specified accessKeyId does not exist or the privateKeyID does not match an assigned accessKeyId.' ) );
		}
		if ( 403 === $curlgetinfo['http_code'] ) {
			throw new BackWPup_Destination_SugarSync_API_Exception( 'Http Error: ' . esc_html( $curlgetinfo['http_code'] . ' ' . substr( $response, $curlgetinfo['header_size'] ) ) );
		}
		if ( 503 === $curlgetinfo['http_code'] ) {
			throw new BackWPup_Destination_SugarSync_API_Exception( 'Http Error: ' . esc_html( $curlgetinfo['http_code'] . ' ' . substr( $response, $curlgetinfo['header_size'] ) ) );
		}

		throw new BackWPup_Destination_SugarSync_API_Exception( 'Http Error: ' . esc_html( $curlgetinfo['http_code'] ) );
	}

	/**
	 * Change the current directory.
	 *
	 * @param string $folder The folder to change to.
	 * @param string $root   The root folder to change to.
	 *
	 * @throws BackWPup_Destination_SugarSync_API_Exception When the folder does not exist or if root folder is not set.
	 *
	 * @return string
	 */
	public function chdir( string $folder, string $root = '' ) {
		$folder = rtrim( $folder, '/' );
		if ( substr( $folder, 0, 1 ) === '/' || empty( $this->folder ) ) {
			if ( ! empty( $root ) ) {
				$this->folder = $root;
			} else {
				throw new BackWPup_Destination_SugarSync_API_Exception( 'chdir: root folder must set!' );
			}
		}
		$folders = explode( '/', $folder );

		foreach ( $folders as $dir ) {
			if ( '..' === $dir ) {
				$contents = $this->do_call( $this->folder );
				if ( ! empty( $contents->parent ) ) {
					$this->folder = $contents->parent;
				}
			} elseif ( ! empty( $dir ) && '.' !== $dir ) {
				$isdir    = false;
				$contents = $this->getcontents( 'folder' );

				foreach ( $contents->collection as $collection ) {
					if ( strtolower( $collection->displayName ) === strtolower( $dir ) ) { //phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						$isdir        = true;
						$this->folder = $collection->ref;
						break;
					}
				}
				if ( ! $isdir ) {
					throw new BackWPup_Destination_SugarSync_API_Exception( 'chdir: Folder ' . esc_html( $folder ) . ' not exitst' );
				}
			}
		}

		return $this->folder;
	}

	/**
	 * Show the current directory path.
	 *
	 * @param string $folderid The folder ID to show the path for.
	 * @return string Returns the path of the folder.
	 */
	public function showdir( string $folderid ): string {
		$showfolder = '';

		while ( $folderid ) {
			$contents   = $this->do_call( $folderid );
			$showfolder = $contents->displayName . '/' . $showfolder; //phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			if ( isset( $contents->parent ) ) {
				$folderid = $contents->parent;
			} else {
				break;
			}
		}

		return $showfolder;
	}

	/**
	 * Create a new folder.
	 *
	 * @param string $folder The name of the folder to create.
	 * @param string $root   The root folder where the new folder should be created.
	 * @throws BackWPup_Destination_SugarSync_API_Exception When the root folder is not set or if an error occurs during the API call.
	 * @return bool Returns true if the folder was created successfully.
	 */
	public function mkdir( string $folder, string $root = '' ): bool {
		$savefolder = $this->folder;
		$folder     = rtrim( $folder, '/' );
		if ( substr( $folder, 0, 1 ) === '/' || empty( $this->folder ) ) {
			if ( ! empty( $root ) ) {
				$this->folder = $root;
			} else {
				throw new BackWPup_Destination_SugarSync_API_Exception( 'mkdir: root folder must set!' );
			}
		}
		$folders = explode( '/', $folder );

		foreach ( $folders as $dir ) {
			if ( '..' === $dir ) {
				$contents = $this->do_call( $this->folder );
				if ( ! empty( $contents->parent ) ) {
					$this->folder = $contents->parent;
				}
			} elseif ( ! empty( $dir ) && '.' !== $dir ) {
				$isdir    = false;
				$contents = $this->getcontents( 'folder' );

				foreach ( $contents->collection as $collection ) {
					if ( strtolower( $collection->displayName ) === strtolower( $dir ) ) { //phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						$isdir        = true;
						$this->folder = $collection->ref;
						break;
					}
				}
				if ( ! $isdir ) {
					$this->do_call( $this->folder, '<?xml version="1.0" encoding="UTF-8"?><folder><displayName>' . mb_convert_encoding( $dir, 'UTF-8', $this->encoding ) . '</displayName></folder>', 'POST' );
					$contents = $this->getcontents( 'folder' );

					foreach ( $contents->collection as $collection ) {
						if ( strtolower( $collection->displayName ) === strtolower( $dir ) ) { //phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
							$isdir        = true;
							$this->folder = $collection->ref;
							break;
						}
					}
				}
			}
		}
		$this->folder = $savefolder;

		return true;
	}

	/**
	 * Get the user information.
	 *
	 * @return string|SimpleXMLElement
	 */
	public function user() {
		return $this->do_call( self::API_URL . '/user' );
	}

	/**
	 * Get it
	 *
	 * @param string $url The URL to retrieve.
	 *
	 * @return string|SimpleXMLElement
	 */
	public function get( string $url ) {
		return $this->do_call( $url, '', 'GET' );
	}

	/**
	 * Download a file from the given URL.
	 *
	 * @param string $url The URL of the file to download.
	 *
	 * @return string|SimpleXMLElement
	 */
	public function download( string $url ) {
		return $this->do_call( $url . '/data' );
	}

	/**
	 * Delete a file or folder.
	 *
	 * @param string $url The URL of the file or folder to delete.
	 *
	 * @return string|SimpleXMLElement
	 */
	public function delete( string $url ) {
		return $this->do_call( $url, '', 'DELETE' );
	}

	/**
	 * Get the contents of the current folder.
	 *
	 * @param string $type  The type of contents to retrieve. Possible values are 'folder' or 'file'. If empty, it retrieves both.
	 * @param int    $start The starting index for pagination. Default is 0.
	 * @param int    $max   The maximum number of items to retrieve. Default is 500.
	 *
	 * @return string|SimpleXMLElement
	 */
	public function getcontents( string $type = '', int $start = 0, int $max = 500 ) {
		$parameters = '';

		if ( strtolower( $type ) === 'folder' || strtolower( $type ) === 'file' ) {
			$parameters .= 'type=' . strtolower( $type );
		}
		if ( ! empty( $start ) && is_integer( $start ) ) {
			if ( ! empty( $parameters ) ) {
				$parameters .= '&';
			}
			$parameters .= 'start=' . $start;
		}
		if ( ! empty( $max ) && is_integer( $max ) ) {
			if ( ! empty( $parameters ) ) {
				$parameters .= '&';
			}
			$parameters .= 'max=' . $max;
		}

		return $this->do_call( $this->folder . '/contents?' . $parameters );
	}

	/**
	 * Upload a file to the current folder.
	 *
	 * @param string $file The file path to upload.
	 * @param string $name The name to give the file in SugarSync. If empty, it will use the base name of the file.
	 *
	 * @return mixed
	 */
	public function upload( string $file, string $name = '' ) {
		if ( empty( $name ) ) {
			$name = basename( $file );
		}
		$content_type = MimeTypeExtractor::fromFilePath( $file );

		$xmlrequest  = '<?xml version="1.0" encoding="UTF-8"?>';
		$xmlrequest .= '<file>';
		$xmlrequest .= '<displayName>' . mb_convert_encoding( $name, 'UTF-8', $this->encoding ) . '</displayName>';
		$xmlrequest .= '<mediaType>' . $content_type . '</mediaType>';
		$xmlrequest .= '</file>';

		$this->do_call( $this->folder, $xmlrequest, 'POST' );
		$getfiles = $this->getcontents( 'file' );
		foreach ( $getfiles->file as $getfile ) {
			if ( (string) $getfile->displayName === (string) $name ) { //phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$this->do_call( $getfile->ref . '/data', $file, 'PUT' );

				return $getfile->ref;
			}
		}
	}
}
