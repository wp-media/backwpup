<?php
declare(strict_types=1);

use BackWPup\Utils\BackWPupHelpers;
use WPMedia\BackWPup\StorageProviders\Rackspace\RackspaceProvider as Rackspace;


class BackWPup_Destination_RSC extends BackWPup_Destinations {


	/**
	 * Rackspace US
	 *
	 * @var string
	 */
	private string $rackspace_us = 'https://identity.api.rackspacecloud.com/v2.0/';

	/**
	 * Rackspace UK
	 *
	 * @var string
	 */
	private string $rackspace_uk = 'https://lon.identity.api.rackspacecloud.com/v2.0/';

	/**
	 * Default options
	 *
	 * @return array
	 */
	public function option_defaults(): array {
		return [
			'rscusername'     => '',
			'rscapikey'       => '',
			'rsccontainer'    => '',
			'rscregion'       => 'DFW',
			'rscdir'          => trailingslashit( sanitize_file_name( get_bloginfo( 'name' ) ) ),
			'rscmaxbackups'   => 15,
			'rscsyncnodelete' => true,
		];
	}

	/**
	 * Get Auth url by region code.
	 *
	 * @param string $region Region code
	 */
	public function get_auth_url_by_region( string $region ): string {
		$region = strtoupper( $region );

		if ( 'LON' === $region ) {
			return $this->rackspace_uk;
		}

		return $this->rackspace_us;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param int|array $id Job ID.
	 * @return void
	 * @throws Exception When the Rackspace Cloud API throws an exception.
	 *
	 * @phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
	 */
	public function edit_form_post_save( $id ): void {
		$_POST['rscdir'] = trailingslashit( str_replace( '//', '/', str_replace( '\\', '/', trim( sanitize_text_field( $_POST['rscdir'] ) ) ) ) );
		if ( substr( $_POST['rscdir'], 0, 1 ) === '/' ) {
			$_POST['rscdir'] = substr( $_POST['rscdir'], 1 );
		}
		if ( '/' === $_POST['rscdir'] ) {
			$_POST['rscdir'] = '';
		}

		$newrsccontainer = '';
		if ( ! empty( $_POST['rscusername'] ) && ! empty( $_POST['rscapikey'] ) && ! empty( $_POST['newrsccontainer'] ) ) {
			try {
				$storage_provider = $this->get_rackspace_client(
					[
						'region'         => $_POST['rscregion'],
						'username'       => $_POST['rscusername'],
						'api_key'        => $_POST['rscapikey'],
						'container_name' => $_POST['rsccontainer'],
					]
				);

				$storage_provider->create_container( $_POST['newrsccontainer'] );
				$newrsccontainer = sanitize_text_field( $_POST['newrsccontainer'] );
				// translators: %s: container name.
				BackWPup_Admin::message( sprintf( __( 'Rackspace Cloud container "%s" created.', 'backwpup' ), esc_html( sanitize_text_field( $_POST['newrsccontainer'] ) ) ) );
			} catch ( Exception $e ) {
				// translators: %s: error message.
				BackWPup_Admin::message( sprintf( __( 'Rackspace Cloud API: %s', 'backwpup' ), $e->getMessage() ), true );
			}
		}

		$jobids = (array) $id;
		foreach ( $jobids as $id ) {
			BackWPup_Option::update( $id, 'rscusername', sanitize_text_field( $_POST['rscusername'] ) );
			BackWPup_Option::update( $id, 'rscapikey', sanitize_text_field( $_POST['rscapikey'] ) );
			BackWPup_Option::update( $id, 'rsccontainer', isset( $_POST['rsccontainer'] ) ? sanitize_text_field( $_POST['rsccontainer'] ) : '' );
			BackWPup_Option::update( $id, 'rscregion', ! empty( $_POST['rscregion'] ) ? sanitize_text_field( $_POST['rscregion'] ) : 'DFW' );
			BackWPup_Option::update( $id, 'rscdir', $_POST['rscdir'] );
			BackWPup_Option::update( $id, 'rscmaxbackups', ! empty( $_POST['rscmaxbackups'] ) ? absint( $_POST['rscmaxbackups'] ) : 0 );
			BackWPup_Option::update( $id, 'rscsyncnodelete', ! empty( $_POST['rscsyncnodelete'] ) );
			if ( ! empty( $newrsccontainer ) ) {
				BackWPup_Option::update( $id, 'rsccontainer', $newrsccontainer );
			}
		}
	}
	// phpcs:enable

	/**
	 * Delete file
	 *
	 * @param string $jobdest Job destination.
	 * @param string $backupfile File to be deleted.
	 *
	 * @return void
	 */
	public function file_delete( string $jobdest, string $backupfile ): void {
		$files          = get_site_transient( 'backwpup_' . strtolower( $jobdest ) );
		[$jobid, $dest] = explode( '_', $jobdest );

		$storage_provider = $this->get_rackspace_client(
			[
				'region'         => BackWPup_Option::get( $jobid, 'rscregion' ),
				'username'       => BackWPup_Option::get( $jobid, 'rscusername' ),
				'api_key'        => BackWPup_Option::get( $jobid, 'rscapikey' ) ,
				'container_name' => BackWPup_Option::get( $jobid, 'rsccontainer' ),
			]
		);

		$delete = $storage_provider->delete_object( $backupfile, $jobid );

		if ( $delete ) {
			foreach ( $files as $key => $file ) {
				if ( is_array( $file ) && $file['file'] === $backupfile ) {
					unset( $files[ $key ] );
				}
			}
		}

		$key = 'backwpup_' . strtolower( $jobdest );

		/**
		 * Fires after jobs is updated.
		 *
		 * @since 5.2.1
		 *
		 * @param string $key The newly backup reference key.
		 * @param array $files An array of files data.
		 */
		do_action( 'backwpup_update_backup_history', $key, $files );
	}

	/**
	 * {@inheritdoc}
	 */
	public function file_get_list(string $jobdest): array
	{
		$list = (array) get_site_transient('backwpup_' . strtolower($jobdest));

		return array_filter($list);
	}

	/**
	 * Get Rackspace client.
	 *
	 * @param array $data Rackspace data.
	 *
	 * @return Rackspace
	 */
	private function get_rackspace_client( array $data ) {
		$rackspace_client = new Rackspace(
			[
				'username'       => $data['username'],
				'api_key'        => BackWPup_Encryption::decrypt( $data['api_key'] ),
				'container_name' => $data['container_name'],
				'region'         => $data['region'],
			],
		);

		$rackspace_client->initialise();

		return $rackspace_client;
	}

	/**
	 * Upload backup to cloud storage.
	 *
	 * @param BackWPup_Job $job_object Job Object.
	 * @param Rackspace    $storage_provider Storage provider.
	 *
	 * @return bool
	 */
	public function upload_backup( BackWPup_Job $job_object, Rackspace $storage_provider ): bool {
		try {
			// translators: %s: container name.
			$job_object->log( sprintf( __( 'Connected to Rackspace cloud files container %s', 'backwpup' ), $job_object->job['rsccontainer'] ) );

			$job_object->log( __( 'Upload to Rackspace cloud started &hellip;', 'backwpup' ), E_USER_NOTICE );

			$upload = $storage_provider->upload_object(
				$job_object->job['rscdir'] . $job_object->backup_file,
				$job_object->backup_folder . $job_object->backup_file
			);
			if ( ! $upload ) {
				$job_object->log( __( 'Cannot transfer backup to Rackspace cloud.', 'backwpup' ), E_USER_ERROR );
				return false;
		    }

			$modified_backup_name = $job_object->job['rscdir'] . $job_object->backup_file;
			$job_object->log( __( 'Backup File transferred to RSC://', 'backwpup' ) . $job_object->job['rsccontainer'] . '/' . $modified_backup_name, E_USER_NOTICE );
			$job_object->substeps_done = 1 + $job_object->backup_filesize;

			if ( ! empty( $job_object->job['jobid'] ) ) {
				BackWPup_Option::update(
					$job_object->job['jobid'],
					'lastbackupdownloadurl',
					network_admin_url( 'admin.php' ) . '?page=backwpupbackups&action=downloadrsc&file=' . $modified_backup_name . '&jobid=' . $job_object->job['jobid']
				);
			}
		} catch ( Exception $e ) {
			// translators: %s: Error message.
			$job_object->log( E_USER_ERROR, sprintf( __( 'Rackspace Cloud API: %s', 'backwpup' ), $e->getMessage() ), $e->getFile(), $e->getLine() );

		    return false;
	    }

        return true;
    }

	/**
	 * Handle cleanup, deletion after backup is uploaded to cloud storage.
	 *
	 * @param BackWPup_Job $job_object Job Object.
	 * @param Rackspace    $storage_provider Storage provider.
	 *
	 * @return bool
	 */
	public function cleanup_after_backup_upload( BackWPup_Job $job_object, Rackspace $storage_provider ): bool {
		try {
			$backupfilelist = [];
			$filecounter    = 0;
			$files          = [];
			$object_list    = $storage_provider->object_list(
				[
					'prefix'    => $job_object->job['rscdir'],
					'delimiter' => '/',
				]
				);

			foreach ( $object_list as $object ) {
				if ( isset( $object['subdir'] ) ) {
					continue;
				}

				$file = basename( (string) $object['name'] );

				if ( $job_object->job['rscdir'] . $file === $object['name'] ) { // only in the folder and not in complete bucket.
					if ( $this->is_backup_archive( $file ) && $this->is_backup_owned_by_job( $file, $job_object->job['jobid'] ) === true ) {
						$backupfilelist[ strtotime( (string) $object['last_modified'] ) ] = $object;
					}
				}

				$files[ $filecounter ]['folder']      = 'RSC://' . $job_object->job['rsccontainer'] . '/' . dirname( (string) $object['name'] ) . '/';
				$files[ $filecounter ]['file']        = $object['name'];
				$files[ $filecounter ]['filename']    = basename( (string) $object['name'] );
				$files[ $filecounter ]['downloadurl'] = network_admin_url( 'admin.php' ) . '?page=backwpupbackups&action=downloadrsc&file=' . $object['name'] . '&jobid=' . $job_object->job['jobid'];
				$files[ $filecounter ]['filesize']    = $object['bytes'];
				$files[ $filecounter ]['time']        = strtotime( (string) $object['last_modified'] );
				++$filecounter;
			}

			if ( ! empty( $job_object->job['rscmaxbackups'] ) && $job_object->job['rscmaxbackups'] > 0 ) { // Delete old backups.
				if ( count( $backupfilelist ) > $job_object->job['rscmaxbackups'] ) {
					ksort( $backupfilelist );
					$numdeltefiles = 0;
					$deleted_files = [];

				    while ($file = array_shift($backupfilelist)) {
					    if (count($backupfilelist) < $job_object->job['rscmaxbackups']) {
						    break;
					    }

						foreach ( $files as $key => $filedata ) {
							if ( $filedata['file'] === $file['name'] ) {
								$deleted_files[] = $filedata['filename'];
								unset( $files[ $key ] );
							}
						}
						$storage_provider->delete_object( $file['name'], (string) $job_object->job['jobid'] );
						++$numdeltefiles;
					}
					if ( $numdeltefiles > 0 ) {
						$job_object->log(
								sprintf(
								// Translators: %d is the number of files deleted from the Rackspace cloud container.
										_n(
											'%d file deleted on Rackspace cloud container.',
											'%d files deleted on Rackspace cloud container.',
											$numdeltefiles,
											'backwpup'
										),
										$numdeltefiles
								),
								E_USER_NOTICE
						);
					}

					parent::remove_file_history_from_database( $deleted_files, 'RSC' );
				}
			}
			set_site_transient( 'backwpup_' . $job_object->job['jobid'] . '_rsc', $files, YEAR_IN_SECONDS );
		} catch ( Exception $e ) {
			// translators: %s: Error message.
			$job_object->log( E_USER_ERROR, sprintf( __( 'Rackspace Cloud API: %s', 'backwpup' ), $e->getMessage() ), $e->getFile(), $e->getLine() );

		    return false;
	    }

        return true;
    }

	public function job_run_archive(BackWPup_Job $job_object): bool
	{
		$job_object->substeps_todo = 2 + $job_object->backup_filesize;
		$job_object->substeps_done = 0;
		$job_object->log(sprintf(__('%d. Trying to send backup file to Rackspace cloud &hellip;', 'backwpup'), $job_object->steps_data[$job_object->step_working]['STEP_TRY']), E_USER_NOTICE);

		$storage_provider = $this->get_rackspace_client(
			[
				'region'         => $job_object->job['rscregion'],
				'username'       => $job_object->job['rscusername'],
				'api_key'        => $job_object->job['rscapikey'] ,
				'container_name' => $job_object->job['rsccontainer'],
			]
			);

		if ( ! $this->upload_backup( $job_object, $storage_provider ) ) {
			return false;
        }

		if ( ! $this->cleanup_after_backup_upload( $job_object, $storage_provider ) ) {
			return false;
        }

		++$job_object->substeps_done;

		return true;
	}

	/**
	 * @param array $job_settings array
	 */
	public function can_run(array $job_settings): bool
	{
		if (empty($job_settings['rscusername'])) {
			return false;
		}

		if (empty($job_settings['rscapikey'])) {
			return false;
		}

		return !(empty($job_settings['rsccontainer']));
	}

	public function edit_inline_js(): void
	{
		?>
		<script type="text/javascript">
			jQuery(document).ready(function ($) {
				function rscgetcontainer() {
					var data = {
						action: 'backwpup_dest_rsc',
						rscusername: $('#rscusername').val(),
						rscapikey: $('#rscapikey').val(),
						rscregion: $('#rscregion').val(),
						rscselected: $('#rsccontainer').val(),
						_ajax_nonce: $('#backwpupajaxnonce').val()
					};
					$.post(ajaxurl, data, function (response) {
						$('#rscbucketContainer').html(response);
					});
				}

				$('#rscregion').on('change', function () {
					rscgetcontainer();
				});
				$('#rscusername').backwpupDelayKeyup(function () {
					rscgetcontainer();
				});
				$('#rscapikey').backwpupDelayKeyup(function () {
					rscgetcontainer();
				});
			});
		</script>
		<?php
	}

	/**
	 * Edit ajax
	 *
	 * @param array $args Storage data like username, containers, region etc.
	 *
	 * @return void
	 */
	public function edit_ajax( array $args = [] ): void {
		$error = '';
		$ajax = false;

		if (isset($_POST['rscusername']) || isset($_POST['rscapikey'])) {
			if (!current_user_can('backwpup_jobs_edit')) {
				wp_die(-1);
			}
			check_ajax_referer('backwpup_ajax_nonce');
			$args['rscusername'] = sanitize_text_field($_POST['rscusername']);
			$args['rscapikey'] = sanitize_text_field($_POST['rscapikey']);
			$args['rscselected'] = sanitize_text_field($_POST['rscselected']);
			$args['rscregion'] = sanitize_text_field($_POST['rscregion']);
			$ajax = true;
		}
		echo '<span id="rsccontainererror" class="bwu-message-error">';

		$container_list = [];
		if ( ! empty( $args['rscusername'] ) && ! empty( $args['rscapikey'] ) && ! empty( $args['rscregion'] ) ) {
			try {
				$storage_provider = $this->get_rackspace_client(
					[
						'region'         => $args['rscregion'],
						'username'       => $args['rscusername'],
						'api_key'        => $args['rscapikey'],
						'container_name' => '', // No container yet, so this will be blank.
					]
				);

				$containers = $storage_provider->get_containers();
				if ( ! empty( $containers ) ) {
					foreach ( $containers as $container ) {
						$container_list[] = $container['name'];
					}
                }
			} catch (Exception $e) {
				$error = $e->getMessage();
			}
		}

		if (empty($args['rscusername'])) {
			_e('Missing username!', 'backwpup');
		} elseif (empty($args['rscapikey'])) {
			_e('Missing API Key!', 'backwpup');
		} elseif (!empty($error)) {
			echo esc_html($error);
		} elseif (empty($container_list)) {
			_e('A container could not be found!', 'backwpup');
		}
		echo '</span>';

		if ( ! empty( $container_list ) ) {
			$mapped_containers = array_combine( $container_list, $container_list );
			echo BackWPupHelpers::component( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				'form/select',
				[
					'name'       => 'rsccontainer',
					'identifier' => 'rsccontainer',
					'label'      => esc_html__( 'Container selection', 'backwpup' ),
					'withEmpty'  => false,
					'value'      => $args['rscselected'], // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'options'    => $mapped_containers, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				]
			);
		}

		if ( $ajax ) {
			wp_die();
		}
	}
}
