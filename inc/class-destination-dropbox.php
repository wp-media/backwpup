<?php

/**
 * This class allows the user to back up to Dropbox.
 *
 * Documentation: https://www.dropbox.com/developers/documentation/http/overview
 */
class BackWPup_Destination_Dropbox extends BackWPup_Destinations {

	/**
	 * @return array
	 */
	public function option_defaults() {
		return array(
			'dropboxtoken'        => array(),
			'dropboxroot'         => 'sandbox',
			'dropboxmaxbackups'   => 15,
			'dropboxsyncnodelete' => true,
			'dropboxdir'          => '/' . trailingslashit( sanitize_file_name( get_bloginfo( 'name' ) ) )
		);
	}

	/**
	 * @param $jobid
	 */
	public function edit_tab( $jobid ) {

		if ( ! empty( $_GET['deleteauth'] ) ) {
			//disable token on dropbox
			try {
				$dropbox = new BackWPup_Destination_Dropbox_API( BackWPup_Option::get( $jobid, 'dropboxroot' ) );
					$dropbox->setOAuthTokens( BackWPup_Option::get( $jobid, 'dropboxtoken' ) );
				$dropbox->authTokenRevoke();
			}
			catch ( Exception $e ) {
				echo '<div id="message" class="error"><p>' . sprintf( __( 'Dropbox API: %s', 'backwpup' ), $e->getMessage() ) . '</p></div>';
			}
			BackWPup_Option::update( $jobid, 'dropboxtoken', array() );
			BackWPup_Option::update( $jobid, 'dropboxroot', 'sandbox' );
		}

		$dropbox          = new BackWPup_Destination_Dropbox_API( 'dropbox' );
		$dropbox_auth_url = $dropbox->oAuthAuthorize();
		$dropbox          = new BackWPup_Destination_Dropbox_API( 'sandbox' );
		$sandbox_auth_url = $dropbox->oAuthAuthorize();

		$dropboxtoken = BackWPup_Option::get( $jobid, 'dropboxtoken' );
		?>

		<h3 class="title"><?php esc_html_e( 'Login', 'backwpup' ); ?></h3>
		<p></p>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Authentication', 'backwpup' ); ?></th>
				<td><?php if ( empty( $dropboxtoken['access_token'] ) ) { ?>
						<span style="color:red;"><?php esc_html_e( 'Not authenticated!', 'backwpup' ); ?></span><br/>&nbsp;<br/>
						<a class="button secondary"
						   href="http://db.tt/8irM1vQ0"><?php esc_html_e( 'Create Account', 'backwpup' ); ?></a>
					<?php } else { ?>
						<span style="color:green;"><?php esc_html_e( 'Authenticated!', 'backwpup' ); ?></span><br/>&nbsp;<br/>
						<a class="button secondary"
						   href="<?php echo wp_nonce_url(network_admin_url( 'admin.php?page=backwpupeditjob&deleteauth=1&jobid=' . $jobid . '&tab=dest-dropbox'), 'edit-job'  ); ?>"
						   title="<?php esc_html_e( 'Delete Dropbox Authentication', 'backwpup' ); ?>"><?php esc_html_e( 'Delete Dropbox Authentication', 'backwpup' ); ?></a>
					<?php } ?>
				</td>
			</tr>

			<?php if ( empty( $dropboxtoken['access_token'] ) ) { ?>
				<tr>
					<th scope="row"><label for="id_sandbox_code"><?php esc_html_e( 'App Access to Dropbox', 'backwpup' ); ?></label></th>
					<td>
						<input id="id_sandbox_code" name="sandbox_code" type="text" value="" class="regular-text code" />&nbsp;
						<a class="button secondary" href="<?php echo esc_attr( $sandbox_auth_url ); ?>" target="_blank"><?php esc_html_e( 'Get Dropbox App auth code', 'backwpup' ); ?></a>
						<p class="description"><?php esc_html_e( 'A dedicated folder named BackWPup will be created inside of the Apps folder in your Dropbox. BackWPup will get read and write access to that folder only. You can specify a subfolder as your backup destination for this job in the destination field below.', 'backwpup' ); ?></p>
					</td>
				</tr>
				<tr>
					<th></th>
					<td><?php esc_html_e( '— OR —', 'backwpup' ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="id_dropbbox_code"><?php esc_html_e( 'Full Access to Dropbox', 'backwpup' ); ?></label></th>
					<td>
						<input id="id_dropbbox_code" name="dropbbox_code" type="text" value="" class="regular-text code" />&nbsp;
						<a class="button secondary" href="<?php echo esc_attr( $dropbox_auth_url ); ?>" target="_blank"><?php esc_html_e( 'Get full Dropbox auth code ', 'backwpup' ); ?></a>
						<p class="description"><?php esc_html_e( 'BackWPup will have full read and write access to your entire Dropbox. You can specify your backup destination wherever you want, just be aware that ANY files or folders inside of your Dropbox can be overridden or deleted by BackWPup.', 'backwpup' ); ?></p>
					</td>
				</tr>
			<?php } ?>
		</table>


		<h3 class="title"><?php esc_html_e( 'Backup settings', 'backwpup' ); ?></h3>
		<p></p>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="iddropboxdir"><?php esc_html_e( 'Destination Folder', 'backwpup' ); ?></label></th>
				<td>
					<input id="iddropboxdir" name="dropboxdir" type="text" value="<?php echo esc_attr( BackWPup_Option::get( $jobid, 'dropboxdir' ) ); ?>" class="regular-text" />
					<p class="description">
						<?php esc_attr_e( 'Specify a subfolder where your backup archives will be stored. If you use the App option from above, this folder will be created inside of Apps/BackWPup. Otherwise it will be created at the root of your Dropbox. Already exisiting folders with the same name will not be overriden.', 'backwpup' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'File Deletion', 'backwpup' ); ?></th>
				<td>
					<?php
					if ( BackWPup_Option::get( $jobid, 'backuptype' ) === 'archive' ) {
						?>
						<label for="iddropboxmaxbackups">
							<input id="iddropboxmaxbackups" name="dropboxmaxbackups" type="number" min="0" step="1" value="<?php echo esc_attr( BackWPup_Option::get( $jobid, 'dropboxmaxbackups' ) ); ?>" class="small-text" />
							&nbsp;<?php esc_html_e( 'Number of files to keep in folder.', 'backwpup' ); ?>
						</label>
						<p><?php _e( '<strong>Warning</strong>: Files belonging to this job are now tracked. Old backup archives which are untracked will not be automatically deleted.', 'backwpup' ) ?></p>
					<?php } else { ?>
						<label for="iddropboxsyncnodelete">
							<input class="checkbox" value="1" type="checkbox" <?php checked( BackWPup_Option::get( $jobid, 'dropboxsyncnodelete' ), true ); ?> name="dropboxsyncnodelete" id="iddropboxsyncnodelete" />
							&nbsp;<?php esc_html_e( 'Do not delete files while syncing to destination!', 'backwpup' ); ?>
						</label>
					<?php } ?>
				</td>
			</tr>
		</table>

		<?php
	}

	/**
	 * @param $jobid
	 */
	public function edit_form_post_save( $jobid ) {

		// get auth
		if ( ! empty( $_POST['sandbox_code'] ) ) {
			try {
				$dropbox      = new BackWPup_Destination_Dropbox_API( 'sandbox' );
				$dropboxtoken = $dropbox->oAuthToken( $_POST['sandbox_code'] );
				BackWPup_Option::update( $jobid, 'dropboxtoken', $dropboxtoken );
				BackWPup_Option::update( $jobid, 'dropboxroot', 'sandbox' );
			}
			catch ( Exception $e ) {
				BackWPup_Admin::message( 'DROPBOX: ' . $e->getMessage(), true );
			}
		}

		if ( ! empty( $_POST['dropbbox_code'] ) ) {
			try {
				$dropbox      = new BackWPup_Destination_Dropbox_API( 'dropbox' );
				$dropboxtoken = $dropbox->oAuthToken( $_POST['dropbbox_code'] );
				BackWPup_Option::update( $jobid, 'dropboxtoken', $dropboxtoken );
				BackWPup_Option::update( $jobid, 'dropboxroot', 'dropbox' );
			}
			catch ( Exception $e ) {
				BackWPup_Admin::message( 'DROPBOX: ' . $e->getMessage(), true );
			}
		}

		BackWPup_Option::update( $jobid, 'dropboxsyncnodelete', ! empty( $_POST['dropboxsyncnodelete'] ) );
		BackWPup_Option::update( $jobid, 'dropboxmaxbackups', ! empty( $_POST['dropboxmaxbackups'] ) ? absint( $_POST['dropboxmaxbackups'] ) : 0 );

		$_POST['dropboxdir'] = trailingslashit( str_replace( '//', '/', str_replace( '\\', '/', trim( sanitize_text_field( $_POST['dropboxdir'] ) ) ) ) );
		if ( $_POST['dropboxdir'] === '/' ) {
			$_POST['dropboxdir'] = '';
		}
		BackWPup_Option::update( $jobid, 'dropboxdir', $_POST['dropboxdir'] );

	}

	/**
	 * @param $jobdest
	 * @param $backupfile
	 */
	public function file_delete( $jobdest, $backupfile ) {
		$files = get_site_transient( 'backwpup_' . strtolower( $jobdest ) );
		list( $jobid, $dest ) = explode( '_', $jobdest );

		try {
			$dropbox = new BackWPup_Destination_Dropbox_API( BackWPup_Option::get( $jobid, 'dropboxroot' ) );
			$dropbox->setOAuthTokens( BackWPup_Option::get( $jobid, 'dropboxtoken' ) );
			$dropbox->filesDelete( array( 'path' => $backupfile ) );

			//update file list
			foreach ( $files as $key => $file ) {
				if ( is_array( $file ) && $file['file'] == $backupfile ) {
					unset( $files[ $key ] );
				}
			}
			unset( $dropbox );
		}
		catch ( Exception $e ) {
			BackWPup_Admin::message( 'DROPBOX: ' . $e->getMessage(), true );
		}

		set_site_transient( 'backwpup_' . strtolower( $jobdest ), $files, YEAR_IN_SECONDS );
	}

	/**
	 * @param $jobid
	 * @param $get_file
	 */
	public function file_download( $jobid, $get_file ) {
		try {
			$dropbox = new BackWPup_Destination_Dropbox_API( BackWPup_Option::get( $jobid, 'dropboxroot' ) );
			$dropbox->setOAuthTokens( BackWPup_Option::get( $jobid, 'dropboxtoken' ) );
			$tempLink = $dropbox->filesGetTemporaryLink( array( 'path' => $get_file ) );
			if ( ! empty( $tempLink['link'] ) ) {
				header( "Location: " . $tempLink['link'] );
			}
			die();
		}
		catch ( Exception $e ) {
			die( $e->getMessage() );
		}
	}

	/**
	 * @param $jobdest
	 *
	 * @return mixed
	 */
	public function file_get_list( $jobdest ) {
		return get_site_transient( 'backwpup_' . strtolower( $jobdest ) );
	}

	/**
	 * @param $job_object
	 *
	 * @return bool
	 */
	public function job_run_archive( BackWPup_Job $job_object ) {
		$job_object->substeps_todo = 2 + $job_object->backup_filesize;
		if ( $job_object->steps_data[ $job_object->step_working ]['SAVE_STEP_TRY'] != $job_object->steps_data[ $job_object->step_working ]['STEP_TRY'] ) {
			$job_object->log( sprintf( __( '%d. Try to send backup file to Dropbox&#160;&hellip;', 'backwpup' ), $job_object->steps_data[ $job_object->step_working ]['STEP_TRY'] ) );
		}

		try {
			$dropbox = new BackWPup_Destination_Dropbox_API( $job_object->job['dropboxroot'], $job_object );
			$dropbox->setOAuthTokens( $job_object->job['dropboxtoken'] );

			//get account info
			if ( $job_object->steps_data[ $job_object->step_working ]['SAVE_STEP_TRY'] != $job_object->steps_data[ $job_object->step_working ]['STEP_TRY'] ) {
				$info = $dropbox->usersGetCurrentAccount();
				if ( ! empty( $info['account_id'] ) ) {
					if ( $job_object->is_debug() ) {
						$user = $info['name']['display_name'] . ' (' . $info['email'] . ')';
					}
					else {
						$user = $info['name']['display_name'];
					}
					$job_object->log( sprintf( __( 'Authenticated with Dropbox of user: %s', 'backwpup' ), $user ) );

					//Quota
					if ( $job_object->is_debug() ) {
						$quota = $dropbox->usersGetSpaceUsage();
						$dropboxfreespase = $quota['allocation']['allocated'] - $quota['used'];
						$job_object->log( sprintf( __( '%s available on your Dropbox', 'backwpup' ), size_format( $dropboxfreespase, 2 ) ) );
					}
				}
				else {
					$job_object->log( __( 'Not Authenticated with Dropbox!', 'backwpup' ), E_USER_ERROR );

					return false;
				}
				$job_object->log( __( 'Uploading to Dropbox&#160;&hellip;', 'backwpup' ) );
			}

			// put the file
			if ( $job_object->substeps_done < $job_object->backup_filesize ) { //only if upload not complete
				$response = $dropbox->upload( $job_object->backup_folder . $job_object->backup_file, $job_object->job['dropboxdir'] . $job_object->backup_file );
				if ( $response['size'] == $job_object->backup_filesize ) {
					if ( ! empty( $job_object->job['jobid'] ) ) {
						BackWPup_Option::update( $job_object->job['jobid'], 'lastbackupdownloadurl', network_admin_url( 'admin.php' ) . '?page=backwpupbackups&action=downloaddropbox&file=' . ltrim( $response['path_display'], '/' ) . '&jobid=' . $job_object->job['jobid'] );
					}
					$job_object->substeps_done = 1 + $job_object->backup_filesize;
					$job_object->log( sprintf( __( 'Backup transferred to %s', 'backwpup' ), $response['path_display'] ), E_USER_NOTICE );
				}
				else {
					if ( $response['size'] != $job_object->backup_filesize ) {
						$job_object->log( __( 'Uploaded file size and local file size don\'t match.', 'backwpup' ), E_USER_ERROR );
					}
					else {
						$job_object->log(
							sprintf(
								__( 'Error transfering backup to %s.', 'backwpup' ) . ' ' . $response['error'],
								__( 'Dropbox', 'backwpup' )
							), E_USER_ERROR );
					}

					return false;
				}
			}

			$backupfilelist = array();
			$filecounter    = 0;
			$files          = array();
			$filesList      = $dropbox->listFolder( $job_object->job['dropboxdir'] );
				foreach ( $filesList as $data ) {
					if ( $data['.tag'] == 'file' && $job_object->owns_backup_archive( $data['name'] ) == true ) {
						$file = $data['name'];
						if ( $job_object->is_backup_archive( $file ) ) {
							$backupfilelist[ strtotime( $data['server_modified'] ) ] = $file;
						}
						$files[ $filecounter ]['folder']      = dirname( $data['path_display'] );
						$files[ $filecounter ]['file']        = $data['path_display'];
						$files[ $filecounter ]['filename']    = $data['name'];
						$files[ $filecounter ]['downloadurl'] = network_admin_url( 'admin.php?page=backwpupbackups&action=downloaddropbox&file=' . $data['path_display'] . '&jobid=' . $job_object->job['jobid'] );
						$files[ $filecounter ]['filesize']    = $data['size'];
						$files[ $filecounter ]['time']        = strtotime( $data['server_modified'] ) + ( get_option( 'gmt_offset' ) * 3600 );
						$filecounter ++;
					}
				}
			if ( $job_object->job['dropboxmaxbackups'] > 0 && is_object( $dropbox ) ) { //Delete old backups
				if ( count( $backupfilelist ) > $job_object->job['dropboxmaxbackups'] ) {
					ksort( $backupfilelist );
					$numdeltefiles = 0;
					while ( $file = array_shift( $backupfilelist ) ) {
						if ( count( $backupfilelist ) < $job_object->job['dropboxmaxbackups'] ) {
							break;
						}
						$response = $dropbox->filesDelete( array( 'path' => $job_object->job['dropboxdir'] . $file ) ); //delete files on Cloud
							foreach ( $files as $key => $filedata ) {
								if ( $filedata['file'] == '/' . $job_object->job['dropboxdir'] . $file ) {
									unset( $files[ $key ] );
								}
							}
							$numdeltefiles ++;
					}
					if ( $numdeltefiles > 0 ) {
						$job_object->log( sprintf( _n( 'One file deleted from Dropbox', '%d files deleted on Dropbox', $numdeltefiles, 'backwpup' ), $numdeltefiles ), E_USER_NOTICE );
					}
				}
			}
			set_site_transient( 'backwpup_' . $job_object->job['jobid'] . '_dropbox', $files, YEAR_IN_SECONDS );
		}
		catch ( Exception $e ) {
			$job_object->log( sprintf( __( 'Dropbox API: %s', 'backwpup' ), $e->getMessage() ), $e->getFile(), $e->getLine(), E_USER_ERROR );
			return false;
		}
		$job_object->substeps_done ++;

		return true;
	}

	/**
	 * @param $job_settings
	 *
	 * @return bool
	 */
	public function can_run( array $job_settings ) {
		if ( empty( $job_settings['dropboxtoken'] ) ) {
			return false;
		}

		return true;
	}

}

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
			$this->oauth_app_key    = get_site_option( 'backwpup_cfg_dropboxappkey', base64_decode( "dHZkcjk1MnRhZnM1NmZ2" ) );
			$this->oauth_app_secret = BackWPup_Encryption::decrypt( get_site_option( 'backwpup_cfg_dropboxappsecret', base64_decode( "OWV2bDR5MHJvZ2RlYmx1" ) ) );
		}
		else {
			$this->oauth_app_key    = get_site_option( 'backwpup_cfg_dropboxsandboxappkey', base64_decode( "cHVrZmp1a3JoZHR5OTFk" ) );
			$this->oauth_app_secret = BackWPup_Encryption::decrypt( get_site_option( 'backwpup_cfg_dropboxsandboxappsecret', base64_decode( "eGNoYzhxdTk5eHE0eWdq" ) ) );
		}

		if ( empty( $this->oauth_app_key ) || empty( $this->oauth_app_secret ) ) {
			throw new BackWPup_Destination_Dropbox_API_Exception( "No App key or App Secret specified." );
		}

		$this->job_object = $job_object;
	}

	// Helper methods

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
		$files = array();
		$result = $this->filesListFolder( array( 'path' => $path ) );
		if ( ! $result ) {
			return array();
		}
		
		$files = array_merge( $files, $result['entries'] );

		$args = array( 'cursor' => $result['cursor'] );

		while ( $result['has_more'] == true ) {
			$result = $this->filesListFolderContinue( $args );
			$files = array_merge( $files, $result['entries'] );
		}

		return $files;
	}

	/**
	 * Uploads a file to Dropbox.
	 *
	 * @param        $file
	 * @param string $path
	 * @param bool $overwrite
	 *
	 * @return array
	 * @throws BackWPup_Destination_Dropbox_API_Exception
	 */
	public function upload( $file, $path = '', $overwrite = true ) {
		$file = str_replace( "\\", "/", $file );

		if ( ! is_readable( $file ) ) {
			throw new BackWPup_Destination_Dropbox_API_Exception( "Error: File \"$file\" is not readable or doesn't exist." );
		}

		if ( filesize( $file ) < 5242880 ) { //chunk transfer on bigger uploads
			$output = $this->filesUpload( array(
				'contents' => file_get_contents( $file ),
				'path' => $path,
				'mode' => ( $overwrite ) ? 'overwrite' : 'add',
			) );
		}
		else {
			$output = $this->multipartUpload( $file, $path, $overwrite );
		}

		return $output;
	}

	/**
	 * @param        $file
	 * @param string $path
	 * @param bool $overwrite
	 *
	 * @return array|mixed|string
	 * @throws BackWPup_Destination_Dropbox_API_Exception
	 */
	public function multipartUpload( $file, $path = '', $overwrite = true ) {
		$file = str_replace( "\\", "/", $file );

		if ( ! is_readable( $file ) ) {
			throw new BackWPup_Destination_Dropbox_API_Exception( "Error: File \"$file\" is not readable or doesn't exist." );
		}

		$chunk_size = 4194304; //4194304 = 4MB

		$file_handel = fopen( $file, 'rb' );
		if ( ! $file_handel ) {
			throw new BackWPup_Destination_Dropbox_API_Exception( "Can not open source file for transfer." );
		}

		if ( ! isset( $this->job_object->steps_data[ $this->job_object->step_working ]['uploadid'] ) ) {
			$this->job_object->log( __( 'Beginning new file upload session', 'backwpup' ) );
			$session = $this->filesUploadSessionStart();
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
				$this->job_object->log( sprintf( __( 'Uploading %s of data', 'backwpup' ), size_format( strlen( $data ) ) ) );
			}

			$this->filesUploadSessionAppendV2( array(
				'contents' => $data,
				'cursor' => array(
					'session_id' => $this->job_object->steps_data[ $this->job_object->step_working ]['uploadid'],
					'offset'    => $this->job_object->steps_data[ $this->job_object->step_working ]['offset']
				),
			) );
			$chunk_upload_time  = microtime( true ) - $chunk_upload_start;
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

		$this->job_object->log( sprintf( __( 'Finishing upload session with a total of %s uploaded', 'backwpup' ), size_format( $this->job_object->steps_data[ $this->job_object->step_working ]['totalread'] ) ) );
		$response = $this->filesUploadSessionFinish( array(
			'cursor' => array(
				'session_id' => $this->job_object->steps_data[ $this->job_object->step_working ]['uploadid'],
				'offset' => $this->job_object->steps_data[ $this->job_object->step_working ]['totalread'],
			),
			'commit' => array(
				'path' => $path,
				'mode' => ( $overwrite ) ? 'overwrite' : 'add',
			),
		) );

		unset( $this->job_object->steps_data[ $this->job_object->step_working ]['uploadid'] );
		unset( $this->job_object->steps_data[ $this->job_object->step_working ]['offset'] );

		return $response;
	}

	// Authentication

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
		return $this->request( 'oauth2/token', array(
			'code'          => trim( $code ),
			'grant_type'    => 'authorization_code',
			'client_id'     => $this->oauth_app_key,
			'client_secret' => $this->oauth_app_secret
		), 'oauth' );
	}

	// Auth Endpoints

	/**
	 * Revokes the auth token.
	 *
	 * @return array
	 */
	public function authTokenRevoke() {
		return $this->request( 'auth/token/revoke' );
	}

	// Files Endpoints

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
		}
		catch ( BackWPup_Destination_Dropbox_API_Request_Exception $e ) {
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
		}
		catch ( BackWPup_Destination_Dropbox_API_Request_Exception $e ) {
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
		}
			catch ( BackWPup_Destination_Dropbox_API_Request_Exception $e ) {
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
		}
		catch ( BackWPup_Destination_Dropbox_API_Request_Exception $e ) {
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
		}
		catch ( BackWPup_Destination_Dropbox_API_Request_Exception $e ) {
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
	 * @return array	The uploaded file's information.
	 */
	public function filesUpload( $args ) {
		$args['path'] = $this->formatPath( $args['path'] );

		if ( isset( $args['client_modified'] )
				&& $args['client_modified'] instanceof DateTime ) {
			$args['client_modified'] = $args['client_modified']->format( 'Y-m-d\TH:m:s\Z' );
		}

		try {
			return $this->request( 'files/upload', $args, 'upload' );
		}
		catch ( BackWPup_Destination_Dropbox_API_Request_Exception $e ) {
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
			return $this->request( 'files/upload_session/append_v2', $args,
					'upload' );
		}
		catch ( BackWPup_Destination_Dropbox_API_Request_Exception $e ) {
			$error = $e->getError();

			// See if we can fix the error first
			if ( $error['.tag'] == 'incorrect_offset' ) {
				$args['cursor']['offset'] = $error['correct_offset'];
			return $this->request( 'files/upload_session/append_v2', $args,
					'upload' );
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
		}
		catch ( BackWPup_Destination_Dropbox_API_Request_Exception $e ) {
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
	 * @return array	An array containing the session's ID.
	 */
	public function filesUploadSessionStart( $args = array() ) {
		return $this->request( 'files/upload_session/start', $args, 'upload' );
}

	// Users endpoints

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

	// Private functions

	/**
	 * @param        $url
	 * @param array $args
	 * @param string $endpointFormat
	 * @param string $data
	 * @param bool $echo
	 *
	 * @throws BackWPup_Destination_Dropbox_API_Exception
	 * @return array|mixed|string
	 */
	private function request( $endpoint, $args = array(), $endpointFormat = 'rpc', $echo = false ) {

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
			$message = 'Call to ' . $endpoint;
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

		if ( $endpointFormat != 'oauth' ) {
				$headers[] = 'Authorization: Bearer ' . $this->oauth_token['access_token'];
			}

		if ( $endpointFormat == 'oauth' ) {
			curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $args, null, '&' ) );
			$headers[] = 'Content-Type: application/x-www-form-urlencoded';
		}
		elseif ( $endpointFormat == 'rpc' ) {
			if ( ! empty( $args ) ) {
			curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $args ) );
			}
			else {
			curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( null ) );
			}
			$headers[] = 'Content-Type: application/json';
		}
		elseif ( $endpointFormat == 'upload' ) {
			if ( isset( $args['contents'] ) ) {
				curl_setopt( $ch, CURLOPT_POSTFIELDS, $args['contents'] );
				unset( $args['contents'] );
			}
			else {
				curl_setopt( $ch, CURLOPT_POSTFIELDS, '' );
			}
			$headers[] = 'Content-Type: application/octet-stream';
			if ( ! empty( $args ) ) {
				$headers[] = 'Dropbox-API-Arg: ' . json_encode( $args );
			}
			else {
				$headers[] = 'Dropbox-API-Arg: {}';
			}
		}
		else {
			curl_setopt( $ch, CURLOPT_BINARYTRANSFER, true );
			$headers[] = 'Dropbox-API-Arg: ' . json_encode( $args );
		}

		curl_setopt( $ch, CURLOPT_USERAGENT, BackWPup::get_plugin_data( 'User-Agent' ) );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		if ( BackWPup::get_plugin_data( 'cacert' ) ) {
			curl_setopt( $ch, CURLOPT_SSLVERSION, 1 );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true );
			$curl_version = curl_version();
			if ( strstr( $curl_version['ssl_version'], 'NSS/' ) === false ) {
				curl_setopt( $ch, CURLOPT_SSL_CIPHER_LIST,
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
		}
		else {
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		}
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
		$output = '';
		if ( $echo ) {
			echo curl_exec( $ch );
		}
		else {
			curl_setopt( $ch, CURLOPT_HEADER, true );
			$responce = explode( "\r\n\r\n", curl_exec( $ch ), 2 );
			if ( ! empty( $responce[1] ) ) {
				$output = json_decode( $responce[1], true );
			}
		}
		$status = curl_getinfo( $ch );

		// Handle error codes
		// If 409 (endpoint-specific error), let the calling method handle it

		// Code 429 = rate limited
		if ( $status['http_code'] == 429 ) {
			$wait = 0;
			if ( preg_match( "/retry-after:\s*(.*?)\r/i", $responce[0], $matches ) ) {
				$wait = trim( $matches[1] );
			}
			//only wait if we get a retry-after header.
			if ( ! empty( $wait ) ) {
				trigger_error( sprintf( '(429) Your app is making too many requests and is being rate limited. Error 429 can be triggered on a per-app or per-user basis. Wait for %d seconds.', $wait ), E_USER_WARNING );
				sleep( $wait );
			}
			else {
				throw new BackWPup_Destination_Dropbox_API_Exception( '(429) This indicates a transient server error.' );
			}

			//redo request
			return $this->request( $url, $args, $endpointFormat, $data, $echo );
		}
		// We can't really handle anything else, so throw it back to the caller
		elseif ( isset( $output['error'] ) || $status['http_code'] >= 400 || curl_errno( $ch ) > 0 ) {
		$code = $status['http_code'];
			if ( curl_errno( $ch ) != 0 ) {
				$message = '(' . curl_errno( $ch ) . ') ' . curl_error( $ch );
			$code = 0;
			}
			elseif ( $status['http_code'] == 400 ) {
				$message = '(400) Bad input parameter: ' . strip_tags( $responce[1] );
			}
			elseif ( $status['http_code'] == 401 ) {
				$message = '(401) Bad or expired token. This can happen if the user or Dropbox revoked or expired an access token. To fix, you should re-authenticate the user.';
			}
			elseif ( $status['http_code'] == 409 ) {
			$message = $output['error_summary'];
			}
			elseif ( $status['http_code'] >= 500 ) {
				$message = '(' . $status['http_code'] . ') There is an error on the Dropbox server.';
			}
			else {
				$message = '(' . $status['http_code'] . ') Invalid response.';
			}
			if ( $this->job_object && $this->job_object->is_debug() ) {
				$this->job_object->log( 'Response with header: ' . $responce[0] );
			}
			throw new BackWPup_Destination_Dropbox_API_Request_Exception( $message, $code, null, isset( $output['error'] ) ? $output['error'] : null );
		}
		else {
			curl_close( $ch );
			if ( ! is_array( $output ) ) {
				return $responce[1];
			}
			else {
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
			$path = "/$path";
		}
		elseif ( $path == '/' ) {
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
						$error['lookup_failed'] );
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
				trigger_error( 'Incorrect offset given. Correct offset is ' .
						$error['correct_offset'] . '.',
						E_USER_WARNING );
				break;

			case 'closed':
				trigger_error( 'This session has been closed already.',
						E_USER_WARNING );
				break;

				case 'not_closed':
				trigger_error( 'This session is not closed.', E_USER_WARNING );
				break;

				case 'other':
				trigger_error( 'Could not look up the file session.',
						E_USER_WARNING );
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

/**
 *
 */
class BackWPup_Destination_Dropbox_API_Exception extends Exception {

}

/**
 * Exception thrown when there is an error in the Dropbox request.
 */
class BackWPup_Destination_Dropbox_API_Request_Exception extends BackWPup_Destination_Dropbox_API_Exception {

	/**
	 * The request error array.
	 */
	protected $error;

	public function __construct( $message, $code = 0, $previous = null, $error = null ) {
		$this->error = $error;
		parent::__construct( $message, $code, $previous );
	}

	public function getError() {
		return $this->error;
	}

}
