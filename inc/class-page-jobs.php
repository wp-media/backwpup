<?php

use function Inpsyde\BackWPup\Infrastructure\Restore\restore_container;
use Inpsyde\Restore\ViewLoader;

/**
 * Class For BackWPup Jobs page.
 */
class BackWPup_Page_Jobs extends WP_List_Table
{
    public static $logfile;

    private static $listtable;
    private $job_object;
    private $job_types;
    private $destinations;

    public function __construct()
    {
        parent::__construct([
            'plural' => 'jobs',
            'singular' => 'job',
            'ajax' => true,
        ]);
    }

    /**
     * @return bool|void
     */
    public function ajax_user_can()
    {
        return current_user_can('backwpup');
    }

    public function prepare_items()
    {
        $this->items = BackWPup_Option::get_job_ids();
        $this->job_object = BackWPup_Job::get_working_data();
        $this->job_types = BackWPup::get_job_types();
        $this->destinations = BackWPup::get_registered_destinations();

        if (!isset($_GET['order']) || !isset($_GET['orderby'])) {
            return;
        }

        if (strtolower((string) $_GET['order']) === 'asc') {
            $order = SORT_ASC;
        } else {
            $order = SORT_DESC;
        }

        if (empty($_GET['orderby']) || !in_array(strtolower((string) $_GET['orderby']), ['jobname', 'type', 'dest', 'next', 'last'], true)) {
            $orderby = 'jobname';
        } else {
            $orderby = strtolower((string) $_GET['orderby']);
        }

        //sorting
        $job_configs = [];
        $i = 0;

        foreach ($this->items as $item) {
            $job_configs[$i]['jobid'] = $item;
            $job_configs[$i]['jobname'] = BackWPup_Option::get($item, 'name');
            $job_configs[$i]['type'] = BackWPup_Option::get($item, 'type');
            $job_configs[$i]['dest'] = BackWPup_Option::get($item, 'destinations');
            if ($order === SORT_ASC) {
                sort($job_configs[$i]['type']);
                sort($job_configs[$i]['dest']);
            } else {
                rsort($job_configs[$i]['type']);
                rsort($job_configs[$i]['dest']);
            }
            $job_configs[$i]['type'] = array_shift($job_configs[$i]['type']);
            $job_configs[$i]['dest'] = array_shift($job_configs[$i]['dest']);
            $job_configs[$i]['next'] = (int) wp_next_scheduled('backwpup_cron', ['arg' => $item]);
            $job_configs[$i]['last'] = BackWPup_Option::get($item, 'lastrun');
            ++$i;
        }

        $tmp = [];

        foreach ($job_configs as &$ma) {
            $tmp[] = &$ma[$orderby];
        }
        array_multisort($tmp, $order, $job_configs);

        $this->items = [];

        foreach ($job_configs as $item) {
            $this->items[] = $item['jobid'];
        }
    }

    public function no_items()
    {
        _e('No Jobs.', 'backwpup');
    }

    /**
     * @return array
     */
    public function get_bulk_actions()
    {
        if (!$this->has_items()) {
            return [];
        }

        $actions = [];
        $actions['delete'] = __('Delete', 'backwpup');

		return wpm_apply_filters_typed( 'array', 'backwpup_page_jobs_get_bulk_actions', $actions );
	}

    /**
     * @return array
     */
    public function get_columns()
    {
        $jobs_columns = [];
        $jobs_columns['cb'] = '<input type="checkbox" />';
        $jobs_columns['jobname'] = __('Job Name', 'backwpup');
        $jobs_columns['type'] = __('Type', 'backwpup');
        $jobs_columns['dest'] = __('Destinations', 'backwpup');
        $jobs_columns['next'] = __('Next Run', 'backwpup');
        $jobs_columns['last'] = __('Last Run', 'backwpup');

        return $jobs_columns;
    }

    /**
     * @return array
     */
    public function get_sortable_columns()
    {
        return [
            'jobname' => 'jobname',
            'type' => 'type',
            'dest' => 'dest',
            'next' => 'next',
            'last' => 'last',
        ];
    }

	/**
	 * Retrieves the table classes for the job page.
	 *
	 * This method constructs an array of CSS classes to be applied to the table
	 * on the job page. The 'fixed' class is intentionally omitted from the default
	 * classes.
	 *
	 * @return array An array of CSS classes for the table.
	 */
	protected function get_table_classes() {
		// Remove 'fixed' from the default classes.
		$classes = [ 'widefat', 'striped', $this->_args['plural'] ];
		return $classes;
	}

    /**
     * The cb Column.
     *
     * @param $item
     *
     * @return string
     */
    public function column_cb($item)
    {
        return '<input type="checkbox" name="jobs[]" value="' . esc_attr($item) . '" />';
    }

    /**
     * The jobname Column.
     *
     * @param $item
     *
     * @return string
     */
    public function column_jobname($item)
    {
        $job_normal_hide = '';
        if (is_object($this->job_object)) {
            $job_normal_hide = ' style="display:none;"';
        }

        $r = '<strong title="' . sprintf(__('Job ID: %d', 'backwpup'), $item) . '">' . esc_html(BackWPup_Option::get($item, 'name')) . '</strong>';
		$actions = [];
		if ( current_user_can( 'backwpup_jobs_edit' ) ) {
			$actions['edit'] = '<a href="' . wp_nonce_url( network_admin_url( 'admin.php' ) . '?page=backwpupeditjob&jobid=' . $item, 'edit-job' ) . '">' . esc_html__( 'Edit', 'backwpup' ) . '</a>';
			// $actions['copy'] = '<a href="' . wp_nonce_url(network_admin_url('admin.php') . '?page=backwpupjobs&action=copy&jobid=' . $item, 'copy-job_' . $item) . '">' . esc_html__('Copy', 'backwpup') . '</a>';
			$actions['delete'] = '<a class="submitdelete" href="' . wp_nonce_url( network_admin_url( 'admin.php' ) . '?page=backwpupjobs&action=delete&jobs[]=' . $item, 'bulk-jobs' ) . '" onclick="return showNotice.warn();">' . esc_html__( 'Delete', 'backwpup' ) . '</a>';
		}
        if (current_user_can('backwpup_jobs_start')) {
            $url = BackWPup_Job::get_jobrun_url('runnowlink', $item);
            $actions['runnow'] = '<a href="' . esc_attr($url['url']) . '">' . esc_html__('Run now', 'backwpup') . '</a>';
        }
        if (current_user_can('backwpup_logs') && BackWPup_Option::get($item, 'logfile')) {
            $logfile = basename((string) BackWPup_Option::get($item, 'logfile'));
            if (is_object($this->job_object) && $this->job_object->job['jobid'] == $item) {
                $logfile = basename((string) $this->job_object->logfile);
            }
            $log_name = str_replace(['.html', '.gz'], '', basename($logfile));
            $actions['lastlog'] = '<a href="' . admin_url('admin-ajax.php') . '?&action=backwpup_view_log&log=' . $log_name . '&_ajax_nonce=' . wp_create_nonce('view-log_' . $log_name) . '&amp;TB_iframe=true&amp;width=640&amp;height=440\" title="' . esc_attr($logfile) . '" class="thickbox">' . __('Last log', 'backwpup') . '</a>';
		}
		$actions = wpm_apply_filters_typed( 'array', 'backwpup_page_jobs_actions', $actions, $item, false );
		$r      .= '<div class="job-normal"' . $job_normal_hide . '>' . $this->row_actions( $actions ) . '</div>';
		if ( is_object( $this->job_object ) ) {
			$actionsrun = [];
			$actionsrun = wpm_apply_filters_typed( 'array', 'backwpup_page_jobs_actions', $actionsrun, $item, true );
			$r         .= '<div class="job-run">' . $this->row_actions( $actionsrun ) . '</div>';
		}

        return $r;
    }

    /**
     * The type Column.
     *
     * @param $item
     *
     * @return string
     */
    public function column_type($item)
    {
        $r = '';
        if ($types = BackWPup_Option::get($item, 'type')) {
            foreach ($types as $type) {
                if (isset($this->job_types[$type])) {
                    $r .= $this->job_types[$type]->info['name'] . '<br />';
                } else {
                    $r .= $type . '<br />';
                }
            }
        }

        return $r;
    }

    /**
     * The destination Column.
     *
     * @param $item
     *
     * @return string
     */
    public function column_dest($item)
    {
        $r = '';
        $backup_to = false;

        foreach (BackWPup_Option::get($item, 'type') as $typeid) {
            if (isset($this->job_types[$typeid]) && $this->job_types[$typeid]->creates_file()) {
                $backup_to = true;
                break;
            }
        }
        if ($backup_to) {
            foreach (BackWPup_Option::get($item, 'destinations') as $destid) {
                if (isset($this->destinations[$destid]['info']['name'])) {
                    $r .= $this->destinations[$destid]['info']['name'] . '<br />';
                } else {
                    $r .= $destid . '<br />';
                }
            }
        } else {
            $r .= '<i>' . __('Not needed or set', 'backwpup') . '</i><br />';
        }

        return $r;
    }

    /**
     * The next Column.
     *
     * @param $item
     *
     * @return string
     */
    public function column_next($item)
    {
        $r = '';

        $job_normal_hide = '';
        if (is_object($this->job_object)) {
            $job_normal_hide = ' style="display:none;"';
        }
        if (is_object($this->job_object) && $this->job_object->job['jobid'] == $item) {
            $runtime = current_time('timestamp') - $this->job_object->start_time;
            $r .= '<div class="job-run">' . sprintf(esc_html__('Running for: %s seconds', 'backwpup'), '<span id="runtime">' . $runtime . '</span>') . '</div>';
        }
        if (is_object($this->job_object) && $this->job_object->job['jobid'] == $item) {
            $r .= '<div class="job-normal"' . $job_normal_hide . '>';
		}
		if ( BackWPup_Option::get( $item, 'activetype' ) === 'wpcron' ) {
			$nextrun = wp_next_scheduled( 'backwpup_cron', [ 'arg' => $item ] ) + ( get_option( 'gmt_offset' ) * 3600 );
			if ( $nextrun ) {
				$r .= '<span title="' . sprintf( esc_html__( 'Cron: %s', 'backwpup' ), BackWPup_Option::get( $item, 'cron' ) ) . '">' . sprintf( __( '%1$s at %2$s', 'backwpup' ), date_i18n( get_option( 'date_format' ), $nextrun, true ), date_i18n( get_option( 'time_format' ), $nextrun, true ) ) . '</span><br />'; // @phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment
			} else {
                $r .= __('Not scheduled!', 'backwpup') . '<br />';
            }
        } elseif (BackWPup_Option::get($item, 'activetype') == 'easycron') {
            $easycron_status = BackWPup_EasyCron::status($item);
            if (!empty($easycron_status)) {
                $nextrun = BackWPup_Cron::cron_next($easycron_status['cron_expression']) + (get_option('gmt_offset') * 3600);
                $r .= '<span title="' . sprintf(esc_html__('Cron: %s', 'backwpup'), $easycron_status['cron_expression']) . '">' . sprintf(__('%1$s at %2$s by EasyCron', 'backwpup'), date_i18n(get_option('date_format'), $nextrun, true), date_i18n(get_option('time_format'), $nextrun, true)) . '</span><br />';
            } else {
                $r .= __('Not scheduled!', 'backwpup') . '<br />';
            }
        } elseif (BackWPup_Option::get($item, 'activetype') == 'link') {
            $r .= __('External link', 'backwpup') . '<br />';
        } else {
            $r .= __('Inactive', 'backwpup');
        }
        if (is_object($this->job_object) && $this->job_object->job['jobid'] == $item) {
            $r .= '</div>';
        }

        return $r;
    }

    /**
     * The last Column.
     *
     * @param $item
     *
     * @return string
     */
    public function column_last($item)
    {
        $r = '';

        if (BackWPup_Option::get($item, 'lastrun')) {
            $lastrun = BackWPup_Option::get($item, 'lastrun');
            $r .= sprintf(__('%1$s at %2$s', 'backwpup'), date_i18n(get_option('date_format'), $lastrun, true), date_i18n(get_option('time_format'), $lastrun, true));
            if (BackWPup_Option::get($item, 'lastruntime')) {
                $r .= '<br />' . sprintf(__('Runtime: %d seconds', 'backwpup'), BackWPup_Option::get($item, 'lastruntime'));
            }
        } else {
            $r .= __('not yet', 'backwpup');
        }
        $r .= '<br /><span class="last-action-links">';

        $download_url = BackWPup_Option::get($item, 'lastbackupdownloadurl');
        if (current_user_can('backwpup_backups_download') && !empty($download_url)) {
            // If it's not a direct link, but will be run through the downloader, then process differently
            if (strpos($download_url, 'backwpupbackups') === false) {
                $r .= '<a href="' . wp_nonce_url($download_url, 'backwpup_action_nonce') . '" title="' . esc_attr(__('Download last backup', 'backwpup')) . '">' . esc_html__('Download', 'backwpup') . '</a> | ';
            } else {
                $r .= self::generate_download_link($download_url) . ' | ';
            }
        }

        if (current_user_can('backwpup_logs') && BackWPup_Option::get($item, 'logfile')) {
            $logfile = basename((string) BackWPup_Option::get($item, 'logfile'));
            if (is_object($this->job_object) && $this->job_object->job['jobid'] == $item) {
                $logfile = basename((string) $this->job_object->logfile);
            }
            $log_name = str_replace(['.html', '.gz'], '', basename($logfile));
            $r .= '<a class="thickbox" href="' . admin_url('admin-ajax.php') . '?&action=backwpup_view_log&log=' . $log_name . '&_ajax_nonce=' . wp_create_nonce('view-log_' . $log_name) . '&amp;TB_iframe=true&amp;width=640&amp;height=440" title="' . esc_attr($logfile) . '">' . esc_html__('Log', 'backwpup') . '</a>';
        }
        $r .= '</span>';

        return $r;
    }

    private static function generate_download_link($download_url)
    {
        $params = [];
        parse_str(wp_parse_url($download_url, PHP_URL_QUERY), $params);

        $file = $params['file'];
        $local_file = untrailingslashit(BackWPup::get_plugin_data('TEMP')) . '/' . basename($params['local_file'] ?? $file);
        $jobid = $params['jobid'];
        $destination = strtoupper(str_replace('download', '', $params['action']));

        // Construct the link
        return sprintf(
			'<a href="#TB_inline?height=300&width=630&inlineId=tb_download_file" 
            class="backup-download-link thickbox js-backwpup-download-backup" 
            id="backup-download-link"
            data-jobid="%1$s" 
            data-destination="%2$s" 
            data-file="%3$s" 
            data-local-file="%4$s" 
            data-nonce="%5$s" 
            data-url="%6$s">%7$s</a>',
            intval($jobid),
            esc_attr($destination),
            esc_attr($file),
            esc_attr($local_file),
            wp_create_nonce('backwpup_action_nonce'),
            wp_nonce_url($download_url, 'backwpup_action_nonce'),
            __('Download', 'backwpup')
        );
    }

    public static function load()
    {
        //Create Table
        self::$listtable = new self();

        switch (self::$listtable->current_action()) {
            case 'delete': //Delete Job
                if (!current_user_can('backwpup_jobs_edit')) {
                    break;
				}
				$database_job_id = get_site_option( 'backwpup_backup_database_job_id', false );
				$files_job_id    = get_site_option( 'backwpup_backup_files_job_id', false );
				if ( isset( $_GET['jobs'] ) && is_array( $_GET['jobs'] ) ) {
					check_admin_referer( 'bulk-jobs' );

					foreach ( $_GET['jobs'] as $jobid ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
						// Do not delete the database or files job.
						if ( $jobid === $database_job_id || $jobid === $files_job_id ) {
							continue;
						}
						wp_clear_scheduled_hook( 'backwpup_cron', [ 'arg' => absint( $jobid ) ] );
						BackWPup_Option::delete_job( absint( $jobid ) );
					}
                }
                break;

            case 'copy': //Copy Job
                if (!current_user_can('backwpup_jobs_edit')) {
                    break;
                }
                $old_job_id = absint($_GET['jobid']);
                check_admin_referer('copy-job_' . $old_job_id);
                //create new
                $newjobid = BackWPup_Option::get_job_ids();
                sort($newjobid);
                $newjobid = end($newjobid) + 1;
                $old_options = BackWPup_Option::get_job($old_job_id);

                foreach ($old_options as $key => $option) {
                    if ($key === 'jobid') {
                        $option = $newjobid;
                    }
                    if ($key === 'name') {
                        $option = __('Copy of', 'backwpup') . ' ' . $option;
                    }
                    if ($key === 'activetype') {
                        $option = '';
                    }
                    if ($key === 'archivename') {
                        $option = str_replace($old_job_id, $newjobid, (string) $option);
                    }
                    if ($key === 'logfile' || $key === 'lastbackupdownloadurl' || $key === 'lastruntime' || $key === 'lastrun') {
                        continue;
                    }
                    BackWPup_Option::update($newjobid, $key, $option);
                }
                break;

            case 'runnow':
                $jobid = absint($_GET['jobid']);
                if ($jobid) {
                    if (!current_user_can('backwpup_jobs_start')) {
                        wp_die(__('Sorry, you don\'t have permissions to do that.', 'backwpup'));
                    }
                    check_admin_referer('backwpup_job_run-runnowlink');

                    //check temp folder
                    $temp_folder_message = BackWPup_File::check_folder(BackWPup::get_plugin_data('TEMP'), true);
                    BackWPup_Admin::message($temp_folder_message, true);
                    //check log folder
                    $log_folder = get_site_option('backwpup_cfg_logfolder');
                    $log_folder = BackWPup_File::get_absolute_path($log_folder);
                    $log_folder_message = BackWPup_File::check_folder($log_folder);
                    BackWPup_Admin::message($log_folder_message, true);
                    //check backup destinations
                    $job_types = BackWPup::get_job_types();
                    $job_conf_types = BackWPup_Option::get($jobid, 'type');
                    $creates_file = false;

                    foreach ($job_types as $id => $job_type_class) {
                        if (in_array($id, $job_conf_types, true) && $job_type_class->creates_file()) {
                            $creates_file = true;
                            break;
                        }
                    }
                    if ($creates_file) {
                        $job_conf_dests = BackWPup_Option::get($jobid, 'destinations');
                        $destinations = 0;

                        foreach (BackWPup::get_registered_destinations() as $id => $dest) {
                            if (!in_array($id, $job_conf_dests, true) || empty($dest['class'])) {
                                continue;
                            }

                            /** @var BackWPup_Destinations $dest_class */
                            $dest_class = BackWPup::get_destination($id);
                            $job_settings = BackWPup_Option::get_job($jobid);
                            if (!$dest_class->can_run($job_settings)) {
                                BackWPup_Admin::message(sprintf(__('The job "%1$s" destination "%2$s" is not configured properly', 'backwpup'), esc_attr(BackWPup_Option::get($jobid, 'name')), $id), true);
                            }
                            ++$destinations;
                        }
                        if ($destinations < 1) {
                            BackWPup_Admin::message(sprintf(__('The job "%s" needs properly configured destinations to run!', 'backwpup'), esc_attr(BackWPup_Option::get($jobid, 'name'))), true);
                        }
                    }

					// only start job if messages empty.
					$log_messages = BackWPup_Admin::get_messages();

					if ( empty( $log_messages ) ) {
						$old_log_file = BackWPup_Option::get( $jobid, 'logfile' );
						BackWPup_Job::get_jobrun_url( 'runnow', $jobid );
						usleep( 250000 ); // wait a quarter second.
						$new_log_file = BackWPup_Option::get( $jobid, 'logfile', null, false );
						// sleep as long as job not started.
						$i = 0;

						while ( $old_log_file === $new_log_file ) {
							usleep( 250000 ); // wait a quarter second for next try.
							$new_log_file = BackWPup_Option::get( $jobid, 'logfile', null, false );
							// wait maximal 10 sec.
							if ( $i >= 40 ) {
								/* translators: 1: Job name, 2: Information URL */
								BackWPup_Admin::message( sprintf( __( 'Job "%1$s" has started, but not responded for 10 seconds. Please check <a href="%2$s">information</a>.', 'backwpup' ), esc_attr( BackWPup_Option::get( $jobid, 'name' ) ), network_admin_url( 'admin.php' ) . '?page=backwpupsettings#backwpup-tab-information' ), true );
								break 2;
                            }
                            ++$i;
						}
						/* translators: Job name */
						BackWPup_Admin::message( sprintf( __( 'Job "%s" started.', 'backwpup' ), esc_attr( BackWPup_Option::get( $jobid, 'name' ) ) ) );
					}
                }
                break;

            case 'abort': //Abort Job
                if (!current_user_can('backwpup_jobs_start')) {
                    break;
                }
                check_admin_referer('abort-job');
                if (!file_exists(BackWPup::get_plugin_data('running_file'))) {
                    break;
                }
                //abort
                BackWPup_Job::user_abort();
                BackWPup_Admin::message(__('Job will be terminated.', 'backwpup'));
                break;

            default:
                do_action('backwpup_page_jobs_load', self::$listtable->current_action());
                break;
        }

        self::$listtable->prepare_items();
    }

    public static function admin_print_styles()
    {
        ?>
        <style type="text/css" media="screen">

            .column-last, .column-next, .column-type, .column-dest {
                width: 15%;
            }

            #TB_ajaxContent {
                background-color: black;
                color: #c0c0c0;
            }

            #showworking {
                white-space:nowrap;
                display: block;
                width: 100%;
                font-family:monospace;
                font-size:12px;
                line-height:15px;
            }
            #runningjob {
                padding:10px;
                position:relative;
                margin: 15px 0 25px 0;
                padding-bottom:25px;
            }
            h2#runnigtitle {
                margin-bottom: 15px;
                padding: 0;
            }
            #warningsid, #errorid {
                margin-right: 10px;
            }

            .infobuttons {
                position: absolute;
                right: 10px;
                bottom: 0;
            }

            .progressbar {
                margin-top: 20px;
                height: auto;
                background: #f6f6f6 url('<?php echo BackWPup::get_plugin_data('URL'); ?>/assets/images/progressbarhg.jpg');
            }

            #lastmsg, #onstep, #lasterrormsg {
                text-align: center;
                margin-bottom: 20px;
            }
            #backwpup-page #lastmsg,
            #backwpup-page #onstep,
            #backwpup-page #lasterrormsg {
                font-family: "Open Sans", sans-serif;
            }
            .bwpu-progress {
                background-color: #1d94cf;
                color: #fff;
                padding: 5px 0;
                text-align: center;
            }
            #progresssteps {
                background-color: #007fb6;
            }

            .row-actions .lastlog {
                display: none;
            }

            @media screen and (max-width: 782px) {
                .column-type, .column-dest {
                    display: none;
                }
                .row-actions .lastlog {
                    display: inline-block;
                }
                .last-action-links {
                    display: none;
                }
            }
        </style>
        <?php
    }

    public static function admin_print_scripts()
    {
        wp_enqueue_script('backwpupgeneral');

        $suffix = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';
        $plugin_url = BackWPup::get_plugin_data('url');
        $plugin_dir = BackWPup::get_plugin_data('plugindir');
        $plugin_scripts_url = "{$plugin_url}/assets/js";
        $plugin_scripts_dir = "{$plugin_dir}/assets/js";
        $shared_scripts_path = "{$plugin_url}/vendor/inpsyde/backwpup-shared/resources/js";
        $shared_scripts_dir = "{$plugin_dir}/vendor/inpsyde/backwpup-shared/resources/js";

        wp_register_script(
            'backwpup_functions',
            "{$shared_scripts_path}/functions{$suffix}.js",
            ['underscore', 'jquery'],
            filemtime("{$shared_scripts_dir}/functions{$suffix}.js"),
            true
        );
        wp_register_script(
            'backwpup_states',
            "{$shared_scripts_path}/states{$suffix}.js",
            [
                'backwpup_functions',
            ],
            filemtime("{$shared_scripts_dir}/states{$suffix}.js"),
            true
        );

        $dependencies = [
            'jquery',
            'underscore',
            'backwpupgeneral',
            'backwpup_functions',
            'backwpup_states',
        ];
        if (\BackWPup::is_pro()) {
            $dependencies[] = 'decrypter';
        }
        wp_enqueue_script(
            'backwpup-backup-downloader',
            "{$plugin_scripts_url}/backup-downloader{$suffix}.js",
            $dependencies,
            filemtime("{$plugin_scripts_dir}/backup-downloader{$suffix}.js"),
            true
        );

        if (\BackWPup::is_pro()) {
            self::admin_print_pro_scripts($suffix, $plugin_url, $plugin_dir);
        }
    }

    private static function admin_print_pro_scripts($suffix, $plugin_url, $plugin_dir)
    {
        $restore_scripts_path = "{$plugin_url}/vendor/inpsyde/backwpup-restore-shared/resources/js";
        $restore_scripts_dir = "{$plugin_dir}/vendor/inpsyde/backwpup-restore-shared/resources/js";

        wp_register_script(
            'decrypter',
            "{$restore_scripts_path}/decrypter{$suffix}.js",
            [
                'underscore',
                'jquery',
                'backwpup_states',
                'backwpup_functions',
            ],
            filemtime("{$restore_scripts_dir}/decrypter{$suffix}.js"),
            true
        );
    }

    public static function page()
    {
		echo '<div class="wrap" id="backwpup-page">';
		// translators: %s: plugin name.
		echo '<h1>' . esc_html( sprintf( __( '%s &rsaquo; Jobs', 'backwpup' ), BackWPup::get_plugin_data( 'name' ) ) ) . '</h1>';
		BackWPup_Admin::display_messages();
        $job_object = BackWPup_Job::get_working_data();
        if (current_user_can('backwpup_jobs_start') && is_object($job_object)) {
            //read existing logfile
            $logfiledata = file_get_contents($job_object->logfile);
            preg_match('/<body[^>]*>/si', $logfiledata, $match);
            if (!empty($match[0])) {
                $startpos = strpos($logfiledata, $match[0]) + strlen($match[0]);
            } else {
                $startpos = 0;
            }
            $endpos = stripos($logfiledata, '</body>');
            if (empty($endpos)) {
                $endpos = strlen($logfiledata);
            }
            $length = strlen($logfiledata) - (strlen($logfiledata) - $endpos) - $startpos; ?>
            <div id="runningjob">
                <div id="runniginfos">
                    <h2 id="runningtitle"><?php esc_html(sprintf(__('Job currently running: %s', 'backwpup'), $job_object->job['name'])); ?></h2>
                    <span id="warningsid"><?php esc_html_e('Warnings:', 'backwpup'); ?> <span id="warnings"><?php echo $job_object->warnings; ?></span></span>
                    <span id="errorid"><?php esc_html_e('Errors:', 'backwpup'); ?> <span id="errors"><?php echo $job_object->errors; ?></span></span>
                    <div class="infobuttons"><a href="#TB_inline?height=440&width=630&inlineId=tb-showworking" id="showworkingbutton" class="thickbox button button-primary button-primary-bwp" title="<?php esc_attr_e('Log of running job', 'backwpup'); ?>"><?php esc_html_e('Display working log', 'backwpup'); ?></a>
                    <a href="<?php echo wp_nonce_url(network_admin_url('admin.php') . '?page=backwpupjobs&action=abort', 'abort-job'); ?>" id="abortbutton" class="backwpup-fancybox button button-bwp"><?php esc_html_e('Abort', 'backwpup'); ?></a>
                    <a href="#" id="showworkingclose" title="<?php esc_html_e('Close working screen', 'backwpup'); ?>" class="button button-bwp" style="display:none" ><?php esc_html_e('Close', 'backwpup'); ?></a></div>
                </div>
                <input type="hidden" name="logpos" id="logpos" value="<?php echo strlen($logfiledata); ?>">
                <div id="lasterrormsg"></div>
                <div class="progressbar"><div id="progressstep" class="bwpu-progress" style="width:<?php echo $job_object->step_percent; ?>%;"><?php echo esc_html($job_object->step_percent); ?>%</div></div>
                <div id="onstep"><?php echo esc_html($job_object->steps_data[$job_object->step_working]['NAME']); ?></div>
                <div class="progressbar"><div id="progresssteps" class="bwpu-progress" style="width:<?php echo $job_object->substep_percent; ?>%;"><?php echo esc_html($job_object->substep_percent); ?>%</div></div>
                <div id="lastmsg"><?php echo esc_html($job_object->lastmsg); ?></div>
                <div id="tb-showworking" style="display:none;">
                    <div id="showworking"><?php echo substr($logfiledata, $startpos, $length); ?></div>
                </div>
            </div>
            <?php
        }

        //display jobs Table?>
        <form id="posts-filter" action="" method="get">
        <input type="hidden" name="page" value="backwpupjobs" />
		<?php
		wp_nonce_field( 'backwpup_ajax_nonce', 'backwpupajaxnonce', false );
		self::$listtable->display();
		?>
		<div id="ajax-response"></div>
        </form>
        </div>

        <div id="tb_download_file" style="display: none;">
            <div id="tb_container">
                <p id="download-file-waiting">
                    <?php esc_html_e('Please wait &hellip;', 'backwpup'); ?>
                </p>
                <p id="download-file-success" style="display: none;">
                    <?php esc_html_e(
                        'Your download has been generated. It should begin downloading momentarily.',
                        'backwpup'
                    ); ?>
                </p>
                <div class="progressbar" style="display: none;">
                    <div id="progresssteps" class="bwpu-progress" style="width:0%;">0%</div>
                </div>
				<?php
				if ( \BackWPup::is_pro() ) {
					$view = new ViewLoader();
					$view->decrypt_key_input();
                } ?>
            </div>
        </div>

        <?php

        if (!empty($job_object->logfile)) { ?>
        <script type="text/javascript">
            //<![CDATA[
            jQuery(document).ready(function ($) {
                backwpup_show_working = function () {
                    var save_log_pos = 0;
                    $.ajax({
                        type: 'GET',
                        url: ajaxurl,
                        cache: false,
                        data:{
                            action: 'backwpup_working',
                            logpos: $('#logpos').val(),
                            logfile: '<?php echo basename((string) $job_object->logfile); ?>',
                            _ajax_nonce: '<?php echo wp_create_nonce('backwpupworking_ajax_nonce'); ?>'
                        },
                        dataType: 'json',
                        success:function (rundata) {
                            if ( rundata == 0 ) {
                                $("#abortbutton").remove();
                                $("#backwpup-adminbar-running").remove();
                                $(".job-run").hide();
                                $("#message").hide();
                                $(".job-normal").show();
                                $('#showworkingclose').show();
                            }
                            if (0 < rundata.log_pos) {
                                $('#logpos').val(rundata.log_pos);
                            }
                            if ('' != rundata.log_text) {
                                $('#showworking').append(rundata.log_text);
                                $('#TB_ajaxContent').scrollTop(rundata.log_pos * 15);
                            }
                            if (0 < rundata.error_count) {
                                $('#errors').replaceWith('<span id="errors">' + rundata.error_count + '</span>');
                            }
                            if (0 < rundata.warning_count) {
                                $('#warnings').replaceWith('<span id="warnings">' + rundata.warning_count + '</span>');
                            }
                            if (0 < rundata.step_percent) {
                                $('#progressstep').replaceWith('<div id="progressstep" class="bwpu-progress">' + rundata.step_percent + '%</div>');
                                $('#progressstep').css('width', parseFloat(rundata.step_percent) + '%');
                            }
                            if (0 < rundata.sub_step_percent) {
                                $('#progresssteps').replaceWith('<div id="progresssteps" class="bwpu-progress">' + rundata.sub_step_percent + '%</div>');
                                $('#progresssteps').css('width', parseFloat(rundata.sub_step_percent) + '%');
                            }
                            if (0 < rundata.running_time) {
                                $('#runtime').replaceWith('<span id="runtime">' + rundata.running_time + '</span>');
                            }
                            if ( '' != rundata.onstep ) {
                                $('#onstep').replaceWith('<div id="onstep">' + rundata.on_step + '</div>');
                            }
                            if ( '' != rundata.last_msg ) {
                                $('#lastmsg').replaceWith('<div id="lastmsg">' + rundata.last_msg + '</div>');
                            }
                            if ( '' != rundata.last_error_msg ) {
                                $('#lasterrormsg').replaceWith('<div id="lasterrormsg">' + rundata.last_error_msg + '</div>');
                            }
                            if ( rundata.job_done == 1 ) {
                                $("#abortbutton").remove();
                                $("#backwpup-adminbar-running").remove();
                                $(".job-run").hide();
                                $("#message").hide();
                                $(".job-normal").show();
                                $('#showworkingclose').show();
                            } else {
                                if ( rundata.restart_url !== '' ) {
                                    backwpup_trigger_cron( rundata.restart_url );
                                }
                                setTimeout('backwpup_show_working()', 750);
                            }
                        },
                        error:function( ) {
                            setTimeout('backwpup_show_working()', 750);
                        }
                    });
                };
                backwpup_trigger_cron = function ( cron_url ) {
                    $.ajax({
                        type: 'POST',
                        url: cron_url,
                        dataType: 'text',
                        cache: false,
                        processData: false,
                        timeout: 1
                    });
                };
				backwpup_show_working();
				$('#showworkingclose').on('click',  function() {
					$("#runningjob").hide( 'slow' );
                    return false;
                });
            });
            //]]>
        </script>
        <?php }
    }

    /**
     * Function to generate json data.
	 */
	public static function ajax_working() {
		$log_folder = get_site_option( 'backwpup_cfg_logfolder' );
		$log_folder = BackWPup_File::get_absolute_path( $log_folder );

		$job_object = BackWPup_Job::get_working_data();
		$logfile    = basename( (string) $job_object->logfile );
		try {
			$logfile = self::get_logfile_path( $log_folder, $_GET['logfile'] ?? $logfile );        // phpcs:ignore 
		} catch ( \InvalidArgumentException $e ) {
			// Logfile either not passed or is invalid.
			echo 'error';
			exit( 0 );
		}

        $logpos = isset($_GET['logpos']) ? absint($_GET['logpos']) : 0; // phpcs:ignore
		$restart_url = '';

        //check if logfile renamed
        if (file_exists($logfile . '.gz')) {
            $logfile .= '.gz';
        }

        if (!is_readable($logfile)) {
            exit('0');
        }

        $job_object = BackWPup_Job::get_working_data();
        $done = 0;
        if (is_object($job_object)) {
            $warnings = $job_object->warnings;
            $errors = $job_object->errors;
            $step_percent = $job_object->step_percent;
			$substep_percent = $job_object->substep_percent;
			$runtime         = current_time( 'timestamp' ) - $job_object->start_time; // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
			$onstep          = $job_object->steps_data[ $job_object->step_working ]['NAME'];
			$lastmsg         = $job_object->lastmsg;
			$lasterrormsg    = $job_object->lasterrormsg;
			$step_done       = count( $job_object->steps_done );
			$step_todo       = count( $job_object->steps_todo );
			$substeps_todo   = $job_object->substeps_todo;
			$substeps_done   = $job_object->substeps_done;
		} else {
            $logheader = BackWPup_Job::read_logheader($logfile);
            $warnings = $logheader['warnings'];
            $runtime = $logheader['runtime'];
            $errors = $logheader['errors'];
            $step_percent = 100;
			$substep_percent = 100;
			$step_done       = 100;
			$step_todo       = 100;
			$substeps_todo   = 100;
			$substeps_done   = 100;
			$onstep          = esc_html__( 'Job completed', 'backwpup' );
			if ( $errors > 0 ) {
				// Translators: %s is duration in seconds.
				$lastmsg = '<div class="bwu-message-error"><p>' . esc_html__( 'ERROR:', 'backwpup' ) . ' ' . sprintf( esc_html__( 'Job has ended with errors in %s seconds. You must resolve the errors for correct execution.', 'backwpup' ), $logheader['runtime'] ) . '</p></div>';
			} elseif ( $warnings > 0 ) {
				// Translators: %s is duration in seconds.
				$lastmsg = '<div class="backwpup-message backwpup-warning"><p>' . esc_html__( 'WARNING:', 'backwpup' ) . ' ' . sprintf( esc_html__( 'backup created with warnings in %s seconds. Please resolve them for correct execution.', 'backwpup' ), $logheader['runtime'] ) . '</p></div>';
			} else {
				// Translators: %s is duration in seconds.
				$lastmsg = sprintf( esc_html__( 'Backup created in %s seconds.', 'backwpup' ), $logheader['runtime'] );
			}
            $lasterrormsg = '';
            $done = 1;
        }

        if ('.gz' == substr($logfile, -3)) {
            $logfiledata = file_get_contents('compress.zlib://' . $logfile, false, null, $logpos);
        } else {
            $logfiledata = file_get_contents($logfile, false, null, $logpos);
        }

        preg_match('/<body[^>]*>/si', $logfiledata, $match);
        if (!empty($match[0])) {
            $startpos = strpos($logfiledata, $match[0]) + strlen($match[0]);
        } else {
            $startpos = 0;
        }

        $endpos = stripos($logfiledata, '</body>');
        if (false === $endpos) {
            $endpos = strlen($logfiledata);
        }

        $length = strlen($logfiledata) - (strlen($logfiledata) - $endpos) - $startpos;

        //check if restart must done on ALTERNATE_WP_CRON
        if (is_object($job_object) && defined('ALTERNATE_WP_CRON') && ALTERNATE_WP_CRON) {
            $restart = BackWPup_Job::get_jobrun_url('restartalt');
            if ($job_object->pid === 0 && $job_object->uniqid === '') {
                $restart_url = $restart['url'];
            }
            $last_update = microtime(true) - $job_object->timestamp_last_update;
            if (empty($job_object->pid) && $last_update > 10) {
                $restart_url = $restart['url'];
            }
        }

		$data_ro_return = [
			'log_pos'          => strlen( $logfiledata ) + $logpos,
			'log_text'         => substr( $logfiledata, $startpos, $length ),
			'warning_count'    => $warnings,
			'error_count'      => $errors,
			'running_time'     => $runtime,
			'step_percent'     => $step_percent,
			'on_step'          => $onstep,
			'last_msg'         => $lastmsg,
			'last_error_msg'   => $lasterrormsg,
			'sub_step_percent' => $substep_percent,
			'restart_url'      => $restart_url,
			'job_done'         => $done,
			'step_done'        => $step_done,
			'step_todo'        => $step_todo,
			'substeps_todo'    => $substeps_todo,
			'substeps_done'    => $substeps_done,
			'job_id'           => $job_object->job['jobid'],
		];
		if ( (string) get_site_option( 'backwpup_backup_database_job_id', false ) !== (string) $job_object->job['jobid'] ) {
			$data_ro_return['job_next_id'] = get_site_option( 'backwpup_backup_database_job_id', false );
		}

		wp_send_json( $data_ro_return );
	}

    private static function get_logfile_path(string $folder, ?string $filename): string
    {
        if (!$filename) {
            throw new \InvalidArgumentException('Log file cannot be null.');
        }

        $filename = basename(trim($filename));

        if (
            preg_match('/^[a-zA-Z0-9_-]+\.[a-zA-Z0-9]{1,5}$/', $filename) === 0
            || strpos($filename, 'backwpup_log_') === false
        ) {
            throw new \InvalidArgumentException('Invalidly formatted log filename passed.');
        }

        return $folder . $filename;
    }
}
