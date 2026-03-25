<?php

/**
 * BackWPup job edit page.
 */
class BackWPup_Page_Editjob {

	/**
	 * Load the edit_auth method from the destination selected.
	 *
	 * @return void
	 */
	public static function auth() {
		$nonce = filter_input( INPUT_GET, '_wpnonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( ! empty( $nonce ) ) {
			check_admin_referer( 'edit-job' );
		}

		$tab = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$tab = $tab ? sanitize_title_with_dashes( $tab ) : 'job';
		if ( 'dest-' !== substr( $tab, 0, 5 ) && 'jobtype-' !== substr( $tab, 0, 8 ) && ! in_array( $tab, [ 'job', 'cron' ], true ) ) {
			$tab = 'job';
		}

		$_GET['tab'] = $tab;
		if ( 'dest-' === substr( $tab, 0, 5 ) ) {
			$jobid      = absint( filter_input( INPUT_GET, 'jobid', FILTER_SANITIZE_NUMBER_INT ) );
			$id         = strtoupper( str_replace( 'dest-', '', $tab ) );
			$dest_class = BackWPup::get_destination( $id );
			if ( $dest_class && method_exists( $dest_class, 'edit_auth' ) ) {
				$dest_class->edit_auth( $jobid );
			}
		}
	}

	/**
	 * Save form data.
	 *
	 * @param string $tab   Active tab slug.
	 * @param int    $jobid Job ID.
	 *
	 * @return string|void
	 */
	public static function save_post_form( $tab, $jobid ) {
		if ( ! current_user_can( 'backwpup_jobs_edit' ) ) {
			return __( 'Sorry, you don\'t have permissions to do that.', 'backwpup' );
		}

		check_admin_referer( 'backwpupeditjob_page' );

		$post_data = wp_unslash( $_POST );
		$job_types = BackWPup::get_job_types();

		switch ( $tab ) {
			case 'job':
				BackWPup_Option::update( $jobid, 'jobid', $jobid );

				// Set type of backup.
				$backuptype_input = isset( $post_data['backuptype'] ) ? sanitize_text_field( $post_data['backuptype'] ) : '';
				$backuptype       = ( class_exists( \BackWPup_Pro::class, false ) && 'sync' === $backuptype_input ) ? 'sync' : 'archive';
				BackWPup_Option::update( $jobid, 'backuptype', $backuptype );

				$type_post = isset( $post_data['type'] ) ? (array) $post_data['type'] : [];
				$type_post = array_map( 'sanitize_text_field', $type_post );
				// Check existing type.
				foreach ( $type_post as $key => $value ) {
					if ( ! isset( $job_types[ $value ] ) ) {
						unset( $type_post[ $key ] );
					}
				}
				sort( $type_post );
				BackWPup_Option::update( $jobid, 'type', $type_post );

				// Test if job type makes backup.
				$makes_file = false;

					/**
					 * Job type instance.
					 *
					 * @var BackWPup_JobTypes $job_type
					 */
				foreach ( $job_types as $type_id => $job_type ) {
					if ( in_array( $type_id, $type_post, true ) ) {
						if ( $job_type->creates_file() ) {
							$makes_file = true;
							break;
						}
					}
				}

				if ( $makes_file ) {
					$destinations_post = isset( $post_data['destinations'] ) ? (array) $post_data['destinations'] : [];
				} else {
					$destinations_post = [];
				}
				$destinations_post = array_map( 'sanitize_text_field', $destinations_post );

				$destinations = BackWPup::get_registered_destinations();

				foreach ( $destinations_post as $key => $dest_id ) {
					// Remove all destinations that do not exist.
					if ( ! isset( $destinations[ $dest_id ] ) ) {
						unset( $destinations_post[ $key ] );

						continue;
					}
					// If sync remove all non-sync destinations.
					if ( 'sync' === $backuptype ) {
						if ( ! $destinations[ $dest_id ]['can_sync'] ) {
							unset( $destinations_post[ $key ] );
						}
					}
				}
				sort( $destinations_post );
				BackWPup_Option::update( $jobid, 'destinations', $destinations_post );

				$name = isset( $post_data['name'] ) ? sanitize_text_field( $post_data['name'] ) : '';
				$name = trim( (string) $name );
				if ( '' === $name || __( 'New Job', 'backwpup' ) === $name ) {
					$name = sprintf(
						/* translators: %d: job ID. */
						__( 'Job with ID %d', 'backwpup' ),
						$jobid
					);
				}
				BackWPup_Option::update( $jobid, 'name', $name );

				$mailaddresslog_input = isset( $post_data['mailaddresslog'] ) ? sanitize_text_field( $post_data['mailaddresslog'] ) : '';
				$emails               = explode( ',', $mailaddresslog_input );

				foreach ( $emails as $key => $email ) {
					$emails[ $key ] = sanitize_email( trim( $email ) );
					if ( ! is_email( $emails[ $key ] ) ) {
						unset( $emails[ $key ] );
					}
				}
				$mailaddresslog = implode( ', ', $emails );
				BackWPup_Option::update( $jobid, 'mailaddresslog', $mailaddresslog );

				$mailaddresssenderlog = isset( $post_data['mailaddresssenderlog'] ) ? sanitize_text_field( $post_data['mailaddresssenderlog'] ) : '';
				$mailaddresssenderlog = trim( $mailaddresssenderlog );
				BackWPup_Option::update( $jobid, 'mailaddresssenderlog', $mailaddresssenderlog );

				BackWPup_Option::update( $jobid, 'mailerroronly', ! empty( $post_data['mailerroronly'] ) );

				$archiveformat_input = isset( $post_data['archiveformat'] ) ? sanitize_text_field( $post_data['archiveformat'] ) : '';
				$archiveformat       = in_array(
					$archiveformat_input,
					[
						'.zip',
						'.tar',
						'.tar.gz',
					],
					true
				) ? $archiveformat_input : '.zip';
				BackWPup_Option::update( $jobid, 'archiveformat', $archiveformat );

				$archivename_input = isset( $post_data['archivename'] ) ? sanitize_text_field( $post_data['archivename'] ) : '';
				$archivename       = BackWPup_Job::sanitize_file_name(
					BackWPup_Option::normalize_archive_name( $archivename_input, $jobid, false )
				);
				BackWPup_Option::update( $jobid, 'archivename', $archivename );
				break;

			case 'cron':
				$activetype_input = isset( $post_data['activetype'] ) ? sanitize_text_field( $post_data['activetype'] ) : '';
				$activetype       = in_array(
					$activetype_input,
					[
						'',
						'wpcron',
						'link',
					],
					true
				) ? $activetype_input : '';
				BackWPup_Option::update( $jobid, 'activetype', $activetype );

				$cronselect_input = isset( $post_data['cronselect'] ) ? sanitize_text_field( $post_data['cronselect'] ) : '';
				$cronselect       = ( 'advanced' === $cronselect_input ) ? 'advanced' : 'basic';
				BackWPup_Option::update( $jobid, 'cronselect', $cronselect );

				$cronminutes = isset( $post_data['cronminutes'] ) ? (array) $post_data['cronminutes'] : [];
				$cronminutes = array_map( 'sanitize_text_field', $cronminutes );
				$cronhours   = isset( $post_data['cronhours'] ) ? (array) $post_data['cronhours'] : [];
				$cronhours   = array_map( 'sanitize_text_field', $cronhours );
				$cronmday    = isset( $post_data['cronmday'] ) ? (array) $post_data['cronmday'] : [];
				$cronmday    = array_map( 'sanitize_text_field', $cronmday );
				$cronmon     = isset( $post_data['cronmon'] ) ? (array) $post_data['cronmon'] : [];
				$cronmon     = array_map( 'sanitize_text_field', $cronmon );
				$cronwday    = isset( $post_data['cronwday'] ) ? (array) $post_data['cronwday'] : [];
				$cronwday    = array_map( 'sanitize_text_field', $cronwday );

				// Save advanced.
				if ( 'advanced' === $cronselect ) {
					$cronminutes_first = $cronminutes[0] ?? '';
					if ( '' === $cronminutes_first || '*' === $cronminutes_first ) {
						$cronminutes_step = $cronminutes[1] ?? '';
						$cronminutes      = '' !== $cronminutes_step ? [ '*/' . $cronminutes_step ] : [ '*' ];
					}
					$cronhours_first = $cronhours[0] ?? '';
					if ( '' === $cronhours_first || '*' === $cronhours_first ) {
						$cronhours_step = $cronhours[1] ?? '';
						$cronhours      = '' !== $cronhours_step ? [ '*/' . $cronhours_step ] : [ '*' ];
					}
					$cronmday_first = $cronmday[0] ?? '';
					if ( '' === $cronmday_first || '*' === $cronmday_first ) {
						$cronmday_step = $cronmday[1] ?? '';
						$cronmday      = '' !== $cronmday_step ? [ '*/' . $cronmday_step ] : [ '*' ];
					}
					$cronmon_first = $cronmon[0] ?? '';
					if ( '' === $cronmon_first || '*' === $cronmon_first ) {
						$cronmon_step = $cronmon[1] ?? '';
						$cronmon      = '' !== $cronmon_step ? [ '*/' . $cronmon_step ] : [ '*' ];
					}
					$cronwday_first = $cronwday[0] ?? '';
					if ( '' === $cronwday_first || '*' === $cronwday_first ) {
						$cronwday_step = $cronwday[1] ?? '';
						$cronwday      = '' !== $cronwday_step ? [ '*/' . $cronwday_step ] : [ '*' ];
					}
					$cron = implode( ',', $cronminutes ) . ' ' . implode( ',', $cronhours ) . ' ' . implode( ',', $cronmday ) . ' ' . implode( ',', $cronmon ) . ' ' . implode( ',', $cronwday );
					BackWPup_Option::update( $jobid, 'cron', $cron );
				} else {
					// Save basic.
					$cronbtype = isset( $post_data['cronbtype'] ) ? sanitize_text_field( $post_data['cronbtype'] ) : '';
					if ( 'mon' === $cronbtype ) {
						$mon_minutes = isset( $post_data['moncronminutes'] ) ? absint( $post_data['moncronminutes'] ) : 0;
						$mon_hours   = isset( $post_data['moncronhours'] ) ? absint( $post_data['moncronhours'] ) : 0;
						$mon_mday    = isset( $post_data['moncronmday'] ) ? absint( $post_data['moncronmday'] ) : 0;
						BackWPup_Option::update( $jobid, 'cron', $mon_minutes . ' ' . $mon_hours . ' ' . $mon_mday . ' * *' );
					}
					if ( 'week' === $cronbtype ) {
						$week_minutes = isset( $post_data['weekcronminutes'] ) ? absint( $post_data['weekcronminutes'] ) : 0;
						$week_hours   = isset( $post_data['weekcronhours'] ) ? absint( $post_data['weekcronhours'] ) : 0;
						$week_wday    = isset( $post_data['weekcronwday'] ) ? absint( $post_data['weekcronwday'] ) : 0;
						BackWPup_Option::update( $jobid, 'cron', $week_minutes . ' ' . $week_hours . ' * * ' . $week_wday );
					}
					if ( 'day' === $cronbtype ) {
						$day_minutes = isset( $post_data['daycronminutes'] ) ? absint( $post_data['daycronminutes'] ) : 0;
						$day_hours   = isset( $post_data['daycronhours'] ) ? absint( $post_data['daycronhours'] ) : 0;
						BackWPup_Option::update( $jobid, 'cron', $day_minutes . ' ' . $day_hours . ' * * *' );
					}
					if ( 'hour' === $cronbtype ) {
						$hour_minutes = isset( $post_data['hourcronminutes'] ) ? absint( $post_data['hourcronminutes'] ) : 0;
						BackWPup_Option::update( $jobid, 'cron', $hour_minutes . ' * * * *' );
					}
				}
				// Reschedule.
				$activetype = BackWPup_Option::get( $jobid, 'activetype' );
				wp_clear_scheduled_hook( 'backwpup_cron', [ 'arg' => $jobid ] );
				if ( 'wpcron' === $activetype ) {
					$cron_next = BackWPup_Cron::cron_next( BackWPup_Option::get( $jobid, 'cron' ) );
					wp_schedule_single_event( $cron_next, 'backwpup_cron', [ 'arg' => $jobid ] );
				}
				break;

			default:
				if ( strstr( (string) $tab, 'dest-' ) ) {
					$dest_class = BackWPup::get_destination( str_replace( 'dest-', '', (string) $tab ) );
					$dest_class->edit_form_post_save( $jobid );
				}
				if ( strstr( (string) $tab, 'jobtype-' ) ) {
					$id = strtoupper( str_replace( 'jobtype-', '', (string) $tab ) );
					$job_types[ $id ]->edit_form_post_save( $jobid, $post_data );
				}
		}

		// Saved message.
		$messages = BackWPup_Admin::get_messages();
		if ( empty( $messages['error'] ) ) {
			$url = BackWPup_Job::get_jobrun_url( 'runnowlink', $jobid );
			BackWPup_Admin::message(
				sprintf(
				/* translators: %s: job name. */
				__( 'Changes for job <i>%s</i> saved.', 'backwpup' ),
				BackWPup_Option::get( $jobid, 'name' )
			) . ' <a href="' . network_admin_url( 'admin.php' ) . '?page=backwpupjobs">' . __( 'Jobs overview', 'backwpup' ) . '</a> | <a href="' . $url['url'] . '">' . __( 'Run now', 'backwpup' ) . '</a>'
				);
		}
	}

	/**
	 * Output css.
	 */
	public static function admin_print_styles() {
		?>
		<style type="text/css" media="screen">
			#cron-min, #cron-hour, #cron-day, #cron-month, #cron-weekday {
				overflow: auto;
				white-space: nowrap;
				height: 7em;
			}
			#cron-min-box, #cron-hour-box, #cron-day-box, #cron-month-box, #cron-weekday-box {
				border: 1px solid gray;
				margin: 10px 0 10px 10px;
				padding: 2px 2px;
				width: 100px;
				float: left;
			}
			#wpcronbasic {
				border-collapse: collapse;
			}
			#wpcronbasic th, #wpcronbasic td {
				width:80px;
				border-bottom: 1px solid gray;
			}
		</style>
		<?php
		// Add CSS for all other tabs.
		$tab = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$tab = $tab ? sanitize_text_field( $tab ) : 'job';
		if ( 'dest-' === substr( $tab, 0, 5 ) ) {
			$dest_object = BackWPup::get_destination( str_replace( 'dest-', '', $tab ) );
			$dest_object->admin_print_styles();
		} elseif ( 'jobtype-' === substr( $tab, 0, 8 ) ) {
			$job_type = BackWPup::get_job_types();
			$id       = strtoupper( str_replace( 'jobtype-', '', $tab ) );
			$job_type[ $id ]->admin_print_styles();
		}
	}

	/**
	 * Output js.
	 */
	public static function admin_print_scripts() {
		wp_enqueue_script( 'backwpupgeneral' );

		$nonce = filter_input( INPUT_GET, '_wpnonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( ! empty( $nonce ) ) {
			check_admin_referer( 'edit-job' );
		}

		$tab = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$tab = $tab ? sanitize_text_field( $tab ) : 'job';

		// Add JS for the first tabs.
		if ( 'job' === $tab ) {
			if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
				wp_enqueue_script( 'backwpuptabjob', BackWPup::get_plugin_data( 'URL' ) . '/assets/js/page_edit_tab_job.js', [ 'jquery' ], time(), true );
			} else {
				wp_enqueue_script( 'backwpuptabjob', BackWPup::get_plugin_data( 'URL' ) . '/assets/js/page_edit_tab_job.min.js', [ 'jquery' ], BackWPup::get_plugin_data( 'Version' ), true );
			}
		} elseif ( 'cron' === $tab ) {
			if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
				wp_enqueue_script( 'backwpuptabcron', BackWPup::get_plugin_data( 'URL' ) . '/assets/js/page_edit_tab_cron.js', [ 'jquery' ], time(), true );
			} else {
				wp_enqueue_script( 'backwpuptabcron', BackWPup::get_plugin_data( 'URL' ) . '/assets/js/page_edit_tab_cron.min.js', [ 'jquery' ], BackWPup::get_plugin_data( 'Version' ), true );
			}
		} elseif ( false !== strpos( $tab, 'dest-' ) ) {
			$dest_object = BackWPup::get_destination( str_replace( 'dest-', '', $tab ) );
			$dest_object->admin_print_scripts();
		} elseif ( false !== strpos( $tab, 'jobtype-' ) ) {
			$job_type = BackWPup::get_job_types();
			$id       = strtoupper( str_replace( 'jobtype-', '', $tab ) );
			$job_type[ $id ]->admin_print_scripts();
		}
	}

	/**
	 * Generates and displays the BackWPup job editing page.
	 *
	 * This method is responsible for rendering the UI and forms for the BackWPup job configuration
	 * page within the WordPress admin dashboard. It checks for an existing job ID or creates a new one,
	 * initializes options and job types, and dynamically generates navigation tabs based on job types.
	 * The page includes forms and input fields for job details such as name, backup tasks, archive name,
	 * and various job-specific settings.
	 *
	 * @return void
	 */
	public static function page() {
		$jobid_param = absint( filter_input( INPUT_GET, 'jobid', FILTER_SANITIZE_NUMBER_INT ) );
		if ( $jobid_param > 0 ) {
			$jobid = in_array( $jobid_param, BackWPup_Option::get_job_ids(), true ) ? $jobid_param : 0;
		} else {
			// Generate job ID if it does not exist.
			$jobid = BackWPup_Option::next_job_id();
		}

		if ( ! $jobid ) {
			return;
		}

		$tab = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$tab = $tab ? sanitize_title_with_dashes( $tab ) : 'job';
		if ( 'dest-' !== substr( $tab, 0, 5 ) && 'jobtype-' !== substr( $tab, 0, 8 ) && ! in_array( $tab, [ 'job', 'cron' ], true ) ) {
			$tab = 'job';
		}
		$_GET['tab'] = $tab;

		$destinations = BackWPup::get_registered_destinations();
		$job_types    = BackWPup::get_job_types();

		// Is encryption disabled?
		$disable_encryption = true;
		if ( ( 'symmetric' === get_site_option( 'backwpup_cfg_encryption' ) && get_site_option( 'backwpup_cfg_encryptionkey' ) )
			|| ( 'asymmetric' === get_site_option( 'backwpup_cfg_encryption' ) && get_site_option( 'backwpup_cfg_publickey' ) )
		) {
			$disable_encryption = false;
		}

		$archive_format_option = BackWPup_Option::get( $jobid, 'archiveformat' );
		?>
	<div class="wrap" id="backwpup-page">
			<?php
			// translators: %1$s: BackWPup plugin name, %2$s: Backup job name.
			echo '<h1>' . sprintf( esc_html__( '%1$s &rsaquo; Job: %2$s', 'backwpup' ), esc_attr( BackWPup::get_plugin_data( 'name' ) ), '<span id="h2jobtitle">' . esc_html( BackWPup_Option::get( $jobid, 'name' ) ) . '</span>' ) . '</h1>';

			// Default tabs.
			$tabs = [
				'job'  => [
					'name'    => esc_html__( 'General', 'backwpup' ),
					'display' => true,
				],
				'cron' => [
					'name'    => __( 'Schedule', 'backwpup' ),
					'display' => true,
				],
			];
			// Add job types to tabs.
			$job_job_types = BackWPup_Option::get( $jobid, 'type' );

			foreach ( $job_types as $typeid => $typeclass ) {
				$tabid                     = 'jobtype-' . strtolower( $typeid );
				$tabs[ $tabid ]['name']    = $typeclass->info['name'];
				$tabs[ $tabid ]['display'] = true;
				if ( ! in_array( $typeid, $job_job_types, true ) ) {
					$tabs[ $tabid ]['display'] = false;
				}
			}
			// Display tabs.
			echo '<h2 class="nav-tab-wrapper">';

			foreach ( $tabs as $id => $tab_data ) {
				$addclass = '';
				if ( $id === $tab ) {
					$addclass = ' nav-tab-active';
				}
				$display = '';
				if ( ! $tab_data['display'] ) {
					$display = 'display:none;';
				}
				$tab_url = wp_nonce_url( network_admin_url( 'admin.php?page=backwpupeditjob&tab=' . rawurlencode( $id ) . '&jobid=' . absint( $jobid ) ), 'edit-job' );
				echo '<a href="' . esc_url( $tab_url ) . '" class="nav-tab' . esc_attr( $addclass ) . '" id="tab-' . esc_attr( $id ) . '" data-nexttab="' . esc_attr( $id ) . '" style=' . esc_attr( $display ) . '>' . esc_html( $tab_data['name'] ) . '</a>';
			}
			echo '</h2>';
		  // phpcs:disable
		  // Display messages.
		  BackWPup_Admin::display_messages();
		  echo '<form name="editjob" id="editjob" method="post" action="' . esc_attr( admin_url( 'admin-post.php' ) ) . '">';
		  echo '<input readonly disabled type="hidden" id="jobid" name="jobid" value="' . esc_attr( $jobid ) . '" />';
		  echo '<input readonly disabled type="hidden" name="tab" value="' . esc_attr( $tab ) . '" />';
		  echo '<input readonly disabled type="hidden" name="nexttab" value="' . esc_attr( $tab ) . '" />';
		  echo '<input readonly disabled type="hidden" name="page" value="backwpupeditjob" />';
		  echo '<input readonly disabled type="hidden" name="action" value="backwpup" />';
		  echo '<input readonly disabled type="hidden" name="anchor" value="" />';
		  wp_nonce_field( 'backwpupeditjob_page' );
		  wp_nonce_field( 'backwpup_ajax_nonce', 'backwpupajaxnonce', false );

		  switch ( $tab ) {
				case 'job':
					?>
				<div class="table" id="info-tab-job">
					<h3><?php esc_html_e('Job Name', 'backwpup'); ?></h3>
					<table class="form-table">
						<tr>
							<th scope="row"><label for="name"><?php esc_html_e('Please name this job.', 'backwpup'); ?></label></th>
							<td>
								<input readonly disabled name="name" type="text" id="name" placeholder="<?php esc_attr_e( 'Job Name', 'backwpup' ); ?>" data-empty="<?php esc_attr_e( 'New Job', 'backwpup' ); ?>" value="<?php echo esc_attr( BackWPup_Option::get( $jobid, 'name' ) ); ?>" class="regular-text" />
							</td>
						</tr>
					</table>

					<h3><?php esc_html_e('Job Tasks', 'backwpup'); ?></h3>
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e('This job is a&#160;&hellip;', 'backwpup'); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text"><span><?php esc_html_e('Job tasks', 'backwpup'); ?></span>
									</legend><?php
									foreach ($job_types as $id => $typeclass) {
										$addclass = '';
										if ($typeclass->creates_file()) {
											$addclass .= ' filetype';
										}
										echo '<p><label for="jobtype-select-' . strtolower( $id ) . '"><input readonly disabled class="jobtype-select checkbox' . $addclass . '" id="jobtype-select-' . strtolower( $id ) . '" type="checkbox" ' . checked( true, in_array( $id, BackWPup_Option::get( $jobid, 'type' ), true ), false ) . ' name="type[]" value="' . esc_attr( $id ) . '" /> ' . esc_attr( $typeclass->info['description'] ) . '</label>';
										if ( ! empty( $typeclass->info['help'] ) ) {
											echo '<br><span class="description">' . esc_attr( $typeclass->info['help'] ) . '</span>';
										}
										echo '</p>';
										}
									?></fieldset>
							</td>
						</tr>
					</table>

					<h3 class="title hasdests"><?php esc_html_e('Backup File Creation', 'backwpup'); ?></h3>
					<p class="hasdests"></p>
					<table class="form-table hasdests">
							<?php if (class_exists(\BackWPup_Pro::class, false)) { ?>
						<tr>
							<th scope="row"><?php esc_html_e('Backup type', 'backwpup'); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text"><span><?php esc_html_e('Backup type', 'backwpup'); ?></span></legend>
									<p>
										<label for="idbackuptype-sync">
											<input readonly disabled class="radio" type="radio"<?php checked( 'sync', BackWPup_Option::get( $jobid, 'backuptype' ), true ); ?> name="backuptype" id="idbackuptype-sync" value="sync" /> <?php esc_html_e( 'Synchronize file by file to destination', 'backwpup' ); ?>
										</label>
									</p>
									<p>
										<label for="idbackuptype-archive">
											<input readonly disabled class="radio" type="radio"<?php checked( 'archive', BackWPup_Option::get( $jobid, 'backuptype' ), true ); ?> name="backuptype" id="idbackuptype-archive" value="archive" /> <?php esc_html_e( 'Create a backup archive', 'backwpup' ); ?>
										</label>
									</p>
								</fieldset>
							</td>
						</tr>
						<?php } ?>
						<tr class="nosync">
							<th scope="row"><label for="archivename"><?php esc_html_e('Archive name', 'backwpup'); ?></label></th>
							<td>
								<input readonly disabled name="archivename" type="text" id="archivename" placeholder="%Y-%m-%d_%H-%i-%s_%hash%" value="<?php echo esc_attr( BackWPup_Option::get( $jobid, 'archivenamenohash' ) ); ?>" class="regular-text code" />
								<p><em><?php esc_html_e( 'Note:', 'backwpup' ); ?></em> <?php esc_html_e( 'In order for backup file tracking to work, %hash% must be included anywhere in the archive name.', 'backwpup' ); ?></p>
									<?php
									$archivename = BackWPup_Option::substitute_date_vars(
										BackWPup_Option::get($jobid, 'archivenamenohash')
									);
									echo '<p>' . esc_html__('Preview: ', 'backwpup') . '<code><span id="archivefilename">' . esc_attr($archivename) . '</span><span id="archiveformat">' . esc_attr($archive_format_option) . '</span></code></p>';
									echo '<p class="description">';
									echo '<strong>' . esc_attr__('Replacement patterns:', 'backwpup') . '</strong><br />';
									echo esc_attr__('%d = Two digit day of the month, with leading zeros', 'backwpup') . '<br />';
									echo esc_attr__('%j = Day of the month, without leading zeros', 'backwpup') . '<br />';
									echo esc_attr__('%m = Two-digit representation of the month, with leading zeros', 'backwpup') . '<br />';
									echo esc_attr__('%n = Representation of the month (without leading zeros)', 'backwpup') . '<br />';
									echo esc_attr__('%Y = Four digit representation of the year', 'backwpup') . '<br />';
									echo esc_attr__('%y = Two digit representation of the year', 'backwpup') . '<br />';
									echo esc_attr__('%a = Lowercase ante meridiem (am) and post meridiem (pm)', 'backwpup') . '<br />';
									echo esc_attr__('%A = Uppercase ante meridiem (AM) and post meridiem (PM)', 'backwpup') . '<br />';
									echo esc_attr__('%B = Swatch Internet Time', 'backwpup') . '<br />';
									echo esc_attr__('%g = Hour in 12-hour format, without leading zeros', 'backwpup') . '<br />';
									echo esc_attr__('%G = Hour in 24-hour format, without leading zeros', 'backwpup') . '<br />';
									echo esc_attr__('%h = Two-digit hour in 12-hour format, with leading zeros', 'backwpup') . '<br />';
									echo esc_attr__('%H = Two-digit hour in 24-hour format, with leading zeros', 'backwpup') . '<br />';
									echo esc_attr__('%i = Two digit representation of the minute', 'backwpup') . '<br />';
									echo esc_attr__('%s = Two digit representation of the second', 'backwpup') . '<br />';
									echo '</p>';
									?>
							</td>
						</tr>
						<tr class="nosync">
							<th scope="row"><?php esc_html_e('Archive Format', 'backwpup'); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text"><span><?php esc_html_e('Archive Format', 'backwpup'); ?></span></legend>
									<?php
									if ( class_exists( \ZipArchive::class ) ) {
										echo '<p><label for="idarchiveformat-zip"><input readonly disabled class="radio" type="radio"' . checked( '.zip', $archive_format_option, false ) . ' name="archiveformat" id="idarchiveformat-zip" value=".zip" /> ' . esc_html__( 'Zip', 'backwpup' ) . '</label></p>';
										} else {
										echo '<p><label for="idarchiveformat-zip"><input readonly disabled class="radio" type="radio"' . checked( '.zip', $archive_format_option, false ) . ' name="archiveformat" id="idarchiveformat-zip" value=".zip" disabled="disabled" /> ' . esc_html__( 'Zip', 'backwpup' ) . '</label>';
										echo '<br /><span class="description">' . esc_html( __( 'ZipArchive PHP class is missing, so BackWPUp will use PclZip instead.', 'backwpup' ) ) . '</span></p>';
										}
									echo '<p><label for="idarchiveformat-tar"><input readonly disabled class="radio" type="radio"' . checked( '.tar', $archive_format_option, false ) . ' name="archiveformat" id="idarchiveformat-tar" value=".tar" /> ' . esc_html__( 'Tar', 'backwpup' ) . '</label></p>';
									if ( function_exists( 'gzopen' ) ) {
									  echo '<p><label for="idarchiveformat-targz"><input readonly disabled class="radio" type="radio"' . checked( '.tar.gz', $archive_format_option, false ) . ' name="archiveformat" id="idarchiveformat-targz" value=".tar.gz" /> ' . esc_html__( 'Tar GZip', 'backwpup' ) . '</label></p>';
									  } else {
									echo '<p><label for="idarchiveformat-targz"><input readonly disabled class="radio" type="radio"' . checked( '.tar.gz', $archive_format_option, false ) . ' name="archiveformat" id="idarchiveformat-targz" value=".tar.gz" disabled="disabled" /> ' . esc_html__( 'Tar GZip', 'backwpup' ) . '</label>';
									echo '<br /><span class="description">' . esc_html( sprintf( __( 'Disabled due to missing %s PHP function.', 'backwpup' ), 'gzopen()' ) ) . '</span></p>';
									  }
									?>
								</fieldset>
							</td>
						</tr>
							<?php if (class_exists(\BackWPup_Pro::class, false)) { ?>
							<tr class="nosync">
								<th scope="row">
									<?php esc_html_e('Encrypt Archive', 'backwpup'); ?>
								</th>
								<td>
									<fieldset>
										<legend class="screen-reader-text">
										<span><?php esc_html_e('Encrypt Archive', 'backwpup'); ?></span>
										</legend>
										<?php
										?>
										<label for="archiveencryption">
											<input readonly disabled type="checkbox" name="archiveencryption"
												id="archiveencryption" value="1"
												<?php
												if ( $disable_encryption ) {
													?>
											disabled="disabled"
													<?php
												} else {
													checked( BackWPup_Option::get( $jobid, 'archiveencryption' ) );
												}
												?>
										/>
											<?php esc_html_e( 'Encrypt Archive', 'backwpup' ); // @phpcs:ignore?>
										</label>
										<?php if ($disable_encryption) { ?>
											<p class="description">
												<?php esc_html_e('You must generate your encryption key in BackWPup Settings before you can enable this option.', 'backwpup'); ?>
											</p>
										<?php } ?>
									</fieldset>
								</td>
							</tr>
						<?php } ?>
					</table>

					<h3 class="title hasdests"><?php esc_html_e('Job Destination', 'backwpup'); ?></h3>
					<p class="hasdests"></p>
					<table class="form-table hasdests">
						<tr>
							<th scope="row"><?php esc_html_e('Where should your backup file be stored?', 'backwpup'); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text"><span><?php esc_html_e('Where should your backup file be stored?', 'backwpup'); ?></span>
									</legend><?php
									foreach ($destinations as $id => $dest) {
										$syncclass = '';
										if (!$dest['can_sync']) {
											$syncclass = 'nosync';
										}
										echo '<p class="' . esc_attr( $syncclass ) . '"><label for="dest-select-' . strtolower( $id ) . '"><input readonly disabled class="checkbox" id="dest-select-' . strtolower( esc_attr( $id ) ) . '" type="checkbox" ' . checked( true, in_array( $id, BackWPup_Option::get( $jobid, 'destinations' ), true ), false ) . ' name="destinations[]" value="' . esc_attr( $id ) . '" ' . disabled( ! empty( $dest['error'] ), true, false ) . ' /> ' . esc_attr( $dest['info']['description'] );
										if ( ! empty( $dest['error'] ) ) {
											echo '<br><span class="description">' . esc_attr( $dest['error'] ) . '</span>';
										}
										echo '</label></p>';
										}
									?></fieldset>
							</td>
						</tr>
					</table>

					<h3 class="title"><?php esc_html_e('Log Files', 'backwpup'); ?></h3>
					<p></p>
					<table class="form-table">
						<tr>
							<th scope="row"><label for="mailaddresslog"><?php esc_html_e('Send log to email address', 'backwpup'); ?></label></th>
							<td>
								<input readonly disabled name="mailaddresslog" type="text" id="mailaddresslog" value="<?php echo esc_html( BackWPup_Option::get( $jobid, 'mailaddresslog' ) ); ?>" class="regular-text" />
								<p class="description"><?php esc_attr_e( 'Leave empty to not have log sent. Or separate with , for more than one receiver.', 'backwpup' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="mailaddresssenderlog"><?php esc_html_e('Email FROM field', 'backwpup'); ?></label></th>
							<td>
								<input readonly disabled name="mailaddresssenderlog" type="text" id="mailaddresssenderlog" value="<?php echo esc_html( BackWPup_Option::get( $jobid, 'mailaddresssenderlog' ) ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Your Name &lt;mail@domain.tld&gt;', 'backwpup' ); ?>"/>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e('Errors only', 'backwpup'); ?></th>
							<td>
								<label for="idmailerroronly">
								<input readonly disabled class="checkbox" value="1" id="idmailerroronly"
										type="checkbox" <?php checked( BackWPup_Option::get( $jobid, 'mailerroronly' ), true ); ?>
										name="mailerroronly" /> <?php esc_html_e( 'Send email with log only when errors occur during job execution.', 'backwpup' ); ?>
								</label>
							</td>
						</tr>
					</table>
				</div>
					<?php
					break;

				case 'cron':
					?>
				<div class="table" id="info-tab-cron">
					<h3 class="title"><?php esc_html_e('Job Schedule', 'backwpup'); ?></h3>
					<p></p>
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e('Start job', 'backwpup'); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text"><span><?php esc_html_e( 'Start job', 'backwpup' ); ?></span></legend>
									<label for="idactivetype"><input readonly disabled class="radio"
											type="radio"<?php checked( '', BackWPup_Option::get( $jobid, 'activetype' ), true ); ?>
											name="activetype" id="idactivetype"
											value="" /> <?php esc_html_e( 'manually only', 'backwpup' ); ?></label><br/>
									<label for="idactivetype-wpcron"><input readonly disabled class="radio"
											type="radio"<?php checked( 'wpcron', BackWPup_Option::get( $jobid, 'activetype' ), true ); ?>
											name="activetype" id="idactivetype-wpcron"
											value="wpcron" /> <?php esc_html_e( 'with WordPress cron', 'backwpup' ); ?></label><br/>
									<?php
									$url = BackWPup_Job::get_jobrun_url('runext', BackWPup_Option::get($jobid, 'jobid'));
									?>
									<label for="idactivetype-link">
										<input readonly disabled class="radio" type="radio"<?php checked( 'link', BackWPup_Option::get( $jobid, 'activetype' ), true ); ?> name="activetype" id="idactivetype-link" value="link" />
										&nbsp;<?php esc_html_e( 'with a link', 'backwpup' ); ?> <code><a href="<?php echo $url['url']; ?>" target="_blank"><?php echo esc_html( $url['url'] ); ?></a></code><br>
										<span class="description"><?php esc_attr_e( 'Copy the link for an external start. This option has to be activated to make the link work.', 'backwpup' ); ?></span>
									</label>

								</fieldset>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e('Start job with CLI', 'backwpup'); ?></th>
							<td>
									<?php
									_e('Use <a href="http://wp-cli.org/">WP-CLI</a> to run jobs from commandline.', 'backwpup');
									?>
							</td>
						</tr>
					</table>
					<h3 class="title wpcron"><?php esc_html_e('Schedule execution time', 'backwpup'); ?></h3>
						<?php BackWPup_Page_Editjob::ajax_cron_text(['cronstamp' => BackWPup_Option::get($jobid, 'cron'), 'crontype' => BackWPup_Option::get($jobid, 'cronselect')]); ?>
					<table class="form-table wpcron">
						<tr>
							<th scope="row"><?php esc_html_e('Scheduler type', 'backwpup'); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text"><span><?php esc_html_e( 'Scheduler type', 'backwpup' ); ?></span></legend>
									<label for="idcronselect-basic"><input readonly disabled class="radio"
											type="radio"<?php checked( 'basic', BackWPup_Option::get( $jobid, 'cronselect' ), true ); ?>
											name="cronselect" id="idcronselect-basic"
											value="basic" /> <?php esc_html_e( 'basic', 'backwpup' ); ?></label><br/>
									<label for="idcronselect-advanced"><input readonly disabled class="radio"
											type="radio"<?php checked( 'advanced', BackWPup_Option::get( $jobid, 'cronselect' ), true ); ?>
											name="cronselect" id="idcronselect-advanced"
											value="advanced" /> <?php esc_html_e( 'advanced', 'backwpup' ); ?></label><br/>
								</fieldset>
							</td>
						</tr>
							<?php

							$cronstr = [];
							[$cronstr['minutes'], $cronstr['hours'], $cronstr['mday'], $cronstr['mon'], $cronstr['wday']] = explode(' ', (string) BackWPup_Option::get($jobid, 'cron'), 5);
							if (strstr($cronstr['minutes'], '*/')) {
								$minutes = explode('/', $cronstr['minutes']);
							} else {
								$minutes = explode(',', $cronstr['minutes']);
							}
							if (strstr($cronstr['hours'], '*/')) {
								$hours = explode('/', $cronstr['hours']);
							} else {
								$hours = explode(',', $cronstr['hours']);
							}
							if (strstr($cronstr['mday'], '*/')) {
								$mday = explode('/', $cronstr['mday']);
							} else {
								$mday = explode(',', $cronstr['mday']);
							}
							if (strstr($cronstr['mon'], '*/')) {
								$mon = explode('/', $cronstr['mon']);
							} else {
								$mon = explode(',', $cronstr['mon']);
							}
							if (strstr($cronstr['wday'], '*/')) {
								$wday = explode('/', $cronstr['wday']);
							} else {
								$wday = explode(',', $cronstr['wday']);
							}
							?>
						<tr class="wpcronbasic"<?php if ( 'basic' !== BackWPup_Option::get( $jobid, 'cronselect' ) ) {
							echo ' style="display:none;"';
							}?>>
							<th scope="row"><?php esc_html_e('Scheduler', 'backwpup'); ?></th>
							<td>
								<table id="wpcronbasic">
									<tr>
										<th>
											<?php esc_html_e('Type', 'backwpup'); ?>
										</th>
										<th>
										</th>
										<th>
											<?php esc_html_e('Hour', 'backwpup'); ?>
										</th>
										<th>
											<?php esc_html_e('Minute', 'backwpup'); ?>
										</th>
									</tr>
									<tr>
										<td><label for="idcronbtype-mon"><?php echo '<input readonly disabled class="radio" type="radio"' . checked( true, is_numeric( $mday[0] ), false ) . ' name="cronbtype" id="idcronbtype-mon" value="mon" /> ' . esc_html__( 'monthly', 'backwpup' ); ?></label></td>
										<td><select name="moncronmday">
										<?php
										for ( $i = 1; $i <= 31; ++$i ) {
											echo '<option ' . selected( in_array( (string) $i, $mday, true ), true, false ) . '  value="' . esc_attr( $i ) . '" />' . esc_html__( 'on', 'backwpup' ) . ' ' . esc_html( $i ) . '</option>';
											}
										?>
						</select></td>
										<td><select name="moncronhours">
										<?php
										for ( $i = 0; $i < 24; ++$i ) {
											echo '<option ' . selected( in_array( (string) $i, $hours, true ), true, false ) . '  value="' . esc_attr( $i ) . '" />' . esc_html( $i ) . '</option>';
											}
										?>
						</select></td>
										<td><select name="moncronminutes">
										<?php
										for ( $i = 0; $i < 60; $i = $i + 5 ) {
											echo '<option ' . selected( in_array( (string) $i, $minutes, true ), true, false ) . '  value="' . esc_attr( $i ) . '" />' . esc_html( $i ) . '</option>';
											}
										?>
						</select></td>
									</tr>
									<tr>
										<td><label for="idcronbtype-week"><?php echo '<input readonly disabled class="radio" type="radio"' . checked( true, is_numeric( $wday[0] ), false ) . ' name="cronbtype" id="idcronbtype-week" value="week" /> ' . esc_html__( 'weekly', 'backwpup' ); ?></label></td>
										<td><select name="weekcronwday">
											<?php echo '<option ' . selected(in_array('0', $wday, true), true, false) . '  value="0" />' . esc_html__('Sunday', 'backwpup') . '</option>';
											echo '<option ' . selected(in_array('1', $wday, true), true, false) . '  value="1" />' . esc_html__('Monday', 'backwpup') . '</option>';
											echo '<option ' . selected(in_array('2', $wday, true), true, false) . '  value="2" />' . esc_html__('Tuesday', 'backwpup') . '</option>';
											echo '<option ' . selected(in_array('3', $wday, true), true, false) . '  value="3" />' . esc_html__('Wednesday', 'backwpup') . '</option>';
											echo '<option ' . selected(in_array('4', $wday, true), true, false) . '  value="4" />' . esc_html__('Thursday', 'backwpup') . '</option>';
											echo '<option ' . selected(in_array('5', $wday, true), true, false) . '  value="5" />' . esc_html__('Friday', 'backwpup') . '</option>';
											echo '<option ' . selected(in_array('6', $wday, true), true, false) . '  value="6" />' . esc_html__('Saturday', 'backwpup') . '</option>'; ?>
										</select></td>
										<td><select name="weekcronhours"><?php for ($i = 0; $i < 24; ++$i) {
												echo '<option ' . selected(in_array((string) $i, $hours, true), true, false) . '  value="' . esc_attr($i) . '" />' . esc_html($i) . '</option>';
											} ?></select></td>
										<td><select name="weekcronminutes"><?php for ($i = 0; $i < 60; $i = $i + 5) {
												echo '<option ' . selected(in_array((string) $i, $minutes, true), true, false) . '  value="' . esc_attr($i) . '" />' . esc_html($i) . '</option>';
											} ?></select></td>
									</tr>
									<tr>
										<td><label for="idcronbtype-day"><?php echo '<input readonly disabled class="radio" type="radio"' . checked( '**', $mday[0] . $wday[0], false ) . ' name="cronbtype" id="idcronbtype-day" value="day" /> ' . esc_html__( 'daily', 'backwpup' ); ?></label></td>
										<td></td>
										<td><select name="daycronhours"><?php for ($i = 0; $i < 24; ++$i) {
												echo '<option ' . selected(in_array((string) $i, $hours, true), true, false) . '  value="' . esc_attr($i) . '" />' . esc_html($i) . '</option>';
											} ?></select></td>
										<td><select name="daycronminutes"><?php for ($i = 0; $i < 60; $i = $i + 5) {
												echo '<option ' . selected(in_array((string) $i, $minutes, true), true, false) . '  value="' . esc_attr($i) . '" />' . esc_html($i) . '</option>';
											} ?></select></td>
									</tr>
									<tr>
										<td><label for="idcronbtype-hour"><?php echo '<input readonly disabled class="radio" type="radio"' . checked( '*', $hours[0], false ) . ' name="cronbtype" id="idcronbtype-hour" value="hour" /> ' . esc_html__( 'hourly', 'backwpup' ); ?></label></td>
										<td></td>
										<td></td>
										<td><select name="hourcronminutes"><?php for ($i = 0; $i < 60; $i = $i + 5) {
												echo '<option ' . selected(in_array((string) $i, $minutes, true), true, false) . '  value="' . esc_attr($i) . '" />' . esc_html($i) . '</option>';
											} ?></select></td>
									</tr>
								</table>
							</td>
						</tr>
						<tr class="wpcronadvanced"<?php if ( 'advanced' !== BackWPup_Option::get( $jobid, 'cronselect' ) ) {
							echo ' style="display:none;"';
							}?>>
							<th scope="row"><?php esc_html_e('Scheduler', 'backwpup'); ?></th>
							<td>
								<div id="cron-min-box">
									<b><?php esc_html_e('Minutes:', 'backwpup'); ?></b><br/>
										<?php
										echo '<label for="idcronminutes"><input readonly disabled class="checkbox" type="checkbox"' . checked( in_array( '*', $minutes, true ), true, false ) . ' name="cronminutes[]" id="idcronminutes" value="*" /> ' . __( 'Any (*)', 'backwpup' ) . '</label><br />';
										?>
									<div id="cron-min">
										<?php
										for ( $i = 0; $i < 60; $i = $i + 5 ) {
											echo '<label for="idcronminutes-' . $i . '"><input readonly disabled class="checkbox" type="checkbox"' . checked( in_array( (string) $i, $minutes, true ), true, false ) . ' name="cronminutes[]" id="idcronminutes-' . esc_attr( $i ) . '" value="' . esc_attr( $i ) . '" /> ' . esc_attr( $i ) . '</label><br />'; // @phpcs:ignore
										}
										?>
									</div>
								</div>
								<div id="cron-hour-box">
									<b><?php esc_html_e('Hours:', 'backwpup'); ?></b><br/>
									  <?php

									  echo '<label for="idcronhours"><input readonly disabled class="checkbox" type="checkbox"' . checked( in_array( '*', $hours, true ), true, false ) . ' name="cronhours[]" id="idcronhours" value="*" /> ' . __( 'Any (*)', 'backwpup' ) . '</label><br />';
									  ?>
									<div id="cron-hour">
									  <?php
									  for ( $i = 0; $i < 24; ++$i ) {
											echo '<label for="idcronhours-' . $i . '"><input readonly disabled class="checkbox" type="checkbox"' . checked( in_array( (string) $i, $hours, true ), true, false ) . ' name="cronhours[]" id="idcronhours-' . esc_attr( $i ) . '" value="' . esc_attr( $i ) . '" /> ' . esc_html( $i ) . '</label><br />'; // @phpcs:ignore
										}
									  ?>
									</div>
								</div>
								<div id="cron-day-box">
									<b><?php esc_html_e( 'Day of Month:', 'backwpup' ); ?></b><br/>
									<label for="idcronmday"><input readonly disabled class="checkbox" type="checkbox"<?php checked( in_array( '*', $mday, true ), true, true ); ?>
											name="cronmday[]" id="idcronmday" value="*"/> <?php esc_html_e( 'Any (*)', 'backwpup' ); ?></label>
									<br/>

									<div id="cron-day">
										<?php
										for ( $i = 1; $i <= 31; ++$i ) {
											echo '<label for="idcronmday-' . $i . '"><input readonly disabled class="checkbox" type="checkbox"' . checked( in_array( (string) $i, $mday, true ), true, false ) . ' name="cronmday[]" id="idcronmday-' . esc_attr( $i ) . '" value="' . esc_attr( $i ) . '" /> ' . esc_html( $i ) . '</label><br />';
											}
										?>
									</div>
								</div>
								<div id="cron-month-box">
									<b><?php esc_html_e('Month:', 'backwpup'); ?></b><br/>
										<?php
										echo '<label for="idcronmon"><input readonly disabled class="checkbox" type="checkbox"' . checked( in_array( '*', $mon, true ), true, false ) . ' name="cronmon[]" id="idcronmon" value="*" /> ' . esc_html__( 'Any (*)', 'backwpup' ) . '</label><br />';
										?>
									<div id="cron-month">
										<?php
										echo '<label for="idcronmon-1"><input readonly disabled class="checkbox" type="checkbox"' . checked( in_array( '1', $mon, true ), true, false ) . ' name="cronmon[]" id="idcronmon-1" value="1" /> ' . esc_html__( 'January', 'backwpup' ) . '</label><br />';
										echo '<label for="idcronmon-2"><input readonly disabled class="checkbox" type="checkbox"' . checked( in_array( '2', $mon, true ), true, false ) . ' name="cronmon[]" id="idcronmon-2" value="2" /> ' . esc_html__( 'February', 'backwpup' ) . '</label><br />';
										echo '<label for="idcronmon-3"><input readonly disabled class="checkbox" type="checkbox"' . checked( in_array( '3', $mon, true ), true, false ) . ' name="cronmon[]" id="idcronmon-3" value="3" /> ' . esc_html__( 'March', 'backwpup' ) . '</label><br />';
										echo '<label for="idcronmon-4"><input readonly disabled class="checkbox" type="checkbox"' . checked( in_array( '4', $mon, true ), true, false ) . ' name="cronmon[]" id="idcronmon-4" value="4" /> ' . esc_html__( 'April', 'backwpup' ) . '</label><br />';
										echo '<label for="idcronmon-5"><input readonly disabled class="checkbox" type="checkbox"' . checked( in_array( '5', $mon, true ), true, false ) . ' name="cronmon[]" id="idcronmon-5" value="5" /> ' . esc_html__( 'May', 'backwpup' ) . '</label><br />';
										echo '<label for="idcronmon-6"><input readonly disabled class="checkbox" type="checkbox"' . checked( in_array( '6', $mon, true ), true, false ) . ' name="cronmon[]" id="idcronmon-6" value="6" /> ' . esc_html__( 'June', 'backwpup' ) . '</label><br />';
										echo '<label for="idcronmon-7"><input readonly disabled class="checkbox" type="checkbox"' . checked( in_array( '7', $mon, true ), true, false ) . ' name="cronmon[]" id="idcronmon-7" value="7" /> ' . esc_html__( 'July', 'backwpup' ) . '</label><br />';
										echo '<label for="idcronmon-8"><input readonly disabled class="checkbox" type="checkbox"' . checked( in_array( '8', $mon, true ), true, false ) . ' name="cronmon[]" id="idcronmon-8" value="8" /> ' . esc_html__( 'August', 'backwpup' ) . '</label><br />';
										echo '<label for="idcronmon-9"><input readonly disabled class="checkbox" type="checkbox"' . checked( in_array( '9', $mon, true ), true, false ) . ' name="cronmon[]" id="idcronmon-9" value="9" /> ' . esc_html__( 'September', 'backwpup' ) . '</label><br />';
										echo '<label for="idcronmon-10"><input readonly disabled class="checkbox" type="checkbox"' . checked( in_array( '10', $mon, true ), true, false ) . ' name="cronmon[]" id="idcronmon-10" value="10" /> ' . esc_html__( 'October', 'backwpup' ) . '</label><br />';
										echo '<label for="idcronmon-11"><input readonly disabled class="checkbox" type="checkbox"' . checked( in_array( '11', $mon, true ), true, false ) . ' name="cronmon[]" id="idcronmon-11" value="11" /> ' . esc_html__( 'November', 'backwpup' ) . '</label><br />';
										echo '<label for="idcronmon-12"><input readonly disabled class="checkbox" type="checkbox"' . checked( in_array( '12', $mon, true ), true, false ) . ' name="cronmon[]" id="idcronmon-12" value="12" /> ' . esc_html__( 'December', 'backwpup' ) . '</label><br />';
										?>
									</div>
								</div>
								<div id="cron-weekday-box">
									<b><?php esc_html_e('Day of Week:', 'backwpup'); ?></b><br/>
										<?php
										echo '<label for="idcronwday"><input readonly disabled class="checkbox" type="checkbox"' . checked( in_array( '*', $wday, true ), true, false ) . ' name="cronwday[]" id="idcronwday" value="*" /> ' . __( 'Any (*)', 'backwpup' ) . '</label><br />';
										?>
									<div id="cron-weekday">
										<?php
										echo '<label for="idcronwday-0"><input readonly disabled class="checkbox" type="checkbox"' . checked( in_array( '0', $wday, true ), true, false ) . ' name="cronwday[]" id="idcronwday-0" value="0" /> ' . esc_html__( 'Sunday', 'backwpup' ) . '</label><br />';
										echo '<label for="idcronwday-1"><input readonly disabled class="checkbox" type="checkbox"' . checked( in_array( '1', $wday, true ), true, false ) . ' name="cronwday[]" id="idcronwday-1" value="1" /> ' . esc_html__( 'Monday', 'backwpup' ) . '</label><br />';
										echo '<label for="idcronwday-2"><input readonly disabled class="checkbox" type="checkbox"' . checked( in_array( '2', $wday, true ), true, false ) . ' name="cronwday[]" id="idcronwday-2" value="2" /> ' . esc_html__( 'Tuesday', 'backwpup' ) . '</label><br />';
										echo '<label for="idcronwday-3"><input readonly disabled class="checkbox" type="checkbox"' . checked( in_array( '3', $wday, true ), true, false ) . ' name="cronwday[]" id="idcronwday-3" value="3" /> ' . esc_html__( 'Wednesday', 'backwpup' ) . '</label><br />';
										echo '<label for="idcronwday-4"><input readonly disabled class="checkbox" type="checkbox"' . checked( in_array( '4', $wday, true ), true, false ) . ' name="cronwday[]" id="idcronwday-4" value="4" /> ' . esc_html__( 'Thursday', 'backwpup' ) . '</label><br />';
										echo '<label for="idcronwday-5"><input readonly disabled class="checkbox" type="checkbox"' . checked( in_array( '5', $wday, true ), true, false ) . ' name="cronwday[]" id="idcronwday-5" value="5" /> ' . esc_html__( 'Friday', 'backwpup' ) . '</label><br />';
										echo '<label for="idcronwday-6"><input readonly disabled class="checkbox" type="checkbox"' . checked( in_array( '6', $wday, true ), true, false ) . ' name="cronwday[]" id="idcronwday-6" value="6" /> ' . esc_html__( 'Saturday', 'backwpup' ) . '</label><br />';
										?>
									</div>
								</div>
								<br class="clear"/>
							</td>
						</tr>
					</table>
				</div>
					<?php
					break;

				default:
					echo '<div class="table" id="info-tab-' . esc_attr( $tab ) . '">';
					if ( false !== strpos( $tab, 'jobtype-' ) ) {
						$id = strtoupper( str_replace( 'jobtype-', '', $tab ) );
						$job_types[ $id ]->edit_tab( $jobid );
					}
					echo '</div>';
		  }
		  echo '<p class="submit">';
		  submit_button(__('Save changes', 'backwpup'), 'primary', 'save', false, ['tabindex' => '2', 'accesskey' => 'p']);
		  echo '</p></form>'; ?>
	</div>

	<script type="text/javascript">
		jQuery(document).ready(function ($) {
			// Auto post if things changed.
			var changed = false;
			$( '#editjob' ).on('change',  function () {
				changed = true;
			});
			$( '.nav-tab' ).on('click',  function () {
				if ( changed ) {
					$( 'input[name="nexttab"]' ).val( $(this).data( "nexttab" ) );
					$( '#editjob' ).submit();
					return false;
				}
			});
		});
	</script>
		  <?php
		  // Add inline JS.
		  if ( false !== strpos( $tab, 'dest-' ) ) {
				$dest_object = BackWPup::get_destination( str_replace( 'dest-', '', $tab ) );
				$dest_object->edit_inline_js();
		  }
		  if ( false !== strpos( $tab, 'jobtype-' ) ) {
				$id = strtoupper( str_replace( 'jobtype-', '', $tab ) );
				$job_types[ $id ]->edit_inline_js();
		  }
		  // phpcs:enable
	}

	/**
	 * Output cron schedule text.
	 *
	 * @param array|string $args Cron data when called from the page render.
	 *
	 * @return void
	 */
	public static function ajax_cron_text( $args = '' ) {
		if ( is_array( $args ) ) {
			$cronstamp = isset( $args['cronstamp'] ) ? (string) $args['cronstamp'] : '';
			$crontype  = isset( $args['crontype'] ) ? (string) $args['crontype'] : '';
			$ajax      = false;
		} else {
			if ( ! current_user_can( 'backwpup_jobs_edit' ) ) {
				wp_die( -1 );
			}
			check_ajax_referer( 'backwpup_ajax_nonce' );

			$post_data   = wp_unslash( $_POST );
			$cronminutes = isset( $post_data['cronminutes'] ) ? (array) $post_data['cronminutes'] : [];
			$cronminutes = array_map( 'sanitize_text_field', $cronminutes );
			$cronhours   = isset( $post_data['cronhours'] ) ? (array) $post_data['cronhours'] : [];
			$cronhours   = array_map( 'sanitize_text_field', $cronhours );
			$cronmday    = isset( $post_data['cronmday'] ) ? (array) $post_data['cronmday'] : [];
			$cronmday    = array_map( 'sanitize_text_field', $cronmday );
			$cronmon     = isset( $post_data['cronmon'] ) ? (array) $post_data['cronmon'] : [];
			$cronmon     = array_map( 'sanitize_text_field', $cronmon );
			$cronwday    = isset( $post_data['cronwday'] ) ? (array) $post_data['cronwday'] : [];
			$cronwday    = array_map( 'sanitize_text_field', $cronwday );

			$cronminutes_first = $cronminutes[0] ?? '';
			if ( '' === $cronminutes_first || '*' === $cronminutes_first ) {
				$cronminutes_step = $cronminutes[1] ?? '';
				$cronminutes      = '' !== $cronminutes_step ? [ '*/' . $cronminutes_step ] : [ '*' ];
			}
			$cronhours_first = $cronhours[0] ?? '';
			if ( '' === $cronhours_first || '*' === $cronhours_first ) {
				$cronhours_step = $cronhours[1] ?? '';
				$cronhours      = '' !== $cronhours_step ? [ '*/' . $cronhours_step ] : [ '*' ];
			}
			$cronmday_first = $cronmday[0] ?? '';
			if ( '' === $cronmday_first || '*' === $cronmday_first ) {
				$cronmday_step = $cronmday[1] ?? '';
				$cronmday      = '' !== $cronmday_step ? [ '*/' . $cronmday_step ] : [ '*' ];
			}
			$cronmon_first = $cronmon[0] ?? '';
			if ( '' === $cronmon_first || '*' === $cronmon_first ) {
				$cronmon_step = $cronmon[1] ?? '';
				$cronmon      = '' !== $cronmon_step ? [ '*/' . $cronmon_step ] : [ '*' ];
			}
			$cronwday_first = $cronwday[0] ?? '';
			if ( '' === $cronwday_first || '*' === $cronwday_first ) {
				$cronwday_step = $cronwday[1] ?? '';
				$cronwday      = '' !== $cronwday_step ? [ '*/' . $cronwday_step ] : [ '*' ];
			}

			$crontype  = isset( $post_data['crontype'] ) ? sanitize_text_field( $post_data['crontype'] ) : '';
			$cronstamp = implode( ',', $cronminutes ) . ' ' . implode( ',', $cronhours ) . ' ' . implode( ',', $cronmday ) . ' ' . implode( ',', $cronmon ) . ' ' . implode( ',', $cronwday );
			$ajax      = true;
		}
		echo '<p class="wpcron" id="schedulecron">';

		if ( 'advanced' === $crontype ) {
			echo wp_kses(
			str_replace(
				'\"',
				'"',
				__( 'Working as <a href="http://wikipedia.org/wiki/Cron">Cron</a> schedule:', 'backwpup' )
			),
			[
				'a' => [
					'href' => [],
				],
			]
			);
			echo ' <i><b>' . esc_html( $cronstamp ) . '</b></i><br />';
		}

		$cronstr = [];
		[$cronstr['minutes'], $cronstr['hours'], $cronstr['mday'], $cronstr['mon'], $cronstr['wday']] = explode( ' ', $cronstamp, 5 );
		if ( false !== strpos( $cronstr['minutes'], '*/' ) || '*' === $cronstr['minutes'] ) {
			$repeatmins = str_replace( '*/', '', $cronstr['minutes'] );
			if ( '*' === $repeatmins || empty( $repeatmins ) ) {
				$repeatmins = 5;
			}
			// translators: %d: number of minutes.
			echo '<span class="bwu-message-error">' . sprintf( esc_html__( 'ATTENTION: Job runs every %d minutes!', 'backwpup' ), absint( $repeatmins ) ) . '</span><br />';
		}
		$cron_next = BackWPup_Cron::cron_next( $cronstamp ) + ( get_option( 'gmt_offset' ) * 3600 );
		if ( PHP_INT_MAX === $cron_next ) {
			echo '<span class="bwu-message-error">' . esc_html__( 'ATTENTION: Can\'t calculate cron!', 'backwpup' ) . '</span><br />';
		} else {
			esc_html_e( 'Next runtime:', 'backwpup' );
			echo ' <b>' . esc_html( wp_date( 'D, j M Y, H:i', $cron_next ) ) . '</b>';
		}
		echo '</p>';

		if ( $ajax ) {
			exit();
		}
	}
}
