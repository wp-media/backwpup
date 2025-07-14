<?php

/**
 * This class allows the user to back up to Dropbox.
 *
 * Documentation: https://www.dropbox.com/developers/documentation/http/overview
 */
class BackWPup_Destination_Dropbox extends BackWPup_Destinations
{
    /**
     * Dropbox.
     *
     * Instance of Dropbox API
     *
     * @var BackWPup_Destination_Dropbox_API|null
     */
    protected $dropbox;

    /**
     * Default Options.
     *
     * @return array The default options for dropbox
     */
    public function option_defaults(): array
    {
        return [
            'dropboxtoken' => [],
            'dropboxroot' => 'sandbox',
            'dropboxmaxbackups' => 15,
            'dropboxsyncnodelete' => true,
            'dropboxdir' => '/' . trailingslashit(sanitize_file_name(get_bloginfo('name'))),
        ];
    }

    /**
     * Edit Tab.
     *
     * @param int $jobid the job id
     *
     * @throws BackWPup_Destination_Dropbox_API_Exception if the destionation instance cannot be created
     */
    public function edit_tab(int $jobid): void
    {
        if (!empty($_GET['deleteauth'])) { // phpcs:ignore
            check_admin_referer('edit-job');

            // Disable token on dropbox.
            try {
                $dropbox = $this->get_dropbox($jobid);
                $dropbox->authTokenRevoke();
            } catch (Exception $e) {
                echo '<div id="message" class="bwu-message-error"><p>'
                    . sprintf(
                    // translators: the $1 is the error message
                        esc_html__('Dropbox API: %s', 'backwpup'),
                        esc_html($e->getMessage())
                    )
                    . '</p></div>';
            }
            BackWPup_Option::update($jobid, 'dropboxtoken', []);
            BackWPup_Option::update($jobid, 'dropboxroot', 'sandbox');
        }

        $dropbox = new BackWPup_Destination_Dropbox_API('dropbox');
        $dropbox_auth_url = $dropbox->oAuthAuthorize();
        $dropbox = new BackWPup_Destination_Dropbox_API('sandbox');
        $sandbox_auth_url = $dropbox->oAuthAuthorize();

        $dropboxtoken = BackWPup_Option::get($jobid, 'dropboxtoken'); ?>

		<h3 class="title"><?php esc_html_e('Login', 'backwpup'); ?></h3>
		<p></p>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e('Authentication', 'backwpup'); ?></th>
				<td><?php if (empty($dropboxtoken['refresh_token'])) { ?>
						<span class="bwu-message-error"><?php esc_html_e('Not authenticated!', 'backwpup'); ?></span>
						<br />&nbsp;<br />
						<a class="button secondary"
							href="http://db.tt/8irM1vQ0"><?php esc_html_e('Create Account', 'backwpup'); ?></a>
					<?php } else { ?>
						<span class="bwu-message-success"><?php esc_html_e('Authenticated!', 'backwpup'); ?></span>
						<br />&nbsp;<br />
						<a class="button secondary"
							href="<?php echo wp_nonce_url(
            network_admin_url(
                'admin.php?page=backwpupeditjob&deleteauth=1&jobid=' . $jobid . '&tab=dest-dropbox'
            ),
            'edit-job'
        ); ?>"
							title="<?php esc_html_e(
            'Delete Dropbox Authentication',
            'backwpup'
        ); ?>"><?php esc_html_e('Delete Dropbox Authentication', 'backwpup'); ?></a>
					<?php } ?>
				</td>
			</tr>

			<?php if (empty($dropboxtoken['refresh_token'])) { ?>
				<tr>
					<th scope="row"><label for="id_sandbox_code"><?php esc_html_e(
            'App Access to Dropbox',
            'backwpup'
        ); ?></label></th>
					<td>
						<input id="id_sandbox_code" name="sandbox_code" type="text" value="" class="regular-text code" />&nbsp;
						<a class="button secondary" href="<?php echo esc_attr(
            $sandbox_auth_url
        ); ?>" target="_blank"><?php esc_html_e('Get Dropbox App auth code', 'backwpup'); ?></a>
						<p class="description"><?php esc_html_e(
            'A dedicated folder named BackWPup will be created inside of the Apps folder in your Dropbox. BackWPup will get read and write access to that folder only. You can specify a subfolder as your backup destination for this job in the destination field below.',
            'backwpup'
        ); ?></p>
					</td>
				</tr>
				<tr>
					<th></th>
					<td><?php esc_html_e('— OR —', 'backwpup'); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="id_dropbbox_code"><?php esc_html_e(
            'Full Access to Dropbox',
            'backwpup'
        ); ?></label></th>
					<td>
						<input id="id_dropbbox_code" name="dropbbox_code" type="text" value="" class="regular-text code" />&nbsp;
						<a class="button secondary" href="<?php echo esc_attr(
            $dropbox_auth_url
        ); ?>" target="_blank"><?php esc_html_e('Get full Dropbox auth code ', 'backwpup'); ?></a>
						<p class="description"><?php esc_html_e(
            'BackWPup will have full read and write access to your entire Dropbox. You can specify your backup destination wherever you want, just be aware that ANY files or folders inside of your Dropbox can be overridden or deleted by BackWPup.',
            'backwpup'
        ); ?></p>
					</td>
				</tr>
			<?php } ?>
		</table>


		<h3 class="title"><?php esc_html_e('Backup settings', 'backwpup'); ?></h3>
		<p></p>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="iddropboxdir"><?php esc_html_e(
            'Destination Folder',
            'backwpup'
        ); ?></label></th>
				<td>
					<input id="iddropboxdir" name="dropboxdir" type="text" value="<?php echo esc_attr(
            BackWPup_Option::get($jobid, 'dropboxdir')
        ); ?>" class="regular-text" />
					<p class="description">
						<?php esc_attr_e(
            'Specify a subfolder where your backup archives will be stored. If you use the App option from above, this folder will be created inside of Apps/BackWPup. Otherwise it will be created at the root of your Dropbox. Already exisiting folders with the same name will not be overriden.',
            'backwpup'
        ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e('File Deletion', 'backwpup'); ?></th>
				<td>
					<?php
                    if (BackWPup_Option::get($jobid, 'backuptype') === 'archive') {
                        ?>
						<label for="iddropboxmaxbackups">
							<input id="iddropboxmaxbackups" name="dropboxmaxbackups" type="number" min="0" step="1" value="<?php echo esc_attr(
                            BackWPup_Option::get($jobid, 'dropboxmaxbackups')
                        ); ?>" class="small-text" />
							&nbsp;<?php esc_html_e('Number of files to keep in folder.', 'backwpup'); ?>
						</label>
						<p><?php _e(
                            '<strong>Warning</strong>: Files belonging to this job are now tracked. Old backup archives which are untracked will not be automatically deleted.',
                            'backwpup'
                        ); ?></p>
					<?php
                    } else { ?>
						<label for="iddropboxsyncnodelete">
							<input class="checkbox" value="1" type="checkbox" <?php checked(
                            BackWPup_Option::get($jobid, 'dropboxsyncnodelete'),
                            true
                        ); ?> name="dropboxsyncnodelete" id="iddropboxsyncnodelete" />
							&nbsp;<?php esc_html_e(
                            'Do not delete files while syncing to destination!',
                            'backwpup'
                        ); ?>
						</label>
					<?php } ?>
				</td>
			</tr>
		</table>

		<?php
    }

	/**
	 * {@inheritdoc}
	 *
	 * @param int|array $jobid the job id.
	 *
	 * @throws BackWPup_Destination_Dropbox_API_Exception If the destionation instance cannot be created.
	 *
	 * @phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
	 */
	public function edit_form_post_save( $jobid ): void {
				$jobids = (array) $jobid;
		// Bet auth.
        if (!empty($_POST['sandbox_code'])) {
			try {
				$dropbox      = new BackWPup_Destination_Dropbox_API( 'sandbox' );
				$dropboxtoken = $dropbox->oAuthToken( $_POST['sandbox_code'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				foreach ( $jobids as $id ) {
					BackWPup_Option::update( $id, 'dropboxtoken', $dropboxtoken );
					BackWPup_Option::update( $id, 'dropboxroot', 'sandbox' );
				}
			} catch ( Exception $e ) {
				BackWPup_Admin::message( 'DROPBOX: ' . $e->getMessage(), true );
								throw $e;
			}
        }

        if (!empty($_POST['dropbbox_code'])) {
			try {
				$dropbox      = new BackWPup_Destination_Dropbox_API( 'dropbox' );
				$dropboxtoken = $dropbox->oAuthToken( $_POST['dropbbox_code'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
				foreach ( $jobids as $id ) {
					BackWPup_Option::update( $id, 'dropboxtoken', $dropboxtoken );
					BackWPup_Option::update( $id, 'dropboxroot', 'dropbox' );
				}
			} catch ( Exception $e ) {
				BackWPup_Admin::message( 'DROPBOX: ' . $e->getMessage(), true );
								throw $e;
			}
		}
				$_POST['dropboxdir'] = trailingslashit(
					str_replace( '//', '/', str_replace( '\\', '/', trim( sanitize_text_field( $_POST['dropboxdir'] ) ) ) ) // phpcs:ignore WordPress.Security
				);
		if ( '/' === $_POST['dropboxdir'] ) {
			$_POST['dropboxdir'] = '';
		}

		// Delete auth.
		if ( ! empty( $_POST['delete_auth'] ) ) {
			// We need to check if the token is used on another job.
			$temp_jobs_ids                  = BackWPup_Option::get_job_ids();
			$job_token                      = BackWPup_Option::get( $jobids[0], 'dropboxtoken' );
			$is_job_token_used_on_other_job = false;
			foreach ( $temp_jobs_ids as $id ) {
				// We need to cast the id to int, because the job id is sometimes a string.
				if ( (int) $id !== (int) $jobids[0] && BackWPup_Option::get( $id, 'dropboxtoken' ) === $job_token ) {
					$is_job_token_used_on_other_job = true;
					break;
				}
			}
			// If the token is used on another job, we don't need to revoke it.
			if ( ! $is_job_token_used_on_other_job ) {
				// Try to revoke the token on dropbox.
				try {
					$dropbox = $this->get_dropbox( $jobids[0] );
					$dropbox->authTokenRevoke();
				} catch ( Exception $e ) {
					BackWPup_Admin::message(
						sprintf(
							// translators: %s is the error message.
							__( 'Failed to revoke Dropbox token: %s', 'backwpup' ),
							esc_html( $e->getMessage() )
						),
						true
					);
                }
			}
			// We delete the token from the jobs and seet the root to sandbox.
			foreach ( $jobids as $id ) {
				BackWPup_Option::update( $id, 'dropboxtoken', [] );
				BackWPup_Option::update( $id, 'dropboxroot', 'sandbox' );
			}
		}

		// Save settings.
		foreach ( $jobids as $id ) {
			BackWPup_Option::update( $id, 'dropboxsyncnodelete', ! empty( $_POST['dropboxsyncnodelete'] ) );
			BackWPup_Option::update(
				$id,
				'dropboxmaxbackups',
				! empty( $_POST['dropboxmaxbackups'] ) ? absint( $_POST['dropboxmaxbackups'] ) : 0
			);
			BackWPup_Option::update( $id, 'dropboxdir', $_POST['dropboxdir'] );
		}
	}
	// phpcs:enable

    /**
     * Delete File.
     *
     * @param string $jobdest    the destionation for this job
     * @param string $backupfile the file to delete
     */
    public function file_delete(string $jobdest, string $backupfile): void
    {
        $files = get_site_transient('backwpup_' . strtolower($jobdest));
        [$jobid, $dest] = explode('_', $jobdest);

        try {
            $dropbox = $this->get_dropbox($jobid);
            $dropbox->filesDelete(['path' => $backupfile]);

            //update file list
            foreach ($files as $key => $file) {
                if (is_array($file) && $file['file'] == $backupfile) {
                    unset($files[$key]);
                }
            }
            unset($dropbox);
        } catch (Exception $e) {
            BackWPup_Admin::message('DROPBOX: ' . $e->getMessage(), true);
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

        $backupfilelist = [];
        $filecounter = 0;
        $files = [];
        $dropbox = $this->get_dropbox($jobid);
        $filesList = $dropbox->listFolder(BackWPup_Option::get($jobid, 'dropboxdir'));

        foreach ($filesList as $data) {
            if ($data['.tag'] == 'file' && $this->is_backup_owned_by_job($data['name'], $jobid) == true) {
                $file = $data['name'];
                if ($this->is_backup_archive($file)) {
                    $backupfilelist[strtotime((string) $data['server_modified'])] = $file;
                }
                $files[$filecounter]['folder'] = dirname((string) $data['path_display']);
                $files[$filecounter]['file'] = $data['path_display'];
                $files[$filecounter]['filename'] = $data['name'];
                $files[$filecounter]['downloadurl'] = network_admin_url(
                    'admin.php?page=backwpupbackups&action=downloaddropbox&file=' . $data['path_display'] . '&local_file=' . $data['name'] . '&jobid=' . $jobid
                );
                $files[$filecounter]['filesize'] = $data['size'];
                $files[$filecounter]['time'] = strtotime((string) $data['server_modified']) + (get_option(
                    'gmt_offset'
                ) * 3600);
                ++$filecounter;
            }
        }
        if ($delete && $job_object && BackWPup_Option::get($jobid, 'dropboxmaxbackups') > 0) { //Delete old backups
            if (count($backupfilelist) > $job_object->job['dropboxmaxbackups']) {
                ksort($backupfilelist);
				$numdeltefiles = 0;
				$deleted_files = [];

                while ($file = array_shift($backupfilelist)) {
                    if (count($backupfilelist) < $job_object->job['dropboxmaxbackups']) {
                        break;
                    }
                    $response = $dropbox->filesDelete(
                        ['path' => $job_object->job['dropboxdir'] . $file]
                    ); //delete files on Cloud

					foreach ( $files as $key => $filedata ) {
						if ( $filedata['file'] === $job_object->job['dropboxdir'] . $file
						&& ! empty( $response )
						) {
							$deleted_files[] = $filedata['filename'];
							unset( $files[ $key ] );
							break;
						}
                    }
                    ++$numdeltefiles;
                }
                if ($numdeltefiles > 0) {
                    $job_object->log(
                        sprintf(
                            _n(
                                'One file deleted from Dropbox',
                                '%d files deleted on Dropbox',
                                $numdeltefiles,
                                'backwpup'
                            ),
                            $numdeltefiles
                        ),
                        E_USER_NOTICE
                    );
				}

				parent::remove_file_history_from_database( $deleted_files, 'DROPBOX' );
			}
		}
		$key = 'backwpup_' . $jobid . '_dropbox';

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

    public function job_run_archive(BackWPup_Job $job_object): bool
    {
        $job_object->substeps_todo = 2 + $job_object->backup_filesize;
        if ($job_object->steps_data[$job_object->step_working]['SAVE_STEP_TRY'] != $job_object->steps_data[$job_object->step_working]['STEP_TRY']) {
            $job_object->log(
                sprintf(
                    __('%d. Try to send backup file to Dropbox&#160;&hellip;', 'backwpup'),
                    $job_object->steps_data[$job_object->step_working]['STEP_TRY']
                )
            );
        }

        try {
            $dropbox = $this->get_dropbox($job_object);

            //get account info
            if ($job_object->steps_data[$job_object->step_working]['SAVE_STEP_TRY'] != $job_object->steps_data[$job_object->step_working]['STEP_TRY']) {
                $info = $dropbox->usersGetCurrentAccount();
                if (!empty($info['account_id'])) {
                    if ($job_object->is_debug()) {
                        $user = $info['name']['display_name'] . ' (' . $info['email'] . ')';
                    } else {
                        $user = $info['name']['display_name'];
                    }
                    $job_object->log(sprintf(__('Authenticated with Dropbox of user: %s', 'backwpup'), $user));

                    //Quota
                    if ($job_object->is_debug()) {
                        $quota = $dropbox->usersGetSpaceUsage();
                        $dropboxfreespase = $quota['allocation']['allocated'] - $quota['used'];
                        $job_object->log(
                            sprintf(
                                __('%s available on your Dropbox', 'backwpup'),
                                size_format($dropboxfreespase, 2)
                            )
                        );
                    }
                } else {
                    $job_object->log(__('Not Authenticated with Dropbox!', 'backwpup'), E_USER_ERROR);

                    return false;
                }
                $job_object->log(__('Uploading to Dropbox&#160;&hellip;', 'backwpup'));
            }

            // put the file
            if ($job_object->substeps_done < $job_object->backup_filesize) { //only if upload not complete
                $response = $dropbox->upload(
                    $job_object->backup_folder . $job_object->backup_file,
                    $job_object->job['dropboxdir'] . $job_object->backup_file
                );
                if ($response['size'] == $job_object->backup_filesize) {
                    if (!empty($job_object->job['jobid'])) {
                        BackWPup_Option::update(
                            $job_object->job['jobid'],
                            'lastbackupdownloadurl',
                            network_admin_url(
                                'admin.php'
                            ) . '?page=backwpupbackups&action=downloaddropbox&file=' . ltrim(
                                (string) $response['path_display'],
                                '/'
                            ) . '&jobid=' . $job_object->job['jobid']
                        );
                    }
                    $job_object->substeps_done = 1 + $job_object->backup_filesize;
                    $job_object->log(
                        sprintf(__('Backup transferred to %s', 'backwpup'), $response['path_display']),
                        E_USER_NOTICE
                    );
                } else {
                    if ($response['size'] != $job_object->backup_filesize) {
                        $job_object->log(
                            __('Uploaded file size and local file size don\'t match.', 'backwpup'),
                            E_USER_ERROR
                        );
                    } else {
                        $job_object->log(
                            sprintf(
                                __('Error transfering backup to %s.', 'backwpup') . ' ' . $response['error'],
                                __('Dropbox', 'backwpup')
                            ),
                            E_USER_ERROR
                        );
                    }

                    return false;
                }
            }

            $this->file_update_list($job_object, true);
        } catch (Exception $e) {
            $job_object->log(
                sprintf(__('Dropbox API: %s', 'backwpup'), $e->getMessage()),
                $e->getFile(),
                $e->getLine(),
                E_USER_ERROR
            );

            return false;
        }
        ++$job_object->substeps_done;

        return true;
    }

    public function can_run(array $job_settings): bool
    {
        return !(empty($job_settings['dropboxtoken']));
    }

    /**
     * Get Dropbox.
     *
     * Gets the Dropbox API instance.
     *
     * @parent BackWPup_Job|int $job Either the job object or job ID
     */
    protected function get_dropbox($job): BackWPup_Destination_Dropbox_API
    {
        if (!$this->dropbox) {
            if ($job instanceof BackWPup_Job) {
                $this->dropbox = new BackWPup_Destination_Dropbox_API($job->job['dropboxroot'], $job);
                $jobid = $job->job['jobid'];
                $token = $job->job['dropboxtoken'];
            } else {
                $this->dropbox = new BackWPup_Destination_Dropbox_API(BackWPup_Option::get($job, 'dropboxroot'));
                $jobid = $job;
                $token = BackWPup_Option::get($job, 'dropboxtoken');
            }

            $this->dropbox->setOAuthTokens($token, function ($token) use ($jobid): void {
                BackWPup_Option::update($jobid, 'dropboxtoken', $token);
            });
        }

        return $this->dropbox;
    }
}
