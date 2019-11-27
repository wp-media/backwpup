<?php
/**
 * Plugin Name: BackWPup 
 * Plugin URI: http://backwpup.com
 * Description: WordPress Backup Plugin
 * Author: Inpsyde GmbH
 * Author URI: http://inpsyde.com
 * Version: 3.7.0
 * Text Domain: backwpup
 * Domain Path: /languages/
 * Network: true
 * License: GPLv2+
 */

if ( ! class_exists( 'BackWPup', false ) ) {
	/**
	 * Main BackWPup Plugin Class
	 */
	final class BackWPup {

		private static $instance = null;

		private static $plugin_data = array();

		private static $destinations = array();

		private static $registered_destinations = array();

		private static $job_types = array();

		private static $wizards = array();

		private static $is_pro = false;

		/**
		 * Set needed filters and actions and load
		 */
		private function __construct() {

			// Nothing else matters if we're not on the main site
			if ( ! is_main_network() && ! is_main_site() ) {
				return;
			}

			require_once __DIR__ . '/inc/functions.php';
			if (file_exists( __DIR__ . '/vendor/autoload.php')) {
                require_once __DIR__ . '/vendor/autoload.php';
            }

            self::$is_pro = file_exists(__DIR__ . '/inc/Pro/class-pro.php');

			// Start upgrade if needed
			if ( get_site_option( 'backwpup_version' ) !== self::get_plugin_data( 'Version' )
			     || ! wp_next_scheduled( 'backwpup_check_cleanup' )
			) {
				BackWPup_Install::activate();
			}

            // Load pro features
            if (self::$is_pro) {
                require __DIR__ . '/inc/Pro/autoupdate.php';
                BackWPup_Pro::get_instance();
            }

			// WP-Cron
			if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
				if ( ! empty( $_GET['backwpup_run'] ) && class_exists( 'BackWPup_Job' ) ) {
					// Early disable caches
					BackWPup_Job::disable_caches();
					// Add action for running jobs in wp-cron.php
					add_action( 'wp_loaded', array( 'BackWPup_Cron', 'cron_active' ), PHP_INT_MAX );
				} else {
					// Add cron actions
					add_action( 'backwpup_cron', array( 'BackWPup_Cron', 'run' ) );
					add_action( 'backwpup_check_cleanup', array( 'BackWPup_Cron', 'check_cleanup' ) );
				}

				// If in cron the rest is not needed
				return;
			}

			// Deactivation hook
			register_deactivation_hook( __FILE__, array( 'BackWPup_Install', 'deactivate' ) );

			// Admin bar
			if ( get_site_option( 'backwpup_cfg_showadminbar' ) ) {
				add_action( 'init', array( 'BackWPup_Adminbar', 'get_instance' ) );
			}

			// Only in backend
			if ( is_admin() && class_exists( 'BackWPup_Admin' ) ) {
				BackWPup_Admin::get_instance();
			}

			// Work with wp-cli
			if ( defined( 'WP_CLI' ) && WP_CLI && method_exists( 'WP_CLI', 'add_command' ) ) {
				WP_CLI::add_command( 'backwpup', 'BackWPup_WP_CLI' );
			}

			if ( ! self::is_pro() ) {
				$promoter_updater = new \Inpsyde\BackWPup\Notice\PromoterUpdater();
				$promoter = new \Inpsyde\BackWPup\Notice\Promoter(
					$promoter_updater,
					new \Inpsyde\BackWPup\Notice\PromoterView()
				);
				$promoter->init();
				add_action( 'upgrader_process_complete', array( $promoter_updater, 'update' ) );
				add_filter(
					'pre_set_site_transient_update_plugins',
					function ( $value ) use ( $promoter_updater ) {

						$promoter_updater->update();

						return $value;
					}
				);

                $isPHCActive = (bool)get_site_option('backwpup_cfg_phone_home_client', true);
                $isPHCActive and $this->home_phone_client_init();
			}
		}

		/**
		 * @return self
		 */
		public static function get_instance() {

			if ( null === self::$instance ) {
				self::$instance = new self;
			}

			return self::$instance;
		}

		/**
		 * @return bool
		 */
		public static function is_pro() {

			return self::$is_pro;
		}

		/**
		 * Prevent Cloning
		 */
		public function __clone() {

			wp_die( 'Cheatin&#8217; huh?' );
		}

		/**
		 * Prevent deserialization
		 */
		public function __wakeup() {

			wp_die( 'Cheatin&#8217; huh?' );
		}

		/**
		 * get information about the Plugin
		 *
		 * @param string $name Name of info to get or NULL to get all
		 *
		 * @return string|array
		 */
		public static function get_plugin_data( $name = null ) {

			if ( $name ) {
				$name = strtolower( trim( $name ) );
			}

			if ( empty( self::$plugin_data ) ) {
				self::$plugin_data = get_file_data(
					__FILE__,
					array(
						'name' => 'Plugin Name',
						'version' => 'Version',
					),
					'plugin'
				);
				self::$plugin_data['name'] = trim( self::$plugin_data['name'] );
				//set some extra vars
				self::$plugin_data['basename'] = plugin_basename( __DIR__ );
				self::$plugin_data['mainfile'] = __FILE__;
				self::$plugin_data['plugindir'] = untrailingslashit( __DIR__ );
				self::$plugin_data['hash'] = get_site_option( 'backwpup_cfg_hash' );
				if ( empty( self::$plugin_data['hash'] ) || strlen( self::$plugin_data['hash'] ) < 6
				     || strlen(
					        self::$plugin_data['hash']
				        ) > 12 ) {
					self::$plugin_data['hash'] = self::get_generated_hash(6);
					update_site_option( 'backwpup_cfg_hash', self::$plugin_data['hash'] );
				}
				if ( defined( 'WP_TEMP_DIR' ) && is_dir( WP_TEMP_DIR ) ) {
					self::$plugin_data['temp'] = str_replace(
						                             '\\',
						                             '/',
						                             get_temp_dir()
					                             ) . 'backwpup-' . self::$plugin_data['hash'] . '/';
				} else {
					$upload_dir = wp_upload_dir();
					self::$plugin_data['temp'] = str_replace(
						                             '\\',
						                             '/',
						                             $upload_dir['basedir']
					                             ) . '/backwpup-' . self::$plugin_data['hash'] . '-temp/';
				}
				self::$plugin_data['running_file'] = self::$plugin_data['temp'] . 'backwpup-working.php';
				self::$plugin_data['url'] = plugins_url( '', __FILE__ );
				self::$plugin_data['cacert'] = apply_filters(
					'backwpup_cacert_bundle',
					ABSPATH . WPINC . '/certificates/ca-bundle.crt'
				);
				//get unmodified WP Versions
				include ABSPATH . WPINC . '/version.php';
				/** @var $wp_version string */
				self::$plugin_data['wp_version'] = $wp_version;
				//Build User Agent
				self::$plugin_data['user-agent'] = self::$plugin_data['name'] . '/' . self::$plugin_data['version'] . '; WordPress/' . self::$plugin_data['wp_version'] . '; ' . home_url();
			}

			if ( ! empty( $name ) ) {
				return self::$plugin_data[ $name ];
			} else {
				return self::$plugin_data;
			}
		}

        /**
         * Generates a random hash
         *
         * @param int $length
         *
         * @return string
         */
		public static function get_generated_hash( $length = 6 ) {

		    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

            $hash = '';
            for ( $i = 0; $i < 254; $i++ ) {
                $hash .= $chars[mt_rand(0, 61)];
            }

            return substr(md5($hash), mt_rand(0, 31 - $length), $length);
        }

		/**
		 * Load Plugin Translation
		 *
		 * @return bool Text domain loaded
		 */
		public static function load_text_domain() {

			if ( is_textdomain_loaded( 'backwpup' ) ) {
				return true;
			}

			return load_plugin_textdomain( 'backwpup', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		}

		/**
		 * Get a array of instances for Backup Destination's
		 *
		 * @param $key string Key of Destination where get class instance from
		 *
		 * @return array BackWPup_Destinations
		 */
		public static function get_destination( $key ) {

			$key = strtoupper( $key );

			if ( isset( self::$destinations[ $key ] ) && is_object( self::$destinations[ $key ] ) ) {
				return self::$destinations[ $key ];
			}

			$reg_dests = self::get_registered_destinations();
			if ( ! empty( $reg_dests[ $key ]['class'] ) ) {
				self::$destinations[ $key ] = new $reg_dests[ $key ]['class'];
			} else {
				return null;
			}

			return self::$destinations[ $key ];
		}

		/**
		 * Get a array of registered Destination's for Backups
		 *
		 * @return array BackWPup_Destinations
		 */
		public static function get_registered_destinations() {

			//only run it one time
			if ( ! empty( self::$registered_destinations ) ) {
				return self::$registered_destinations;
			}

			//add BackWPup Destinations
			// to folder
			self::$registered_destinations['FOLDER'] = array(
				'class' => 'BackWPup_Destination_Folder',
				'info' => array(
					'ID' => 'FOLDER',
					'name' => __( 'Folder', 'backwpup' ),
					'description' => __( 'Backup to Folder', 'backwpup' ),
				),
				'can_sync' => false,
				'needed' => array(
					'php_version' => '',
					'functions' => array(),
					'classes' => array(),
				),
			);
			// backup with mail
			self::$registered_destinations['EMAIL'] = array(
				'class' => 'BackWPup_Destination_Email',
				'info' => array(
					'ID' => 'EMAIL',
					'name' => __( 'Email', 'backwpup' ),
					'description' => __( 'Backup sent via email', 'backwpup' ),
				),
				'can_sync' => false,
				'needed' => array(
					'php_version' => '',
					'functions' => array(),
					'classes' => array(),
				),
			);
			// backup to ftp
			self::$registered_destinations['FTP'] = array(
				'class' => 'BackWPup_Destination_Ftp',
				'info' => array(
					'ID' => 'FTP',
					'name' => __( 'FTP', 'backwpup' ),
					'description' => __( 'Backup to FTP', 'backwpup' ),
				),
				'can_sync' => false,
				'needed' => array(
					'php_version' => '',
					'functions' => array( 'ftp_nb_fput' ),
					'classes' => array(),
				),
			);
			// backup to dropbox
			self::$registered_destinations['DROPBOX'] = array(
				'class' => 'BackWPup_Destination_Dropbox',
				'info' => array(
					'ID' => 'DROPBOX',
					'name' => __( 'Dropbox', 'backwpup' ),
					'description' => __( 'Backup to Dropbox', 'backwpup' ),
				),
				'can_sync' => false,
				'needed' => array(
					'php_version' => '',
					'functions' => array( 'curl_exec' ),
					'classes' => array(),
				),
			);
			// Backup to S3
			self::$registered_destinations['S3'] = array(
				'class' => 'BackWPup_Destination_S3',
				'info' => array(
					'ID' => 'S3',
					'name' => __( 'S3 Service', 'backwpup' ),
					'description' => __( 'Backup to an S3 Service', 'backwpup' ),
				),
				'can_sync' => false,
				'needed' => array(
					'php_version' => '',
					'functions' => array( 'curl_exec' ),
					'classes' => array( 'XMLWriter' ),
				),
			);
			// backup to MS Azure
			self::$registered_destinations['MSAZURE'] = array(
				'class' => 'BackWPup_Destination_MSAzure',
				'info' => array(
					'ID' => 'MSAZURE',
					'name' => __( 'MS Azure', 'backwpup' ),
					'description' => __( 'Backup to Microsoft Azure (Blob)', 'backwpup' ),
				),
				'can_sync' => false,
				'needed' => array(
					'php_version' => '5.6.0',
					'functions' => array(),
					'classes' => array(),
				),
			);
			// backup to Rackspace Cloud
			self::$registered_destinations['RSC'] = array(
				'class' => 'BackWPup_Destination_RSC',
				'info' => array(
					'ID' => 'RSC',
					'name' => __( 'RSC', 'backwpup' ),
					'description' => __( 'Backup to Rackspace Cloud Files', 'backwpup' ),
				),
				'can_sync' => false,
				'needed' => array(
					'php_version' => '',
					'functions' => array( 'curl_exec' ),
					'classes' => array(),
				),
			);
			// backup to Sugarsync
			self::$registered_destinations['SUGARSYNC'] = array(
				'class' => 'BackWPup_Destination_SugarSync',
				'info' => array(
					'ID' => 'SUGARSYNC',
					'name' => __( 'SugarSync', 'backwpup' ),
					'description' => __( 'Backup to SugarSync', 'backwpup' ),
				),
				'can_sync' => false,
				'needed' => array(
					'php_version' => '',
					'functions' => array( 'curl_exec' ),
					'classes' => array(),
				),
			);

			//Hook for adding Destinations like above
			self::$registered_destinations = apply_filters(
				'backwpup_register_destination',
				self::$registered_destinations
			);

			//check BackWPup Destinations
			foreach ( self::$registered_destinations as $dest_key => $dest ) {
				self::$registered_destinations[ $dest_key ]['error'] = '';
				// check PHP Version
				if ( ! empty( $dest['needed']['php_version'] )
				     && version_compare(
					     PHP_VERSION,
					     $dest['needed']['php_version'],
					     '<'
				     ) ) {
					self::$registered_destinations[ $dest_key ]['error'] .= sprintf(
						                                                        __( 'PHP Version %1$s is to low, you need Version %2$s or above.',
							                                                        'backwpup' ),
						                                                        PHP_VERSION,
						                                                        $dest['needed']['php_version']
					                                                        ) . ' ';
					self::$registered_destinations[ $dest_key ]['class'] = null;
				}
				//check functions exists
				if ( ! empty( $dest['needed']['functions'] ) ) {
					foreach ( $dest['needed']['functions'] as $function_need ) {
						if ( ! function_exists( $function_need ) ) {
							self::$registered_destinations[ $dest_key ]['error'] .= sprintf(
								                                                        __( 'Missing function "%s".',
									                                                        'backwpup' ),
								                                                        $function_need
							                                                        ) . ' ';
							self::$registered_destinations[ $dest_key ]['class'] = null;
						}
					}
				}
				//check classes exists
				if ( ! empty( $dest['needed']['classes'] ) ) {
					foreach ( $dest['needed']['classes'] as $class_need ) {
						if ( ! class_exists( $class_need ) ) {
							self::$registered_destinations[ $dest_key ]['error'] .= sprintf(
								                                                        __( 'Missing class "%s".',
									                                                        'backwpup' ),
								                                                        $class_need
							                                                        ) . ' ';
							self::$registered_destinations[ $dest_key ]['class'] = null;
						}
					}
				}
			}

			return self::$registered_destinations;
		}

		/**
		 * Gets a array of instances from Job types
		 *
		 * @return array BackWPup_JobTypes
		 */
		public static function get_job_types() {

			if ( ! empty( self::$job_types ) ) {
				return self::$job_types;
			}

			self::$job_types['DBDUMP'] = new BackWPup_JobType_DBDump;
			self::$job_types['FILE'] = new BackWPup_JobType_File;
			self::$job_types['WPEXP'] = new BackWPup_JobType_WPEXP;
			self::$job_types['WPPLUGIN'] = new BackWPup_JobType_WPPlugin;
			self::$job_types['DBCHECK'] = new BackWPup_JobType_DBCheck;

			self::$job_types = apply_filters( 'backwpup_job_types', self::$job_types );

			//remove types can't load
			foreach ( self::$job_types as $key => $job_type ) {
				if ( empty( $job_type ) || ! is_object( $job_type ) ) {
					unset( self::$job_types[ $key ] );
				}
			}

			return self::$job_types;
		}

		/**
		 * Gets a array of instances from Wizards
		 *
		 * @return array BackWPup_Pro_Wizards
		 */
		public static function get_wizards() {

			if ( ! empty( self::$wizards ) ) {
				return self::$wizards;
			}

			self::$wizards = apply_filters( 'backwpup_pro_wizards', self::$wizards );

			//remove wizards can't load
			foreach ( self::$wizards as $key => $wizard ) {
				if ( empty( $wizard ) || ! is_object( $wizard ) ) {
					unset( self::$wizards[ $key ] );
				}
			}

			return self::$wizards;

		}

		/**
		 * Initialize Home Phone Client
		 *
		 * @return void
		 */
		private function home_phone_client_init() {

			if ( ! class_exists( 'Inpsyde_PhoneHome_FrontController' ) ) {
				return;
			}

			Inpsyde_PhoneHome_FrontController::initialize_for_network(
				'BackWPup',
				__DIR__ . '/assets/templates/phpnotice',
				'backwpup',
				array(
					Inpsyde_PhoneHome_Configuration::ANONYMIZE => true,
					Inpsyde_PhoneHome_Configuration::MINIMUM_CAPABILITY => 'manage_options',
					Inpsyde_PhoneHome_Configuration::COLLECT_PHP => true,
					Inpsyde_PhoneHome_Configuration::COLLECT_WP => true,
					Inpsyde_PhoneHome_Configuration::SERVER_ADDRESS => 'https://backwpup.com/wp-json',
				)
			);
		}
	}

	require_once __DIR__ . '/inc/class-system-requirements.php';
	require_once __DIR__ . '/inc/class-system-tests.php';
	$system_requirements = new BackWPup_System_Requirements();
	$system_tests = new BackWPup_System_Tests( $system_requirements );

	// Don't activate on anything less than PHP 5.3 or WordPress 3.9
	if ( ! $system_tests->is_php_version_compatible() || ! $system_tests->is_wp_version_compatible() ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		deactivate_plugins( __FILE__ );
		die(
		sprintf(
			esc_html__(
				'BackWPup requires PHP version %1$s with spl extension or greater and WordPress %2$s or greater.',
				'backwpup'
			),
			$system_requirements->php_minimum_version(),
			$system_requirements->wp_minimum_version()
		)
		);
	}

	//Start Plugin
	add_action( 'plugins_loaded', array( 'BackWPup', 'get_instance' ), 11 );
}
