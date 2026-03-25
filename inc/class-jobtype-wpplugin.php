<?php
class BackWPup_JobType_WPPlugin extends BackWPup_JobTypes {

	/**
	 * Constructs the plugin list job type.
	 */
	public function __construct() {
		$this->info['ID']          = 'WPPLUGIN';
		$this->info['name']        = __( 'Plugins', 'backwpup' );
		$this->info['description'] = __( 'Installed plugins list', 'backwpup' );
		$this->info['URI']         = __( 'http://backwpup.com', 'backwpup' );
		$this->info['author']      = 'WP Media';
		$this->info['authorURI']   = 'https://wp-media.me';
		$this->info['version']     = BackWPup::get_plugin_data( 'Version' );
	}

	/**
	 * Whether the job type creates a file.
	 *
	 * @return bool
	 */
	public function creates_file() {
		return true;
	}

	/**
	 * Returns default options for the job type.
	 *
	 * @return array
	 */
	public function option_defaults() {
		return [
			'pluginlistfilecompression' => '',
			'pluginlistfile'            => sanitize_file_name( get_bloginfo( 'name' ) ) . '.pluginlist.%Y-%m-%d',
		];
	}

	/**
	 * Renders the job type edit tab.
	 *
	 * @param int|array $jobid Job ID or list of job IDs.
	 */
	public function edit_tab( $jobid ) {
		?>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="idpluginlistfile"><?php esc_html_e( 'Plugin list file name', 'backwpup' ); ?></label></th>
				<td>
					<input readonly disabled  name="pluginlistfile" type="text" id="idpluginlistfile"
							value="<?php echo esc_attr( BackWPup_Option::get( $jobid, 'pluginlistfile' ) ); ?>"
							class="medium-text code"/>.txt
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'File compression', 'backwpup' ); ?></th>
				<td>
					<fieldset>
						<?php
						echo '<label for="idpluginlistfilecompression"><input readonly disabled  class="radio" type="radio"' . checked( '', BackWPup_Option::get( $jobid, 'pluginlistfilecompression' ), false ) . ' name="pluginlistfilecompression"  id="idpluginlistfilecompression" value="" /> ' . esc_html__( 'none', 'backwpup' ) . '</label><br />';
						if ( function_exists( 'gzopen' ) ) {
							echo '<label for="idpluginlistfilecompression-gz"><input readonly disabled  class="radio" type="radio"' . checked( '.gz', BackWPup_Option::get( $jobid, 'pluginlistfilecompression' ), false ) . ' name="pluginlistfilecompression" id="idpluginlistfilecompression-gz" value=".gz" /> ' . esc_html__( 'GZip', 'backwpup' ) . '</label><br />';
						} else {
							echo '<label for="idpluginlistfilecompression-gz"><input readonly disabled  class="radio" type="radio"' . checked( '.gz', BackWPup_Option::get( $jobid, 'pluginlistfilecompression' ), false ) . ' name="pluginlistfilecompression" id="idpluginlistfilecompression-gz" value=".gz" disabled="disabled" /> ' . esc_html__( 'GZip', 'backwpup' ) . '</label><br />';
						}
						?>
					</fieldset>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Saves the job type settings.
	 *
	 * @param int   $id     Job ID.
	 * @param array $params Optional. Posted values to update.
	 */
	public function edit_form_post_save( $id, array $params = [] ) {
		if ( empty( $params ) ) {
			check_admin_referer( 'backwpupeditjob_page' );
			$params = $_POST;
		}

		if ( isset( $params['pluginlistfile'] ) ) {
			$plugin_list_file = sanitize_text_field( wp_unslash( $params['pluginlistfile'] ) );
			BackWPup_Option::update( $id, 'pluginlistfile', $plugin_list_file );
		}

		$compression = '';
		if ( isset( $params['pluginlistfilecompression'] ) ) {
			$compression = sanitize_text_field( wp_unslash( $params['pluginlistfilecompression'] ) );
		}

		if ( '' === $compression || '.gz' === $compression || '.bz2' === $compression ) {
			BackWPup_Option::update( $id, 'pluginlistfilecompression', $compression );
		}
	}

	/**
	 * Runs the job type.
	 *
	 * @param BackWPup_Job $job_object Job object.
	 *
	 * @return bool
	 */
	public function job_run( BackWPup_Job $job_object ) {
		$job_object->substeps_todo = 1;

		$job_object->log(
			sprintf(
			/* translators: %d: attempt number. */
			__( '%d. Trying to generate a file with installed plugin names&#160;&hellip;', 'backwpup' ),
			$job_object->steps_data[ $job_object->step_working ]['STEP_TRY']
		)
			);
		// Build filename.
		if ( empty( $job_object->temp['pluginlistfile'] ) ) {
			$job_object->temp['pluginlistfile'] = $job_object->generate_filename( $job_object->job['pluginlistfile'], 'txt' ) . $job_object->job['pluginlistfilecompression'];
		}

		if ( '.gz' === $job_object->job['pluginlistfilecompression'] ) {
			$handle = fopen( 'compress.zlib://' . BackWPup::get_plugin_data( 'TEMP' ) . $job_object->temp['pluginlistfile'], 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		} else {
			$handle = fopen( BackWPup::get_plugin_data( 'TEMP' ) . $job_object->temp['pluginlistfile'], 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		}

		if ( $handle ) {
			// Open file.
			$header  = '------------------------------------------------------------' . PHP_EOL;
			$header .= '  Plugin list generated with BackWPup version: ' . BackWPup::get_plugin_data( 'Version' ) . PHP_EOL;
			$header .= '  http://backwpup.com' . PHP_EOL;
			$header .= '  Blog Name: ' . get_bloginfo( 'name' ) . PHP_EOL;
			$header .= '  Blog URL: ' . get_bloginfo( 'url' ) . PHP_EOL;
			$header .= '  Generated on: ' . wp_date( 'Y-m-d H:i.s', time() ) . PHP_EOL;
			$header .= '------------------------------------------------------------' . PHP_EOL . PHP_EOL;
			fwrite( $handle, $header ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
			// Get plugins.
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php'; // @phpstan-ignore-line
			}
			$plugins        = get_plugins();
			$plugins_active = get_option( 'active_plugins' );
			// Write it to file.
			fwrite( $handle, PHP_EOL . __( 'All plugin information:', 'backwpup' ) . PHP_EOL . '------------------------------' . PHP_EOL ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite

			foreach ( $plugins as $plugin ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
				fwrite(
					$handle,
					$plugin['Name'] . ' (v.' . $plugin['Version'] . ') ' . html_entity_decode(
					sprintf(
					/* translators: %s: plugin author. */
					__( 'from %s', 'backwpup' ),
					$plugin['Author']
					),
					ENT_QUOTES
					) . PHP_EOL . "\t" . $plugin['PluginURI'] . PHP_EOL
					);
			}
			fwrite( $handle, PHP_EOL . __( 'Active plugins:', 'backwpup' ) . PHP_EOL . '------------------------------' . PHP_EOL ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite

			foreach ( $plugins as $key => $plugin ) {
				if ( in_array( $key, $plugins_active, true ) ) {
					fwrite( $handle, $plugin['Name'] . PHP_EOL ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
				}
			}
			fwrite( $handle, PHP_EOL . __( 'Inactive plugins:', 'backwpup' ) . PHP_EOL . '------------------------------' . PHP_EOL ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite

			foreach ( $plugins as $key => $plugin ) {
				if ( ! in_array( $key, $plugins_active, true ) ) {
					fwrite( $handle, $plugin['Name'] . PHP_EOL ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
				}
			}
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		} else {
			$job_object->log( __( 'Can not open target file for writing.', 'backwpup' ), E_USER_ERROR );

			return false;
		}

		// Add file to backup files.
		if ( is_readable( BackWPup::get_plugin_data( 'TEMP' ) . $job_object->temp['pluginlistfile'] ) ) {
			$job_object->additional_files_to_backup[] = BackWPup::get_plugin_data( 'TEMP' ) . $job_object->temp['pluginlistfile'];
			$job_object->log(
				sprintf(
					/* translators: 1: plugin list file name, 2: file size. */
					__( 'Added plugin list file "%1$s" with %2$s to backup file list.', 'backwpup' ),
					$job_object->temp['pluginlistfile'],
					size_format( filesize( BackWPup::get_plugin_data( 'TEMP' ) . $job_object->temp['pluginlistfile'] ), 2 )
				)
			);
		}
		$job_object->substeps_done = 1;

		return true;
	}
}
