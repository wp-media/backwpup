<?php

class BackWPup_JobType_File extends BackWPup_JobTypes {

	public function __construct() {

		$this->info[ 'ID' ]          = 'FILE';
		$this->info[ 'name' ]        = __( 'Files', 'backwpup' );
		$this->info[ 'description' ] = __( 'File backup', 'backwpup' );
		$this->info[ 'URI' ]         = __( 'http://backwpup.com', 'backwpup' );
		$this->info[ 'author' ]      = 'Inpsyde GmbH';
		$this->info[ 'authorURI' ]   = __( 'http://inpsyde.com', 'backwpup' );
		$this->info[ 'version' ]     = BackWPup::get_plugin_data( 'Version' );

	}

	public function admin_print_scripts() {

		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			wp_enqueue_script( 'backwpupjobtypefile', BackWPup::get_plugin_data( 'URL' ) . '/assets/js/page_edit_jobtype_file.js', array( 'jquery' ), time(), TRUE );
		} else {
			wp_enqueue_script( 'backwpupjobtypefile', BackWPup::get_plugin_data( 'URL' ) . '/assets/js/page_edit_jobtype_file.min.js', array( 'jquery' ), BackWPup::get_plugin_data( 'Version' ), TRUE );
		}
	}

	/**
	 * @return bool
	 */
	public function creates_file() {

		return TRUE;
	}

    /**
     * @return array
     */
    public function option_defaults()
    {

        $log_folder = get_site_option('backwpup_cfg_logfolder');
        $log_folder = BackWPup_File::get_absolute_path($log_folder);

        return [
            'backupexcludethumbs' => false,
            'backupspecialfiles' => true,
            'backuproot' => true,
            'backupcontent' => true,
            'backupplugins' => true,
            'backupthemes' => true,
            'backupuploads' => true,
            'backuprootexcludedirs' => apply_filters(
                'backwpup_root_exclude_dirs',
                ['logs', 'usage', 'restore', 'restore_temp']
            ),
            'backupcontentexcludedirs' => apply_filters(
                'backwpup_content_exclude_dirs',
                [
                    'cache',
                    'wflogs',
                    'logs',
                    'upgrade',
                    'w3tc',
                    'updraft',
                    'ai1wm-backups',
                    'snapshots',
                    'wp-clone',
                    'ithemes-security',
                    'backwpup-restore',
                ]
            ),
            'backuppluginsexcludedirs' => apply_filters(
                'backwpup_plugins_exclude_dirs',
                ['backwpup', 'backwpup-pro']
            ),
            'backupthemesexcludedirs' => apply_filters('backwpup_themes_exclude_dirs', []),
            'backupuploadsexcludedirs' => apply_filters(
                'backwpup_upload_exclude_dirs',
                [basename($log_folder)]
            ),
            'fileexclude' => apply_filters(
                'backwpup_file_exclude',
                '.tmp,.svn,.git,desktop.ini,.DS_Store,/node_modules/'
            ),
            'dirinclude' => apply_filters('backwpup_dir_include', ''),
            'backupabsfolderup' => false,
        ];
    }

	/**
	 * @param $main
	 */
	public function edit_tab( $main ) {

		@set_time_limit( 300 );
		$abs_folder_up  = BackWPup_Option::get( $main, 'backupabsfolderup' );
		$abs_path = realpath( BackWPup_Path_Fixer::fix_path( ABSPATH ) );
		if ( $abs_folder_up ) {
			$abs_path = dirname( $abs_path );
		}
		?>
		<h3 class="title"><?php esc_html_e( 'Folders to backup', 'backwpup' ) ?></h3>
		<p></p>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="idbackuproot"><?php esc_html_e( 'Backup WordPress install folder', 'backwpup' ); ?></label></th>
				<td>
					<?php
					$this->show_folder( 'root', $main, $abs_path );
					?>
				</td>
			</tr>
            <tr>
                <th scope="row"><label for="idbackupcontent"><?php esc_html_e( 'Backup content folder', 'backwpup' ); ?></label></th>
                <td>
					<?php
					$this->show_folder( 'content', $main, WP_CONTENT_DIR );
					?>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="idbackupplugins"><?php _e( 'Backup plugins', 'backwpup' ); ?></label></th>
                <td>
					<?php
					$this->show_folder( 'plugins', $main, WP_PLUGIN_DIR );
					?>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="idbackupthemes"><?php esc_html_e( 'Backup themes', 'backwpup' ); ?></label></th>
                <td>
					<?php
					$this->show_folder( 'themes', $main, get_theme_root() );
					?>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="idbackupuploads"><?php esc_html_e( 'Backup uploads folder', 'backwpup' ); ?></label></th>
                <td>
					<?php
					$this->show_folder( 'uploads', $main, BackWPup_File::get_upload_dir() );
					?>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="dirinclude"><?php esc_html_e( 'Extra folders to backup', 'backwpup' ); ?></label></th>
                <td>
					<textarea name="dirinclude" id="dirinclude" class="text code" rows="7" cols="50"><?php echo esc_attr( BackWPup_Option::get( $main, 'dirinclude' ) ); ?></textarea>
	                <p class="description"><?php esc_attr_e( 'Separate folder names with a line-break or a comma. Folders must be set with their absolute path!', 'backwpup' )?></p>
                </td>
            </tr>
		</table>

		<h3 class="title"><?php esc_html_e( 'Exclude from backup', 'backwpup' ) ?></h3>
		<p></p>
		<table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'Thumbnails in uploads', 'backwpup' ); ?></th>
                <td>
                    <label for="idbackupexcludethumbs"><input class="checkbox" type="checkbox"<?php checked( BackWPup_Option::get( $main, 'backupexcludethumbs' ), TRUE, TRUE );?> name="backupexcludethumbs" id="idbackupexcludethumbs" value="1" /> <?php esc_html_e( 'Don\'t backup thumbnails from the site\'s uploads folder.', 'backwpup' ); ?></label>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="idfileexclude"><?php esc_html_e( 'Exclude files/folders from backup', 'backwpup' ); ?></label></th>
                <td>
                    <textarea name="fileexclude" id="idfileexclude" class="text code" rows="7" cols="50"><?php echo esc_attr( BackWPup_Option::get( $main, 'fileexclude' ) ); ?></textarea>
	                <p class="description"><?php esc_attr_e( 'Separate file / folder name parts with a line-break or a comma. For example /logs/,.log,.tmp', 'backwpup' ); ?></p>
                </td>
            </tr>
        </table>

		<h3 class="title"><?php esc_html_e( 'Special options', 'backwpup' ) ?></h3>
		<p></p>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Include special files', 'backwpup' ); ?></th>
				<td>
					<label for="idbackupspecialfiles"><input class="checkbox" id="idbackupspecialfiles" type="checkbox"<?php checked( BackWPup_Option::get( $main, 'backupspecialfiles' ), TRUE, TRUE ); ?> name="backupspecialfiles" value="1" /> <?php esc_html_e( 'Backup wp-config.php, robots.txt, nginx.conf, .htaccess, .htpasswd, favicon.ico, and Web.config from root if it is not included in backup.', 'backwpup' ); ?></label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Use one folder above as WP install folder', 'backwpup' ); ?></th>
				<td>
					<label for="idbackupabsfolderup"><input class="checkbox" id="idbackupabsfolderup" type="checkbox"<?php checked( $abs_folder_up, TRUE, TRUE ); ?>
							name="backupabsfolderup" value="1" /> <?php _e( 'Use one folder above as WordPress install folder! That can be helpful, if you would backup files and folder that are not in the WordPress installation folder. Or if you made a "<a href="https://codex.wordpress.org/Giving_WordPress_Its_Own_Directory">Giving WordPress Its Own Directory</a>" installation. Excludes must be configured again.', 'backwpup' ); ?></label>
				</td>
			</tr>
		</table>
	<?php
	}


	/**
	 * @param $id
	 */
	public function edit_form_post_save( $id ) {

		// Parse and save files to exclude
		$exclude_input = filter_input( INPUT_POST , 'fileexclude' );
		$to_exclude_list = $exclude_input ? str_replace( array( "\r\n", "\r" ), ',', $exclude_input ) : array();
		$to_exclude_list and $to_exclude_list = sanitize_text_field( stripslashes( $to_exclude_list ) );
		$to_exclude = $to_exclude_list ? explode( ',', $to_exclude_list ) : array();
		$to_exclude_parsed = array();
		foreach ( $to_exclude as $key => $value ) {
			$normalized = wp_normalize_path( trim( $value ) );
			$normalized and $to_exclude_parsed[$key] = $normalized;
		}
		sort( $to_exclude_parsed );
		BackWPup_Option::update( $id, 'fileexclude', implode( ',', $to_exclude_parsed ) );
		unset( $exclude_input, $to_exclude_list, $to_exclude, $to_exclude_parsed, $normalized );

		// Parse and save folders to include
		$include_input = filter_input( INPUT_POST , 'dirinclude' );
		$include_list = $include_input ? str_replace( array( "\r\n", "\r" ), ',', $include_input ) : array();
		$to_include = $include_list ? explode( ',', $include_list ) : array();
		$to_include_parsed = array();
		foreach ( $to_include as $key => $value ) {
			$normalized = trailingslashit( wp_normalize_path( trim( $value ) ) );
			$normalized and $normalized = filter_var( $normalized, FILTER_SANITIZE_URL );
			$realpath = $normalized && $normalized !== '/' ? realpath( $normalized ) : false;
			$realpath and $to_include_parsed[$key] = $realpath;
		}
		sort( $to_include_parsed );
		BackWPup_Option::update( $id, 'dirinclude', implode( ',', $to_include_parsed ) );
		unset( $include_input, $include_list, $to_include, $to_include_parsed, $normalized, $realpath  );

		// Parse and save boolean fields
		$boolean_fields_def = array(
			'backupexcludethumbs' => FILTER_VALIDATE_BOOLEAN,
			'backupspecialfiles'  => FILTER_VALIDATE_BOOLEAN,
			'backuproot'          => FILTER_VALIDATE_BOOLEAN,
			'backupabsfolderup'   => FILTER_VALIDATE_BOOLEAN,
			'backupcontent'       => FILTER_VALIDATE_BOOLEAN,
			'backupplugins'       => FILTER_VALIDATE_BOOLEAN,
			'backupthemes'        => FILTER_VALIDATE_BOOLEAN,
			'backupuploads'       => FILTER_VALIDATE_BOOLEAN,
		);
		$boolean_data = filter_input_array( INPUT_POST, $boolean_fields_def );
		$boolean_data or $boolean_data = array();
		foreach( $boolean_fields_def as $key => $value ) {
			BackWPup_Option::update( $id, $key, ! empty( $boolean_data[$key] ) );
		}
		unset( $boolean_fields_def, $boolean_data );

		// Parse and save directories to exclude
		$exclude_dirs_def = array(
			'backuprootexcludedirs'    => array( 'filter' => FILTER_SANITIZE_URL, 'flags' => FILTER_FORCE_ARRAY ),
			'backupcontentexcludedirs'    => array( 'filter' => FILTER_SANITIZE_URL, 'flags' => FILTER_FORCE_ARRAY ),
			'backuppluginsexcludedirs' => array( 'filter' => FILTER_SANITIZE_URL, 'flags' => FILTER_FORCE_ARRAY ),
			'backupthemesexcludedirs'  => array( 'filter' => FILTER_SANITIZE_URL, 'flags' => FILTER_FORCE_ARRAY ),
			'backupuploadsexcludedirs' => array( 'filter' => FILTER_SANITIZE_URL, 'flags' => FILTER_FORCE_ARRAY ),
		);
		$exclude_dirs = filter_input_array( INPUT_POST, $exclude_dirs_def );
		$exclude_dirs or $exclude_dirs = array();
		foreach( $exclude_dirs_def as $key => $filter ) {
			$value = ! empty( $exclude_dirs[$key] ) && is_array( $exclude_dirs[$key] ) ? $exclude_dirs[$key] : array();
			BackWPup_Option::update( $id, $key, $value );
		}
		unset( $exclude_dirs_def, $exclude_dirs );
	}

	/**
	 * @param $job_object
	 * @return bool
	 */
	public function job_run( BackWPup_Job $job_object ) {

		if ( $job_object->steps_data[ $job_object->step_working ]['SAVE_STEP_TRY'] != $job_object->steps_data[ $job_object->step_working ][ 'STEP_TRY' ] ) {
			$job_object->log( sprintf( __( '%d. Trying to make a list of folders to back up&#160;&hellip;', 'backwpup' ), $job_object->steps_data[ $job_object->step_working ]['STEP_TRY'] ) );
		}
		$job_object->substeps_todo = 8;

		$abs_path = realpath( BackWPup_Path_Fixer::fix_path( ABSPATH ) );
		if ( $job_object->job['backupabsfolderup'] ) {
			$abs_path = dirname( $abs_path );
		}
		$abs_path = trailingslashit( str_replace( '\\', '/', $abs_path ) );

		$job_object->temp['folders_to_backup'] = array();
		$folders_already_in = $job_object->get_folders_to_backup();

		//Folder lists for blog folders
		if ( $job_object->substeps_done === 0 ) {
			if ( $abs_path && ! empty( $job_object->job['backuproot'] ) ) {
				$abs_path = trailingslashit( str_replace( '\\', '/', $abs_path ) );
				$excludes = $this->get_exclude_dirs( $abs_path, $folders_already_in );
				foreach ( $job_object->job['backuprootexcludedirs'] as $folder ) {
					$excludes[] = trailingslashit( $abs_path . $folder );
				}
				$this->get_folder_list( $job_object, $abs_path, $excludes );
			}
			$job_object->substeps_done = 1;
			$job_object->update_working_data();
			$job_object->do_restart_time();
		}

		if ( $job_object->substeps_done === 1 ) {
			$wp_content_dir = realpath( WP_CONTENT_DIR );
			if ( $wp_content_dir && ! empty( $job_object->job['backupcontent'] ) ) {
				$wp_content_dir = trailingslashit( str_replace( '\\', '/', $wp_content_dir ) );
				$excludes       = $this->get_exclude_dirs( $wp_content_dir, $folders_already_in );
				foreach ( $job_object->job['backupcontentexcludedirs'] as $folder ) {
					$excludes[] = trailingslashit( $wp_content_dir . $folder );
				}
				$this->get_folder_list( $job_object, $wp_content_dir, $excludes );
			}
			$job_object->substeps_done = 2;
			$job_object->update_working_data();
			$job_object->do_restart_time();
		}

		if ( $job_object->substeps_done === 2 ) {
			$wp_plugin_dir = realpath( WP_PLUGIN_DIR );
			if ( $wp_plugin_dir && ! empty( $job_object->job['backupplugins'] ) ) {
				$wp_plugin_dir = trailingslashit( str_replace( '\\', '/', $wp_plugin_dir ) );
				$excludes      = $this->get_exclude_dirs( $wp_plugin_dir, $folders_already_in );
				foreach ( $job_object->job['backuppluginsexcludedirs'] as $folder ) {
					$excludes[] = trailingslashit( $wp_plugin_dir . $folder );
				}
				$this->get_folder_list( $job_object, $wp_plugin_dir, $excludes );
			}
			$job_object->substeps_done = 3;
			$job_object->update_working_data();
			$job_object->do_restart_time();
		}

		if ( $job_object->substeps_done === 3 ) {
			$theme_root = realpath( get_theme_root() );
			if ( $theme_root && ! empty( $job_object->job['backupthemes'] ) ) {
				$theme_root = trailingslashit( str_replace( '\\', '/', $theme_root ) );
				$excludes   = $this->get_exclude_dirs( $theme_root, $folders_already_in );
				foreach ( $job_object->job['backupthemesexcludedirs'] as $folder ) {
					$excludes[] = trailingslashit( $theme_root . $folder );
				}
				$this->get_folder_list( $job_object, $theme_root, $excludes );
			}
			$job_object->substeps_done = 4;
			$job_object->update_working_data();
			$job_object->do_restart_time();
		}

		if ( $job_object->substeps_done === 4 ) {
			$upload_dir = realpath( BackWPup_File::get_upload_dir() );
			if ( $upload_dir && ! empty( $job_object->job['backupuploads'] ) ) {
				$upload_dir = trailingslashit( str_replace( '\\', '/', $upload_dir ) );
				$excludes   = $this->get_exclude_dirs( $upload_dir, $folders_already_in );
				foreach ( $job_object->job['backupuploadsexcludedirs'] as $folder ) {
					$excludes[] = trailingslashit( $upload_dir . $folder );
				}
				$this->get_folder_list( $job_object, $upload_dir, $excludes );
			}
			$job_object->substeps_done = 5;
			$job_object->update_working_data();
			$job_object->do_restart_time();
		}

		if ( $job_object->substeps_done === 5 ) {
			//include dirs
			if ( $job_object->job['dirinclude'] ) {
				$dirinclude = explode( ',', $job_object->job['dirinclude'] );
				$dirinclude = array_unique( $dirinclude );
				//Crate file list for includes
				foreach ( $dirinclude as $dirincludevalue ) {
					if ( is_dir( $dirincludevalue ) ) {
						$this->get_folder_list( $job_object, $dirincludevalue );
					}
				}
			}
			$job_object->substeps_done = 6;
			$job_object->update_working_data();
			$job_object->do_restart_time();
		}

		if ( $job_object->substeps_done === 6 ) {
			//clean up folder list
			$folders = $job_object->get_folders_to_backup();
			$job_object->add_folders_to_backup( $folders, true );
			$job_object->substeps_done = 7;
			$job_object->update_working_data();
			$job_object->do_restart_time();
		}

		//add extra files if selected
		if ( ! empty( $job_object->job['backupspecialfiles'] ) ) {

			// Special handling for wp-config.php
			if ( is_readable( ABSPATH . 'wp-config.php' ) ) {
				$job_object->additional_files_to_backup[] = str_replace( '\\', '/', ABSPATH . 'wp-config.php' );
				$job_object->log( sprintf( __( 'Added "%s" to backup file list', 'backwpup' ), 'wp-config.php' ) );
			} elseif ( BackWPup_File::is_in_open_basedir( dirname( ABSPATH ) . '/wp-config.php' ) ) {
				if ( is_readable( dirname( ABSPATH ) . '/wp-config.php' ) && ! is_readable( dirname( ABSPATH ) . '/wp-settings.php' ) ) {
					$job_object->additional_files_to_backup[] = str_replace( '\\', '/', dirname( ABSPATH ) . '/wp-config.php' );
					$job_object->log( sprintf( __( 'Added "%s" to backup file list', 'backwpup' ), 'wp-config.php' ) );
				}
			}

			// Files to include
			$special_files = array(
				'.htaccess',
				'nginx.conf',
				'.htpasswd',
				'robots.txt',
				'favicon.ico',
				'Web.config',
			);

			foreach ( $special_files as $file ) {
				if ( is_readable( $abs_path . $file ) && empty( $job_object->job['backuproot'] ) ) {
					$job_object->additional_files_to_backup[] = $abs_path . $file;
					$job_object->log( sprintf( __( 'Added "%s" to backup file list', 'backwpup' ), $file ) );
				}
			}
		}

		if ( $job_object->count_folder === 0 && count( $job_object->additional_files_to_backup ) === 0 ) {
			$job_object->log( __( 'No files/folder for the backup.', 'backwpup' ), E_USER_WARNING );
		} elseif ( $job_object->count_folder > 1 ) {
			$job_object->log( sprintf( __( '%1$d folders to backup.', 'backwpup' ), $job_object->count_folder ) );
		}

		$job_object->substeps_done = 8;

		return true;
	}

	/**
	 *
	 * Helper function for folder_list()
	 *
	 * @param        $job_object BackWPup_Job
	 * @param string $folder
	 * @param array $excludedirs
	 * @param bool $first
	 *
	 * @return bool
	 *
	 */
	private function get_folder_list( &$job_object, $folder, $excludedirs = array(), $first = true ) {

		$folder = trailingslashit( $folder );

		try {
			$dir = new BackWPup_Directory( $folder );
			//add folder to folder list
			$job_object->add_folders_to_backup( $folder );
			//scan folder
			foreach ( $dir as $file ) {
				if ( $file->isDot() ) {
					continue;
				}
				$path = str_replace( '\\', '/', realpath( $file->getPathname() ) );
				foreach ( $job_object->exclude_from_backup as $exclusion ) { //exclude files
					$exclusion = trim( $exclusion );
					if ( stripos( $path, $exclusion ) !== false && ! empty( $exclusion ) ) {
						continue 2;
					}
				}
				if ( $file->isDir() ) {
					if ( in_array( trailingslashit( $path ), $excludedirs, true ) ) {
						continue;
					}
					if ( file_exists( trailingslashit( $file->getPathname() ) . '.donotbackup' ) ) {
						continue;
					}
					if ( ! $file->isReadable() ) {
						$job_object->log( sprintf( __( 'Folder "%s" is not readable!', 'backwpup' ), $file->getPathname() ), E_USER_WARNING );
						continue;
					}
					$this->get_folder_list( $job_object, trailingslashit( $path ), $excludedirs, false );
				}
				if ( $first ) {
					$job_object->do_restart_time();
				}
			}
		}
		catch ( UnexpectedValueException $e ) {
			$job_object->log( sprintf( __( "Could not open path: %s", 'backwpup' ), $e->getMessage() ), E_USER_WARNING );
		}

		return true;
	}

	/**
	 *
	 * Get folder to exclude from a given folder for file backups
	 *
	 * @param $folder string folder to check for excludes
	 *
	 * @param array $excludedir
	 *
	 * @return array of folder to exclude
	 */
	private function get_exclude_dirs( $folder, $excludedir = array() ) {

		$folder     = trailingslashit( str_replace( '\\', '/', realpath( BackWPup_Path_Fixer::fix_path( $folder ) ) ) );

		if ( false !== strpos( trailingslashit( str_replace( '\\', '/', realpath( WP_CONTENT_DIR ) ) ), $folder ) && trailingslashit( str_replace( '\\', '/', realpath( WP_CONTENT_DIR ) ) ) != $folder ) {
			$excludedir[] = trailingslashit( str_replace( '\\', '/', realpath( WP_CONTENT_DIR ) ) );
		}
		if ( false !== strpos( trailingslashit( str_replace( '\\', '/', realpath( WP_PLUGIN_DIR ) ) ), $folder ) && trailingslashit( str_replace( '\\', '/', realpath( WP_PLUGIN_DIR ) ) ) != $folder ) {
			$excludedir[] = trailingslashit( str_replace( '\\', '/', realpath( WP_PLUGIN_DIR ) ) );
		}
		if ( false !== strpos( trailingslashit( str_replace( '\\', '/', realpath( get_theme_root() ) ) ), $folder ) && trailingslashit( str_replace( '\\', '/', realpath( get_theme_root() ) ) ) != $folder ) {
			$excludedir[] = trailingslashit( str_replace( '\\', '/', realpath( get_theme_root() ) ) );
		}
		if ( false !== strpos( trailingslashit( str_replace( '\\', '/', realpath( BackWPup_File::get_upload_dir() ) ) ), $folder ) && trailingslashit( str_replace( '\\', '/', realpath( BackWPup_File::get_upload_dir() ) ) ) != $folder ) {
			$excludedir[] = trailingslashit( str_replace( '\\', '/', realpath( BackWPup_File::get_upload_dir() ) ) );
		}

		return array_unique( $excludedir );
	}

	/**
	 * Shows a folder with the options of which files to exclude.
	 *
	 */
	private function show_folder( $id, $jobid, $path ) {
		$folder = realpath( BackWPup_Path_Fixer::fix_path( $path ) );
		if ( $folder ) {
			$folder = untrailingslashit( str_replace( '\\', '/', $folder ) );
			$folder_size = ( get_site_option( 'backwpup_cfg_showfoldersize') ) ? ' (' . size_format( BackWPup_File::get_folder_size( $folder, FALSE ), 2 ) . ')' : '';
		}
		?>
		<input class="checkbox"
			   type="checkbox"<?php checked( BackWPup_Option::get( $jobid, 'backup' . $id ) ) ?>
			   name="backup<?php echo esc_attr( $id ) ?>" id="idbackup<?php echo esc_attr( $id ) ?>" value="1" /> <code title="<?php echo esc_attr( sprintf( __( 'Path as set by user (symlink?): %s', 'backwpup' ), $path ) ) ?>"><?php echo esc_attr( $folder ) ?></code><?php echo esc_html( $folder_size ) ?>

		<fieldset id="backup<?php echo esc_attr( $id ) ?>excludedirs" style="padding-left:15px; margin:2px;">
			<legend><strong><?php  esc_html_e( 'Exclude:', 'backwpup' ) ?></strong></legend>
			<?php
			try {
				$dir = new BackWPup_Directory( $folder );
				$excludes = BackWPup_Option::get( $jobid, 'backup' . $id . 'excludedirs' );
				foreach ( $dir as $file ) {
					if ( ! $file->isDot() && $file->isDir() && ! in_array( trailingslashit( $file->getPathname() ), $this->get_exclude_dirs( $folder ), true ) ) {
						$donotbackup = file_exists( $file->getPathname() . '/.donotbackup' );
						$folder_size = ( get_site_option( 'backwpup_cfg_showfoldersize' ) ) ? ' (' . size_format( BackWPup_File::get_folder_size( $file->getPathname() ), 2 ) . ')' : '';
						$title = '';
						if ( $donotbackup ) {
							$excludes[] = $file->getFilename();
							$title = ' title="' . esc_attr__( 'Excluded by .donotbackup file!', 'backwpup' ) . '"';
						}
						echo '<nobr><label for="id' . esc_attr( $id ) . 'excludedirs-' . sanitize_file_name( $file->getFilename() ) . '">' .
							'<input class="checkbox" type="checkbox"' .
							checked( in_array( $file->getFilename(), $excludes, true ), true, false ) . ' name="backup' . esc_attr( $id ) . 'excludedirs[]" ' .
							'id="id' . esc_attr( $id ) . 'excludedirs-' . sanitize_file_name( $file->getFilename() ) . '" ' .
							'value="' . esc_attr( $file->getFilename() ) . '"' . disabled( $donotbackup, true, false ) . $title . ' /> ' .
							esc_html( $file->getFilename() ) . esc_html( $folder_size ) . '</label><br /></nobr>';
					}
				}
			}
			catch ( Exception $e ) {
				// Do nothing, just skip
			}
			?>
		</fieldset>
		<?php
	}

}
