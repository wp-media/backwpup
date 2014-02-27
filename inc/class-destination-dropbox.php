<?php
/**
 * Documentation: https://www.dropbox.com/developers/reference/api
 */
class BackWPup_Destination_Dropbox extends BackWPup_Destinations {

	/**
	 * @var $backwpup_job_object BackWPup_Job
	 */
	public static $backwpup_job_object = NULL;

	/**
	 * @return array
	 */
	public function option_defaults() {

		return array( 'dropboxtoken' => '', 'dropboxsecret' => '', 'dropboxroot' => 'sandbox', 'dropboxmaxbackups' => 15, 'dropboxsyncnodelete' => TRUE, 'dropboxdir' => trailingslashit( sanitize_file_name( get_bloginfo( 'name' ) ) ) );
	}


	/**
	 * @param $jobid
	 */
	public function edit_tab( $jobid ) {

		//Dropbox auth keys from Dropbox
		// if cancelled auth
		if ( ! empty( $_GET[ 'not_approved' ] ) ) {
			echo '<div id="message" class="error"><p>' .__( 'Dropbox authentication not approved', 'backwpup' ) . '</p></div>';
			delete_site_transient( 'backwpup_dropbox_auth_' . $jobid );
		}
		// if Auth data exists
		$auth_data = get_site_transient( 'backwpup_dropbox_auth_' . $jobid );
		if ( $auth_data ) {
			$oAuthStuff = array();
			try {
				$dropbox    = new BackWPup_Destination_Dropbox_API( $auth_data[ 'type' ] );
				$oAuthStuff = $dropbox->oAuthAccessToken( $auth_data[ 'oauth_token' ], $auth_data[ 'oauth_token_secret' ] );
				if ( ! empty( $oAuthStuff ) ) {
					echo '<div id="message" class="updated below-h2"><p>' .  __( 'Dropbox authentication complete!', 'backwpup' ) . '</p></div>';
					BackWPup_Option::update( $jobid, 'dropboxtoken', $oAuthStuff[ 'oauth_token' ] );
					BackWPup_Option::update( $jobid, 'dropboxsecret', BackWPup_Encryption::encrypt( $oAuthStuff[ 'oauth_token_secret' ] ) );
					BackWPup_Option::update( $jobid, 'dropboxroot', $auth_data[ 'type' ] );
					delete_site_transient( 'backwpup_dropbox_auth_' . $jobid );
				}
			} catch ( Exception $e ) {
				echo '<div  id="message" class="error"><p>' . sprintf( __( 'Dropbox API: %s', 'backwpup' ), $e->getMessage() ) . '</p></div>';
				delete_site_transient( 'backwpup_dropbox_auth_' . $jobid );
			}
		}
		?>

    <h3 class="title"><?php _e( 'Login', 'backwpup' ); ?></h3>
    <p></p>
    <table class="form-table">
        <tr>
            <th scope="row"><?php _e( 'Authenticate', 'backwpup' ); ?></th>
            <td><?php if ( ! BackWPup_Option::get( $jobid, 'dropboxtoken' ) && ! BackWPup_Option::get( $jobid, 'dropboxsecret' ) && ! isset( $oAuthStuff[ 'oauth_token' ] ) ) { ?>
               		<span style="color:red;"><?php _e( 'Not authenticated!', 'backwpup' ); ?></span>&nbsp;<a href="http://db.tt/8irM1vQ0"><?php _e( 'Create Account', 'backwpup' ); ?></a><br />
				<?php } else { ?>
                	<span style="color:green;"><?php _e( 'Authenticated!', 'backwpup' ); ?></span><br />
				<?php } ?>
				<a class="button secondary" href="<?php echo admin_url( 'admin-ajax.php', 'relative' );?>?action=backwpup_dest_dropbox&type=sandbox&jobid=<?php echo $jobid ?>"><?php _e( 'Reauthenticate (Sandbox)', 'backwpup' ); ?></a>&nbsp;
    			<a class="button secondary" href="<?php echo admin_url( 'admin-ajax.php', 'relative' );?>?action=backwpup_dest_dropbox&type=dropbox&jobid=<?php echo $jobid ?>"><?php _e( 'Reauthenticate (full Dropbox)', 'backwpup' ); ?></a>
            </td>
        </tr>
    </table>


    <h3 class="title"><?php _e( 'Backup settings', 'backwpup' ); ?></h3>
    <p></p>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="iddropboxdir"><?php _e( 'Folder in Dropbox', 'backwpup' ); ?></label></th>
            <td>
                <input id="iddropboxdir" name="dropboxdir" type="text" value="<?php echo esc_attr( BackWPup_Option::get( $jobid, 'dropboxdir' ) ); ?>" class="regular-text" />
            </td>
        </tr>
        <tr>
            <th scope="row"><?php _e( 'File Deletion', 'backwpup' ); ?></th>
            <td>
				<?php
				if ( BackWPup_Option::get( $jobid, 'backuptype' ) == 'archive' ) {
					?>
                    <label for="iddropboxmaxbackups"><input id="iddropboxmaxbackups" name="dropboxmaxbackups" title="<?php esc_attr_e( 'Oldest files will be deleted first. 0 = no deletion', 'backwpup' ); ?>" type="text" size="3" value="<?php echo esc_attr( BackWPup_Option::get( $jobid, 'dropboxmaxbackups' ) );?>" class="small-text help-tip" />&nbsp;
					<?php  _e( 'Number of files to keep in folder.', 'backwpup' ); ?></label>
					<?php } else { ?>
                    <label for="iddropboxsyncnodelete" ><input class="checkbox" value="1"
                           type="checkbox" <?php checked( BackWPup_Option::get( $jobid, 'dropboxsyncnodelete' ), TRUE ); ?>
                           name="dropboxsyncnodelete" id="iddropboxsyncnodelete" /> <?php _e( 'Do not delete files while syncing to destination!', 'backwpup' ); ?></label>
					<?php } ?>
            </td>
        </tr>
    </table>

	<?php
	}

	/**
	 * Authentication over ajax
	 */
	public function edit_ajax() {

		$_GET[ 'jobid' ] = (int) $_GET[ 'jobid' ];

		// dropbox auth
		if ( $_GET[ 'type' ] == 'dropbox' ) {
			try {
				$dropbox = new BackWPup_Destination_Dropbox_API( 'dropbox' );
				// let the user authorize (user will be redirected)
				$response = $dropbox->oAuthAuthorize( network_admin_url( 'admin.php' ) . '?page=backwpupeditjob&jobid=' .$_GET[ 'jobid' ] .'&tab=dest-dropbox&_wpnonce=' . wp_create_nonce( 'edit-job' ) );
				// save oauth_token_secret
				$auth_data = array(
									 'oauth_token'        => $response[ 'oauth_token' ],
									 'oauth_token_secret' => $response[ 'oauth_token_secret' ],
									 'type'				  => 'dropbox'
									);
				set_site_transient( 'backwpup_dropbox_auth_' . $_GET[ 'jobid' ], $auth_data, 3600 );
				wp_redirect( $response[ 'authurl' ] );

			}
			catch ( Exception $e ) {
				BackWPup_Admin::message( sprintf( __( 'Dropbox API: %s', 'backwpup' ), $e->getMessage() ), true );
				wp_redirect( network_admin_url( 'admin.php' ) . '?page=backwpupeditjob&jobid=' .$_GET[ 'jobid' ] .'&tab=dest-dropbox&_wpnonce=' . wp_create_nonce( 'edit-job' ) );
			}
		}
		// sandbox auth
		elseif ( $_GET[ 'type' ] == 'sandbox' ) {
			try {
				$dropbox = new BackWPup_Destination_Dropbox_API( 'sandbox' );
				// let the user authorize (user will be redirected)
				$response = $dropbox->oAuthAuthorize( network_admin_url( 'admin.php' ) . '?page=backwpupeditjob&jobid=' .$_GET[ 'jobid' ] .'&tab=dest-dropbox&_wpnonce=' . wp_create_nonce( 'edit-job' ) );
				// save oauth_token_secret
				$auth_data = array(
									 'oauth_token'        => $response[ 'oauth_token' ],
									 'oauth_token_secret' => $response[ 'oauth_token_secret' ],
									 'type'				  => 'sandbox'
									);
				set_site_transient(  'backwpup_dropbox_auth_' . $_GET[ 'jobid' ], $auth_data, 3600 );
				wp_redirect( $response[ 'authurl' ] );
			}
			catch ( Exception $e ) {
				BackWPup_Admin::message( sprintf( __( 'Dropbox API: %s', 'backwpup' ), $e->getMessage() ), true );
				wp_redirect( network_admin_url( 'admin.php' ) . '?page=backwpupeditjob&jobid=' .$_GET[ 'jobid' ] .'&tab=dest-dropbox&_wpnonce=' . wp_create_nonce( 'edit-job' ) );
			}
		}
	}


	/**
	 * @param $jobid
	 * @return string|void
	 */
	public function edit_form_post_save( $jobid ) {

		BackWPup_Option::update( $jobid, 'dropboxsyncnodelete', ( isset( $_POST[ 'dropboxsyncnodelete' ] ) && $_POST[ 'dropboxsyncnodelete' ] == 1 ) ? TRUE : FALSE );
		BackWPup_Option::update( $jobid, 'dropboxmaxbackups', isset( $_POST[ 'dropboxmaxbackups' ] ) ? (int)$_POST[ 'dropboxmaxbackups' ] : 0 );

		$_POST[ 'dropboxdir' ] = trailingslashit( str_replace( '//', '/', str_replace( '\\', '/', trim( stripslashes( $_POST[ 'dropboxdir' ] ) ) ) ) );
		if ( substr( $_POST[ 'dropboxdir' ], 0, 1 ) == '/' )
			$_POST[ 'dropboxdir' ] = substr( $_POST[ 'dropboxdir' ], 1 );
		if ( $_POST[ 'dropboxdir' ] == '/' )
			$_POST[ 'dropboxdir' ] = '';
		BackWPup_Option::update( $jobid, 'dropboxdir', $_POST[ 'dropboxdir' ] );

	}

	/**
	 * @param $jobdest
	 * @param $backupfile
	 */
	public function file_delete( $jobdest, $backupfile ) {

		$files = get_site_transient( 'backwpup_' . strtolower( $jobdest ) );
		list( $jobid, $dest ) = explode( '_', $jobdest );

		if ( BackWPup_Option::get( $jobid, 'dropboxtoken' ) && BackWPup_Option::get( $jobid, 'dropboxsecret' ) ) {
			try {
				$dropbox = new BackWPup_Destination_Dropbox_API( BackWPup_Option::get( $jobid, 'dropboxroot' ) );
				$dropbox->setOAuthTokens( BackWPup_Option::get( $jobid, 'dropboxtoken' ), BackWPup_Encryption::decrypt( BackWPup_Option::get( $jobid, 'dropboxsecret' ) ) );
				$dropbox->fileopsDelete( $backupfile );
				//update file list
				foreach ( $files as $key => $file ) {
					if ( is_array( $file ) && $file[ 'file' ] == $backupfile )
						unset( $files[ $key ] );
				}
				unset( $dropbox );
			}
			catch ( Exception $e ) {
				BackWPup_Admin::message( 'DROPBOX: ' . $e->getMessage(), TRUE );
			}
		}
		set_site_transient( 'backwpup_' . strtolower( $jobdest ), $files, 60 * 60 * 24 * 7 );
	}

	/**
	 * @param $jobid
	 * @param $get_file
	 */
	public function file_download( $jobid, $get_file ) {

		try {
			$dropbox = new BackWPup_Destination_Dropbox_API( BackWPup_Option::get( $jobid, 'dropboxroot' ) );
			$dropbox->setOAuthTokens( BackWPup_Option::get( $jobid, 'dropboxtoken' ), BackWPup_Encryption::decrypt( BackWPup_Option::get( $jobid, 'dropboxsecret' ) ) );
			$media = $dropbox->media( $get_file );
			if ( ! empty( $media[ 'url' ] ) )
				header( "Location: " . $media[ 'url' ] );
			die();
		}
		catch ( Exception $e ) {
			die( $e->getMessage() );
		}
	}

	/**
	 * @param $jobdest
	 * @return mixed
	 */
	public function file_get_list( $jobdest ) {
		return get_site_transient( 'BackWPup_' . $jobdest );
	}

	/**
	 * @param $job_object
	 * @return bool
	 */
	public function job_run_archive( &$job_object ) {

		$job_object->substeps_todo = 2 + $job_object->backup_filesize;
		if ( $job_object->steps_data[ $job_object->step_working ]['SAVE_STEP_TRY'] != $job_object->steps_data[ $job_object->step_working ][ 'STEP_TRY' ] )
			$job_object->log( sprintf( __( '%d. Try to send backup file to Dropbox&#160;&hellip;', 'backwpup' ), $job_object->steps_data[ $job_object->step_working ][ 'STEP_TRY' ] ), E_USER_NOTICE );

		try {
			$dropbox = new BackWPup_Destination_Dropbox_API( $job_object->job[ 'dropboxroot' ] );
			// set the tokens
			$dropbox->setOAuthTokens( $job_object->job[ 'dropboxtoken' ], BackWPup_Encryption::decrypt( $job_object->job[ 'dropboxsecret' ] ) );
			//get account info
			if ( $job_object->steps_data[ $job_object->step_working ]['SAVE_STEP_TRY'] != $job_object->steps_data[ $job_object->step_working ][ 'STEP_TRY' ] ) {
				$info = $dropbox->accountInfo();
				if ( ! empty( $info[ 'uid' ] ) ) {
					$job_object->log( sprintf( __( 'Authenticated with Dropbox of user %s', 'backwpup' ), $info[ 'display_name' ] . ' (' . $info[ 'email' ] . ')' ), E_USER_NOTICE );
				} else {
					$job_object->log( __( 'Not Authenticated with Dropbox!', 'backwpup' ), E_USER_ERROR );
					return FALSE;
				}
				// put the file
				$job_object->log( __( 'Uploading to Dropbox&#160;&hellip;', 'backwpup' ), E_USER_NOTICE );
			}

			self::$backwpup_job_object = &$job_object;

			if ( $job_object->substeps_done < $job_object->backup_filesize ) { //only if upload not complete
				$response = $dropbox->upload( $job_object->backup_folder . $job_object->backup_file, $job_object->job[ 'dropboxdir' ] . $job_object->backup_file );
				if ( $response[ 'bytes' ] == $job_object->backup_filesize ) {
					if ( ! empty( $job_object->job[ 'jobid' ] ) )
						BackWPup_Option::update(  $job_object->job[ 'jobid' ], 'lastbackupdownloadurl', network_admin_url( 'admin.php' ) . '?page=backwpupbackups&action=downloaddropbox&file=' . ltrim( $response[ 'path' ], '/' ) . '&jobid=' . $job_object->job[ 'jobid' ] );
					$job_object->substeps_done = 1 + $job_object->backup_filesize;
					$job_object->log( sprintf( __( 'Backup transferred to %s', 'backwpup' ), 'https://api-content.dropbox.com/1/files/' . $job_object->job[ 'dropboxroot' ] . $response[ 'path' ] ), E_USER_NOTICE );
				}
				else {
					if ( $response[ 'bytes' ] != $job_object->backup_filesize )
						$job_object->log( __( 'Uploaded file size and local file size don\'t match.', 'backwpup' ), E_USER_ERROR );
					else
						$job_object->log(
										sprintf(
											__( 'Error transfering backup to %s.', 'backwpup' ) . ' ' . $response[ 'error' ],
											__( 'Dropbox', 'backwpup' )
										), E_USER_ERROR	);

					return FALSE;
				}
			}


			$backupfilelist = array();
			$filecounter    = 0;
			$files          = array();
			$metadata       = $dropbox->metadata( $job_object->job[ 'dropboxdir' ] );
			if ( is_array( $metadata ) ) {
				foreach ( $metadata[ 'contents' ] as $data ) {
					if ( $data[ 'is_dir' ] != TRUE ) {
						$file = basename( $data[ 'path' ] );
						if ( $job_object->is_backup_archive( $file ) )
							$backupfilelist[ strtotime( $data[ 'modified' ] ) ] = $file;
						$files[ $filecounter ][ 'folder' ]      = "https://api-content.dropbox.com/1/files/" . $job_object->job[ 'dropboxroot' ]  . dirname( $data[ 'path' ] ) . "/";
						$files[ $filecounter ][ 'file' ]        = $data[ 'path' ];
						$files[ $filecounter ][ 'filename' ]    = basename( $data[ 'path' ] );
						$files[ $filecounter ][ 'downloadurl' ] = network_admin_url( 'admin.php' ) . '?page=backwpupbackups&action=downloaddropbox&file=' . $data[ 'path' ] . '&jobid=' . $job_object->job[ 'jobid' ];
						$files[ $filecounter ][ 'filesize' ]    = $data[ 'bytes' ];
						$files[ $filecounter ][ 'time' ]        = strtotime( $data[ 'modified' ] ) + ( get_option( 'gmt_offset' ) * 3600 );
						$filecounter ++;
					}
				}
			}
			if ( $job_object->job[ 'dropboxmaxbackups' ] > 0 && is_object( $dropbox ) ) { //Delete old backups
				if ( count( $backupfilelist ) > $job_object->job[ 'dropboxmaxbackups' ] ) {
					ksort( $backupfilelist );
					$numdeltefiles = 0;
					while ( $file = array_shift( $backupfilelist ) ) {
						if ( count( $backupfilelist ) < $job_object->job[ 'dropboxmaxbackups' ] )
							break;
						$response = $dropbox->fileopsDelete( $job_object->job[ 'dropboxdir' ] . $file ); //delete files on Cloud
						if ( $response[ 'is_deleted' ] == 'true' ) {
							foreach ( $files as $key => $filedata ) {
								if ( $filedata[ 'file' ] == '/' .$job_object->job[ 'dropboxdir' ] . $file )
									unset( $files[ $key ] );
							}
							$numdeltefiles ++;
						}
						else
							$job_object->log( sprintf( __( 'Error while deleting file from Dropbox: %s', 'backwpup' ), $file ), E_USER_ERROR );
					}
					if ( $numdeltefiles > 0 )
						$job_object->log( sprintf( _n( 'One file deleted from Dropbox', '%d files deleted on Dropbox', $numdeltefiles, 'backwpup' ), $numdeltefiles ), E_USER_NOTICE );
				}
			}
			set_site_transient( 'backwpup_' . $job_object->job[ 'jobid' ] . '_dropbox', $files, 60 * 60 * 24 * 7 );
		}
		catch ( Exception $e ) {
			$job_object->log( E_USER_ERROR, sprintf( __( 'Dropbox API: %s', 'backwpup' ), htmlentities( $e->getMessage() ) ), $e->getFile(), $e->getLine() );

			return FALSE;
		}
		$job_object->substeps_done ++;

		return TRUE;
	}

	/**
	 * @param $job_object
	 * @return bool
	 */
	public function can_run( $job_object ) {

		if ( empty( $job_object->job[ 'dropboxtoken' ] ) )
			return FALSE;

		if ( empty( $job_object->job[ 'dropboxsecret' ] ) )
			return FALSE;

		return TRUE;
	}

}


/**
 *
 */
final class BackWPup_Destination_Dropbox_API {

	/**
	 *
	 */
	const API_URL         = 'https://api.dropbox.com/';

	/**
	 *
	 */
	const API_CONTENT_URL = 'https://api-content.dropbox.com/';

	/**
	 *
	 */
	const API_WWW_URL     = 'https://www.dropbox.com/';

	/**
	 *
	 */
	const API_VERSION_URL = '1/';

	/**
	 * dropbox vars
	 *
	 * @var string
	 */
	private $root = 'sandbox';

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
	 * @var string
	 */
	private $oauth_token_secret = '';


	/**
	 * @param string $boxtype
	 * @throws BackWPup_Destination_Dropbox_API_Exception
	 */
	public function __construct( $boxtype = 'dropbox' ) {

		if ( $boxtype == 'dropbox' ) {
			$this->oauth_app_key 	= get_site_option( 'backwpup_cfg_dropboxappkey', base64_decode( "dHZkcjk1MnRhZnM1NmZ2" ) );
			$this->oauth_app_secret = BackWPup_Encryption::decrypt( get_site_option( 'backwpup_cfg_dropboxappsecret', base64_decode( "OWV2bDR5MHJvZ2RlYmx1" ) ) );
			$this->root             = 'dropbox';
		}
		else {
			$this->oauth_app_key 	= get_site_option( 'backwpup_cfg_dropboxsandboxappkey', base64_decode( "cHVrZmp1a3JoZHR5OTFk" ) );
			$this->oauth_app_secret = BackWPup_Encryption::decrypt( get_site_option( 'backwpup_cfg_dropboxsandboxappsecret', base64_decode( "eGNoYzhxdTk5eHE0eWdq" ) ) );
			$this->root             = 'sandbox';
		}

		if ( empty( $this->oauth_app_key ) or empty( $this->oauth_app_secret ) )
			throw new BackWPup_Destination_Dropbox_API_Exception( "No App key or App Secret specified." );
	}

	/**
	 * @param $token
	 * @param $secret
	 * @throws BackWPup_Destination_Dropbox_API_Exception
	 */
	public function setOAuthTokens( $token, $secret ) {

		$this->oauth_token        = $token;
		$this->oauth_token_secret = $secret;

		if ( empty( $this->oauth_token ) or empty( $this->oauth_token_secret ) )
			throw new BackWPup_Destination_Dropbox_API_Exception( "No oAuth token or secret specified." );
	}

	/**
	 * @return array|mixed|string
	 */
	public function accountInfo() {

		$url = self::API_URL . self::API_VERSION_URL . 'account/info';

		return $this->request( $url );
	}

	/**
	 * @param        $file
	 * @param string $path
	 * @param bool   $overwrite
	 * @return array|mixed|string
	 * @throws BackWPup_Destination_Dropbox_API_Exception
	 */
	public function upload( $file, $path = '', $overwrite = TRUE ) {

		$file = str_replace( "\\", "/", $file );

		if ( ! is_readable( $file ) )
			throw new BackWPup_Destination_Dropbox_API_Exception( "Error: File \"$file\" is not readable or doesn't exist." );

		if ( filesize( $file ) < 5242880 ) { //chunk transfer on bigger uploads
			$url        = self::API_CONTENT_URL . self::API_VERSION_URL . 'files_put/' . $this->root . '/' . $this->encode_path( $path );
			$output     = $this->request( $url, array( 'overwrite' => ( $overwrite ) ? 'true' : 'false' ), 'PUT', file_get_contents( $file ) );
		}
		else {
			$output = $this->chunked_upload( $file, $path, $overwrite );
		}

		return $output;
	}

	/**
	 * @param        $file
	 * @param string $path
	 * @param bool   $overwrite
	 * @return array|mixed|string
	 * @throws BackWPup_Destination_Dropbox_API_Exception
	 */
	public function chunked_upload( $file, $path = '', $overwrite = TRUE ) {

		$backwpup_job_object = BackWPup_Destination_Dropbox::$backwpup_job_object;

		$file = str_replace( "\\", "/", $file );

		if ( ! is_readable( $file ) )
			throw new BackWPup_Destination_Dropbox_API_Exception( "Error: File \"$file\" is not readable or doesn't exist." );

		$chunk_size = 4194304; //4194304 = 4MB

		$file_handel = fopen( $file, 'rb' );
		if ( ! is_resource( $file_handel ) )
			throw new BackWPup_Destination_Dropbox_API_Exception( "Can not open source file for transfer." );

		if ( ! isset( $backwpup_job_object->steps_data[ $backwpup_job_object->step_working ][ 'uploadid' ] ) )
			$backwpup_job_object->steps_data[ $backwpup_job_object->step_working ][ 'uploadid' ] = NULL;
		if ( ! isset( $backwpup_job_object->steps_data[ $backwpup_job_object->step_working ][ 'offset' ] ) )
			$backwpup_job_object->steps_data[ $backwpup_job_object->step_working ][ 'offset' ] = 0;

		//seek to current position
		if ( $backwpup_job_object->steps_data[ $backwpup_job_object->step_working ][ 'offset' ] > 0 )
			fseek( $file_handel, $backwpup_job_object->steps_data[ $backwpup_job_object->step_working ][ 'offset' ] );

		while ( $data = fread( $file_handel, $chunk_size ) ) {
			$chunk_upload_start = microtime( TRUE );
			$url    = self::API_CONTENT_URL . self::API_VERSION_URL . 'chunked_upload';
			$output = $this->request( $url, array( 'upload_id' => $backwpup_job_object->steps_data[ $backwpup_job_object->step_working ][ 'uploadid' ], 'offset' => $backwpup_job_object->steps_data[ $backwpup_job_object->step_working ][ 'offset' ] ), 'PUT', $data );
			$chunk_upload_time = microtime( TRUE ) - $chunk_upload_start;
			//args for next chunk
			$backwpup_job_object->steps_data[ $backwpup_job_object->step_working ][ 'offset' ]   = $output[ 'offset' ];
			$backwpup_job_object->steps_data[ $backwpup_job_object->step_working ][ 'uploadid' ] = $output[ 'upload_id' ];
			if ( $backwpup_job_object->job[ 'backuptype' ] == 'archive' ) {
				$backwpup_job_object->substeps_done = $backwpup_job_object->steps_data[ $backwpup_job_object->step_working ][ 'offset' ];
				if ( strlen( $data ) == $chunk_size ) {
					$time_remaining = $backwpup_job_object->do_restart_time();
					//calc next chunk
					if ( $time_remaining < $chunk_upload_time ) {
						$chunk_size = floor ( $chunk_size / $chunk_upload_time * ( $time_remaining - 3 ) );
						if ( $chunk_size < 0 )
							$chunk_size = 1024;
						if ( $chunk_size > 4194304 )
							$chunk_size = 4194304;
					}
				}
			}
			$backwpup_job_object->update_working_data();
			//correct position
			fseek( $file_handel, $backwpup_job_object->steps_data[ $backwpup_job_object->step_working ][ 'offset' ] );
		}

		fclose( $file_handel );

		$url = self::API_CONTENT_URL . self::API_VERSION_URL . 'commit_chunked_upload/' . $this->root . '/' . $this->encode_path( $path );

		return $this->request( $url, array( 'overwrite' => ( $overwrite ) ? 'true' : 'false', 'upload_id' => $backwpup_job_object->steps_data[ $backwpup_job_object->step_working ][ 'uploadid' ] ), 'POST' );
	}

	/**
	 * @param      $path
	 * @param bool $echo
	 * @return string
	 */
	public function download( $path, $echo = FALSE ) {

		$url = self::API_CONTENT_URL . self::API_VERSION_URL . 'files/' . $this->root . '/' .  $path;
		if ( ! $echo )
			return $this->request( $url );
		else {
			$this->request( $url, NULL, 'GET', '', TRUE );
			return '';
		}
	}

	/**
	 * @param string $path
	 * @param bool   $listContents
	 * @param int    $fileLimit
	 * @param string $hash
	 * @return array|mixed|string
	 */
	public function metadata( $path = '', $listContents = TRUE, $fileLimit = 10000, $hash = '' ) {

		$url = self::API_URL . self::API_VERSION_URL . 'metadata/' . $this->root . '/' . $this->encode_path( $path );

		return $this->request( $url, array(
										  'list'       => ( $listContents ) ? 'true' : 'false',
										  'hash'       => ( $hash ) ? $hash : '',
										  'file_limit' => $fileLimit
									 ) );
	}

	/**
	 * @param string $path
	 * @return array|mixed|string
	 */
	public function media( $path = '' ) {

		$url = self::API_URL . self::API_VERSION_URL . 'media/' . $this->root . '/' . $path;

		return $this->request( $url );
	}

	/**
	 * @param $path
	 * @return array|mixed|string
	 */
	public function fileopsDelete( $path ) {

		$url = self::API_URL . self::API_VERSION_URL . 'fileops/delete';

		return $this->request( $url, array(
										  'path' => '/' . $path,
										  'root' => $this->root
									 ) );
	}

	/**
	 * @param $callback_url
	 * @return array
	 * @throws BackWPup_Destination_Dropbox_API_Exception
	 */
	public function oAuthAuthorize( $callback_url ) {

		$headers[ ] = 'Authorization: OAuth oauth_version="1.0", oauth_signature_method="PLAINTEXT", oauth_consumer_key="' . $this->oauth_app_key . '", oauth_signature="' . $this->oauth_app_secret . '&"';
		$ch         = curl_init();
		curl_setopt( $ch, CURLOPT_URL, self::API_URL . self::API_VERSION_URL . 'oauth/request_token' );
		curl_setopt( $ch, CURLOPT_USERAGENT, BackWPup::get_plugin_data( 'User-Agent' ) );
		if ( BackWPup::get_plugin_data( 'cacert' ) ) {
			curl_setopt( $ch, CURLOPT_SSLVERSION, 3 );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, TRUE );
			curl_setopt( $ch, CURLOPT_SSL_CIPHER_LIST,
						 'ECDHE-RSA-AES256-GCM-SHA384:'.
						 'ECDHE-RSA-AES128-GCM-SHA256:'.
						 'ECDHE-RSA-AES256-SHA384:'.
						 'ECDHE-RSA-AES128-SHA256:'.
						 'ECDHE-RSA-AES256-SHA:'.
						 'ECDHE-RSA-AES128-SHA:'.
						 'ECDHE-RSA-RC4-SHA:'.
						 'DHE-RSA-AES256-GCM-SHA384:'.
						 'DHE-RSA-AES128-GCM-SHA256:'.
						 'DHE-RSA-AES256-SHA256:'.
						 'DHE-RSA-AES128-SHA256:'.
						 'DHE-RSA-AES256-SHA:'.
						 'DHE-RSA-AES128-SHA:'.
						 'AES256-GCM-SHA384:'.
						 'AES128-GCM-SHA256:'.
						 'AES256-SHA256:'.
						 'AES128-SHA256:'.
						 'AES256-SHA:'.
						 'AES128-SHA'
			);
			if ( defined( 'CURLOPT_PROTOCOLS' ) )
				curl_setopt( $ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS );
			if ( defined( 'CURLOPT_REDIR_PROTOCOLS' ) )
				curl_setopt( $ch, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTPS );
			curl_setopt( $ch, CURLOPT_CAINFO, BackWPup::get_plugin_data( 'cacert' ) );
			curl_setopt( $ch, CURLOPT_CAPATH, dirname( BackWPup::get_plugin_data( 'cacert' ) ) );
		} else {
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
		}
		curl_setopt( $ch, CURLOPT_AUTOREFERER, TRUE );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
		$content = curl_exec( $ch );
		$status  = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		if ( $status >= 200 && $status < 300 && 0 == curl_errno( $ch ) ) {
			parse_str( $content, $oauth_token );
		}
		else {
			$output = json_decode( $content, TRUE );
			if ( isset( $output[ 'error' ] ) && is_string( $output[ 'error' ] ) ) $message = $output[ 'error' ];
			elseif ( isset( $output[ 'error' ][ 'hash' ] ) && $output[ 'error' ][ 'hash' ] != '' ) $message = (string)$output[ 'error' ][ 'hash' ];
			elseif ( 0 != curl_errno( $ch ) ) $message = '(' . curl_errno( $ch ) . ') ' . curl_error( $ch );
			else $message = '(' . $status . ') Invalid response.';
			throw new BackWPup_Destination_Dropbox_API_Exception( $message );
		}
		curl_close( $ch );

		return array(
			'authurl'            => self::API_WWW_URL . self::API_VERSION_URL . 'oauth/authorize?oauth_token=' . $oauth_token[ 'oauth_token' ] . '&oauth_callback=' . urlencode( $callback_url ),
			'oauth_token'        => $oauth_token[ 'oauth_token' ],
			'oauth_token_secret' => $oauth_token[ 'oauth_token_secret' ]
		);
	}

	/**
	 * @param $oauth_token
	 * @param $oauth_token_secret
	 *
	 * @return array|null
	 * @throws BackWPup_Destination_Dropbox_API_Exception
	 */
	public function oAuthAccessToken( $oauth_token, $oauth_token_secret ) {

		$headers[ ] = 'Authorization: OAuth oauth_version="1.0", oauth_signature_method="PLAINTEXT", oauth_consumer_key="' . $this->oauth_app_key . '", oauth_token="' . $oauth_token . '", oauth_signature="' . $this->oauth_app_secret . '&' . $oauth_token_secret . '"';
		$ch         = curl_init();
		curl_setopt( $ch, CURLOPT_URL, self::API_URL . self::API_VERSION_URL . 'oauth/access_token' );
		curl_setopt( $ch, CURLOPT_USERAGENT, BackWPup::get_plugin_data( 'User-Agent' ) );
		if ( BackWPup::get_plugin_data( 'cacert' ) ) {
			curl_setopt( $ch, CURLOPT_SSLVERSION, 3 );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, TRUE );
			curl_setopt( $ch, CURLOPT_SSL_CIPHER_LIST,
						 'ECDHE-RSA-AES256-GCM-SHA384:'.
						 'ECDHE-RSA-AES128-GCM-SHA256:'.
						 'ECDHE-RSA-AES256-SHA384:'.
						 'ECDHE-RSA-AES128-SHA256:'.
						 'ECDHE-RSA-AES256-SHA:'.
						 'ECDHE-RSA-AES128-SHA:'.
						 'ECDHE-RSA-RC4-SHA:'.
						 'DHE-RSA-AES256-GCM-SHA384:'.
						 'DHE-RSA-AES128-GCM-SHA256:'.
						 'DHE-RSA-AES256-SHA256:'.
						 'DHE-RSA-AES128-SHA256:'.
						 'DHE-RSA-AES256-SHA:'.
						 'DHE-RSA-AES128-SHA:'.
						 'AES256-GCM-SHA384:'.
						 'AES128-GCM-SHA256:'.
						 'AES256-SHA256:'.
						 'AES128-SHA256:'.
						 'AES256-SHA:'.
						 'AES128-SHA'
			);
			if ( defined( 'CURLOPT_PROTOCOLS' ) )
				curl_setopt( $ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS );
			if ( defined( 'CURLOPT_REDIR_PROTOCOLS' ) )
				curl_setopt( $ch, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTPS );
			curl_setopt( $ch, CURLOPT_CAINFO, BackWPup::get_plugin_data( 'cacert' ) );
			curl_setopt( $ch, CURLOPT_CAPATH, dirname( BackWPup::get_plugin_data( 'cacert' ) ) );
		} else {
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
		}
		curl_setopt( $ch, CURLOPT_AUTOREFERER, TRUE );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
		$content = curl_exec( $ch );
		$status  = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		if ( $status >= 200 && $status < 300 && 0 == curl_errno( $ch ) ) {
			parse_str( $content, $oauth_token );
			$this->setOAuthTokens( $oauth_token[ 'oauth_token' ], $oauth_token[ 'oauth_token_secret' ] );

			return $oauth_token;
		}
		else {
			$output = json_decode( $content, TRUE );
			if ( isset( $output[ 'error' ] ) && is_string( $output[ 'error' ] ) ) $message = $output[ 'error' ];
			elseif ( isset( $output[ 'error' ][ 'hash' ] ) && $output[ 'error' ][ 'hash' ] != '' ) $message = (string)$output[ 'error' ][ 'hash' ];
			elseif ( 0 != curl_errno( $ch ) ) $message = '(' . curl_errno( $ch ) . ') ' . curl_error( $ch );
			else $message = '(' . $status . ') Invalid response.';
			throw new BackWPup_Destination_Dropbox_API_Exception( $message );
		}
	}

	/**
	 * @param        $url
	 * @param array  $args
	 * @param string $method
	 * @param string $data
	 * @param bool   $echo
	 *
	 * @throws BackWPup_Destination_Dropbox_API_Exception
	 * @internal param null $file
	 * @return array|mixed|string
	 */
	private function request( $url, $args = array(), $method = 'GET', $data = '', $echo = FALSE ) {

		/* Header*/
		$headers[ ] = 'Authorization: OAuth oauth_version="1.0", oauth_signature_method="PLAINTEXT", oauth_consumer_key="' . $this->oauth_app_key . '", oauth_token="' . $this->oauth_token . '", oauth_signature="' . $this->oauth_app_secret . '&' . $this->oauth_token_secret . '"';
		$headers[ ] = 'Expect:';

		/* Build cURL Request */
		$ch = curl_init();
		if ( $method == 'POST' ) {
			curl_setopt( $ch, CURLOPT_POST, TRUE );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $args );
			curl_setopt( $ch, CURLOPT_URL, $url );
		}
		elseif ( $method == 'PUT' ) {
			curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'PUT' );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
			$headers[ ] = 'Content-Type: application/octet-stream';
			$args = ( is_array( $args ) ) ? '?' . http_build_query( $args, '', '&' ) : $args;
			curl_setopt( $ch, CURLOPT_URL, $url . $args );
		}
		else {
			curl_setopt( $ch, CURLOPT_BINARYTRANSFER, TRUE );
			$args = ( is_array( $args ) ) ? '?' . http_build_query( $args, '', '&' ) : $args;
			curl_setopt( $ch, CURLOPT_URL, $url . $args );
		}
		curl_setopt( $ch, CURLOPT_USERAGENT, BackWPup::get_plugin_data( 'User-Agent' ) );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
		if ( BackWPup::get_plugin_data( 'cacert' ) ) {
			curl_setopt( $ch, CURLOPT_SSLVERSION, 3 );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, TRUE );
			curl_setopt( $ch, CURLOPT_SSL_CIPHER_LIST,
						 'ECDHE-RSA-AES256-GCM-SHA384:'.
						 'ECDHE-RSA-AES128-GCM-SHA256:'.
						 'ECDHE-RSA-AES256-SHA384:'.
						 'ECDHE-RSA-AES128-SHA256:'.
						 'ECDHE-RSA-AES256-SHA:'.
						 'ECDHE-RSA-AES128-SHA:'.
						 'ECDHE-RSA-RC4-SHA:'.
						 'DHE-RSA-AES256-GCM-SHA384:'.
						 'DHE-RSA-AES128-GCM-SHA256:'.
						 'DHE-RSA-AES256-SHA256:'.
						 'DHE-RSA-AES128-SHA256:'.
						 'DHE-RSA-AES256-SHA:'.
						 'DHE-RSA-AES128-SHA:'.
						 'AES256-GCM-SHA384:'.
						 'AES128-GCM-SHA256:'.
						 'AES256-SHA256:'.
						 'AES128-SHA256:'.
						 'AES256-SHA:'.
						 'AES128-SHA'
			);
			if ( defined( 'CURLOPT_PROTOCOLS' ) )
				curl_setopt( $ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS );
			if ( defined( 'CURLOPT_REDIR_PROTOCOLS' ) )
				curl_setopt( $ch, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTPS );
			curl_setopt( $ch, CURLOPT_CAINFO, BackWPup::get_plugin_data( 'cacert' ) );
			curl_setopt( $ch, CURLOPT_CAPATH, dirname( BackWPup::get_plugin_data( 'cacert' ) ) );
		} else {
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
		}
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
		$output = '';
		if ( $echo ) {
			echo curl_exec( $ch );
		}
		else {
			curl_setopt( $ch, CURLOPT_HEADER, TRUE );
			if ( 0 == curl_errno( $ch ) ) {
				$responce = explode( "\r\n\r\n", curl_exec( $ch ), 2 );
				if ( ! empty( $responce[ 1 ] ) )
					$output = json_decode( $responce[ 1 ], TRUE );
			}
		}
		$status = curl_getinfo( $ch );
		if ( isset( $datafilefd ) && is_resource( $datafilefd ) )
			fclose( $datafilefd );

		if ( $status[ 'http_code' ] == 503 ) {
			$wait = 0;
			if ( preg_match( "/retry-after:(.*?)\r/i", $responce[ 0 ], $matches ) )
				$wait = trim( $matches[ 1 ] );
			//only wait if we get a retry-after header.
			if ( ! empty( $wait ) ) {
				trigger_error( sprintf( '(503) Your app is making too many requests and is being rate limited. Error 503 can be triggered on a per-app or per-user basis. Wait for %d seconds.', $wait ), E_USER_WARNING );
				sleep( $wait );
			} else {
				trigger_error( '(503) Service unavailable. Retrying.', E_USER_WARNING );
			}
			//redo request
			return $this->request( $url, $args, $method, $data, $echo );
		}
		elseif ( $status[ 'http_code' ] == 400 && $method == 'PUT' ) {	//correct offset on chunk uploads
			trigger_error( '(' . $status[ 'http_code' ] . ') False offset will corrected', E_USER_NOTICE );
			return $output;
		}
		elseif ( $status[ 'http_code' ] == 404 && ! empty( $output[ 'error' ] )) {
			trigger_error( '(' . $status[ 'http_code' ] . ') ' . $output[ 'error' ], E_USER_WARNING );

			return FALSE;
		}
		elseif ( isset( $output[ 'error' ] ) || $status[ 'http_code' ] >= 300 || $status[ 'http_code' ] < 200 || curl_errno( $ch ) > 0 ) {
			if ( isset( $output[ 'error' ] ) && is_string( $output[ 'error' ] ) ) $message = '(' . $status[ 'http_code' ] . ') ' . $output[ 'error' ];
			elseif ( isset( $output[ 'error' ][ 'hash' ] ) && $output[ 'error' ][ 'hash' ] != '' ) $message = (string)'(' . $status[ 'http_code' ] . ') ' . $output[ 'error' ][ 'hash' ];
			elseif ( 0 != curl_errno( $ch ) ) $message = '(' . curl_errno( $ch ) . ') ' . curl_error( $ch );
			elseif ( $status[ 'http_code' ] == 304 ) $message = '(304) Folder contents have not changed (relies on hash parameter).';
			elseif ( $status[ 'http_code' ] == 400 ) $message = '(400) Bad input parameter: ' . strip_tags( $responce[ 1 ] );
			elseif ( $status[ 'http_code' ] == 401 ) $message = '(401) Bad or expired token. Please re-authenticate the user.';
			elseif ( $status[ 'http_code' ] == 403 ) $message = '(403) Bad OAuth request (wrong consumer key, bad nonce, expired timestamp,&hellip;)';
			elseif ( $status[ 'http_code' ] == 404 ) $message = '(404) File could not be found at the specified path or rev.';
			elseif ( $status[ 'http_code' ] == 405 ) $message = '(405) Request method not expected (generally should be GET,PUT or POST).';
			elseif ( $status[ 'http_code' ] == 406 ) $message = '(406) There are too many file entries to return.';
			elseif ( $status[ 'http_code' ] == 411 ) $message = '(411) Chunked encoding was attempted for this upload, but is not supported by Dropbox.';
			elseif ( $status[ 'http_code' ] == 415 ) $message = '(415) The image is invalid and cannot be thumbnailed.';
			elseif ( $status[ 'http_code' ] == 503 ) $message = '(503) Service unavailable.';
			elseif ( $status[ 'http_code' ] == 507 ) $message = '(507) User exceeding Dropbox storage quota.';
			else $message = '(' . $status[ 'http_code' ] . ') Invalid response.';
			throw new BackWPup_Destination_Dropbox_API_Exception( $message );
		}
		else {
			curl_close( $ch );
			if ( ! is_array( $output ) )
				return $responce[ 1 ];
			else
				return $output;
		}
	}

	/**
	 * @param $path
	 *
	 * @return mixed
	 */
	private function encode_path( $path ) {

		$path = preg_replace( '#/+#', '/', trim( $path, '/' ) );
		$path = str_replace( '%2F', '/', rawurlencode( $path ) );

		return $path;
	}
}

/**
 *
 */
class BackWPup_Destination_Dropbox_API_Exception extends Exception {

}