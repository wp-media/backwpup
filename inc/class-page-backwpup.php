<?php
/**
 * Render plugin dashboard.
 *
 */
class BackWPup_Page_BackWPup {


	/**
	 * Called on load action.
	 *
	 * @return void
	 */
	public static function load() {
		global $wpdb;

		if ( isset( $_GET[ 'action' ] ) && $_GET[ 'action' ] == 'dbdumpdl' ) {

			//check permissions
			check_admin_referer( 'backwpupdbdumpdl' );

			if ( ! current_user_can( 'backwpup_jobs_edit' ) )
				die();

			//doing dump
			header( "Pragma: public" );
			header( "Expires: 0" );
			header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
			header( "Content-Type: application/octet-stream; charset=". get_bloginfo( 'charset' ) );
			header( "Content-Disposition: attachment; filename=" . DB_NAME . ".sql;" );
			try {
				$sql_dump = new BackWPup_MySQLDump();
				foreach ( $sql_dump->tables_to_dump as $key => $table ) {
					if ( $wpdb->prefix != substr( $table,0 , strlen( $wpdb->prefix ) ) )
						unset( $sql_dump->tables_to_dump[ $key ] );
				}
				$sql_dump->execute();
				unset( $sql_dump );
			} catch ( Exception $e ) {
				die( $e->getMessage() );
			}
			die();
		}
	}

	/**
	 * Enqueue style.
	 *
	 * @return void
	 */
	public static function admin_print_styles() {

		wp_enqueue_style('backwpupgeneral');

	}

	/**
	 * Enqueue script.
	 *
	 * @return void
	 */
	public static function admin_print_scripts() {

		wp_enqueue_script( 'backwpupgeneral' );

	}

	/**
	 * Print the markup.
	 *
	 * @return void
	 */
	public static function page() {
		// get wizards
		$wizards = BackWPup::get_wizards();
		?>
        <div class="wrap">
			<?php screen_icon(); ?>
            <h2><?php echo sprintf( __( '%s Dashboard', 'backwpup' ), BackWPup::get_plugin_data( 'name') ); ?></h2>

            <div style="float:left;width:63%;margin-right:10px;min-width:500px">
			<?php
				if ( class_exists( 'BackWPup_Pro', FALSE ) ) { ?>
					<div class="backwpup-welcome">
						<p><?php _e('Here you can schedule backup plans with a wizard.','backwpup' ) ?><br />
							<?php _e('The backup files can be used to save your whole installation including <code>/wp-content/</code> and push them to an external Backup Service, if you don’t want to save the backups on the same server. With a single backup file you are able to restore an installation.','backwpup'); ?></p>
						<p><?php _e('First set up a job, and plan what you want to save. You can use the wizards or the normal mode. Please note: the plugin author gives no warranty for your data.','backwpup'); ?></p>
					</div>
				<?php } else {?>
					<div class="backwpup-welcome">
						<p><?php _e('Use the short links in the <b>First steps</b> box to schedule backup plans.','backwpup' ) ?><br />
							<?php _e('The backup files can be used to save your whole installation including <code>/wp-content/</code> and push them to an external Backup Service, if you don’t want to save the backups on the same server. With a single backup file you are able to restore an installation.','backwpup'); ?></p>
						<p><?php _e('First set up a job, and plan what you want to save. Please note: the plugin author gives no warranty for your data.','backwpup'); ?></p>
					</div>
				<?php }

				if ( class_exists( 'BackWPup_Pro', FALSE ) ) {
					/* @var BackWPup_Pro_Wizards $wizard_class */

					foreach ( $wizards as $wizard_class ) {
						//check permissions
						if ( ! current_user_can( $wizard_class->info[ 'cap' ] ) )
							continue;
						//get info of wizard
						echo '<div class="wizardbox" id="wizard-' . strtolower( $wizard_class->info[ 'ID' ] ) . '"><form method="get" action="' . network_admin_url( 'admin.php' ) . '">';
						echo '<div class="wizardbox_name">' . $wizard_class->info[ 'name' ] . '</div>';
						echo '<div class="wizardbox_description">' . $wizard_class->info[ 'description' ] . '</div>';
						$conf_names = $wizard_class->get_pre_configurations();
						if ( ! empty ( $conf_names ) ) {
							echo '<select id="wizardbox_pre_conf" name="pre_conf" size="1">';
							foreach( $conf_names as $conf_key => $conf_name) {
								echo '<option value="' . esc_attr( $conf_key ) . '">' . esc_attr( $conf_name ) . '</option>';
							}
							echo '</select>';
						} else {
							echo '<input type="hidden" name="pre_conf" value="" />';
						}
						wp_nonce_field( 'wizard' );
						echo '<input type="hidden" name="page" value="backwpupwizard" />';
						echo '<input type="hidden" name="wizard_start" value="' . esc_attr( $wizard_class->info[ 'ID' ] ) . '" />';
						echo '<div class="wizardbox_start"><input type="submit" name="submit" class="button-primary-bwp" value="' . esc_attr( __( 'Start wizard', 'backwpup' ) ) . '" /></div>';
						echo '</form></div>';
					}
				}
				?><div style="clear:both"><?php
					self::mb_next_jobs();
					self::mb_last_logs();
				?>
				</div>
			</div>

			<div style="width:35%;float:left;">
				<?php if ( ! class_exists( 'BackWPup_Pro', FALSE ) ) { ?>
					<div class="metabox-holder postbox" style="padding-top:0;margin:10px;cursor:auto;width:30%;float:left;min-width:320px">
						<h3 class="hndle" style="cursor: auto;"><span><?php  _e( 'Thank you for using BackWPup!', 'backwpup' ); ?></span></h3>
						<div class="inside backwpuppro">
							<img src="<?php echo BackWPup::get_plugin_data( 'URL' ) . '/images/backwpupbanner-pro.png'; ?>" alt="BackWPup Banner" />
							<?php _e( 'BackWPup Pro offers you first-class premium support and more features like a wizard for scheduled backup jobs, differential backup of changed directories in the cloud and much more!', 'backwpup' ); ?>.
							<div style="text-align: center;margin-top:10px;">
								<a href="<?php _e( 'http://marketpress.com/product/backwpup-pro/', 'backwpup' ); ?>" class="button-primary" title="<?php _e( 'Get BackWPup Pro now', 'backwpup' ); ?>"><?php _e( 'Get BackWPup Pro now', 'backwpup' ); ?></a><br />
							</div>
						</div>
					</div>
				<?php } ?>

				<?php if ( current_user_can( 'backwpup_jobs_edit' ) && current_user_can( 'backwpup_logs' ) && current_user_can( 'backwpup_jobs_start' ) ) {?>
					<div class="metabox-holder postbox" style="padding-top:0;margin:10px;cursor:auto;width:30%;float:left;min-width:320px">
						<h3 class="hndle" style="cursor: auto;"><span><?php  _e( 'First Steps', 'backwpup' ); ?></span></h3>
						<div class="inside">
							<ul style="margin-left: 30px;">
								<?php if ( class_exists( 'BackWPup_Pro', FALSE ) ) { ?>
									<li type="1"><a href="<?php echo wp_nonce_url( network_admin_url( 'admin.php' ) . '?page=backwpupwizard&wizard_start=SYSTEMTEST', 'wizard' ); ?>"><?php  _e( 'Test the installation', 'backwpup' ); ?></a></li>
									<li type="1"><a href="<?php echo wp_nonce_url( network_admin_url( 'admin.php' ) . '?page=backwpupwizard&wizard_start=JOB', 'wizard' ); ?>"><?php  _e( 'Create a Job', 'backwpup' ); ?></a></li>
								<?php } else { ?>
                               		<li type="1"><a href="<?php echo network_admin_url( 'admin.php' ) . '?page=backwpupsettings#backwpup-tab-information'; ?>"><?php  _e( 'Check the installation', 'backwpup' ); ?></a></li>
                                	<li type="1"><a href="<?php echo network_admin_url( 'admin.php' ) . '?page=backwpupeditjob'; ?>"><?php  _e( 'Create a Job', 'backwpup' ); ?></a></li>
								<?php } ?>
								<li type="1"><a href="<?php echo network_admin_url( 'admin.php' ) . '?page=backwpupjobs'; ?>"><?php  _e( 'Run the created job', 'backwpup' ); ?></a></li>
								<li type="1"><a href="<?php echo network_admin_url( 'admin.php' ) . '?page=backwpuplogs'; ?>"><?php  _e( 'Check the job log', 'backwpup' ); ?></a></li>
							</ul>
						</div>
					</div>
				<?php }

				if ( current_user_can( 'backwpup_jobs_start' ) ) {?>
					<div class="metabox-holder postbox" style="padding-top:0;margin:10px;cursor:auto;width:30%;float:left;min-width:320px">
						<h3 class="hndle" style="cursor: auto;"><span><?php  _e( 'One click backup', 'backwpup' ); ?></span></h3>
						<div class="inside" style="text-align: center;">
							<a href="<?php echo wp_nonce_url( network_admin_url( 'admin.php' ). '?page=backwpup&action=dbdumpdl', 'backwpupdbdumpdl' ); ?>" class="button-primary" title="<?php _e( 'Generate a database backup of WordPress tables and download it right away!', 'backwpup' ); ?>"><?php _e( 'Download database backup', 'backwpup' ); ?></a><br />
						</div>
					</div>
				<?php }	?>
			</div>
        </div>
	<?php
	}

	/**
	 * Displaying last logs
	 */
	private static function mb_last_logs() {

		if ( ! current_user_can( 'backwpup_logs' ) )
			return;
		?>
		<table class="wp-list-table widefat" cellspacing="0" style="margin:10px;width:47%;float:left;clear:none;min-width:300px">
			<thead>
			<tr><th colspan="3" style="font-size:15px"><?php _e( 'Last logs', 'backwpup' ); ?></tr>
			<tr><th style="width:30%"><?php _e( 'Time', 'backwpup' ); ?></th><th style="width:55%"><?php  _e( 'Job', 'backwpup' ); ?></th><th style="width:20%"><?php  _e( 'Result', 'backwpup' ); ?></th></tr>
			</thead>
			<?php
			//get log files
			$logfiles = array();
			if ( is_writeable( BackWPup_Option::get( 'cfg', 'logfolder' ) ) && $dir = @opendir( BackWPup_Option::get( 'cfg', 'logfolder' ) ) ) {
				while ( ( $file = readdir( $dir ) ) !== FALSE ) {
					if ( is_readable( BackWPup_Option::get( 'cfg', 'logfolder' ) . $file ) && ! is_link( BackWPup_Option::get( 'cfg', 'logfolder' ) . $file ) && ! is_dir( BackWPup_Option::get( 'cfg', 'logfolder' ) . $file ) && strstr( $file, 'backwpup_log_' ) && ( strstr( $file, '.html' ) ||  strstr( $file, '.html.gz' ) ) )
						$logfiles[ ] = $file;
				}
				closedir( $dir );
				rsort( $logfiles );
			}

			if ( count( $logfiles ) > 0 ) {
				$count = 0;
				$alternate = TRUE;
				foreach ( $logfiles as $logfile ) {
					$logdata = BackWPup_Job::read_logheader( BackWPup_Option::get( 'cfg', 'logfolder' ) . $logfile );
					if ( ! $alternate ) {
						echo '<tr>';
						$alternate = TRUE;
					} else {
						echo '<tr class="alternate">';
						$alternate = FALSE;
					}
					echo '<td>' . date_i18n( get_option( 'date_format' ) , $logdata[ 'logtime' ] ). '<br />' . date_i18n( get_option( 'time_format' ), $logdata[ 'logtime' ] ) . '</td>';
					echo '<td><a class="thickbox" href="' . admin_url( 'admin-ajax.php' ) . '?&action=backwpup_view_log&logfile=' . basename( $logfile ) .'&_ajax_nonce=' . wp_create_nonce( 'view-logs' ) . '&height=440&width=630&TB_iframe=true" title="' . esc_attr( basename( $logfile ) ) . '">' . $logdata[ 'name' ] . '</i></a></td>';
					echo '<td>';
					if ( $logdata[ 'errors' ] > 0 )
						printf( '<span style="color:red;font-weight:bold;">' . _n( "%d ERROR", "%d ERRORS", $logdata[ 'errors' ], 'backwpup' ) . '</span><br />', $logdata[ 'errors' ] );
					if ( $logdata[ 'warnings' ] > 0 )
						printf( '<span style="color:#e66f00;font-weight:bold;">' . _n( "%d WARNING", "%d WARNINGS", $logdata[ 'warnings' ], 'backwpup' ) . '</span><br />', $logdata[ 'warnings' ] );
					if ( $logdata[ 'errors' ] == 0 && $logdata[ 'warnings' ] == 0 )
						echo '<span style="color:green;font-weight:bold;">' . __( 'OK', 'backwpup' ) . '</span>';
					echo '</td></tr>';
					$count ++;
					if ( $count >= 5 )
						break;
				}
			}
			else {
				echo '<tr><td colspan="3">' . __( 'none', 'backwpup' ) . '</td></tr>';
			}
			?>
		</table>
		<?php
	}

	/**
	 * Displaying next jobs
	 */
	private static function mb_next_jobs() {

		if ( ! current_user_can( 'backwpup_jobs' ) )
			return;
		?>
		<table class="wp-list-table widefat" cellspacing="0" style="margin:10px;width:47%;float:left;clear:none;min-width:300px">
			<thead>
			<tr><th colspan="2" style="font-size:15px"><?php _e( 'Next scheduled jobs', 'backwpup' ); ?></th></tr>
			<tr>
				<th style="width: 30%"><?php  _e( 'Time', 'backwpup' ); ?></th>
				<th style="width: 70%"><?php  _e( 'Job', 'backwpup' ); ?></th>
			</tr>
			</thead>
			<?php
			//get next jobs
			$mainsactive = BackWPup_Option::get_job_ids( 'activetype', 'wpcron' );
			sort( $mainsactive );
			$alternate = TRUE;
			// add working job if it not in active jobs
			$job_array = BackWPup_Job::get_working_data( 'ARRAY' );
			if ( ! empty( $job_array[ 'job' ][ 'jobid' ] ) && ! in_array( $job_array[ 'job' ][ 'jobid' ], $mainsactive ) )
				$mainsactive[ ] = $job_array[ 'job' ][ 'jobid' ];
			foreach ( $mainsactive as $jobid ) {
				$name = BackWPup_Option::get( $jobid, 'name' );
				if ( ! empty( $job_object ) && $job_object->job[ 'jobid' ] == $jobid ) {
					$runtime  = current_time( 'timestamp' ) -  $job_object->job[ 'lastrun' ];
					if ( ! $alternate ) {
						echo '<tr>';
						$alternate = TRUE;
					} else {
						echo '<tr class="alternate">';
						$alternate = FALSE;
					}
					echo '<td>' . sprintf( '<span style="color:#e66f00;">' . __( 'working since %d seconds', 'backwpup' ) . '</span>', $runtime ) . '</td>';
					echo '<td><span style="font-weight:bold;">' . esc_html ( $job_object->job[ 'name' ] ) . '</span><br />';
					echo "<a style=\"color:red;\" href=\"" . wp_nonce_url( network_admin_url( 'admin.php' ) . '?page=backwpupjobs&action=abort', 'abort-job' ) . "\">" . __( 'Abort', 'backwpup' ) . "</a>";
					echo "</td></tr>";
				}
				else {
					if ( ! $alternate ) {
						echo '<tr>';
						$alternate = TRUE;
					} else {
						echo '<tr class="alternate">';
						$alternate = FALSE;
					}
					if ( $nextrun = wp_next_scheduled( 'backwpup_cron', array( 'id' => $jobid ) ) + ( get_option( 'gmt_offset' ) * 3600 ) )
						echo '<td>' . date_i18n( get_option( 'date_format' ), $nextrun, TRUE ) . '<br />' . date_i18n( get_option( 'time_format' ), $nextrun, TRUE ) . '</td>';
					else
						echo '<td><em>' . __( 'Not scheduled!', 'backwpup' ) . '</em></td>';

					echo '<td><a href="' . wp_nonce_url( network_admin_url( 'admin.php' ) . '?page=backwpupeditjob&jobid=' . $jobid, 'edit-job' ) . '" title="' . esc_attr( __( 'Edit Job', 'backwpup' ) ) . '">' . $name . '</a></td></tr>';
				}
			}
			if ( empty( $mainsactive ) and ! empty( $job_object ) ) {
				echo '<tr><td colspan="2"><i>' . __( 'none', 'backwpup' ) . '</i></td></tr>';
			}
			?>
		</table>
		<?php
	}


}
