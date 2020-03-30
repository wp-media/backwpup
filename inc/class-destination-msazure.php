<?php

use Inpsyde\BackWPup\MsAzureDestinationConfiguration;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Blob\Models\Blob;
use MicrosoftAzure\Storage\Blob\Models\CreateContainerOptions;
use MicrosoftAzure\Storage\Blob\Models\PublicAccessType;

/**
 * Documentation: http://www.windowsazure.com/en-us/develop/php/how-to-guides/blob-service/
 */
class BackWPup_Destination_MSAzure extends BackWPup_Destinations {

    const MSAZUREDIR = 'msazuredir';
    const MSAZUREMAXBACKUPS = 'msazuremaxbackups';
    const MSAZURESYNCNODELETE = 'msazuresyncnodelete';
    const NEWMSAZURECONTAINER = 'newmsazurecontainer';

    /**
	 * @return array
	 */
	public function option_defaults() {

		return array( MsAzureDestinationConfiguration::MSAZURE_ACCNAME => '', MsAzureDestinationConfiguration::MSAZURE_KEY => '', MsAzureDestinationConfiguration::MSAZURE_CONTAINER => '', self::MSAZUREDIR => trailingslashit( sanitize_file_name( get_bloginfo( 'name' ) ) ), self::MSAZUREMAXBACKUPS => 15, self::MSAZURESYNCNODELETE => TRUE );
	}

	/**
	 * @param $jobid
	 */
	public function edit_tab( $jobid ) {
		?>
		<h3 class="title"><?php esc_html_e( 'MS Azure access keys', 'backwpup' ); ?></h3>
		<p></p>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="msazureaccname"><?php esc_html_e( 'Account name', 'backwpup' ); ?></label></th>
				<td>
					<input id="msazureaccname" name="msazureaccname" type="text"
						   value="<?php echo esc_attr( BackWPup_Option::get( $jobid, MsAzureDestinationConfiguration::MSAZURE_ACCNAME ) );?>" class="regular-text" autocomplete="off" />
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="msazurekey"><?php esc_html_e( 'Access key', 'backwpup' ); ?></label></th>
				<td>
					<input id="msazurekey" name="msazurekey" type="password"
						   value="<?php echo esc_attr( BackWPup_Encryption::decrypt( BackWPup_Option::get( $jobid, MsAzureDestinationConfiguration::MSAZURE_KEY ) ) );?>" class="regular-text" autocomplete="off" />
				</td>
			</tr>
		</table>

		<h3 class="title"><?php esc_html_e( 'Blob container', 'backwpup' ); ?></h3>
		<p></p>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="msazurecontainerselected"><?php esc_html_e( 'Container selection', 'backwpup' ); ?></label></th>
				<td>
					<input id="msazurecontainerselected" name="msazurecontainerselected" type="hidden" value="<?php echo esc_attr( BackWPup_Option::get( $jobid, MsAzureDestinationConfiguration::MSAZURE_CONTAINER ) );?>" />
					<?php if ( BackWPup_Option::get( $jobid, MsAzureDestinationConfiguration::MSAZURE_ACCNAME ) && BackWPup_Option::get( $jobid, MsAzureDestinationConfiguration::MSAZURE_KEY ) ) $this->edit_ajax( array(
																																						 MsAzureDestinationConfiguration::MSAZURE_ACCNAME  => BackWPup_Option::get( $jobid, MsAzureDestinationConfiguration::MSAZURE_ACCNAME ),
																																						 MsAzureDestinationConfiguration::MSAZURE_KEY      => BackWPup_Encryption::decrypt( BackWPup_Option::get( $jobid, MsAzureDestinationConfiguration::MSAZURE_KEY ) ),
																																						 'msazureselected' => BackWPup_Option::get( $jobid, MsAzureDestinationConfiguration::MSAZURE_CONTAINER )
																																					) ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="newmsazurecontainer"><?php esc_html_e( 'Create a new container', 'backwpup' ); ?></label></th>
				<td>
					<input id="newmsazurecontainer" name="newmsazurecontainer" type="text" value="" class="regular-text" autocomplete="off" />
				</td>
			</tr>
		</table>

		<h3 class="title"><?php esc_html_e( 'Backup settings', 'backwpup' ); ?></h3>
		<p></p>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="idmsazuredir"><?php esc_html_e( 'Folder in container', 'backwpup' ); ?></label></th>
				<td>
					<input id="idmsazuredir" name="msazuredir" type="text" value="<?php echo esc_attr( BackWPup_Option::get( $jobid,
                        self::MSAZUREDIR
                    ) ); ?>" class="regular-text" />
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'File deletion', 'backwpup' ); ?></th>
				<td>
					<?php
					if ( BackWPup_Option::get( $jobid, 'backuptype' ) === 'archive' ) {
						?>
						<label for="idmsazuremaxbackups">
							<input id="idmsazuremaxbackups" name="msazuremaxbackups" type="number" min="0" step="1" value="<?php echo esc_attr( BackWPup_Option::get( $jobid,
                                self::MSAZUREMAXBACKUPS
                            ) ); ?>" class="small-text" />
							&nbsp;<?php esc_html_e( 'Number of files to keep in folder.', 'backwpup' ); ?>
						</label>
						<p><?php _e( '<strong>Warning</strong>: Files belonging to this job are now tracked. Old backup archives which are untracked will not be automatically deleted.', 'backwpup' ) ?></p>
					<?php } else { ?>
						<label for="idmsazuresyncnodelete">
							<input class="checkbox" value="1" type="checkbox" <?php checked( BackWPup_Option::get( $jobid,
                                self::MSAZURESYNCNODELETE
                            ), true ); ?> name="msazuresyncnodelete" id="idmsazuresyncnodelete" />
							&nbsp;<?php esc_html_e( 'Do not delete files while syncing to destination!', 'backwpup' ); ?>
						</label>
					<?php } ?>
				</td>
			</tr>
		</table>
		<?php
	}

    /**
     * @param int $jobid
     * @return void
     */
	public function edit_form_post_save( $jobid ) {

        try {
            $msazureConfiguration = $this->msazureConfiguration();
        } catch (\UnexpectedValueException $exception) {
            BackWPup_Admin::message(__('Microsoft Azure Configuration: ', 'backwpup') . $exception->getMessage(), true);
            return;
        }

        BackWPup_Option::update(
            $jobid,
            MsAzureDestinationConfiguration::MSAZURE_ACCNAME,
            $msazureConfiguration->msazureaccname()
        );
        BackWPup_Option::update(
            $jobid,
            MsAzureDestinationConfiguration::MSAZURE_KEY,
            $msazureConfiguration->msazurekey()
        );
        BackWPup_Option::update(
            $jobid,
            MsAzureDestinationConfiguration::MSAZURE_CONTAINER,
            $msazureConfiguration->msazurecontainer()
        );

        $msazureDir = $this->msazureDir();

        BackWPup_Option::update($jobid, self::MSAZUREDIR, $msazureDir);

        BackWPup_Option::update(
            $jobid,
            self::MSAZUREMAXBACKUPS,
            filter_input(INPUT_POST, self::MSAZUREMAXBACKUPS, FILTER_SANITIZE_NUMBER_INT) ?: 0
        );

        BackWPup_Option::update(
            $jobid,
            self::MSAZURESYNCNODELETE,
            filter_input(INPUT_POST, self::MSAZURESYNCNODELETE, FILTER_SANITIZE_STRING) ?: ''
        );

        $newmsazurecontainer = filter_input(
            INPUT_POST,
            self::NEWMSAZURECONTAINER,
            FILTER_SANITIZE_STRING
        );

        if ($newmsazurecontainer) {
			try {
                $this->createContainer(
                    $newmsazurecontainer,
                    $msazureConfiguration
                );

                BackWPup_Admin::message(
                    sprintf(
                        __('MS Azure container "%s" created.', 'backwpup'),
                        esc_html(sanitize_text_field($newmsazurecontainer))
                    )
                );
            } catch ( Exception $e ) {
				BackWPup_Admin::message( sprintf( __( 'MS Azure container create: %s', 'backwpup' ), $e->getMessage() ), TRUE );
			    return;
			}

            BackWPup_Option::update(
                $jobid,
                MsAzureDestinationConfiguration::MSAZURE_CONTAINER,
                sanitize_text_field($newmsazurecontainer)
            );
        }
	}

	/**
	 * @param $jobdest
	 * @param $backupfile
	 */
	public function file_delete( $jobdest, $backupfile ) {

		$files = get_site_transient( 'backwpup_'. strtolower( $jobdest ) );
		list( $jobid, $dest ) = explode( '_', $jobdest );

        if (BackWPup_Option::get($jobid, MsAzureDestinationConfiguration::MSAZURE_ACCNAME)
            && BackWPup_Option::get($jobid, MsAzureDestinationConfiguration::MSAZURE_KEY)
            && BackWPup_Option::get($jobid, MsAzureDestinationConfiguration::MSAZURE_CONTAINER)) {
			try {
                $blobClient = $this->createBlobClient(
                    BackWPup_Option::get($jobid, MsAzureDestinationConfiguration::MSAZURE_ACCNAME),
                    BackWPup_Encryption::decrypt(
                        BackWPup_Option::get($jobid, MsAzureDestinationConfiguration::MSAZURE_KEY)
                    )
                );

                $this->deleteBlob(
                    $blobClient,
                    BackWPup_Option::get(
                        $jobid,
                        MsAzureDestinationConfiguration::MSAZURE_CONTAINER
                    ),
                    $backupfile
                );

                //update file list
				foreach ( $files as $key => $file ) {
					if ( is_array( $file ) && $file[ 'file' ] == $backupfile )
						unset( $files[ $key ] );
				}
			}
			catch ( Exception $e ) {
				BackWPup_Admin::message( 'MS AZURE: ' . $e->getMessage(), TRUE );
			}
		}

		set_site_transient( 'backwpup_' . strtolower( $jobdest ), $files, YEAR_IN_SECONDS );
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
	 * @param $job_object
	 * @return bool
	 */
	public function job_run_archive( BackWPup_Job $job_object ) {

		$job_object->substeps_todo = $job_object->backup_filesize + 2;

		if ( $job_object->steps_data[ $job_object->step_working ]['SAVE_STEP_TRY'] != $job_object->steps_data[ $job_object->step_working ][ 'STEP_TRY' ] )
			$job_object->log( sprintf( __( '%d. Try sending backup to a Microsoft Azure (Blob)&#160;&hellip;', 'backwpup' ), $job_object->steps_data[ $job_object->step_working ][ 'STEP_TRY' ] ), E_USER_NOTICE );

		try {
            $blobRestProxy = $this->createBlobClient(
                $job_object->job[MsAzureDestinationConfiguration::MSAZURE_ACCNAME],
                BackWPup_Encryption::decrypt($job_object->job[MsAzureDestinationConfiguration::MSAZURE_KEY])
            );

			if ( $job_object->steps_data[ $job_object->step_working ]['SAVE_STEP_TRY'] != $job_object->steps_data[ $job_object->step_working ][ 'STEP_TRY' ] ) {

				//test vor existing container
                $containers = $this->getContainers($blobRestProxy);

				$job_object->steps_data[ $job_object->step_working ][ 'container_url' ] = '';
				foreach( $containers as $container ) {
					if ( $container->getName() == $job_object->job[ MsAzureDestinationConfiguration::MSAZURE_CONTAINER ] ) {
						$job_object->steps_data[ $job_object->step_working ][ 'container_url' ] = $container->getUrl();
						break;
					}
				}

				if ( ! $job_object->steps_data[ $job_object->step_working ][ 'container_url' ] ) {
					$job_object->log( sprintf( __( 'MS Azure container "%s" does not exist!', 'backwpup'), $job_object->job[ MsAzureDestinationConfiguration::MSAZURE_CONTAINER ] ), E_USER_ERROR );

					return TRUE;
				} else {
					$job_object->log( sprintf( __( 'Connected to MS Azure container "%s".', 'backwpup'), $job_object->job[ MsAzureDestinationConfiguration::MSAZURE_CONTAINER ] ), E_USER_NOTICE );
				}

				$job_object->log( __( 'Starting upload to MS Azure&#160;&hellip;', 'backwpup' ), E_USER_NOTICE );
			}

			//Prepare Upload
			if ( $file_handel = fopen( $job_object->backup_folder . $job_object->backup_file, 'rb' ) ) {
				fseek( $file_handel, $job_object->substeps_done );

				if ( empty( $job_object->steps_data[ $job_object->step_working ][ 'BlockList' ] ) ) {
					$job_object->steps_data[ $job_object->step_working ][ 'BlockList' ] = array();
				}

				while ( ! feof( $file_handel ) ) {
					$data = fread( $file_handel, 1048576 * 4 ); //4MB
					if ( strlen( $data ) == 0 ) {
						continue;
					}
					$chunk_upload_start = microtime( TRUE );
					$block_count = count( $job_object->steps_data[ $job_object->step_working ][ 'BlockList' ] ) + 1;
                    $block_id = base64_encode(str_pad($block_count, 6, "0", STR_PAD_LEFT));

                    $blobRestProxy->createBlobBlock(
                        $job_object->job[MsAzureDestinationConfiguration::MSAZURE_CONTAINER],
                        $job_object->job[self::MSAZUREDIR] . $job_object->backup_file,
                        $block_id,
                        $data
                    );

					$job_object->steps_data[ $job_object->step_working ][ 'BlockList' ][] = $block_id;
					$chunk_upload_time = microtime( TRUE ) - $chunk_upload_start;
					$job_object->substeps_done = $job_object->substeps_done + strlen( $data );
					$time_remaining = $job_object->do_restart_time();
					if ( $time_remaining < $chunk_upload_time ) {
						$job_object->do_restart_time( TRUE );
					}
					$job_object->update_working_data();
				}
				fclose( $file_handel );
			} else {
				$job_object->log( __( 'Can not open source file for transfer.', 'backwpup' ), E_USER_ERROR );
				return FALSE;
			}

            $blocklist = $this->createBlockList();

            foreach( $job_object->steps_data[ $job_object->step_working ][ 'BlockList' ] as $block_id ) {
				$blocklist->addUncommittedEntry( $block_id );
			}
			unset( $job_object->steps_data[ $job_object->step_working ][ 'BlockList' ] );

			//Commit Blocks
			$blobRestProxy->commitBlobBlocks( $job_object->job[ MsAzureDestinationConfiguration::MSAZURE_CONTAINER ], $job_object->job[self::MSAZUREDIR] . $job_object->backup_file, $blocklist->getEntries() );

			$job_object->substeps_done ++;
			$job_object->log( sprintf( __( 'Backup transferred to %s', 'backwpup' ), $job_object->steps_data[ $job_object->step_working ][ 'container_url' ] . '/' . $job_object->job[self::MSAZUREDIR] . $job_object->backup_file ), E_USER_NOTICE );
			if ( !empty( $job_object->job[ 'jobid' ] ) ) {
				BackWPup_Option::update( $job_object->job[ 'jobid' ] , 'lastbackupdownloadurl', network_admin_url( 'admin.php' ) . '?page=backwpupbackups&action=downloadmsazure&file=' . $job_object->job[self::MSAZUREDIR] . $job_object->backup_file . '&jobid=' . $job_object->job[ 'jobid' ] );
			}
		}
		catch ( Exception $e ) {
			$job_object->log( E_USER_ERROR, sprintf( __( 'Microsoft Azure API: %s', 'backwpup' ), $e->getMessage() ), $e->getFile(), $e->getLine() );
			$job_object->substeps_done = 0;
			unset( $job_object->steps_data[ $job_object->step_working ][ 'BlockList' ] );
			if ( isset( $file_handel ) && is_resource( $file_handel ) )
				fclose( $file_handel );

			return FALSE;
		}


		try {

			$backupfilelist = array();
			$filecounter    = 0;
			$files          = array();

            $blob_options = $this->createListBlobsOptions();
            $blob_options->setPrefix($job_object->job[self::MSAZUREDIR]);

            $blobs = $this->getBlobs(
                $blobRestProxy,
                $job_object->job[MsAzureDestinationConfiguration::MSAZURE_CONTAINER],
                $blob_options
            );

			if ( is_array( $blobs ) ) {
				foreach ( $blobs as $blob ) {
					$file = basename( $blob->getName() );
					if ( $this->is_backup_archive( $file ) && $this->is_backup_owned_by_job( $file, $job_object->job['jobid'] ) == true )
						$backupfilelist[ $blob->getProperties()->getLastModified()->getTimestamp() ] = $file;
					$files[ $filecounter ][ 'folder' ]      = $job_object->steps_data[ $job_object->step_working ][ 'container_url' ] . "/" . dirname( $blob->getName() ) . "/";
					$files[ $filecounter ][ 'file' ]        = $blob->getName();
					$files[ $filecounter ][ 'filename' ]    = basename( $blob->getName() );
					$files[ $filecounter ][ 'downloadurl' ] = network_admin_url( 'admin.php' ) . '?page=backwpupbackups&action=downloadmsazure&file=' . $blob->getName() . '&jobid=' . $job_object->job[ 'jobid' ];
					$files[ $filecounter ][ 'filesize' ]    = $blob->getProperties()->getContentLength();
					$files[ $filecounter ][ 'time' ]        = $blob->getProperties()->getLastModified()->getTimestamp()  + ( get_option( 'gmt_offset' ) * 3600 );
					$filecounter ++;
				}
			}
			// Delete old backups
			if ( ! empty ($job_object->job[self::MSAZUREMAXBACKUPS] ) && $job_object->job[self::MSAZUREMAXBACKUPS] > 0 ) {
				if ( count( $backupfilelist ) > $job_object->job[self::MSAZUREMAXBACKUPS] ) {
					ksort( $backupfilelist );
					$numdeltefiles = 0;
					while ( $file = array_shift( $backupfilelist ) ) {
						if ( count( $backupfilelist ) < $job_object->job[self::MSAZUREMAXBACKUPS] )
							break;
						$blobRestProxy->deleteBlob( $job_object->job[ MsAzureDestinationConfiguration::MSAZURE_CONTAINER ], $job_object->job[self::MSAZUREDIR] . $file );
						foreach ( $files as $key => $filedata ) {
							if ( $filedata[ 'file' ] == $job_object->job[self::MSAZUREDIR] . $file )
								unset( $files[ $key ] );
						}
						$numdeltefiles ++;
					}
					if ( $numdeltefiles > 0 )
						$job_object->log( sprintf( _n( 'One file deleted on Microsoft Azure container.', '%d files deleted on Microsoft Azure container.', $numdeltefiles, 'backwpup' ), $numdeltefiles ), E_USER_NOTICE );

				}
			}
			set_site_transient( 'backwpup_' . $job_object->job[ 'jobid' ] . '_msazure', $files, YEAR_IN_SECONDS );
		}
		catch ( Exception $e ) {
			$job_object->log( E_USER_ERROR, sprintf( __( 'Microsoft Azure API: %s', 'backwpup' ), $e->getMessage() ), $e->getFile(), $e->getLine() );

			return FALSE;
		}

		$job_object->substeps_done = $job_object->backup_filesize + 2;

		return TRUE;
	}

	/**
	 * @param $job_settings array
	 * @return bool
	 */
	public function can_run( array $job_settings ) {

		if ( empty( $job_settings[ MsAzureDestinationConfiguration::MSAZURE_ACCNAME ] ) )
			return FALSE;

		if ( empty( $job_settings[ MsAzureDestinationConfiguration::MSAZURE_KEY ]) )
			return FALSE;

		if ( empty( $job_settings[ MsAzureDestinationConfiguration::MSAZURE_CONTAINER ] ) )
			return FALSE;

		return TRUE;
	}

	/**
	 *
	 */
	public function edit_inline_js() {
		?>
		<script type="text/javascript">
			jQuery(document).ready(function ($) {
				function msazuregetcontainer() {
					var data = {
						action: 'backwpup_dest_msazure',
						msazureaccname: $('#msazureaccname').val(),
						msazurekey: $('#msazurekey').val(),
						msazureselected: $('#msazurecontainerselected').val(),
						_ajax_nonce: $('#backwpupajaxnonce').val()
					};
					$.post(ajaxurl, data, function (response) {
						$('#msazurecontainererror').remove();
						$('#msazurecontainer').remove();
						$('#msazurecontainerselected').after(response);
					});
				}

				$('#msazureaccname').backwpupDelayKeyup(function () {
					msazuregetcontainer();
				});
				$('#msazurekey').backwpupDelayKeyup(function () {
					msazuregetcontainer();
				});
			});
		</script>
	<?php
	}

	/**
	 * @param array $args
	 */
	public function edit_ajax( $args = array() ) {

		$error = '';
		$ajax = FALSE;

        $msazureName = filter_input(
            INPUT_POST,
            MsAzureDestinationConfiguration::MSAZURE_ACCNAME,
            FILTER_SANITIZE_STRING
        ) ?: '';
        $msazureKey = filter_input(
            INPUT_POST,
            MsAzureDestinationConfiguration::MSAZURE_KEY,
            FILTER_SANITIZE_STRING
        ) ?: '';
        $msazureSelected = filter_input(
            INPUT_POST,
            'msazureselected',
            FILTER_SANITIZE_STRING
        ) ?: '';

        if ($msazureName || $msazureKey) {
            if (!current_user_can('backwpup_jobs_edit')) {
                wp_die(-1);
            }
            check_ajax_referer('backwpup_ajax_nonce');
            $args[MsAzureDestinationConfiguration::MSAZURE_ACCNAME] = $msazureName;
            $args[MsAzureDestinationConfiguration::MSAZURE_KEY] = $msazureKey;
            $args['msazureselected'] = $msazureSelected;
            $ajax = true;
		}
		echo '<span id="msazurecontainererror" class="bwu-message-error">';

		if ( ! empty( $args[ MsAzureDestinationConfiguration::MSAZURE_ACCNAME ] ) && ! empty( $args[ MsAzureDestinationConfiguration::MSAZURE_KEY ] ) ) {
			try {
                $blobClient = $this->createBlobClient(
                    $args[MsAzureDestinationConfiguration::MSAZURE_ACCNAME],
                    BackWPup_Encryption::decrypt($args[MsAzureDestinationConfiguration::MSAZURE_KEY])
                );

                $containers = $blobClient->listContainers()->getContainers();
			}
			catch ( Exception $e ) {
				$error = $e->getMessage();
			}
		}

		if ( empty( $args[ MsAzureDestinationConfiguration::MSAZURE_ACCNAME ] ) )
			_e( 'Missing account name!', 'backwpup' );
		elseif ( empty( $args[ MsAzureDestinationConfiguration::MSAZURE_KEY ] ) )
			_e( 'Missing access key!', 'backwpup' );
		elseif ( ! empty( $error ) )
			echo esc_html( $error );
		elseif ( empty( $containers ) )
			_e( 'No container found!', 'backwpup' );
		echo '</span>';

		if ( !empty( $containers ) ) {
			echo '<select name="msazurecontainer" id="msazurecontainer">';
			foreach ( $containers as $container ) {
				echo "<option " . selected( strtolower( $args[ 'msazureselected' ] ), strtolower( $container->getName() ), FALSE ) . ">" . esc_html( $container->getName() ) . "</option>";
			}
			echo '</select>';
		}
		if ( $ajax )
			die();
		else
			return;
	}

    /**
     * Creates the service used to access the blob.
     * @param string $accountName
     * @param string $accountKey
     * @return BlobRestProxy
     */
    public function createBlobClient($accountName, $accountKey)
    {
        $connectionString = 'DefaultEndpointsProtocol=https;AccountName='
            . $accountName . ';AccountKey=' . $accountKey;

        return BlobRestProxy::createBlobService($connectionString);
    }

    /**
     * @return MsAzureDestinationConfiguration
     */
    protected function msazureConfiguration()
    {
        $msazureaccname = filter_input(INPUT_POST, MsAzureDestinationConfiguration::MSAZURE_ACCNAME, FILTER_SANITIZE_STRING);
        $msazurekey = filter_input(INPUT_POST, MsAzureDestinationConfiguration::MSAZURE_KEY, FILTER_SANITIZE_STRING);
        $msazurecontainer = filter_input(
            INPUT_POST,
            MsAzureDestinationConfiguration::MSAZURE_CONTAINER,
            FILTER_SANITIZE_STRING
        );

        return new MsAzureDestinationConfiguration(
            $msazureaccname,
            $msazurekey,
            $msazurecontainer
        );
    }

    /**
     * @param string $name
     * @param MsAzureDestinationConfiguration $configuration
     */
    protected function createContainer(
        $name,
        $configuration
    ) {
        $blobClient = $this->createBlobClient(
            $configuration->msazureaccname(),
            $configuration->msazurekey()
        );

        $createContainerOptions = $this->createContainerOptionsFactory();
        $createContainerOptions->setPublicAccess(PublicAccessType::NONE);

        $blobClient->createContainer(
            $name,
            $createContainerOptions
        );
    }

    protected function createContainerOptionsFactory()
    {
        return new CreateContainerOptions();
    }

    /**
     * @param BlobRestProxy $blobClient
     * @param string $container
     * @param string $backupfile
     * @return void
     */
    protected function deleteBlob($blobClient, $container, $backupfile)
    {
        $blobClient->deleteBlob(
            $container,
            $backupfile
        );
    }

    /**
     * @param BlobRestProxy $blobClient
     * @param string $container
     * @param \MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions $options
     * @return Blob[]
     */
    protected function getBlobs($blobClient, $container, $options)
    {
        return $blobClient->listBlobs(
            $container,
            $options
        )->getBlobs();
    }

    /**
     * @param BlobRestProxy $blobClient
     * @return \MicrosoftAzure\Storage\Blob\Models\Container[]
     */
    protected function getContainers($blobClient)
    {
        return $blobClient->listContainers()->getContainers();
    }

    /**
     * @return \MicrosoftAzure\Storage\Blob\Models\BlockList
     */
    protected function createBlockList()
    {
        $blocklist = new MicrosoftAzure\Storage\Blob\Models\BlockList();

        return $blocklist;
    }

    /**
     * @return \MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions
     */
    protected function createListBlobsOptions()
    {
        $blob_options = new MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions();

        return $blob_options;
    }

    /**
     * @return false|string
     */
    protected function msazureDir()
    {
        $msazureDir = trailingslashit(
            str_replace(
                '//',
                '/',
                str_replace(
                    '\\',
                    '/',
                    trim(
                        filter_input(INPUT_POST, self::MSAZUREDIR, FILTER_SANITIZE_STRING) ?: ''
                    )
                )
            )
        );

        if (substr($msazureDir, 0, 1) == '/') {
            $msazureDir = substr($msazureDir, 1);
        }

        if ($msazureDir == '/') {
            $msazureDir = '';
        }

        return $msazureDir;
    }

    /**
     * It extracts the job id from a job destination string.
     * @param string $jobDestination String containing a job destination, ex. 1_SOME_DESTINATION.
     * @return int
     * @throws RuntimeException
     */
    protected function extractJobIdFromDestination($jobDestination)
    {
        $jobId = intval(substr($jobDestination, 0, strpos($jobDestination, '_', 1)));

        if (!$jobId || $jobId === 0) {
            throw new RuntimeException(
                sprintf(__('Could not extract job id from destination %s.', 'backwpup'), $jobDestination)
            );
        }

        return $jobId;
    }
}
