<?php

class BackWPup_Destination_Ftp extends BackWPup_Destinations
{
    private const FILTER_USEPASVADDRESS = 'backwpup_ftp_use_passive_address';

    /**
     * FTP Connection Resource.
     *
     * @var resource|null
     */
    private $ftp_conn_id;

    public function option_defaults(): array
    {
        return [
            'ftphost' => '',
            'ftphostport' => 21,
            'ftptimeout' => 90,
            'ftpuser' => '',
            'ftppass' => '',
            'ftpdir' => trailingslashit(sanitize_title_with_dashes(get_bloginfo('name'))),
            'ftpmaxbackups' => 15,
            'ftppasv' => true,
            'ftpssl' => false,
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
					! empty( $_POST['ftpmaxbackups'] ) ? absint( $_POST['ftpmaxbackups'] ) : 0
				);
			if ( function_exists( 'ftp_ssl_connect' ) ) {
				BackWPup_Option::update( $id, 'ftpssl', ! empty( $_POST['ftpssl'] ) );
			} else {
					BackWPup_Option::update( $id, 'ftpssl', false );
			}
				BackWPup_Option::update( $id, 'ftppasv', ! empty( $_POST['ftppasv'] ) );
		}
	}
	// phpcs:enable

    public function file_delete(string $jobdest, string $backupfile): void
    {
        $files = get_site_transient('backwpup_' . strtolower($jobdest));
        [$jobid, $dest] = explode('_', $jobdest);

        $job_options = (object) BackWPup_Option::get_job($jobid);
        $service = new BackWPup_Destination_Ftp_Connect(
            $job_options->ftphost,
            $job_options->ftpuser,
            BackWPup_Encryption::decrypt($job_options->ftppass),
            $job_options->ftphostport,
            $job_options->ftptimeout,
            $job_options->ftpssl,
            $job_options->ftppasv
        );

        try {
            $resource = $service
                ->connect()
                ->resource()
            ;

            // Delete file.
            ftp_delete($resource, $backupfile);

            // Update file list of existing files.
            foreach ($files as $key => $file) {
                if (is_array($file) && $file['file'] == $backupfile) {
                    unset($files[$key]);
                }
            }
        } catch (\RuntimeException $e) {
            BackWPup_Admin::message('FTP: ' . $e->getMessage(), true);
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

    /**
     * File Update List.
     *
     * Update the list of files in the transient.
     *
     * @param BackWPup_Job|int $job    Either the job object or job ID
     * @param bool             $delete whether to delete old backups
     */
    public function file_update_list($job, bool $delete = false): void
    {
        if ($job instanceof BackWPup_Job) {
            $job_object = $job;
            $jobid = $job->job['jobid'];
        } else {
            $job_object = null;
            $jobid = $job;
        }

        if (!$this->ftp_conn_id) {
            $ftp_ssl = BackWPup_Option::get($jobid, 'ftpssl');
            if (!empty($ftp_ssl)
                && function_exists('ftp_ssl_connect')) {
                $ftp_conn_id = ftp_ssl_connect(
                    BackWPup_Option::get($jobid, 'ftphost'),
                    BackWPup_Option::get($jobid, 'ftphostport'),
                    BackWPup_Option::get($jobid, 'ftptimeout')
                );
            } else { //make normal FTP connection if SSL not work
                $ftp_conn_id = ftp_connect(
                    BackWPup_Option::get($jobid, 'ftphost'),
                    BackWPup_Option::get($jobid, 'ftphostport'),
                    BackWPup_Option::get($jobid, 'ftptimeout')
                );
            }

            //FTP Login
            if ($loginok = @ftp_login(
                $ftp_conn_id,
                BackWPup_Option::get($jobid, 'ftpuser'),
                BackWPup_Encryption::decrypt(BackWPup_Option::get($jobid, 'ftppass'))
            )) {
            } else { //if PHP ftp login don't work use raw login
                $return = ftp_raw($ftp_conn_id, 'USER ' . BackWPup_Option::get($jobid, 'ftpuser'));
                if (substr(trim($return[0]), 0, 3) <= 400) {
                    $return = ftp_raw(
                        $ftp_conn_id,
                        'PASS ' . BackWPup_Encryption::decrypt(BackWPup_Option::get($jobid, 'ftppass'))
                    );
                    if (substr(trim($return[0]), 0, 3) <= 400) {
                        $loginok = true;
                    }
                }
            }

            if (!$loginok) {
                throw new Exception(__('Could not log in to FTP server.', 'backwpup'));
            }

            //set actual ftp dir to ftp dir
            $ftp_dir = BackWPup_Option::get($jobid, 'ftpdir');
            if (empty($ftp_dir)) {
                $ftp_dir = trailingslashit(ftp_pwd($ftp_conn_id));
            }
            // prepend actual ftp dir if relative dir
            if (substr((string) $ftp_dir, 0, 1) != '/') {
                $ftp_dir = trailingslashit(ftp_pwd($ftp_conn_id)) . $ftp_dir;
            }
            ftp_chdir($ftp_conn_id, $ftp_dir);

			if ( BackWPup_Option::get( $jobid, 'ftppasv' ) ) {
				ftp_set_option( $ftp_conn_id, FTP_USEPASVADDRESS, wpm_apply_filters_typed( 'string', self::FILTER_USEPASVADDRESS, true ) );
				ftp_pasv( $ftp_conn_id, true );
			} else {
                ftp_pasv($ftp_conn_id, false);
            }
        } else {
            $ftp_conn_id = $this->ftp_conn_id;
            $ftp_dir = $job_object->job['ftpdir'];
        }

        $backupfilelist = [];
        $filecounter = 0;
        $files = [];
        if ($filelist = ftp_nlist($ftp_conn_id, '.')) {
            foreach ($filelist as $file) {
                if (basename($file) != '.' && basename($file) != '..') {
                    if ($this->is_backup_archive($file)
                        && $this->is_backup_owned_by_job(
                            $file,
                            $jobid
                        ) == true) {
                        $time = ftp_mdtm($ftp_conn_id, $file);
                        if ($time != -1) {
                            $backupfilelist[$time] = basename($file);
                        } else {
                            $backupfilelist[] = basename($file);
                        }
                    }
                    $files[$filecounter]['folder'] = 'ftp://' . BackWPup_Option::get($jobid, 'ftphost') . ':' . BackWPup_Option::get($jobid, 'ftphostport') . $ftp_dir;
                    $files[$filecounter]['file'] = trailingslashit($ftp_dir) . basename($file);
                    $files[$filecounter]['filename'] = basename($file);
                    $files[$filecounter]['downloadurl'] = network_admin_url(
                        'admin.php?page=backwpupbackups&action=downloadftp&file=' . trailingslashit($ftp_dir) . basename($file) . '&local_file=' . basename($file) . '&jobid=' . $jobid
                    );
                    $files[$filecounter]['filesize'] = ftp_size($ftp_conn_id, $file);
                    $files[$filecounter]['time'] = ftp_mdtm($ftp_conn_id, $file);
                    ++$filecounter;
                }
            }
        }

        if ($delete && $job_object && !empty($job_object->job['ftpmaxbackups']) && $job_object->job['ftpmaxbackups'] > 0) { //Delete old backups
            if (count($backupfilelist) > $job_object->job['ftpmaxbackups']) {
                ksort($backupfilelist);
				$numdeltefiles = 0;
				$deleted_files = [];

                while ($file = array_shift($backupfilelist)) {
                    if (count($backupfilelist) < $job_object->job['ftpmaxbackups']) {
                        break;
					}
					if ( ftp_delete( $ftp_conn_id, $file ) ) { // delete files on ftp.
						foreach ( $files as $key => $filedata ) {
							if ( trailingslashit( $job_object->job['ftpdir'] ) . $file === $filedata['file'] ) {
								$deleted_files[] = $filedata['file'];
								unset( $files[ $key ] );
							}
                        }
                        ++$numdeltefiles;
                    } else {
                        $job_object->log(
                            sprintf(
                                __('Cannot delete "%s" on FTP server!', 'backwpup'),
                                $job_object->job['ftpdir'] . $file
                            ),
                            E_USER_ERROR
                        );
                    }
                }
                if ($numdeltefiles > 0) {
                    $job_object->log(
                        sprintf(
                            _n(
                                'One file deleted on FTP server',
                                '%d files deleted on FTP server',
                                $numdeltefiles,
                                'backwpup'
                            ),
                            $numdeltefiles
                        ),
                        E_USER_NOTICE
                    );
                }

				parent::remove_file_history_from_database( $deleted_files, 'FTP' );
			}
        }
        set_site_transient('backwpup_' . $jobid . '_ftp', $files, YEAR_IN_SECONDS);
    }

    public function job_run_archive(BackWPup_Job $job_object): bool
    {
        $job_object->substeps_todo = 2 + $job_object->backup_filesize;
        if ($job_object->steps_data[$job_object->step_working]['SAVE_STEP_TRY'] != $job_object->steps_data[$job_object->step_working]['STEP_TRY']) {
            $job_object->log(
                sprintf(
                    __('%d. Try to send backup file to an FTP server&#160;&hellip;', 'backwpup'),
                    $job_object->steps_data[$job_object->step_working]['STEP_TRY']
                ),
                E_USER_NOTICE
            );
        }

        if (!empty($job_object->job['ftpssl'])) { //make SSL FTP connection
            if (function_exists('ftp_ssl_connect')) {
                $ftp_conn_id = ftp_ssl_connect(
                    $job_object->job['ftphost'],
                    $job_object->job['ftphostport'],
                    $job_object->job['ftptimeout']
                );
                if ($ftp_conn_id) {
                    $job_object->log(
                        sprintf(
                            __('Connected via explicit SSL-FTP to server: %s', 'backwpup'),
                            $job_object->job['ftphost'] . ':' . $job_object->job['ftphostport']
                        ),
                        E_USER_NOTICE
                    );
                } else {
                    $job_object->log(
                        sprintf(
                            __('Cannot connect via explicit SSL-FTP to server: %s', 'backwpup'),
                            $job_object->job['ftphost'] . ':' . $job_object->job['ftphostport']
                        ),
                        E_USER_ERROR
                    );

                    return false;
                }
            } else {
                $job_object->log(
                    __('PHP function to connect with explicit SSL-FTP to server does not exist!', 'backwpup'),
                    E_USER_ERROR
                );

                return true;
            }
        } else { //make normal FTP connection if SSL not work
            $ftp_conn_id = ftp_connect(
                $job_object->job['ftphost'],
                $job_object->job['ftphostport'],
                $job_object->job['ftptimeout']
            );
            if ($ftp_conn_id) {
                $job_object->log(
                    sprintf(
                        __('Connected to FTP server: %s', 'backwpup'),
                        $job_object->job['ftphost'] . ':' . $job_object->job['ftphostport']
                    ),
                    E_USER_NOTICE
                );
            } else {
                $job_object->log(
                    sprintf(
                        __('Cannot connect to FTP server: %s', 'backwpup'),
                        $job_object->job['ftphost'] . ':' . $job_object->job['ftphostport']
                    ),
                    E_USER_ERROR
                );

                return false;
            }
        }

        //FTP Login
        $job_object->log(
            sprintf(__('FTP client command: %s', 'backwpup'), 'USER ' . $job_object->job['ftpuser']),
            E_USER_NOTICE
        );
        if ($loginok = @ftp_login(
            $ftp_conn_id,
            $job_object->job['ftpuser'],
            BackWPup_Encryption::decrypt($job_object->job['ftppass'])
        )) {
            $job_object->log(
                sprintf(
                    __('FTP server response: %s', 'backwpup'),
                    'User ' . $job_object->job['ftpuser'] . ' logged in.'
                ),
                E_USER_NOTICE
            );
        } else { //if PHP ftp login don't work use raw login
            $return = ftp_raw($ftp_conn_id, 'USER ' . $job_object->job['ftpuser']);
            $job_object->log(sprintf(__('FTP server reply: %s', 'backwpup'), $return[0]), E_USER_NOTICE);
            if (substr(trim($return[0]), 0, 3) <= 400) {
                $job_object->log(
                    sprintf(__('FTP client command: %s', 'backwpup'), 'PASS *******'),
                    E_USER_NOTICE
                );
                $return = ftp_raw(
                    $ftp_conn_id,
                    'PASS ' . BackWPup_Encryption::decrypt($job_object->job['ftppass'])
                );
                if (substr(trim($return[0]), 0, 3) <= 400) {
                    $job_object->log(sprintf(__('FTP server reply: %s', 'backwpup'), $return[0]), E_USER_NOTICE);
                    $loginok = true;
                } else {
                    $job_object->log(sprintf(__('FTP server reply: %s', 'backwpup'), $return[0]), E_USER_ERROR);
                }
            }
        }

        if (!$loginok) {
            return false;
        }

        $this->ftp_conn_id = $ftp_conn_id;

        //SYSTYPE
        $job_object->log(sprintf(__('FTP client command: %s', 'backwpup'), 'SYST'), E_USER_NOTICE);
        $systype = ftp_systype($ftp_conn_id);
        if ($systype) {
            $job_object->log(sprintf(__('FTP server reply: %s', 'backwpup'), $systype), E_USER_NOTICE);
        } else {
            $job_object->log(
                sprintf(__('FTP server reply: %s', 'backwpup'), __('Error getting SYSTYPE', 'backwpup')),
                E_USER_ERROR
            );
        }

        //set actual ftp dir to ftp dir
        if (empty($job_object->job['ftpdir'])) {
            $job_object->job['ftpdir'] = trailingslashit(ftp_pwd($ftp_conn_id));
        }
        // prepend actual ftp dir if relative dir
        if (substr((string) $job_object->job['ftpdir'], 0, 1) != '/') {
            $job_object->job['ftpdir'] = trailingslashit(ftp_pwd($ftp_conn_id)) . $job_object->job['ftpdir'];
        }

        //test ftp dir and create it if not exists
        if ($job_object->job['ftpdir'] != '/') {
            @ftp_chdir($ftp_conn_id, '/'); //go to root
            $ftpdirs = explode('/', trim((string) $job_object->job['ftpdir'], '/'));

            foreach ($ftpdirs as $ftpdir) {
                if (empty($ftpdir)) {
                    continue;
                }

                if (!@ftp_chdir($ftp_conn_id, $ftpdir)) {
                    if (!$this->create_dir($ftp_conn_id, $ftpdir, $job_object)) {
                        return false;
                    }

                    ftp_chdir($ftp_conn_id, $ftpdir);
                }
            }
        }

        // Get the current working directory
        $current_ftp_dir = trailingslashit(ftp_pwd($ftp_conn_id));
        if ($job_object->substeps_done == 0) {
            $job_object->log(
                sprintf(__('FTP current folder is: %s', 'backwpup'), $current_ftp_dir),
                E_USER_NOTICE
            );
        }

        //get file size to resume upload
        @clearstatcache();
        $job_object->substeps_done = @ftp_size($ftp_conn_id, $job_object->job['ftpdir'] . $job_object->backup_file);
        if ($job_object->substeps_done == -1) {
            $job_object->substeps_done = 0;
        }

		// PASV.
		$job_object->log( sprintf( __( 'FTP client command: %s', 'backwpup' ), 'PASV' ), E_USER_NOTICE ); // @phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
		if ( $job_object->job['ftppasv'] ) {
			ftp_set_option( $ftp_conn_id, FTP_USEPASVADDRESS, wpm_apply_filters_typed( 'boolean', self::FILTER_USEPASVADDRESS, true ) );
			if ( ftp_pasv( $ftp_conn_id, true ) ) {
				$job_object->log(
                    sprintf(__('FTP server reply: %s', 'backwpup'), __('Entering passive mode', 'backwpup')),
                    E_USER_NOTICE
                );
            } else {
                $job_object->log(
                    sprintf(__('FTP server reply: %s', 'backwpup'), __('Cannot enter passive mode', 'backwpup')),
                    E_USER_WARNING
                );
            }
        } else {
            if (ftp_pasv($ftp_conn_id, false)) {
                $job_object->log(
                    sprintf(__('FTP server reply: %s', 'backwpup'), __('Entering normal mode', 'backwpup')),
                    E_USER_NOTICE
                );
            } else {
                $job_object->log(
                    sprintf(__('FTP server reply: %s', 'backwpup'), __('Cannot enter normal mode', 'backwpup')),
                    E_USER_WARNING
                );
            }
        }

        if ($job_object->substeps_done < $job_object->backup_filesize) {
            $job_object->log(__('Starting upload to FTP &#160;&hellip;', 'backwpup'), E_USER_NOTICE);
            if ($fp = fopen($job_object->backup_folder . $job_object->backup_file, 'rb')) {
                //go to actual file pos
                fseek($fp, $job_object->substeps_done);
                $ret = ftp_nb_fput(
                    $ftp_conn_id,
                    $current_ftp_dir . $job_object->backup_file,
                    $fp,
                    FTP_BINARY,
                    $job_object->substeps_done
                );

                while ($ret == FTP_MOREDATA) {
                    $job_object->substeps_done = ftell($fp);
                    $job_object->update_working_data();
                    $job_object->do_restart_time();
                    $ret = ftp_nb_continue($ftp_conn_id);
                }
                if ($ret != FTP_FINISHED) {
                    $job_object->log(__('Cannot transfer backup to FTP server!', 'backwpup'), E_USER_ERROR);

                    return false;
                }
                $job_object->substeps_done = $job_object->backup_filesize + 1;
                $job_object->log(
                    sprintf(
                        __('Backup transferred to FTP server: %s', 'backwpup'),
                        $current_ftp_dir . $job_object->backup_file
                    ),
                    E_USER_NOTICE
                );
                if (!empty($job_object->job['jobid'])) {
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

                fclose($fp);
            } else {
                $job_object->log(__('Can not open source file for transfer.', 'backwpup'), E_USER_ERROR);

                return false;
            }
        }

        $this->file_update_list($job_object, true);
        ++$job_object->substeps_done;

        ftp_close($ftp_conn_id);

        return true;
    }

	/**
	 * Test if the job can run.
	 *
	 * @todo Refactor and log errors and clean it up.
	 *
	 * @param array $job_settings
	 * @return boolean
	 */
    // phpcs:disable 
	public function can_run( array $job_settings ): bool {
		if ( empty( $job_settings['ftphost'] ) ) {
			return false;
        }

        if (empty($job_settings['ftpuser'])) {
            return false;
        }

		if ( empty( $job_settings['ftppass'] ) ) {
			return false;
		}
		if ( ! empty( $job_settings['ftpssl'] ) ) { // make SSL FTP connection
			if ( function_exists( 'ftp_ssl_connect' ) ) {
				$ftp_conn_id = ftp_ssl_connect(
					$job_settings['ftphost'],
					$job_settings['ftphostport'],
					$job_settings['ftptimeout']
				);
				if ( ! $ftp_conn_id ) {
					return false;
				}
			}
        } else { //make normal FTP connection if SSL not work
			$ftp_conn_id = ftp_connect(
				$job_settings['ftphost'],
				$job_settings['ftphostport'],
				$job_settings['ftptimeout']
			);
			if ( ! $ftp_conn_id ) {
				return false;
			}
        }

		// FTP Login
		if ( $loginok = @ftp_login(
			$ftp_conn_id,
			$job_settings['ftpuser'],
			BackWPup_Encryption::decrypt( $job_settings['ftppass'] )
		) ) {
			// var_dump(
			// sprintf(
			// __('FTP server response: %s', 'backwpup'),
			// 'User ' . $job_settings['ftpuser'] . ' logged in.'
			// ));
		} else { // if PHP ftp login don't work use raw login
			$return = ftp_raw( $ftp_conn_id, 'USER ' . $job_settings['ftpuser'] );
			// var_dump(sprintf(__('FTP server reply: %s', 'backwpup'), $return[0]));
			if ( substr( trim( $return[0] ), 0, 3 ) <= 400 ) {
				// var_dump(
				// sprintf(__('FTP client command: %s', 'backwpup'), 'PASS *******')
				// );
				$return = ftp_raw(
					$ftp_conn_id,
					'PASS ' . BackWPup_Encryption::decrypt( $job_settings['ftppass'] )
				);
				if ( substr( trim( $return[0] ), 0, 3 ) <= 400 ) {
					// var_dump(sprintf(__('FTP server reply: %s', 'backwpup'), $return[0]));
					$loginok = true;
				} else {
					// var_dump(sprintf(__('FTP server reply: %s', 'backwpup'), $return[0]));
				}
            }
		}
		// var_dump($loginok);
		if ( ! $loginok ) {
			return false;
		}
		return true;
    }
    // phpcs:enable 

    /**
     * Create a directory.
     *
     * Try to create the directory and if not possible, try to change the permissions of the parent,
     * then try to create it again.
     *
     * @param resource $stream the ftp stream pointer
     */
    private function create_dir($stream, string $dir, BackWPup_Job $job_object): bool
    {
        // Try to create the directory.
        $response = (bool) ftp_mkdir($stream, $dir);

        if (!$response) {
            // Trying to set the parent directory permissions.
            $response = (bool) ftp_chmod($stream, 0775, './');

            if (!$response) {
                $job_object->log(
                    sprintf(
                        esc_html__(
                            'FTP Folder "%s" cannot be created! Parent directory may be not writable.',
                            'backwpup'
                        ),
                        $dir
                    ),
                    E_USER_ERROR
                );

                return $response;
            }

            // Try to create the directory for the second time.
            $response = (bool) ftp_mkdir($stream, $dir);

            if (!$response) {
                $job_object->log(
                    sprintf(
                        esc_html__('FTP Folder "%s" cannot be created!', 'backwpup'),
                        $dir
                    ),
                    E_USER_ERROR
                );

                return $response;
            }
        }

        $job_object->log(
            sprintf(
                esc_html__('FTP Folder "%s" created!', 'backwpup'),
                $dir
            ),
            E_USER_NOTICE
        );

        return $response;
    }
}
