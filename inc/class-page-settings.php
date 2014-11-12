<?php
/**
 * Class for BackWPup settings page
 */
class BackWPup_Page_Settings {

	/**
	 *
	 * Output js
	 *
	 * @return void
	 */
	public static function admin_print_scripts() {

		wp_enqueue_script( 'backwpupgeneral' );

		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			wp_enqueue_script( 'backwpuppagesettings', BackWPup::get_plugin_data( 'URL' ) . '/assets/js/page_settings.js', array( 'jquery' ), time(), TRUE );
		} else {
			wp_enqueue_script( 'backwpuppagesettings', BackWPup::get_plugin_data( 'URL' ) . '/assets/js/page_settings.min.js', array( 'jquery' ), BackWPup::get_plugin_data( 'Version' ), TRUE );
		}
	}


	/**
	 * Save settings form data
	 */
	public static function save_post_form() {

		if ( ! current_user_can( 'backwpup_settings' ) )
			return;

		//set default options if button clicked
		if ( isset( $_POST[ 'default_settings' ] ) && $_POST[ 'default_settings' ] ) {

			delete_site_option( 'backwpup_cfg_showadminbar' );
			delete_site_option( 'backwpup_cfg_showfoldersize' );
			delete_site_option( 'backwpup_cfg_jobstepretry' );
			delete_site_option( 'backwpup_cfg_jobmaxexecutiontime' );
			delete_site_option( 'backwpup_cfg_jobziparchivemethod' );
			delete_site_option( 'backwpup_cfg_jobnotranslate' );
			delete_site_option( 'backwpup_cfg_jobwaittimems' );
			delete_site_option( 'backwpup_cfg_jobrunauthkey' );
			delete_site_option( 'backwpup_cfg_maxlogs' );
			delete_site_option( 'backwpup_cfg_gzlogs' );
			delete_site_option( 'backwpup_cfg_protectfolders' );
			delete_site_option( 'backwpup_cfg_httpauthuser' );
			delete_site_option( 'backwpup_cfg_httpauthpassword' );
			delete_site_option( 'backwpup_cfg_logfolder' );
			delete_site_option( 'backwpup_cfg_dropboxappkey' );
			delete_site_option( 'backwpup_cfg_dropboxappsecret' );
			delete_site_option( 'backwpup_cfg_dropboxsandboxappkey' );
			delete_site_option( 'backwpup_cfg_dropboxsandboxappsecret' );
			delete_site_option( 'backwpup_cfg_sugarsynckey' );
			delete_site_option( 'backwpup_cfg_sugarsyncsecret' );
			delete_site_option( 'backwpup_cfg_sugarsyncappid' );

			BackWPup_Option::default_site_options();

			BackWPup_Admin::message( __( 'Settings reset to default', 'backwpup' ) );
			return;
		}

		update_site_option( 'backwpup_cfg_showadminbar', isset( $_POST[ 'showadminbar' ] ) ? 1 : 0 );
		update_site_option( 'backwpup_cfg_showfoldersize', isset( $_POST[ 'showfoldersize' ] ) ? 1 : 0 );
		if ( 100 > $_POST[ 'jobstepretry' ] && 0 < $_POST[ 'jobstepretry' ] )
			$_POST[ 'jobstepretry' ] = abs( (int)$_POST[ 'jobstepretry' ] );
		if ( empty( $_POST[ 'jobstepretry' ] ) or ! is_int( $_POST[ 'jobstepretry' ] ) )
			$_POST[ 'jobstepretry' ] = 3;
		update_site_option( 'backwpup_cfg_jobstepretry', $_POST[ 'jobstepretry' ] );
		$max_exe_time = abs( (int)$_POST[ 'jobmaxexecutiontime' ] );
		if ( ! is_int( $max_exe_time ) || $max_exe_time < 0 ) {
			$max_exe_time = 0;
		} elseif ( $max_exe_time > 300 ) {
			$max_exe_time = 300;
		}
		update_site_option( 'backwpup_cfg_jobmaxexecutiontime', $max_exe_time );
		update_site_option( 'backwpup_cfg_jobziparchivemethod', ( $_POST[ 'jobziparchivemethod' ] == '' || $_POST[ 'jobziparchivemethod' ] == 'PclZip' || $_POST[ 'jobziparchivemethod' ] == 'ZipArchive' ) ? $_POST[ 'jobziparchivemethod' ] : '' );
		update_site_option( 'backwpup_cfg_jobnotranslate', isset( $_POST[ 'jobnotranslate' ] ) ? 1 : 0 );
		update_site_option( 'backwpup_cfg_jobwaittimems', $_POST[ 'jobwaittimems' ] );
		update_site_option( 'backwpup_cfg_maxlogs', abs( (int)$_POST[ 'maxlogs' ] ) );
		update_site_option( 'backwpup_cfg_gzlogs', isset( $_POST[ 'gzlogs' ] ) ? 1 : 0 );
		update_site_option( 'backwpup_cfg_protectfolders', isset( $_POST[ 'protectfolders' ] ) ? 1 : 0 );
		update_site_option( 'backwpup_cfg_httpauthuser', $_POST[ 'httpauthuser' ] );
		update_site_option( 'backwpup_cfg_httpauthpassword', BackWPup_Encryption::encrypt( $_POST[ 'httpauthpassword' ] ) );
		$_POST[ 'jobrunauthkey' ] = preg_replace( '/[^a-zA-Z0-9]/', '', trim( $_POST[ 'jobrunauthkey' ] ) );
		update_site_option( 'backwpup_cfg_jobrunauthkey', $_POST[ 'jobrunauthkey' ] );
		$_POST[ 'logfolder' ] = trailingslashit( str_replace( '\\', '/', trim( stripslashes( $_POST[ 'logfolder' ] ) ) ) );
		if ( $_POST[ 'logfolder' ][ 0 ] == '.' || ( $_POST[ 'logfolder' ][ 0 ] != '/' && ! preg_match( '#^[a-zA-Z]:/#', $_POST[ 'logfolder' ] ) ) )
			$_POST[ 'logfolder' ] = trailingslashit( str_replace( '\\', '/', ABSPATH ) ) . $_POST[ 'logfolder' ];
		//set def. folders
		if ( empty( $_POST[ 'logfolder' ] ) || $_POST[ 'logfolder' ] == '/' ) {
			delete_site_option( 'backwpup_cfg_logfolder' );
			BackWPup_Option::default_site_options();
		} else {
			update_site_option( 'backwpup_cfg_logfolder', $_POST[ 'logfolder' ] );
		}

		do_action( 'backwpup_page_settings_save' );

		BackWPup_Admin::message( __( 'Settings saved', 'backwpup' ) );
	}

	/**
	 * Page Output
	 */
	public static function page() {
		global $wpdb;

		?>
    <div class="wrap" id="backwpup-page">
		<h2><span id="backwpup-page-icon">&nbsp;</span><?php echo sprintf( __( '%s Settings', 'backwpup' ), BackWPup::get_plugin_data( 'name' ) ); ?></h2>
		<?php
		$tabs = array( 'general' => __( 'General', 'backwpup' ), 'job' => __( 'Jobs', 'backwpup' ), 'log' => __( 'Logs', 'backwpup' ), 'net' => __( 'Network', 'backwpup' ), 'apikey' => __( 'API Keys', 'backwpup' ), 'information' => __( 'Information', 'backwpup' ) );
		$tabs = apply_filters( 'backwpup_page_settings_tab', $tabs );
		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $tabs as $id => $name ) {
			echo '<a href="#backwpup-tab-' . $id . '" class="nav-tab">' . $name . '</a>';
		}
		echo '</h2>';
		BackWPup_Admin::display_messages();
		?>

    <form id="settingsform" action="<?php echo admin_url( 'admin-post.php?action=backwpup' ); ?>" method="post">
		<?php wp_nonce_field( 'backwpupsettings_page' ); ?>
        <input type="hidden" name="page" value="backwpupsettings" />
    	<input type="hidden" name="anchor" value="#backwpup-tab-general" />

		<div class="table ui-tabs-hide" id="backwpup-tab-general">

			<h3 class="title"><?php _e( 'Display Settings', 'backwpup' ); ?></h3>
            <p><?php _e( 'Do you want to see BackWPup in the WordPress admin bar?', 'backwpup' ); ?></p>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e( 'Admin bar', 'backwpup' ); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span><?php _e( 'Admin Bar', 'backwpup' ); ?></span>
                            </legend>
                            <label for="showadminbar">
                                <input name="showadminbar" type="checkbox" id="showadminbar"
                                       value="1" <?php checked( get_site_option( 'backwpup_cfg_showadminbar' ), TRUE ); ?> />
								<?php _e( 'Show BackWPup links in admin bar.', 'backwpup' ); ?></label>
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Folder sizes', 'backwpup' ); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span><?php _e( 'Folder sizes', 'backwpup' ); ?></span>
                            </legend>
                            <label for="showfoldersize">
                                <input name="showfoldersize" type="checkbox" id="showfoldersize"
                                       value="1" <?php checked( get_site_option( 'backwpup_cfg_showfoldersize' ), TRUE ); ?> />
								<?php _e( 'Display folder sizes in the files tab when editing a job. (Might increase loading time of files tab.)', 'backwpup' ); ?></label>
                        </fieldset>
                    </td>
                </tr>
            </table>
			<h3 class="title"><?php _e( 'Security', 'backwpup' ); ?></h3>
			<p><?php _e( 'Security option for BackWPup', 'backwpup' ); ?></p>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e( 'Protect folders', 'backwpup' ); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span><?php _e( 'Protect folders', 'backwpup' ); ?></span>
                            </legend>
                            <label for="protectfolders">
                                <input name="protectfolders" type="checkbox" id="protectfolders"
                                       value="1" <?php checked( get_site_option( 'backwpup_cfg_protectfolders' ), TRUE ); ?> />
								<?php _e( 'Protect BackWPup folders ( Temp, Log and Backups ) with <code>.htaccess</code> and <code>index.php</code>', 'backwpup' ); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
            </table>

			<?php do_action('backwpup_page_settings_tab_generel'); ?>

		</div>

        <div class="table ui-tabs-hide" id="backwpup-tab-log">

            <p><?php _e( 'Every time BackWPup runs a backup job, a log file is being generated. Choose where to store your log files and how many of them.', 'backwpup' ); ?></p>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="logfolder"><?php _e( 'Log file folder', 'backwpup' ); ?></label></th>
                    <td>
                        <input name="logfolder" type="text" id="logfolder"
                               value="<?php echo get_site_option( 'backwpup_cfg_logfolder' );?>"
                               class="regular-text code"/>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="maxlogs"><?php _e( 'Maximum number of log files in folder', 'backwpup' ); ?></label>
                    </th>
                    <td>
                        <input name="maxlogs" type="text" id="maxlogs" title="<?php esc_attr_e( 'Oldest files will be deleted first.', 'backwpup' ); ?>"
                               value="<?php echo get_site_option( 'backwpup_cfg_maxlogs' );?>" class="small-text code help-tip"/>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Compression', 'backwpup' ); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span><?php _e( 'Compression', 'backwpup' ); ?></span>
                            </legend>
                            <label for="gzlogs">
                                <input name="gzlogs" type="checkbox" id="gzlogs"
                                       value="1" <?php checked( get_site_option( 'backwpup_cfg_gzlogs' ), TRUE ); ?><?php if ( ! function_exists( 'gzopen' ) ) echo " disabled=\"disabled\""; ?> />
								<?php _e( 'Compress log files with GZip.', 'backwpup' ); ?></label>
                        </fieldset>
                    </td>
                </tr>
            </table>

        </div>
        <div class="table ui-tabs-hide" id="backwpup-tab-job">

            <p><?php _e( 'There are a couple of general options for backup jobs. Set them here.', 'backwpup' ); ?></p>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="jobstepretry"><?php _e( "Maximum number of retries for job steps", 'backwpup' ); ?></label></th>
                    <td>
                        <input name="jobstepretry" type="text" id="jobstepretry"
                               value="<?php echo get_site_option( 'backwpup_cfg_jobstepretry' );?>"
                               class="small-text code" />
                    </td>
                </tr>
				<tr>
                    <th scope="row"><?php _e( 'Maximum script execution time', 'backwpup' ); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span><?php _e( 'Maximum PHP Script execution time', 'backwpup' ); ?></span>
                            </legend>
                            <label for="jobmaxexecutiontime">
                                <input name="jobmaxexecutiontime" type="text" id="jobmaxexecutiontime" size="3" title="<?php esc_attr_e( 'Job will restart before hitting maximum execution time. It will not work with CLI and not on every step during execution. If <code>ALTERNATE_WP_CRON</code> has been defined, WordPress Cron will be used.', 'backwpup' ); ?>"
                                       value="<?php echo get_site_option( 'backwpup_cfg_jobmaxexecutiontime' ); ?>" class="help-tip" />
								<?php _e( 'seconds. 0 = disabled.', 'backwpup' ); ?>
							</label>
                        </fieldset>
                    </td>
                </tr>
				<tr>
                    <th scope="row"><?php _e( 'Method for creating ZIP-file archives', 'backwpup' ); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span><?php _e( 'Method for creating ZIP-file archives', 'backwpup' ); ?></span>
                            </legend>
                            <label for="jobziparchivemethod">
								<select name="jobziparchivemethod" size="1" class="help-tip" title="<?php esc_attr_e( 'Auto = Uses PHP class ZipArchive if available; otherwise uses PclZip.<br />ZipArchive = Uses less memory, but many open files at a time.<br />PclZip = Uses more memory, but only 2 open files at a time.', 'backwpup' ); ?>">
									<option value="" <?php selected( get_site_option( 'backwpup_cfg_jobziparchivemethod' ), '' ); ?>><?php _e( 'Auto', 'backwpup' ); ?></option>
                                    <option value="ZipArchive" <?php selected( get_site_option( 'backwpup_cfg_jobziparchivemethod' ), 'ZipArchive' ); ?><?php disabled( function_exists( 'ZipArchive' ), TRUE ); ?>><?php _e( 'ZipArchive', 'backwpup' ); ?></option>
                                    <option value="PclZip" <?php selected( get_site_option( 'backwpup_cfg_jobziparchivemethod' ), 'PclZip' ); ?>><?php _e( 'PclZip', 'backwpup' ); ?></option>
                                </select>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="jobrunauthkey"><?php _e( 'Key to start jobs externally with an URL', 'backwpup' ); ?></label>
                    </th>
                    <td>
                        <input name="jobrunauthkey" type="text" id="jobrunauthkey" title="<?php esc_attr_e( 'empty = deactivated. Will be used to protect job starts from unauthorized person.', 'backwpup' ); ?>"
                               value="<?php echo get_site_option( 'backwpup_cfg_jobrunauthkey' );?>" class="text code help-tip"/>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'No translation', 'backwpup' ); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span><?php _e( 'No Translation', 'backwpup' ); ?></span>
                            </legend>
                            <label for="jobnotranslate">
                                <input name="jobnotranslate" type="checkbox" id="jobnotranslate"
                                       value="1" <?php checked( get_site_option( 'backwpup_cfg_jobnotranslate' ), TRUE ); ?> />
								<?php _e( 'No translation for the job, the log will be written in English', 'backwpup' ); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Reduce server load', 'backwpup' ); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span><?php _e( 'Reduce server load', 'backwpup' ); ?></span>
                            </legend>
                            <label for="jobwaittimems">
								<select name="jobwaittimems" size="1" class="help-tip" title="<?php esc_attr_e( 'This adds short pauses to the process. Can be used to reduce the CPU load.<br /> Disabled = off<br /> minimum = shortest sleep<br /> medium = middle between minimum and maximum<br /> maximum = longest sleep<br />', 'backwpup' ); ?>">
									<option value="0" <?php selected( get_site_option( 'backwpup_cfg_jobwaittimems' ), 0 ); ?>><?php _e( 'disabled', 'backwpup' ); ?></option>
                                    <option value="10000" <?php selected( get_site_option( 'backwpup_cfg_jobwaittimems' ), 10000 ); ?>><?php _e( 'minimum', 'backwpup' ); ?></option>
                                    <option value="30000" <?php selected( get_site_option( 'backwpup_cfg_jobwaittimems' ), 30000 ); ?>><?php _e( 'medium', 'backwpup' ); ?></option>
                                    <option value="90000" <?php selected( get_site_option( 'backwpup_cfg_jobwaittimems' ), 90000 ); ?>><?php _e( 'maximum', 'backwpup' ); ?></option>
                                </select>
                            </label>
                        </fieldset>
                    </td>
                </tr>
            </table>

        </div>

        <div class="table ui-tabs-hide" id="backwpup-tab-net">

			<h3 class="title"><?php _e( 'Authentication', 'backwpup' ); ?></h3>
            <p><?php _e( 'Is your blog protected with HTTP basic authentication (.htaccess)? If yes, please set the username and password for authentication here.', 'backwpup' ); ?></p>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="httpauthuser"><?php _e( 'Username:', 'backwpup' ); ?></label></th>
                    <td>
                        <input name="httpauthuser" type="text" id="httpauthuser"
                               value="<?php echo get_site_option( 'backwpup_cfg_httpauthuser' );?>"
                               class="regular-text" autocomplete="off" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="httpauthpassword"><?php _e( 'Password:', 'backwpup' ); ?></label></th>
                    <td>
                        <input name="httpauthpassword" type="password" id="httpauthpassword"
                               value="<?php echo BackWPup_Encryption::decrypt( get_site_option( 'backwpup_cfg_httpauthpassword' ) );?>"
                               class="regular-text" autocomplete="off" />
                </tr>
            </table>

        </div>

        <div class="table ui-tabs-hide" id="backwpup-tab-apikey">

			<?php do_action( 'backwpup_page_settings_tab_apikey' ); ?>

        </div>

        <div class="table ui-tabs-hide" id="backwpup-tab-information">
			<br />
			<?php
			echo '<table class="wp-list-table widefat fixed" cellspacing="0" style="width: 85%;margin-left:auto;;margin-right:auto;">';
			echo '<thead><tr><th width="35%">' . __( 'Setting', 'backwpup' ) . '</th><th>' . __( 'Value', 'backwpup' ) . '</th></tr></thead>';
			echo '<tfoot><tr><th>' . __( 'Setting', 'backwpup' ) . '</th><th>' . __( 'Value', 'backwpup' ) . '</th></tr></tfoot>';
			echo '<tr title="&gt;=3.2"><td>' . __( 'WordPress version', 'backwpup' ) . '</td><td>' . BackWPup::get_plugin_data( 'wp_version' ) . '</td></tr>';
			if ( ! class_exists( 'BackWPup_Pro', FALSE ) )
				echo '<tr title=""><td>' . __( 'BackWPup version', 'backwpup' ) . '</td><td>' . BackWPup::get_plugin_data( 'Version' ) . ' <a href="' . translate( BackWPup::get_plugin_data( 'pluginuri' ), 'backwpup' ) . '">' . __( 'Get pro.', 'backwpup' ) . '</a></td></tr>';
			else
				echo '<tr title=""><td>' . __( 'BackWPup Pro version', 'backwpup' ) . '</td><td>' . BackWPup::get_plugin_data( 'Version' ) . '</td></tr>';
			echo '<tr title="&gt;=5.3.3"><td>' . __( 'PHP version', 'backwpup' ) . '</td><td>' . PHP_VERSION . '</td></tr>';
			echo '<tr title="&gt;=5.0.7"><td>' . __( 'MySQL version', 'backwpup' ) . '</td><td>' . $wpdb->get_var( "SELECT VERSION() AS version" ) . '</td></tr>';
			if ( function_exists( 'curl_version' ) ) {
				$curlversion = curl_version();
				echo '<tr title=""><td>' . __( 'cURL version', 'backwpup' ) . '</td><td>' . $curlversion[ 'version' ] . '</td></tr>';
				echo '<tr title=""><td>' . __( 'cURL SSL version', 'backwpup' ) . '</td><td>' . $curlversion[ 'ssl_version' ] . '</td></tr>';
			}
			else {
				echo '<tr title=""><td>' . __( 'cURL version', 'backwpup' ) . '</td><td>' . __( 'unavailable', 'backwpup' ) . '</td></tr>';
			}
			echo '<tr title=""><td>' . __( 'WP-Cron url:', 'backwpup' ) . '</td><td>' . site_url( 'wp-cron.php' ) . '</td></tr>';
			//response test
			echo '<tr><td>' . __( 'Server self connect:', 'backwpup' ) . '</td><td>';
			$raw_response = BackWPup_Job::get_jobrun_url( 'test' );
			$test_result = '';
			if ( is_wp_error( $raw_response ) )
				$test_result .= sprintf( __( 'The HTTP response test get an error "%s"','backwpup' ), $raw_response->get_error_message() );
			elseif ( 200 != wp_remote_retrieve_response_code( $raw_response ) && 204 != wp_remote_retrieve_response_code( $raw_response ) )
				$test_result .= sprintf( __( 'The HTTP response test get a false http status (%s)','backwpup' ), wp_remote_retrieve_response_code( $raw_response ) );
			$headers = wp_remote_retrieve_headers( $raw_response );
			if ( isset( $headers['x-backwpup-ver'] ) && $headers['x-backwpup-ver'] != BackWPup::get_plugin_data( 'version' ) )
				$test_result .= sprintf( __( 'The BackWPup HTTP response header returns a false value: "%s"','backwpup' ), $headers['x-backwpup-ver'] );

			if ( empty( $test_result ) )
				_e( 'Response Test O.K.', 'backwpup' );
			else
				echo $test_result;
			echo '</td></tr>';
			//folder test
			echo '<tr><td>' . __( 'Temp folder:', 'backwpup' ) . '</td><td>';
			if ( ! is_dir( BackWPup::get_plugin_data( 'TEMP' ) ) )
				echo sprintf( __( 'Temp folder %s doesn\'t exist.','backwpup' ), BackWPup::get_plugin_data( 'TEMP' ) );
			elseif ( ! is_writable( BackWPup::get_plugin_data( 'TEMP' ) ) )
				echo sprintf( __( 'Temporary folder %s is not writable.','backwpup' ), BackWPup::get_plugin_data( 'TEMP' ) );
			else
				echo BackWPup::get_plugin_data( 'TEMP' );
			echo '</td></tr>';

			echo '<tr><td>' . __( 'Log folder:', 'backwpup' ) . '</td><td>';
			if ( ! is_dir(  get_site_option( 'backwpup_cfg_logfolder' ) ) )
				echo sprintf( __( 'Logs folder %s not exist.','backwpup' ),  get_site_option( 'backwpup_cfg_logfolder' ) );
			elseif ( ! is_writable(  get_site_option( 'backwpup_cfg_logfolder' ) ) )
				echo sprintf( __( 'Log folder %s is not writable.','backwpup' ),  get_site_option( 'backwpup_cfg_logfolder' ) );
			else
				echo  get_site_option( 'backwpup_cfg_logfolder' );
			echo '</td></tr>';
			echo '<tr title=""><td>' . __( 'Server', 'backwpup' ) . '</td><td>' . $_SERVER[ 'SERVER_SOFTWARE' ] . '</td></tr>';
			echo '<tr title=""><td>' . __( 'Operating System', 'backwpup' ) . '</td><td>' . PHP_OS . '</td></tr>';
			echo '<tr title=""><td>' . __( 'PHP SAPI', 'backwpup' ) . '</td><td>' . PHP_SAPI . '</td></tr>';
			echo '<tr title=""><td>' . __( 'Current PHP user', 'backwpup' ) . '</td><td>' . get_current_user() . '</td></tr>';
			$text  = (bool)ini_get( 'safe_mode' ) ? __( 'On', 'backwpup' ) : __( 'Off', 'backwpup' );
			echo '<tr title=""><td>' . __( 'Safe Mode', 'backwpup' ) . '</td><td>' . $text . '</td></tr>';
			echo '<tr title="&gt;=30"><td>' . __( 'Maximum execution time', 'backwpup' ) . '</td><td>' . ini_get( 'max_execution_time' ) . ' ' . __( 'seconds', 'backwpup' ) . '</td></tr>';
			if ( defined( 'ALTERNATE_WP_CRON' ) && ALTERNATE_WP_CRON )
				echo '<tr title="ALTERNATE_WP_CRON"><td>' . __( 'Alternative WP Cron', 'backwpup' ) . '</td><td>' . __( 'On', 'backwpup' ) . '</td></tr>';
			else
				echo '<tr title="ALTERNATE_WP_CRON"><td>' . __( 'Alternative WP Cron', 'backwpup' ) . '</td><td>' . __( 'Off', 'backwpup' ) . '</td></tr>';
			if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON )
				echo '<tr title="DISABLE_WP_CRON"><td>' . __( 'Disabled WP Cron', 'backwpup' ) . '</td><td>' . __( 'On', 'backwpup' ) . '</td></tr>';
			else
				echo '<tr title="DISABLE_WP_CRON"><td>' . __( 'Disabled WP Cron', 'backwpup' ) . '</td><td>' . __( 'Off', 'backwpup' ) . '</td></tr>';
			if ( defined( 'FS_CHMOD_DIR' ) )
				echo '<tr title="FS_CHMOD_DIR"><td>' . __( 'CHMOD Dir', 'backwpup' ) . '</td><td>' . FS_CHMOD_DIR . '</td></tr>';
			else
				echo '<tr title="FS_CHMOD_DIR"><td>' . __( 'CHMOD Dir', 'backwpup' ) . '</td><td>0755</td></tr>';
			$now = localtime( time(), TRUE );
			echo '<tr title=""><td>' . __( 'Server Time', 'backwpup' ) . '</td><td>' . $now[ 'tm_hour' ] . ':' . $now[ 'tm_min' ] . '</td></tr>';
			echo '<tr title=""><td>' . __( 'Blog Time', 'backwpup' ) . '</td><td>' . date_i18n( 'H:i' ) . '</td></tr>';
			echo '<tr title=""><td>' . __( 'Blog Timezone', 'backwpup' ) . '</td><td>' . get_option( 'timezone_string' ) . '</td></tr>';
			echo '<tr title=""><td>' . __( 'Blog Time offset', 'backwpup' ) . '</td><td>' . sprintf( __( '%s hours', 'backwpup' ), get_option( 'gmt_offset' ) ) . '</td></tr>';
			echo '<tr title="WPLANG"><td>' . __( 'Blog language', 'backwpup' ) . '</td><td>' . get_bloginfo( 'language' ) . '</td></tr>';
			echo '<tr title="utf8"><td>' . __( 'MySQL Client encoding', 'backwpup' ) . '</td><td>';
			echo defined( 'DB_CHARSET' ) ? DB_CHARSET : '';
			echo '</td></tr>';
			echo '<tr title="URF-8"><td>' . __( 'Blog charset', 'backwpup' ) . '</td><td>' . get_bloginfo( 'charset' ) . '</td></tr>';
			echo '<tr title="&gt;=128M"><td>' . __( 'PHP Memory limit', 'backwpup' ) . '</td><td>' . ini_get( 'memory_limit' ) . '</td></tr>';
			echo '<tr title="WP_MEMORY_LIMIT"><td>' . __( 'WP memory limit', 'backwpup' ) . '</td><td>' . WP_MEMORY_LIMIT . '</td></tr>';
			echo '<tr title="WP_MAX_MEMORY_LIMIT"><td>' . __( 'WP maximum memory limit', 'backwpup' ) . '</td><td>' . WP_MAX_MEMORY_LIMIT . '</td></tr>';
			echo '<tr title=""><td>' . __( 'Memory in use', 'backwpup' ) . '</td><td>' . size_format( @memory_get_usage( TRUE ), 2 ) . '</td></tr>';
			//disabled PHP functions
			$disabled = ini_get( 'disable_functions' );
			if ( ! empty( $disabled ) ) {
				$disabledarry = explode( ',', $disabled );
				echo '<tr title=""><td>' . __( 'Disabled PHP Functions:', 'backwpup' ) . '</td><td>';
				echo implode( ', ', $disabledarry );
				echo '</td></tr>';
			}
			//Loaded PHP Extensions
			echo '<tr title=""><td>' . __( 'Loaded PHP Extensions:', 'backwpup' ) . '</td><td>';
			$extensions = get_loaded_extensions();
			sort( $extensions );
			echo  implode( ', ', $extensions);
			echo '</td></tr>';
			echo '</table>'
			?>
        </div>

		<?php do_action( 'backwpup_page_settings_tab_content' ); ?>

        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button-primary" value="<?php _e( 'Save Changes', 'backwpup' ); ?>" />
			&nbsp;
			<input type="submit" name="default_settings" id="default_settings" class="button-secondary" value="<?php _e( 'Reset all settings to default', 'backwpup' ); ?>" />
        </p>
    </form>
    </div>
	<?php
	}

}
