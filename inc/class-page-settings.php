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

		wp_enqueue_script( 'backwpup_clipboard' );

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
			delete_site_option( 'backwpup_cfg_loglevel' );
			delete_site_option( 'backwpup_cfg_jobwaittimems' );
			delete_site_option( 'backwpup_cfg_jobrunauthkey' );
			delete_site_option( 'backwpup_cfg_jobdooutput' );
			delete_site_option( 'backwpup_cfg_windows' );
			delete_site_option( 'backwpup_cfg_maxlogs' );
			delete_site_option( 'backwpup_cfg_gzlogs' );
			delete_site_option( 'backwpup_cfg_protectfolders' );
			delete_site_option( 'backwpup_cfg_authentication' );
			delete_site_option( 'backwpup_cfg_logfolder' );
			delete_site_option( 'backwpup_cfg_dropboxappkey' );
			delete_site_option( 'backwpup_cfg_dropboxappsecret' );
			delete_site_option( 'backwpup_cfg_dropboxsandboxappkey' );
			delete_site_option( 'backwpup_cfg_dropboxsandboxappsecret' );
			delete_site_option( 'backwpup_cfg_sugarsynckey' );
			delete_site_option( 'backwpup_cfg_sugarsyncsecret' );
			delete_site_option( 'backwpup_cfg_sugarsyncappid' );
			delete_site_option( 'backwpup_cfg_hash' );

			BackWPup_Option::default_site_options();

			BackWPup_Admin::message( __( 'Settings reset to default', 'backwpup' ) );
			return;
		}

		update_site_option( 'backwpup_cfg_showadminbar', ! empty( $_POST[ 'showadminbarmenu' ] ) );
		update_site_option( 'backwpup_cfg_showfoldersize', ! empty( $_POST[ 'showfoldersize' ] ) );
		if ( empty( $_POST[ 'jobstepretry' ] ) || 100 < $_POST[ 'jobstepretry' ] || 1 > $_POST[ 'jobstepretry' ] ) {
			$_POST[ 'jobstepretry' ] = 3;
		}
		update_site_option( 'backwpup_cfg_jobstepretry', absint( $_POST[ 'jobstepretry' ] ) );
		if ( (int) $_POST[ 'jobmaxexecutiontime' ] > 300 ) {
			$_POST[ 'jobmaxexecutiontime' ] = 300;
		}
		update_site_option( 'backwpup_cfg_jobmaxexecutiontime', absint( $_POST[ 'jobmaxexecutiontime' ] ) );
		update_site_option( 'backwpup_cfg_loglevel', in_array( $_POST[ 'loglevel' ], array( 'normal_translated', 'normal', 'debug_translated', 'debug' ), true ) ? $_POST[ 'loglevel' ] : 'normal_translated' );
		update_site_option( 'backwpup_cfg_jobwaittimems', absint( $_POST[ 'jobwaittimems' ] ) );
		update_site_option( 'backwpup_cfg_jobdooutput', ! empty( $_POST[ 'jobdooutput' ] ) );
		update_site_option( 'backwpup_cfg_windows', ! empty( $_POST[ 'windows' ] ) );
		update_site_option( 'backwpup_cfg_maxlogs', absint( $_POST[ 'maxlogs' ] ) );
		update_site_option( 'backwpup_cfg_gzlogs', ! empty( $_POST[ 'gzlogs' ] ) );
		update_site_option( 'backwpup_cfg_protectfolders', ! empty( $_POST[ 'protectfolders' ] ) );
		$_POST[ 'jobrunauthkey' ] = preg_replace( '/[^a-zA-Z0-9]/', '', trim( $_POST[ 'jobrunauthkey' ] ) );
		update_site_option( 'backwpup_cfg_jobrunauthkey', $_POST[ 'jobrunauthkey' ] );
		$_POST[ 'logfolder' ] = trailingslashit( str_replace( '\\', '/', trim( stripslashes( sanitize_text_field( $_POST[ 'logfolder' ] ) ) ) ) );
		//set def. folders
		if ( empty( $_POST[ 'logfolder' ] ) || $_POST[ 'logfolder' ] === '/' ) {
			delete_site_option( 'backwpup_cfg_logfolder' );
			BackWPup_Option::default_site_options();
		} else {
			update_site_option( 'backwpup_cfg_logfolder', $_POST[ 'logfolder' ] );
		}

		$authentication = get_site_option( 'backwpup_cfg_authentication', array( 'method' => '', 'basic_user' => '', 'basic_password' => '', 'user_id' => 0, 'query_arg' => '' ) );
		$authentication[ 'method' ] = ( in_array( $_POST[ 'authentication_method' ], array( 'user', 'basic', 'query_arg' ), true ) ) ? $_POST[ 'authentication_method' ] : '';
		$authentication[ 'basic_user' ] = sanitize_text_field( $_POST[ 'authentication_basic_user' ] );
		$authentication[ 'basic_password' ] = BackWPup_Encryption::encrypt( (string) $_POST[ 'authentication_basic_password' ] );
		$authentication[ 'query_arg' ] =  sanitize_text_field( $_POST[ 'authentication_query_arg' ] );
		$authentication[ 'user_id' ] = absint( $_POST[ 'authentication_user_id' ] );
		update_site_option( 'backwpup_cfg_authentication', $authentication );
		delete_site_transient( 'backwpup_cookies' );

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
		<h1><?php echo sprintf( __( '%s &rsaquo; Settings', 'backwpup' ), BackWPup::get_plugin_data( 'name' ) ); ?></h1>
		<?php
		$tabs = array( 'general' => __( 'General', 'backwpup' ), 'job' => __( 'Jobs', 'backwpup' ), 'log' => __( 'Logs', 'backwpup' ), 'net' => __( 'Network', 'backwpup' ), 'apikey' => __( 'API Keys', 'backwpup' ), 'information' => __( 'Information', 'backwpup' ) );
		$tabs = apply_filters( 'backwpup_page_settings_tab', $tabs );
		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $tabs as $id => $name ) {
			echo '<a href="#backwpup-tab-' . esc_attr( $id ) . '" class="nav-tab">' . esc_attr( $name ). '</a>';
		}
		echo '</h2>';
		BackWPup_Admin::display_messages();
		?>

    <form id="settingsform" action="<?php echo admin_url( 'admin-post.php' ); ?>" method="post">
		<?php wp_nonce_field( 'backwpupsettings_page' ); ?>
        <input type="hidden" name="page" value="backwpupsettings" />
	    <input type="hidden" name="action" value="backwpup" />
    	<input type="hidden" name="anchor" value="#backwpup-tab-general" />

		<div class="table ui-tabs-hide" id="backwpup-tab-general">

			<h3 class="title"><?php _e( 'Display Settings', 'backwpup' ); ?></h3>
            <p><?php _e( 'Do you want to see BackWPup in the WordPress admin bar?', 'backwpup' ); ?></p>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e( 'Admin bar', 'backwpup' ); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span><?php _e( 'Admin Bar', 'backwpup' ); ?></span></legend>
                            <label for="showadminbarmenu">
                                <input name="showadminbarmenu" type="checkbox" id="showadminbarmenu" value="1" <?php checked( get_site_option( 'backwpup_cfg_showadminbar' ), TRUE ); ?> />
								<?php _e( 'Show BackWPup links in admin bar.', 'backwpup' ); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Folder sizes', 'backwpup' ); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span><?php _e( 'Folder sizes', 'backwpup' ); ?></span></legend>
                            <label for="showfoldersize">
                                <input name="showfoldersize" type="checkbox" id="showfoldersize" value="1" <?php checked( get_site_option( 'backwpup_cfg_showfoldersize' ), TRUE ); ?> />
								<?php _e( 'Display folder sizes in the files tab when editing a job. (Might increase loading time of files tab.)', 'backwpup' ); ?>
                            </label>
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
                            <legend class="screen-reader-text"><span><?php _e( 'Protect folders', 'backwpup' ); ?></span></legend>
                            <label for="protectfolders">
                                <input name="protectfolders" type="checkbox" id="protectfolders" value="1" <?php checked( get_site_option( 'backwpup_cfg_protectfolders' ), TRUE ); ?> />
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
                        <input name="logfolder" type="text" id="logfolder" value="<?php echo esc_attr( get_site_option( 'backwpup_cfg_logfolder' ) );?>" class="regular-text code"/>
	                    <p class="description"><?php echo sprintf( __( 'You can use absolute or relative path! Relative path is relative to %s.', 'backwpup' ), '<code>' . trailingslashit( str_replace( '\\', '/', WP_CONTENT_DIR ) ) .'</code>' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="maxlogs"><?php _e( 'Maximum log files', 'backwpup' ); ?></label></th>
                    <td>
                        <input name="maxlogs" type="number" min="0" step="1" id="maxlogs" value="<?php echo absint( get_site_option( 'backwpup_cfg_maxlogs' ) );?>" class="small-text"/>
	                    <?php _e( 'Maximum log files in folder.', 'backwpup' ); ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Compression', 'backwpup' ); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span><?php _e( 'Compression', 'backwpup' ); ?></span></legend>
                            <label for="gzlogs">
                                <input name="gzlogs" type="checkbox" id="gzlogs" value="1" <?php checked( get_site_option( 'backwpup_cfg_gzlogs' ), TRUE ); ?><?php if ( ! function_exists( 'gzopen' ) ) echo ' disabled="disabled"'; ?> />
								<?php _e( 'Compress log files with GZip.', 'backwpup' ); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
	            <tr>
		            <th scope="row"><?php _e( 'Logging Level', 'backwpup' ); ?></th>
		            <td>
			            <fieldset>
				            <legend class="screen-reader-text"><span><?php _e( 'Logging Level', 'backwpup' ); ?></span></legend>
				            <label for="loglevel">
					            <select name="loglevel" size="1">
						            <option value="normal_translated" <?php selected( get_site_option( 'backwpup_cfg_loglevel', 'normal_translated' ), 'normal_translated' ); ?>><?php _e( 'Normal (translated)', 'backwpup' ); ?></option>
						            <option value="normal" <?php selected( get_site_option( 'backwpup_cfg_loglevel' ), 'normal' ); ?>><?php _e( 'Normal (not translated)', 'backwpup' ); ?></option>
						            <option value="debug_translated" <?php selected( get_site_option( 'backwpup_cfg_loglevel' ), 'debug_translated' ); ?>><?php _e( 'Debug (translated)', 'backwpup' ); ?></option>
						            <option value="debug" <?php selected( get_site_option( 'backwpup_cfg_loglevel' ), 'debug' ); ?>><?php _e( 'Debug (not translated)', 'backwpup' ); ?></option>
					            </select>
				            </label>
				            <p class="description"><?php esc_attr_e( 'Debug log has much more informations than normal logs. It is for support and should be handled carefully. For support is the best to use a not translated log file. Usage of not translated logs can reduce the PHP memory usage too.', 'backwpup' ); ?></p>
			            </fieldset>
		            </td>
	            </tr>
            </table>

        </div>
        <div class="table ui-tabs-hide" id="backwpup-tab-job">

            <p><?php _e( 'There are a couple of general options for backup jobs. Set them here.', 'backwpup' ); ?></p>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="jobstepretry"><?php _e( "Maximum number of retries for job steps", 'backwpup' ); ?></label></th>
                    <td>
                        <input name="jobstepretry" type="number" min="1" step="1" max="99" id="jobstepretry" value="<?php echo absint( get_site_option( 'backwpup_cfg_jobstepretry' ) );?>" class="small-text" />
                    </td>
                </tr>
				<tr>
                    <th scope="row"><?php _e( 'Maximum script execution time', 'backwpup' ); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span><?php _e( 'Maximum PHP Script execution time', 'backwpup' ); ?></span></legend>
                            <label for="jobmaxexecutiontime">
                                <input name="jobmaxexecutiontime" type="number" min="0" step="1" max="300" id="jobmaxexecutiontime" value="<?php echo absint( get_site_option( 'backwpup_cfg_jobmaxexecutiontime' ) ); ?>" class="small-text" />
								<?php _e( 'seconds.', 'backwpup' ); ?>
	                            <p class="description"><?php _e( 'Job will restart before hitting maximum execution time. Restarts will be disabled on CLI usage. If <code>ALTERNATE_WP_CRON</code> has been defined, WordPress Cron will be used for restarts, so it can take a while. 0 means no maximum.', 'backwpup' ); ?></p>
							</label>
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="jobrunauthkey"><?php _e( 'Key to start jobs externally with an URL', 'backwpup' ); ?></label>
                    </th>
                    <td>
                        <input name="jobrunauthkey" type="text" id="jobrunauthkey" value="<?php echo esc_attr( get_site_option( 'backwpup_cfg_jobrunauthkey' ) );?>" class="text code"/>
	                    <p class="description"><?php _e( 'Will be used to protect job starts from unauthorized person.', 'backwpup' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Reduce server load', 'backwpup' ); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><span><?php _e( 'Reduce server load', 'backwpup' ); ?></span></legend>
                            <label for="jobwaittimems">
								<select name="jobwaittimems" size="1">
									<option value="0" <?php selected( get_site_option( 'backwpup_cfg_jobwaittimems' ), 0 ); ?>><?php _e( 'disabled', 'backwpup' ); ?></option>
                                    <option value="10000" <?php selected( get_site_option( 'backwpup_cfg_jobwaittimems' ), 10000 ); ?>><?php _e( 'minimum', 'backwpup' ); ?></option>
                                    <option value="30000" <?php selected( get_site_option( 'backwpup_cfg_jobwaittimems' ), 30000 ); ?>><?php _e( 'medium', 'backwpup' ); ?></option>
                                    <option value="90000" <?php selected( get_site_option( 'backwpup_cfg_jobwaittimems' ), 90000 ); ?>><?php _e( 'maximum', 'backwpup' ); ?></option>
                                </select>
                            </label>
	                        <p class="description"><?php _e( 'This adds short pauses to the process. Can be used to reduce the CPU load.', 'backwpup' ); ?></p>
                        </fieldset>
                    </td>
                </tr>
	            <tr>
		            <th scope="row"><?php _e( 'Empty output on working', 'backwpup' ); ?></th>
		            <td>
			            <fieldset>
				            <legend class="screen-reader-text"><span><?php _e( 'Enable an empty Output on backup working.', 'backwpup' ); ?></span></legend>
				            <label for="jobdooutput">
					            <input name="jobdooutput" type="checkbox" id="jobdooutput" value="1" <?php checked( get_site_option( 'backwpup_cfg_jobdooutput' ), TRUE ); ?> />
					            <?php _e( 'Enable an empty Output on backup working.', 'backwpup' ); ?>
				            </label>
				            <p class="description"><?php _e( 'This do an empty output on job working. This can help in some situations or can brake the working. You must test it.', 'backwpup' ); ?></p>
			            </fieldset>
		            </td>
	            </tr>
	            <tr>
		            <th scope="row"><?php _e( 'Windows IIS compatibility', 'backwpup' ); ?></th>
		            <td>
			            <fieldset>
				            <legend class="screen-reader-text"><span><?php _e( 'Enable compatibility with IIS on Windows.', 'backwpup' ); ?></span></legend>
				            <label for="windows">
					            <input name="windows" type="checkbox" id="windows" value="1"<?php checked( get_site_option( 'backwpup_cfg_windows' ), true ) ?> />
					            <?php _e( 'Enable compatibility with IIS on Windows.', 'backwpup' ); ?>
				            </label>
				            <p class="description"><?php _e( 'There is a PHP bug (<a href="https://bugs.php.net/43817">bug #43817</a>), which is triggered on some versions of Windows and IIS. Checking this box will enable a workaround for that bug. Only enable if you are getting errors about &ldquo;Permission denied&rdquo; in your logs.', 'backwpup' ) ?></p>
			            </fieldset>
		            </td>
	            </tr>
            </table>

        </div>

        <div class="table ui-tabs-hide" id="backwpup-tab-net">

			<h3><?php echo sprintf( __( 'Authentication for <code>%s</code>', 'backwpup' ), site_url( 'wp-cron.php' ) ); ?></h3>
            <p><?php _e( 'If you protected your blog with HTTP basic authentication (.htaccess), or you use a Plugin to secure wp-cron.php, then use the authentication methods below.', 'backwpup' ); ?></p>
            <?php
                $authentication = get_site_option( 'backwpup_cfg_authentication', array( 'method' => '', 'basic_user' => '', 'basic_password' => '', 'user_id' => 0, 'query_arg' => '' ) );
            ?>
	        <table class="form-table">
	            <tr>
		            <th scope="row"><?php _e( 'Authentication method', 'backwpup' ); ?></th>
		            <td>
			            <fieldset>
				            <legend class="screen-reader-text"><span><?php _e( 'Authentication method', 'backwpup' ); ?></span></legend>
				            <label for="authentication_method">
					            <select name="authentication_method" id="authentication_method" size="1" >
						            <option value="" <?php selected( $authentication[ 'method' ], '' ); ?>><?php _e( 'none', 'backwpup' ); ?></option>
						            <option value="basic" <?php selected( $authentication[ 'method' ], 'basic' ); ?>><?php _e( 'Basic auth', 'backwpup' ); ?></option>
						            <option value="user" <?php selected( $authentication[ 'method' ], 'user' ); ?>><?php _e( 'WordPress User', 'backwpup' ); ?></option>
						            <option value="query_arg" <?php selected( $authentication[ 'method' ], 'query_arg' ); ?>><?php _e( 'Query argument', 'backwpup' ); ?></option>
					            </select>
				            </label>
			            </fieldset>
		            </td>
	            </tr>
                <tr class="authentication_basic" <?php if ( $authentication[ 'method' ] !== 'basic' ) echo 'style="display:none"'; ?>>
                    <th scope="row"><label for="authentication_basic_user"><?php _e( 'Basic Auth Username:', 'backwpup' ); ?></label></th>
                    <td>
                        <input name="authentication_basic_user" type="text" id="authentication_basic_user" value="<?php echo esc_attr( $authentication[ 'basic_user' ] );?>" class="regular-text" autocomplete="off" />
                    </td>
                </tr>
                <tr class="authentication_basic" <?php if ( $authentication[ 'method' ] !== 'basic' ) echo 'style="display:none"'; ?>>
			        <th scope="row"><label for="authentication_basic_password"><?php _e( 'Basic Auth Password:', 'backwpup' ); ?></label></th>
			        <td>
				        <input name="authentication_basic_password" type="password" id="authentication_basic_password" value="<?php echo esc_attr( BackWPup_Encryption::decrypt( $authentication[ 'basic_password' ] ) );?>" class="regular-text" autocomplete="off" />
		        </tr>
		        <tr class="authentication_user" <?php if ( $authentication[ 'method' ] !== 'user' ) echo 'style="display:none"'; ?>>
			        <th scope="row"><?php _e( 'Select WordPress User', 'backwpup' ); ?></th>
			        <td>
				        <fieldset>
					        <legend class="screen-reader-text"><span><?php _e( 'Select WordPress User', 'backwpup' ); ?></span>
					        </legend>
					        <label for="authentication_user_id">
						        <select name="authentication_user_id" size="1" >
							        <?php
							        $users = get_users( array( 'role' => 'administrator', 'number' => 99, 'orderby' => 'display_name' ) );
							        foreach ( $users as $user ) {
								        echo '<option value="' . $user->ID . '" '. selected( $authentication[ 'user_id' ], $user->ID, FALSE ) .'>'. esc_attr( $user->display_name ) .'</option>';
							        }
							        ?>
						        </select>
					        </label>
				        </fieldset>
			        </td>
		        </tr>
		        <tr class="authentication_query_arg" <?php if ( $authentication[ 'method' ] != 'query_arg' ) echo 'style="display:none"'; ?>>
			        <th scope="row"><label for="authentication_query_arg"><?php _e( 'Query arg key=value:', 'backwpup' ); ?></label></th>
			        <td>
				        ?<input name="authentication_query_arg" type="text" id="authentication_query_arg" value="<?php echo esc_attr( $authentication[ 'query_arg' ] );?>" class="regular-text" />
			        </td>
		        </tr>
            </table>

        </div>

        <div class="table ui-tabs-hide" id="backwpup-tab-apikey">

			<?php do_action( 'backwpup_page_settings_tab_apikey' ); ?>

        </div>

        <div class="table ui-tabs-hide" id="backwpup-tab-information">
			<br />
			<?php
			$information = self::get_information();
			
			echo '<p>';
			_e(
				'Experiencing an issue and need to contact BackWPup support? Click the link below to get debug information you can send to us.',
				'backwpup'
			);
			echo '</p>';
			
			?><p><a href="#TB_inline?height=440&width=630&inlineId=tb-debug-info" id="debug-button" class="thickbox button button-primary" title="<?php _e("Debug Info" , "backwpup");?>">
					<?php _e( 'Get Debug Info', 'backwpup' ) ?></a></p>
					
					<div id="tb-debug-info" tabindex="-1" style="display: none;"><?php
						ob_start();
						?>
						<p><?php _e( 'You will find debug information below. Click the button to copy the debug info to send to support.', 'backwpup' ) ?></p>
						<p><?php _e( '<strong>Note</strong>: ' .
							'Would you like faster, more streamlined support? Pro users can contact BackWPup from right within the plugin.',
							'backwpup'
						) ?> <a href="<?php _e( 'https://backwpup.com', 'backwpup' ) ?>">
								<?php _e( 'Get Pro', 'backwpup' ) ?>
							</a></p>
						<?php
						$html = ob_get_clean();
						echo apply_filters( 'backwpup_get_debug_info_text', $html );
						?>
						<p><a href="#" id="backwpup-copy-debug-info" data-clipboard-target="#backwpup-debug-info" class="button button-primary">
							<?php _e( 'Copy Debug Info', 'backwpup' ) ?>
						</a></p>
						
						<div class="inline" id="backwpup-copy-debug-info-success" style="display:none;">
							<p><span class="dashicons dashicons-yes"></span><?php _e( 'Debug info copied to clipboard.', 'backwpup' ) ?></p>
						</div>
						<div class="inline" id="backwpup-copy-debug-info-error" style="display:none;">
							<p><span class="dashicons dashicons-no"></span><?php _e( 'Could not copy debug info. You can simply press ctrl+C to copy it.', 'backwpup' ) ?></p>
						</div>
						
                                                <textarea id="backwpup-debug-info" readonly="readonly" style="width: 100%;height: 100%;overflow: scroll;"><?php
							foreach ( $information as $item ) {
								echo esc_html( $item['label'] ) . ': ' .
								esc_html( $item['value'] ) . "\n";
							}
                                                ?></textarea>
					</div>

						<script type="text/javascript">
							jQuery(document).ready(function ($) {
								clipboard = new Clipboard('#backwpup-copy-debug-info');
								
								clipboard.on('success', function (e) {
									setTimeout(
										function () {
											$('#backwpup-copy-debug-info-success').attr('style', 'display:inline-block !important;color:green');
										},
										300
									);
									
									setTimeout(
										function () {
											$('#backwpup-copy-debug-info-success').attr('style', 'display:none !important;');
										},
										5000
									);
									e.clearSelection();
								});
								
								clipboard.on('error', function (e) {
									$('backwpup-copy-debug-info-error').attr('style', 'display:inline-block !important;color:red');
								});
								
                                                        
								$('#debug-button').on('click', function () {
									$('#tb-debug-info').focus();
//                                                               $("#TB_ajaxWindowTitle").text("<?php _e("Debug Info");?>");
                                                               $("#TB_ajaxWindowTitle").text("WTF");
								});
							});
						</script>

					<?php
					
			echo '<table class="wp-list-table widefat fixed" cellspacing="0" style="width:100%;margin-left:auto;margin-right:auto;">';
			echo '<thead><tr><th width="35%">' . __( 'Setting', 'backwpup' ) . '</th><th>' . __( 'Value', 'backwpup' ) . '</th></tr></thead>';
			echo '<tfoot><tr><th>' . __( 'Setting', 'backwpup' ) . '</th><th>' . __( 'Value', 'backwpup' ) . '</th></tr></tfoot>';
			foreach ( $information as $item ) {
				echo "<tr>\n" .
					"<td>" . $item['label'] . "</td>\n" .
					"<td>" .
					( isset( $item['html'] ) ? $item['html'] : esc_html( $item['value'] ) ) .
					"</td>\n" .
					"</tr>\n";
			}
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
	
	/**
	 * Get debug information for this installation
	 */
	public static function get_information() {
		global $wpdb;
		
		$information = array();
		
		// Wordpress version
		$information['wpversion']['label'] = __( 'WordPress version', 'backwpup' );
		$information['wpversion']['value'] = BackWPup::get_plugin_data( 'wp_version' );
		
		// BackWPup version
		if ( ! class_exists( 'BackWPup_Pro', false ) ) {
			$information['bwuversion']['label'] = __( 'BackWPup version', 'backwpup' );
			$information['bwuversion']['value'] = BackWPup::get_plugin_data( 'Version' );
			$information['bwuversion']['html'] = BackWPup::get_plugin_data( 'Version' ) .
				' <a href="' . __( 'http://backwpup.com', 'backwpup' ) . '">' .
				__( 'Get pro.', 'backwpup' ) . '</a>';
		} else {
			$information['bwuversion']['label'] = __( 'BackWPup Pro version', 'backwpup' );
			$information['bwuversion']['value'] = BackWPup::get_plugin_data( 'Version' );
		}
		
		// PHP version
		$information['phpversion']['label'] = __( 'PHP version', 'backwpup' );
		$bit = '';
		if ( PHP_INT_SIZE === 4 ) {
			$bit = ' (32bit)';
		} elseif ( PHP_INT_SIZE === 8 ) {
			$bit = ' (64bit)';
		}
		$information['phpversion']['value'] = PHP_VERSION . ' ' . $bit;
		
		// MySQL version
		$information['mysqlversion']['label'] = __( 'MySQL version', 'backwpup' );
		$information['mysqlversion']['value'] = $wpdb->get_var( "SELECT VERSION() AS version" );
		
		// Curl version
		$information['curlversion']['label'] = __( 'cURL version', 'backwpup' );
		if ( function_exists( 'curl_version' ) ) {
			$curl_version = curl_version();
			$information['curlversion']['value'] = $curl_version['version'];
			$information['curlsslversion']['label'] = __( 'cURL SSL version', 'backwpup' );
			$information['curlsslversion']['value'] = $curl_version['ssl_version'];
		} else {
			$information['curlversion']['value'] = __( 'unavailable', 'backwpup' );
		}
		
		// WP cron URL
		$information['wpcronurl']['label'] = __( 'WP-Cron url', 'backwpup' );
		$information['wpcronurl']['value'] = site_url( 'wp-cron.php' );
		
		// Response test
		$server_connect['label'] = __( 'Server self connect', 'backwpup' );
		
		$raw_response = BackWPup_Job::get_jobrun_url( 'test' );
		$response_code = wp_remote_retrieve_response_code( $raw_response );
		$response_body = wp_remote_retrieve_body( $raw_response );
		if ( strstr( $response_body, 'BackWPup test request' ) === false ) {
			$server_connect['value'] = __( 'Not expected HTTP response:', 'backwpup' ) . "\n";
			$server_connect['html'] = __( '<strong>Not expected HTTP response:</strong><br>', 'backwpup' );
			if ( ! $response_code ) {
				$server_connect['value'] .= sprintf( __( 'WP Http Error: %s', 'backwpup' ), $raw_response->get_error_message() ) . "\n";
				$server_connect['html'] = sprintf( __( 'WP Http Error: <code>%s</code>', 'backwpup' ), esc_html( $raw_response->get_error_message() ) ) . '<br>';
			} else {
				$server_connect['value'] .= sprintf( __( 'Status-Code: %d', 'backwpup' ), $response_code ) . "\n";
				$server_connect['html'] .= sprintf( __( 'Status-Code: <code>%d</code>', 'backwpup' ), esc_html( $response_code ) ) . '<br>';
			}
			$response_headers = wp_remote_retrieve_headers( $raw_response );
			foreach( $response_headers as $key => $value ) {
				$server_connect['value'] .= ucfirst( $key ) . ": $value\n";
				$server_connect['html'] .= esc_html( ucfirst( $key ) ) . ': <code>' . esc_html( $value ) . '</code><br>';
			}
			$content = wp_remote_retrieve_body( $raw_response );
			if ( $content ) {
				$server_connect['value'] .= sprintf( __( 'Content: %s', 'backwpup' ), $content );
				$server_connect['html'] .= sprintf( __( 'Content: <code>%s</code>', 'backwpup' ), esc_html( $content ) );
			}
		} else {
			$server_connect['value'] = __( 'Response Test O.K.', 'backwpup' );
		}
		$information['serverconnect'] = $server_connect;
		
		// Document root
		$information['docroot']['label'] = 'Document root';
		$information['docroot']['value'] = $_SERVER['DOCUMENT_ROOT'];
		
		// Temp folder
		$information['tmpfolder']['label'] = __( 'Temp folder', 'backwpup' );
		if ( ! is_dir( BackWPup::get_plugin_data( 'TEMP' ) ) ) {
			$information['tmpfolder']['value'] = sprintf( __( 'Temp folder %s doesn\'t exist.', 'backwpup' ), BackWPup::get_plugin_data( 'TEMP' ) );
		} elseif ( ! is_writable( BackWPup::get_plugin_data( 'TEMP' ) ) ) {
			$information['tmpfolder']['value'] = sprintf( __( 'Temporary folder %s is not writable.', 'backwpup' ), BackWPup::get_plugin_data( 'TEMP' ) );
		} else {
			$information['tmpfolder']['value'] = BackWPup::get_plugin_data( 'TEMP' );
		}
		
		// Log folder
		$information['logfolder']['label'] = __( 'Log folder', 'backwpup' );
		$log_folder = BackWPup_File::get_absolute_path( get_site_option( 'backwpup_cfg_logfolder' ) );
		if ( ! is_dir( $log_folder ) ) {
			$information['logfolder']['value'] = sprintf( __( 'Log folder %s does not exist.','backwpup' ), $log_folder );
		} elseif ( ! is_writable( $log_folder ) ) {
			$information['logfolder']['value'] = sprintf( __( 'Log folder %s is not writable.','backwpup' ), $log_folder );
		} else {
			$information['logfolder']['value'] = $log_folder;
		}
		
		// Server
		$information['server']['label'] = __( 'Server', 'backwpup' );
		$information['server']['value'] = $_SERVER['SERVER_SOFTWARE'];
		
		// OS
		$information['os']['label'] = __( 'Operating System', 'backwpup' );
		$information['os']['value'] = PHP_OS;
		
		// PHP SAPI
		$information['phpsapi']['label'] = __( 'PHP SAPI', 'backwpup' );
		$information['phpsapi']['value'] = PHP_SAPI;
		
		// PHP user
		$information['phpuser']['label'] = __( 'Current PHP user', 'backwpup' );
		if ( function_exists( 'get_current_user' ) ) {
			$information['phpuser']['value'] = get_current_user();
		} else {
			$information['phpuser']['value'] = __( 'Function Disabled', 'backwpup' );
		}
		
		// Maximum execution time
		$information['maxexectime']['label'] = __( 'Maximum execution time', 'backwpup' );
		$information['maxexectime']['value'] = sprintf(
			__( '%d seconds', 'backwpup' ),
			ini_get( 'max_execution_time' )
		);
		
		// Alternate WP cron
		$information['altwpcron']['label'] = __( 'Alternative WP Cron', 'backwpup' );
		if ( defined( 'ALTERNATE_WP_CRON' ) && ALTERNATE_WP_CRON ) {
			$information['altwpcron']['value'] = __( 'On', 'backwpup' );
		} else {
			$information['altwpcron']['value'] = __( 'Off', 'backwpup' );
		}
		
		// Disable WP cron
		$information['disablewpcron']['label'] = __( 'Disabled WP Cron', 'backwpup' );
		if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
			$information['disablewpcron']['value'] = __( 'On', 'backwpup' );
		} else {
			$information['disablewpcron']['value'] = __( 'Off', 'backwpup' );
		}
		
		// CHMOD dir
		$information['chmoddir']['label'] = __( 'CHMOD Dir', 'backwpup' );
		if ( defined( 'FS_CHMOD_DIR' ) ) {
			$information['chmoddir']['value'] = FS_CHMOD_DIR;
		} else {
			$information['chmoddir']['value'] = '0755';
		}
		
		// Server time
		$information['servertime']['label'] = __( 'Server Time', 'backwpup' );
		$now = localtime( time(), TRUE );
		$information['servertime']['value'] = $now['tm_hour'] . ':' . $now['tm_min'];
		
		// Blog time
		$information['blogtime']['label'] = __( 'Blog Time', 'backwpup' );
		$information['blogtime']['value'] = date( 'H:i', current_time( 'timestamp' ) );
		
		// Blog timezone
		$information['blogtz']['label'] = __( 'Blog Timezone', 'backwpup' );
		$information['blogtz']['value'] = get_option( 'timezone_string' );
		
		// Blog time offset
		$information['blogoffset']['label'] = __( 'Blog Time offset', 'backwpup' );
		$information['blogoffset']['value'] = sprintf(
			__( '%s hours', 'backwpup' ),
			(int) get_option( 'gmt_offset' )
		);
		
		// Blog language
		$information['bloglang']['label'] = __( 'Blog language', 'backwpup' );
		$information['bloglang']['value'] = get_bloginfo( 'language' );
		
		// MySQL encoding
		$information['mysqlencoding']['label'] = __( 'MySQL Client encoding', 'backwpup' );
		$information['mysqlencoding']['value'] = defined( 'DB_CHARSET' ) ? DB_CHARSET : '';
		
		// PHP memory limit
		$information['phpmemlimit']['label'] = __( 'PHP Memory limit', 'backwpup' );
		$information['phpmemlimit']['value'] = ini_get( 'memory_limit' );
		
		// WP memory limit
		$information['wpmemlimit']['label'] = __( 'WP memory limit', 'backwpup' );
		$information['wpmemlimit']['value'] = WP_MEMORY_LIMIT;
		
		// WP maximum memory limit
		$information['wpmaxmemlimit']['label'] = __( 'WP maximum memory limit', 'backwpup' );
		$information['wpmaxmemlimit']['value'] = WP_MAX_MEMORY_LIMIT;
		
		// Memory in use
		$information['memusage']['label'] = __( 'Memory in use', 'backwpup' );
		$information['memusage']['value'] = size_format( @memory_get_usage( true ), 2 );
		
		// Disabled PHP functions
		$disabled = esc_html( ini_get( 'disable_functions' ) );
		if ( ! empty( $disabled ) ) {
			$information['disabledfunctions']['label'] = __( 'Disabled PHP Functions:', 'backwpup' );
			$information['disabledfunctions']['value'] = implode( ', ', explode( ',', $disabled ) );
		}
		
		// Loaded PHP extensions
		$information['loadedextensions']['label'] = __( 'Loaded PHP Extensions:', 'backwpup' );
		$extensions = get_loaded_extensions();
		sort( $extensions );
		$information['loadedextensions']['value'] = implode( ', ', $extensions );
		
		return $information;
	}

}
