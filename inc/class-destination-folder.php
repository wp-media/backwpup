<?php

/**
 * Class BackWPup_Destination_Folder.
 */
class BackWPup_Destination_Folder extends BackWPup_Destinations
{
    public function option_defaults(): array
    {
        $upload_dir = wp_upload_dir(null, false, true);
        $backups_dir = trailingslashit(str_replace(
            '\\',
            '/',
            $upload_dir['basedir']
        )) . 'backwpup-' . BackWPup::get_plugin_data('hash') . '-backups/';
        $content_path = trailingslashit(str_replace('\\', '/', WP_CONTENT_DIR));
        $backups_dir = str_replace($content_path, '', $backups_dir);

        return ['maxbackups' => 15, 'backupdir' => $backups_dir, 'backupsyncnodelete' => true];
    }

    /**
     * {@inheritdoc}
     */
    public function edit_tab($jobid): void
    {
        ?>
		<h3 class="title"><?php esc_html_e('Backup settings', 'backwpup'); ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row"><label
						for="idbackupdir"><?php esc_html_e('Folder to store backups in', 'backwpup'); ?></label></th>
				<td>
					<input
						name="backupdir"
						id="idbackupdir"
						type="text"
						value="<?php echo esc_attr(BackWPup_Option::get($jobid, 'backupdir')); ?>"
						class="regular-text"
					/>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e('File Deletion', 'backwpup'); ?></th>
				<td>
					<?php
                    if (BackWPup_Option::get($jobid, 'backuptype') === 'archive') {
                        ?>
						<label for="idmaxbackups">
							<input
								id="idmaxbackups"
								name="maxbackups"
								type="number"
								min="0"
								step="1"
								value="<?php echo esc_attr(BackWPup_Option::get($jobid, 'maxbackups')); ?>"
								class="small-text"
							/>
							&nbsp;<?php esc_html_e('Number of files to keep in folder.', 'backwpup'); ?>
						</label>
						<p>
							<?php
                            _e(
                            '<strong>Warning</strong>: Files belonging to this job are now tracked. Old backup archives which are untracked will not be automatically deleted.',
                            'backwpup'
                        ); ?>
						</p>
					<?php
                    } else { ?>
						<label for="idbackupsyncnodelete">
							<input
								class="checkbox"
								value="1"
								type="checkbox"
								<?php checked(BackWPup_Option::get($jobid, 'backupsyncnodelete'), true); ?>
								name="backupsyncnodelete" id="idbackupsyncnodelete"
							/>
							&nbsp;
							<?php
                            esc_html_e(
                            'Do not delete files while syncing to destination!',
                            'backwpup'
                        );
                            ?>
						</label>
					<?php } ?>
				</td>
			</tr>
		</table>
		<?php
    }

    /**
     * {@inheritdoc}
     */
    public function edit_form_post_save(int $jobid): void
    {
        $to_replace = ['//', '\\'];
        $backup_dir = trim(sanitize_text_field($_POST['backupdir']));
        $_POST['backupdir'] = trailingslashit(str_replace($to_replace, '/', $backup_dir));
        $max_backups = isset($_POST['maxbackups']) ? absint($_POST['maxbackups']) : 0;

        BackWPup_Option::update($jobid, 'backupdir', $_POST['backupdir']);
        BackWPup_Option::update($jobid, 'maxbackups', $max_backups);
        BackWPup_Option::update($jobid, 'backupsyncnodelete', !empty($_POST['backupsyncnodelete']));
    }

    /**
     * {@inheritdoc}
     */
    public function file_delete(string $jobdest, string $backupfile): void
    {
        [$jobid, $dest] = explode('_', $jobdest, 2);

        if (empty($jobid)) {
            return;
        }

        $backup_dir = esc_attr(BackWPup_Option::get((int) $jobid, 'backupdir'));
        $backup_dir = BackWPup_File::get_absolute_path($backup_dir);

        $backupfile = realpath(trailingslashit($backup_dir) . basename($backupfile));

        if ($backupfile && is_writeable($backupfile) && !is_dir($backupfile) && !is_link($backupfile)) {
            unlink($backupfile);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function file_get_list(string $jobdest): array
    {
        [$jobid, $dest] = explode('_', $jobdest, 2);

        $filecounter = 0;
        $files = [];
        $backup_folder = BackWPup_Option::get($jobid, 'backupdir');
        $backup_folder = BackWPup_File::get_absolute_path($backup_folder);
        $not_allowed_files = [
            'index.php',
            '.htaccess',
            '.donotbackup',
            'Web.config',
        ];

        if (is_dir($backup_folder)) {
            $dir = $this->get_backwpup_directory($backup_folder);

            foreach ($dir as $file) {
                if (
                    $file->isDot()
                    || $file->isDir()
                    || $file->isLink()
                    || in_array($file->getFilename(), $not_allowed_files, true)
                    || !$this->is_backup_archive($file->getFilename())
                ) {
                    continue;
                }

                if ($file->isReadable()) {
                    //file list for backups
                    $files[$filecounter]['folder'] = $backup_folder;
                    $files[$filecounter]['file'] = str_replace('\\', '/', $file->getPathname());
                    $files[$filecounter]['filename'] = $file->getFilename();
                    $files[$filecounter]['downloadurl'] = add_query_arg(
                        [
                            'page' => 'backwpupbackups',
                            'action' => 'downloadfolder',
                            'file' => $file->getFilename(),
                            'local_file' => $file->getFilename(),
                            'jobid' => $jobid,
                        ],
                        network_admin_url('admin.php')
                    );
                    $files[$filecounter]['filesize'] = $file->getSize();
                    $files[$filecounter]['time'] = $file->getMTime() + (get_option('gmt_offset') * 3600);
                    ++$filecounter;
                }
            }
        }

        return array_filter($files);
    }

    /**
     * {@inheritdoc}
     */
    public function job_run_archive(BackWPup_Job $job_object): bool
    {
        $job_object->substeps_todo = 1;
        if (!empty($job_object->job['jobid'])) {
            BackWPup_Option::update(
                $job_object->job['jobid'],
                'lastbackupdownloadurl',
                add_query_arg(
                    [
                        'page' => 'backwpupbackups',
                        'action' => 'downloadfolder',
                        'file' => basename($job_object->backup_file),
                        'jobid' => $job_object->job['jobid'],
                    ],
                    network_admin_url('admin.php')
                )
            );
        }

        // Delete old Backupfiles.
        $backupfilelist = [];
        $files = [];

        if (is_writable($job_object->backup_folder)) { //make file list
            try {
                $dir = new BackWPup_Directory($job_object->backup_folder);

                foreach ($dir as $file) {
                    if ($file->isDot() || $file->isDir() || $file->isLink() || !$file->isWritable()) {
                        continue;
                    }

                    $is_backup_archive = $this->is_backup_archive($file->getFilename());
                    $is_owned_by_job = $this->is_backup_owned_by_job($file->getFilename(), $job_object->job['jobid']);
                    if ($is_backup_archive && $is_owned_by_job) {
                        $backupfilelist[$file->getMTime()] = clone $file;
                    }
                }
            } catch (UnexpectedValueException $e) {
                $job_object->log(
                    sprintf(
                        esc_html__('Could not open path: %s', 'backwpup'),
                        $e->getMessage()
                    ),
                    E_USER_WARNING
                );
            }
        }

        if ($job_object->job['maxbackups'] > 0) {
            if (count($backupfilelist) > $job_object->job['maxbackups']) {
                ksort($backupfilelist);
                $numdeltefiles = 0;

                while ($file = array_shift($backupfilelist)) {
                    if (count($backupfilelist) < $job_object->job['maxbackups']) {
                        break;
                    }
                    unlink($file->getPathname());

                    foreach ($files as $key => $filedata) {
                        if ($filedata['file'] === $file->getPathname()) {
                            unset($files[$key]);
                        }
                    }
                    ++$numdeltefiles;
                }

                if ($numdeltefiles > 0) {
                    $job_object->log(
                        sprintf(
                            _n('One backup file deleted', '%d backup files deleted', $numdeltefiles, 'backwpup'),
                            $numdeltefiles
                        ),
                        E_USER_NOTICE
                    );
                }
            }
        }

        ++$job_object->substeps_done;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function can_run(array $job_settings): bool
    {
        return !(empty($job_settings['backupdir']) || $job_settings['backupdir'] == '/');
    }

    /**
     * Returns new instance of BackWPup_Directory.
     *
     * @param string $dir the directory to iterate
     */
    protected function get_backwpup_directory(string $dir): BackWPup_Directory
    {
        return new BackWPup_Directory($dir);
    }
}
