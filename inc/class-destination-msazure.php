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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Documentation: http://www.windowsazure.com/en-us/develop/php/how-to-guides/blob-service/.
 */
class BackWPup_Destination_MSAzure extends BackWPup_Destinations {

	/**
	 * Service name
	 *
	 * @var string
	 */
	private const SERVICE_NAME = 'MSAzure';

	public const MSAZUREDIR          = 'msazuredir';
	public const MSAZUREMAXBACKUPS   = 'msazuremaxbackups';
	public const MSAZURESYNCNODELETE = 'msazuresyncnodelete';
	public const NEWMSAZURECONTAINER = 'newmsazurecontainer';

	/**
	 * Get default options for Azure destination.
	 *
	 * @return array
	 */
	public function option_defaults(): array {
		return [
			MsAzureDestinationConfiguration::MSAZURE_ACCNAME => '',
			MsAzureDestinationConfiguration::MSAZURE_KEY => '',
			MsAzureDestinationConfiguration::MSAZURE_CONTAINER => '',
			self::MSAZUREDIR                             => trailingslashit( sanitize_file_name( get_bloginfo( 'name' ) ) ),
			self::MSAZUREMAXBACKUPS                      => 15,
			self::MSAZURESYNCNODELETE                    => true,
		];
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

		if ( $msazure_configuration->is_new() ) {
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
		$jobids      = (array) $jobid;
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
				isset( $_POST[ self::MSAZUREMAXBACKUPS ] ) && is_numeric( $_POST[ self::MSAZUREMAXBACKUPS ] ) ? absint( $_POST[ self::MSAZUREMAXBACKUPS ] ) : $this->option_defaults()[ self::MSAZUREMAXBACKUPS ] // phpcs:ignore WordPress.Security.NonceVerification.Missing
			);

			BackWPup_Option::update(
				$jobid,
				self::MSAZURESYNCNODELETE,
				filter_input( INPUT_POST, self::MSAZURESYNCNODELETE ) ?: ''
			);
		}
	}

	/**
	 * Delete a file from Azure.
	 *
	 * @param string $jobdest    Job destination string.
	 * @param string $backupfile Backup file path.
	 *
	 * @return void
	 */
	public function file_delete( string $jobdest, string $backupfile ): void {
		$files          = get_site_transient( 'backwpup_' . strtolower( $jobdest ) );
		[$jobid, $dest] = explode( '_', $jobdest );

		if ( BackWPup_Option::get( $jobid, MsAzureDestinationConfiguration::MSAZURE_ACCNAME )
			&& BackWPup_Option::get( $jobid, MsAzureDestinationConfiguration::MSAZURE_KEY )
			&& BackWPup_Option::get( $jobid, MsAzureDestinationConfiguration::MSAZURE_CONTAINER ) ) {
			try {
				$blob_client = $this->createBlobClient(
					BackWPup_Option::get( $jobid, MsAzureDestinationConfiguration::MSAZURE_ACCNAME ),
					BackWPup_Encryption::decrypt(
						BackWPup_Option::get( $jobid, MsAzureDestinationConfiguration::MSAZURE_KEY )
					)
				);

				$this->deleteBlob(
					$blob_client,
					BackWPup_Option::get(
						$jobid,
						MsAzureDestinationConfiguration::MSAZURE_CONTAINER
					),
					$backupfile
				);

				// Update file list.
				foreach ( $files as $key => $file ) {
					if ( is_array( $file ) && $backupfile === $file['file'] ) {
						unset( $files[ $key ] );
					}
				}
			} catch ( Exception $e ) {
				BackWPup_Admin::message( 'MS AZURE: ' . $e->getMessage(), true );
			}
		}

		set_site_transient( 'backwpup_' . strtolower( $jobdest ), $files, YEAR_IN_SECONDS );
	}

	/**
	 * Run archive job for Azure.
	 *
	 * @param BackWPup_Job $job_object Job object.
	 *
	 * @return bool
	 */
	public function job_run_archive( BackWPup_Job $job_object ): bool {
		$job_object->substeps_todo = $job_object->backup_filesize + 2;

		if ( $job_object->steps_data[ $job_object->step_working ]['SAVE_STEP_TRY'] !== $job_object->steps_data[ $job_object->step_working ]['STEP_TRY'] ) {
			$job_object->log(
				sprintf(
				/* translators: %d: attempt number. */
				__( '%d. Try sending backup to a Microsoft Azure (Blob)&#160;&hellip;', 'backwpup' ),
				$job_object->steps_data[ $job_object->step_working ]['STEP_TRY']
			),
				E_USER_NOTICE
				);
		}

		try {
			$blob_rest_proxy = $this->createBlobClient(
				$job_object->job[ MsAzureDestinationConfiguration::MSAZURE_ACCNAME ],
				BackWPup_Encryption::decrypt( $job_object->job[ MsAzureDestinationConfiguration::MSAZURE_KEY ] )
			);

			if ( $job_object->steps_data[ $job_object->step_working ]['SAVE_STEP_TRY'] !== $job_object->steps_data[ $job_object->step_working ]['STEP_TRY'] ) {
				// Test for existing container.
				$containers = $this->getContainers( $blob_rest_proxy );

				$job_object->steps_data[ $job_object->step_working ]['container_url'] = '';

				foreach ( $containers as $container ) {
					if ( $job_object->job[ MsAzureDestinationConfiguration::MSAZURE_CONTAINER ] === $container->getName() ) {
						$job_object->steps_data[ $job_object->step_working ]['container_url'] = $container->getUrl();
						break;
					}
				}

				if ( ! $job_object->steps_data[ $job_object->step_working ]['container_url'] ) {
					$job_object->log(
						sprintf(
						/* translators: %s: container name. */
						__( 'MS Azure container "%s" does not exist!', 'backwpup' ),
						$job_object->job[ MsAzureDestinationConfiguration::MSAZURE_CONTAINER ]
					),
						E_USER_ERROR
						);

					return true;
				}
				$job_object->log(
					sprintf(
					/* translators: %s: container name. */
					__( 'Connected to MS Azure container "%s".', 'backwpup' ),
					$job_object->job[ MsAzureDestinationConfiguration::MSAZURE_CONTAINER ]
				),
					E_USER_NOTICE
					);

				$job_object->log( __( 'Starting upload to MS Azure&#160;&hellip;', 'backwpup' ), E_USER_NOTICE );
			}

			// Prepare upload.
			$file_handel = fopen( $job_object->backup_folder . $job_object->backup_file, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
			if ( $file_handel ) {
				fseek( $file_handel, $job_object->substeps_done );

				if ( empty( $job_object->steps_data[ $job_object->step_working ]['BlockList'] ) ) {
					$job_object->steps_data[ $job_object->step_working ]['BlockList'] = [];
				}

				while ( ! feof( $file_handel ) ) {
					$data = fread( $file_handel, 1048576 * 4 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread -- 4MB.
					if ( 0 === strlen( $data ) ) {
						continue;
					}
					$chunk_upload_start = microtime( true );
					$block_count        = count( $job_object->steps_data[ $job_object->step_working ]['BlockList'] ) + 1;
          // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
					$block_id = base64_encode( str_pad( $block_count, 6, '0', STR_PAD_LEFT ) );

					$blob_rest_proxy->createBlobBlock(
						$job_object->job[ MsAzureDestinationConfiguration::MSAZURE_CONTAINER ],
						$job_object->job[ self::MSAZUREDIR ] . $job_object->backup_file,
						$block_id,
						$data
					);

					$job_object->steps_data[ $job_object->step_working ]['BlockList'][] = $block_id;
					$chunk_upload_time         = microtime( true ) - $chunk_upload_start;
					$job_object->substeps_done = $job_object->substeps_done + strlen( $data );
					$time_remaining            = $job_object->do_restart_time();
					if ( $time_remaining < $chunk_upload_time ) {
						$job_object->do_restart_time( true );
					}
					$job_object->update_working_data();
				}
				fclose( $file_handel ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			} else {
				$job_object->log( __( 'Can not open source file for transfer.', 'backwpup' ), E_USER_ERROR );

				return false;
			}

			$blocklist = $this->createBlockList();

			foreach ( $job_object->steps_data[ $job_object->step_working ]['BlockList'] as $block_id ) {
				$blocklist->addUncommittedEntry( $block_id );
			}
			unset( $job_object->steps_data[ $job_object->step_working ]['BlockList'] );

			// Commit Blocks.
			$blob_rest_proxy->commitBlobBlocks( $job_object->job[ MsAzureDestinationConfiguration::MSAZURE_CONTAINER ], $job_object->job[ self::MSAZUREDIR ] . $job_object->backup_file, $blocklist->getEntries() );

			++$job_object->substeps_done;
			$job_object->log(
				sprintf(
				/* translators: %s: destination path. */
				__( 'Backup transferred to %s', 'backwpup' ),
				$job_object->steps_data[ $job_object->step_working ]['container_url'] . '/' . $job_object->job[ self::MSAZUREDIR ] . $job_object->backup_file
			),
				E_USER_NOTICE
				);
			if ( ! empty( $job_object->job['jobid'] ) ) {
				BackWPup_Option::update( $job_object->job['jobid'], 'lastbackupdownloadurl', network_admin_url( 'admin.php' ) . '?page=backwpupbackups&action=downloadmsazure&file=' . $job_object->job[ self::MSAZUREDIR ] . $job_object->backup_file . '&jobid=' . $job_object->job['jobid'] );
			}
		} catch ( Exception $e ) {
			$context = $this->msazure_error_context( $e );
			$job_object->log(
				E_USER_ERROR,
				sprintf(
				/* translators: %s: error message. */
				__( 'Microsoft Azure API: %s', 'backwpup' ),
				$e->getMessage()
			),
				$e->getFile(),
				$e->getLine(),
				$context
				);
			$job_object->substeps_done = 0;
			unset( $job_object->steps_data[ $job_object->step_working ]['BlockList'] );
			if ( isset( $file_handel ) && is_resource( $file_handel ) ) {
				fclose( $file_handel ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			}

			return false;
		}

		try {
			$backupfilelist = [];
			$filecounter    = 0;
			$files          = [];

			$blob_options = $this->createListBlobsOptions();
			$blob_options->setPrefix( $job_object->job[ self::MSAZUREDIR ] );

			$blobs = $this->getBlobs(
				$blob_rest_proxy,
				$job_object->job[ MsAzureDestinationConfiguration::MSAZURE_CONTAINER ],
				$blob_options
			);

			if ( is_array( $blobs ) ) {
				foreach ( $blobs as $blob ) {
					$file = basename( $blob->getName() );
					if ( $this->is_backup_archive( $file ) && $this->is_backup_owned_by_job( $file, $job_object->job['jobid'] ) ) {
						$backupfilelist[ $blob->getProperties()->getLastModified()->getTimestamp() ] = $file;
					}
					$files[ $filecounter ]['folder']      = $job_object->steps_data[ $job_object->step_working ]['container_url'] . '/' . dirname( $blob->getName() ) . '/';
					$files[ $filecounter ]['file']        = $blob->getName();
					$files[ $filecounter ]['filename']    = basename( $blob->getName() );
					$files[ $filecounter ]['downloadurl'] = network_admin_url( 'admin.php' ) . '?page=backwpupbackups&action=downloadmsazure&file=' . $blob->getName() . '&jobid=' . $job_object->job['jobid'];
					$files[ $filecounter ]['filesize']    = $blob->getProperties()->getContentLength();
					$files[ $filecounter ]['time']        = $blob->getProperties()->getLastModified()->getTimestamp() + ( get_option( 'gmt_offset' ) * 3600 );
					++$filecounter;
				}
			}
			// Delete old backups.
			if ( ! empty( $job_object->job[ self::MSAZUREMAXBACKUPS ] ) && $job_object->job[ self::MSAZUREMAXBACKUPS ] > 0 ) {
				if ( count( $backupfilelist ) > $job_object->job[ self::MSAZUREMAXBACKUPS ] ) {
					ksort( $backupfilelist );
					$numdeltefiles = 0;
					$deleted_files = [];

					while ( ! empty( $backupfilelist ) ) {
						$file = array_shift( $backupfilelist );
						if ( count( $backupfilelist ) < $job_object->job[ self::MSAZUREMAXBACKUPS ] ) {
							break;
						}
						$blob_rest_proxy->deleteBlob( $job_object->job[ MsAzureDestinationConfiguration::MSAZURE_CONTAINER ], $job_object->job[ self::MSAZUREDIR ] . $file );

						foreach ( $files as $key => $filedata ) {
							if ( $filedata['file'] === $job_object->job[ self::MSAZUREDIR ] . $file ) {
								$deleted_files[] = $filedata['filename'];
								unset( $files[ $key ] );
							}
						}
						++$numdeltefiles;
					}
					if ( $numdeltefiles > 0 ) {
						$job_object->log(
							sprintf(
								// translators: %d: number of files.
								_n(
									'%d file deleted on Microsoft Azure container.',
									'%d files deleted on Microsoft Azure container.',
									$numdeltefiles,
									'backwpup'
								),
								$numdeltefiles
							),
							E_USER_NOTICE
						);
					}

					parent::remove_file_history_from_database( $deleted_files, 'MSAZURE' );
				}
			}
			set_site_transient( 'backwpup_' . $job_object->job['jobid'] . '_msazure', $files, YEAR_IN_SECONDS );
		} catch ( Exception $e ) {
			$context = $this->msazure_error_context( $e );
			$job_object->log(
				E_USER_ERROR,
				sprintf(
				/* translators: %s: error message. */
				__( 'Microsoft Azure API: %s', 'backwpup' ),
				$e->getMessage()
			),
				$e->getFile(),
				$e->getLine(),
				$context
				);

			return false;
		}

		$job_object->substeps_done = $job_object->backup_filesize + 2;

		return true;
	}

	/**
	 * Check if Azure destination can run.
	 *
	 * @param array $job_settings Job settings.
	 *
	 * @return bool
	 */
	public function can_run( array $job_settings ): bool {
		if ( empty( $job_settings[ MsAzureDestinationConfiguration::MSAZURE_ACCNAME ] ) ) {
			return false;
		}

		if ( empty( $job_settings[ MsAzureDestinationConfiguration::MSAZURE_KEY ] ) ) {
			return false;
		}

		return ! ( empty( $job_settings[ MsAzureDestinationConfiguration::MSAZURE_CONTAINER ] ) );
	}

	/**
	 * Output inline JavaScript for Azure settings.
	 *
	 * @return void
	 */
	public function edit_inline_js(): void {
		?>
		<script type="text/javascript">
			jQuery(document).ready(function ($) {
				function msazuregetcontainer() {
					var data = {
						action: 'backwpup_dest_msazure',
						msazureaccname: $('#msazureaccname').val(),
						msazurekey: $('#msazurekey').val(),
						msazureselected: $('#msazurecontainer').val(),
						_ajax_nonce: $('input[name="backwpupajaxnonce"]').val()
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

	/**
	 * Handle AJAX requests for Azure settings.
	 *
	 * @param array $args Request arguments.
	 *
	 * @return void
	 */
	public function edit_ajax( array $args = [] ): void {
		$error = '';
		$ajax  = false;

		$msazure_name     = filter_input(
			INPUT_POST,
			MsAzureDestinationConfiguration::MSAZURE_ACCNAME
		) ?: '';
		$msazure_key      = filter_input(
			INPUT_POST,
			MsAzureDestinationConfiguration::MSAZURE_KEY
		) ?: '';
		$msazure_selected = filter_input(
			INPUT_POST,
			'msazureselected'
		) ?: '';

		if ( $msazure_name || $msazure_key ) {
			if ( ! current_user_can( 'backwpup_jobs_edit' ) ) {
				wp_die( -1 );
			}
			check_ajax_referer( 'backwpup_ajax_nonce' );
			$args[ MsAzureDestinationConfiguration::MSAZURE_ACCNAME ] = $msazure_name;
			$args[ MsAzureDestinationConfiguration::MSAZURE_KEY ]     = $msazure_key;
			$args['msazureselected']                                  = $msazure_selected;
			$ajax = true;
		}
		echo '<span id="msazurecontainererror" class="bwu-message-error">';

		$containers = null;

		if ( ! empty( $args[ MsAzureDestinationConfiguration::MSAZURE_ACCNAME ] ) && ! empty( $args[ MsAzureDestinationConfiguration::MSAZURE_KEY ] ) ) {
			try {
				$blob_client = $this->createBlobClient(
					$args[ MsAzureDestinationConfiguration::MSAZURE_ACCNAME ],
					BackWPup_Encryption::decrypt( $args[ MsAzureDestinationConfiguration::MSAZURE_KEY ] )
				);

				$containers = $blob_client->listContainers()->getContainers();
			} catch ( Exception $e ) {
				$error = $e->getMessage();
			}
		}

		if ( empty( $args[ MsAzureDestinationConfiguration::MSAZURE_ACCNAME ] ) ) {
			esc_html_e( 'Missing account name!', 'backwpup' );
		} elseif ( empty( $args[ MsAzureDestinationConfiguration::MSAZURE_KEY ] ) ) {
			esc_html_e( 'Missing access key!', 'backwpup' );
		} elseif ( ! empty( $error ) ) {
			echo esc_html( $error );
		} elseif ( empty( $containers ) ) {
			esc_html_e( 'No container found!', 'backwpup' );
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
		if ( $ajax ) {
			exit();
		}
	}

	/**
	 * Creates the service used to access the blob.
	 *
	 * @param string $account_name Account name.
	 * @param string $account_key  Account key.
	 *
	 * @return BlobRestProxy
	 */
	public function createBlobClient( string $account_name, string $account_key ): BlobRestProxy {
		$connection_string = 'DefaultEndpointsProtocol=https;AccountName='
			. $account_name . ';AccountKey=' . $account_key;

		return BlobRestProxy::createBlobService( $connection_string );
	}

	/**
	 * Build Azure configuration from request data.
	 *
	 * @return MsAzureDestinationConfiguration
	 */
	protected function msazureConfiguration(): MsAzureDestinationConfiguration {
		$msazureaccname   = filter_input( INPUT_POST, MsAzureDestinationConfiguration::MSAZURE_ACCNAME );
		$msazurekey       = filter_input( INPUT_POST, MsAzureDestinationConfiguration::MSAZURE_KEY );
		$msazurecontainer = filter_input(
			INPUT_POST,
			MsAzureDestinationConfiguration::MSAZURE_CONTAINER
		);

		if ( ! $msazurecontainer ) {
			$newmsazurecontainer = filter_input(
				INPUT_POST,
				self::NEWMSAZURECONTAINER
			);

			return MsAzureDestinationConfiguration::with_new_container(
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

	/**
	 * Create container if needed.
	 *
	 * @param MsAzureDestinationConfiguration $configuration Configuration.
	 *
	 * @return void
	 */
	protected function createContainer( MsAzureDestinationConfiguration $configuration ): void {
		$blob_client = $this->createBlobClient(
			$configuration->msazureaccname(),
			$configuration->msazurekey()
		);

		$create_container_options = $this->createContainerOptionsFactory();
		$create_container_options->setPublicAccess( PublicAccessType::NONE );

		$blob_client->createContainer(
			$configuration->msazurecontainer(),
			$create_container_options
		);
	}

	/**
	 * Create container options factory.
	 *
	 * @return CreateContainerOptions
	 */
	protected function createContainerOptionsFactory(): CreateContainerOptions {
		return new CreateContainerOptions();
	}

	/**
	 * Delete a blob from a container.
	 *
	 * @param BlobRestProxy $blob_client Blob client.
	 * @param string        $container   Container name.
	 * @param string        $backupfile  Backup file name.
	 *
	 * @return void
	 */
	protected function deleteBlob( BlobRestProxy $blob_client, string $container, string $backupfile ): void {
		$blob_client->deleteBlob(
			$container,
			$backupfile
		);
	}

	/**
	 * Get blobs for a container.
	 *
	 * @param BlobRestProxy    $blob_client Blob client.
	 * @param string           $container   Container name.
	 * @param ListBlobsOptions $options     List options.
	 *
	 * @return Blob[]
	 */
	protected function getBlobs( BlobRestProxy $blob_client, string $container, ListBlobsOptions $options ): array {
		return $blob_client->listBlobs(
			$container,
			$options
		)->getBlobs();
	}

	/**
	 * Get containers for the account.
	 *
	 * @param BlobRestProxy $blob_client Blob client.
	 *
	 * @return Container[]
	 */
	protected function getContainers( BlobRestProxy $blob_client ): array {
		return $blob_client->listContainers()->getContainers();
	}

	/**
	 * Create a block list.
	 *
	 * @return BlockList
	 */
	protected function createBlockList(): BlockList {
		return new BlockList();
	}

	/**
	 * Create list blobs options.
	 *
	 * @return ListBlobsOptions
	 */
	protected function createListBlobsOptions(): ListBlobsOptions {
		return new ListBlobsOptions();
	}

	/**
	 * Build Azure directory path.
	 *
	 * @return string
	 */
	protected function msazureDir(): string {
		$msazure_dir = trailingslashit(
			str_replace(
				'//',
				'/',
				str_replace(
					'\\',
					'/',
					trim(
						filter_input( INPUT_POST, self::MSAZUREDIR ) ?: ''
					)
				)
			)
		);

		if ( '/' === substr( $msazure_dir, 0, 1 ) ) {
			$msazure_dir = substr( $msazure_dir, 1 );
		}

		if ( '/' === $msazure_dir ) {
			$msazure_dir = '';
		}

		return $msazure_dir;
	}

	/**
	 * It extracts the job id from a job destination string.
	 *
	 * @param string $job_destination String containing a job destination, ex. 1_SOME_DESTINATION.
	 *
	 * @return int
	 * @throws RuntimeException If the job id could not be extracted.
	 */
	protected function extractJobIdFromDestination( string $job_destination ) {
		$job_id = (int) substr( $job_destination, 0, strpos( $job_destination, '_', 1 ) );

		if ( ! $job_id ) {
			throw new RuntimeException(
				// translators: %s: Destination string.
				sprintf( esc_html__( 'Could not extract job id from destination %s.', 'backwpup' ), esc_html( $job_destination ) )
			);
		}

		return $job_id;
	}

	/**
	 * Build error context for Azure errors.
	 *
	 * @param Exception $exception Exception instance.
	 * @return array
	 */
	private function msazure_error_context( Exception $exception ): array {
		$message     = strtolower( $exception->getMessage() );
		$status      = (int) $exception->getCode();
		$error_code  = '';
		$error_lower = '';

		if ( method_exists( $exception, 'getErrorCode' ) ) {
			$error_code  = (string) $exception->getErrorCode();
			$error_lower = strtolower( $error_code );
		}

		if (
			in_array( $status, [ 401, 403 ], true )
			|| false !== strpos( $error_lower, 'auth' )
			|| false !== strpos( $error_lower, 'unauthorized' )
			|| false !== strpos( $message, 'auth' )
			|| false !== strpos( $message, 'unauthorized' )
		) {
			$context = [
				'reason_code'   => 'incorrect_login',
				'destination'   => 'MSAZURE',
				'provider_code' => $error_code ?: 'auth_failed',
			];
			if ( $status > 0 ) {
				$context['http_status'] = $status;
			}
			return $context;
		}

		if (
			false !== strpos( $error_lower, 'insufficient' )
			|| false !== strpos( $error_lower, 'quota' )
			|| false !== strpos( $message, 'insufficient' )
			|| false !== strpos( $message, 'quota' )
			|| false !== strpos( $message, 'not enough' )
		) {
			$context = [
				'reason_code'   => 'not_enough_storage',
				'destination'   => 'MSAZURE',
				'provider_code' => $error_code ?: 'quota_exceeded',
			];
			if ( $status > 0 ) {
				$context['http_status'] = $status;
			}
			return $context;
		}

		return [];
	}

	/**
	 * Get service name.
	 *
	 * @return string
	 */
	public function get_service_name(): string {
		return self::SERVICE_NAME;
	}
}
