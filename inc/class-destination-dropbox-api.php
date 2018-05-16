<?php

/**
 * Class for communicating with Dropbox API V2.
 */
final class BackWPup_Destination_Dropbox_API {

	/**
	 * URL to Dropbox API endpoint.
	 */
	const API_URL = 'https://api.dropboxapi.com/';

	/**
	 * URL to Dropbox content endpoint.
	 */
	const API_CONTENT_URL = 'https://content.dropboxapi.com/';

	/**
	 * URL to Dropbox for authentication.
	 */
	const API_WWW_URL = 'https://www.dropbox.com/';

	/**
	 * API version.
	 */
	const API_VERSION_URL = '2/';

	/**
	 * oAuth vars
	 *
	 * @var string
	 */
	private $oauth_app_key = '';

	/**
	 * @var string
	 */
	private $oauth_app_secret = '';

	/**
	 * @var string
	 */
	private $oauth_token = '';

	/**
	 * Job object for logging.
	 *
	 * @var BackWPup_Job
	 */
	private $job_object;

	/**
	 * @param string $boxtype
	 *
	 * @throws BackWPup_Destination_Dropbox_API_Exception
	 */
	public function __construct( $boxtype = 'dropbox', BackWPup_Job $job_object = null ) {

		if ( $boxtype == 'dropbox' ) {
			$this->oauth_app_key    = get_site_option(
				'backwpup_cfg_dropboxappkey',
				base64_decode( "dHZkcjk1MnRhZnM1NmZ2" )
			);
			$this->oauth_app_secret = BackWPup_Encryption::decrypt(
				get_site_option( 'backwpup_cfg_dropboxappsecret', base64_decode( "OWV2bDR5MHJvZ2RlYmx1" ) )
			);
		} else {
			$this->oauth_app_key    = get_site_option(
				'backwpup_cfg_dropboxsandboxappkey',
				base64_decode( "cHVrZmp1a3JoZHR5OTFk" )
			);
			$this->oauth_app_secret = BackWPup_Encryption::decrypt(
				get_site_option( 'backwpup_cfg_dropboxsandboxappsecret', base64_decode( "eGNoYzhxdTk5eHE0eWdq" ) )
			);
		}

		if ( empty( $this->oauth_app_key ) || empty( $this->oauth_app_secret ) ) {
			throw new BackWPup_Destination_Dropbox_API_Exception( "No App key or App Secret specified." );
		}

		$this->job_object = $job_object;
	}

	/**
	 * List a folder
	 *
	 * This is a helper method to use filesListFolder and
	 * filesListFolderContinue to construct an array of files within a given
	 * folder path.
	 *
	 * @param string $path
	 *
	 * @return array
	 */
	public function listFolder( $path ) {

		$files  = array();
		$result = $this->filesListFolder( array( 'path' => $path ) );

		if ( ! $result ) {
			return array();
		}

		$files = array_merge( $files, $result['entries'] );

		$args = array( 'cursor' => $result['cursor'] );

		while ( $result['has_more'] == true ) {
			$result = $this->filesListFolderContinue( $args );
			$files  = array_merge( $files, $result['entries'] );
		}

		return $files;
	}

	/**
	 * Uploads a file to Dropbox.
	 *
	 * @param        $file
	 * @param string $path
	 * @param bool   $overwrite
	 *
	 * @return array
	 * @throws BackWPup_Destination_Dropbox_API_Exception
	 */
	public function upload( $file, $path = '', $overwrite = true ) {

		$file = str_replace( "\\", "/", $file );

		if ( ! is_readable( $file ) ) {
			throw new BackWPup_Destination_Dropbox_API_Exception(
				"Error: File \"$file\" is not readable or doesn't exist."
			);
		}

		if ( filesize( $file ) < 5242880 ) { //chunk transfer on bigger uploads
			$output = $this->filesUpload(
				array(
					'contents' => file_get_contents( $file ),
					'path'     => $path,
					'mode'     => ( $overwrite ) ? 'overwrite' : 'add',
				)
			);
		} else {
			$output = $this->multipartUpload( $file, $path, $overwrite );
		}

		return $output;
	}

	/**
	 * @param        $file
	 * @param string $path
	 * @param bool   $overwrite
	 *
	 * @return array|mixed|string
	 * @throws BackWPup_Destination_Dropbox_API_Exception
	 */
	public function multipartUpload( $file, $path = '', $overwrite = true ) {

		$file = str_replace( "\\", "/", $file );

		if ( ! is_readable( $file ) ) {
			throw new BackWPup_Destination_Dropbox_API_Exception(
				"Error: File \"$file\" is not readable or doesn't exist."
			);
		}

		$chunk_size = 4194304; //4194304 = 4MB

		$file_handel = fopen( $file, 'rb' );
		if ( ! $file_handel ) {
			throw new BackWPup_Destination_Dropbox_API_Exception( "Can not open source file for transfer." );
		}

		if ( ! isset( $this->job_object->steps_data[ $this->job_object->step_working ]['uploadid'] ) ) {
			$this->job_object->log( __( 'Beginning new file upload session', 'backwpup' ) );
			$session                                                                     = $this->filesUploadSessionStart(
			);
			$this->job_object->steps_data[ $this->job_object->step_working ]['uploadid'] = $session['session_id'];
		}
		if ( ! isset( $this->job_object->steps_data[ $this->job_object->step_working ]['offset'] ) ) {
			$this->job_object->steps_data[ $this->job_object->step_working ]['offset'] = 0;
		}
		if ( ! isset( $this->job_object->steps_data[ $this->job_object->step_working ]['totalread'] ) ) {
			$this->job_object->steps_data[ $this->job_object->step_working ]['totalread'] = 0;
		}

		//seek to current position
		if ( $this->job_object->steps_data[ $this->job_object->step_working ]['offset'] > 0 ) {
			fseek( $file_handel, $this->job_object->steps_data[ $this->job_object->step_working ]['offset'] );
		}

		while ( $data = fread( $file_handel, $chunk_size ) ) {
			$chunk_upload_start = microtime( true );

			if ( $this->job_object->is_debug() ) {
				$this->job_object->log(
					sprintf( __( 'Uploading %s of data', 'backwpup' ), size_format( strlen( $data ) ) )
				);
			}

			$this->filesUploadSessionAppendV2(
				array(
					'contents' => $data,
					'cursor'   => array(
						'session_id' => $this->job_object->steps_data[ $this->job_object->step_working ]['uploadid'],
						'offset'     => $this->job_object->steps_data[ $this->job_object->step_working ]['offset'],
					),
				)
			);
			$chunk_upload_time                                                            = microtime(
					true
				) - $chunk_upload_start;
			$this->job_object->steps_data[ $this->job_object->step_working ]['totalread'] += strlen( $data );

			//args for next chunk
			$this->job_object->steps_data[ $this->job_object->step_working ]['offset'] += $chunk_size;
			if ( $this->job_object->job['backuptype'] === 'archive' ) {
				$this->job_object->substeps_done = $this->job_object->steps_data[ $this->job_object->step_working ]['offset'];
				if ( strlen( $data ) == $chunk_size ) {
					$time_remaining = $this->job_object->do_restart_time();
					//calc next chunk
					if ( $time_remaining < $chunk_upload_time ) {
						$chunk_size = floor( $chunk_size / $chunk_upload_time * ( $time_remaining - 3 ) );
						if ( $chunk_size < 0 ) {
							$chunk_size = 1024;
						}
						if ( $chunk_size > 4194304 ) {
							$chunk_size = 4194304;
						}
					}
				}
			}
			$this->job_object->update_working_data();
			//correct position
			fseek( $file_handel, $this->job_object->steps_data[ $this->job_object->step_working ]['offset'] );
		}

		fclose( $file_handel );

		$this->job_object->log(
			sprintf(
				__( 'Finishing upload session with a total of %s uploaded', 'backwpup' ),
				size_format( $this->job_object->steps_data[ $this->job_object->step_working ]['totalread'] )
			)
		);
		$response = $this->filesUploadSessionFinish(
			array(
				'cursor' => array(
					'session_id' => $this->job_object->steps_data[ $this->job_object->step_working ]['uploadid'],
					'offset'     => $this->job_object->steps_data[ $this->job_object->step_working ]['totalread'],
				),
				'commit' => array(
					'path' => $path,
					'mode' => ( $overwrite ) ? 'overwrite' : 'add',
				),
			)
		);

		unset( $this->job_object->steps_data[ $this->job_object->step_working ]['uploadid'] );
		unset( $this->job_object->steps_data[ $this->job_object->step_working ]['offset'] );

		return $response;
	}

	/**
	 * Set the oauth tokens for this request.
	 *
	 * @param $token
	 *
	 * @throws BackWPup_Destination_Dropbox_API_Exception
	 */
	public function setOAuthTokens( $token ) {

		if ( empty( $token['access_token'] ) ) {
			throw new BackWPup_Destination_Dropbox_API_Exception( "No oAuth token specified." );
		}

		$this->oauth_token = $token;
	}

	/**
	 * Returns the URL to authorize the user.
	 *
	 * @return string The authorization URL
	 */
	public function oAuthAuthorize() {

		return self::API_WWW_URL . 'oauth2/authorize?response_type=code&client_id=' . $this->oauth_app_key;
	}

	/**
	 * Tkes the oauth code and returns the access token.
	 *
	 * @param string $code The oauth code
	 *
	 * @return array An array including the access token, account ID, and
	 * other information.
	 */
	public function oAuthToken( $code ) {

		return $this->request(
			'oauth2/token',
			array(
				'code'          => trim( $code ),
				'grant_type'    => 'authorization_code',
				'client_id'     => $this->oauth_app_key,
				'client_secret' => $this->oauth_app_secret,
			),
			'oauth'
		);
	}

	/**
	 * Revokes the auth token.
	 *
	 * @return array
	 */
	public function authTokenRevoke() {

		return $this->request( 'auth/token/revoke' );
	}

	/**
	 * Download
	 *
	 * @throws BackWPup_Destination_Dropbox_API_Exception Because of a rate limit.
	 *
	 * @param array $args Argument for the api request.
	 *
	 * @return mixed Whatever the api request returns.
	 */
	public function download( $args, $startByte = null, $endByte = null ) {

		$args['path'] = $this->formatPath( $args['path'] );

		if ( $startByte !== null && $endByte !== null ) {
			return $this->request( 'files/download', $args, 'download', false, "{$startByte}-{$endByte}" );
		}

		return $this->request( 'files/download', $args, 'download' );
	}

	/**
	 * Deletes a file.
	 *
	 * @param array $args An array of arguments
	 *
	 * @return array Information on the deleted file
	 */
	public function filesDelete( $args ) {

		$args['path'] = $this->formatPath( $args['path'] );

		try {
			return $this->request( 'files/delete', $args );
		} catch ( BackWPup_Destination_Dropbox_API_Request_Exception $e ) {
			$this->handleFilesDeleteError( $e->getError() );
		}
	}

	/**
	 * Gets the metadata of a file.
	 *
	 * @param array $args An array of arguments
	 *
	 * @return array The file's metadata
	 */
	public function filesGetMetadata( $args ) {

		$args['path'] = $this->formatPath( $args['path'] );
		try {
			return $this->request( 'files/get_metadata', $args );
		} catch ( BackWPup_Destination_Dropbox_API_Request_Exception $e ) {
			$this->handleFilesGetMetadataError( $e->getError() );
		}
	}

	/**
	 * Gets a temporary link from Dropbox to access the file.
	 *
	 * @param array $args An array of arguments
	 *
	 * @return array Information on the file and link
	 */
	public function filesGetTemporaryLink( $args ) {

		$args['path'] = $this->formatPath( $args['path'] );
		try {
			return $this->request( 'files/get_temporary_link', $args );
		} catch ( BackWPup_Destination_Dropbox_API_Request_Exception $e ) {
			$this->handleFilesGetTemporaryLinkError( $e->getError() );
		}
	}

	/**
	 * Lists all the files within a folder.
	 *
	 * @param array $args An array of arguments
	 *
	 * @return array A list of files
	 */
	public function filesListFolder( $args ) {

		$args['path'] = $this->formatPath( $args['path'] );
		try {
			Return $this->request( 'files/list_folder', $args );
		} catch ( BackWPup_Destination_Dropbox_API_Request_Exception $e ) {
			$this->handleFilesListFolderError( $e->getError() );
		}
	}

	/**
	 * Continue to list more files.
	 *
	 * When a folder has a lot of files, the API won't return all at once.
	 * So this method is to fetch more of them.
	 *
	 * @param array $args An array of arguments
	 *
	 * @return array An array of files
	 */
	public function filesListFolderContinue( $args ) {

		try {
			Return $this->request( 'files/list_folder/continue', $args );
		} catch ( BackWPup_Destination_Dropbox_API_Request_Exception $e ) {
			$this->handleFilesListFolderContinueError( $e->getError() );
		}
	}

	/**
	 * Uploads a file to Dropbox.
	 *
	 * The file must be no greater than 150 MB.
	 *
	 * @param array $args An array of arguments
	 *
	 * @return array    The uploaded file's information.
	 */
	public function filesUpload( $args ) {

		$args['path'] = $this->formatPath( $args['path'] );

		if ( isset( $args['client_modified'] )
			&& $args['client_modified'] instanceof DateTime ) {
			$args['client_modified'] = $args['client_modified']->format( 'Y-m-d\TH:m:s\Z' );
		}

		try {
			return $this->request( 'files/upload', $args, 'upload' );
		} catch ( BackWPup_Destination_Dropbox_API_Request_Exception $e ) {
			$this->handleFilesUploadError( $e->getError() );
		}
	}

	/**
	 * Append more data to an uploading file
	 *
	 * @param array $args An array of arguments
	 */
	public function filesUploadSessionAppendV2( $args ) {

		try {
			return $this->request(
				'files/upload_session/append_v2',
				$args,
				'upload'
			);
		} catch ( BackWPup_Destination_Dropbox_API_Request_Exception $e ) {
			$error = $e->getError();

			// See if we can fix the error first
			if ( $error['.tag'] == 'incorrect_offset' ) {
				$args['cursor']['offset'] = $error['correct_offset'];

				return $this->request(
					'files/upload_session/append_v2',
					$args,
					'upload'
				);
			}

			// Otherwise, can't fix
			$this->handleFilesUploadSessionLookupError( $error );
		}
	}

	/**
	 * Finish an upload session.
	 *
	 * @param array $args
	 *
	 * @return array Information on the uploaded file
	 */
	public function filesUploadSessionFinish( $args ) {

		$args['commit']['path'] = $this->formatPath( $args['commit']['path'] );;
		try {
			return $this->request( 'files/upload_session/finish', $args, 'upload' );
		} catch ( BackWPup_Destination_Dropbox_API_Request_Exception $e ) {
			$error = $e->getError();
			if ( $error['.tag'] == 'lookup_failed' ) {
				if ( $error['lookup_failed']['.tag'] == 'incorrect_offset' ) {
					$args['cursor']['offset'] = $error['lookup_failed']['correct_offset'];

					return $this->request( 'files/upload_session/finish', $args, 'upload' );
				}
			}
			$this->handleFilesUploadSessionFinishError( $e->getError() );
		}
	}

	/**
	 * Starts an upload session.
	 *
	 * When a file larger than 150 MB needs to be uploaded, then this API
	 * endpoint is used to start a session to allow the file to be uploaded in
	 * chunks.
	 *
	 * @param array $args
	 *
	 * @return array    An array containing the session's ID.
	 */
	public function filesUploadSessionStart( $args = array() ) {

		return $this->request( 'files/upload_session/start', $args, 'upload' );
	}

	/**
	 * Get user's current account info.
	 *
	 * @return array
	 */
	public function usersGetCurrentAccount() {

		return $this->request( 'users/get_current_account' );
	}

	/**
	 * Get quota info for this user.
	 *
	 * @return array
	 */
	public function usersGetSpaceUsage() {

		return $this->request( 'users/get_space_usage' );
	}

	/**
	 * Request
	 *
	 * @param        $url
	 * @param array  $args
	 * @param string $endpointFormat
	 * @param string $data
	 * @param bool   $echo
	 * @param string $bytes
	 *
	 * @throws BackWPup_Destination_Dropbox_API_Exception
	 *
	 * @return array|mixed|string
	 */
	private function request( $endpoint, $args = array(), $endpointFormat = 'rpc', $echo = false, $bytes = null ) {

		// Get complete URL
		switch ( $endpointFormat ) {
			case 'oauth':
				$url = self::API_URL . $endpoint;
				break;

			case 'rpc':
				$url = self::API_URL . self::API_VERSION_URL . $endpoint;
				break;

			case 'upload':
			case 'download':
				$url = self::API_CONTENT_URL . self::API_VERSION_URL . $endpoint;
				break;
		}

		if ( $this->job_object && $this->job_object->is_debug() && $endpointFormat != 'oauth' ) {
			$message    = 'Call to ' . $endpoint;
			$parameters = $args;
			if ( isset( $parameters['contents'] ) ) {
				$message .= ', with ' . size_format( strlen( $parameters['contents'] ) ) . ' of data';
				unset( $parameters['contents'] );
			}
			if ( ! empty( $parameters ) ) {
				$message .= ', with parameters: ' . json_encode( $parameters );
			}
			$this->job_object->log( $message );
		}

		// Build cURL Request
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_POST, true );

		$headers[] = 'Expect:';

		if ( $endpointFormat !== 'oauth' ) {
			$headers[] = 'Authorization: Bearer ' . $this->oauth_token['access_token'];
		}

		switch ( $endpointFormat ) {
			case 'oauth':
				curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $args, null, '&' ) );
				$headers[] = 'Content-Type: application/x-www-form-urlencoded';
				break;

			case 'rpc':
				$args      = empty( $args ) ? null : $args;
				$headers[] = 'Content-Type: application/json';

				curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $args ) );
				break;

			case 'upload':
				$args['contents'] = isset( $args['contents'] ) ? $args['contents'] : '';

				curl_setopt( $ch, CURLOPT_POSTFIELDS, $args['contents'] );
				unset( $args['contents'] );

				$headers[] = 'Content-Type: application/octet-stream';
				$headers[] = empty( $args )
					? 'Dropbox-API-Arg: {}'
					: 'Dropbox-API-Arg: ' . json_encode( $args );
				break;

			case 'download':
				curl_setopt( $ch, CURLOPT_BINARYTRANSFER, true );

				$headers[] = 'Content-Type: text/plain';
				$headers[] = 'Dropbox-API-Arg: ' . json_encode( $args );

				if ( $bytes !== null ) {
					$headers[] = "Range: bytes=$bytes";
				}
				break;

			default:
				curl_setopt( $ch, CURLOPT_BINARYTRANSFER, true );
				$headers[] = 'Dropbox-API-Arg: ' . json_encode( $args );
				break;
		}

		curl_setopt( $ch, CURLOPT_USERAGENT, BackWPup::get_plugin_data( 'User-Agent' ) );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		if ( BackWPup::get_plugin_data( 'cacert' ) ) {
			curl_setopt( $ch, CURLOPT_SSLVERSION, 1 );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true );
			$curl_version = curl_version();
			if ( strstr( $curl_version['ssl_version'], 'NSS/' ) === false ) {
				curl_setopt(
					$ch,
					CURLOPT_SSL_CIPHER_LIST,
					'ECDHE-RSA-AES256-GCM-SHA384:' .
					'ECDHE-RSA-AES128-GCM-SHA256:' .
					'ECDHE-RSA-AES256-SHA384:' .
					'ECDHE-RSA-AES128-SHA256:' .
					'ECDHE-RSA-AES256-SHA:' .
					'ECDHE-RSA-AES128-SHA:' .
					'ECDHE-RSA-RC4-SHA:' .
					'DHE-RSA-AES256-GCM-SHA384:' .
					'DHE-RSA-AES128-GCM-SHA256:' .
					'DHE-RSA-AES256-SHA256:' .
					'DHE-RSA-AES128-SHA256:' .
					'DHE-RSA-AES256-SHA:' .
					'DHE-RSA-AES128-SHA:' .
					'AES256-GCM-SHA384:' .
					'AES128-GCM-SHA256:' .
					'AES256-SHA256:' .
					'AES128-SHA256:' .
					'AES256-SHA:' .
					'AES128-SHA'
				);
			}
			if ( defined( 'CURLOPT_PROTOCOLS' ) ) {
				curl_setopt( $ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS );
			}
			if ( defined( 'CURLOPT_REDIR_PROTOCOLS' ) ) {
				curl_setopt( $ch, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTPS );
			}
			curl_setopt( $ch, CURLOPT_CAINFO, BackWPup::get_plugin_data( 'cacert' ) );
			curl_setopt( $ch, CURLOPT_CAPATH, dirname( BackWPup::get_plugin_data( 'cacert' ) ) );
		} else {
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		}
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
		$output = '';
		if ( $echo ) {
			echo curl_exec( $ch );
		} else {
			curl_setopt( $ch, CURLOPT_HEADER, true );
			$response = curl_exec( $ch );
			if ( stripos( $response, "HTTP/1.0 200 Connection established\r\n\r\n" ) !== false ) {
				$response = str_ireplace( "HTTP/1.0 200 Connection established\r\n\r\n", '', $response );
			}
			$response = explode( "\r\n\r\n", $response, 2 );

			if ( ! empty( $response[1] ) ) {
				$output = json_decode( $response[1], true );
			}
		}
		$status = curl_getinfo( $ch );

		// Handle error codes
		// If 409 (endpoint-specific error), let the calling method handle it

		// Code 429 = rate limited
		if ( $status['http_code'] == 429 ) {
			$wait = 0;
			if ( preg_match( "/retry-after:\s*(.*?)\r/i", $response[0], $matches ) ) {
				$wait = trim( $matches[1] );
			}
			//only wait if we get a retry-after header.
			if ( ! empty( $wait ) ) {
				trigger_error(
					sprintf(
						'(429) Your app is making too many requests and is being rate limited. Error 429 can be triggered on a per-app or per-user basis. Wait for %d seconds.',
						$wait
					),
					E_USER_WARNING
				);
				sleep( $wait );
			} else {
				throw new BackWPup_Destination_Dropbox_API_Exception(
					'(429) This indicates a transient server error.'
				);
			}

			//redo request
			return $this->request( $url, $args, $endpointFormat, $endpointFormat, $echo );
		} // We can't really handle anything else, so throw it back to the caller
		elseif ( isset( $output['error'] ) || $status['http_code'] >= 400 || curl_errno( $ch ) > 0 ) {
			$code = $status['http_code'];
			if ( curl_errno( $ch ) != 0 ) {
				$message = '(' . curl_errno( $ch ) . ') ' . curl_error( $ch );
				$code    = 0;
			} elseif ( $status['http_code'] == 400 ) {
				$message = '(400) Bad input parameter: ' . strip_tags( $response[1] );
			} elseif ( $status['http_code'] == 401 ) {
				$message = '(401) Bad or expired token. This can happen if the user or Dropbox revoked or expired an access token. To fix, you should re-authenticate the user.';
			} elseif ( $status['http_code'] == 409 ) {
				$message = $output['error_summary'];
			} elseif ( $status['http_code'] >= 500 ) {
				$message = '(' . $status['http_code'] . ') There is an error on the Dropbox server.';
			} else {
				$message = '(' . $status['http_code'] . ') Invalid response.';
			}
			if ( $this->job_object && $this->job_object->is_debug() ) {
				$this->job_object->log( 'Response with header: ' . $response[0] );
			}
			throw new BackWPup_Destination_Dropbox_API_Request_Exception(
				$message,
				$code,
				null,
				isset( $output['error'] ) ? $output['error'] : null
			);
		} else {
			curl_close( $ch );
			if ( ! is_array( $output ) ) {
				return $response[1];
			} else {
				return $output;
			}
		}
	}

	/**
	 * Formats a path to be valid for Dropbox.
	 *
	 * @param string $path
	 *
	 * @return string The formatted path
	 */
	private function formatPath( $path ) {

		if ( ! empty( $path ) && substr( $path, 0, 1 ) != '/' ) {
			$path = '/' . rtrim( $path, '/' );
		} elseif ( $path == '/' ) {
			$path = '';
		}

		return $path;
	}

	// Error Handlers

	private function handleFilesDeleteError( $error ) {

		switch ( $error['.tag'] ) {
			case 'path_lookup':
				$this->handleFilesLookupError( $error['path_lookup'] );
				break;

			case 'path_write':
				$this->handleFilesWriteError( $error['path_write'] );
				break;

			case 'other':
				trigger_error( 'Could not delete file.', E_USER_WARNING );
				break;
		}
	}

	private function handleFilesGetMetadataError( $error ) {

		switch ( $error['.tag'] ) {
			case 'path':
				$this->handleFilesLookupError( $error['path'] );
				break;

			case 'other':
				trigger_error( 'Cannot look up file metadata.', E_USER_WARNING );
				break;
		}
	}

	private function handleFilesGetTemporaryLinkError( $error ) {

		switch ( $error['.tag'] ) {
			case 'path':
				$this->handleFilesLookupError( $error['path'] );
				break;

			case 'other':
				trigger_error( 'Cannot get temporary link.', E_USER_WARNING );
				break;
		}
	}

	private function handleFilesListFolderError( $error ) {

		switch ( $error['.tag'] ) {
			case 'path':
				$this->handleFilesLookupError( $error['path'] );
				break;

			case 'other':
				trigger_error( 'Cannot list files in folder.', E_USER_WARNING );
				break;
		}
	}

	private function handleFilesListFolderContinueError( $error ) {

		switch ( $error['.tag'] ) {
			case 'path':
				$this->handleFilesLookupError( $error['path'] );
				break;

			case 'reset':
				trigger_error( 'This cursor has been invalidated.', E_USER_WARNING );
				break;

			case 'other':
				trigger_error( 'Cannot list files in folder.', E_USER_WARNING );
				break;
		}
	}

	private function handleFilesLookupError( $error ) {

		switch ( $error['.tag'] ) {
			case 'malformed_path':
				trigger_error( 'The path was malformed.', E_USER_WARNING );
				break;

			case 'not_found':
				trigger_error( 'File could not be found.', E_USER_WARNING );
				break;

			case 'not_file':
				trigger_error( 'That is not a file.', E_USER_WARNING );
				break;

			case 'not_folder':
				trigger_error( 'That is not a folder.', E_USER_WARNING );
				break;

			case 'restricted_content':
				trigger_error( 'This content is restricted.', E_USER_WARNING );
				break;

			case 'invalid_path_root':
				trigger_error( 'Path root is invalid.', E_USER_WARNING );
				break;

			case 'other':
				trigger_error( 'File could not be found.', E_USER_WARNING );
				break;
		}
	}

	private function handleFilesUploadSessionFinishError( $error ) {

		switch ( $error['.tag'] ) {
			case 'lookup_failed':
				$this->handleFilesUploadSessionLookupError(
					$error['lookup_failed']
				);
				break;

			case 'path':
				$this->handleFilesWriteError( $error['path'] );
				break;

			case 'too_many_shared_folder_targets':
				trigger_error( 'Too many shared folder targets.', E_USER_WARNING );
				break;

			case 'other':
				trigger_error( 'The file could not be uploaded.', E_USER_WARNING );
				break;
		}
	}

	private function handleFilesUploadSessionLookupError( $error ) {

		switch ( $error['.tag'] ) {
			case 'not_found':
				trigger_error( 'Session not found.', E_USER_WARNING );
				break;

			case 'incorrect_offset':
				trigger_error(
					'Incorrect offset given. Correct offset is ' .
					$error['correct_offset'] . '.',
					E_USER_WARNING
				);
				break;

			case 'closed':
				trigger_error(
					'This session has been closed already.',
					E_USER_WARNING
				);
				break;

			case 'not_closed':
				trigger_error( 'This session is not closed.', E_USER_WARNING );
				break;

			case 'other':
				trigger_error(
					'Could not look up the file session.',
					E_USER_WARNING
				);
				break;
		}
	}

	private function handleFilesUploadError( $error ) {

		switch ( $error['.tag'] ) {
			case 'path':
				$this->handleFilesUploadWriteFailed( $error['path'] );
				break;

			case 'other':
				trigger_error( 'There was an unknown error when uploading the file.', E_USER_WARNING );
				break;
		}
	}

	private function handleFilesUploadWriteFailed( $error ) {

		$this->handleFilesWriteError( $error['reason'] );
	}

	private function handleFilesWriteError( $error ) {

		$message = '';

		// Type of error
		switch ( $error['.tag'] ) {
			case 'malformed_path':
				$message = 'The path was malformed.';
				break;

			case 'conflict':
				$message = 'Cannot write to the target path due to conflict.';
				break;

			case 'no_write_permission':
				$message = 'You do not have permission to save to this location.';
				break;

			case 'insufficient_space':
				$message = 'You do not have enough space in your Dropbox.';
				break;

			case 'disallowed_name':
				$message = 'The given name is disallowed by Dropbox.';
				break;

			case 'team_folder':
				$message = 'Unable to modify team folders.';
				break;

			case 'other':
				$message = 'There was an unknown error when uploading the file.';
				break;
		}

		trigger_error( $message, E_USER_WARNING );
	}
}
