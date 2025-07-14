<?php

use Inpsyde\BackWPup\MsAzureDestinationConfiguration;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Blob\Models\Blob;
use MicrosoftAzure\Storage\Blob\Models\BlockList;
use MicrosoftAzure\Storage\Blob\Models\Container;
use MicrosoftAzure\Storage\Blob\Models\CreateContainerOptions;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;
use MicrosoftAzure\Storage\Blob\Models\PublicAccessType;
use BackWPup\Utils\BackWPupHelpers;

/**
 * Documentation: http://www.windowsazure.com/en-us/develop/php/how-to-guides/blob-service/.
 */
class BackWPup_Destination_MSAzure extends BackWPup_Destinations
{
    public const MSAZUREDIR = 'msazuredir';
    public const MSAZUREMAXBACKUPS = 'msazuremaxbackups';
    public const MSAZURESYNCNODELETE = 'msazuresyncnodelete';
    public const NEWMSAZURECONTAINER = 'newmsazurecontainer';

    public function option_defaults(): array
    {
        return [MsAzureDestinationConfiguration::MSAZURE_ACCNAME => '', MsAzureDestinationConfiguration::MSAZURE_KEY => '', MsAzureDestinationConfiguration::MSAZURE_CONTAINER => '', self::MSAZUREDIR => trailingslashit(sanitize_file_name(get_bloginfo('name'))), self::MSAZUREMAXBACKUPS => 15, self::MSAZURESYNCNODELETE => true];
    }

    public function edit_tab(int $jobid): void
    {
        ?>
		<h3 class="title"><?php esc_html_e('MS Azure access keys', 'backwpup'); ?></h3>
		<p></p>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="msazureaccname"><?php esc_html_e('Account name', 'backwpup'); ?></label></th>
				<td>
					<input id="msazureaccname" name="msazureaccname" type="text"
						   value="<?php echo esc_attr(BackWPup_Option::get($jobid, MsAzureDestinationConfiguration::MSAZURE_ACCNAME)); ?>" class="regular-text" autocomplete="off" />
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="msazurekey"><?php esc_html_e('Access key', 'backwpup'); ?></label></th>
				<td>
					<input id="msazurekey" name="msazurekey" type="password"
						   value="<?php echo esc_attr(BackWPup_Encryption::decrypt(BackWPup_Option::get($jobid, MsAzureDestinationConfiguration::MSAZURE_KEY))); ?>" class="regular-text" autocomplete="off" />
				</td>
			</tr>
		</table>

		<h3 class="title"><?php esc_html_e('Blob container', 'backwpup'); ?></h3>
		<p></p>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="msazurecontainerselected"><?php esc_html_e('Container selection', 'backwpup'); ?></label></th>
				<td>
					<input id="msazurecontainerselected" name="msazurecontainerselected" type="hidden" value="<?php echo esc_attr(BackWPup_Option::get($jobid, MsAzureDestinationConfiguration::MSAZURE_CONTAINER)); ?>" />
					<?php if (BackWPup_Option::get($jobid, MsAzureDestinationConfiguration::MSAZURE_ACCNAME) && BackWPup_Option::get($jobid, MsAzureDestinationConfiguration::MSAZURE_KEY)) {
            $this->edit_ajax([
                MsAzureDestinationConfiguration::MSAZURE_ACCNAME => BackWPup_Option::get($jobid, MsAzureDestinationConfiguration::MSAZURE_ACCNAME),
                MsAzureDestinationConfiguration::MSAZURE_KEY => BackWPup_Encryption::decrypt(BackWPup_Option::get($jobid, MsAzureDestinationConfiguration::MSAZURE_KEY)),
                'msazureselected' => BackWPup_Option::get($jobid, MsAzureDestinationConfiguration::MSAZURE_CONTAINER),
            ]);
        } ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="newmsazurecontainer"><?php esc_html_e('Create a new container', 'backwpup'); ?></label></th>
				<td>
					<input id="newmsazurecontainer" name="newmsazurecontainer" type="text" value="" class="regular-text" autocomplete="off" />
				</td>
			</tr>
		</table>

		<h3 class="title"><?php esc_html_e('Backup settings', 'backwpup'); ?></h3>
		<p></p>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="idmsazuredir"><?php esc_html_e('Folder in container', 'backwpup'); ?></label></th>
				<td>
					<input id="idmsazuredir" name="msazuredir" type="text" value="<?php echo esc_attr(BackWPup_Option::get(
            $jobid,
            self::MSAZUREDIR
        )); ?>" class="regular-text" />
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e('File deletion', 'backwpup'); ?></th>
				<td>
					<?php
                    if (BackWPup_Option::get($jobid, 'backuptype') === 'archive') {
                        ?>
						<label for="idmsazuremaxbackups">
							<input id="idmsazuremaxbackups" name="msazuremaxbackups" type="number" min="0" step="1" value="<?php echo esc_attr(BackWPup_Option::get(
                            $jobid,
                            self::MSAZUREMAXBACKUPS
                        )); ?>" class="small-text" />
							&nbsp;<?php esc_html_e('Number of files to keep in folder.', 'backwpup'); ?>
						</label>
						<p><?php _e('<strong>Warning</strong>: Files belonging to this job are now tracked. Old backup archives which are untracked will not be automatically deleted.', 'backwpup'); ?></p>
					<?php
                    } else { ?>
						<label for="idmsazuresyncnodelete">
							<input class="checkbox" value="1" type="checkbox" <?php checked(BackWPup_Option::get(
                            $jobid,
                            self::MSAZURESYNCNODELETE
                        ), true); ?> name="msazuresyncnodelete" id="idmsazuresyncnodelete" />
							&nbsp;<?php esc_html_e('Do not delete files while syncing to destination!', 'backwpup'); ?>
						</label>
					<?php } ?>
				</td>
			</tr>
		</table>
		<?php
    }

	/**
	 * {@inheritDoc}
	 *
	 * @param int|array $jobid The job ID or an array of job IDs.
	 *
	 * @throws \UnexpectedValueException If there is an issue with the Microsoft Azure configuration.
	 * @throws Exception If there is an issue creating the Microsoft Azure container.
	 *
	 * @return void
	 */
	public function edit_form_post_save( $jobid ): void {
		try {
				$msazure_configuration = $this->msazureConfiguration();
		} catch ( \UnexpectedValueException $exception ) {
			BackWPup_Admin::message( __( 'Microsoft Azure Configuration: ', 'backwpup' ) . $exception->getMessage(), true );
			throw $exception;
		}

		if ( $msazure_configuration->isNew() ) {
			try {
				$this->createContainer( $msazure_configuration );

                BackWPup_Admin::message(
					sprintf(
						// translators: %s is the container name.
						__( 'MS Azure container "%s" created.', 'backwpup' ),
						esc_html( sanitize_text_field( $msazure_configuration->msazurecontainer() ) )
					)
				);
			} catch ( Exception $e ) {
				// translators: %s is the error message.
				BackWPup_Admin::message( sprintf( __( 'MS Azure container create: %s', 'backwpup' ), $e->getMessage() ), true );
				throw $e;
			}
        }

				$msazure_dir = $this->msazureDir();

				$jobids = (array) $jobid;
		foreach ( $jobids as $jobid ) {
				BackWPup_Option::update(
					$jobid,
					MsAzureDestinationConfiguration::MSAZURE_ACCNAME,
					$msazure_configuration->msazureaccname()
				);
				BackWPup_Option::update(
					$jobid,
					MsAzureDestinationConfiguration::MSAZURE_KEY,
			$msazure_configuration->msazurekey()
				);
				BackWPup_Option::update(
					$jobid,
					MsAzureDestinationConfiguration::MSAZURE_CONTAINER,
			$msazure_configuration->msazurecontainer()
				);

				BackWPup_Option::update( $jobid, self::MSAZUREDIR, $msazure_dir );

				BackWPup_Option::update(
					$jobid,
					self::MSAZUREMAXBACKUPS,
					filter_input( INPUT_POST, self::MSAZUREMAXBACKUPS, FILTER_SANITIZE_NUMBER_INT ) ?: 0
				);

				BackWPup_Option::update(
					$jobid,
					self::MSAZURESYNCNODELETE,
					filter_input( INPUT_POST, self::MSAZURESYNCNODELETE ) ?: ''
				);
		}
	}

    public function file_delete(string $jobdest, string $backupfile): void
    {
        $files = get_site_transient('backwpup_' . strtolower($jobdest));
        [$jobid, $dest] = explode('_', $jobdest);

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
                foreach ($files as $key => $file) {
                    if (is_array($file) && $file['file'] == $backupfile) {
                        unset($files[$key]);
                    }
                }
            } catch (Exception $e) {
                BackWPup_Admin::message('MS AZURE: ' . $e->getMessage(), true);
            }
        }

        set_site_transient('backwpup_' . strtolower($jobdest), $files, YEAR_IN_SECONDS);
    }

    /**
     * {@inheritdoc}
     */
    public function file_get_list(string $jobdest): array
    {
        $list = (array) get_site_transient('backwpup_' . strtolower($jobdest));

        return array_filter($list);
    }

    public function job_run_archive(BackWPup_Job $job_object): bool
    {
        $job_object->substeps_todo = $job_object->backup_filesize + 2;

        if ($job_object->steps_data[$job_object->step_working]['SAVE_STEP_TRY'] != $job_object->steps_data[$job_object->step_working]['STEP_TRY']) {
            $job_object->log(sprintf(__('%d. Try sending backup to a Microsoft Azure (Blob)&#160;&hellip;', 'backwpup'), $job_object->steps_data[$job_object->step_working]['STEP_TRY']), E_USER_NOTICE);
        }

        try {
            $blobRestProxy = $this->createBlobClient(
                $job_object->job[MsAzureDestinationConfiguration::MSAZURE_ACCNAME],
                BackWPup_Encryption::decrypt($job_object->job[MsAzureDestinationConfiguration::MSAZURE_KEY])
            );

            if ($job_object->steps_data[$job_object->step_working]['SAVE_STEP_TRY'] != $job_object->steps_data[$job_object->step_working]['STEP_TRY']) {
                //test vor existing container
                $containers = $this->getContainers($blobRestProxy);

                $job_object->steps_data[$job_object->step_working]['container_url'] = '';

                foreach ($containers as $container) {
                    if ($container->getName() == $job_object->job[MsAzureDestinationConfiguration::MSAZURE_CONTAINER]) {
                        $job_object->steps_data[$job_object->step_working]['container_url'] = $container->getUrl();
                        break;
                    }
                }

                if (!$job_object->steps_data[$job_object->step_working]['container_url']) {
                    $job_object->log(sprintf(__('MS Azure container "%s" does not exist!', 'backwpup'), $job_object->job[MsAzureDestinationConfiguration::MSAZURE_CONTAINER]), E_USER_ERROR);

                    return true;
                }
                $job_object->log(sprintf(__('Connected to MS Azure container "%s".', 'backwpup'), $job_object->job[MsAzureDestinationConfiguration::MSAZURE_CONTAINER]), E_USER_NOTICE);

                $job_object->log(__('Starting upload to MS Azure&#160;&hellip;', 'backwpup'), E_USER_NOTICE);
            }

            //Prepare Upload
            $file_handel = null;
            if ($file_handel = fopen($job_object->backup_folder . $job_object->backup_file, 'rb')) {
                fseek($file_handel, $job_object->substeps_done);

                if (empty($job_object->steps_data[$job_object->step_working]['BlockList'])) {
                    $job_object->steps_data[$job_object->step_working]['BlockList'] = [];
                }

                while (!feof($file_handel)) {
                    $data = fread($file_handel, 1048576 * 4); //4MB
                    if (strlen($data) == 0) {
                        continue;
                    }
                    $chunk_upload_start = microtime(true);
                    $block_count = count($job_object->steps_data[$job_object->step_working]['BlockList']) + 1;
                    $block_id = base64_encode(str_pad($block_count, 6, '0', STR_PAD_LEFT));

                    $blobRestProxy->createBlobBlock(
                        $job_object->job[MsAzureDestinationConfiguration::MSAZURE_CONTAINER],
                        $job_object->job[self::MSAZUREDIR] . $job_object->backup_file,
                        $block_id,
                        $data
                    );

                    $job_object->steps_data[$job_object->step_working]['BlockList'][] = $block_id;
                    $chunk_upload_time = microtime(true) - $chunk_upload_start;
                    $job_object->substeps_done = $job_object->substeps_done + strlen($data);
                    $time_remaining = $job_object->do_restart_time();
                    if ($time_remaining < $chunk_upload_time) {
                        $job_object->do_restart_time(true);
                    }
                    $job_object->update_working_data();
                }
                fclose($file_handel);
            } else {
                $job_object->log(__('Can not open source file for transfer.', 'backwpup'), E_USER_ERROR);

                return false;
            }

            $blocklist = $this->createBlockList();

            foreach ($job_object->steps_data[$job_object->step_working]['BlockList'] as $block_id) {
                $blocklist->addUncommittedEntry($block_id);
            }
            unset($job_object->steps_data[$job_object->step_working]['BlockList']);

            //Commit Blocks
            $blobRestProxy->commitBlobBlocks($job_object->job[MsAzureDestinationConfiguration::MSAZURE_CONTAINER], $job_object->job[self::MSAZUREDIR] . $job_object->backup_file, $blocklist->getEntries());

            ++$job_object->substeps_done;
            $job_object->log(sprintf(__('Backup transferred to %s', 'backwpup'), $job_object->steps_data[$job_object->step_working]['container_url'] . '/' . $job_object->job[self::MSAZUREDIR] . $job_object->backup_file), E_USER_NOTICE);
            if (!empty($job_object->job['jobid'])) {
                BackWPup_Option::update($job_object->job['jobid'], 'lastbackupdownloadurl', network_admin_url('admin.php') . '?page=backwpupbackups&action=downloadmsazure&file=' . $job_object->job[self::MSAZUREDIR] . $job_object->backup_file . '&jobid=' . $job_object->job['jobid']);
            }
        } catch (Exception $e) {
            $job_object->log(E_USER_ERROR, sprintf(__('Microsoft Azure API: %s', 'backwpup'), $e->getMessage()), $e->getFile(), $e->getLine());
            $job_object->substeps_done = 0;
            unset($job_object->steps_data[$job_object->step_working]['BlockList']);
            if (isset($file_handel) && is_resource($file_handel)) {
                fclose($file_handel);
            }

            return false;
        }

        try {
            $backupfilelist = [];
            $filecounter = 0;
            $files = [];

            $blob_options = $this->createListBlobsOptions();
            $blob_options->setPrefix($job_object->job[self::MSAZUREDIR]);

            $blobs = $this->getBlobs(
                $blobRestProxy,
                $job_object->job[MsAzureDestinationConfiguration::MSAZURE_CONTAINER],
                $blob_options
            );

            if (is_array($blobs)) {
                foreach ($blobs as $blob) {
                    $file = basename($blob->getName());
                    if ($this->is_backup_archive($file) && $this->is_backup_owned_by_job($file, $job_object->job['jobid']) == true) {
                        $backupfilelist[$blob->getProperties()->getLastModified()->getTimestamp()] = $file;
                    }
                    $files[$filecounter]['folder'] = $job_object->steps_data[$job_object->step_working]['container_url'] . '/' . dirname($blob->getName()) . '/';
                    $files[$filecounter]['file'] = $blob->getName();
                    $files[$filecounter]['filename'] = basename($blob->getName());
                    $files[$filecounter]['downloadurl'] = network_admin_url('admin.php') . '?page=backwpupbackups&action=downloadmsazure&file=' . $blob->getName() . '&jobid=' . $job_object->job['jobid'];
                    $files[$filecounter]['filesize'] = $blob->getProperties()->getContentLength();
                    $files[$filecounter]['time'] = $blob->getProperties()->getLastModified()->getTimestamp() + (get_option('gmt_offset') * 3600);
                    ++$filecounter;
                }
            }
            // Delete old backups
            if (!empty($job_object->job[self::MSAZUREMAXBACKUPS]) && $job_object->job[self::MSAZUREMAXBACKUPS] > 0) {
                if (count($backupfilelist) > $job_object->job[self::MSAZUREMAXBACKUPS]) {
                    ksort($backupfilelist);
					$numdeltefiles = 0;
					$deleted_files = [];

                    while ($file = array_shift($backupfilelist)) {
                        if (count($backupfilelist) < $job_object->job[self::MSAZUREMAXBACKUPS]) {
                            break;
                        }
                        $blobRestProxy->deleteBlob($job_object->job[MsAzureDestinationConfiguration::MSAZURE_CONTAINER], $job_object->job[self::MSAZUREDIR] . $file);

						foreach ( $files as $key => $filedata ) {
							if ( $filedata['file'] === $job_object->job[ self::MSAZUREDIR ] . $file ) {
								$deleted_files[] = $filedata['filename'];
								unset( $files[ $key ] );
							}
                        }
                        ++$numdeltefiles;
                    }
                    if ($numdeltefiles > 0) {
                        $job_object->log(sprintf(_n('One file deleted on Microsoft Azure container.', '%d files deleted on Microsoft Azure container.', $numdeltefiles, 'backwpup'), $numdeltefiles), E_USER_NOTICE);
					}

					parent::remove_file_history_from_database( $deleted_files, 'MSAZURE' );
				}
            }
            set_site_transient('backwpup_' . $job_object->job['jobid'] . '_msazure', $files, YEAR_IN_SECONDS);
        } catch (Exception $e) {
            $job_object->log(E_USER_ERROR, sprintf(__('Microsoft Azure API: %s', 'backwpup'), $e->getMessage()), $e->getFile(), $e->getLine());

            return false;
        }

        $job_object->substeps_done = $job_object->backup_filesize + 2;

        return true;
    }

    public function can_run(array $job_settings): bool
    {
        if (empty($job_settings[MsAzureDestinationConfiguration::MSAZURE_ACCNAME])) {
            return false;
        }

        if (empty($job_settings[MsAzureDestinationConfiguration::MSAZURE_KEY])) {
            return false;
        }

        return !(empty($job_settings[MsAzureDestinationConfiguration::MSAZURE_CONTAINER]));
    }

    public function edit_inline_js(): void
    {
        ?>
		<script type="text/javascript">
			jQuery(document).ready(function ($) {
				function msazuregetcontainer() {
					var data = {
						action: 'backwpup_dest_msazure',
						msazureaccname: $('#msazureaccname').val(),
						msazurekey: $('#msazurekey').val(),
						msazureselected: $('#msazurecontainer').val(),
						_ajax_nonce: $('#backwpupajaxnonce').val()
					};
					$.post(ajaxurl, data, function (response) {
						$('#msazureBucketContainer').html(response);
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

    public function edit_ajax(array $args = []): void
    {
        $error = '';
        $ajax = false;

        $msazureName = filter_input(
            INPUT_POST,
            MsAzureDestinationConfiguration::MSAZURE_ACCNAME
        ) ?: '';
        $msazureKey = filter_input(
            INPUT_POST,
            MsAzureDestinationConfiguration::MSAZURE_KEY
        ) ?: '';
        $msazureSelected = filter_input(
            INPUT_POST,
            'msazureselected'
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

        $containers = null;

        if (!empty($args[MsAzureDestinationConfiguration::MSAZURE_ACCNAME]) && !empty($args[MsAzureDestinationConfiguration::MSAZURE_KEY])) {
            try {
                $blobClient = $this->createBlobClient(
                    $args[MsAzureDestinationConfiguration::MSAZURE_ACCNAME],
                    BackWPup_Encryption::decrypt($args[MsAzureDestinationConfiguration::MSAZURE_KEY])
                );

                $containers = $blobClient->listContainers()->getContainers();
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }

        if (empty($args[MsAzureDestinationConfiguration::MSAZURE_ACCNAME])) {
            _e('Missing account name!', 'backwpup');
        } elseif (empty($args[MsAzureDestinationConfiguration::MSAZURE_KEY])) {
            _e('Missing access key!', 'backwpup');
        } elseif (!empty($error)) {
            echo esc_html($error);
        } elseif (empty($containers)) {
            _e('No container found!', 'backwpup');
        }
        echo '</span>';

		if ( ! empty( $containers ) ) {
			$containers_list = [];
			foreach ( $containers as $container ) {
				$containers_list[ $container->getName() ] = $container->getName();
			}
			echo BackWPupHelpers::component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				'form/select',
				[
					'name'       => 'msazurecontainer',
					'identifier' => 'msazurecontainer',
					'label'      => esc_html__( 'Bucket selection', 'backwpup' ),
					'withEmpty'  => false,
					'value'      => $args['msazureselected'], // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'options'    => $containers_list, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				]
				);
		}
        if ($ajax) {
            exit();
        }
    }

    /**
     * Creates the service used to access the blob.
     */
    public function createBlobClient(string $accountName, string $accountKey): BlobRestProxy
    {
        $connectionString = 'DefaultEndpointsProtocol=https;AccountName='
            . $accountName . ';AccountKey=' . $accountKey;

        return BlobRestProxy::createBlobService($connectionString);
    }

    protected function msazureConfiguration(): MsAzureDestinationConfiguration
    {
        $msazureaccname = filter_input(INPUT_POST, MsAzureDestinationConfiguration::MSAZURE_ACCNAME);
        $msazurekey = filter_input(INPUT_POST, MsAzureDestinationConfiguration::MSAZURE_KEY);
        $msazurecontainer = filter_input(
            INPUT_POST,
            MsAzureDestinationConfiguration::MSAZURE_CONTAINER
        );

        if (!$msazurecontainer) {
            $newmsazurecontainer = filter_input(
                INPUT_POST,
                self::NEWMSAZURECONTAINER
            );

            return MsAzureDestinationConfiguration::withNewContainer(
                $msazureaccname,
                $msazurekey,
                $newmsazurecontainer
            );
        }

        return new MsAzureDestinationConfiguration(
            $msazureaccname,
            $msazurekey,
            $msazurecontainer
        );
    }

    protected function createContainer(MsAzureDestinationConfiguration $configuration): void
    {
        $blobClient = $this->createBlobClient(
            $configuration->msazureaccname(),
            $configuration->msazurekey()
        );

        $createContainerOptions = $this->createContainerOptionsFactory();
        $createContainerOptions->setPublicAccess(PublicAccessType::NONE);

        $blobClient->createContainer(
            $configuration->msazurecontainer(),
            $createContainerOptions
        );
    }

    protected function createContainerOptionsFactory(): CreateContainerOptions
    {
        return new CreateContainerOptions();
    }

    protected function deleteBlob(BlobRestProxy $blobClient, string $container, string $backupfile): void
    {
        $blobClient->deleteBlob(
            $container,
            $backupfile
        );
    }

    /**
     * @return Blob[]
     */
    protected function getBlobs(BlobRestProxy $blobClient, string $container, ListBlobsOptions $options): array
    {
        return $blobClient->listBlobs(
            $container,
            $options
        )->getBlobs();
    }

    /**
     * @return Container[]
     */
    protected function getContainers(BlobRestProxy $blobClient): array
    {
        return $blobClient->listContainers()->getContainers();
    }

    protected function createBlockList(): BlockList
    {
        return new BlockList();
    }

    protected function createListBlobsOptions(): ListBlobsOptions
    {
        return new ListBlobsOptions();
    }

    protected function msazureDir(): string
    {
        $msazureDir = trailingslashit(
            str_replace(
                '//',
                '/',
                str_replace(
                    '\\',
                    '/',
                    trim(
                        filter_input(INPUT_POST, self::MSAZUREDIR) ?: ''
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
     *
     * @param string $jobDestination String containing a job destination, ex. 1_SOME_DESTINATION.
     *
     * @throws RuntimeException
     *
     * @return int
     */
    protected function extractJobIdFromDestination(string $jobDestination)
    {
        $jobId = intval(substr($jobDestination, 0, strpos($jobDestination, '_', 1)));

        if (!$jobId) {
            throw new RuntimeException(
                sprintf(__('Could not extract job id from destination %s.', 'backwpup'), $jobDestination)
            );
        }

        return $jobId;
    }
}
