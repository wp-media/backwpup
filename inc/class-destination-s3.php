<?php
// Amazon S3 SDK v2.8.27
// http://aws.amazon.com/de/sdkforphp2/
// https://github.com/aws/aws-sdk-php
// http://docs.aws.amazon.com/general/latest/gr/rande.html#s3_region

use Inpsyde\BackWPup\Helper;

/**
 * Documentation: http://docs.amazonwebservices.com/aws-sdk-php-2/latest/class-Aws.S3.S3Client.html
 */
class BackWPup_Destination_S3 extends BackWPup_Destinations {


	/**
	 * @return array
	 */
	public function option_defaults() {

		return array(
			's3base_url'     => '',
			's3accesskey'    => '',
			's3secretkey'    => '',
			's3bucket'       => '',
			's3region'       => 'us-east-1',
			's3ssencrypt'    => '',
			's3storageclass' => '',
			's3dir'          => trailingslashit( sanitize_file_name( get_bloginfo( 'name' ) ) ),
			's3maxbackups'   => 15,
			's3syncnodelete' => true,
			's3multipart'    => true,
		);
	}

	/**
	 * @param $jobid
	 */
	public function edit_tab( $jobid ) {

		$current_destination = BackWPup_Option::get( $jobid, 's3region' );
		preg_match( '/^google|dreamhost/', $current_destination, $destination_doesnt_allow_multipart_upload );
		?>
		<h3 class="title">
			<?php esc_html_e( 'S3 Service', 'backwpup' ); ?>
		</h3>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="s3region">
						<?php esc_html_e( 'Select a S3 service', 'backwpup' ); ?>
					</label>
				</th>
				<td>
					<select name="s3region"
					        id="s3region"
					        title="<?php esc_attr_e( 'Amazon S3 Region', 'backwpup' ); ?>">
						<?php foreach ( $this->destinations_options_list( $jobid ) as $option ) : ?>
							<option value="<?php echo esc_attr( $option['value'] ); ?>"
								<?php selected( $option['value'], $current_destination, true ); ?>
							>
								<?php echo esc_html( $option['label'] ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="s3base_url">
						<?php esc_html_e( 'Or a S3 Server URL', 'backwpup' ); ?>
					</label>
				</th>
				<td>
					<input
						id="s3base_url"
						name="s3base_url"
						type="text"
						value="<?php echo esc_attr( BackWPup_Option::get( $jobid, 's3base_url' ) ); ?>"
						class="regular-text"
						autocomplete="off"
					/>
				</td>
			</tr>

			<?php if ( BackWPup_Option::get( $jobid, 'backuptype' ) === 'archive' ) : ?>
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Multipart Upload', 'backwpup' ); ?>
					</th>
					<td>
						<label for="ids3multipart">
							<input class="checkbox"
							       value="1"
							       type="checkbox"
								<?php checked( BackWPup_Option::get( $jobid, 's3multipart' ), true ); ?>
								<?php echo $destination_doesnt_allow_multipart_upload ? 'disabled="disabled"' : '' ?>
								   name="s3multipart"
								   id="ids3multipart"
							/>
							<?php esc_html_e( 'Use multipart upload for uploading a file', 'backwpup' ); ?>
						</label>
						<p class="description">
							<?php
							echo wp_kses_post( __(
								'Multipart splits file into multiple chunks while uploading.<br />This is necessary for displaying the upload process and to transfer bigger files.<br />Don\'t work with Google or Dreamhost.',
								'backwpup'
							) );
							?>
						</p>
					</td>
				</tr>
			<?php endif; ?>

		</table>

		<h3 class="title">
			<?php esc_html_e( 'S3 Access Keys', 'backwpup' ); ?>
		</h3>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="s3accesskey">
						<?php esc_html_e( 'Access Key', 'backwpup' ); ?>
					</label>
				</th>
				<td>
					<input id="s3accesskey"
					       name="s3accesskey"
					       type="text"
					       value="<?php echo esc_attr( BackWPup_Option::get( $jobid, 's3accesskey' ) ); ?>"
					       class="regular-text"
					       autocomplete="off"
					/>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="s3secretkey"><?php esc_html_e( 'Secret Key', 'backwpup' ); ?></label></th>
				<td>
					<input id="s3secretkey" name="s3secretkey" type="password"
					       value="<?php echo esc_attr( BackWPup_Encryption::decrypt( BackWPup_Option::get( $jobid,
						       's3secretkey' ) ) ); ?>" class="regular-text" autocomplete="off"/>
				</td>
			</tr>
		</table>

		<h3 class="title">
			<?php esc_html_e( 'S3 Bucket', 'backwpup' ); ?>
		</h3>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="s3bucketselected">
						<?php esc_html_e( 'Bucket selection', 'backwpup' ); ?>
					</label>
				</th>
				<td>
					<input id="s3bucketselected"
					       name="s3bucketselected"
					       type="hidden"
					       value="<?php echo esc_attr( BackWPup_Option::get( $jobid, 's3bucket' ) ); ?>"
					/>
					<?php
					if ( BackWPup_Option::get( $jobid, 's3accesskey' )
					     && BackWPup_Option::get( $jobid, 's3secretkey' )
					) {
						$this->edit_ajax( array(
							's3accesskey'      => BackWPup_Option::get( $jobid, 's3accesskey' ),
							's3secretkey'      => BackWPup_Encryption::decrypt(
								BackWPup_Option::get( $jobid, 's3secretkey' )
							),
							's3bucketselected' => BackWPup_Option::get( $jobid, 's3bucket' ),
							's3base_url'       => BackWPup_Option::get( $jobid, 's3base_url' ),
							's3region'         => BackWPup_Option::get( $jobid, 's3region' ),
						) );
					}
					?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="s3newbucket">
						<?php esc_html_e( 'Create a new bucket', 'backwpup' ); ?>
					</label>
				</th>
				<td>
					<input id="s3newbucket"
					       name="s3newbucket"
					       type="text"
					       value=""
					       class="small-text"
					       autocomplete="off"
					/>
				</td>
			</tr>
		</table>

		<h3 class="title">
			<?php esc_html_e( 'S3 Backup settings', 'backwpup' ); ?>
		</h3>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="ids3dir">
						<?php esc_html_e( 'Folder in bucket', 'backwpup' ); ?>
					</label>
				</th>
				<td>
					<input id="ids3dir"
					       name="s3dir"
					       type="text"
					       value="<?php echo esc_attr( BackWPup_Option::get( $jobid, 's3dir' ) ); ?>"
					       class="regular-text"
					/>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'File deletion', 'backwpup' ); ?></th>
				<td>
					<?php
					if ( BackWPup_Option::get( $jobid, 'backuptype' ) === 'archive' ) :
						?>
						<label for="ids3maxbackups">
							<input id="ids3maxbackups"
							       name="s3maxbackups"
							       type="number"
							       min="0"
							       step="1"
							       value="<?php echo esc_attr( BackWPup_Option::get( $jobid, 's3maxbackups' ) ); ?>"
							       class="small-text"
							/>
							&nbsp;<?php esc_html_e( 'Number of files to keep in folder.', 'backwpup' ); ?>
						</label>
						<p>
							<?php _e(
								'<strong>Warning</strong>: Files belonging to this job are now tracked. Old backup archives which are untracked will not be automatically deleted.',
								'backwpup'
							) ?>
						</p>
					<?php else : ?>
						<label for="ids3syncnodelete">
							<input class="checkbox" value="1"
							       type="checkbox"
								<?php checked( BackWPup_Option::get( $jobid, 's3syncnodelete' ), true ); ?>
								   name="s3syncnodelete"
								   id="ids3syncnodelete"
							/>
							<?php esc_html_e( 'Do not delete files while syncing to destination!', 'backwpup' ); ?>
						</label>
					<?php endif; ?>
				</td>
			</tr>
		</table>

		<h3 class="title"><?php esc_html_e( 'Amazon specific settings', 'backwpup' ); ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="ids3storageclass">
						<?php esc_html_e( 'Amazon: Storage Class', 'backwpup' ); ?>
					</label>
				</th>
				<td>
					<select name="s3storageclass"
					        id="ids3storageclass"
					        title="<?php esc_html_e( 'Amazon: Storage Class', 'backwpup' ); ?>">
						<option value=""
							<?php selected( '', BackWPup_Option::get( $jobid, 's3storageclass' ), true ) ?>>
							<?php esc_html_e( 'Standard', 'backwpup' ); ?>
						</option>
						<option value="STANDARD_IA"
							<?php selected( 'STANDARD_IA', BackWPup_Option::get( $jobid, 's3storageclass' ), true ) ?>>
							<?php esc_html_e( 'Standard-Infrequent Access', 'backwpup' ); ?>
						</option>
						<option value="REDUCED_REDUNDANCY"
							<?php selected(
								'REDUCED_REDUNDANCY',
								BackWPup_Option::get( $jobid, 's3storageclass' ),
								true
							) ?>>
							<?php esc_html_e( 'Reduced Redundancy', 'backwpup' ); ?>
						</option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="ids3ssencrypt">
						<?php esc_html_e( 'Server side encryption', 'basckwpup' ); ?>
					</label>
				</th>
				<td>
					<input class="checkbox"
					       value="AES256"
					       type="checkbox"
						<?php checked( BackWPup_Option::get( $jobid, 's3ssencrypt' ), 'AES256' ); ?>
						   name="s3ssencrypt"
						   id="ids3ssencrypt"
					/>
					<?php esc_html_e( 'Save files encrypted (AES256) on server.', 'backwpup' ); ?>
				</td>
			</tr>
		</table>

		<?php
	}

	/**
	 * @param string $args
	 */
	public function edit_ajax( $args = '' ) {

		$error        = '';
		$buckets_list = array();

		if ( is_array( $args ) ) {
			$ajax = false;
		} else {
			if ( ! current_user_can( 'backwpup_jobs_edit' ) ) {
				wp_die( - 1 );
			}
			check_ajax_referer( 'backwpup_ajax_nonce' );
			$args                     = array();
			$args['s3accesskey']      = sanitize_text_field( $_POST['s3accesskey'] );
			$args['s3secretkey']      = sanitize_text_field( $_POST['s3secretkey'] );
			$args['s3bucketselected'] = sanitize_text_field( $_POST['s3bucketselected'] );
			$args['s3base_url']       = esc_url_raw( $_POST['s3base_url'] );
			$args['s3region']         = sanitize_text_field( $_POST['s3region'] );
			$ajax                     = true;
		}
		echo '<span id="s3bucketerror" style="color:red;">';

		if ( ! empty( $args['s3accesskey'] ) && ! empty( $args['s3secretkey'] ) ) {
			try {
				$s3 = Aws\S3\S3Client::factory( array(
					'signature'                 => 'v4',
					'key'                       => $args['s3accesskey'],
					'secret'                    => BackWPup_Encryption::decrypt( $args['s3secretkey'] ),
					'region'                    => $args['s3region'],
					'base_url'                  => $this->get_s3_base_url( $args['s3region'], $args['s3base_url'] ),
					'scheme'                    => 'https',
					'ssl.certificate_authority' => BackWPup::get_plugin_data( 'cacert' ),
				) );

				$buckets = $s3->listBuckets();
				if ( ! empty( $buckets['Buckets'] ) ) {
					$buckets_list = $buckets['Buckets'];
				}

				while ( ! empty( $vaults['Marker'] ) ) {
					$buckets = $s3->listBuckets( array( 'marker' => $buckets['Marker'] ) );
					if ( ! empty( $buckets['Buckets'] ) ) {
						$buckets_list = array_merge( $buckets_list, $buckets['Buckets'] );
					}
				}
			} catch ( Exception $e ) {
				$error = $e->getMessage();
			}
		}

		if ( empty( $args['s3accesskey'] ) ) {
			esc_html_e( 'Missing access key!', 'backwpup' );
		} elseif ( empty( $args['s3secretkey'] ) ) {
			esc_html_e( 'Missing secret access key!', 'backwpup' );
		} elseif ( ! empty( $error ) && $error == 'Access Denied' ) {
			echo '<input type="text" name="s3bucket" id="s3bucket" value="' . esc_attr( $args['s3bucketselected'] ) . '" >';
		} elseif ( ! empty( $error ) ) {
			echo esc_html( $error );
		} elseif ( ! isset( $buckets ) || count( $buckets['Buckets'] ) < 1 ) {
			esc_html_e( 'No bucket found!', 'backwpup' );
		}
		echo '</span>';

		if ( ! empty( $buckets_list ) ) {
			echo '<select name="s3bucket" id="s3bucket">';
			foreach ( $buckets_list as $bucket ) {
				echo "<option " . selected( $args['s3bucketselected'], esc_attr( $bucket['Name'] ), false ) . ">"
				     . esc_attr( $bucket['Name'] )
				     . "</option>";
			}
			echo '</select>';
		}

		if ( $ajax ) {
			die();
		}
	}

	/**
	 * @param $s3region
	 *
	 * @return string
	 */
	protected function get_s3_base_url( $s3region, $s3base_url = '' ) {

		if ( ! empty( $s3base_url ) ) {
			return $s3base_url;
		}

		switch ( $s3region ) {
			case 'us-east-1':
				return 'https://s3.amazonaws.com';
			case 'us-west-1':
				return 'https://s3-us-west-1.amazonaws.com';
			case 'us-west-2':
				return 'https://s3-us-west-2.amazonaws.com';
			case 'eu-west-1':
				return 'https://s3-eu-west-1.amazonaws.com';
			case 'eu-west-2':
				return 'https://s3-eu-west-2.amazonaws.com';
			case 'eu-central-1':
				return 'https://s3-eu-central-1.amazonaws.com';
			case 'ap-south-1':
				return 'https://s3-ap-south-1.amazonaws.com';
			case 'ap-northeast-1':
				return 'https://s3-ap-northeast-1.amazonaws.com';
			case 'ap-northeast-2':
				return 'https://s3-ap-northeast-2.amazonaws.com';
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
			case 'google-storage-us':
				return 'https://storage.googleapis.com';
			case 'google-storage-asia':
				return 'https://storage.googleapis.com';
			case 'dreamhost':
				return 'https://objects-us-west-1.dream.io';
			default:
				return '';
		}
	}

	/**
	 * @param $jobid
	 *
	 * @return string
	 */
	public function edit_form_post_save( $jobid ) {

		BackWPup_Option::update( $jobid, 's3accesskey', sanitize_text_field( $_POST['s3accesskey'] ) );
		BackWPup_Option::update(
			$jobid,
			's3secretkey',
			isset( $_POST['s3secretkey'] )
				? BackWPup_Encryption::encrypt( $_POST['s3secretkey'] )
				: ''
		);
		BackWPup_Option::update(
			$jobid,
			's3base_url',
			isset( $_POST['s3base_url'] )
				? esc_url_raw( $_POST['s3base_url'] )
				: ''
		);
		BackWPup_Option::update( $jobid, 's3region', sanitize_text_field( $_POST['s3region'] ) );
		BackWPup_Option::update( $jobid, 's3storageclass', sanitize_text_field( $_POST['s3storageclass'] ) );
		BackWPup_Option::update( $jobid,
			's3ssencrypt',
			( isset( $_POST['s3ssencrypt'] ) && $_POST['s3ssencrypt'] === 'AES256' ) ? 'AES256' : '' );
		BackWPup_Option::update( $jobid,
			's3bucket',
			isset( $_POST['s3bucket'] ) ? sanitize_text_field( $_POST['s3bucket'] ) : '' );

		$_POST['s3dir'] = trailingslashit( str_replace( '//',
			'/',
			str_replace( '\\', '/', trim( sanitize_text_field( $_POST['s3dir'] ) ) ) ) );
		if ( substr( $_POST['s3dir'], 0, 1 ) == '/' ) {
			$_POST['s3dir'] = substr( $_POST['s3dir'], 1 );
		}
		if ( $_POST['s3dir'] == '/' ) {
			$_POST['s3dir'] = '';
		}
		BackWPup_Option::update( $jobid, 's3dir', $_POST['s3dir'] );

		BackWPup_Option::update( $jobid,
			's3maxbackups',
			! empty( $_POST['s3maxbackups'] ) ? absint( $_POST['s3maxbackups'] ) : 0 );
		BackWPup_Option::update( $jobid, 's3syncnodelete', ! empty( $_POST['s3syncnodelete'] ) );
		BackWPup_Option::update( $jobid, 's3multipart', ! empty( $_POST['s3multipart'] ) );

		//create new bucket
		if ( ! empty( $_POST['s3newbucket'] ) ) {
			try {
				$s3 = Aws\S3\S3Client::factory( array(
					'signature'                 => 'v4',
					'key'                       => sanitize_text_field( $_POST['s3accesskey'] ),
					'secret'                    => sanitize_text_field( $_POST['s3secretkey'] ),
					'region'                    => sanitize_text_field( $_POST['s3region'] ),
					'base_url'                  => $this->get_s3_base_url(
						sanitize_text_field( $_POST['s3region'] ),
						esc_url_raw( $_POST['s3base_url'] )
					),
					'scheme'                    => 'https',
					'ssl.certificate_authority' => BackWPup::get_plugin_data( 'cacert' ),
				) );
				// set bucket creation region
				if ( $_POST['s3region'] === 'google-storage' ) {
					$region = 'EU';
				} elseif ( $_POST['s3region'] === 'google-storage-us' ) {
					$region = 'US';
				} elseif ( $_POST['s3region'] === 'google-storage-asia' ) {
					$region = 'ASIA';
				} else {
					$region = sanitize_text_field( $_POST['s3region'] );
				}

				if ( $s3->isValidBucketName( $_POST['s3newbucket'] ) ) {
					$s3->createBucket( array(
						'Bucket'             => sanitize_text_field( $_POST['s3newbucket'] ),
						'LocationConstraint' => $region,
					) );
					$s3->waitUntil( 'bucket_exists', array( 'Bucket' => $_POST['s3newbucket'] ) );
					BackWPup_Admin::message( sprintf( __( 'Bucket %1$s created.', 'backwpup' ),
						sanitize_text_field( $_POST['s3newbucket'] ) ) );
				} else {
					BackWPup_Admin::message( sprintf( __( ' %s is not a valid bucket name.', 'backwpup' ),
						sanitize_text_field( $_POST['s3newbucket'] ) ),
						true );
				}
			} catch ( Aws\S3\Exception\S3Exception $e ) {
				BackWPup_Admin::message( $e->getMessage(), true );
			}
			BackWPup_Option::update( $jobid, 's3bucket', sanitize_text_field( $_POST['s3newbucket'] ) );
		}
	}

	/**
	 * @param $jobdest
	 * @param $backupfile
	 */
	public function file_delete( $jobdest, $backupfile ) {

		$files = get_site_transient( 'backwpup_' . strtolower( $jobdest ) );
		list( $jobid, $dest ) = explode( '_', $jobdest );

		if ( BackWPup_Option::get( $jobid, 's3accesskey' ) && BackWPup_Option::get( $jobid,
				's3secretkey' ) && BackWPup_Option::get( $jobid, 's3bucket' ) ) {
			try {
				$s3 = Aws\S3\S3Client::factory( array(
					'signature'                 => 'v4',
					'key'                       => BackWPup_Option::get( $jobid, 's3accesskey' ),
					'secret'                    => BackWPup_Encryption::decrypt(
						BackWPup_Option::get( $jobid, 's3secretkey' )
					),
					'region'                    => BackWPup_Option::get( $jobid, 's3region' ),
					'base_url'                  => $this->get_s3_base_url(
						BackWPup_Option::get( $jobid, 's3region' ),
						BackWPup_Option::get( $jobid, 's3base_url' )
					),
					'scheme'                    => 'https',
					'ssl.certificate_authority' => BackWPup::get_plugin_data( 'cacert' ),
				) );

				$s3->deleteObject( array(
					'Bucket' => BackWPup_Option::get( $jobid, 's3bucket' ),
					'Key'    => $backupfile,
				) );
				//update file list
				foreach ( (array) $files as $key => $file ) {
					if ( is_array( $file ) && $file['file'] == $backupfile ) {
						unset( $files[ $key ] );
					}
				}
				unset( $s3 );
			} catch ( Exception $e ) {
				BackWPup_Admin::message( sprintf( __( 'S3 Service API: %s', 'backwpup' ), $e->getMessage() ), true );
			}
		}

		set_site_transient( 'backwpup_' . strtolower( $jobdest ), $files, YEAR_IN_SECONDS );
	}

	/**
	 * Download
	 *
	 * @param int    $jobid
	 * @param string $file_path
	 * @param string $local_file_path
	 */
	public function file_download( $jobid, $file_path, $local_file_path = null ) {

		$capability = 'backwpup_backups_download';
		$filename   = untrailingslashit( BackWPup::get_plugin_data( 'temp' ) ) . '/' . basename( $file_path );
		$job_id     = filter_var( $_GET['jobid'], FILTER_SANITIZE_NUMBER_INT );

		$self       = $this;
		$downloader = new BackWpup_Download_Handler(
			new BackWPup_Download_File(
				$filename,
				function ( \BackWPup_Download_File_Interface $obj ) use ( $self, $filename, $file_path, $job_id ) {

					$base_url = $self->get_s3_base_url(
						BackWPup_Option::get( $job_id, 's3region' ),
						BackWPup_Option::get( $job_id, 's3base_url' )
					);
					$factory = new BackWPup_Destination_Downloader_Factory();
					$downloader = $factory->create(
						'S3',
						$job_id,
						$file_path,
						$filename,
						$base_url
					);
					$downloader->download_by_chunks();

					die();
				},
				$capability
			),
			'backwpup_action_nonce',
			$capability,
			'download_backup_file'
		);

		// Download the file.
		$downloader->handle();
	}

	/**
	 * @inheritdoc
	 */
	public function file_get_list( $jobdest ) {

		$list = (array) get_site_transient( 'backwpup_' . strtolower( $jobdest ) );
		$list = array_filter( $list );

		return $list;
	}

	/**
	 * File Update List
	 *
	 * Update the list of files in the transient.
	 *
	 * @param BackWPup_Job|int $job    Either the job object or job ID
	 * @param bool             $delete Whether to delete old backups.
	 */
	public function file_update_list( $job, $delete = false ) {

		if ( $job instanceof BackWPup_Job ) {
			$job_object = $job;
			$jobid      = $job->job['jobid'];
		} else {
			$job_object = null;
			$jobid      = $job;
		}

		if ( ! $this->s3 ) {
			$s3 = Aws\S3\S3Client::factory( array(
				'signature'                 => 'v4',
				'key'                       => BackWPup_Option::get( $jobid, 's3accesskey' ),
				'secret'                    => BackWPup_Encryption::decrypt(
					BackWPup_Option::get( $jobid, 's3secretkey' )
				),
				'region'                    => BackWPup_Option::get( $jobid, 's3region' ),
				'scheme'                    => 'https',
				'ssl.certificate_authority' => BackWPup::get_plugin_data( 'cacert' ),
			) );
		} else {
			$s3 = $this->s3;
		}

		$backupfilelist = array();
		$filecounter    = 0;
		$files          = array();
		$args           = array(
			'Bucket' => BackWPup_Option::get( $jobid, 's3bucket' ),
			'Prefix' => (string) BackWPup_Option::get( $jobid, 's3dir' ),
		);
		$objects        = $s3->getIterator( 'ListObjects', $args );

		if ( is_object( $objects ) ) {
			foreach ( $objects as $object ) {
				$file       = basename( $object['Key'] );
				$changetime = strtotime( $object['LastModified'] ) + ( get_option( 'gmt_offset' ) * 3600 );

				if ( $this->is_backup_archive( $file ) && $this->is_backup_owned_by_job( $file, $jobid ) == true ) {
					$backupfilelist[ $changetime ] = $file;
				}

				$files[ $filecounter ]['folder'] = $this->get_s3_base_url(
						$job_object->job['s3region'],
						$job_object->job['s3base_url']
					) . '/' . $job_object->job['s3bucket'] . '/' . dirname( $object['Key'] );

				$files[ $filecounter ]['folder']   = $this->get_s3_base_url(
						BackWPup_Option::get( $jobid, 's3region' ),
						BackWPup_Option::get( $jobid, 's3base_url' )
					) . '/' . BackWPup_Option::get( $jobid, 's3bucket' ) . '/' . dirname( $object['Key'] );
				$files[ $filecounter ]['file']     = $object['Key'];
				$files[ $filecounter ]['filename'] = basename( $object['Key'] );

				if ( ! empty( $object['StorageClass'] ) ) {
					$files[ $filecounter ]['info'] = sprintf( __( 'Storage Class: %s', 'backwpup' ),
						$object['StorageClass'] );
				}

				$files[ $filecounter ]['downloadurl'] = network_admin_url( 'admin.php' ) . '?page=backwpupbackups&action=downloads3&file=' . $object['Key'] . '&local_file=' . basename( $object['Key'] ) . '&jobid=' . $jobid;
				$files[ $filecounter ]['filesize']    = $object['Size'];
				$files[ $filecounter ]['time']        = $changetime;

				$filecounter ++;
			}
		}

		if ( $delete && $job_object && $job_object->job['s3maxbackups'] > 0 && is_object( $s3 ) ) { //Delete old backups
			if ( count( $backupfilelist ) > $job_object->job['s3maxbackups'] ) {
				ksort( $backupfilelist );
				$numdeltefiles = 0;
				while ( $file = array_shift( $backupfilelist ) ) {
					if ( count( $backupfilelist ) < $job_object->job['s3maxbackups'] ) {
						break;
					}
					//delete files on S3
					$args = array(
						'Bucket' => $job_object->job['s3bucket'],
						'Key'    => $job_object->job['s3dir'] . $file,
					);

					if ( $s3->deleteObject( $args ) ) {
						foreach ( $files as $key => $filedata ) {
							if ( $filedata['file'] == $job_object->job['s3dir'] . $file ) {
								unset( $files[ $key ] );
							}
						}
						$numdeltefiles ++;
					} else {
						$job_object->log(
							sprintf( __( 'Cannot delete backup from %s.', 'backwpup' ),
								$this->get_s3_base_url(
									$job_object->job['s3region'],
									$job_object->job['s3base_url']
								) . '/' . $job_object->job['s3bucket'] . '/' . $job_object->job['s3dir'] . $file ),
							E_USER_ERROR
						);
					}
				}


				if ( $numdeltefiles > 0 ) {
					$job_object->log( sprintf( _n( 'One file deleted on S3 Bucket.',
						'%d files deleted on S3 Bucket',
						$numdeltefiles,
						'backwpup' ),
						$numdeltefiles ),
						E_USER_NOTICE );
				}
			}
		}
		set_site_transient( 'backwpup_' . $jobid . '_s3', $files, YEAR_IN_SECONDS );

	}

	/**
	 * @param $job_object BAckWPup_Job
	 *
	 * @return bool
	 */
	public function job_run_archive( BackWPup_Job $job_object ) {

		// Backward Compatibility, in case the user has this option set for google and dreamhost regions.
		preg_match( '/^google|dreamhost/', $job_object->job['s3region'], $destination_doesnt_allow_multipart_upload );
		if ( $destination_doesnt_allow_multipart_upload ) {
			$job_object->job['s3multipart'] = false;
		}

		$job_object->substeps_todo = 2 + $job_object->backup_filesize;

		if ( $job_object->steps_data[ $job_object->step_working ]['SAVE_STEP_TRY'] != $job_object->steps_data[ $job_object->step_working ]['STEP_TRY'] ) {
			$job_object->log(
				sprintf(
					__( '%d. Trying to send backup file to S3 Service&#160;&hellip;', 'backwpup' ),
					$job_object->steps_data[ $job_object->step_working ]['STEP_TRY']
				),
				E_USER_NOTICE
			);
		}

		try {
			$s3 = Aws\S3\S3Client::factory( array(
				'signature'                 => 'v4',
				'key'                       => $job_object->job['s3accesskey'],
				'secret'                    => BackWPup_Encryption::decrypt( $job_object->job['s3secretkey'] ),
				'region'                    => $job_object->job['s3region'],
				'base_url'                  => $this->get_s3_base_url(
					$job_object->job['s3region'],
					$job_object->job['s3base_url']
				),
				'scheme'                    => 'https',
				'ssl.certificate_authority' => BackWPup::get_plugin_data( 'cacert' ),
			) );

			$this->s3 = $s3;

			if ( $job_object->steps_data[ $job_object->step_working ]['SAVE_STEP_TRY'] != $job_object->steps_data[ $job_object->step_working ]['STEP_TRY'] && $job_object->substeps_done < $job_object->backup_filesize ) {
				if ( $s3->doesBucketExist( $job_object->job['s3bucket'] ) ) {
					$bucketregion = $s3->getBucketLocation( array( 'Bucket' => $job_object->job['s3bucket'] ) );
					$job_object->log( sprintf( __( 'Connected to S3 Bucket "%1$s" in %2$s', 'backwpup' ),
						$job_object->job['s3bucket'],
						$bucketregion->get( 'Location' ) ),
						E_USER_NOTICE );
				} else {
					$job_object->log( sprintf( __( 'S3 Bucket "%s" does not exist!', 'backwpup' ),
						$job_object->job['s3bucket'] ),
						E_USER_ERROR );

					return true;
				}

				if ( $job_object->job['s3multipart'] && empty( $job_object->steps_data[ $job_object->step_working ]['UploadId'] ) ) {
					//Check for aboded Multipart Uploads
					$job_object->log( __( 'Checking for not aborted multipart Uploads&#160;&hellip;', 'backwpup' ) );
					$multipart_uploads = $s3->listMultipartUploads( array(
						'Bucket' => $job_object->job['s3bucket'],
						'Prefix' => (string) $job_object->job['s3dir'],
					) );
					$uploads           = $multipart_uploads->get( 'Uploads' );
					if ( ! empty( $uploads ) ) {
						foreach ( $uploads as $upload ) {
							$s3->abortMultipartUpload( array(
								'Bucket'   => $job_object->job['s3bucket'],
								'Key'      => $upload['Key'],
								'UploadId' => $upload['UploadId'],
							) );
							$job_object->log( sprintf( __( 'Upload for %s aborted.', 'backwpup' ), $upload['Key'] ) );
						}
					}
				}

				//transfer file to S3
				$job_object->log( __( 'Starting upload to S3 Service&#160;&hellip;', 'backwpup' ) );
			}


			if ( ! $job_object->job['s3multipart'] || $job_object->backup_filesize < 1048576 * 6 ) {
				// Prepare Upload
				if ( ! $up_file_handle = fopen( $job_object->backup_folder . $job_object->backup_file, 'rb' ) ) {
					$job_object->log( __( 'Can not open source file for transfer.', 'backwpup' ), E_USER_ERROR );

					return false;
				}
				$create_args           = array();
				$create_args['Bucket'] = $job_object->job['s3bucket'];
				$create_args['ACL']    = 'private';
				// Encryption
				if ( ! empty( $job_object->job['s3ssencrypt'] ) ) {
					$create_args['ServerSideEncryption'] = $job_object->job['s3ssencrypt'];
				}
				// Storage Class
				if ( ! empty( $job_object->job['s3storageclass'] ) ) {
					$create_args['StorageClass'] = $job_object->job['s3storageclass'];
				}
				$create_args['Metadata'] = array( 'BackupTime' => date( 'Y-m-d H:i:s', $job_object->start_time ) );

				$create_args['Body']        = $up_file_handle;
				$create_args['Key']         = $job_object->job['s3dir'] . $job_object->backup_file;
				$create_args['ContentType'] = Helper\MimeType::from_file_path( $job_object->backup_folder . $job_object->backup_file );

				try {
					$s3->putObject( $create_args );
				} catch ( Aws\Common\Exception\MultipartUploadException $e ) {
					$job_object->log( E_USER_ERROR,
						sprintf( __( 'S3 Service API: %s', 'backwpup' ), $e->getMessage() ),
						$e->getFile(),
						$e->getLine() );

					return false;
				}
			} else {
				// Prepare Upload
				if ( $file_handle = fopen( $job_object->backup_folder . $job_object->backup_file, 'rb' ) ) {
					fseek( $file_handle, $job_object->substeps_done );

					try {

						if ( empty ( $job_object->steps_data[ $job_object->step_working ]['UploadId'] ) ) {
							$args = array(
								'ACL'         => 'private',
								'Bucket'      => $job_object->job['s3bucket'],
								'ContentType' => Helper\MimeType::from_file_path( $job_object->backup_folder . $job_object->backup_file ),
								'Key'         => $job_object->job['s3dir'] . $job_object->backup_file,
							);
							if ( ! empty( $job_object->job['s3ssencrypt'] ) ) {
								$args['ServerSideEncryption'] = $job_object->job['s3ssencrypt'];
							}
							if ( ! empty( $job_object->job['s3storageclass'] ) ) {
								$args['StorageClass'] = empty( $job_object->job['s3storageclass'] ) ? '' : $job_object->job['s3storageclass'];
							}

							$upload = $s3->createMultipartUpload( $args );

							$job_object->steps_data[ $job_object->step_working ]['UploadId'] = $upload->get( 'UploadId' );
							$job_object->steps_data[ $job_object->step_working ]['Parts']    = array();
							$job_object->steps_data[ $job_object->step_working ]['Part']     = 1;
						}

						while ( ! feof( $file_handle ) ) {
							$chunk_upload_start                                             = microtime( true );
							$part_data                                                      = fread( $file_handle,
								1048576 * 5 ); //5MB Minimum part size
							$part                                                           = $s3->uploadPart( array(
								'Bucket'     => $job_object->job['s3bucket'],
								'UploadId'   => $job_object->steps_data[ $job_object->step_working ]['UploadId'],
								'Key'        => $job_object->job['s3dir'] . $job_object->backup_file,
								'PartNumber' => $job_object->steps_data[ $job_object->step_working ]['Part'],
								'Body'       => $part_data,
							) );
							$chunk_upload_time                                              = microtime( true ) - $chunk_upload_start;
							$job_object->substeps_done                                      = $job_object->substeps_done + strlen( $part_data );
							$job_object->steps_data[ $job_object->step_working ]['Parts'][] = array(
								'ETag'       => $part->get( 'ETag' ),
								'PartNumber' => $job_object->steps_data[ $job_object->step_working ]['Part'],
							);
							$job_object->steps_data[ $job_object->step_working ]['Part'] ++;
							$time_remaining = $job_object->do_restart_time();
							if ( $time_remaining < $chunk_upload_time ) {
								$job_object->do_restart_time( true );
							}
							$job_object->update_working_data();
						}

						$s3->completeMultipartUpload( array(
							'Bucket'   => $job_object->job['s3bucket'],
							'UploadId' => $job_object->steps_data[ $job_object->step_working ]['UploadId'],
							'Key'      => $job_object->job['s3dir'] . $job_object->backup_file,
							'Parts'    => $job_object->steps_data[ $job_object->step_working ]['Parts'],
						) );

					} catch ( Exception $e ) {
						$job_object->log( E_USER_ERROR,
							sprintf( __( 'S3 Service API: %s', 'backwpup' ), $e->getMessage() ),
							$e->getFile(),
							$e->getLine() );
						if ( ! empty( $job_object->steps_data[ $job_object->step_working ]['uploadId'] ) ) {
							$s3->abortMultipartUpload( array(
								'Bucket'   => $job_object->job['s3bucket'],
								'UploadId' => $job_object->steps_data[ $job_object->step_working ]['uploadId'],
								'Key'      => $job_object->job['s3dir'] . $job_object->backup_file,
							) );
						}
						unset( $job_object->steps_data[ $job_object->step_working ]['UploadId'] );
						unset( $job_object->steps_data[ $job_object->step_working ]['Parts'] );
						unset( $job_object->steps_data[ $job_object->step_working ]['Part'] );
						$job_object->substeps_done = 0;
						if ( is_resource( $file_handle ) ) {
							fclose( $file_handle );
						}

						return false;
					}
					fclose( $file_handle );
				} else {
					$job_object->log( __( 'Can not open source file for transfer.', 'backwpup' ), E_USER_ERROR );

					return false;
				}
			}

			$result = $s3->headObject( array(
				'Bucket' => $job_object->job['s3bucket'],
				'Key'    => $job_object->job['s3dir'] . $job_object->backup_file,
			) );

			if ( $result->get( 'ContentLength' ) == filesize( $job_object->backup_folder . $job_object->backup_file ) ) {
				$job_object->substeps_done = 1 + $job_object->backup_filesize;
				$job_object->log(
					sprintf( __( 'Backup transferred to %s.', 'backwpup' ),
						$this->get_s3_base_url(
							$job_object->job['s3region'],
							$job_object->job['s3base_url']
						) . '/' . $job_object->job['s3bucket'] . '/' . $job_object->job['s3dir'] . $job_object->backup_file ),
					E_USER_NOTICE
				);

				if ( ! empty( $job_object->job['jobid'] ) ) {
					BackWPup_Option::update(
						$job_object->job['jobid'],
						'lastbackupdownloadurl',
						network_admin_url( 'admin.php' ) . '?page=backwpupbackups&action=downloads3&file=' . $job_object->job['s3dir'] . $job_object->backup_file . '&jobid=' . $job_object->job['jobid']
					);
				}
			} else {
				$job_object->log( sprintf( __( 'Cannot transfer backup to S3! (%1$d) %2$s', 'backwpup' ),
					$result->get( "status" ),
					$result->get( "Message" ) ),
					E_USER_ERROR );
			}
		} catch ( Exception $e ) {
			$job_object->log( E_USER_ERROR,
				sprintf( __( 'S3 Service API: %s', 'backwpup' ), $e->getMessage() ),
				$e->getFile(),
				$e->getLine() );

			return false;
		}

		try {
			$this->file_update_list( $job_object, true );
		} catch ( Exception $e ) {
			$job_object->log( E_USER_ERROR,
				sprintf( __( 'S3 Service API: %s', 'backwpup' ), $e->getMessage() ),
				$e->getFile(),
				$e->getLine() );

			return false;
		}
		$job_object->substeps_done = 2 + $job_object->backup_filesize;

		return true;
	}

	/**
	 * @param $job_settings array
	 *
	 * @return bool
	 */
	public function can_run( array $job_settings ) {

		if ( empty( $job_settings['s3accesskey'] ) ) {
			return false;
		}

		if ( empty( $job_settings['s3secretkey'] ) ) {
			return false;
		}

		if ( empty( $job_settings['s3bucket'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 *
	 */
	public function edit_inline_js() {

		?>
		<script type="text/javascript">
			jQuery( document ).ready( function ( $ ) {
				function awsgetbucket() {
					var data = {
						action          : 'backwpup_dest_s3',
						s3accesskey     : $( 'input[name="s3accesskey"]' ).val(),
						s3secretkey     : $( 'input[name="s3secretkey"]' ).val(),
						s3bucketselected: $( 'input[name="s3bucketselected"]' ).val(),
						s3base_url      : $( 'input[name="s3base_url"]' ).val(),
						s3region        : $( '#s3region' ).val(),
						_ajax_nonce     : $( '#backwpupajaxnonce' ).val()
					};
					$.post( ajaxurl, data, function ( response ) {
						$( '#s3bucketerror' ).remove();
						$( '#s3bucket' ).remove();
						$( '#s3bucketselected' ).after( response );
					} );
				}

				function disableMultipartUploadOption() {

					var select  = document.querySelector( '#s3region' );
					var baseUrl = document.querySelector( 'input[name="s3base_url"]' );

					if ( ! select && ! baseUrl ) {
						return;
					}

					var regExp   = new RegExp( /^google|dreamhost|.*google.*|.*dreamhost.*/, 'g' );
					var disabled = null !== regExp.exec( select.value );
					var input    = document.querySelector( '#ids3multipart' );

					disabled = disabled || null !== regExp.exec( baseUrl.value );

					input.disabled = disabled;

					if ( disabled ) {
						input.checked = false;
					}
				}

				$( 'input[name="s3accesskey"]' ).backwpupDelayKeyup( function () {
					awsgetbucket();
				} );
				$( 'input[name="s3secretkey"]' ).backwpupDelayKeyup( function () {
					awsgetbucket();
				} );
				$( 'input[name="s3base_url"]' ).backwpupDelayKeyup( function () {
					awsgetbucket();
				} );

				$( 'input[name="s3base_url"]' ).change( function () {
					disableMultipartUploadOption();
				} );
				$( '#s3region' ).change( function () {
					disableMultipartUploadOption();

					awsgetbucket();
				} );
			} );
		</script>
		<?php
	}

	/**
	 * @param $jobid
	 *
	 * @return array
	 */
	private function destinations_options_list() {

		return array(
			array(
				'label' => __( 'Amazon S3: US Standard', 'backwpup' ),
				'value' => 'us-east-1',
			),
			array(
				'label' => __( 'Amazon S3: US West (Northern California)', 'backwpup' ),
				'value' => 'us-west-1',
			),
			array(
				'label' => __( 'Amazon S3: US West (Oregon)', 'backwpup' ),
				'value' => 'us-west-2',
			),
			array(
				'label' => __( 'Amazon S3: EU (Ireland)', 'backwpup' ),
				'value' => 'eu-west-1',
			),
			array(
				'label' => __( 'Amazon S3: EU (London)', 'backwpup' ),
				'value' => 'eu-west-2',
			),
			array(
				'label' => __( 'Amazon S3: EU (Germany)', 'backwpup' ),
				'value' => 'eu-central-1',
			),
			array(
				'label' => __( 'Amazon S3: Asia Pacific (Mumbai)', 'backwpup' ),
				'value' => 'ap-south-1',
			),
			array(
				'label' => __( 'Amazon S3: Asia Pacific (Tokyo)', 'backwpup' ),
				'value' => 'ap-northeast-1',
			),
			array(
				'label' => __( 'Amazon S3: Asia Pacific (Seoul)', 'backwpup' ),
				'value' => 'ap-northeast-2',
			),
			array(
				'label' => __( 'Amazon S3: Asia Pacific (Singapore)', 'backwpup' ),
				'value' => 'ap-southeast-1',
			),
			array(
				'label' => __( 'Amazon S3: Asia Pacific (Sydney)', 'backwpup' ),
				'value' => 'ap-southeast-2',
			),
			array(
				'label' => __( 'Amazon S3: South America (Sao Paulo)', 'backwpup' ),
				'value' => 'sa-east-1',
			),
			array(
				'label' => __( 'Amazon S3: China (Beijing)', 'backwpup' ),
				'value' => 'cn-north-1',
			),
			array(
				'label' => __( 'Google Storage: EU', 'backwpup' ),
				'value' => 'google-storage',
			),
			array(
				'label' => __( 'Google Storage: USA', 'backwpup' ),
				'value' => 'google-storage-us',
			),
			array(
				'label' => __( 'Google Storage: Asia', 'backwpup' ),
				'value' => 'google-storage-asia',
			),
			array(
				'label' => __( 'Dream Host Cloud Storage', 'backwpup' ),
				'value' => 'dreamhost',
			),
		);
	}
}
