<?php

require_once __DIR__ . '/class-destination-sugarsync-api-exception.php';

class BackWPup_Destination_SugarSync extends BackWPup_Destinations {

	/**
	 * Service name
	 *
	 * @var string
	 */
	private const SERVICE_NAME = 'SugarSync';

	/**
	 * BackWPup_Job Object
	 *
	 * @var BackWPup_Job Object.
	 */
	public static $backwpup_job_object = null;

	/**
	 * Get default options for SugarSync.
	 *
	 * @return array
	 */
	public function option_defaults(): array {
		return [
			'sugarrefreshtoken' => '',
			'sugarroot'         => '',
			'sugardir'          => trailingslashit( sanitize_file_name( get_bloginfo( 'name' ) ) ),
			'sugarmaxbackups'   => 15,
		];
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param int|array $jobid Job id or list of job ids.
	 *
	 * @return void
	 *
	 * @phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	 */
	public function edit_form_post_save( $jobid ): void {
		$jobids = (array) $jobid;

		if ( ! empty( $_POST['sugaremail'] ) && ! empty( $_POST['sugarpass'] ) && __( 'Authenticate with Sugarsync!', 'backwpup' ) === $_POST['authbutton'] ) {
			try {
				$sugarsync     = new BackWPup_Destination_SugarSync_API();
				$refresh_token = $sugarsync->get_refresh_token( sanitize_email( $_POST['sugaremail'] ), $_POST['sugarpass'] );
				if ( ! empty( $refresh_token ) ) {
					foreach ( $jobids as $jobid ) {
							BackWPup_Option::update( $jobid, 'sugarrefreshtoken', $refresh_token );
					}
				}
			} catch ( Exception $e ) {
				BackWPup_Admin::message( 'SUGARSYNC: ' . $e->getMessage(), true );
			}
		}

		if ( isset( $_POST['authbutton'] ) && __( 'Delete Sugarsync authentication!', 'backwpup' ) === $_POST['authbutton'] ) {
			foreach ( $jobids as $jobid ) {
					BackWPup_Option::delete( $jobid, 'sugarrefreshtoken' );
			}
		}

		if ( isset( $_POST['authbutton'] ) && __( 'Create Sugarsync account', 'backwpup' ) === $_POST['authbutton'] ) {
			try {
				$sugarsync = new BackWPup_Destination_SugarSync_API();
				$sugarsync->create_account( sanitize_email( $_POST['sugaremail'] ), $_POST['sugarpass'] );
			} catch ( Exception $e ) {
				BackWPup_Admin::message( 'SUGARSYNC: ' . $e->getMessage(), true );
			}
		}

		$_POST['sugardir'] = trailingslashit( str_replace( '//', '/', str_replace( '\\', '/', trim( sanitize_text_field( $_POST['sugardir'] ) ) ) ) );
		if ( '/' === substr( $_POST['sugardir'], 0, 1 ) ) {
			$_POST['sugardir'] = substr( $_POST['sugardir'], 1 );
		}
		if ( '/' === $_POST['sugardir'] ) {
			$_POST['sugardir'] = '';
		}
		foreach ( $jobids as $jobid ) {
				BackWPup_Option::update( $jobid, 'sugardir', $_POST['sugardir'] );

				BackWPup_Option::update( $jobid, 'sugarroot', isset( $_POST['sugarroot'] ) ? sanitize_text_field( $_POST['sugarroot'] ) : '' );
				BackWPup_Option::update( $jobid, 'sugarmaxbackups', isset( $_POST['sugarmaxbackups'] ) && is_numeric( $_POST['sugarmaxbackups'] ) ? absint( $_POST['sugarmaxbackups'] ) : $this->option_defaults()['sugarmaxbackups'] );
		}
	}
	// phpcs:enable

	/**
	 * Delete a file from SugarSync.
	 *
	 * @param string $jobdest    Job destination string.
	 * @param string $backupfile Backup file path.
	 *
	 * @return void
	 */
	public function file_delete( string $jobdest, string $backupfile ): void {
		$files          = get_site_transient( 'backwpup_' . strtolower( $jobdest ) );
		[$jobid, $dest] = explode( '_', $jobdest );

		if ( BackWPup_Option::get( $jobid, 'sugarrefreshtoken' ) ) {
			try {
				$sugarsync = new BackWPup_Destination_SugarSync_API( BackWPup_Option::get( $jobid, 'sugarrefreshtoken' ) );
				$sugarsync->delete( urldecode( $backupfile ) );
				// update the file list.
				if ( $files ) {
					foreach ( $files as $key => $file ) {
						if ( is_array( $file ) && $file['file'] === $backupfile ) {
							unset( $files[ $key ] );
						}
					}
					set_site_transient( 'backwpup_' . strtolower( $jobdest ), $files, YEAR_IN_SECONDS );
				}
				unset( $sugarsync );
			} catch ( Exception $e ) {
				BackWPup_Admin::message( 'SUGARSYNC: ' . $e->getMessage(), true );
			}
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param string $jobdest Job destination identifier.
	 */
	public function file_get_list( string $jobdest ): array {
		[$job_id, $dest] = explode( '_', $jobdest );

		if ( ! $job_id ) {
			return [];
		}

		$list = get_site_transient( 'backwpup_' . strtolower( $jobdest ) );
		if ( false === $list ) {
			return [];
		}

		foreach ( $list as $index => &$file ) {
			if ( ! $file['file'] ) {
				continue;
			}
			$file['restoreurl'] = add_query_arg(
				[
					'page'         => 'backwpuprestore',
					'action'       => 'restore-destination_sugarsync',
					'file'         => $file['file'],
					'restore_file' => (string) $file['filename'],
					'jobid'        => (int) $job_id,
					'service'      => 'sugarsync',
				],
				network_admin_url( 'admin.php' )
			);
		}

		return array_filter( $list );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param BackWPup_Job $job_object Job object.
	 */
	public function job_run_archive( BackWPup_Job $job_object ): bool {
		self::$backwpup_job_object = $job_object;
		$job_object->substeps_todo = 2 + $job_object->backup_filesize;
		$job_object->log(
			sprintf(
			/* translators: %d: attempt number. */
			__( '%d. Try to send backup to SugarSync&#160;&hellip;', 'backwpup' ),
			$job_object->steps_data[ $job_object->step_working ]['STEP_TRY']
		),
			E_USER_NOTICE
			);

		try {
			$sugarsync = new BackWPup_Destination_SugarSync_API( $job_object->job['sugarrefreshtoken'] );
			// Check quota.
			$user = $sugarsync->user();
			if ( ! empty( $user->nickname ) ) {
				$job_object->log(
					sprintf(
					/* translators: %s: SugarSync nickname. */
					__( 'Authenticated to SugarSync with nickname %s', 'backwpup' ),
					$user->nickname
				),
					E_USER_NOTICE
					);
			}
			$sugarsyncfreespase = (float) $user->quota->limit - (float) $user->quota->usage; // Float fixes bug for display of no free space.
			if ( $job_object->backup_filesize > $sugarsyncfreespase ) {
				$job_object->log(
					sprintf(
					/* translators: %s: available space on SugarSync. */
					_x( 'Not enough disk space available on SugarSync. Available: %s.', 'Available space on SugarSync', 'backwpup' ),
					size_format( $sugarsyncfreespase, 2 )
				),
					E_USER_ERROR,
					__FILE__,
					__LINE__,
					[
						'reason_code'   => 'not_enough_storage',
						'destination'   => 'SUGARSYNC',
						'provider_code' => 'quota_exceeded',
					]
					);
				$job_object->substeps_todo = 1 + $job_object->backup_filesize;

				return true;
			}

			// translators: %s: available space on SugarSync.
			$job_object->log( sprintf( __( '%s available at SugarSync', 'backwpup' ), size_format( $sugarsyncfreespase, 2 ) ) );

			// Create and change folder.
			$sugarsync->mkdir( $job_object->job['sugardir'], $job_object->job['sugarroot'] );
			$dirid = $sugarsync->chdir( $job_object->job['sugardir'], $job_object->job['sugarroot'] );
			// Upload to SugarSync.
			$job_object->substeps_done = 0;
			$job_object->log( __( 'Starting upload to SugarSync&#160;&hellip;', 'backwpup' ) );

			$response = $sugarsync->upload( $job_object->backup_folder . $job_object->backup_file );

			if ( is_object( $response ) ) {
				if ( ! empty( $job_object->job['jobid'] ) ) {
					BackWPup_Option::update(
						$job_object->job['jobid'],
						'lastbackupdownloadurl',
						sprintf(
							'%s?page=backwpupbackups&action=downloadsugarsync&file=%s&local_file=%s&jobid=%d',
							network_admin_url( 'admin.php' ),
							(string) $response,
							$job_object->backup_file,
							$job_object->job['jobid']
						)
					);
				}
				++$job_object->substeps_done;
				$job_object->log(
					sprintf(
					/* translators: %s: destination path. */
					__( 'Backup transferred to %s', 'backwpup' ),
					'https://' . $user->nickname . '.sugarsync.com/' . $sugarsync->showdir( $dirid ) . $job_object->backup_file
				),
					E_USER_NOTICE
					);
			} else {
				$job_object->log( __( 'Cannot transfer backup to SugarSync!', 'backwpup' ), E_USER_ERROR );

				return false;
			}

			$backupfilelist = [];
			$files          = [];
			$filecounter    = 0;
			$dir            = $sugarsync->showdir( $dirid );
			$getfiles       = $sugarsync->getcontents( 'file' );
			if ( is_object( $getfiles ) ) {
				foreach ( $getfiles->file as $getfile ) {
					$getfile_vars  = get_object_vars( $getfile );
					$display_name  = isset( $getfile_vars['displayName'] ) ? utf8_decode( (string) $getfile_vars['displayName'] ) : '';
					$last_modified = isset( $getfile_vars['lastModified'] ) ? (string) $getfile_vars['lastModified'] : '';
					if ( $this->is_backup_archive( $display_name ) && $this->is_backup_owned_by_job( $display_name, $job_object->job['jobid'] ) ) {
						$backupfilelist[ strtotime( $last_modified ) ] = (string) $getfile->ref;
					}
					$files[ $filecounter ]['folder']      = 'https://' . (string) $user->nickname . '.sugarsync.com/' . $dir;
					$files[ $filecounter ]['file']        = (string) $getfile->ref;
					$files[ $filecounter ]['filename']    = $display_name;
					$files[ $filecounter ]['downloadurl'] = sprintf(
						'%s?page=backwpupbackups&action=downloadsugarsync&file=%s&local_file=%s&jobid=%d',
						network_admin_url( 'admin.php' ),
						(string) $getfile->ref,
						$display_name,
						$job_object->job['jobid']
					);
					$files[ $filecounter ]['filesize']    = (int) $getfile->size;
					$files[ $filecounter ]['time']        = strtotime( $last_modified ) + ( get_option( 'gmt_offset' ) * 3600 );
					++$filecounter;
				}
			}
			if ( ! empty( $job_object->job['sugarmaxbackups'] ) && $job_object->job['sugarmaxbackups'] > 0 ) { // Delete old backups.
				if ( count( $backupfilelist ) > $job_object->job['sugarmaxbackups'] ) {
					ksort( $backupfilelist );
					$numdeltefiles = 0;
					$deleted_files = [];

					while ( ! empty( $backupfilelist ) ) {
						$file = array_shift( $backupfilelist );
						if ( count( $backupfilelist ) < $job_object->job['sugarmaxbackups'] ) {
							break;
						}
						$sugarsync->delete( $file ); // Delete files on Cloud.

						foreach ( $files as $key => $filedata ) {
							if ( $filedata['file'] === $file ) {
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
									'%d file deleted on SugarSync folder',
									'%d files deleted on SugarSync folder',
									$numdeltefiles,
									'backwpup'
								),
								$numdeltefiles
							),
							E_USER_NOTICE
						);
					}

					parent::remove_file_history_from_database( $deleted_files, 'SUGARSYNC' );
				}
			}
			set_site_transient( 'BackWPup_' . $job_object->job['jobid'] . '_SUGARSYNC', $files, YEAR_IN_SECONDS );
		} catch ( Exception $e ) {
			$context = $this->sugarsync_error_context( $e->getMessage() );
			$job_object->log(
				sprintf(
				/* translators: %s: error message. */
				__( 'SugarSync API: %s', 'backwpup' ),
				$e->getMessage()
			),
				E_USER_ERROR,
				$e->getFile(),
				$e->getLine(),
				$context
				);

			return false;
		}
		++$job_object->substeps_done;

		return true;
	}

	/**
	 * Check if SugarSync destination can run.
	 *
	 * @param array $job_settings Job settings.
	 *
	 * @return bool
	 */
	public function can_run( array $job_settings ): bool {
		if ( empty( $job_settings['sugarrefreshtoken'] ) ) {
			return false;
		}

		return ! ( empty( $job_settings['sugarroot'] ) );
	}

	/**
	 * Get service name.
	 *
	 * @return string
	 */
	public function get_service_name(): string {
		return self::SERVICE_NAME;
	}

	/**
	 * Build error context for SugarSync errors.
	 *
	 * @param string $message Error message.
	 * @return array
	 */
	private function sugarsync_error_context( string $message ): array {
		$normalized = strtolower( $message );
		$status     = '';

		if ( preg_match( '/\((\d{3})\)/', $message, $matches ) ) {
			$status = $matches[1];
		}

		if (
			'401' === $status
			|| '403' === $status
			|| false !== strpos( $normalized, 'unauthorized' )
			|| false !== strpos( $normalized, 'authentication' )
		) {
			return [
				'reason_code'   => 'incorrect_login',
				'destination'   => 'SUGARSYNC',
				'provider_code' => $status ?: 'auth_failed',
			];
		}

		if (
			false !== strpos( $normalized, 'quota' )
			|| false !== strpos( $normalized, 'insufficient' )
			|| false !== strpos( $normalized, 'not enough' )
		) {
			return [
				'reason_code'   => 'not_enough_storage',
				'destination'   => 'SUGARSYNC',
				'provider_code' => $status ?: 'quota_exceeded',
			];
		}

		return [];
	}
}
