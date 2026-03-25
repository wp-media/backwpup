<?php

require_once __DIR__ . '/class-destination-sugarsync-api-exception.php';

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

	/**
	 * Default constructor/Auth.
	 *
	 * @param string|null $refresh_token The refresh token to use for authentication.
	 *
	 * @throws BackWPup_Destination_SugarSync_API_Exception When an error occurs during the API call.
	 */
	public function __construct( $refresh_token = null ) {
		// auth xml.
		$this->encoding = mb_internal_encoding();

		// get access token.
		if ( ! empty( $refresh_token ) ) {
			$this->refresh_token = $refresh_token;
			$this->get_access_token();
		}
	}

	/**
	 * Make the call.
	 *
	 * @param string $url the url to call.
	 * @param string $body the data to send, File on put, xml on post.
	 * @param string $method the method to use. Possible values are GET, POST, PUT, DELETE.
	 * @param array  $extra_headers Additional headers (e.g., Range).
	 *
	 * @throws BackWPup_Destination_SugarSync_API_Exception When an error is thrown.
	 *
	 * @return string|SimpleXMLElement The response body, or a SimpleXMLElement object if the response is XML.
	 */
	private function do_call( string $url, string $body = '', string $method = 'GET', array $extra_headers = [] ) {
		$headers = [];
		if ( $this->access_token ) {
			$headers['Authorization'] = $this->access_token;
		}
		$headers['Content-Length'] = strlen( $body );
		if ( 'POST' === $method ) {
			$headers['Content-Type'] = 'application/xml; charset=UTF-8';
		}
		$headers = array_merge( $headers, $extra_headers );
		if ( isset( $headers['Transfer-Encoding'] ) && 'chunked' === $headers['Transfer-Encoding'] ) {
			unset( $headers['Content-Length'] );
		}
		if ( isset( $headers['Content-Length'] ) && ! $headers['Content-Length'] ) {
			unset( $headers['Content-Length'] );
		}
		$request = wp_remote_request(
			$url,
			[
				'method'      => $method,
				'headers'     => $headers,
				'timeout'     => 30,
				'body'        => ! $body ? null : $body,
				'user-agent'  => BackWPup::get_plugin_data( 'User-Agent' ),
				'redirection' => 0,
				'blocking'    => true,
				'compress'    => false,
			]
		);

		$response_status  = wp_remote_retrieve_response_code( $request );
		$response_headers = wp_remote_retrieve_headers( $request );
		if ( $response_headers ) {
			$response_headers = $response_headers->getAll();
		}
		$response_body = wp_remote_retrieve_body( $request );

		if ( is_wp_error( $request ) ) {
			throw new BackWPup_Destination_SugarSync_API_Exception(
				sprintf(
					// translators: %1$s: HTTP response error message.
					esc_html__( 'SugarSync error: %1$s', 'backwpup' ),
					esc_html( $request->get_error_message() )
				)
			);
		}

		if ( $response_status >= 200 && $response_status < 300 ) {
			if ( 201 === $response_status && ! empty( $response_headers['location'] ) ) {
				return trim( $response_headers['location'] );
			}
			if ( ! empty( $response_body ) && false !== stripos( $response_headers['content-type'], 'application/xml' ) ) {
				return simplexml_load_string( $response_body );
			}

			return $response_body;
		}

		throw new BackWPup_Destination_SugarSync_API_Exception(
			sprintf(
				// translators: %1$s: HTTP status code, %2$s: HTTP response message.
				esc_html__( 'SugarSync error: (%1$s) %2$s', 'backwpup' ),
				esc_html( $response_status ),
				esc_html(
					wp_remote_retrieve_response_message( $request )
				)
			)
		);
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

		$result = $this->do_call( self::API_URL . '/authorization', $auth, 'POST' );

		if ( $result && ! $result instanceof SimpleXMLElement ) {
			$this->access_token = $result;

			return $this->access_token;
		}

		return '';
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

		$result = $this->do_call( self::API_URL . '/app-authorization', $auth, 'POST' );

		if ( $result && ! $result instanceof SimpleXMLElement ) {
			$this->access_token = $result;

			return $this->access_token;
		}

		return '';
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

		$result = $this->do_call( 'https://provisioning-api.sugarsync.com/users', $auth, 'POST' );
		if ( ! $result ) {
			throw new BackWPup_Destination_SugarSync_API_Exception( esc_html__( 'Failed to create SugarSync account.', 'backwpup' ) );
		}
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
		if ( '/' === $folder[0] || empty( $this->folder ) ) {
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
	 *
	 * @return string Returns the path of the folder.
	 * @throws BackWPup_Destination_SugarSync_API_Exception When the folder does not exist.
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
		if ( '/' === $folder[0] || empty( $this->folder ) ) {
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
	 * @throws BackWPup_Destination_SugarSync_API_Exception When an error occurs during the API call.
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
	 * @throws BackWPup_Destination_SugarSync_API_Exception When an error occurs during the API call.
	 */
	public function get( string $url ) {
		return $this->do_call( $url );
	}

	/**
	 * Download a file from the given URL.
	 *
	 * @param string $url The URL of the file to download.
	 *
	 * @return string|SimpleXMLElement
	 * @throws BackWPup_Destination_SugarSync_API_Exception When an error occurs during the API call.
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
	 * @throws BackWPup_Destination_SugarSync_API_Exception When an error occurs during the API call.
	 */
	public function delete( string $url ) {
		return $this->do_call( $url, '', 'DELETE' );
	}

	/**
	 * Get the contents of the current folder.
	 *
	 * @param string $type The type of contents to retrieve. Possible values are 'folder' or 'file'. If empty, it retrieves both.
	 * @param int    $start The starting index for pagination. Default is 0.
	 * @param int    $max The maximum number of items to retrieve. Default is 500.
	 *
	 * @return string|SimpleXMLElement
	 * @throws BackWPup_Destination_SugarSync_API_Exception When an error occurs during the API call.
	 */
	public function getcontents( string $type = '', int $start = 0, int $max = 500 ) {
		$parameters = '';

		if ( strtolower( $type ) === 'folder' || strtolower( $type ) === 'file' ) {
			$parameters .= 'type=' . strtolower( $type );
		}
		if ( ! empty( $start ) && is_int( $start ) ) {
			if ( ! empty( $parameters ) ) {
				$parameters .= '&';
			}
			$parameters .= 'start=' . $start;
		}
		if ( ! empty( $max ) && is_int( $max ) ) {
			if ( ! empty( $parameters ) ) {
				$parameters .= '&';
			}
			$parameters .= 'max=' . $max;
		}

		return $this->do_call( $this->folder . '/contents?' . $parameters );
	}

	/**
	 *
	 * Upload a file to the current folder.
	 *
	 * phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
	 *
	 * @param string $file The file path to upload.
	 * @param string $name The name to give the file in SugarSync. If empty, it will use the base name of the file.
	 *
	 * @return SimpleXMLElement|bool Returns the file data if successful, false otherwise.
	 * @throws BackWPup_Destination_SugarSync_API_Exception When an error occurs during the API call.
	 */
	public function upload( string $file, string $name = '' ) {
		if ( empty( $name ) ) {
			$name = basename( $file );
		}

		// create a new file.
		$content_type = MimeTypeExtractor::fromFilePath( $file );

		$xml_request  = '<?xml version="1.0" encoding="UTF-8"?>';
		$xml_request .= '<file>';
		$xml_request .= '<displayName>' . mb_convert_encoding( $name, 'UTF-8', $this->encoding ) . '</displayName>';
		$xml_request .= '<mediaType>' . $content_type . '</mediaType>';
		$xml_request .= '</file>';

		$location = $this->do_call( $this->folder, $xml_request, 'POST' );

		if ( ! $location ) {
			return false;
		}

		$file_data = $this->do_call( $location );

		// create a new file version.
		$location = $this->do_call( $file_data->versions, '', 'POST' );
		if ( ! $location ) {
			return false;
		}

		$version_data = $this->do_call( $location );

		// upload with native curl todo: refactor with wp_remote_* functions when we find a solution how it can work.
		// Documentations https://www.sugarsync.com/dev/upload-file-data-example.html#resumeup outdated.
		$data_file_fd = fopen( $file, 'rb' ); //phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$curl         = curl_init(); //phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_init
		curl_setopt( $curl, CURLOPT_URL, $version_data->fileData ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase, WordPress.WP.AlternativeFunctions.curl_curl_setopt
		curl_setopt( $curl, CURLOPT_USERAGENT, BackWPup::get_plugin_data( 'User-Agent' ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
		curl_setopt( // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
			$curl,
			CURLOPT_HTTPHEADER,
			[
				'Authorization: ' . $this->access_token,
				'Expect:',
				'Content-Length: ' . filesize( $file ),
			]
		);
		if ( BackWPup::get_plugin_data( 'cacert' ) ) {
			curl_setopt( $curl, CURLOPT_SSLVERSION, 1 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
			curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
			curl_setopt( $curl, CURLOPT_CAINFO, BackWPup::get_plugin_data( 'cacert' ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
			curl_setopt( $curl, CURLOPT_CAPATH, dirname( BackWPup::get_plugin_data( 'cacert' ) ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
		} else {
			curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
		}
		curl_setopt( $curl, CURLOPT_PUT, true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
		curl_setopt( $curl, CURLOPT_INFILE, $data_file_fd ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
		curl_setopt( $curl, CURLOPT_INFILESIZE, filesize( $file ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
		curl_setopt( $curl, CURLOPT_READFUNCTION, [ BackWPup_Destination_SugarSync::$backwpup_job_object, 'curl_read_callback' ] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt

		curl_exec( $curl ); //phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_exec
		$curl_info = curl_getinfo( $curl ); //phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_getinfo

		// fetch curl errors.
		if ( curl_errno( $curl ) !== 0 ) { //phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_errno
			throw new BackWPup_Destination_SugarSync_API_Exception( 'cUrl Error: ' . esc_html( curl_error( $curl ) ) ); //phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_error
		}
		curl_close( $curl ); //phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_close
		fclose( $data_file_fd ); //phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		if ( $curl_info['http_code'] < 200 || $curl_info['http_code'] >= 300 ) {
			throw new BackWPup_Destination_SugarSync_API_Exception( 'Http Error: ' . esc_html( $curl_info['http_code'] ) );
		}

		return $file_data;
	}

	/**
	 * Download a chunk of a file with a Range header.
	 *
	 * @param string $url File URL.
	 * @param int    $start_byte Start byte.
	 * @param int    $end_byte End byte.
	 *
	 * @return string|SimpleXMLElement Binary data on success or a SimpleXMLElement on error.
	 * @throws BackWPup_Destination_SugarSync_API_Exception When an error occurs during the API call.
	 */
	public function download_chunk( string $url, int $start_byte, int $end_byte ) {
		$header = [
			'Range' => 'bytes=' . $start_byte . '-' . $end_byte,
		];
		return $this->do_call( $url . '/data', '', 'GET', $header );
	}
}
