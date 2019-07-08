<?php

use \Inpsyde\BackWPup\Pro\Settings;

/**
 * BackWPup_Admin
 */
final class BackWPup_Admin {

	private static $instance = null;

	public $page_hooks = array();

	private $settings;

	/**
	 *
	 * Set needed filters and actions and load all needed
	 */
	public function __construct() {

		$settings_views = array();
		$settings_updaters = array();

		if ( \BackWPup::is_pro() ) {
			$settings_views = array_merge(
				$settings_views,
				array(
					new Settings\EncryptionSettingsView(),
				)
			);
			$settings_updaters = array_merge(
				$settings_updaters,
				array(
					new Settings\EncryptionSettingUpdater(),
				)
			);
		}

		$this->settings = new BackWPup_Page_Settings(
			$settings_views,
			$settings_updaters
		);

		//Load text domain
		BackWPup::load_text_domain();

		//Add menu pages
		add_filter( 'backwpup_admin_pages', array( $this, 'admin_page_jobs' ), 2 );
		add_filter( 'backwpup_admin_pages', array( $this, 'admin_page_editjob' ), 3 );
		add_filter( 'backwpup_admin_pages', array( $this, 'admin_page_logs' ), 4 );
		add_filter( 'backwpup_admin_pages', array( $this, 'admin_page_backups' ), 5 );
		add_filter( 'backwpup_admin_pages', array( $this, 'admin_page_settings' ), 6 );
		add_filter( 'backwpup_admin_pages', array( $this, 'admin_page_about' ), 20 );

		//Add Menu
		if ( is_multisite() ) {
			add_action( 'network_admin_menu', array( $this, 'admin_menu' ) );
		} else {
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		}
		//add Plugin links
		add_filter( 'plugin_row_meta', array( $this, 'plugin_links' ), 10, 2 );
		//add more actions
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_css' ) );
		//Save Form posts general
		add_action( 'admin_post_backwpup', array( $this, 'save_post_form' ) );
		//Save Form posts wizard
		add_action( 'admin_post_backwpup_wizard', array( 'BackWPup_Pro_Page_Wizard', 'save_post_form' ) );
		// Save form posts for support
		add_action( 'admin_post_backwpup_support', array( 'BackWPup_Pro_Page_Support', 'save_post_form' ) );
		//Admin Footer Text replacement
		add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 100 );
		add_filter( 'update_footer', array( $this, 'update_footer' ), 100 );
		//User Profile fields
		add_action( 'show_user_profile', array( $this, 'user_profile_fields' ) );
		add_action( 'edit_user_profile', array( $this, 'user_profile_fields' ) );
		add_action( 'profile_update', array( $this, 'save_profile_update' ) );
		// show "phone home" notices only on plugin pages
		add_filter( 'inpsyde-phone-home-show_notice', array( $this, 'hide_phone_home_client_notices' ), 10, 2 );

		new BackWPup_EasyCron();
	}

	/**
	 * @static
	 * @return \BackWPup_Admin
	 */
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

    /**
     * Admin init function
     */
    public static function admin_css()
    {
        $isDebug = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG);

        wp_enqueue_style(
            'backwpup',
            BackWPup::get_plugin_data('URL') . '/assets/css/main.min.css',
            array(),
            ($isDebug ? BackWPup::get_plugin_data('Version') : time()),
            'screen'
        );
    }

	/**
	 * Load for all BackWPup pages
	 */
	public static function init_general() {

		add_thickbox();

		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_register_script(
			'backwpupgeneral',
			BackWPup::get_plugin_data( 'URL' ) . "/assets/js/general{$suffix}.js",
			array( 'jquery' ),
			( $suffix ? BackWPup::get_plugin_data( 'Version' ) : time() ),
			false
		);

		// Register clipboard.js script
		wp_register_script(
			'backwpup_clipboard',
			BackWPup::get_plugin_data( 'URL' ) . '/assets/js/vendor/clipboard.min.js',
			array( 'jquery' ),
			'1.7.1',
			true
		);

		// Add Help.
		BackWPup_Help::help();
	}

	/**
	 * Add Message (across site loadings)
	 *
	 * @param string $message string Message test.
	 * @param bool $error bool ist it a error message.
	 */
	public static function message( $message, $error = false ) {

		if ( empty( $message ) ) {
			return;
		}

		$saved_message = self::get_messages();

		if ( $error ) {
			$saved_message['error'][] = $message;
		} else {
			$saved_message['updated'][] = $message;
		}

		update_site_option( 'backwpup_messages', $saved_message );
	}

	/**
	 * Get all Message that not displayed
	 *
	 * @return array
	 */
	public static function get_messages() {

		return get_site_option( 'backwpup_messages', array() );
	}

	/**
	 * Display Messages
	 *
	 * @param bool $echo
	 *
	 * @return string
	 */
	public static function display_messages( $echo = true ) {

		/**
		 * This hook can be used to display more messages in all BackWPup pages
		 */
		do_action( 'backwpup_admin_messages' );

		$message_updated = '';
		$message_error = '';
		$saved_message = self::get_messages();
		$message_id = ' id="message"';

		if ( empty( $saved_message ) ) {
			return '';
		}

		if ( ! empty( $saved_message['updated'] ) ) {
			foreach ( $saved_message['updated'] as $msg ) {
				$message_updated .= '<p>' . $msg . '</p>';
			}
		}
		if ( ! empty( $saved_message['error'] ) ) {
			foreach ( $saved_message['error'] as $msg ) {
				$message_error .= '<p>' . $msg . '</p>';
			}
		}

		update_site_option( 'backwpup_messages', array() );

		if ( ! empty( $message_updated ) ) {
			$message_updated = '<div' . $message_id . ' class="updated">' . $message_updated . '</div>';
			$message_id = '';
		}
		if ( ! empty( $message_error ) ) {
			$message_error = '<div' . $message_id . ' class="error">' . $message_error . '</div>';
		}

		if ( $echo ) {
			echo $message_updated . $message_error;
		}

		return $message_updated . $message_error;
	}

	/**
	 * Admin init function
	 */
	public function admin_init() {

		if ( ! is_admin() ) {
			return;
		}
		if ( ! defined( 'DOING_AJAX' ) || ( defined( 'DOING_AJAX' ) && ! DOING_AJAX ) ) {
			return;
		}

		$jobtypes = BackWPup::get_job_types();
		$destinations = BackWPup::get_registered_destinations();

		add_action( 'wp_ajax_backwpup_working', array( 'BackWPup_Page_Jobs', 'ajax_working' ) );
		add_action( 'wp_ajax_backwpup_cron_text', array( 'BackWPup_Page_Editjob', 'ajax_cron_text' ) );
		add_action( 'wp_ajax_backwpup_view_log', array( 'BackWPup_Page_Logs', 'ajax_view_log' ) );
		add_action( 'wp_ajax_download_backup_file', array( 'BackWPup_Destination_Downloader', 'download_by_ajax' ) );

		foreach ( $jobtypes as $id => $jobtypeclass ) {
			add_action( 'wp_ajax_backwpup_jobtype_' . strtolower( $id ), array( $jobtypeclass, 'edit_ajax' ) );
		}

		foreach ( $destinations as $id => $dest ) {
			if ( ! empty( $dest['class'] ) ) {
				add_action(
					'wp_ajax_backwpup_dest_' . strtolower( $id ),
					array(
						BackWPup::get_destination( $id ),
						'edit_ajax',
					), 10, 0
				);
			}
		}

		if ( \BackWPup::is_pro() ) {
			$this->admin_init_pro();
		}
	}

	private function admin_init_pro() {

		$ajax_encryption_key_handler = new Settings\AjaxEncryptionKeyHandler(
			new \phpseclib\Crypt\RSA()
		);

		add_action( 'wp_ajax_encrypt_key_handler', array( $ajax_encryption_key_handler, 'handle' ) );
	}

	/**
	 *
	 * Add Links in Plugins Menu to BackWPup
	 *
	 * @param $links
	 * @param $file
	 *
	 * @return array
	 */
	public function plugin_links( $links, $file ) {

		if ( $file == plugin_basename( BackWPup::get_plugin_data( 'MainFile' ) ) ) {
			$links[] = '<a href="' . esc_attr__( 'http://docs.backwpup.com', 'backwpup' ) . '">' . __( 'Documentation',
					'backwpup' ) . '</a>';
		}

		return $links;
	}

	/**
	 * Add menu entries
	 */
	public function admin_menu() {

		add_menu_page( BackWPup::get_plugin_data( 'name' ),
			BackWPup::get_plugin_data( 'name' ),
			'backwpup',
			'backwpup',
			array(
				'BackWPup_Page_BackWPup',
				'page',
			),
			'div' );
		$this->page_hooks['backwpup'] = add_submenu_page( 'backwpup',
			__( 'BackWPup Dashboard', 'backwpup' ),
			__( 'Dashboard', 'backwpup' ),
			'backwpup',
			'backwpup',
			array(
				'BackWPup_Page_BackWPup',
				'page',
			) );
		add_action( 'load-' . $this->page_hooks['backwpup'], array( 'BackWPup_Admin', 'init_general' ) );
		add_action( 'load-' . $this->page_hooks['backwpup'], array( 'BackWPup_Page_BackWPup', 'load' ) );
		add_action( 'admin_print_scripts-' . $this->page_hooks['backwpup'],
			array(
				'BackWPup_Page_BackWPup',
				'admin_print_scripts',
			) );

		//Add pages form plugins
		$this->page_hooks = apply_filters( 'backwpup_admin_pages', $this->page_hooks );

	}

	/**
	 * @param $page_hooks
	 *
	 * @return mixed
	 */
	public function admin_page_jobs( $page_hooks ) {

		$this->page_hooks['backwpupjobs'] = add_submenu_page( 'backwpup',
			__( 'Jobs', 'backwpup' ),
			__( 'Jobs', 'backwpup' ),
			'backwpup_jobs',
			'backwpupjobs',
			array(
				'BackWPup_Page_Jobs',
				'page',
			) );
		add_action( 'load-' . $this->page_hooks['backwpupjobs'], array( 'BackWPup_Admin', 'init_general' ) );
		add_action( 'load-' . $this->page_hooks['backwpupjobs'], array( 'BackWPup_Page_Jobs', 'load' ) );
		add_action( 'admin_print_styles-' . $this->page_hooks['backwpupjobs'],
			array(
				'BackWPup_Page_Jobs',
				'admin_print_styles',
			) );
		add_action( 'admin_print_scripts-' . $this->page_hooks['backwpupjobs'],
			array(
				'BackWPup_Page_Jobs',
				'admin_print_scripts',
			) );

		return $page_hooks;
	}

	/**
	 * @param $page_hooks
	 *
	 * @return mixed
	 */
	public function admin_page_editjob( $page_hooks ) {

		$this->page_hooks['backwpupeditjob'] = add_submenu_page( 'backwpup',
			__( 'Add new job', 'backwpup' ),
			__( 'Add new job', 'backwpup' ),
			'backwpup_jobs_edit',
			'backwpupeditjob',
			array(
				'BackWPup_Page_Editjob',
				'page',
			) );
		add_action( 'load-' . $this->page_hooks['backwpupeditjob'], array( 'BackWPup_Admin', 'init_general' ) );
		add_action( 'load-' . $this->page_hooks['backwpupeditjob'], array( 'BackWPup_Page_Editjob', 'auth' ) );
		add_action( 'admin_print_styles-' . $this->page_hooks['backwpupeditjob'],
			array(
				'BackWPup_Page_Editjob',
				'admin_print_styles',
			) );
		add_action( 'admin_print_scripts-' . $this->page_hooks['backwpupeditjob'],
			array(
				'BackWPup_Page_Editjob',
				'admin_print_scripts',
			) );

		return $page_hooks;
	}

	/**
	 * @param $page_hooks
	 *
	 * @return mixed
	 */
	public function admin_page_logs( $page_hooks ) {

		$this->page_hooks['backwpuplogs'] = add_submenu_page( 'backwpup',
			__( 'Logs', 'backwpup' ),
			__( 'Logs', 'backwpup' ),
			'backwpup_logs',
			'backwpuplogs',
			array(
				'BackWPup_Page_Logs',
				'page',
			) );
		add_action( 'load-' . $this->page_hooks['backwpuplogs'], array( 'BackWPup_Admin', 'init_general' ) );
		add_action( 'load-' . $this->page_hooks['backwpuplogs'], array( 'BackWPup_Page_Logs', 'load' ) );
		add_action( 'admin_print_styles-' . $this->page_hooks['backwpuplogs'],
			array(
				'BackWPup_Page_Logs',
				'admin_print_styles',
			) );
		add_action( 'admin_print_scripts-' . $this->page_hooks['backwpuplogs'],
			array(
				'BackWPup_Page_Logs',
				'admin_print_scripts',
			) );

		return $page_hooks;
	}

	/**
	 * @param $page_hooks
	 *
	 * @return mixed
	 */
	public function admin_page_backups( $page_hooks ) {

		$this->page_hooks['backwpupbackups'] = add_submenu_page( 'backwpup',
			__( 'Backups', 'backwpup' ),
			__( 'Backups', 'backwpup' ),
			'backwpup_backups',
			'backwpupbackups',
			array(
				'BackWPup_Page_Backups',
				'page',
			) );
		add_action( 'load-' . $this->page_hooks['backwpupbackups'], array( 'BackWPup_Admin', 'init_general' ) );
		add_action( 'load-' . $this->page_hooks['backwpupbackups'], array( 'BackWPup_Page_Backups', 'load' ) );
		add_action( 'admin_print_styles-' . $this->page_hooks['backwpupbackups'],
			array(
				'BackWPup_Page_Backups',
				'admin_print_styles',
			) );
		add_action( 'admin_print_scripts-' . $this->page_hooks['backwpupbackups'],
			array(
				'BackWPup_Page_Backups',
				'admin_print_scripts',
			) );

		return $page_hooks;
	}

	/**
	 * @param $page_hooks
	 *
	 * @return mixed
	 */
	public function admin_page_settings( $page_hooks ) {

		$this->page_hooks['backwpupsettings'] = add_submenu_page(
			'backwpup',
			esc_html__( 'Settings', 'backwpup' ),
			esc_html__( 'Settings', 'backwpup' ),
			'backwpup_settings',
			'backwpupsettings',
			array( $this->settings, 'page' )
		);
		add_action( 'load-' . $this->page_hooks['backwpupsettings'], array( 'BackWPup_Admin', 'init_general' ) );
		add_action(
			'admin_print_scripts-' . $this->page_hooks['backwpupsettings'],
			array( $this->settings, 'admin_print_scripts' )
		);

		return $page_hooks;
	}

	/**
	 * @param $page_hooks
	 *
	 * @return mixed
	 */
	public function admin_page_about( $page_hooks ) {

		$this->page_hooks['backwpupabout'] = add_submenu_page( 'backwpup',
			__( 'About', 'backwpup' ),
			__( 'About', 'backwpup' ),
			'backwpup',
			'backwpupabout',
			array(
				'BackWPup_Page_About',
				'page',
			) );
		add_action( 'load-' . $this->page_hooks['backwpupabout'], array( 'BackWPup_Admin', 'init_general' ) );
		add_action( 'admin_print_styles-' . $this->page_hooks['backwpupabout'],
			array(
				'BackWPup_Page_About',
				'admin_print_styles',
			) );
		add_action( 'admin_print_scripts-' . $this->page_hooks['backwpupabout'],
			array(
				'BackWPup_Page_About',
				'admin_print_scripts',
			) );

		return $page_hooks;
	}

	/**
	 * Called on save form. Only POST allowed.
	 */
	public function save_post_form() {

		$allowed_pages = array(
			'backwpupeditjob',
			'backwpupinformation',
			'backwpupsettings',
		);

		if ( ! in_array( $_POST['page'], $allowed_pages, true ) ) {
			wp_die( esc_html__( 'Cheating, huh?', 'backwpup' ) );
		}

		//nonce check
		check_admin_referer( $_POST['page'] . '_page' );

		if ( ! current_user_can( 'backwpup' ) ) {
			wp_die( esc_html__( 'Cheating, huh?', 'backwpup' ) );
		}

		//build query for redirect
		if ( ! isset( $_POST['anchor'] ) ) {
			$_POST['anchor'] = null;
		}
		$query_args = array();
		if ( isset( $_POST['page'] ) ) {
			$query_args['page'] = $_POST['page'];
		}
		if ( isset( $_POST['tab'] ) ) {
			$query_args['tab'] = $_POST['tab'];
		}
		if ( isset( $_POST['tab'] ) && isset( $_POST['nexttab'] ) && $_POST['tab'] !== $_POST['nexttab'] ) {
			$query_args['tab'] = $_POST['nexttab'];
		}

		$jobid = null;
		if ( isset( $_POST['jobid'] ) ) {
			$jobid = (int) $_POST['jobid'];
			$query_args['jobid'] = $jobid;
		}

		// Call method to save data
		if ( $_POST['page'] === 'backwpupeditjob' ) {
			BackWPup_Page_Editjob::save_post_form( $_POST['tab'], $jobid );
		} elseif ( $_POST['page'] === 'backwpupsettings' ) {
			$this->settings->save_post_form();
		}

		//Back to topic
		wp_safe_redirect( add_query_arg( $query_args, network_admin_url( 'admin.php' ) ) . $_POST['anchor'] );
		exit;
	}

	/**
	 * Overrides WordPress text in Footer
	 *
	 * @param $admin_footer_text string
	 *
	 * @return string
	 */
	public function admin_footer_text( $admin_footer_text ) {

		$default_text = $admin_footer_text;
		if ( isset( $_REQUEST['page'] ) && strstr( $_REQUEST['page'], 'backwpup' ) ) {

			$admin_footer_text = sprintf(
				'<a href="http://inpsyde.com" class="inpsyde_logo" title="Inpsyde GmbH">%s <span class="screen-reader-text">Inpsyde GmbH</span></a>',
				'<svg id="inpsyde_logo" data-name="Inpsyde Logo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 226.77 70.7"><defs><style>.cls-1{fill:#9ec75f}.cls-2{fill:#3e3c3d}</style></defs><path class="cls-1" d="M218.35 3.09c-.47.58-1 1.16-1.48 1.78l-1.48 2c-1 1.36-2.09 2.79-3.07 4.35l-1.54 2.34-1.44 2.44c-1 1.64-1.85 3.4-2.74 5.14s-1.62 3.53-2.34 5.3-1.36 3.53-1.91 5.27-1 3.41-1.5 5-.83 3.16-1.17 4.6c-.19.83-.39 1.61-.56 2.36 32.89-9.3 31.35-8.09 21.3-43.05-.65.74-1.32 1.56-2.07 2.43M194.63 34.67c.67-1.64 1.37-3.37 2.2-5.12s1.7-3.55 2.66-5.34 2-3.56 3.11-5.29 2.23-3.45 3.47-5c.6-.8 1.19-1.61 1.79-2.39l1.86-2.24c1.19-1.49 2.46-2.83 3.66-4.09l1.74-1.85 1.71-1.63c.62-.57 1.19-1.12 1.74-1.64-33.71 9.59-34.24 8.1-25.41 38.51.45-1.23.93-2.51 1.47-3.88" transform="translate(0 -.04)"/><path class="cls-2" d="M3.76 36.86H.31a12 12 0 0 1-.23-1.42c0-.5-.08-1-.08-1.42a12 12 0 0 1 .31-2.73h10.6v26.62a21.36 21.36 0 0 1-3.6.31 21.14 21.14 0 0 1-3.55-.31zm-1.08-10a19.52 19.52 0 0 1-.31-3.46 20.18 20.18 0 0 1 .31-3.45c.58-.1 1.21-.18 1.88-.23s1.28-.07 1.82-.07 1.22 0 1.91.07 1.32.13 1.9.23a13.16 13.16 0 0 1 .21 1.73v3.42a13.2 13.2 0 0 1-.21 1.76 14.89 14.89 0 0 1-1.87.2c-.67 0-1.3.05-1.88.05s-1.17 0-1.86-.05a15 15 0 0 1-1.9-.2M18.12 31.29a14.37 14.37 0 0 1 1.44-.23 15.55 15.55 0 0 1 1.6-.08 13.67 13.67 0 0 1 1.54.08c.45.05.91.13 1.39.23a2.93 2.93 0 0 1 .29.75c.08.33.16.67.23 1s.13.71.18 1.06.1.63.13.87a11.84 11.84 0 0 1 1.23-1.54 8.82 8.82 0 0 1 1.68-1.39 9.18 9.18 0 0 1 2.14-1 8.59 8.59 0 0 1 2.62-.38c3 0 5.3.82 6.82 2.47s2.29 4.24 2.29 7.77v17a21.7 21.7 0 0 1-7.31 0v-15a8.69 8.69 0 0 0-.85-4.27 3.1 3.1 0 0 0-3-1.5 6.81 6.81 0 0 0-1.8.26 3.82 3.82 0 0 0-1.67 1 5.44 5.44 0 0 0-1.21 2.09 10.91 10.91 0 0 0-.46 3.5v13.93a21.76 21.76 0 0 1-7.32 0zM48.19 31.24a11.87 11.87 0 0 1 1.42-.24c.46 0 1-.05 1.57-.05a14.9 14.9 0 0 1 2.93.31 3.36 3.36 0 0 1 .29.75c.08.33.17.68.25 1.06s.15.74.21 1.1.09.66.13.91a10.39 10.39 0 0 1 1.13-1.63 7.4 7.4 0 0 1 1.62-1.41 9.18 9.18 0 0 1 2.14-1 8.63 8.63 0 0 1 2.62-.39 10.46 10.46 0 0 1 4.15.83A9.09 9.09 0 0 1 70 34a11.67 11.67 0 0 1 2.24 4.2 19.93 19.93 0 0 1 .8 6 17.22 17.22 0 0 1-1 6 12.23 12.23 0 0 1-2.83 4.51 12.56 12.56 0 0 1-4.53 2.86 17.23 17.23 0 0 1-6.08 1c-.58 0-1.16 0-1.72-.07s-1-.12-1.42-.19v11.8a16.13 16.13 0 0 1-1.88.21q-.9.06-1.77.06c-.59 0-1.18 0-1.78-.06a16.13 16.13 0 0 1-1.88-.21zm7.31 21a11.07 11.07 0 0 0 3.19.41 6 6 0 0 0 4.89-2.06c1.14-1.37 1.7-3.45 1.7-6.23a15.31 15.31 0 0 0-.26-3 6.44 6.44 0 0 0-.87-2.34 4.65 4.65 0 0 0-1.57-1.55 4.58 4.58 0 0 0-2.34-.47 4.49 4.49 0 0 0-2.19.49 4 4 0 0 0-1.47 1.34 5.7 5.7 0 0 0-.82 2 11 11 0 0 0-.26 2.42zM83.92 46.33a10.21 10.21 0 0 1-4.5-2.45A6.6 6.6 0 0 1 77.79 39a7.38 7.38 0 0 1 2.81-6.13 12.07 12.07 0 0 1 7.65-2.22 22.69 22.69 0 0 1 4 .36 26.45 26.45 0 0 1 4 1.09 13.8 13.8 0 0 1-.52 2.78 12.86 12.86 0 0 1-1 2.52 21.15 21.15 0 0 0-2.72-.9 12.47 12.47 0 0 0-3.15-.39 5.62 5.62 0 0 0-2.73.55 1.82 1.82 0 0 0-1 1.72A1.78 1.78 0 0 0 85.8 40a8.37 8.37 0 0 0 2 .87l2.94.88a14.37 14.37 0 0 1 2.6 1 7 7 0 0 1 2 1.47 6.09 6.09 0 0 1 1.29 2.16 9.33 9.33 0 0 1 .46 3.14 8 8 0 0 1-3.12 6.41 11.48 11.48 0 0 1-3.67 1.91 16.14 16.14 0 0 1-4.89.69c-.83 0-1.59 0-2.27-.07s-1.35-.15-2-.26-1.27-.27-1.88-.44a20.54 20.54 0 0 1-2-.67 14.56 14.56 0 0 1 .49-2.8 17.82 17.82 0 0 1 1-2.76 20.59 20.59 0 0 0 3.22 1 14.26 14.26 0 0 0 3.17.34 11.43 11.43 0 0 0 1.57-.13 6.08 6.08 0 0 0 1.57-.46 3.89 3.89 0 0 0 1.21-.88A2 2 0 0 0 90 50a2 2 0 0 0-.75-1.77 7.07 7.07 0 0 0-2.09-1zM99.37 31.29a11.84 11.84 0 0 1 2.09-.28h1.67c.69 0 1.39 0 2.11.05a12.25 12.25 0 0 1 2 .26l5.56 23.22 6.2-23.25a20.07 20.07 0 0 1 3.5-.31h1.65a13.9 13.9 0 0 1 2.16.28l-9.9 32.94a12.88 12.88 0 0 1-1.47 3.24 6.77 6.77 0 0 1-1.93 2 6.85 6.85 0 0 1-2.42 1 13.78 13.78 0 0 1-2.88.29 18.4 18.4 0 0 1-2.6-.18 22.23 22.23 0 0 1-2.29-.5 1.83 1.83 0 0 1 0-.43v-.39a9.32 9.32 0 0 1 .28-2.34 16.74 16.74 0 0 1 .7-2.14 8.21 8.21 0 0 0 1.2.25 9 9 0 0 0 1.55.13 10.14 10.14 0 0 0 1.23-.07 2.93 2.93 0 0 0 1.21-.47 3.88 3.88 0 0 0 1.11-1.21 9.31 9.31 0 0 0 .93-2.31l.87-3.2c-.38 0-.8.06-1.26.08s-.94 0-1.41 0h-1.09a3.68 3.68 0 0 1-.92-.11zM145.55 20.27c.62-.1 1.23-.16 1.83-.2s1.2-.05 1.78-.05 1.18 0 1.8.05 1.24.1 1.86.2V57a38 38 0 0 1-4.92 1.11 41.41 41.41 0 0 1-6.05.38 18.47 18.47 0 0 1-5.23-.74 12.1 12.1 0 0 1-4.4-2.37 11.11 11.11 0 0 1-3-4.2 15.86 15.86 0 0 1-1.11-6.28 16.12 16.12 0 0 1 1-5.54 13.31 13.31 0 0 1 2.73-4.5 12.83 12.83 0 0 1 4.3-3 13.78 13.78 0 0 1 5.62-1.11c.61 0 1.26 0 1.92.08a9.85 9.85 0 0 1 1.93.33zm0 16.53a13.76 13.76 0 0 0-1.67-.33 14.29 14.29 0 0 0-1.62-.08 6 6 0 0 0-3 .7 6.2 6.2 0 0 0-2.09 1.85 7.72 7.72 0 0 0-1.2 2.73 13.56 13.56 0 0 0-.39 3.27 11 11 0 0 0 .51 3.58 6.06 6.06 0 0 0 1.42 2.34 5.31 5.31 0 0 0 2.14 1.26 8.79 8.79 0 0 0 2.67.39 15.4 15.4 0 0 0 1.6-.08 8.29 8.29 0 0 0 1.59-.34zM164.93 46.84a5.65 5.65 0 0 0 2.16 4.49 8.76 8.76 0 0 0 5.15 1.38 20.65 20.65 0 0 0 7.06-1.28 12.22 12.22 0 0 1 1 2.47 13.15 13.15 0 0 1 .46 3.09 25.1 25.1 0 0 1-9.06 1.54 17.52 17.52 0 0 1-6.36-1 11.22 11.22 0 0 1-4.34-2.88 11.47 11.47 0 0 1-2.48-4.36 18.21 18.21 0 0 1-.79-5.51 17.89 17.89 0 0 1 .82-5.5 12.86 12.86 0 0 1 2.45-4.49 11.55 11.55 0 0 1 4-3 13.34 13.34 0 0 1 5.59-1.1 11.74 11.74 0 0 1 4.94 1 10.87 10.87 0 0 1 3.68 2.67 11.31 11.31 0 0 1 2.29 4 15.85 15.85 0 0 1 .78 5c0 .66 0 1.3-.08 1.93s-.12 1.15-.18 1.52zm10.46-5.09a6.49 6.49 0 0 0-1.37-4 4.35 4.35 0 0 0-3.53-1.52 5.08 5.08 0 0 0-3.91 1.44 6.85 6.85 0 0 0-1.6 4.07zM187.25 58.07a20.45 20.45 0 0 1-.3-3.45 21 21 0 0 1 .3-3.45c.58-.1 1.21-.18 1.88-.23s1.28-.08 1.83-.08 1.22 0 1.9.08 1.32.13 1.91.23a14.88 14.88 0 0 1 .2 1.72c0 .57.06 1.15.06 1.73s0 1.12-.06 1.7a14.88 14.88 0 0 1-.2 1.75 17.41 17.41 0 0 1-1.88.21h-3.73a17.16 17.16 0 0 1-1.91-.21" transform="translate(0 -.04)"/></svg>'
			);

			if ( ! class_exists( 'BackWPup_Pro', false ) ) {
				$admin_footer_text .= sprintf( __( '<a class="backwpup-get-pro" href="%s">Get BackWPup Pro now.</a>',
					'backwpup' ),
					__( 'http://backwpup.com', 'backwpup' ) );
			}

			return $admin_footer_text . $default_text;
		}

		return $admin_footer_text;
	}

	/**
	 * Overrides WordPress Version in Footer
	 *
	 * @param $update_footer_text string
	 *
	 * @return string
	 */
	public function update_footer( $update_footer_text ) {

		$default_text = $update_footer_text;

		if ( isset( $_REQUEST['page'] ) && strstr( $_REQUEST['page'], 'backwpup' ) ) {
			$update_footer_text = '<span class="backwpup-update-footer"><a href="' . __( 'http://backwpup.com',
					'backwpup' ) . '">' . BackWPup::get_plugin_data( 'Name' ) . '</a> ' . sprintf( __( 'version %s',
					'backwpup' ),
					BackWPup::get_plugin_data( 'Version' ) ) . '</span>';

			return $update_footer_text . $default_text;
		}

		return $update_footer_text;
	}

	/**
	 *  Add filed for selecting user role in user section
	 *
	 * @param $user WP_User
	 */
	public function user_profile_fields( $user ) {

		global $wp_roles;

		if ( ! is_super_admin() && ! current_user_can( 'backwpup_admin' ) ) {
			return;
		}

		//user is admin and has BackWPup rights
		if ( $user->has_cap( 'administrator' ) && $user->has_cap( 'backwpup_settings' ) ) {
			return;
		}

		//get backwpup roles
		$backwpup_roles = array();
		foreach ( $wp_roles->roles as $role => $role_value ) {
			if ( substr( $role, 0, 8 ) != 'backwpup' ) {
				continue;
			}
			$backwpup_roles[ $role ] = $role_value;
		}

		//only if user has other than backwpup role
		if ( ! empty( $user->roles[0] ) && in_array( $user->roles[0], array_keys( $backwpup_roles ), true ) ) {
			return;
		}

		?>
		<h3><?php echo BackWPup::get_plugin_data( 'name' ); ?></h3>
		<table class="form-table">
			<tr>
				<th>
					<label for="backwpup_role"><?php _e( 'Add BackWPup Role', 'backwpup' ); ?></label>
				</th>
				<td>
					<select name="backwpup_role" id="backwpup_role" style="display:inline-block; float:none;">
						<option
							value=""><?php _e( '&mdash; No additional role for BackWPup &mdash;',
								'backwpup' ); ?></option>
						<?php
						foreach ( $backwpup_roles as $role => $role_value ) {
							echo '<option value="' . $role . '" ' . selected( $user->has_cap( $role ),
									true,
									false ) . '>' . $role_value['name'] . '</option>';
						}
						?>
					</select>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save for user role adding
	 *
	 * @param $user_id int
	 */
	public function save_profile_update( $user_id ) {

		global $wp_roles;

		if ( ! is_super_admin() && ! current_user_can( 'backwpup_admin' ) ) {
			return;
		}

		if ( empty( $user_id ) ) {
			return;
		}

		if ( ! isset( $_POST['backwpup_role'] ) ) {
			return;
		}

		$backwpup_role = esc_attr( $_POST['backwpup_role'] );

		//get BackWPup roles
		$backwpup_roles = array();
		foreach ( array_keys( $wp_roles->roles ) as $role ) {
			if ( ! strstr( $role, 'backwpup_' ) ) {
				continue;
			}
			$backwpup_roles[] = $role;
		}

		//get user for adding/removing role
		$user = new WP_User( $user_id );
		//a admin needs no extra role
		if ( $user->has_cap( 'administrator' ) && $user->has_cap( 'backwpup_settings' ) ) {
			$backwpup_role = '';
		}

		//remove BackWPup role from user if it not the actual
		foreach ( $user->roles as $role ) {
			if ( ! strstr( $role, 'backwpup_' ) ) {
				continue;
			}
			if ( $role !== $backwpup_role ) {
				$user->remove_role( $role );
			} else {
				$backwpup_role = '';
			}
		}

		//add new role to user if it not the actual
		if ( $backwpup_role && in_array( $backwpup_role, $backwpup_roles, true ) ) {
			$user->add_role( $backwpup_role );
		}

		return;
	}

	/**
	 * @param bool $show
	 * @param null|WP_Screen $screen
	 *
	 * @return bool
	 */
	public function hide_phone_home_client_notices( $show = true, $screen = null ) {

		if ( $screen instanceof WP_Screen ) {
			return $screen->id === 'toplevel_page_backwpup' || strpos( $screen->id, 'backwpup' ) === 0;
		}

		return $show;
	}

	private function __clone() {
	}

}
