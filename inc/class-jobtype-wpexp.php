<?php
/**
 *
 */
class BackWPup_JobType_WPEXP extends BackWPup_JobTypes {

	/**
	 * @var $backwpup_job_object BackWPup_Job
	 */
	private static $backwpup_job_object = NULL;

	/**
	 *
	 */
	public function __construct() {

		$this->info[ 'ID' ]        	 = 'WPEXP';
		$this->info[ 'name' ]        = __( 'XML export', 'backwpup' );
		$this->info[ 'description' ] = __( 'WordPress XML export', 'backwpup' );
		$this->info[ 'URI' ]         = translate( BackWPup::get_plugin_data( 'PluginURI' ), 'backwpup' );
		$this->info[ 'author' ]      = BackWPup::get_plugin_data( 'Author' );
		$this->info[ 'authorURI' ]   = translate( BackWPup::get_plugin_data( 'AuthorURI' ), 'backwpup' );
		$this->info[ 'version' ]     = BackWPup::get_plugin_data( 'Version' );

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
		return array( 'wpexportcontent' => 'all', 'wpexportfilecompression' => '', 'wpexportfile' => sanitize_file_name( get_bloginfo( 'name' ) ) . '.wordpress.%Y-%m-%d' );
	}


	/**
	 * @param $jobid
	 * @internal param $main
	 */
	public function edit_tab( $jobid ) {
		?>
		<table class="form-table">
			<tr>
				<th scope="row"><?php _e( 'Items to export', 'backwpup' ) ?></th>
				<td>
					<p><label for="idwpexportcontent-all"><input type="radio" name="wpexportcontent" id="idwpexportcontent-all" value="all" <?php checked( BackWPup_Option::get( $jobid, 'wpexportcontent' ), 'all' ); ?> /> <?php _e( 'All content', 'backwpup' ); ?></label></p>
					<p><label for="idwpexportcontent-posts"><input type="radio" name="wpexportcontent" id="idwpexportcontent-posts" value="posts" <?php checked( BackWPup_Option::get( $jobid, 'wpexportcontent' ), 'posts' ); ?> /> <?php _e( 'Posts', 'backwpup' ); ?></label></p>
					<p><label for="idwpexportcontent-pages"><input type="radio" name="wpexportcontent" id="idwpexportcontent-pages" value="pages" <?php checked( BackWPup_Option::get( $jobid, 'wpexportcontent' ), 'pages' ); ?> /> <?php _e( 'Pages', 'backwpup' ); ?></label></p>
					<?php
					foreach ( get_post_types( array( '_builtin' => FALSE, 'can_export' => TRUE ), 'objects' ) as $post_type ) {
						?>
						<p><label for="idwpexportcontent-<?php echo esc_attr( $post_type->name ); ?>"><input type="radio" name="wpexportcontent" id="idwpexportcontent-<?php echo esc_attr( $post_type->name ); ?>" value="<?php echo esc_attr( $post_type->name ); ?>" <?php checked( BackWPup_Option::get( $jobid, 'wpexportcontent' ), esc_attr( $post_type->name ) ); ?> /> <?php echo esc_html( $post_type->label ); ?></label></p>
					<?php } ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="idwpexportfile"><?php _e( 'XML Export file name', 'backwpup' ) ?></label></th>
				<td>
					<input name="wpexportfile" type="text" id="idwpexportfile"
						   value="<?php echo BackWPup_Option::get( $jobid, 'wpexportfile' );?>"
						   class="medium-text code"/>.xml
				</td>
			</tr>
			<tr>
				<th scope="row"><?php _e( 'File compression', 'backwpup' ) ?></th>
				<td>
					<?php
					echo '<label for="idwpexportfilecompression"><input class="radio" type="radio"' . checked( '', BackWPup_Option::get( $jobid, 'wpexportfilecompression' ), FALSE ) . ' name="wpexportfilecompression" id="idwpexportfilecompression" value="" /> ' . __( 'none', 'backwpup' ). '</label><br />';
					if ( function_exists( 'gzopen' ) )
						echo '<label for="idwpexportfilecompression-gz"><input class="radio" type="radio"' . checked( '.gz', BackWPup_Option::get( $jobid, 'wpexportfilecompression' ), FALSE ) . ' name="wpexportfilecompression" id="idwpexportfilecompression-gz" value=".gz" /> ' . __( 'GZip', 'backwpup' ). '</label><br />';
					else
						echo '<label for="idwpexportfilecompression-gz"><input class="radio" type="radio"' . checked( '.gz', BackWPup_Option::get( $jobid, 'wpexportfilecompression' ), FALSE ) . ' name="wpexportfilecompression" id="idwpexportfilecompression-gz" value=".gz" disabled="disabled" /> ' . __( 'GZip', 'backwpup' ). '</label><br />';
					if ( function_exists( 'bzopen' ) )
						echo '<label for="idwpexportfilecompression-bz2"><input class="radio" type="radio"' . checked( '.bz2', BackWPup_Option::get( $jobid, 'wpexportfilecompression' ), FALSE ) . ' name="wpexportfilecompression" id="idwpexportfilecompression-bz2" value=".bz2" /> ' . __( 'BZip2', 'backwpup' ). '</label><br />';
					else
						echo '<label for="idwpexportfilecompression-bz2"><input class="radio" type="radio"' . checked( '.bz2', BackWPup_Option::get( $jobid, 'wpexportfilecompression' ), FALSE ) . ' name="wpexportfilecompression" id="idwpexportfilecompression-bz2" value=".bz2" disabled="disabled" /> ' . __( 'BZip2', 'backwpup' ). '</label><br />';
					?>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * @param $id
	 */
	public function edit_form_post_save( $id ) {

		BackWPup_Option::update( $id, 'wpexportcontent', $_POST[ 'wpexportcontent' ] );
		BackWPup_Option::update( $id, 'wpexportfile', $_POST[ 'wpexportfile' ] );
		if ( $_POST[ 'wpexportfilecompression' ] == '' || $_POST[ 'wpexportfilecompression' ] == '.gz' || $_POST[ 'wpexportfilecompression' ] == '.bz2' )
			BackWPup_Option::update( $id, 'wpexportfilecompression', $_POST[ 'wpexportfilecompression' ] );
	}

	/**
	 * @param $job_object
	 * @return bool
	 */
	public function job_run( $job_object ) {

		$job_object->substeps_todo = 2;

		$job_object->log( sprintf( __( '%d. Trying to create a WordPress export to XML file&#160;&hellip;', 'backwpup' ), $job_object->steps_data[ $job_object->step_working ][ 'STEP_TRY' ] ) );
		//build filename
		$job_object->temp[ 'wpexportfile' ] = $job_object->generate_filename( $job_object->job[ 'wpexportfile' ], 'xml' );

		//include WP export function
		require_once ABSPATH . 'wp-admin/includes/export.php';
		self::$backwpup_job_object = $job_object;
		ob_start( array( $this, 'wp_export_ob_bufferwrite' ), 1048576 ); //start output buffering
		$args = array(
			'content' =>  $job_object->job[ 'wpexportcontent' ]
		);
		@export_wp( $args ); //WP export
		@ob_flush(); //send rest of data
		@ob_end_clean(); //End output buffering

		if ( ! is_readable( BackWPup::get_plugin_data( 'TEMP' ) . $job_object->temp[ 'wpexportfile' ] )  ) {
			$job_object->log( __( 'WP Export file could not generated.', 'backwpup' ), E_USER_ERROR );

			return FALSE;
		}


		if ( extension_loaded( 'simplexml' ) || extension_loaded( 'xml' ) ) {
			$job_object->log( __( 'Check WP Export file&#160;&hellip;', 'backwpup' ) );
			$valid = TRUE;
			if ( extension_loaded( 'simplexml' ) ) {
				$internal_errors = libxml_use_internal_errors( TRUE );
				$dom = new DOMDocument;
				$old_value = NULL;
				if ( function_exists( 'libxml_disable_entity_loader' ) )
					$old_value = libxml_disable_entity_loader( TRUE );
				$success = $dom->loadXML( file_get_contents( BackWPup::get_plugin_data( 'TEMP' ) . $job_object->temp[ 'wpexportfile' ] ) );
				if ( ! is_null( $old_value ) )
					libxml_disable_entity_loader( $old_value );

				if ( ! $success || isset( $dom->doctype ) ) {
					$errors = libxml_get_errors();
					$valid = FALSE;

					foreach ( $errors as $error ) {
						switch ( $error->level ) {
							case LIBXML_ERR_WARNING:
								$job_object->log( E_USER_WARNING, sprintf( __( 'XML WARNING (%s): %s', 'backwpup' ), $error->code, trim( $error->message ) ), BackWPup::get_plugin_data( 'TEMP' ) . $job_object->temp[ 'wpexportfile' ], $error->line );
								break;
							case LIBXML_ERR_ERROR:
								$job_object->log( E_USER_WARNING, sprintf( __( 'XML RECOVERABLE (%s): %s', 'backwpup' ), $error->code,  trim( $error->message ) ), BackWPup::get_plugin_data( 'TEMP' ) . $job_object->temp[ 'wpexportfile' ], $error->line  );
								break;
							case LIBXML_ERR_FATAL:
								$job_object->log( E_USER_WARNING, sprintf( __( 'XML ERROR (%s): %s', 'backwpup' ),$error->code,  trim( $error->message ) ), BackWPup::get_plugin_data( 'TEMP' ) . $job_object->temp[ 'wpexportfile' ], $error->line );
								break;
						}
					}
				} else {
					$xml = simplexml_import_dom( $dom );
					unset( $dom );

					// halt if loading produces an error
					if ( ! $xml ) {
						$job_object->log( __( 'There was an error when reading this WXR file', 'backwpup' ), E_USER_ERROR );
						$valid = FALSE;
					} else {

						$wxr_version = $xml->xpath('/rss/channel/wp:wxr_version');
						if ( ! $wxr_version ) {
							$job_object->log( __( 'This does not appear to be a WXR file, missing/invalid WXR version number', 'backwpup' ), E_USER_ERROR );
							$valid = FALSE;
						}

						$wxr_version = (string) trim( $wxr_version[0] );
						// confirm that we are dealing with the correct file format
						if ( ! preg_match( '/^\d+\.\d+$/', $wxr_version ) ) {
							$job_object->log( __( 'This does not appear to be a WXR file, missing/invalid WXR version number', 'backwpup' ), E_USER_ERROR );
							$valid = FALSE;
						}
					}
				}

				libxml_use_internal_errors( $internal_errors );

			} else if ( extension_loaded( 'xml' ) ) {

				$xml = xml_parser_create( 'UTF-8' );
				xml_parser_set_option( $xml, XML_OPTION_SKIP_WHITE, 1 );
				xml_parser_set_option( $xml, XML_OPTION_CASE_FOLDING, 0 );
				xml_set_object( $xml, $this );
				xml_set_character_data_handler( $xml, 'cdata' );
				xml_set_element_handler( $xml, 'tag_open', 'tag_close' );

				if ( ! xml_parse( $xml, file_get_contents( BackWPup::get_plugin_data( 'TEMP' ) . $job_object->temp[ 'wpexportfile' ] ), true ) ) {
					$current_line = xml_get_current_line_number( $xml );
					$current_column = xml_get_current_column_number( $xml );
					$error_code = xml_get_error_code( $xml );
					$error_string = xml_error_string( $error_code );
					$job_object->log( sprintf( __( 'There was an error (%s) when reading this WXR file on %d:%d. Message: %s', 'backwpup' ), $error_code, $current_line, $current_column, $error_string ), E_USER_ERROR );
					$valid = FALSE;
				}
				xml_parser_free( $xml );

				if ( ! preg_match( '/^\d+\.\d+$/', $this->wxr_version ) ) {
					$job_object->log( __( 'This does not appear to be a WXR file, missing/invalid WXR version number', 'backwpup' ), E_USER_ERROR );
					$valid = FALSE;
				}

			}

			if ( $valid )
				$job_object->log( __( 'WP Export file is a valid WXR file.', 'backwpup' ) );
		} else {
			$job_object->log( __( 'WP Export file can not checked, because no XML extension loaded with the file can checked.', 'backwpup' ) );
		}

		$job_object->substeps_done ++;


		//Compress file
		if ( ! empty( $job_object->job[ 'wpexportfilecompression' ] ) ) {
			$job_object->log( __( 'Compressing file&#160;&hellip;', 'backwpup' ) );
			try {
				$compress = new BackWPup_Create_Archive( BackWPup::get_plugin_data( 'TEMP' ) . $job_object->temp[ 'wpexportfile' ] . $job_object->job[ 'wpexportfilecompression' ] );
				if ( $compress->add_file( BackWPup::get_plugin_data( 'TEMP' ) . $job_object->temp[ 'wpexportfile' ] ) ) {
					unset( $compress );
					unlink( BackWPup::get_plugin_data( 'TEMP' ) . $job_object->temp[ 'wpexportfile' ] );
					$job_object->temp[ 'wpexportfile' ] .= $job_object->job[ 'wpexportfilecompression' ];
					$job_object->log( __( 'Compressing done.', 'backwpup' ) );
				}
			} catch ( Exception $e ) {
				$job_object->log( $e->getMessage(), E_USER_ERROR, $e->getFile(), $e->getLine() );
				unset( $compress );
				return FALSE;
			}
		}
		$job_object->substeps_done ++;

		//add XML file to backup files
		if ( is_readable( BackWPup::get_plugin_data( 'TEMP' ) . $job_object->temp[ 'wpexportfile' ] ) ) {
			$job_object->additional_files_to_backup[ ] = BackWPup::get_plugin_data( 'TEMP' ) . $job_object->temp[ 'wpexportfile' ];
			$job_object->count_files ++;
			$job_object->count_filesize = $job_object->count_filesize + @filesize( BackWPup::get_plugin_data( 'TEMP' ) . $job_object->temp[ 'wpexportfile' ] );
			$job_object->log( sprintf( __( 'Added XML export "%1$s" with %2$s to backup file list.', 'backwpup' ), $job_object->temp[ 'wpexportfile' ], size_format( filesize( BackWPup::get_plugin_data( 'TEMP' ) . $job_object->temp[ 'wpexportfile' ] ), 2 ) ) );
		}
		$job_object->substeps_done = 1;

		return TRUE;
	}

	/**
	 *
	 * Callback for wp-export()
	 *
	 * @param $output
	 */
	public function wp_export_ob_bufferwrite( $output ) {
		// not allowed UTF-8 chars in XML
		$pattern = '/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u';

		self::$backwpup_job_object->log( self::$backwpup_job_object->temp[ 'wpexportfile' ] );
		file_put_contents( BackWPup::get_plugin_data( 'TEMP' ) . self::$backwpup_job_object->temp[ 'wpexportfile' ], preg_replace( $pattern, '', $output ), FILE_APPEND );
		self::$backwpup_job_object->update_working_data();
	}

}
