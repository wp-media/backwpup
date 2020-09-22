<?php
// Amazon S3 SDK v3.93.7
// http://aws.amazon.com/de/sdkforphp2/
// https://github.com/aws/aws-sdk-php
// http://docs.aws.amazon.com/general/latest/gr/rande.html#s3_region

use \Inpsyde\BackWPupShared\File\MimeTypeExtractor;

/**
 * Documentation: http://docs.amazonwebservices.com/aws-sdk-php-2/latest/class-Aws.S3.S3Client.html
 */
class BackWPup_Destination_S3 extends BackWPup_Destinations {


	/**
	 * @return array
	 */
	public function option_defaults() {

        return [
            's3base_url' => '',
            's3base_multipart' => true,
            's3base_pathstyle' => false,
            's3base_version' => 'latest',
            's3base_signature' => 'v4',
            's3accesskey' => '',
            's3secretkey' => '',
            's3bucket' => '',
            's3region' => 'us-east-1',
            's3ssencrypt' => '',
            's3storageclass' => '',
            's3dir' => trailingslashit(sanitize_file_name(get_bloginfo('name'))),
            's3maxbackups' => 15,
            's3syncnodelete' => true,
        ];
    }

	/**
	 * @param $jobid
	 */
	public function edit_tab( $jobid ) {

		?>
		<h3 class="title">
			<?php esc_html_e( 'S3 Service', 'backwpup' ); ?>
		</h3>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="s3region">
                        <?php esc_html_e('Select a S3 service', 'backwpup'); ?>
                    </label>
                </th>
                <td>
                    <select name="s3region"
                            id="s3region"
                            title="<?php esc_attr_e('S3 Region', 'backwpup'); ?>">
                        <?php foreach (BackWPup_S3_Destination::options() as $id => $option) : ?>
                            <option value="<?php echo esc_attr($id); ?>"
                                <?php selected($id, BackWPup_Option::get($jobid, 's3region')); ?>
                            >
                                <?php echo esc_html($option['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="s3base_url">
                        <?php esc_html_e('Or a S3 Server URL', 'backwpup'); ?>
                    </label>
                </th>
                <td>
                    <div class="card" style="margin-top:0;padding:10px">
                        <table class="form-table">
                            <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="s3base_url-s3"><?php esc_html_e('Endpoint' , 'backwpup') ?><span
                                            style="color:red">*</span></label>
                                </th>
                                <td>
                                    <input
                                        id="s3base_url"
                                        name="s3base_url"
                                        type="text"
                                        value="<?php echo esc_attr(
                                            BackWPup_Option::get($jobid, 's3base_url')
                                        ); ?>"
                                        class="regular-text"
                                        autocomplete="off"
                                    />
                                    <p class="description"><?php esc_html_e(
                                            'Leave it empty to use a destination from S3 service list',
                                            'backwpup'
                                        ) ?></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="s3base_region"><?php esc_html_e(
                                            'Region',
                                            'backwpup'
                                        ) ?><span style="color:red">*</span></label>
                                </th>
                                <td>
                                    <input type="text" name="s3base_region" value="<?= esc_attr(
                                        BackWPup_Option::get($jobid, 's3base_region')
                                    ); ?>" class="regular-text" autocomplete="off">
                                    <p class="description"><?php esc_html_e(
                                            'Specify S3 region like "us-west-1"',
                                            'backwpup'
                                        ) ?></p>
                                </td>
                            </tr>
                            </tbody>

                            <tbody class="custom_s3_advanced">
                            <tr>
                                <th scope="row"><?php esc_html_e('Multipart', 'backwpup') ?></th>
                                <td>
                                    <fieldset>
                                        <legend class="screen-reader-text">
                                            <span><?php esc_html_e(
                                                    'Multipart',
                                                    'backwpup'
                                                ) ?></span>
                                        </legend>
                                        <label for="s3base_multipart">
                                            <input name="s3base_multipart" type="checkbox"
                                                   checked="checked" value="<?= !empty(
                                            BackWPup_Option::get(
                                                $jobid,
                                                's3base_multipart'
                                            )
                                            ) ? '1' : '' ?>">
                                            <?php esc_html_e(
                                                'Destination supports multipart',
                                                'backwpup'
                                            ) ?> </label>
                                    </fieldset>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e(
                                        'Pathstyle-Only Bucket',
                                        'backwpup'
                                    ); ?></th>
                                <td>
                                    <fieldset>
                                        <legend class="screen-reader-text">
                                            <span><?php esc_html_e(
                                                    'Pathstyle-Only Bucket',
                                                    'backwpup'
                                                ) ?></span>
                                        </legend>
                                        <label
                                            for="s3base_pathstylebucket">
                                            <input name="s3base_pathstylebucket" type="checkbox"
                                                   value="<?= !empty(
                                                   BackWPup_Option::get(
                                                       $jobid,
                                                       's3base_pathstylebucket'
                                                   ) ? '1' : ''
                                                   ) ?>">
                                            <?php esc_html_e(
                                                'Destination provides only Pathstyle buckets',
                                                'backwpup'
                                            ); ?>    </label>
                                        <p class="description"><?php esc_html_e(
                                                'Example: http://s3.example.com/bucket-name',
                                                'backwpup'
                                            ); ?></p>

                                    </fieldset>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="s3base_version">Version</label>
                                </th>
                                <td>
                                    <input type="text" name="s3base_version"
                                           value="<?= !empty(
                                           BackWPup_Option::get(
                                               $jobid,
                                               's3base_version'
                                           )
                                           ) ? esc_attr(
                                               BackWPup_Option::get($jobid, 's3base_version')
                                           ) : 'latest' ?>"
                                           placeholder="latest">
                                    <p class="description"><?php esc_html_e(
                                            'The S3 version for the API like "2006-03-01", default "latest"',
                                            'backwpup'
                                        ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label
                                        for="s3base_signature"><?php esc_html_e(
                                            'Signature',
                                            'backwpup'
                                        ); ?></label>
                                </th>
                                <td>
                                    <input type="text" name="s3base_signature"
                                           value="<?= !empty(
                                           BackWPup_Option::get(
                                               $jobid,
                                               's3base_signature'
                                           )
                                           ) ? esc_attr(
                                               BackWPup_Option::get($jobid, 's3base_signature')
                                           ) : 'v4' ?>"
                                           placeholder="v4">
                                    <p class="description"><?php esc_html_e(
                                            'The signature for the API like "v4"',
                                            'backwpup'
                                        ); ?></p>
                                </td>
                            </tr>
                            </tbody><!-- advanced section-->
                        </table>
                    </div>
                </td><!-- custom s3 section-->
            </tr>
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
                    if (BackWPup_Option::get($jobid, 's3accesskey')
                        && BackWPup_Option::get($jobid, 's3secretkey')
                    ) {
                        $this->edit_ajax(
                            [
                                's3accesskey' => BackWPup_Option::get($jobid, 's3accesskey'),
                                's3secretkey' => BackWPup_Option::get($jobid, 's3secretkey'),
                                's3bucketselected' => BackWPup_Option::get($jobid, 's3bucket'),
                                's3region' => BackWPup_Option::get($jobid, 's3region'),
                                's3base_url' => BackWPup_Option::get($jobid, 's3base_url'),
                                's3base_region' => BackWPup_Option::get($jobid, 's3base_region'),
                                's3base_multipart' => BackWPup_Option::get(
                                    $jobid,
                                    's3base_multipart'
                                ),
                                's3base_pathstylebucket' => BackWPup_Option::get(
                                    $jobid,
                                    's3base_pathstylebucket'
                                ),
                                's3base_version' => BackWPup_Option::get($jobid, 's3base_version'),
                                's3base_signature' => BackWPup_Option::get(
                                    $jobid,
                                    's3base_signature'
                                ),
                            ]
                        );
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
                           size="63"
					       class="regular-text"
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
		$ajax         = false;

		if ( ! $args ) {
			if ( ! current_user_can( 'backwpup_jobs_edit' ) ) {
				wp_die( - 1 );
			}
            check_ajax_referer('backwpup_ajax_nonce');
            $args = [];
            $args['s3accesskey'] = sanitize_text_field($_POST['s3accesskey']);
            $args['s3secretkey'] = sanitize_text_field($_POST['s3secretkey']);
            $args['s3bucketselected'] = sanitize_text_field($_POST['s3bucketselected']);
            $args['s3region'] = sanitize_text_field($_POST['s3region']);
            $args['s3base_url'] = esc_url_raw($_POST['s3base_url']);
            $args['s3base_region'] = sanitize_text_field($_POST['s3base_region']);
            $args['s3base_multipart'] = sanitize_text_field($_POST['s3base_multipart']);
            $args['s3base_pathstylebucket'] = sanitize_text_field($_POST['s3base_pathstylebucket']);
            $args['s3base_version'] = sanitize_text_field($_POST['s3base_version']);
            $args['s3base_signature'] = sanitize_text_field($_POST['s3base_signature']);
            $ajax = true;
		}

		if ($args['s3base_url']) {
            $args['s3region'] = $args['s3base_url'];
        }

		echo '<span id="s3bucketerror" class="bwu-message-error">';

		if ( ! empty( $args['s3accesskey'] ) && ! empty( $args['s3secretkey'] ) ) {

            if ( empty($args['s3base_url']) ) {
                $aws_destination = BackWPup_S3_Destination::fromOption($args['s3region']);
            }else{
                $options = [
                    'label' => __('Custom S3 destination', 'backwpup'),
                    'endpoint' => $args['s3base_url'],
                    'region' => $args['s3base_region'],
                    'multipart' => !empty($args['s3base_multipart']) ? true : false,
                    'only_path_style_bucket' => !empty($args['s3base_pathstylebucket']) ? true : false,
                    'version' => $args['s3base_version'],
                    'signature' => $args['s3base_signature'],
                ];
                $aws_destination = $this->get_custom_S3_destination_object($options);
            }

			try {
				$s3 = $aws_destination->client($args['s3accesskey'], $args['s3secretkey']);
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
			}

			catch ( Exception $e ) {
			    $error = $e->getMessage();
			    if ( $e instanceof Aws\Exception\AwsException ) {
			       $error = $e->getAwsErrorMessage();
                }
			}
		}

		if ( empty( $args['s3accesskey'] ) ) {
			esc_html_e( 'Missing access key!', 'backwpup' );
		} elseif ( empty( $args['s3secretkey'] ) ) {
			esc_html_e( 'Missing secret access key!', 'backwpup' );
		} elseif ( ! empty( $error ) && $error === 'Access Denied' ) {
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
        BackWPup_Option::update(
            $jobid,
            's3base_region',
            isset($_POST['s3base_region']) ? sanitize_text_field($_POST['s3base_region']) : ''
        );
        BackWPup_Option::update(
            $jobid,
            's3base_multipart',
            isset($_POST['s3base_multipart']) ? '1' : ''
        );
        BackWPup_Option::update(
            $jobid,
            's3base_pathstyle',
            isset($_POST['s3base_pathstyle']) ? '1' : ''
        );
        BackWPup_Option::update(
            $jobid,
            's3base_version',
            isset($_POST['s3base_version']) ? sanitize_text_field(
                $_POST['s3base_version']
            ) : 'latest'
        );
        BackWPup_Option::update(
            $jobid,
            's3base_signature',
            isset($_POST['s3base_signature']) ? sanitize_text_field(
                $_POST['s3base_signature']
            ) : 'v4'
        );

        BackWPup_Option::update($jobid, 's3region', sanitize_text_field($_POST['s3region']));
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
		if (strpos($_POST['s3dir'], '/') === 0) {
			$_POST['s3dir'] = substr( $_POST['s3dir'], 1 );
		}
		if ( $_POST['s3dir'] === '/' ) {
			$_POST['s3dir'] = '';
		}
		BackWPup_Option::update( $jobid, 's3dir', $_POST['s3dir'] );

		BackWPup_Option::update( $jobid,
			's3maxbackups',
			! empty( $_POST['s3maxbackups'] ) ? absint( $_POST['s3maxbackups'] ) : 0 );
		BackWPup_Option::update( $jobid, 's3syncnodelete', ! empty( $_POST['s3syncnodelete'] ) );

		//create new bucket
		if ( ! empty( $_POST['s3newbucket'] ) ) {
			try {
			    $region = BackWPup_Option::get($jobid, 's3base_url');
			    if (empty($region) ) {
			        $region = BackWPup_Option::get($jobid, 's3region');
                    $aws_destination = BackWPup_S3_Destination::fromOption($region);
                }else{
                    $aws_destination = $this->get_custom_S3_destination_object($jobid);
                }

			    $s3 = $aws_destination->client(
                    BackWPup_Option::get($jobid, 's3accesskey'),
                    BackWPup_Option::get($jobid, 's3secretkey')
                );
                $s3->createBucket(
                    array(
                        'Bucket'             => sanitize_text_field( $_POST['s3newbucket'] ),
                        'PathStyle'          => $aws_destination->onlyPathStyleBucket(),
                        'LocationConstraint' => $aws_destination->region(),
                    )
                );
                BackWPup_Admin::message(
                    sprintf( __( 'Bucket %1$s created.', 'backwpup' ),
                    sanitize_text_field( $_POST['s3newbucket'] ) )
                );
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
			    $region = BackWPup_Option::get($jobid, 's3base_url');
			    if (empty($region) ) {
			        $region = BackWPup_Option::get($jobid, 's3region');
                    $aws_destination = BackWPup_S3_Destination::fromOption($region);
                }else{
                    $aws_destination = $this->get_custom_S3_destination_object($jobid);
                }

				$s3 = $aws_destination->client(
                    BackWPup_Option::get($jobid, 's3accesskey'),
                    BackWPup_Option::get($jobid, 's3secretkey')
                );

				$s3->deleteObject( array(
					'Bucket' => BackWPup_Option::get( $jobid, 's3bucket' ),
					'Key'    => $backupfile,
				) );
				//update file list
				foreach ( (array) $files as $key => $file ) {
					if ( is_array( $file ) && $file['file'] === $backupfile ) {
						unset( $files[ $key ] );
					}
				}
				unset( $s3 );
			} catch ( Exception $e ) {
			    $errorMessage = $e->getMessage();
			    if ( $e instanceof Aws\Exception\AwsException ) {
			       $errorMessage = $e->getAwsErrorMessage();
                }
				BackWPup_Admin::message( sprintf( __( 'S3 Service API: %s', 'backwpup' ), $errorMessage ), true );
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

		$downloader = new BackWpup_Download_Handler(
			new BackWPup_Download_File(
				$filename,
				function ( \BackWPup_Download_File_Interface $obj ) use ( $filename, $file_path, $job_id ) {

					$factory = new BackWPup_Destination_Downloader_Factory();
					$downloader = $factory->create(
						'S3',
						$job_id,
						$file_path,
						$filename,
						BackWPup_Option::get( $job_id, 's3base_url' )
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

        if ( empty($job_object->job['s3base_url']) ) {
            $aws_destination = BackWPup_S3_Destination::fromOption($job_object->job['s3region']);
        }else{
            $aws_destination = $this->get_custom_S3_destination_object($job_object->job['jobid']);
        }
        $s3 = $aws_destination->client(
            BackWPup_Option::get($jobid, 's3accesskey'),
            BackWPup_Option::get($jobid, 's3secretkey')
        );

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

				if ( $this->is_backup_archive( $file ) && $this->is_backup_owned_by_job( $file, $jobid ) ) {
					$backupfilelist[ $changetime ] = $file;
				}

				$files[ $filecounter ]['folder']   = $s3->getObjectUrl(BackWPup_Option::get( $jobid, 's3bucket' ), dirname( $object['Key'] ) );
				$files[ $filecounter ]['file']     = $object['Key'];
				$files[ $filecounter ]['filename'] = basename( $object['Key'] );

				if ( ! empty( $object['StorageClass'] ) ) {
					$files[ $filecounter ]['info'] = sprintf( __( 'Storage Class: %s', 'backwpup' ),
						$object['StorageClass'] );
				}

				$files[ $filecounter ]['downloadurl'] = network_admin_url( 'admin.php' ) . '?page=backwpupbackups&action=downloads3&file=' . $object['Key'] . '&local_file=' . basename( $object['Key'] ) . '&jobid=' . $jobid;
				$files[ $filecounter ]['filesize']    = (int)$object['Size'];
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
								$s3->getObjectUrl($job_object->job['s3bucket'],$job_object->job['s3dir'] . $file )),
							E_USER_ERROR
						);
					}
				}


				if ( $numdeltefiles > 0 ) {
					$job_object->log( sprintf( _n( 'One file deleted on S3 Bucket.',
						'%d files deleted on S3 Bucket',
						$numdeltefiles,
						'backwpup' ),
						$numdeltefiles ) );
				}
			}
		}
		set_site_transient( 'backwpup_' . $jobid . '_s3', $files, YEAR_IN_SECONDS );

	}

	/**
	 * @param $job_object BackWPup_Job
	 *
	 * @return bool
	 */
	public function job_run_archive( BackWPup_Job $job_object ) {

		$job_object->substeps_todo = 2 + $job_object->backup_filesize;

		if ( $job_object->steps_data[ $job_object->step_working ]['SAVE_STEP_TRY'] != $job_object->steps_data[ $job_object->step_working ]['STEP_TRY'] ) {
			$job_object->log(
				sprintf(
					__( '%d. Trying to send backup file to S3 Service&#160;&hellip;', 'backwpup' ),
					$job_object->steps_data[ $job_object->step_working ]['STEP_TRY']
				)
			);
		}

		try {
            if ( empty($job_object->job['s3base_url']) ) {
                $aws_destination = BackWPup_S3_Destination::fromOption($job_object->job['s3region']);
            }else{
                $aws_destination = $this->get_custom_S3_destination_object($job_object->job['jobid']);
            }

            $s3 = $aws_destination->client(
                $job_object->job['s3accesskey'],
                $job_object->job['s3secretkey']
            );

			if ( $job_object->steps_data[ $job_object->step_working ]['SAVE_STEP_TRY'] != $job_object->steps_data[ $job_object->step_working ]['STEP_TRY'] && $job_object->substeps_done < $job_object->backup_filesize ) {
				if ( $s3->doesBucketExist( $job_object->job['s3bucket'] ) ) {
					$bucketregion = $s3->getBucketLocation( array( 'Bucket' => $job_object->job['s3bucket'] ) );
					$job_object->log( sprintf( __( 'Connected to S3 Bucket "%1$s" in %2$s', 'backwpup' ),
						$job_object->job['s3bucket'],
						$bucketregion->get( 'LocationConstraint' ) )
                    );
				} else {
					$job_object->log( sprintf( __( 'S3 Bucket "%s" does not exist!'  , 'backwpup' ),
						$job_object->job['s3bucket'] ),E_USER_ERROR );
					return true;
				}

				if ( $aws_destination->supportsMultipart() && empty( $job_object->steps_data[ $job_object->step_working ]['UploadId'] ) ) {
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


			if ( ! $aws_destination->supportsMultipart() || $job_object->backup_filesize < 1048576 * 6 ) {
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
				$create_args['ContentType'] = MimeTypeExtractor::fromFilePath( $job_object->backup_folder . $job_object->backup_file );

				try {
					$s3->putObject( $create_args );
				} catch ( Exception $e ) {
				    $errorMessage = $e->getMessage();
                    if ( $e instanceof Aws\Exception\AwsException ) {
                       $errorMessage = $e->getAwsErrorMessage();
                    }
					$job_object->log( E_USER_ERROR,
						sprintf( __( 'S3 Service API: %s', 'backwpup' ), $errorMessage ),
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
								'ContentType' => MimeTypeExtractor::fromFilePath( $job_object->backup_folder . $job_object->backup_file ),
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
							$chunk_upload_start  = microtime( true );
							$part_data  = fread( $file_handle, 1048576 * 5 ); //5MB Minimum part size
							$part = $s3->uploadPart( array(
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

						$parts = $s3->listParts(array(
                            'Bucket' => $job_object->job['s3bucket'],
                            'Key'       => $job_object->job['s3dir'] . $job_object->backup_file,
                            'UploadId'  => $job_object->steps_data[ $job_object->step_working ]['UploadId']
                        ));

						$s3->completeMultipartUpload( array(
							'Bucket'   => $job_object->job['s3bucket'],
							'UploadId' => $job_object->steps_data[ $job_object->step_working ]['UploadId'],
							'MultipartUpload' => array(
                                'Parts' => $parts['Parts'],
                            ),
							'Key'      => $job_object->job['s3dir'] . $job_object->backup_file,
						) );

					} catch ( Exception $e ) {
					    $errorMessage = $e->getMessage();
                        if ( $e instanceof Aws\Exception\AwsException ) {
                           $errorMessage = $e->getAwsErrorMessage();
                        }
						$job_object->log( E_USER_ERROR,
							sprintf( __( 'S3 Service API: %s', 'backwpup' ), $errorMessage ),
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
						$s3->getObjectUrl($job_object->job['s3bucket'], $job_object->job['s3dir'] . $job_object->backup_file ) )
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
            $errorMessage = $e->getMessage();
            if ( $e instanceof Aws\Exception\AwsException ) {
               $errorMessage = $e->getAwsErrorMessage();
            }
			$job_object->log( E_USER_ERROR,
				sprintf( __( 'S3 Service API: %s', 'backwpup' ), $errorMessage ),
				$e->getFile(),
				$e->getLine() );

			return false;
		}

		try {
			$this->file_update_list( $job_object, true );
		} catch ( Exception $e ) {
		    $errorMessage = $e->getMessage();
            if ( $e instanceof Aws\Exception\AwsException ) {
               $errorMessage = $e->getAwsErrorMessage();
            }
			$job_object->log( E_USER_ERROR,
				sprintf( __( 'S3 Service API: %s', 'backwpup' ), $errorMessage ),
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

				$( 'select[name="s3region"]' ).change( function () {
					awsgetbucket();
				} );
				$( 'input[name="s3accesskey"], input[name="s3secretkey"], input[name="s3base_url"]' ).backwpupDelayKeyup( function () {
					awsgetbucket();
				} );

			} );
		</script>
		<?php
	}

    /**
     * Get BackWPup_S3_Destination object for custom s3
     * @param $jobIdOrOptionArr
     * @return BackWPup_S3_Destination
     */
    public function get_custom_S3_destination_object($jobIdOrOptionArr)
    {

        $options = !is_array($jobIdOrOptionArr) ? [
            'label' => __('Custom S3 destination', 'backwpup'),
            'endpoint' => BackWPup_Option::get($jobIdOrOptionArr, 's3base_url'),
            'region' => BackWPup_Option::get($jobIdOrOptionArr, 's3base_region'),
            'multipart' => !empty(BackWPup_Option::get($jobIdOrOptionArr, 's3base_multipart')) ? true : false,
            'only_path_style_bucket' => !empty(
            BackWPup_Option::get(
                $jobIdOrOptionArr,
                's3base_pathstylebucket'
            )
            ) ? true : false,
            'version' => BackWPup_Option::get($jobIdOrOptionArr, 's3base_version'),
            'signature' => BackWPup_Option::get($jobIdOrOptionArr, 's3base_signature'),
        ] : $jobIdOrOptionArr;

        return BackWPup_S3_Destination::fromOptionArray($options);
    }
}
