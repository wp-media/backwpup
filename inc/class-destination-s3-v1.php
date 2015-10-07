<?php
// Amazon S3 SDK v1.6.2
// http://aws.amazon.com/de/sdkforphp/
// https://github.com/amazonwebservices/aws-sdk-for-php
if ( ! defined( 'E_USER_DEPRECATED') )
	define( 'E_USER_DEPRECATED', 16384 );

/**
 * Documentation: http://docs.amazonwebservices.com/aws-sdk-php-2/latest/class-Aws.S3.S3Client.html
 */
class BackWPup_Destination_S3_V1 extends BackWPup_Destinations {

	/**
	 * @param        $s3region
	 * @param string $s3base_url
	 * @return string
	 */
	protected function get_s3_base_url( $s3region, $s3base_url = '' ) {

		if ( ! empty( $s3base_url ) )
			return $s3base_url;

		switch ( $s3region ) {
			case 'us-east-1':
				return 'https://s3.amazonaws.com';
			case 'us-west-1':
				return 'https://s3-us-west-1.amazonaws.com';
			case 'us-west-2':
				return 'https://s3-us-west-2.amazonaws.com';
			case 'eu-west-1':
				return 'https://s3-eu-west-1.amazonaws.com';
			case 'eu-central-1':
				return 'https://s3-eu-central-1.amazonaws.com';
			case 'ap-northeast-1':
				return 'https://s3-ap-northeast-1.amazonaws.com';
			case 'ap-southeast-1':
				return 'https://s3-ap-southeast-1.amazonaws.com';
			case 'ap-southeast-2':
				return 'https://s3-ap-southeast-2.amazonaws.com';
			case 'sa-east-1':
				return 'https://s3-sa-east-1.amazonaws.com';
			case 'cn-north-1':
				return 'https://cn-north-1.amazonaws.com';
			case 'google-storage':
				return 'https://storage.googleapis.com';
			case 'dreamhost':
				return 'https://objects.dreamhost.com';
			case 'greenqloud':
				return 'http://s.greenqloud.com';
			default:
				return '';
		}

	}

	/**
	 * @return array
	 */
	public function option_defaults() {

		return array( 's3accesskey' => '', 's3secretkey' => '', 's3bucket' => '', 's3region' => 'us-east-1', 's3base_url' => '', 's3ssencrypt' => '', 's3storageclass' => '', 's3dir' => trailingslashit( sanitize_file_name( get_bloginfo( 'name' ) ) ), 's3maxbackups' => 15, 's3syncnodelete' => TRUE );
	}


	/**
	 * @param $jobid
	 */
	public function edit_tab( $jobid ) {

		?>
		<h3 class="title"><?php _e( 'S3 Service', 'backwpup' ) ?></h3>
		<p></p>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="s3region"><?php _e( 'Select a S3 service', 'backwpup' ) ?></label></th>
				<td>
					<select name="s3region" id="s3region" title="<?php _e( 'Amazon S3 Region', 'backwpup' ); ?>">
						<option value="us-east-1" <?php selected( 'us-east-1', BackWPup_Option::get( $jobid, 's3region' ), TRUE ) ?>><?php _e( 'Amazon S3: US Standard', 'backwpup' ); ?></option>
						<option value="us-west-1" <?php selected( 'us-west-1', BackWPup_Option::get( $jobid, 's3region' ), TRUE ) ?>><?php _e( 'Amazon S3: US West (Northern California)', 'backwpup' ); ?></option>
						<option value="us-west-2" <?php selected( 'us-west-2', BackWPup_Option::get( $jobid, 's3region' ), TRUE ) ?>><?php _e( 'Amazon S3: US West (Oregon)', 'backwpup' ); ?></option>
						<option value="eu-west-1" <?php selected( 'eu-west-1', BackWPup_Option::get( $jobid, 's3region' ), TRUE ) ?>><?php _e( 'Amazon S3: EU (Ireland)', 'backwpup' ); ?></option>
						<option value="eu-central-1" <?php selected( 'eu-central-1', BackWPup_Option::get( $jobid, 's3region' ), TRUE ) ?>><?php _e( 'Amazon S3: EU (Germany)', 'backwpup' ); ?></option>
						<option value="ap-northeast-1" <?php selected( 'ap-northeast-1', BackWPup_Option::get( $jobid, 's3region' ), TRUE ) ?>><?php _e( 'Amazon S3: Asia Pacific (Tokyo)', 'backwpup' ); ?></option>
						<option value="ap-southeast-1" <?php selected( 'ap-southeast-1', BackWPup_Option::get( $jobid, 's3region' ), TRUE ) ?>><?php _e( 'Amazon S3: Asia Pacific (Singapore)', 'backwpup' ); ?></option>
						<option value="ap-southeast-2" <?php selected( 'ap-southeast-2', BackWPup_Option::get( $jobid, 's3region' ), TRUE ) ?>><?php _e( 'Amazon S3: Asia Pacific (Sydney)', 'backwpup' ); ?></option>
						<option value="sa-east-1" <?php selected( 'sa-east-1', BackWPup_Option::get( $jobid, 's3region' ), TRUE ) ?>><?php _e( 'Amazon S3: South America (Sao Paulo)', 'backwpup' ); ?></option>
						<option value="cn-north-1" <?php selected( 'cn-north-1', BackWPup_Option::get( $jobid, 's3region' ), TRUE ) ?>><?php _e( 'Amazon S3: China (Beijing)', 'backwpup' ); ?></option>
						<option value="google-storage" <?php selected( 'google-storage', BackWPup_Option::get( $jobid, 's3region' ), TRUE ) ?>><?php _e( 'Google Storage (Interoperable Access)', 'backwpup' ); ?></option>
                        <option value="dreamhost" <?php selected( 'dreamhost', BackWPup_Option::get( $jobid, 's3region' ), TRUE ) ?>><?php _e( 'Dream Host Cloud Storage', 'backwpup' ); ?></option>
						<option value="greenqloud" <?php selected( 'greenqloud', BackWPup_Option::get( $jobid, 's3region' ), TRUE ) ?>><?php _e( 'GreenQloud Storage Qloud', 'backwpup' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="s3base_url"><?php _e( 'Or a S3 Server URL', 'backwpup' ) ?></label></th>
				<td>
					<input id="s3base_url" name="s3base_url" type="text"  value="<?php echo esc_attr( BackWPup_Option::get( $jobid, 's3base_url' ) );?>" class="regular-text" autocomplete="off" />
				</td>
			</tr>
		</table>

		<h3 class="title"><?php _e( 'S3 Access Keys', 'backwpup' ); ?></h3>
		<p></p>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="s3accesskey"><?php _e( 'Access Key', 'backwpup' ); ?></label></th>
				<td>
					<input id="s3accesskey" name="s3accesskey" type="text"
						   value="<?php echo esc_attr( BackWPup_Option::get( $jobid, 's3accesskey' ) );?>" class="regular-text" autocomplete="off" />
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="s3secretkey"><?php _e( 'Secret Key', 'backwpup' ); ?></label></th>
				<td>
					<input id="s3secretkey" name="s3secretkey" type="password"
						   value="<?php echo esc_attr( BackWPup_Encryption::decrypt( BackWPup_Option::get( $jobid, 's3secretkey' ) ) ); ?>" class="regular-text" autocomplete="off" />
				</td>
			</tr>
		</table>

		<h3 class="title"><?php _e( 'S3 Bucket', 'backwpup' ); ?></h3>
		<p></p>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="s3bucketselected"><?php _e( 'Bucket selection', 'backwpup' ); ?></label></th>
				<td>
					<input id="s3bucketselected" name="s3bucketselected" type="hidden" value="<?php echo esc_attr( BackWPup_Option::get( $jobid, 's3bucket' ) ); ?>" />
					<?php if ( BackWPup_Option::get( $jobid, 's3accesskey' ) && BackWPup_Option::get( $jobid, 's3secretkey' ) ) $this->edit_ajax( array(
																																					   's3accesskey'  => BackWPup_Option::get( $jobid, 's3accesskey' ),
																																					   's3secretkey'  => BackWPup_Encryption::decrypt(BackWPup_Option::get( $jobid, 's3secretkey' ) ),
																																					   's3bucketselected'   => BackWPup_Option::get( $jobid, 's3bucket' ),
																																					   's3base_url' 	=> BackWPup_Option::get( $jobid, 's3base_url' ),
																																					   's3region' 	=> BackWPup_Option::get( $jobid, 's3region' )
																																				  ) ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="s3newbucket"><?php _e( 'Create a new bucket', 'backwpup' ); ?></label></th>
				<td>
					<input id="s3newbucket" name="s3newbucket" type="text" value="" class="small-text" autocomplete="off" />
				</td>
			</tr>
		</table>

		<h3 class="title"><?php _e( 'S3 Backup settings', 'backwpup' ); ?></h3>
		<p></p>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="ids3dir"><?php _e( 'Folder in bucket', 'backwpup' ); ?></label></th>
				<td>
					<input id="ids3dir" name="s3dir" type="text" value="<?php echo esc_attr( BackWPup_Option::get( $jobid, 's3dir' ) ); ?>" class="regular-text" />
				</td>
			</tr>
			<tr>
				<th scope="row"><?php _e( 'File deletion', 'backwpup' ); ?></th>
				<td>
					<?php
					if ( BackWPup_Option::get( $jobid, 'backuptype' ) == 'archive' ) {
						?>
						<label for="ids3maxbackups"><input id="ids3maxbackups" name="s3maxbackups" type="text" size="3" value="<?php echo esc_attr( BackWPup_Option::get( $jobid, 's3maxbackups' ) ); ?>" class="small-text help-tip" title="<?php esc_attr_e( 'Oldest files will be deleted first. 0 = no deletion', 'backwpup' ); ?>" />&nbsp;
						<?php  _e( 'Number of files to keep in folder.', 'backwpup' ); ?></label>
						<?php } else { ?>
                        <label for="ids3syncnodelete"><input class="checkbox" value="1"
							   type="checkbox" <?php checked( BackWPup_Option::get( $jobid, 's3syncnodelete' ), TRUE ); ?>
							   name="s3syncnodelete" id="ids3syncnodelete" /> <?php _e( 'Do not delete files while syncing to destination!', 'backwpup' ); ?></label>
						<?php } ?>
				</td>
			</tr>
		</table>

		<h3 class="title"><?php _e( 'Amazon specific settings', 'backwpup' ); ?></h3>
		<p></p>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="ids3storageclass"><?php _e( 'Amazon: Storage Class', 'backwpup' ); ?></label></th>
				<td>
					<select name="s3storageclass" id="ids3storageclass" title="<?php _e( 'Amazon: Storage Class', 'backwpup' ); ?>">
						<option value="" <?php selected( 'us-east-1', BackWPup_Option::get( $jobid, 's3storageclass' ), TRUE ) ?>><?php _e( 'Standard', 'backwpup' ); ?></option>
						<option value="STANDARD_IA" <?php selected( 'STANDARD_IA', BackWPup_Option::get( $jobid, 's3storageclass' ), TRUE ) ?>><?php _e( 'Standard-Infrequent Access', 'backwpup' ); ?></option>
						<option value="REDUCED_REDUNDANCY" <?php selected( 'REDUCED_REDUNDANCY', BackWPup_Option::get( $jobid, 's3storageclass' ), TRUE ) ?>><?php _e( 'Reduced Redundancy', 'backwpup' ); ?></option>
				</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="ids3ssencrypt"><?php _e( 'Server side encryption', 'backwpup' ); ?></label></th>
				<td>
					<input class="checkbox" value="AES256"
						   type="checkbox" <?php checked( BackWPup_Option::get( $jobid, 's3ssencrypt' ), 'AES256' ); ?>
						   name="s3ssencrypt" id="ids3ssencrypt" /> <?php _e( 'Save files encrypted (AES256) on server.', 'backwpup' ); ?>
				</td>
			</tr>
		</table>

		<?php
	}


	/**
	 * @param $jobid
	 * @return string
	 */
	public function edit_form_post_save( $jobid ) {

		BackWPup_Option::update( $jobid, 's3accesskey', isset( $_POST[ 's3accesskey' ] ) ? $_POST[ 's3accesskey' ] : '' );
		BackWPup_Option::update( $jobid, 's3secretkey', isset( $_POST[ 's3secretkey' ] ) ? BackWPup_Encryption::encrypt( $_POST[ 's3secretkey' ] ) : '' );
		BackWPup_Option::update( $jobid, 's3base_url', isset( $_POST[ 's3base_url' ] ) ? esc_url_raw( $_POST[ 's3base_url' ] ) : '' );
		BackWPup_Option::update( $jobid, 's3region', isset( $_POST[ 's3region' ] ) ? $_POST[ 's3region' ] : '' );
		BackWPup_Option::update( $jobid, 's3storageclass', isset( $_POST[ 's3storageclass' ] ) ? $_POST[ 's3storageclass' ] : '' );
		BackWPup_Option::update( $jobid, 's3ssencrypt', ( isset( $_POST[ 's3ssencrypt' ] ) && $_POST[ 's3ssencrypt' ] == 'AES256' ) ? 'AES256' : '' );
		BackWPup_Option::update( $jobid, 's3bucket', isset( $_POST[ 's3bucket' ] ) ? $_POST[ 's3bucket' ] : '' );

		$_POST[ 's3dir' ] = trailingslashit( str_replace( '//', '/', str_replace( '\\', '/', trim( stripslashes( $_POST[ 's3dir' ] ) ) ) ) );
		if ( substr( $_POST[ 's3dir' ], 0, 1 ) == '/' )
			$_POST[ 's3dir' ] = substr( $_POST[ 's3dir' ], 1 );
		if ( $_POST[ 's3dir' ] == '/' )
			$_POST[ 's3dir' ] = '';
		BackWPup_Option::update( $jobid, 's3dir', $_POST[ 's3dir' ] );

		BackWPup_Option::update( $jobid, 's3maxbackups', isset( $_POST[ 's3maxbackups' ] ) ? (int)$_POST[ 's3maxbackups' ] : 0 );
		BackWPup_Option::update( $jobid, 's3syncnodelete', ( isset( $_POST[ 's3syncnodelete' ] ) && $_POST[ 's3syncnodelete' ] == 1 ) ? TRUE : FALSE );
		BackWPup_Option::update( $jobid, 's3multipart', ( isset( $_POST[ 's3multipart' ] ) && $_POST[ 's3multipart' ] == 1 ) ? TRUE : FALSE );

		//create new bucket
		if ( !empty( $_POST[ 's3newbucket' ] ) ) {
			try {
				$s3 = new AmazonS3( array( 	'key' => $_POST[ 's3accesskey' ],
											'secret' => BackWPup_Encryption::decrypt( $_POST[ 's3secretkey' ] ),
											'certificate_authority'	=> TRUE ) );
				$base_url = $this->get_s3_base_url( $_POST[ 's3region' ], $_POST[ 's3base_url' ] );
				if ( stristr( $base_url, 'amazonaws.com' ) ) {
					$s3->set_region( str_replace( array( 'http://', 'https://' ), '', $base_url ) );
				} else {
					$s3->set_hostname( str_replace( array( 'http://', 'https://' ), '', $base_url ) );
					$s3->allow_hostname_override( FALSE );
					if ( substr( $base_url, -1 ) == '/')
						$s3->enable_path_style( TRUE );
				}
				if ( stristr( $base_url, 'http://' ) )
					$s3->disable_ssl();

				// set bucket creation region
				if ( $_POST[ 's3region' ] == 'google-storage' || $_POST[ 's3region' ] == 'hosteurope' )
					$region = 'EU';
				else
					$region = str_replace( array( 'http://', 'https://' ), '', $base_url );

				$bucket = $s3->create_bucket(  $_POST[ 's3newbucket' ], $region, 'private' );

				if ( $bucket->status == 200 )
					BackWPup_Admin::message( sprintf( __( 'Bucket %1$s created.','backwpup'), $_POST[ 's3newbucket' ] ) );
				else
					BackWPup_Admin::message( sprintf( __( 'Bucket %s could not be created.','backwpup'), $_POST[ 's3newbucket' ] ), TRUE );

			}
			catch ( Exception $e ) {
				BackWPup_Admin::message( $e->getMessage(), TRUE );
			}
			BackWPup_Option::update( $jobid, 's3bucket', $_POST[ 's3newbucket' ] );
		}
	}

	/**
	 * @param $jobdest
	 * @param $backupfile
	 */
	public function file_delete( $jobdest, $backupfile ) {

		$files = get_site_transient( 'backwpup_'. strtolower( $jobdest ) );
		list( $jobid, $dest ) = explode( '_', $jobdest );

		if ( BackWPup_Option::get( $jobid, 's3accesskey' ) && BackWPup_Option::get( $jobid, 's3secretkey' ) && BackWPup_Option::get( $jobid, 's3bucket' ) ) {
			try {
				$s3 = new AmazonS3( array( 	'key' => BackWPup_Option::get( $jobid, 's3accesskey' ),
											'secret' => BackWPup_Encryption::decrypt( BackWPup_Option::get( $jobid, 's3secretkey' ) ),
											'certificate_authority'	=> TRUE ) );
				$base_url = $this->get_s3_base_url( BackWPup_Option::get( $jobid, 's3region' ), BackWPup_Option::get( $jobid, 's3base_url' ) );
				if ( stristr( $base_url, 'amazonaws.com' ) ) {
					$s3->set_region( str_replace( array( 'http://', 'https://' ), '', $base_url ) );
				} else {
					$s3->set_hostname( str_replace( array( 'http://', 'https://' ), '', $base_url ) );
					$s3->allow_hostname_override( FALSE );
					if ( substr( $base_url, -1 ) == '/')
						$s3->enable_path_style( TRUE );
				}
				if ( stristr( $base_url, 'http://' ) )
					$s3->disable_ssl();

				$s3->delete_object( BackWPup_Option::get( $jobid, 's3bucket' ), $backupfile );
				//update file list
				foreach ( (array) $files as $key => $file ) {
					if ( is_array( $file ) && $file[ 'file' ] == $backupfile ) {
						unset( $files[ $key ] );
					}
				}
				unset( $s3 );
			}
			catch ( Exception $e ) {
				BackWPup_Admin::message( sprintf( __('S3 Service API: %s','backwpup'), $e->getMessage() ), TRUE );
			}
		}

		set_site_transient( 'backwpup_'. strtolower( $jobdest ), $files, 60 * 60 * 24 * 7 );
	}

	/**
	 * @param $jobid
	 * @param $get_file
	 */
	public function file_download( $jobid, $get_file ) {

		try {
			$s3 = new AmazonS3( array( 	'key' => BackWPup_Option::get( $jobid, 's3accesskey' ),
										'secret' => BackWPup_Encryption::decrypt( BackWPup_Option::get( $jobid, 's3secretkey' ) ),
										'certificate_authority'	=> TRUE ) );
			$base_url = $this->get_s3_base_url( BackWPup_Option::get( $jobid, 's3region' ), BackWPup_Option::get( $jobid, 's3base_url' ) );
			if ( stristr( $base_url, 'amazonaws.com' ) ) {
				$s3->set_region( str_replace( array( 'http://', 'https://' ), '', $base_url ) );
			} else {
				$s3->set_hostname( str_replace( array( 'http://', 'https://' ), '', $base_url ) );
				$s3->allow_hostname_override( FALSE );
				if ( substr( $base_url, -1 ) == '/')
					$s3->enable_path_style( TRUE );
			}
			if ( stristr( $base_url, 'http://' ) )
				$s3->disable_ssl();

			$s3file = $s3->get_object( BackWPup_Option::get( $jobid, 's3bucket' ), $get_file );
		}
		catch ( Exception $e ) {
			die( $e->getMessage() );
		}

		if ( $s3file->status==200 ) {
			header( "Pragma: public" );
			header( "Expires: 0" );
			header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
			header( "Content-Type: application/octet-stream" );
			header( "Content-Disposition: attachment; filename=" . basename( $get_file ) . ";" );
			header( "Content-Transfer-Encoding: binary" );
			header( "Content-Length: " . $s3file->header->_info->size_download );
			@set_time_limit( 300 );
			echo $s3file->body;
			die();
		}
	}

	/**
	 * @param $jobdest
	 * @return mixed
	 */
	public function file_get_list( $jobdest ) {

		return get_site_transient( 'backwpup_' . strtolower( $jobdest ) );
	}

	/**
	 * @param $job_object BackWPup_Job
	 * @return bool
	 */
	public function job_run_archive( BackWPup_Job $job_object ) {

		$job_object->substeps_todo = 2 + $job_object->backup_filesize;
		$job_object->log( sprintf( __( '%d. Trying to send backup file to S3 Service&#160;&hellip;', 'backwpup' ), $job_object->steps_data[ $job_object->step_working ][ 'STEP_TRY' ] ), E_USER_NOTICE );

		try {

			$s3 = new AmazonS3( array( 	'key' => $job_object->job[ 's3accesskey' ],
										'secret' => BackWPup_Encryption::decrypt( $job_object->job[ 's3secretkey' ] ),
										'certificate_authority'	=> TRUE ) );
			$base_url = $this->get_s3_base_url( $job_object->job[ 's3region' ], $job_object->job[ 's3base_url' ] );
			if ( stristr( $base_url, 'amazonaws.com' ) ) {
				$s3->set_region( str_replace( array( 'http://', 'https://' ), '', $base_url ) );
			} else {
				$s3->set_hostname( str_replace( array( 'http://', 'https://' ), '', $base_url ) );
				$s3->allow_hostname_override( FALSE );
				if ( substr( $base_url, -1 ) == '/')
					$s3->enable_path_style( TRUE );
			}
			if ( stristr( $base_url, 'http://' ) )
				$s3->disable_ssl();


			if ( $s3->if_bucket_exists( $job_object->job[ 's3bucket' ] ) ) {
				$job_object->log( sprintf( __( 'Connected to S3 Bucket "%1$s" in %2$s', 'backwpup' ), $job_object->job[ 's3bucket' ], $base_url ), E_USER_NOTICE );
			}
			else {
				$job_object->log( sprintf( __( 'S3 Bucket "%s" does not exist!', 'backwpup' ), $job_object->job[ 's3bucket' ] ), E_USER_ERROR );

				return TRUE;
			}

			//transfer file to S3
			$job_object->log( __( 'Starting upload to S3 Service&#160;&hellip;', 'backwpup' ), E_USER_NOTICE );

			//Transfer Backup to S3
		    if ( $job_object->job[ 's3storageclass' ] == 'REDUCED_REDUNDANCY' ) //set reduced redundancy or not
				$storage=AmazonS3::STORAGE_REDUCED;
		    else
				$storage=AmazonS3::STORAGE_STANDARD;

			if ( empty( $job_object->job[ 's3ssencrypt' ] ) )
				$job_object->job[ 's3ssencrypt' ] = NULL;

		    //set progress bar
			$s3->register_streaming_read_callback( array( $job_object, 'curl_read_callback' ) );

			$result = $s3->create_object( $job_object->job[ 's3bucket' ], $job_object->job[ 's3dir' ] . $job_object->backup_file, array( 'fileUpload' => $job_object->backup_folder . $job_object->backup_file, 'acl' => AmazonS3::ACL_PRIVATE, 'storage' => $storage, 'encryption' => $job_object->job[ 's3ssencrypt' ] ) );

			if ( $result->status >= 200 and $result->status < 300 ) {
				$job_object->substeps_done = 1 + $job_object->backup_filesize;
				$job_object->log( sprintf( __( 'Backup transferred to %s.', 'backwpup' ), $this->get_s3_base_url( $job_object->job[ 's3region' ], $job_object->job[ 's3base_url' ] ). '/' .$job_object->job[ 's3bucket' ] . '/' . $job_object->job[ 's3dir' ] . $job_object->backup_file ), E_USER_NOTICE );
				if ( ! empty( $job_object->job[ 'jobid' ] ) )
					BackWPup_Option::update( $job_object->job[ 'jobid' ], 'lastbackupdownloadurl', network_admin_url( 'admin.php' ) . '?page=backwpupbackups&action=downloads3&file=' . $job_object->job[ 's3dir' ] . $job_object->backup_file . '&jobid=' . $job_object->job[ 'jobid' ] );
			}
			else {
				$job_object->log( sprintf( __( 'Cannot transfer backup to S3! (%1$d) %2$s', 'backwpup' ), $result->status, $result->body ), E_USER_ERROR );
			}
		}
		catch ( Exception $e ) {
			$job_object->log( E_USER_ERROR, sprintf( __( 'S3 Service API: %s', 'backwpup' ), $e->getMessage() ), $e->getFile(), $e->getLine() );

			return FALSE;
		}

		try {
			$backupfilelist = array();
			$filecounter    = 0;
			$files          = array();
			$objects = $s3->list_objects( $job_object->job[ 's3bucket' ], array( 'prefix' => $job_object->job[ 's3dir' ] ) );
			if ( is_object( $objects ) ) {
				foreach ( $objects->body->Contents as $object ) {
					$file       = basename( (string) $object->Key );
					$changetime = strtotime( (string) $object->LastModified ) + ( get_option( 'gmt_offset' ) * 3600 );
					if ( $job_object->is_backup_archive( $file ) )
						$backupfilelist[ $changetime ] = $file;
					$files[ $filecounter ][ 'folder' ]      = $this->get_s3_base_url( $job_object->job[ 's3region' ], $job_object->job[ 's3base_url' ] ). '/' .$job_object->job[ 's3bucket' ] . '/' . dirname( (string) $object->Key );
					$files[ $filecounter ][ 'file' ]        = (string) $object->Key;
					$files[ $filecounter ][ 'filename' ]    = basename( $object->Key );
					$files[ $filecounter ][ 'downloadurl' ] = network_admin_url( 'admin.php' ) . '?page=backwpupbackups&action=downloads3&file=' . (string) $object->Key . '&jobid=' . $job_object->job[ 'jobid' ];
					$files[ $filecounter ][ 'filesize' ]    = (int) $object->Size;
					$files[ $filecounter ][ 'time' ]        = $changetime;
					$filecounter ++;
				}
			}
			if ( $job_object->job[ 's3maxbackups' ] > 0 && is_object( $s3 ) ) { //Delete old backups
				if ( count( $backupfilelist ) > $job_object->job[ 's3maxbackups' ] ) {
					ksort( $backupfilelist );
					$numdeltefiles = 0;
					while ( $file = array_shift( $backupfilelist ) ) {
						if ( count( $backupfilelist ) < $job_object->job[ 's3maxbackups' ] )
							break;
						//delete files on S3
						$delete_s3 = $s3->delete_object( $job_object->job[ 's3bucket' ], $job_object->job[ 's3dir' ] . $file );
						if ($delete_s3 ) {
							foreach ( $files as $key => $filedata ) {
								if ( $filedata[ 'file' ] == $job_object->job[ 's3dir' ] . $file )
									unset( $files[ $key ] );
							}
							$numdeltefiles ++;
						} else {
							$job_object->log( sprintf( __( 'Cannot delete backup from %s.', 'backwpup' ), $this->get_s3_base_url( $job_object->job[ 's3region' ], $job_object->job[ 's3base_url' ] ). '/' .$job_object->job[ 's3bucket' ] . '/' . $job_object->job[ 's3dir' ] . $file ), E_USER_ERROR );
						}
					}
					if ( $numdeltefiles > 0 )
						$job_object->log( sprintf( _n( 'One file deleted on S3 Bucket.', '%d files deleted on S3 Bucket', $numdeltefiles, 'backwpup' ), $numdeltefiles ), E_USER_NOTICE );
				}
			}
			set_site_transient( 'backwpup_' . $job_object->job[ 'jobid' ] . '_s3', $files, 60 * 60 * 24 * 7 );
		}
		catch ( Exception $e ) {
			$job_object->log( E_USER_ERROR, sprintf( __( 'S3 Service API: %s', 'backwpup' ), $e->getMessage() ), $e->getFile(), $e->getLine() );

			return FALSE;
		}
		$job_object->substeps_done = 2 + $job_object->backup_filesize;

		return TRUE;
	}


	/**
	 * @param $job_settings array
	 * @return bool
	 */
	public function can_run( array $job_settings ) {

		if ( empty( $job_settings[ 's3accesskey' ] ) )
			return FALSE;

		if ( empty( $job_settings[ 's3secretkey' ] ) )
			return FALSE;

		if ( empty( $job_settings[ 's3bucket' ] ) )
			return FALSE;

		return TRUE;
	}

	/**
	 *
	 */
	public function edit_inline_js() {
		//<script type="text/javascript">
		?>
		function awsgetbucket() {
            var data = {
                action: 'backwpup_dest_s3',
                s3accesskey: $('input[name="s3accesskey"]').val(),
                s3secretkey: $('input[name="s3secretkey"]').val(),
                s3bucketselected: $('input[name="s3bucketselected"]').val(),
                s3base_url: $('input[name="s3base_url"]').val(),
                s3region: $('#s3region').val(),
                _ajax_nonce: $('#backwpupajaxnonce').val()
            };
            $.post(ajaxurl, data, function(response) {
                $('#s3bucketerror').remove();
                $('#s3bucket').remove();
                $('#s3bucketselected').after(response);
            });
        }
		$('input[name="s3accesskey"]').change(function() {awsgetbucket();});
		$('input[name="s3secretkey"]').change(function() {awsgetbucket();});
		$('input[name="s3base_url"]').change(function() {awsgetbucket();});
		$('#s3region').change(function() {awsgetbucket();});
		<?php
	}

	/**
	 * @param string $args
	 */
	public function edit_ajax( $args = '' ) {

		$error = '';

		if ( is_array( $args ) ) {
			$ajax = FALSE;
		}
		else {
			if ( ! current_user_can( 'backwpup_jobs_edit' ) )
				wp_die( -1 );
			check_ajax_referer( 'backwpup_ajax_nonce' );
			$args[ 's3accesskey' ]  	= $_POST[ 's3accesskey' ];
			$args[ 's3secretkey' ]  	= $_POST[ 's3secretkey' ];
			$args[ 's3bucketselected' ]	= $_POST[ 's3bucketselected' ];
			$args[ 's3base_url' ]  	 	= $_POST[ 's3base_url' ];
			$args[ 's3region' ]  	 	= $_POST[ 's3region' ];
			$ajax         				= TRUE;
		}
		echo '<span id="s3bucketerror" style="color:red;">';

		if ( ! empty( $args[ 's3accesskey' ] ) && ! empty( $args[ 's3secretkey' ] ) ) {
			try {
				$s3 = new AmazonS3( array( 	'key' => $args[ 's3accesskey' ],
											'secret' => BackWPup_Encryption::decrypt( $args[ 's3secretkey' ] ),
											'certificate_authority'	=> TRUE ) );
				$base_url = $this->get_s3_base_url( $args[ 's3region' ], $args[ 's3base_url' ] );
				if ( stristr( $base_url, 'amazonaws.com' ) ) {
					$s3->set_region( str_replace( array( 'http://', 'https://' ), '', $base_url ) );
				} else {
					$s3->set_hostname( str_replace( array( 'http://', 'https://' ), '', $base_url ) );
					$s3->allow_hostname_override( FALSE );
					if ( substr( $base_url, -1 ) == '/')
						$s3->enable_path_style( TRUE );
				}
				if ( stristr( $base_url, 'http://' ) )
					$s3->disable_ssl();

				$buckets = $s3->list_buckets();
			}
			catch ( Exception $e ) {
				$error = $e->getMessage();
			}
		}

		if ( empty( $args[ 's3accesskey' ] ) )
			_e( 'Missing access key!', 'backwpup' );
		elseif ( empty( $args[ 's3secretkey' ] ) )
			_e( 'Missing secret access key!', 'backwpup' );
		elseif ( ! empty( $error ) && $error == 'Access Denied' )
			echo '<input type="text" name="s3bucket" id="s3bucket" value="' . esc_attr( $args[ 's3bucketselected' ] ) . '" >';
		elseif ( ! empty( $error ) )
			echo esc_html( $error );
		elseif ( ! isset( $buckets ) || count( $buckets->body->Buckets->Bucket  ) < 1 )
			_e( 'No bucket found!', 'backwpup' );
		echo '</span>';

		if ( ! empty(  $buckets->body->Buckets->Bucket ) ) {
			echo '<select name="s3bucket" id="s3bucket">';
			foreach ( $buckets->body->Buckets->Bucket  as $bucket ) {
				echo "<option " . selected( $args[ 's3bucketselected' ], esc_attr( $bucket->Name ), FALSE ) . ">" . esc_attr( $bucket->Name ) . "</option>";
			}
			echo '</select>';
		}

		if ( $ajax )
			die();
	}
}
