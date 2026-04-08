<?php

class BackWPup_Destination_Ftp extends BackWPup_Destinations {

	/**
	 * Service name
	 *
	 * @var string
	 */
	private const SERVICE_NAME = 'FTP';

	/**
	 * Returns default FTP options.
	 *
	 * @return array<string, mixed> Default options.
	 */
	public function option_defaults(): array {
		return [
			'ftphost'       => '',
			'ftphostport'   => 21,
			'ftptimeout'    => 90,
			'ftpuser'       => '',
			'ftppass'       => '',
			'ftpdir'        => trailingslashit( sanitize_title_with_dashes( get_bloginfo( 'name' ) ) ),
			'ftpmaxbackups' => 15,
			'ftppasv'       => true,
			'ftpssl'        => false,
			'ftpssh'        => false,
			'ftpsshprivkey' => '',
		];
	}


	/**
	 * {@inheritDoc}
	 *
	 * @param int|array $id
	 *
	 * @return void
	 *
	 * @phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
	 */
	public function edit_form_post_save( $id ): void {
				$jobids = (array) $id;

		$_POST['ftphost'] = str_replace( [ 'http://', 'ftp://' ], '', sanitize_text_field( $_POST['ftphost'] ) );
		if ( ! empty( $_POST['ftpdir'] ) ) {
				$_POST['ftpdir'] = trailingslashit(
					str_replace( '//', '/', str_replace( '\\', '/', trim( sanitize_text_field( $_POST['ftpdir'] ) ) ) )
				);
		}
		foreach ( $jobids as $id ) {
				BackWPup_Option::update( $id, 'ftphost', $_POST['ftphost'] ?? '' );
				BackWPup_Option::update(
					$id,
					'ftphostport',
					! empty( $_POST['ftphostport'] ) ? absint( $_POST['ftphostport'] ) : 21
				);
				BackWPup_Option::update(
					$id,
					'ftptimeout',
					! empty( $_POST['ftptimeout'] ) ? absint( $_POST['ftptimeout'] ) : 90
				);
				BackWPup_Option::update( $id, 'ftpuser', sanitize_text_field( $_POST['ftpuser'] ) );
				BackWPup_Option::update( $id, 'ftppass', BackWPup_Encryption::encrypt( $_POST['ftppass'] ) );
				BackWPup_Option::update( $id, 'ftpdir', $_POST['ftpdir'] );
				BackWPup_Option::update(
					$id,
					'ftpmaxbackups',
					isset( $_POST['ftpmaxbackups'] ) && is_numeric( $_POST['ftpmaxbackups'] ) ? absint( $_POST['ftpmaxbackups'] ) : $this->option_defaults()['ftpmaxbackups']
				);
			if ( function_exists( 'ftp_ssl_connect' ) ) {
				BackWPup_Option::update( $id, 'ftpssl', ! empty( $_POST['ftpssl'] ) );
			} else {
				BackWPup_Option::update( $id, 'ftpssl', false );
			}
			if ( class_exists( phpseclib3\Net\SFTP::class ) ) {
				BackWPup_Option::update( $id, 'ftpssh', ! empty( $_POST['ftpssh'] ) );
			} else {
				BackWPup_Option::update( $id, 'ftpssh', false );
			}
				BackWPup_Option::update( $id, 'ftppasv', ! empty( $_POST['ftppasv'] ) );
			if ( ! empty( $_POST['ftpsshprivkey'] ) ) {
				BackWPup_Option::update( $id, 'ftpsshprivkey', BackWPup_Encryption::encrypt( wp_unslash( trim( $_POST['ftpsshprivkey'] ) ) ) );
			}
		}
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $jobdest    Job destination identifier.
	 * @param string $backupfile Backup file name.
	 *
	 * @return void
	 */
	public function file_delete( string $jobdest, string $backupfile ): void {
		$files          = $this->file_get_list( $jobdest );
		[$jobid, $dest] = explode( '_', $jobdest );

		$job_options = (object) BackWPup_Option::get_job( $jobid );

		if ( ! empty( $job_options->ftpssh ) && BackWPup::is_pro() ) {
			$ftp = new BackWPup_Pro_Destination_Ftp_Type_Sftp();
		} else {
			$ftp = new BackWPup_Destination_Ftp_Type_Ftp();
		}

		$deleted_files = [];
		try {
			$ftp->connect(
				$job_options->ftpuser,
				BackWPup_Encryption::decrypt( $job_options->ftppass ),
				$job_options->ftphost,
				[
					'port'    => $job_options->ftphostport,
					'timeout' => $job_options->ftptimeout,
					'ssl'     => ! empty( $job_options->ftpssl ),
					'pasv'    => ! empty( $job_options->ftppasv ),
					'privkey' => ! empty( $job_options->ftpsshprivkey ) ? BackWPup_Encryption::decrypt( $job_options->ftpsshprivkey ) : '',
				]
			);

			$ftp->delete( $backupfile );
			foreach ( $files as $key => $file ) {
				if ( is_array( $file ) && $file['file'] === $backupfile ) {
					unset( $files[ $key ] );
				}
			}
			$deleted_files[] = $backupfile;
		} catch ( \Exception $e ) {
			BackWPup_Admin::message( 'FTP: ' . $e->getMessage(), true );
		}

		$this->remove_file_history_from_database( $deleted_files, 'FTP' );

		set_site_transient( 'backwpup_' . strtolower( $jobdest ), $files, YEAR_IN_SECONDS );
	}

	/**
	 * Upload the backup archive to FTP.
	 *
	 * @param BackWPup_Job $job_object Job object.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function job_run_archive( BackWPup_Job $job_object ): bool {
		$job_object->substeps_todo = 2 + $job_object->backup_filesize;
		if ( $job_object->steps_data[ $job_object->step_working ]['SAVE_STEP_TRY'] !== $job_object->steps_data[ $job_object->step_working ]['STEP_TRY'] ) {
			$job_object->log(
				sprintf(
					// translators: %d: try number.
					__( '%d. Try to send backup file to an FTP server&#160;&hellip;', 'backwpup' ),
					$job_object->steps_data[ $job_object->step_working ]['STEP_TRY']
				)
			);
		}

		if ( ! empty( $job_object->job['ftpssh'] ) && BackWPup::is_pro() ) {
			$ftp = new BackWPup_Pro_Destination_Ftp_Type_Sftp( [ $job_object, 'log' ] );
		} else {
			$ftp = new BackWPup_Destination_Ftp_Type_Ftp( [ $job_object, 'log' ] );
		}
		try {
			$ftp->connect(
				$job_object->job['ftpuser'],
				BackWPup_Encryption::decrypt( $job_object->job['ftppass'] ),
				$job_object->job['ftphost'],
				[
					'port'    => $job_object->job['ftphostport'],
					'timeout' => $job_object->job['ftptimeout'],
					'ssl'     => ! empty( $job_object->job['ftpssl'] ),
					'pasv'    => ! empty( $job_object->job['ftppasv'] ),
					'privkey' => ! empty( $job_object->job['ftpsshprivkey'] ) ? BackWPup_Encryption::decrypt( $job_object->job['ftpsshprivkey'] ) : '',
				]
			);

			$current_ftp_dir = trailingslashit( $ftp->chdir( $job_object->job['ftpdir'] ) );
			if ( ! $job_object->substeps_done ) {
				$job_object->log(
					// translators: %s: current FTP folder.
					sprintf( __( 'FTP current folder is: %s', 'backwpup' ), $current_ftp_dir )
				);
			}

			// upload backup file.
			$job_object->substeps_done = $ftp->size( $current_ftp_dir . $job_object->backup_file );
			if ( $job_object->substeps_done < $job_object->backup_filesize ) {
				$job_object->log( __( 'Starting upload to FTP &#160;&hellip;', 'backwpup' ) );
				// get file size to resume upload and check if appending works.
				if ( ! $ftp->supports_appending() ) {
					$job_object->substeps_done = 0;
					$job_max_execution_time    = get_site_option( 'backwpup_cfg_jobmaxexecutiontime' );
					if ( $job_max_execution_time ) {
						$job_object->log( __( 'Looks like the FTP Server do not support upload files in chunks. Try to upload it at once without restarts.', 'backwpup' ), E_USER_WARNING );
					}
				}
				$fp = fopen( $job_object->backup_folder . $job_object->backup_file, 'rb' ); //phpcs:ignore
				fseek( $fp, $job_object->substeps_done );
				$continue                  = $ftp->upload( $current_ftp_dir . $job_object->backup_file, $fp );
				$job_object->substeps_done = ftell( $fp );
				while ( $continue ) {
					$job_object->update_working_data();
					if ( $ftp->supports_appending() ) {
						$job_object->do_restart_time();
					}
					$continue                  = $ftp->upload( $current_ftp_dir . $job_object->backup_file, $fp );
					$job_object->substeps_done = ftell( $fp );
				}
				fclose( $fp ); //phpcs:ignore

				// check backup file size.
				if ( $job_object->backup_filesize !== $job_object->substeps_done ) {
					$job_object->log( __( 'Backup not correctly transferred to FTP server! Filesize not match.', 'backwpup' ), E_USER_ERROR );
					return false;
				}

				$job_object->substeps_done = $job_object->backup_filesize + 1;
				$job_object->log(
					sprintf(
					// translators: %s: backup file name.
						__( 'Backup transferred to FTP server: %s', 'backwpup' ),
						$current_ftp_dir . $job_object->backup_file
					)
				);
				if ( ! empty( $job_object->job['jobid'] ) ) {
					BackWPup_Option::update(
						$job_object->job['jobid'],
						'lastbackupdownloadurl',
						network_admin_url(
							'admin.php?page=backwpupbackups&action=downloadftp&file=' .
							$current_ftp_dir . $job_object->backup_file . '&local_file=' .
							$job_object->backup_file . '&jobid=' . $job_object->job['jobid']
						)
					);
				}
			}

			// get list of backups on FTP server.
			$job_object->do_restart_time();
			$backup_file_list = $ftp->list_files( $current_ftp_dir );
			foreach ( $backup_file_list as $key => $file ) {
				if ( ! $this->is_backup_archive( $file['file'] ) || ! $this->is_backup_owned_by_job( $file['file'], $job_object->job['jobid'] ) ) {
					unset( $backup_file_list[ $key ] );
				} else {
					$backup_file_list[ $key ]['folder']      = $ftp->connection_url . '/' . ltrim( $file['file'], '/' );
					$backup_file_list[ $key ]['downloadurl'] = network_admin_url(
						'admin.php?page=backwpupbackups&action=downloadftp&file=' . $file['file'] . '&local_file=' . $file['filename'] . '&jobid=' . $job_object->job['jobid']
					);
				}
			}

			// Delete old backups.
			if ( $backup_file_list && ! empty( $job_object->job['ftpmaxbackups'] ) && $job_object->job['ftpmaxbackups'] > 0 && count( $backup_file_list ) > $job_object->job['ftpmaxbackups'] ) {
				ksort( $backup_file_list );
				$backups_to_delete = array_slice( $backup_file_list, 0, count( $backup_file_list ) - $job_object->job['ftpmaxbackups'] );
				if ( $backups_to_delete ) {
					$deleted_files = [];
					foreach ( $backups_to_delete as $file ) {
						if ( $ftp->delete( $file['file'] ) ) {
							foreach ( $backup_file_list as $index => $file_data ) {
								if ( $file_data === $file ) {
									unset( $backup_file_list[ $index ] );
								}
							}
							$deleted_files[] = $file['file'];
						} else {
							$job_object->log(
								sprintf(
									// translators: %s: file name.
									__( 'Cannot delete "%s" on FTP server!', 'backwpup' ),
									$job_object->job['ftpdir'] . $file
								),
								E_USER_ERROR
							);
						}
					}
					if ( count( $deleted_files ) > 0 ) {
						$job_object->log(
							sprintf(
								// translators: %d: number of deleted files.
								_n(
									'One file deleted on FTP server', //phpcs:ignore
									'%d files deleted on FTP server',
									count( $deleted_files ),
									'backwpup'
								),
								count( $deleted_files )
							)
						);
					}

					$this->remove_file_history_from_database( $deleted_files, 'FTP' );
				}
			}

			set_site_transient( 'backwpup_' . $job_object->job['jobid'] . '_ftp', $backup_file_list, YEAR_IN_SECONDS );
			++$job_object->substeps_done;

			$ftp->disconnect();
		} catch ( Exception $e ) {
			$context = $this->ftp_error_context( $e->getMessage() );
			$job_object->log(
				/* translators: %s: FTP error message. */
				sprintf( esc_html__( 'FTP server error: %s', 'backwpup' ), $e->getMessage() ),
				E_USER_ERROR,
				$e->getFile(),
				$e->getLine(),
				$context
			);
			$ftp->disconnect();
			if ( $fp && is_resource( $fp ) ) {
				fclose( $fp ); //phpcs:ignore
			}
			return false;
		}

		return true;
	}

	/**
	 * Test if the job can run.
	 *
	 * @param array $job_settings
	 * @return boolean
	 */
	public function can_run( array $job_settings ): bool {
		if ( empty( $job_settings['ftphost'] ) ) {
			return false;
		}

		if ( empty( $job_settings['ftpuser'] ) ) {
			return false;
		}

		if ( ! empty( $job_settings['ftpssh'] ) && BackWPup::is_pro() ) {
			$ftp = new BackWPup_Pro_Destination_Ftp_Type_Sftp();
		} else {
			$ftp = new BackWPup_Destination_Ftp_Type_Ftp();
		}
		try {
			$login = $ftp->connect(
				$job_settings['ftpuser'],
				BackWPup_Encryption::decrypt( $job_settings['ftppass'] ),
				$job_settings['ftphost'],
				[
					'port'    => $job_settings['ftphostport'],
					'timeout' => $job_settings['ftptimeout'],
					'ssl'     => ! empty( $job_settings['ftpssl'] ),
					'pasv'    => ! empty( $job_settings['ftppasv'] ),
					'privkey' => ! empty( $job_settings['ftpsshprivkey'] ) ? BackWPup_Encryption::decrypt( $job_settings['ftpsshprivkey'] ) : '',
				]
			);
			if ( $login ) {
				$ftp->disconnect();
			}
			return $login;
		} catch ( Exception $e ) {
			$ftp->disconnect();
			return false;
		}
	}

	/**
	 * Get service name
	 */
	public function get_service_name(): string {
		return self::SERVICE_NAME;
	}

	/**
	 * Build error context for FTP errors.
	 *
	 * @param string $message FTP error message.
	 * @return array
	 */
	private function ftp_error_context( string $message ): array {
		$normalized = strtolower( $message );
		$code       = '';

		if ( preg_match( '/\b(4|5)\d{2}\b/', $message, $matches ) ) {
			$code = $matches[0];
		}

		if (
			'530' === $code
			|| false !== strpos( $normalized, 'login' )
			|| false !== strpos( $normalized, 'not logged' )
			|| false !== strpos( $normalized, 'authentication' )
		) {
			return [
				'reason_code'   => 'incorrect_login',
				'destination'   => 'FTP',
				'provider_code' => $code ?: 'login_failed',
			];
		}

		if (
			'552' === $code
			|| '452' === $code
			|| false !== strpos( $normalized, 'quota' )
			|| false !== strpos( $normalized, 'disk full' )
			|| false !== strpos( $normalized, 'no space' )
			|| false !== strpos( $normalized, 'insufficient' )
		) {
			return [
				'reason_code'   => 'not_enough_storage',
				'destination'   => 'FTP',
				'provider_code' => $code ?: 'insufficient_storage',
			];
		}

		return [];
	}
}
