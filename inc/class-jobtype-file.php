<?php
/**
 *
 */
class BackWPup_JobType_File extends BackWPup_JobTypes {

	private $folers_to_backup = array();

	/**
	 *
	 */
	public function __construct() {

		$this->info[ 'ID' ]          = 'FILE';
		$this->info[ 'name' ]        = __( 'Files', 'backwpup' );
		$this->info[ 'description' ] = __( 'File backup', 'backwpup' );
		$this->info[ 'URI' ]         = translate( BackWPup::get_plugin_data( 'PluginURI' ), 'backwpup' );
		$this->info[ 'author' ]      = BackWPup::get_plugin_data( 'Author' );
		$this->info[ 'authorURI' ]   = translate( BackWPup::get_plugin_data( 'AuthorURI' ), 'backwpup' );
		$this->info[ 'version' ]     = BackWPup::get_plugin_data( 'Version' );

	}

	/**
	 *
	 */
	public function admin_print_scripts() {

		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			wp_enqueue_script( 'backwpupjobtypefile', BackWPup::get_plugin_data( 'URL' ) . '/js/page_edit_jobtype_file.js', array( 'jquery' ), time(), TRUE );
		} else {
			wp_enqueue_script( 'backwpupjobtypefile', BackWPup::get_plugin_data( 'URL' ) . '/js/page_edit_jobtype_file.min.js', array( 'jquery' ), BackWPup::get_plugin_data( 'Version' ), TRUE );
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
	public function option_defaults() {

		return array(
			'backupexcludethumbs'   => FALSE, 'backupspecialfiles' => TRUE,
			'backuproot'            => TRUE, 'backupcontent' => TRUE, 'backupplugins' => FALSE, 'backupthemes' => TRUE, 'backupuploads' => TRUE,
			'backuprootexcludedirs' => array(), 'backupcontentexcludedirs' => array(), 'backuppluginsexcludedirs' => array(), 'backupthemesexcludedirs' => array(), 'backupuploadsexcludedirs' => array(),
			'fileexclude'           => '.tmp,.svn,.git,desktop.ini,.DS_Store', 'dirinclude' => ''
		);
	}

	/**
	 * @param $main
	 */
	public function edit_tab( $main ) {
		?>
		<h3 class="title"><?php _e( 'Folders to backup', 'backwpup' ) ?></h3>
		<p></p>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="idbackuproot"><?php _e( 'Backup root folder', 'backwpup' ); ?></label></th>
				<td>
					<?php
					$folder = untrailingslashit( str_replace( '\\', '/', ABSPATH ) );
					$folder_size = ( BackWPup_Option::get( 'cfg', 'showfoldersize') ) ? ' (' . size_format( BackWPup_File::get_folder_size( $folder, FALSE ), 2 ) . ')' : '';
					?>
					<input class="checkbox"
						   type="checkbox"<?php checked( BackWPup_Option::get( $main, 'backuproot' ), TRUE, TRUE );?>
						   name="backuproot" id="idbackuproot" value="1" /> <code><?php echo $folder; ?></code><?php echo $folder_size; ?>

					<fieldset id="backuprootexcludedirs" style="padding-left:15px; margin:2px;">
                        <legend><?php  _e( 'Exclude:', 'backwpup' ); ?></legend>
						<?php
						if ( $dir = @opendir( $folder ) ) {
							while ( ( $file = readdir( $dir ) ) !== FALSE ) {
								if ( ! in_array( $file, array( '.', '..' ) ) && is_dir( $folder . '/' . $file ) && ! in_array( $folder . '/' . $file . '/', $this->get_exclude_dirs( $folder ) ) ) {
									$folder_size = ( BackWPup_Option::get( 'cfg', 'showfoldersize') ) ? ' (' . size_format( BackWPup_File::get_folder_size( $folder . '/' . $file ), 2 ) . ')' : '';
									echo '<nobr><label for="idrootexcludedirs-'.sanitize_file_name( $file ).'"><input class="checkbox" type="checkbox"' . checked( in_array( $file, BackWPup_Option::get( $main, 'backuprootexcludedirs' ) ), TRUE, FALSE ) . ' name="backuprootexcludedirs[]" id="idrootexcludedirs-'.sanitize_file_name( $file ).'" value="' . $file . '" /> ' . $file . $folder_size . '</label><br /></nobr>';
								}
							}
							@closedir( $dir );
						}
						?>
                    </fieldset>
				</td>
			</tr>
            <tr>
                <th scope="row"><label for="idbackupcontent"><?php _e( 'Backup content folder', 'backwpup' ); ?></label></th>
                <td>
					<?php
					$folder = untrailingslashit( str_replace( '\\', '/', WP_CONTENT_DIR ) );
					$folder_size = ( BackWPup_Option::get( 'cfg', 'showfoldersize') ) ? ' (' . size_format( BackWPup_File::get_folder_size( $folder, FALSE ), 2 ) . ')' : '';
					?>
                    <input class="checkbox"
                           type="checkbox"<?php checked( BackWPup_Option::get( $main, 'backupcontent' ), TRUE, TRUE );?>
                           name="backupcontent" id="idbackupcontent" value="1" /> <code><?php echo $folder; ?></code><?php echo $folder_size; ?>

                    <fieldset id="backupcontentexcludedirs" style="padding-left:15px; margin:2px;">
                        <legend><?php  _e( 'Exclude:', 'backwpup' ); ?></legend>
						<?php
						if ( $dir = @opendir( $folder ) ) {
							while ( ( $file = readdir( $dir ) ) !== FALSE ) {
								if ( ! in_array( $file, array( '.', '..' ) ) && is_dir( $folder . '/' . $file ) && ! in_array( $folder . '/' . $file . '/', $this->get_exclude_dirs( $folder ) ) ) {
									$folder_size = ( BackWPup_Option::get( 'cfg', 'showfoldersize') ) ? ' (' . size_format( BackWPup_File::get_folder_size( $folder . '/' . $file ), 2 ) . ')' : '';
									echo '<nobr><label for="idcontentexcludedirs-'.sanitize_file_name( $file ).'"><input class="checkbox" type="checkbox"' . checked( in_array( $file, BackWPup_Option::get( $main, 'backupcontentexcludedirs' ) ), TRUE, FALSE ) . ' name="backupcontentexcludedirs[]" id="idcontentexcludedirs-'.sanitize_file_name( $file ).'" value="' . $file . '" /> ' . $file . $folder_size . '</label><br /></nobr>';
								}
							}
							@closedir( $dir );
						}
						?>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="idbackupplugins"><?php _e( 'Backup plugins', 'backwpup' ); ?></label></th>
                <td>
					<?php
					$folder = untrailingslashit( str_replace( '\\', '/', WP_PLUGIN_DIR ) );
					$folder_size = ( BackWPup_Option::get( 'cfg', 'showfoldersize') ) ? ' (' . size_format( BackWPup_File::get_folder_size( $folder, FALSE ), 2 ) . ')' : '';
					?>
                    <input class="checkbox"
                           type="checkbox"<?php checked( BackWPup_Option::get( $main, 'backupplugins' ), TRUE, TRUE );?>
                           name="backupplugins" id="idbackupplugins" value="1" /> <code><?php echo $folder; ?></code><?php echo $folder_size; ?>

                    <fieldset id="backuppluginsexcludedirs" style="padding-left:15px; margin:2px;">
						<legend><?php  _e( 'Exclude:', 'backwpup' ); ?></legend>
						<?php
						if ( $dir = @opendir( $folder ) ) {
							while ( ( $file = readdir( $dir ) ) !== FALSE ) {
								if ( ! in_array( $file, array( '.', '..' ) ) && is_dir( $folder . '/' . $file ) && ! in_array( $folder . '/' . $file . '/', $this->get_exclude_dirs( $folder ) ) ) {
									$folder_size = ( BackWPup_Option::get( 'cfg', 'showfoldersize') ) ? ' (' . size_format( BackWPup_File::get_folder_size( $folder . '/' . $file ), 2 ) . ')' : '';
									echo '<nobr><label for="idpluginexcludedirs-'.sanitize_file_name( $file ).'"><input class="checkbox" type="checkbox"' . checked( in_array( $file, BackWPup_Option::get( $main, 'backuppluginsexcludedirs' ) ), TRUE, FALSE ) . ' name="backuppluginsexcludedirs[]" id="idpluginexcludedirs-'.sanitize_file_name( $file ).'" value="' . $file . '" /> ' . $file . $folder_size . '</label><br /></nobr>';
								}
							}
							@closedir( $dir );
						}
						?>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="idbackupthemes"><?php _e( 'Backup themes', 'backwpup' ); ?></label></th>
                <td>
					<?php
					$folder = untrailingslashit( str_replace( '\\', '/', trailingslashit( get_theme_root() ) ) );
					$folder_size = ( BackWPup_Option::get( 'cfg', 'showfoldersize') ) ? ' (' . size_format( BackWPup_File::get_folder_size( $folder, FALSE ), 2 ) . ')' : '';
					?>
                    <input class="checkbox"
                           type="checkbox"<?php checked( BackWPup_Option::get( $main, 'backupthemes' ), TRUE, TRUE );?>
                           name="backupthemes" id="idbackupthemes" value="1" /> <code><?php echo $folder; ?></code><?php echo $folder_size; ?>

                    <fieldset id="backupthemesexcludedirs" style="padding-left:15px; margin:2px;">
                        <legend><?php  _e( 'Exclude:', 'backwpup' ); ?></legend>
						<?php
						if ( $dir = @opendir( $folder ) ) {
							while ( ( $file = readdir( $dir ) ) !== FALSE ) {
								if ( ! in_array( $file, array( '.', '..' ) ) && is_dir( $folder . '/' . $file ) && ! in_array( $folder . '/' . $file . '/', $this->get_exclude_dirs( $folder ) ) ) {
									$folder_size = ( BackWPup_Option::get( 'cfg', 'showfoldersize') ) ? ' (' . size_format( BackWPup_File::get_folder_size( $folder . '/' . $file ), 2 ) . ')' : '';
									echo '<nobr><label for="idthemesexcludedirs-'.sanitize_file_name( $file ).'"><input class="checkbox" type="checkbox"' . checked( in_array( $file, BackWPup_Option::get( $main, 'backupthemesexcludedirs' ) ), TRUE, FALSE ) . ' name="backupthemesexcludedirs[]" id="idthemesexcludedirs-'.sanitize_file_name( $file ).'" value="' . $file . '" /> ' . $file . $folder_size . '</label><br /></nobr>';
								}
							}
							@closedir( $dir );
						}
						?>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="idbackupuploads"><?php _e( 'Backup uploads folder', 'backwpup' ); ?></label></th>
                <td>
					<?php
					$folder = untrailingslashit( BackWPup_File::get_upload_dir() );
					$folder_size = ( BackWPup_Option::get( 'cfg', 'showfoldersize') ) ? ' (' . size_format( BackWPup_File::get_folder_size( $folder, FALSE ), 2 ) . ')' : '';
					?>
                    <input class="checkbox"
                           type="checkbox"<?php checked( BackWPup_Option::get( $main, 'backupuploads' ), TRUE, TRUE );?>
                           name="backupuploads" id="idbackupuploads" value="1" /> <code><?php echo $folder; ?></code><?php echo $folder_size; ?>

                    <fieldset id="backupuploadsexcludedirs" style="padding-left:15px; margin:2px;">
                        <legend><?php  _e( 'Exclude:', 'backwpup' ); ?></legend>
						<?php
						if ( $dir = @opendir( $folder ) ) {
							while ( ( $file = readdir( $dir ) ) !== FALSE ) {
								if ( ! in_array( $file, array( '.', '..' ) ) && is_dir( $folder . '/' . $file ) && ! in_array( $folder . '/' . $file . '/', $this->get_exclude_dirs( $folder ) ) ) {
									$folder_size = ( BackWPup_Option::get( 'cfg', 'showfoldersize') ) ? ' (' . size_format( BackWPup_File::get_folder_size( $folder . '/' . $file ), 2 ) . ')' : '';
									echo '<nobr><label for="iduploadexcludedirs-'.sanitize_file_name( $file ).'"><input class="checkbox" type="checkbox"' . checked( in_array( $file, BackWPup_Option::get( $main, 'backupuploadsexcludedirs' ) ), TRUE, FALSE ) . ' name="backupuploadsexcludedirs[]" id="iduploadexcludedirs-'.sanitize_file_name( $file ).'" value="' . $file . '" /> ' . $file . $folder_size . '</label><br /></nobr>';
								}
							}
							@closedir( $dir );
						}
						?>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="dirinclude"><?php _e( 'Extra folders to backup', 'backwpup' ); ?></label> <?php BackWPup_Help::tip( __( 'Separate folder names with a line-break or a comma. Folders must be set with their absolute path!', 'backwpup' ) )?></th>
                <td>
					<textarea name="dirinclude" id="dirinclude" class="text code" rows="7" cols="50"><?php echo BackWPup_Option::get( $main, 'dirinclude' ); ?></textarea>
                </td>
            </tr>
		</table>

		<h3 class="title"><?php _e( 'Exclude from backup', 'backwpup' ) ?></h3>
		<p></p>
		<table class="form-table">
            <tr>
                <th scope="row"><?php _e( 'Thumbnails in uploads', 'backwpup' ); ?></th>
                <td>
                    <label for="idbackupexcludethumbs"><input class="checkbox"
                           type="checkbox"<?php checked( BackWPup_Option::get( $main, 'backupexcludethumbs' ), TRUE, TRUE );?>
                           name="backupexcludethumbs" id="idbackupexcludethumbs" value="1" /> <?php _e( 'Don\'t backup thumbnails from the site\'s uploads folder.', 'backwpup' ); BackWPup_Help::add_tab( __( 'All images with -???x???. will be excluded. Use a plugin like Regenerate Thumbnails to rebuild them after a restore.', 'backwpup' ) );?></label>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="idfileexclude"><?php _e( 'Exclude files/folders from backup', 'backwpup' ); ?></label> <?php BackWPup_Help::tip( __( 'Separate file / folder name parts with a line-break or a comma. For example /logs/,.log,.tmp', 'backwpup' ) )?></th>
                <td>
                    <textarea name="fileexclude" id="idfileexclude" class="text code" rows="7" cols="50"><?php echo BackWPup_Option::get( $main, 'fileexclude' ); ?></textarea>
                </td>
            </tr>
        </table>

		<h3 class="title"><?php _e( 'Special option', 'backwpup' ) ?></h3>
		<p></p>
		<table class="form-table">
			<tr>
				<th scope="row"><?php _e( 'Include special files', 'backwpup' ); ?></th>
				<td>
					<label for="idbackupspecialfiles"><input class="checkbox" id="idbackupspecialfiles"
						   type="checkbox"<?php checked( BackWPup_Option::get( $main, 'backupspecialfiles' ), TRUE, TRUE );?>
						   name="backupspecialfiles" value="1" /> <?php _e( 'Backup wp-config.php, robots.txt, .htaccess, .htpasswd and favicon.ico from root.', 'backwpup' ); BackWPup_Help::add_tab( __( 'If the WordPress root folder is not included in this backup job, check this option to additionally include wp-config.php, robots.txt, .htaccess, .htpasswd and favicon.ico into the backup. Your wp-config.php will be included even if you placed it in the parent directory of your root folder.', 'backwpup' ) ); ?></label>
				</td>
			</tr>
		</table>
	<?php
	}


	/**
	 * @param $id
	 */
	public function edit_form_post_save( $id ) {

		$fileexclude = explode( ',', stripslashes( str_replace( array( "\r\n", "\r" ), ',', $_POST[ 'fileexclude' ] ) ) );

		foreach ( $fileexclude as $key => $value ) {
			$fileexclude[ $key ] = str_replace( '//', '/', str_replace( '\\', '/', trim( $value ) ) );
			if ( empty( $fileexclude[ $key ] ) )
				unset( $fileexclude[ $key ] );
		}
		sort( $fileexclude );
		BackWPup_Option::update( $id, 'fileexclude', implode( ',', $fileexclude ) );

		$dirinclude = explode( ',', stripslashes( str_replace( array( "\r\n", "\r" ), ',', $_POST[ 'dirinclude' ] ) ) );
		foreach ( $dirinclude as $key => $value ) {
			$dirinclude[ $key ] = trailingslashit( str_replace( '//', '/', str_replace( '\\', '/', trim( $value ) ) ) );
			if ( $dirinclude[ $key ] == '/' || empty( $dirinclude[ $key ] ) || ! is_dir( $dirinclude[ $key ] ) )
				unset( $dirinclude[ $key ] );
		}
		sort( $dirinclude );
		BackWPup_Option::update( $id, 'dirinclude', implode( ',', $dirinclude ) );

		BackWPup_Option::update( $id, 'backupexcludethumbs', ( isset( $_POST[ 'backupexcludethumbs' ] ) && $_POST[ 'backupexcludethumbs' ] == 1 ) ? TRUE : FALSE );
		BackWPup_Option::update( $id, 'backupspecialfiles', ( isset( $_POST[ 'backupspecialfiles' ] ) && $_POST[ 'backupspecialfiles' ] == 1 ) ? TRUE : FALSE );
		BackWPup_Option::update( $id, 'backuproot', ( isset( $_POST[ 'backuproot' ] ) && $_POST[ 'backuproot' ] == 1 ) ? TRUE : FALSE );


		if ( ! isset( $_POST[ 'backuprootexcludedirs' ] ) || ! is_array( $_POST[ 'backuprootexcludedirs' ] ) )
			$_POST[ 'backuprootexcludedirs' ] = array();
		sort( $_POST[ 'backuprootexcludedirs' ] );
		BackWPup_Option::update( $id, 'backuprootexcludedirs', $_POST[ 'backuprootexcludedirs' ] );

		BackWPup_Option::update( $id, 'backupcontent', ( isset( $_POST[ 'backupcontent' ] ) && $_POST[ 'backupcontent' ] == 1 ) ? TRUE : FALSE );

		if ( ! isset( $_POST[ 'backupcontentexcludedirs' ] ) || ! is_array( $_POST[ 'backupcontentexcludedirs' ] ) )
			$_POST[ 'backupcontentexcludedirs' ] = array();
		sort( $_POST[ 'backupcontentexcludedirs' ] );
		BackWPup_Option::update( $id, 'backupcontentexcludedirs', $_POST[ 'backupcontentexcludedirs' ] );

		BackWPup_Option::update( $id, 'backupplugins', ( isset( $_POST[ 'backupplugins' ] ) && $_POST[ 'backupplugins' ] == 1 ) ? TRUE : FALSE );

		if ( ! isset( $_POST[ 'backuppluginsexcludedirs' ] ) || ! is_array( $_POST[ 'backuppluginsexcludedirs' ] ) )
			$_POST[ 'backuppluginsexcludedirs' ] = array();
		sort( $_POST[ 'backuppluginsexcludedirs' ] );
		BackWPup_Option::update( $id, 'backuppluginsexcludedirs', $_POST[ 'backuppluginsexcludedirs' ] );

		BackWPup_Option::update( $id, 'backupthemes', ( isset( $_POST[ 'backupthemes' ] ) && $_POST[ 'backupthemes' ] == 1 ) ? TRUE : FALSE );

		if ( ! isset( $_POST[ 'backupthemesexcludedirs' ] ) || ! is_array( $_POST[ 'backupthemesexcludedirs' ] ) )
			$_POST[ 'backupthemesexcludedirs' ] = array();
		sort( $_POST[ 'backupthemesexcludedirs' ] );
		BackWPup_Option::update( $id, 'backupthemesexcludedirs', $_POST[ 'backupthemesexcludedirs' ] );

		BackWPup_Option::update( $id, 'backupuploads', ( isset( $_POST[ 'backupuploads' ] ) && $_POST[ 'backupuploads' ] == 1 ) ? TRUE : FALSE );

		if ( ! isset( $_POST[ 'backupuploadsexcludedirs' ] ) || ! is_array( $_POST[ 'backupuploadsexcludedirs' ] ) )
			$_POST[ 'backupuploadsexcludedirs' ] = array();
		sort( $_POST[ 'backupuploadsexcludedirs' ] );
		BackWPup_Option::update( $id, 'backupuploadsexcludedirs', $_POST[ 'backupuploadsexcludedirs' ] );
	}

	/**
	 * @param $job_object
	 * @return bool
	 */
	public function job_run( $job_object ) {

		$job_object->log( sprintf( __( '%d. Trying to make a list of folders to back up&#160;&hellip;', 'backwpup' ), $job_object->steps_data[ $job_object->step_working ][ 'STEP_TRY' ] ) );
		$job_object->substeps_todo = 7;

		$job_object->temp[ 'folders_to_backup' ]=array();

		//Folder lists for blog folders
		if ( ! empty( $job_object->job[ 'backuproot'] ) && $job_object->substeps_done == 0 ) {
			$excludes = $this->get_exclude_dirs( ABSPATH );
			foreach( $job_object->job[ 'backuprootexcludedirs' ] as $folder ) {
				$excludes[] = trailingslashit( str_replace( '\\', '/', ABSPATH ) ) . $folder . '/';
			}
			$this->get_folder_list( $job_object, trailingslashit( str_replace( '\\', '/', ABSPATH ) ), $excludes );
		}
		if ( $job_object->substeps_done == 0 )
			$job_object->substeps_done = 1;
		$job_object->update_working_data();

		if ( ! empty( $job_object->job[ 'backupcontent'] ) && $job_object->substeps_done == 1 ) {
			$excludes = $this->get_exclude_dirs( WP_CONTENT_DIR );
			foreach( $job_object->job[ 'backupcontentexcludedirs' ] as $folder ) {
				$excludes[] = trailingslashit( str_replace( '\\', '/', WP_CONTENT_DIR ) ) . $folder . '/';
			}
			$this->get_folder_list( $job_object, trailingslashit( str_replace( '\\', '/', WP_CONTENT_DIR ) ), $excludes );
		}
		if ( $job_object->substeps_done == 1 )
			$job_object->substeps_done = 2;
		$job_object->update_working_data();

		if ( ! empty( $job_object->job[ 'backupplugins'] ) && $job_object->substeps_done == 2 ) {
			$excludes = $this->get_exclude_dirs( WP_PLUGIN_DIR );
			foreach( $job_object->job[ 'backuppluginsexcludedirs' ] as $folder ) {
				$excludes[] = trailingslashit( str_replace( '\\', '/', WP_PLUGIN_DIR ) ) . $folder . '/';
			}
			$this->get_folder_list( $job_object, trailingslashit( str_replace( '\\', '/', WP_PLUGIN_DIR ) ), $excludes );
		}
		if ( $job_object->substeps_done == 2 )
			$job_object->substeps_done = 3;
		$job_object->update_working_data();

		if ( ! empty( $job_object->job[ 'backupthemes'] ) && $job_object->substeps_done == 3 ) {
			$excludes = $this->get_exclude_dirs( get_theme_root() );
			foreach( $job_object->job[ 'backupthemesexcludedirs' ] as $folder ) {
				$excludes[] = trailingslashit( str_replace( '\\', '/', get_theme_root() ) ) . $folder . '/';
			}
			$this->get_folder_list( $job_object, trailingslashit( str_replace( '\\', '/',  get_theme_root() ) ), $excludes );
		}
		if ( $job_object->substeps_done == 3 )
			$job_object->substeps_done = 4;
		$job_object->update_working_data();

		if ( ! empty( $job_object->job[ 'backupuploads'] ) && $job_object->substeps_done == 4 ) {
			$excludes = $this->get_exclude_dirs( BackWPup_File::get_upload_dir() );
			foreach( $job_object->job[ 'backupuploadsexcludedirs' ] as $folder ) {
				$excludes[] = BackWPup_File::get_upload_dir() . $folder . '/';
			}
			$this->get_folder_list( $job_object, BackWPup_File::get_upload_dir(), $excludes );
		}
		if ( $job_object->substeps_done == 4 )
			$job_object->substeps_done = 5;
		$job_object->update_working_data();

		//include dirs
		if ( $job_object->job[ 'dirinclude' ] && $job_object->substeps_done == 5 ) {
			$dirinclude = explode( ',', $job_object->job[ 'dirinclude' ] );
			$dirinclude = array_unique( $dirinclude );
			//Crate file list for includes
			foreach ( $dirinclude as $dirincludevalue ) {
				if ( is_dir( $dirincludevalue ) )
					$this->get_folder_list( $job_object, $dirincludevalue );
			}
		}
		if ( $job_object->substeps_done == 5 )
			$job_object->substeps_done = 6;

		//save folder list
		$this->folers_to_backup = array_unique( $this->folers_to_backup );
		sort( $this->folers_to_backup );
		$job_object->data_storage( 'folder', $this->folers_to_backup );
		$job_object->count_folder = count( $this->folers_to_backup );
		$job_object->update_working_data();


		//add extra files if selected
		if ( ! empty( $job_object->job[ 'backupspecialfiles'] ) ) {
			if ( is_file( ABSPATH . 'wp-config.php' ) && empty( $job_object->job[ 'backuproot' ] ) ) {
				$job_object->additional_files_to_backup[ ] = str_replace( '\\', '/', ABSPATH . 'wp-config.php' );
				$job_object->count_files ++;
				$job_object->count_filesize = $job_object->count_filesize + @filesize( ABSPATH . 'wp-config.php' );
				$job_object->log( sprintf( __( 'Added "%s" to backup file list', 'backwpup' ), 'wp-config.php' ) );
			}
			elseif ( BackWPup_File::is_in_open_basedir( dirname( ABSPATH ) . '/wp-config.php' ) ) {
				if ( is_file( dirname( ABSPATH ) . '/wp-config.php' ) && ! is_file( dirname( ABSPATH ) . '/wp-settings.php' ) ) {
					$job_object->additional_files_to_backup[ ] = str_replace( '\\', '/', dirname( ABSPATH ) . '/wp-config.php' );
					$job_object->count_files ++;
					$job_object->count_filesize = $job_object->count_filesize + @filesize( dirname( ABSPATH ) . '/wp-config.php' );
					$job_object->log( sprintf( __( 'Added "%s" to backup file list', 'backwpup' ), 'wp-config.php' ) );
				}
			}
			if ( is_file( ABSPATH . '.htaccess' ) && empty( $job_object->job[ 'backuproot' ] ) ) {
				$job_object->additional_files_to_backup[ ] = str_replace( '\\', '/', ABSPATH . '.htaccess' );
				$job_object->count_files ++;
				$job_object->count_filesize = $job_object->count_filesize + @filesize( ABSPATH . '.htaccess' );
				$job_object->log( sprintf( __( 'Added "%s" to backup file list', 'backwpup' ), '.htaccess' ) );
			}
			if ( is_file( ABSPATH . '.htpasswd' ) && empty( $job_object->job[ 'backuproot' ] ) ) {
				$job_object->additional_files_to_backup[ ] = str_replace( '\\', '/', ABSPATH . '.htpasswd' );
				$job_object->count_files ++;
				$job_object->count_filesize = $job_object->count_filesize + @filesize( ABSPATH . '.htpasswd' );
				$job_object->log( sprintf( __( 'Added "%s" to backup file list', 'backwpup' ), '.htpasswd' ) );
			}
			if ( is_file( ABSPATH . 'robots.txt' ) && empty( $job_object->job[ 'backuproot' ] ) ) {
				$job_object->additional_files_to_backup[ ] = str_replace( '\\', '/', ABSPATH . 'robots.txt' );
				$job_object->count_files ++;
				$job_object->count_filesize = $job_object->count_filesize + @filesize( ABSPATH . 'robots.txt' );
				$job_object->log( sprintf( __( 'Added "%s" to backup file list', 'backwpup' ), 'robots.txt' ) );
			}
			if ( is_file( ABSPATH . 'favicon.ico' ) && empty( $job_object->job[ 'backuproot' ] ) ) {
				$job_object->additional_files_to_backup[ ] = str_replace( '\\', '/', ABSPATH . 'favicon.ico' );
				$job_object->count_files ++;
				$job_object->count_filesize = $job_object->count_filesize + @filesize( ABSPATH . 'favicon.ico' );
				$job_object->log( sprintf( __( 'Added "%s" to backup file list', 'backwpup' ), 'favicon.ico' ) );
			}
		}

		if ( empty( $job_object->count_folder ) )
			$job_object->log( __( 'No folder to back up.', 'backwpup' ), E_USER_WARNING );
		else
			$job_object->log( sprintf( __( '%1$d folders to back up.', 'backwpup' ), $job_object->count_folder ) );

		$job_object->substeps_done = 7;

		return TRUE;
	}

	/**
	 *
	 * Helper function for folder_list()
	 *
	 * @param        $job_object BackWPup_Job
	 * @param string $folder
	 * @param array  $excludedirs
	 *
	 * @return bool
	 */
	private function get_folder_list( $job_object, $folder, $excludedirs = array() ) {

		$folder = trailingslashit( $folder );

		if ( $dir = opendir( $folder ) ) {
			//add folder to folder list
			$this->folers_to_backup[] = $folder;
			//scan folder
			while ( FALSE !== ( $file = readdir( $dir ) ) ) {
				if ( in_array( $file, array( '.', '..' ) ) )
					continue;
				foreach ( $job_object->exclude_from_backup as $exclusion ) { //exclude files
					$exclusion = trim( $exclusion );
					if ( FALSE !== stripos( $folder . $file, trim( $exclusion ) ) && ! empty( $exclusion ) )
						continue 2;
				}
				if ( is_dir( $folder . $file ) ) {
					if ( in_array( trailingslashit( $folder . $file ), $excludedirs ) )
						continue;
					if ( ! is_readable( $folder . $file ) ) {
						$job_object->log( sprintf( __( 'Folder "%s" is not readable!', 'backwpup' ), $folder . $file ), E_USER_WARNING );
						continue;
					}
					$this->get_folder_list( $job_object, trailingslashit( $folder . $file ), $excludedirs );
				}
			}
			closedir( $dir );
		}

		return TRUE;
	}


	/**
	 *
	 * Get folder to exclude from a given folder for file backups
	 *
	 * @param $folder string folder to check for excludes
	 *
	 * @return array of folder to exclude
	 */
	private function get_exclude_dirs( $folder ) {

		$folder        = trailingslashit( str_replace( '\\', '/', $folder ) );
		$excludedir    = array();
		$excludedir[ ] = BackWPup::get_plugin_data( 'TEMP' ); //exclude temp
		$excludedir[ ] = BackWPup_Option::get( 'cfg', 'logfolder' ); //exclude log folder

		if ( FALSE !== strpos( trailingslashit( str_replace( '\\', '/', ABSPATH ) ), $folder ) && trailingslashit( str_replace( '\\', '/', ABSPATH ) ) != $folder )
			$excludedir[ ] = trailingslashit( str_replace( '\\', '/', ABSPATH ) );
		if ( FALSE !== strpos( trailingslashit( str_replace( '\\', '/', WP_CONTENT_DIR ) ), $folder ) && trailingslashit( str_replace( '\\', '/', WP_CONTENT_DIR ) ) != $folder )
			$excludedir[ ] = trailingslashit( str_replace( '\\', '/', WP_CONTENT_DIR ) );
		if ( FALSE !== strpos( trailingslashit( str_replace( '\\', '/', WP_PLUGIN_DIR ) ), $folder ) && trailingslashit( str_replace( '\\', '/', WP_PLUGIN_DIR ) ) != $folder )
			$excludedir[ ] = trailingslashit( str_replace( '\\', '/', WP_PLUGIN_DIR ) );
		if ( FALSE !== strpos( str_replace( '\\', '/', trailingslashit( get_theme_root() ) ), $folder ) && str_replace( '\\', '/', trailingslashit( get_theme_root() ) ) != $folder )
			$excludedir[ ] = str_replace( '\\', '/', trailingslashit( get_theme_root() ) );
		if ( FALSE !== strpos( BackWPup_File::get_upload_dir(), $folder ) && BackWPup_File::get_upload_dir() != $folder )
			$excludedir[ ] = BackWPup_File::get_upload_dir();

		//Exclude Backup dirs
		$jobids = BackWPup_Option::get_job_ids();
		foreach ( $jobids as $id ) {
			$backupdir = BackWPup_Option::get( $id, 'backupdir' );
			if ( ! empty( $backupdir ) && $backupdir != '/' ) {
				$excludedir[ ] = $backupdir;
			}
		}

		return array_unique( $excludedir );
	}
}
