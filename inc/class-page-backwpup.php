<?php
/**
 * Render plugin dashboard.
 */
class BackWPup_Page_BackWPup {

	/**
	 * Called on load action.
	 */
	public static function load() {
		global $wpdb;

		$action = filter_input( INPUT_GET, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( null === $action && isset( $_GET['action'] ) ) {
			$action = sanitize_text_field( wp_unslash( $_GET['action'] ) );
		} else {
			$action = $action ? sanitize_text_field( $action ) : '';
		}
		$action = $action ? sanitize_key( $action ) : '';
		if ( 'dbdumpdl' === $action ) {
			// Check permissions.
			check_admin_referer( 'backwpupdbdumpdl' );

			if ( ! current_user_can( 'backwpup_jobs_edit' ) ) {
				exit();
			}

			// Start dump.
			header( 'Pragma: public' );
			header( 'Expires: 0' );
			header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
			header( 'Content-Type: application/octet-stream; charset=' . get_bloginfo( 'charset' ) );
			header( 'Content-Disposition: attachment; filename=' . DB_NAME . '.sql;' );

			try {
				$sql_dump = new BackWPup_MySQLDump();

				foreach ( $sql_dump->tables_to_dump as $key => $table ) {
					if ( substr( (string) $table, 0, strlen( (string) $wpdb->prefix ) ) !== $wpdb->prefix ) {
						unset( $sql_dump->tables_to_dump[ $key ] );
					}
				}
				$sql_dump->execute();
				unset( $sql_dump );
			} catch ( Exception $e ) {
				exit( esc_html( $e->getMessage() ) );
			}

			exit();
		}
	}

	/**
	 * Enqueue script.
	 */
	public static function admin_print_scripts() {
		wp_enqueue_script( 'backwpupgeneral' );
	}

	/**
	 * Print the markup
     * @phpcs:disable
	 */
	public static function page() {
		?>
		<div class="wrap" id="backwpup-page">
            <h1><?php printf(
                esc_html__( '%s &rsaquo; Dashboard', 'backwpup' ),
                esc_html( BackWPup::get_plugin_data( 'name' ) )
            ); ?></h1>
			<?php

            BackWPup_Admin::display_messages();

        if (BackWPup::is_pro()) { ?>
				<div class="backwpup-welcome backwpup-max-width">
					<h3><?php echo esc_html_x( 'Planning backups', 'Dashboard heading', 'backwpup' ); ?></h3>
					<p><?php esc_html_e('BackWPup’s job wizards make planning and scheduling your backup jobs a breeze.', 'backwpup'); echo ' '; echo wp_kses_post( __( 'Use your backup archives to save your entire WordPress installation including <code>/wp-content/</code>. Push them to an external storage service if you don’t want to save the backups on the same server.', 'backwpup' ) ); ?></p>
					<h3><?php echo esc_html_x( 'Restoring backups', 'Dashboard heading', 'backwpup' ); ?></h3>
					<p><?php esc_html_e('With a single backup archive you are able to restore an installation. Use our restore feature, which is integrated in BackWPup Pro to restore your website directly from your WordPress backend. We also provide a restore standalone app with the Pro version to restore your site in case it is destroyed completely.', 'backwpup'); ?></p>
					<h3><?php echo esc_html_x( 'Ready to set up a backup job?', 'Dashboard heading', 'backwpup' ); ?></h3>
					<p><?php echo wp_kses(
                        sprintf(
                            __( 'Use one of the wizards to plan a backup, or use <a href="%s">expert mode</a> for full control over all options.', 'backwpup' ),
                            esc_url( network_admin_url('admin.php') . '?page=backwpupeditjob' )
                        ),
                        [
                            'a' => [
                                'href' => [],
                            ],
                        ]
							); echo ' '; echo wp_kses_post( __( '<strong>Please note: You are solely responsible for the security of your data; the authors of this plugin are not.</strong>', 'backwpup' ) ); ?></p>
				</div>
																																																				<?php } else { ?>
				<div class="backwpup-welcome backwpup-max-width">
					<h3><?php echo esc_html_x( 'Planning backups', 'Dashboard heading', 'backwpup' ); ?></h3>
					<p><?php echo wp_kses_post( __( 'Use the short links in the <strong>First steps</strong> box to plan and schedule backup jobs.', 'backwpup' ) ); echo ' '; echo wp_kses_post( __( 'Use your backup archives to save your entire WordPress installation including <code>/wp-content/</code>. Push them to an external storage service if you don’t want to save the backups on the same server.', 'backwpup' ) ); ?></p>
					<h3><?php echo esc_html_x( 'Restoring backups', 'Dashboard heading', 'backwpup' ); ?></h3>
					<p><?php esc_html_e('With a single backup archive you are able to restore an installation. Use our restore feature, which is integrated in BackWPup Pro to restore your website directly from your WordPress backend. We also provide a restore standalone app with the Pro version to restore your site in case it is destroyed completely.', 'backwpup'); ?></p>
					<h3><?php echo esc_html_x( 'Ready to set up a backup job?', 'Dashboard heading', 'backwpup' ); ?></h3>
					<p><?php echo wp_kses(
                        sprintf(
                            __( '<a href="%s">Add a new backup job</a> and plan what you want to save.', 'backwpup' ),
                            esc_url( network_admin_url('admin.php') . '?page=backwpupeditjob' )
                        ),
                        [
                            'a' => [
                                'href' => [],
                            ],
                        ]
                    ); ?>
					<br /><?php echo wp_kses_post( __( '<strong>Please note: You are solely responsible for the security of your data; the authors of this plugin are not.</strong>', 'backwpup' ) ); ?></p>
				</div>
			<?php }

									if ( current_user_can( 'backwpup_jobs_edit' ) && current_user_can( 'backwpup_logs' ) && current_user_can( 'backwpup_jobs_start' ) ) {
																																																																									?>
				<div  id="backwpup-first-steps" class="metabox-holder postbox backwpup-floated-postbox">
					<h3 class="hndle"><span><?php esc_html_e('First Steps', 'backwpup'); ?></span></h3>
					<div class="inside">
						<ul>
              <li type="1"><a href="<?php echo esc_url( network_admin_url('admin.php') . '?page=backwpupsettings#backwpup-tab-information' ); ?>"><?php esc_html_e('Check the installation', 'backwpup'); ?></a></li>
              <li type="1"><a href="<?php echo esc_url( network_admin_url('admin.php') . '?page=backwpupeditjob' ); ?>"><?php esc_html_e('Create a Job', 'backwpup'); ?></a></li>
							<li type="1"><a href="<?php echo esc_url( network_admin_url('admin.php') . '?page=backwpupjobs' ); ?>"><?php esc_html_e('Run the created job', 'backwpup'); ?></a></li>
							<li type="1"><a href="<?php echo esc_url( network_admin_url('admin.php') . '?page=backwpuplogs' ); ?>"><?php esc_html_e('Check the job log', 'backwpup'); ?></a></li>
						</ul>
					</div>
				</div>
																																																																															<?php
									}

        if (current_user_can('backwpup_jobs_start')) {?>
				<div id="backwpup-one-click-backup" class="metabox-holder postbox backwpup-floated-postbox">
					<h3 class="hndle"><span><?php esc_html_e('One click backup', 'backwpup'); ?></span></h3>
					<div class="inside">
						<a href="<?php echo esc_url( wp_nonce_url(network_admin_url('admin.php?page=backwpup&action=dbdumpdl'), 'backwpupdbdumpdl') ); ?>" class="button button-primary button-primary-bwp" title="<?php esc_attr_e('Generate a database backup of WordPress tables and download it right away!', 'backwpup'); ?>"><?php esc_html_e('Download database backup', 'backwpup'); ?></a><br />
					</div>
				</div>
																		<?php } ?>

			<div id="backwpup-rss-feed" class="metabox-holder postbox backwpup-cleared-postbox backwpup-max-width">
				<h3 class="hndle"><span><?php esc_html_e('BackWPup News', 'backwpup'); ?></span></h3>
				<div class="inside">
					<?php
					$rss = fetch_feed( _x( 'https://backwpup.com/feed/', 'BackWPup News RSS Feed URL', 'backwpup' ) );
					if ( is_wp_error( $rss ) ) {
						echo '<p>' . wp_kses(
							sprintf( __( '<strong>RSS Error</strong>: %s', 'backwpup' ), esc_html( $rss->get_error_message() ) ),
							[
								'strong' => [],
							]
						) . '</p>';
					} elseif ( ! $rss->get_item_quantity() ) {
						echo '<ul><li>' . esc_html__( 'An error has occurred, which probably means the feed is down. Try again later.', 'backwpup' ) . '</li></ul>';
						$rss->__destruct();
						unset( $rss );
					} else {
						echo '<ul>';
						$first = true;

						foreach ( $rss->get_items( 0, 4 ) as $item ) {
							$link = $item->get_link();

							while ( stristr( (string) $link, 'http' ) != $link ) {
								$link = substr( (string) $link, 1 );
							}
							$link  = esc_url( strip_tags( (string) $link ) );
							$title = esc_html( strip_tags( (string) $item->get_title() ) );
							if ( empty( $title ) ) {
								$title = esc_html__( 'Untitled', 'backwpup' );
							}

							$desc    = str_replace( [ "\n", "\r" ], ' ', esc_attr( strip_tags( @html_entity_decode( (string) $item->get_description(), ENT_QUOTES, get_option( 'blog_charset' ) ) ) ) );
							$excerpt = wp_html_excerpt( $desc, 360 );

							// Append ellipsis. Change existing [...] to [&hellip;].
							if ( '[...]' == substr( $excerpt, -5 ) ) {
								$excerpt = substr( $excerpt, 0, -5 ) . '[&hellip;]';
							} elseif ( '[&hellip;]' != substr( $excerpt, -10 ) && $desc != $excerpt ) {
								$excerpt .= ' [&hellip;]';
							}

							$excerpt = esc_html( $excerpt );

							if ( $first ) {
								$summary = "<div class='rssSummary'>{$excerpt}</div>";
							} else {
								$summary = '';
							}

							$date = '';
							if ( $first ) {
								$date = $item->get_date( 'U' );

								if ( $date ) {
									$date = ' <span class="rss-date">' . esc_html( date_i18n( get_option( 'date_format' ), $date ) ) . '</span>';
								}
							}

							echo "<li><a href=\"{$link}\" title=\"{$desc}\">{$title}</a>{$date}{$summary}</li>";
							$first = false;
						}
						echo '</ul>';
						$rss->__destruct();
						unset( $rss );
					}
					?>
				</div>
			</div>

	        <div class="metabox-holder postbox backwpup-cleared-postbox backwpup-floated-postbox">
		        <h3 class="hndle"><span><a href="https://www.ostraining.com/">OSTraining</a> <?php esc_html_e('Video: Introduction', 'backwpup'); ?></span></h3>
		        <iframe class="inside" width="340" height="190" src="https://www.youtube.com/embed/pECMkLE27QQ?rel=0&amp;showinfo=0" frameborder="0" allowfullscreen></iframe>
	        </div>

	        <div class="metabox-holder postbox backwpup-floated-postbox">
		        <h3 class="hndle"><span><a href="https://www.ostraining.com/">OSTraining</a> <?php esc_html_e('Video: Settings', 'backwpup'); ?></span></h3>
		        <iframe class="inside" width="340" height="190" src="https://www.youtube.com/embed/F55xEoDnS0U?rel=0&amp;showinfo=0" frameborder="0" allowfullscreen></iframe>
	        </div>

	        <div class="metabox-holder postbox backwpup-cleared-postbox backwpup-floated-postbox">
		        <h3 class="hndle"><span><a href="https://www.ostraining.com/">OSTraining</a> <?php esc_html_e('Video: Daily Backups', 'backwpup'); ?></span></h3>
		        <iframe class="inside" width="340" height="190" src="https://www.youtube.com/embed/staZo0DS5m4?rel=0&amp;showinfo=0" frameborder="0" allowfullscreen></iframe>
	        </div>

	        <div class="metabox-holder postbox backwpup-floated-postbox">
		        <h3 class="hndle"><span><a href="https://www.ostraining.com/">OSTraining</a> <?php esc_html_e('Video: Creating Full Backups', 'backwpup'); ?></span></h3>
		        <iframe class="inside" width="340" height="190" src="https://www.youtube.com/embed/3N9FbmBuaac?rel=0&amp;showinfo=0" frameborder="0" allowfullscreen></iframe>
	        </div>

	        <div class="metabox-holder postbox backwpup-cleared-postbox backwpup-floated-postbox">
		        <h3 class="hndle"><span><a href="https://www.ostraining.com/">OSTraining</a> <?php esc_html_e('Video: Restoring Backups', 'backwpup'); ?></span></h3>
		        <iframe class="inside" width="340" height="190" src="https://www.youtube.com/embed/VIwDp87vYZY?rel=0&amp;showinfo=0" frameborder="0" allowfullscreen></iframe>
	        </div>

			<div id="backwpup-stats" class="metabox-holder postbox backwpup-cleared-postbox backwpup-max-width">
				<div class="backwpup-table-wrap">
				<?php
                    self::mb_next_jobs();
        self::mb_last_logs(); ?>
				</div>
			</div>

			<?php if (!BackWPup::is_pro()) { ?>
			<div id="backwpup-thank-you" class="metabox-holder postbox backwpup-cleared-postbox backwpup-max-width">
				<h3 class="hndle"><span><?php echo esc_html_x( 'Thank you for using BackWPup!', 'Pro teaser box', 'backwpup' ); ?></span></h3>
				<div class="inside">
                    <p><a href="<?php echo esc_url( __( 'http://backwpup.com', 'backwpup' ) ); ?>"><img class="backwpup-banner-img" src="<?php echo esc_url( BackWPup::get_plugin_data('URL') ); ?>/assets/images/banner.png" alt="<?php esc_attr_e('BackWPup banner', 'backwpup'); ?>" /></a></p>
					<h3 class="backwpup-text-center"><?php echo esc_html_x( 'Get access to:', 'Pro teaser box', 'backwpup' ); ?></h3>
					<ul class="backwpup-text-center">
						<li><?php echo wp_kses_post( _x( 'First-class <strong>dedicated support</strong> at backwpup.com.', 'Pro teaser box', 'backwpup' ) ); ?></li>
						<li><?php echo esc_html_x('Differential backups to Google Drive and other cloud storage service.', 'Pro teaser box', 'backwpup'); ?></li>
						<li><?php echo esc_html_x('Easy-peasy wizards to create and schedule backup jobs.', 'Pro teaser box', 'backwpup'); ?></li>
						<li><?php printf(
								'<a href="%s">%s</a>',
								esc_url( __( 'http://backwpup.com', 'backwpup' ) ),
								esc_html_x( 'And more…', 'Pro teaser box, link text', 'backwpup' )
							); ?></li>
					</ul>
					<p class="backwpup-text-center"><a href="<?php echo esc_url( __( 'http://backwpup.com', 'backwpup' ) ); ?>" class="button button-primary button-primary-bwp" title="<?php echo esc_attr_x( 'Get BackWPup Pro now', 'Pro teaser box, link title', 'backwpup' ); ?>"><?php echo esc_html_x( 'Get BackWPup Pro now', 'Pro teaser box, link text', 'backwpup' ); ?></a></p>
				</div>
			</div>
			<?php } ?>

        </div>
	<?php
    }

    /**
     * Displaying next jobs.
     */
    private static function mb_next_jobs()
    {
        if (!current_user_can('backwpup_jobs')) {
            return;
        } ?>
		<table class="wp-list-table widefat" cellspacing="0">
			<caption><?php esc_html_e('Next scheduled jobs', 'backwpup'); ?></caption>
			<thead>
			<tr>
				<th style="width: 30%"><?php esc_html_e('Time', 'backwpup'); ?></th>
				<th style="width: 70%"><?php esc_html_e('Job', 'backwpup'); ?></th>
			</tr>
			</thead>
			<?php
			// get next jobs.
			$mainsactive = BackWPup_Option::get_job_ids( 'activetype', 'wpcron' );
			sort( $mainsactive );
			$alternate = true;
			// add working job if it not in active jobs.
			$job_object = BackWPup_Job::get_working_data();
			if ( ! empty( $job_object ) && ! empty( $job_object->job['jobid'] ) && ! in_array( $job_object->job['jobid'], $mainsactive, true ) ) {
				$mainsactive[] = $job_object->job['jobid'];
			}

			foreach ( $mainsactive as $jobid ) {
				$name = BackWPup_Option::get( $jobid, 'name' );
				if ( ! empty( $job_object ) && $job_object->job['jobid'] === $jobid ) {
					$runtime = current_time( 'timestamp' ) - $job_object->job['lastrun'];
					if ( ! $alternate ) {
						echo '<tr>';
						$alternate = true;
					} else {
						echo '<tr class="alternate">';
						$alternate = false;
					}
					echo '<td><span style="color:#e66f00;">' . sprintf( esc_html__( 'working since %d seconds', 'backwpup' ), absint( $runtime ) ) . '</span></td>';
					echo '<td><span style="font-weight:bold;">' . esc_html( $job_object->job['name'] ) . '</span><br />';
					echo '<a style="color:red;" href="' . esc_url( wp_nonce_url( network_admin_url( 'admin.php?page=backwpupjobs&action=abort' ), 'abort-job' ) ) . '">' . esc_html__( 'Abort', 'backwpup' ) . '</a>';
					echo '</td></tr>';
				} else {
					if ( ! $alternate ) {
						echo '<tr>';
						$alternate = true;
					} else {
						echo '<tr class="alternate">';
						$alternate = false;
					}
					if ( $nextrun = wp_next_scheduled( 'backwpup_cron', [ 'arg' => $jobid ] ) + ( get_option( 'gmt_offset' ) * 3600 ) ) {
						echo '<td>' . sprintf(
							esc_html__( '%1$s at %2$s', 'backwpup' ),
							esc_html( date_i18n( get_option( 'date_format' ), $nextrun, true ) ),
							esc_html( date_i18n( get_option( 'time_format' ), $nextrun, true ) )
						) . '</td>';
					} else {
						echo '<td><em>' . esc_html__( 'Not scheduled!', 'backwpup' ) . '</em></td>';
					}

					echo '<td><a href="' . esc_url( wp_nonce_url( network_admin_url( 'admin.php' ) . '?page=backwpupeditjob&jobid=' . $jobid, 'edit-job' ) ) . '" title="' . esc_attr( __( 'Edit Job', 'backwpup' ) ) . '">' . esc_html( $name ) . '</a></td></tr>';
				}
			}
			if ( empty( $mainsactive ) and ! empty( $job_object ) ) {
				echo '<tr><td colspan="2"><i>' . esc_html__( 'none', 'backwpup' ) . '</i></td></tr>';
			}
			?>
		</table>
		<?php
    }

    /**
     * Displaying last logs.
     */
    private static function mb_last_logs()
    {
        if (!current_user_can('backwpup_logs')) {
            return;
        } ?>
		<table class="wp-list-table widefat" cellspacing="0">
			<caption><?php esc_html_e('Last logs', 'backwpup'); ?></caption>
			<thead>
			<tr><th style="width:30%"><?php esc_html_e('Time', 'backwpup'); ?></th><th style="width:55%"><?php esc_html_e('Job', 'backwpup'); ?></th><th style="width:20%"><?php esc_html_e('Result', 'backwpup'); ?></th></tr>
			</thead>
			<?php
			// get log files
			$logfiles   = [];
			$log_folder = get_site_option( 'backwpup_cfg_logfolder' );
			$log_folder = BackWPup_File::get_absolute_path( $log_folder );
			if ( is_readable( $log_folder ) ) {
				try {
					$dir = new BackWPup_Directory( $log_folder );

					foreach ( $dir as $file ) {
						if ( $file->isReadable() && $file->isFile() && strpos( $file->getFilename(), 'backwpup_log_' ) !== false && strpos( $file->getFilename(), '.html' ) !== false ) {
							$logfiles[ $file->getMTime() ] = clone $file;
						}
					}
					krsort( $logfiles, SORT_NUMERIC );
				} catch ( UnexpectedValueException $e ) {
					echo '<tr><td colspan="3"><span style="color:red;font-weight:bold;">' .
					sprintf( esc_html__( 'Could not open log folder: %s', 'backwpup' ), esc_html( $log_folder ) ) .
					'</td></tr>';
				}
			}

			if ( count( $logfiles ) > 0 ) {
				$count     = 0;
				$alternate = true;

				foreach ( $logfiles as $logfile ) {
					$logdata = BackWPup_Job::read_logheader( $logfile->getPathname() );
					if ( ! $alternate ) {
						echo '<tr>';
						$alternate = true;
					} else {
						echo '<tr class="alternate">';
						$alternate = false;
					}
					echo '<td>' . sprintf(
						esc_html__( '%1$s at %2$s', 'backwpup' ),
						esc_html( date_i18n( get_option( 'date_format' ), $logdata['logtime'] ) ),
						esc_html( date_i18n( get_option( 'time_format' ), $logdata['logtime'] ) )
					) . '</td>';
					$log_name = str_replace( [ '.html', '.gz' ], '', $logfile->getBasename() );
					echo '<td><a class="thickbox" href="' . esc_url( admin_url( 'admin-ajax.php?action=backwpup_view_log&log=' . $log_name . '&_ajax_nonce=' . wp_create_nonce( 'view-log_' . $log_name ) . '&TB_iframe=true&width=640&height=440' ) ) . '" title="' . esc_attr( $logfile->getBasename() ) . '">' . esc_html( $logdata['name'] ) . '</i></a></td>';
					echo '<td>';
					if ( $logdata['errors'] ) {
						printf(
							'<span style="color:red;font-weight:bold;">%s</span><br />',
							esc_html(
								sprintf(
									_n( '%d ERROR', '%d ERRORS', $logdata['errors'], 'backwpup' ),
									absint( $logdata['errors'] )
								)
							)
						);
					}
					if ( $logdata['warnings'] ) {
						printf(
							'<span style="color:#e66f00;font-weight:bold;">%s</span><br />',
							esc_html(
								sprintf(
									_n( '%d WARNING', '%d WARNINGS', $logdata['warnings'], 'backwpup' ),
									absint( $logdata['warnings'] )
								)
							)
						);
					}
					if ( ! $logdata['errors'] && ! $logdata['warnings'] ) {
						echo '<span style="color:green;font-weight:bold;">' . esc_html__( 'OK', 'backwpup' ) . '</span>';
					}
					echo '</td></tr>';
					++$count;
					if ( $count >= 5 ) {
						break;
					}
				}
			} else {
				echo '<tr><td colspan="3">' . esc_html__( 'none', 'backwpup' ) . '</td></tr>';
			}
			?>
		</table>
		<?php
    }
}
