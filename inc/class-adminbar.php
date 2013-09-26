<?php
/**
 * Class to display BackWPup in Adminbar
 */
class BackWPup_Adminbar {

	private static $instance = NULL;

	/**
	 *
	 */
	private function __construct() {

		if ( defined( 'DOING_CRON' )  || ! current_user_can( 'backwpup' ) || ! is_admin_bar_showing() || ! BackWPup_Option::get( 'cfg', 'showadminbar' ) )
			return;

		//load text domain
		load_plugin_textdomain( 'backwpupadminbar', FALSE, BackWPup::get_plugin_data( 'BaseName' ) . '/languages' );
		//add admin bar. Works only in init
		add_action( 'admin_bar_menu', array( $this, 'adminbar' ), 100 );
		//admin bar css
		add_action( 'wp_enqueue_scripts', array( $this, 'print_styles' ) );
		add_action( 'admin_print_scripts', array( $this, 'print_styles' ) );
	}

	/**
	 * @static
	 * @return \BackWPup_Adminbar
	 */
	public static function get_instance() {

		if (NULL === self::$instance) {
			self::$instance = new self;
		}
		return self::$instance;
	}


	private function __clone() {}


	/**
	 * @global $wp_admin_bar WP_Admin_Bar
	 */
	public function adminbar() {
		global $wp_admin_bar;
		/* @var WP_Admin_Bar $wp_admin_bar */

		$job_bool = BackWPup_Job::get_working_data( 'BOOL' );
		$menu_title = '<span class="ab-icon"></span><span class="ab-label">' . BackWPup::get_plugin_data( 'name' ) . '</span>';
		$menu_herf  = network_admin_url( 'admin.php' ) . '?page=backwpup';
		if ( $job_bool && current_user_can( 'backwpup_jobs_start' ) ) {
			$menu_title = '<span class="ab-icon"></span><span class="ab-label">' . BackWPup::get_plugin_data( 'name' )  . ' <span id="backwpup-adminbar-running">' .__( 'running', 'backwpupadminbar') . '</span></span>';
			$menu_herf  = network_admin_url( 'admin.php' ) . '?page=backwpupjobs';
		}

		if ( current_user_can( 'backwpup' ) )
			$wp_admin_bar->add_menu( array(
										  'id'    => 'backwpup',
										  'title' => $menu_title,
										  'href'  => $menu_herf,
										  'meta'  => array( 'title' => __( 'BackWPup', 'backwpupadminbar' ) )
									 ) );

		if ( $job_bool && current_user_can( 'backwpup_jobs_start' ) ) {
			$wp_admin_bar->add_menu( array(
										  'id'     => 'backwpup_working',
										  'parent' => 'backwpup_jobs',
										  'title'  => __( 'Now Running', 'backwpupadminbar' ),
										  'href'   => network_admin_url( 'admin.php' ) . '?page=backwpupjobs'
									 ) );
			$wp_admin_bar->add_menu( array(
										  'id'     => 'backwpup_working_abort',
										  'parent' => 'backwpup_working',
										  'title'  => __( 'Abort!', 'backwpupadminbar' ),
										  'href'   => wp_nonce_url( network_admin_url( 'admin.php' ) . '?page=backwpup&action=abort', 'abort-job' )
									 ) );
		}

		if ( current_user_can( 'backwpup_jobs' ) )
			$wp_admin_bar->add_menu( array(
									  'id'     => 'backwpup_jobs',
									  'parent' => 'backwpup',
									  'title'  => __( 'Jobs', 'backwpupadminbar' ),
									  'href'   => network_admin_url( 'admin.php' ) . '?page=backwpupjobs'
								 ) );

		if ( current_user_can( 'backwpup_jobs_edit' ) )
			$wp_admin_bar->add_menu( array(
									  'id'     => 'backwpup_jobs_new',
									  'parent' => 'backwpup_jobs',
									  'title'  => __( 'Add New', 'backwpupadminbar' ),
									  'href'   => network_admin_url( 'admin.php' ) . '?page=backwpupeditjob'
								 ) );

		if ( current_user_can( 'backwpup_logs' ) )
			$wp_admin_bar->add_menu( array(
									  'id'     => 'backwpup_logs',
									  'parent' => 'backwpup',
									  'title'  => __( 'Logs', 'backwpupadminbar' ),
									  'href'   => network_admin_url( 'admin.php' ) . '?page=backwpuplogs'
								 ) );

		if ( current_user_can( 'backwpup_backups' ) )
			$wp_admin_bar->add_menu( array(
									  'id'     => 'backwpup_backups',
									  'parent' => 'backwpup',
									  'title'  => __( 'Backups', 'backwpupadminbar' ),
									  'href'   => network_admin_url( 'admin.php' ) . '?page=backwpupbackups'
								 ) );


		//add jobs
		$jobs = (array)BackWPup_Option::get_job_ids();
		foreach ( $jobs as $jobid ) {
			if ( current_user_can( 'backwpup_jobs_edit' ) ) {
				$name = BackWPup_Option::get( $jobid, 'name' );
				$wp_admin_bar->add_menu( array(
											  'id'     => 'backwpup_jobs_' . $jobid,
											  'parent' => 'backwpup_jobs',
											  'title'  => $name,
											  'href'   => wp_nonce_url( network_admin_url( 'admin.php' ) . '?page=backwpupeditjob&jobid=' . $jobid, 'edit-job' )
										 ) );
			}
			if ( current_user_can( 'backwpup_jobs_start' ) ) {
				$url = BackWPup_Job::get_jobrun_url( 'runnowlink', $jobid );
				$wp_admin_bar->add_menu( array(
											  'id'     => 'backwpup_jobs_runnow_' . $jobid,
											  'parent' => 'backwpup_jobs_' . $jobid,
											  'title'  => __( 'Run Now', 'backwpupadminbar' ),
											  'href'   => $url[ 'url' ]
										 ) );
			}
		}
	}

	/**
	 *
	 */
	public function print_styles() {

		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG )
			wp_enqueue_style( 'backwpupadminbar', BackWPup::get_plugin_data( 'URL' ) . '/css/adminbar.css', '', time(), 'screen' );
		else
			wp_enqueue_style( 'backwpupadminbar', BackWPup::get_plugin_data( 'URL' ) . '/css/adminbar.min.css', '', BackWPup::get_plugin_data( 'Version' ), 'screen' );
	}
}
