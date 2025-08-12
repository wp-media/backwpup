<?php

class BackWPup_Destination_SugarSync extends BackWPup_Destinations
{
    public static $backwpup_job_object;

    public function option_defaults(): array
    {
        return ['sugarrefreshtoken' => '', 'sugarroot' => '', 'sugardir' => trailingslashit(sanitize_file_name(get_bloginfo('name'))), 'sugarmaxbackups' => 15];
    }

 	/**
	 * {@inheritdoc}
	 *
	 * @param int|array $jobid
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
            } catch (Exception $e) {
                BackWPup_Admin::message('SUGARSYNC: ' . $e->getMessage(), true);
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
                $sugarsync->create_account(sanitize_email($_POST['sugaremail']), $_POST['sugarpass']);
            } catch (Exception $e) {
                BackWPup_Admin::message('SUGARSYNC: ' . $e->getMessage(), true);
            }
        }

        $_POST['sugardir'] = trailingslashit(str_replace('//', '/', str_replace('\\', '/', trim(sanitize_text_field($_POST['sugardir'])))));
        if (substr($_POST['sugardir'], 0, 1) == '/') {
            $_POST['sugardir'] = substr($_POST['sugardir'], 1);
        }
        if ($_POST['sugardir'] == '/') {
            $_POST['sugardir'] = '';
		}
		foreach ( $jobids as $jobid ) {
				BackWPup_Option::update( $jobid, 'sugardir', $_POST['sugardir'] );

				BackWPup_Option::update( $jobid, 'sugarroot', isset( $_POST['sugarroot'] ) ? sanitize_text_field( $_POST['sugarroot'] ) : '' );
				BackWPup_Option::update( $jobid, 'sugarmaxbackups', isset( $_POST['sugarmaxbackups'] ) ? absint( $_POST['sugarmaxbackups'] ) : 0 );
		}
	}
	// phpcs:enable

    public function file_delete(string $jobdest, string $backupfile): void
    {
        $files = get_site_transient('backwpup_' . strtolower($jobdest));
        [$jobid, $dest] = explode('_', $jobdest);

        if (BackWPup_Option::get($jobid, 'sugarrefreshtoken')) {
            try {
                $sugarsync = new BackWPup_Destination_SugarSync_API(BackWPup_Option::get($jobid, 'sugarrefreshtoken'));
                $sugarsync->delete(urldecode($backupfile));
                //update file list
                foreach ($files as $key => $file) {
                    if (is_array($file) && $file['file'] == $backupfile) {
                        unset($files[$key]);
                    }
                }
                unset($sugarsync);
            } catch (Exception $e) {
                BackWPup_Admin::message('SUGARSYNC: ' . $e->getMessage(), true);
            }
        }

        set_site_transient('backwpup_' . strtolower($jobdest), $files, YEAR_IN_SECONDS);
    }

    public function file_download(int $jobid, string $get_file, ?string $local_file_path = null): void
    {
        try {
            $sugarsync = new BackWPup_Destination_SugarSync_API(BackWPup_Option::get($jobid, 'sugarrefreshtoken'));
            $response = $sugarsync->get(urldecode($get_file));
            if ($level = ob_get_level()) {
                for ($i = 0; $i < $level; ++$i) {
                    ob_end_clean();
                }
            }

            @set_time_limit(300);

            $fh = fopen(untrailingslashit(BackWPup::get_plugin_data('temp')) . '/' . basename($local_file_path ?: $get_file), 'w');
            fwrite($fh, $sugarsync->download(urldecode($get_file)));
            fclose($fh);

            echo "event: message\n" .
                'data: ' . wp_json_encode([
                    'state' => 'done',
                    'message' => esc_html__(
                        'Your download is being generated &hellip;',
                        'backwpup'
                    ),
                ]) . "\n\n";
            flush();

            exit();
        } catch (Exception $e) {
            exit($e->getMessage());
        }
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
        $job_object->substeps_todo = 2 + $job_object->backup_filesize;
        $job_object->log(sprintf(__('%d. Try to send backup to SugarSync&#160;&hellip;', 'backwpup'), $job_object->steps_data[$job_object->step_working]['STEP_TRY']), E_USER_NOTICE);

        try {
            $sugarsync = new BackWPup_Destination_SugarSync_API($job_object->job['sugarrefreshtoken']);
            //Check Quota
            $user = $sugarsync->user();
            if (!empty($user->nickname)) {
                $job_object->log(sprintf(__('Authenticated to SugarSync with nickname %s', 'backwpup'), $user->nickname), E_USER_NOTICE);
            }
            $sugarsyncfreespase = (float) $user->quota->limit - (float) $user->quota->usage; //float fixes bug for display of no free space
            if ($job_object->backup_filesize > $sugarsyncfreespase) {
                $job_object->log(sprintf(_x('Not enough disk space available on SugarSync. Available: %s.', 'Available space on SugarSync', 'backwpup'), size_format($sugarsyncfreespase, 2)), E_USER_ERROR);
                $job_object->substeps_todo = 1 + $job_object->backup_filesize;

                return true;
            }

            $job_object->log(sprintf(__('%s available at SugarSync', 'backwpup'), size_format($sugarsyncfreespase, 2)), E_USER_NOTICE);

            //Create and change folder
            $sugarsync->mkdir($job_object->job['sugardir'], $job_object->job['sugarroot']);
            $dirid = $sugarsync->chdir($job_object->job['sugardir'], $job_object->job['sugarroot']);
            //Upload to SugarSync
            $job_object->substeps_done = 0;
            $job_object->log(__('Starting upload to SugarSync&#160;&hellip;', 'backwpup'), E_USER_NOTICE);
            self::$backwpup_job_object = &$job_object;
            $response = $sugarsync->upload($job_object->backup_folder . $job_object->backup_file);
            if (is_object($response)) {
                if (!empty($job_object->job['jobid'])) {
                    BackWPup_Option::update(
                        $job_object->job['jobid'],
                        'lastbackupdownloadurl',
                        sprintf(
                            '%s?page=backwpupbackups&action=downloadsugarsync&file=%s&local_file=%s&jobid=%d',
                            network_admin_url('admin.php'),
                            (string) $response,
                            $job_object->backup_file,
                            $job_object->job['jobid']
                        )
                    );
                }
                ++$job_object->substeps_done;
                $job_object->log(sprintf(__('Backup transferred to %s', 'backwpup'), 'https://' . $user->nickname . '.sugarsync.com/' . $sugarsync->showdir($dirid) . $job_object->backup_file), E_USER_NOTICE);
            } else {
                $job_object->log(__('Cannot transfer backup to SugarSync!', 'backwpup'), E_USER_ERROR);

                return false;
            }

            $backupfilelist = [];
            $files = [];
            $filecounter = 0;
            $dir = $sugarsync->showdir($dirid);
            $getfiles = $sugarsync->getcontents('file');
            if (is_object($getfiles)) {
                foreach ($getfiles->file as $getfile) {
                    $getfile->displayName = utf8_decode((string) $getfile->displayName);
                    if ($this->is_backup_archive($getfile->displayName) && $this->is_backup_owned_by_job($getfile->displayName, $job_object->job['jobid']) == true) {
                        $backupfilelist[strtotime((string) $getfile->lastModified)] = (string) $getfile->ref;
                    }
                    $files[$filecounter]['folder'] = 'https://' . (string) $user->nickname . '.sugarsync.com/' . $dir;
                    $files[$filecounter]['file'] = (string) $getfile->ref;
                    $files[$filecounter]['filename'] = (string) $getfile->displayName;
                    $files[$filecounter]['downloadurl'] = sprintf(
                        '%s?page=backwpupbackups&action=downloadsugarsync&file=%s&local_file=%s&jobid=%d',
                        network_admin_url('admin.php'),
                        (string) $getfile->ref,
                        (string) $getfile->displayName,
                        $job_object->job['jobid']
                    );
                    $files[$filecounter]['filesize'] = (int) $getfile->size;
                    $files[$filecounter]['time'] = strtotime((string) $getfile->lastModified) + (get_option('gmt_offset') * 3600);
                    ++$filecounter;
                }
            }
            if (!empty($job_object->job['sugarmaxbackups']) && $job_object->job['sugarmaxbackups'] > 0) { //Delete old backups
                if (count($backupfilelist) > $job_object->job['sugarmaxbackups']) {
                    ksort($backupfilelist);
					$numdeltefiles = 0;
					$deleted_files = [];

                    while ($file = array_shift($backupfilelist)) {
                        if (count($backupfilelist) < $job_object->job['sugarmaxbackups']) {
                            break;
                        }
                        $sugarsync->delete($file); //delete files on Cloud

						foreach ( $files as $key => $filedata ) {
							if ( $filedata['file'] === $file ) {
								$deleted_files[] = $filedata['filename'];
								unset( $files[ $key ] );
							}
                        }
                        ++$numdeltefiles;
                    }
                    if ($numdeltefiles > 0) {
                        $job_object->log(sprintf(_n('One file deleted on SugarSync folder', '%d files deleted on SugarSync folder', $numdeltefiles, 'backwpup'), $numdeltefiles), E_USER_NOTICE);
					}

					parent::remove_file_history_from_database( $deleted_files, 'SUGARSYNC' );
				}
            }
            set_site_transient('BackWPup_' . $job_object->job['jobid'] . '_SUGARSYNC', $files, YEAR_IN_SECONDS);
        } catch (Exception $e) {
            $job_object->log(sprintf(__('SugarSync API: %s', 'backwpup'), $e->getMessage()), E_USER_ERROR, $e->getFile(), $e->getLine());

            return false;
        }
        ++$job_object->substeps_done;

        return true;
    }

    public function can_run(array $job_settings): bool
    {
        if (empty($job_settings['sugarrefreshtoken'])) {
            return false;
        }

        return !(empty($job_settings['sugarroot']));
    }
}



/**
 * SugarSync Exception class.
 *
 * @author    Daniel Hüsken <daniel@huesken-net.de>
 */
class BackWPup_Destination_SugarSync_API_Exception extends Exception
{
}
