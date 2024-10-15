<?php
/*
 * Plugin Name: BackWPup 
 * Plugin URI: https://backwpup.com/
 * Description: WordPress Backup Plugin
 * Author: BackWPup – WordPress Backup & Restore Plugin
 * Author URI: https://backwpup.com
 * Version: 4.1.6
 * Requires at least: 4.9
 * Requires PHP: 7.4
 * Text Domain: backwpup
 * Domain Path: /languages/
 * Network: true
 * License: GPLv2+
 */

use Inpsyde\BackWPup\Pro\License\Api\LicenseActivation;
use Inpsyde\BackWPup\Pro\License\Api\LicenseDeactivation;
use Inpsyde\BackWPup\Pro\License\Api\LicenseStatusRequest;
use Inpsyde\BackWPup\Pro\License\Api\PluginInformation;
use Inpsyde\BackWPup\Pro\License\Api\PluginUpdate;
use Inpsyde\BackWPup\Pro\License\License;
use Inpsyde\BackWPup\Pro\License\LicenseSettingsView;
use Inpsyde\BackWPup\Pro\License\LicenseSettingUpdater;
use Inpsyde\BackWPup\Pro\Settings\EncryptionSettingsView;
use Inpsyde\BackWPup\Pro\Settings\EncryptionSettingUpdater;

if (!class_exists(\BackWPup::class, false)) {
    /**
     * Main BackWPup Plugin Class.
     */
    final class BackWPup
    {
        private static $instance;

        private static $plugin_data = [];

        private static $destinations = [];

        private static $registered_destinations = [];

        private static $job_types = [];

        private static $wizards = [];

        private static $is_pro = false;

        /**
         * Set needed filters and actions and load.
         */
        private function __construct()
        {
            // Nothing else matters if we're not on the main site
            if (!is_main_network() && !is_main_site()) {
                return;
            }

            require_once __DIR__ . '/inc/functions.php';
            if (file_exists(__DIR__ . '/vendor/autoload.php')) {
                require_once __DIR__ . '/vendor/autoload.php';
            }

            self::$is_pro = file_exists(__DIR__ . '/inc/Pro/class-pro.php');

            // Start upgrade if needed
            if (get_site_option('backwpup_version') !== self::get_plugin_data('Version')
                 || !wp_next_scheduled('backwpup_check_cleanup')
            ) {
                BackWPup_Install::activate();
            }

            $pluginData = [
                'version' => BackWPup::get_plugin_data('version'),
                'pluginName' => 'backwpup-pro/backwpup.php',
                'slug' => 'backwpup',
            ];

			// Register the third party services.
			BackWPup_ThirdParties::register();

            // Load pro features
            if (self::$is_pro) {
                $license = new License(
                    get_site_option('license_product_id', ''),
                    get_site_option('license_api_key', ''),
                    get_site_option('license_instance_key') ?: wp_generate_password(12, false),
                    get_site_option('license_status', 'inactive')
                );

                $pluginUpdate = new PluginUpdate($license, $pluginData);
                $pluginInformation = new PluginInformation($license, $pluginData);

                $pro = new BackWPup_Pro($pluginUpdate, $pluginInformation);
                $pro->init();
            }

            // WP-Cron
            if (defined('DOING_CRON') && DOING_CRON) {
                if (!empty($_GET['backwpup_run']) && class_exists(\BackWPup_Job::class)) {
                    // Early disable caches
                    BackWPup_Job::disable_caches();
                    // Add action for running jobs in wp-cron.php
                    add_action('wp_loaded', [\BackWPup_Cron::class, 'cron_active'], PHP_INT_MAX);
                } else {
                    // Add cron actions
                    add_action('backwpup_cron', [\BackWPup_Cron::class, 'run']);
                    add_action('backwpup_check_cleanup', [\BackWPup_Cron::class, 'check_cleanup']);
                }

                // If in cron the rest is not needed
                return;
            }

            // Deactivation hook
            register_deactivation_hook(__FILE__, [\BackWPup_Install::class, 'deactivate']);

            // Only in backend
            if (is_admin() && class_exists(\BackWPup_Admin::class)) {
                $settings_views = [];
                $settings_updaters = [];

                if (\BackWPup::is_pro()) {
                    $activate = new LicenseActivation($pluginData);
                    $deactivate = new LicenseDeactivation($pluginData);
                    $status = new LicenseStatusRequest();

                    $settings_views = array_merge(
                        $settings_views,
                        [
                            new EncryptionSettingsView(),
                            new LicenseSettingsView(
                                $activate,
                                $deactivate,
                                $status
                            ),
                        ]
                    );
                    $settings_updaters = array_merge(
                        $settings_updaters,
                        [
                            new EncryptionSettingUpdater(),
                            new LicenseSettingUpdater(
                                $activate,
                                $deactivate,
                                $status
                            ),
                        ]
                    );
                }

                $settings = new BackWPup_Page_Settings(
                    $settings_views,
                    $settings_updaters
                );

                $admin = new BackWPup_Admin($settings);
                $admin->init();

				/**
				 * Filter whether BackWPup will show the plugins in the admin bar or not.
				 *
				 * @param bool $is_in_admin_bar Whether the admin link will be shown in the admin bar or not.
				 */
				$is_in_admin_bar = (bool) apply_filters( 'backwpup_is_in_admin_bar', (bool) get_site_option( 'backwpup_cfg_showadminbar' ) );

				if ( true === $is_in_admin_bar ) {
					$admin_bar = new BackWPup_Adminbar( $admin );
					add_action( 'init', [ $admin_bar, 'init' ] );
				}

                new BackWPup_EasyCron();
            }

            // Work with wp-cli
            if (defined(\WP_CLI::class) && WP_CLI && method_exists(\WP_CLI::class, 'add_command')) {
                WP_CLI::add_command('backwpup', \BackWPup_WP_CLI::class);
			}
		}

        /**
         * @return self
         */
        public static function get_instance()
        {
            if (null === self::$instance) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * @return bool
         */
        public static function is_pro()
        {
            return self::$is_pro;
        }

        /**
         * Prevent Cloning.
         */
        public function __clone()
        {
            wp_die('Cheatin&#8217; huh?');
        }

        /**
         * Prevent deserialization.
         */
        public function __wakeup()
        {
            wp_die('Cheatin&#8217; huh?');
        }

        /**
         * get information about the Plugin.
         *
         * @param string $name Name of info to get or NULL to get all
         *
         * @return string|array
         */
        public static function get_plugin_data($name = null)
        {
            if ($name) {
                $name = strtolower(trim($name));
            }

            if (empty(self::$plugin_data)) {
                self::$plugin_data = get_file_data(
                    __FILE__,
                    [
                        'name' => 'Plugin Name',
                        'version' => 'Version',
                    ],
                    'plugin'
				);
				self::$plugin_data['name'] = trim( self::$plugin_data['name'] );
				// set some extra vars.
				self::$plugin_data['basename']          = plugin_basename( __DIR__ );
				self::$plugin_data['mainfile']          = __FILE__;
				self::$plugin_data['plugindir']         = untrailingslashit( __DIR__ );
				self::$plugin_data['pluginincdir']      = untrailingslashit( self::$plugin_data['plugindir'] . '/inc' );
				self::$plugin_data['plugin3rdpartydir'] = untrailingslashit( self::$plugin_data['pluginincdir'] . '/ThirdParty' );
				self::$plugin_data['hash']              = get_site_option( 'backwpup_cfg_hash' );
				if ( empty( self::$plugin_data['hash'] ) || strlen( self::$plugin_data['hash'] ) < 6
					|| strlen(
						self::$plugin_data['hash']
					) > 12 ) {
					self::$plugin_data['hash'] = self::get_generated_hash( 6 );
					update_site_option( 'backwpup_cfg_hash', self::$plugin_data['hash'] );
				}
                if (defined('WP_TEMP_DIR') && is_dir(WP_TEMP_DIR)) {
                    self::$plugin_data['temp'] = str_replace(
                        '\\',
                        '/',
                        get_temp_dir()
                    ) . 'backwpup/' . self::$plugin_data['hash'] . '/';
                } else {
                    $upload_dir = wp_upload_dir();
                    self::$plugin_data['temp'] = str_replace(
                        '\\',
                        '/',
                        $upload_dir['basedir']
                    ) . '/backwpup/' . self::$plugin_data['hash'] . '/temp/';
                }
                self::$plugin_data['running_file'] = self::$plugin_data['temp'] . 'backwpup-working.php';
                self::$plugin_data['url'] = plugins_url('', __FILE__);
                self::$plugin_data['cacert'] = apply_filters(
                    'backwpup_cacert_bundle',
                    ABSPATH . WPINC . '/certificates/ca-bundle.crt'
                );
                //get unmodified WP Versions
                include ABSPATH . WPINC . '/version.php';
                /** @var string $wp_version */
                self::$plugin_data['wp_version'] = $wp_version;
                //Build User Agent
				self::$plugin_data['user-agent'] = self::$plugin_data['name'] . '/' . self::$plugin_data['version'] . '; WordPress/' . self::$plugin_data['wp_version'] . '; ' . home_url();

				$activation_time = get_site_option( 'backwpup_activation_time' );
				if ( ! $activation_time ) {
					update_site_option( 'backwpup_activation_time', time() );
				}
				self::$plugin_data['activation_time'] = $activation_time;
			}

            if (!empty($name)) {
                return self::$plugin_data[$name];
            }

            return self::$plugin_data;
        }

        /**
         * Generates a random hash.
         *
         * @param int $length
         *
         * @return string
         */
        public static function get_generated_hash($length = 6)
        {
            $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

            $hash = '';

            for ($i = 0; $i < 254; ++$i) {
                $hash .= $chars[random_int(0, 61)];
            }

            return substr(md5($hash), random_int(0, 31 - $length), $length);
        }

        /**
         * Load Plugin Translation.
         *
         * @return bool Text domain loaded
         */
        public static function load_text_domain()
        {
            if (is_textdomain_loaded('backwpup')) {
                return true;
            }

            return load_plugin_textdomain('backwpup', false, dirname(plugin_basename(__FILE__)) . '/languages');
        }

        /**
         * Get a array of instances for Backup Destination's.
         *
         * @param $key string Key of Destination where get class instance from
         *
         * @return array BackWPup_Destinations
         */
        public static function get_destination($key)
        {
            $key = strtoupper($key);

            if (isset(self::$destinations[$key]) && is_object(self::$destinations[$key])) {
                return self::$destinations[$key];
            }

            $reg_dests = self::get_registered_destinations();
            if (!empty($reg_dests[$key]['class'])) {
                self::$destinations[$key] = new $reg_dests[$key]['class']();
            } else {
                return null;
            }

            return self::$destinations[$key];
        }

        /**
         * Get a array of registered Destination's for Backups.
         *
         * @return array BackWPup_Destinations
         */
        public static function get_registered_destinations()
        {
            //only run it one time
            if (!empty(self::$registered_destinations)) {
                return self::$registered_destinations;
            }

            //add BackWPup Destinations
            // to folder
            self::$registered_destinations['FOLDER'] = [
                'class' => \BackWPup_Destination_Folder::class,
                'info' => [
                    'ID' => 'FOLDER',
                    'name' => __('Folder', 'backwpup'),
                    'description' => __('Backup to Folder', 'backwpup'),
                ],
                'can_sync' => false,
                'needed' => [
                    'php_version' => '',
                    'functions' => [],
                    'classes' => [],
                ],
            ];
            // backup with mail
            self::$registered_destinations['EMAIL'] = [
                'class' => \BackWPup_Destination_Email::class,
                'info' => [
                    'ID' => 'EMAIL',
                    'name' => __('Email', 'backwpup'),
                    'description' => __('Backup sent via email', 'backwpup'),
                ],
                'can_sync' => false,
                'needed' => [
                    'php_version' => '',
                    'functions' => [],
                    'classes' => [],
                ],
            ];
            // backup to ftp
            self::$registered_destinations['FTP'] = [
                'class' => \BackWPup_Destination_Ftp::class,
                'info' => [
                    'ID' => 'FTP',
                    'name' => __('FTP', 'backwpup'),
                    'description' => __('Backup to FTP', 'backwpup'),
                ],
                'can_sync' => false,
                'needed' => [
                    'php_version' => '',
                    'functions' => ['ftp_nb_fput'],
                    'classes' => [],
                ],
            ];
            // backup to dropbox
            self::$registered_destinations['DROPBOX'] = [
                'class' => \BackWPup_Destination_Dropbox::class,
                'info' => [
                    'ID' => 'DROPBOX',
                    'name' => __('Dropbox', 'backwpup'),
                    'description' => __('Backup to Dropbox', 'backwpup'),
                ],
                'can_sync' => false,
                'needed' => [
                    'php_version' => '',
                    'functions' => ['curl_exec'],
                    'classes' => [],
                ],
            ];
            // Backup to S3
            self::$registered_destinations['S3'] = [
                'class' => \BackWPup_Destination_S3::class,
                'info' => [
                    'ID' => 'S3',
                    'name' => __('S3 Service', 'backwpup'),
                    'description' => __('Backup to an S3 Service', 'backwpup'),
                ],
                'can_sync' => false,
                'needed' => [
                    'php_version' => '',
                    'functions' => ['curl_exec'],
                    'classes' => [\XMLWriter::class],
                ],
            ];
            // backup to MS Azure
            self::$registered_destinations['MSAZURE'] = [
                'class' => \BackWPup_Destination_MSAzure::class,
                'info' => [
                    'ID' => 'MSAZURE',
                    'name' => __('MS Azure', 'backwpup'),
                    'description' => __('Backup to Microsoft Azure (Blob)', 'backwpup'),
                ],
                'can_sync' => false,
                'needed' => [
                    'php_version' => '5.6.0',
                    'functions' => [],
                    'classes' => [],
                ],
            ];
            // backup to Rackspace Cloud
            self::$registered_destinations['RSC'] = [
                'class' => \BackWPup_Destination_RSC::class,
                'info' => [
                    'ID' => 'RSC',
                    'name' => __('RSC', 'backwpup'),
                    'description' => __('Backup to Rackspace Cloud Files', 'backwpup'),
                ],
                'can_sync' => false,
                'needed' => [
                    'php_version' => '',
                    'functions' => ['curl_exec'],
                    'classes' => [],
                ],
            ];
            // backup to Sugarsync
            self::$registered_destinations['SUGARSYNC'] = [
                'class' => \BackWPup_Destination_SugarSync::class,
                'info' => [
                    'ID' => 'SUGARSYNC',
                    'name' => __('SugarSync', 'backwpup'),
                    'description' => __('Backup to SugarSync', 'backwpup'),
                ],
                'can_sync' => false,
                'needed' => [
                    'php_version' => '',
                    'functions' => ['curl_exec'],
                    'classes' => [],
                ],
            ];

            //Hook for adding Destinations like above
            self::$registered_destinations = apply_filters(
                'backwpup_register_destination',
                self::$registered_destinations
            );

            //check BackWPup Destinations
            foreach (self::$registered_destinations as $dest_key => $dest) {
                self::$registered_destinations[$dest_key]['error'] = '';
                // check PHP Version
                if (!empty($dest['needed']['php_version'])
                     && version_compare(
                         PHP_VERSION,
                         $dest['needed']['php_version'],
                         '<'
                     )) {
                    self::$registered_destinations[$dest_key]['error'] .= sprintf(
                        __(
                            'PHP Version %1$s is to low, you need Version %2$s or above.',
                            'backwpup'
                        ),
                        PHP_VERSION,
                        $dest['needed']['php_version']
                    ) . ' ';
                    self::$registered_destinations[$dest_key]['class'] = null;
                }
                //check functions exists
                if (!empty($dest['needed']['functions'])) {
                    foreach ($dest['needed']['functions'] as $function_need) {
                        if (!function_exists($function_need)) {
                            self::$registered_destinations[$dest_key]['error'] .= sprintf(
                                __(
                                    'Missing function "%s".',
                                    'backwpup'
                                ),
                                $function_need
                            ) . ' ';
                            self::$registered_destinations[$dest_key]['class'] = null;
                        }
                    }
                }
                //check classes exists
                if (!empty($dest['needed']['classes'])) {
                    foreach ($dest['needed']['classes'] as $class_need) {
                        if (!class_exists($class_need)) {
                            self::$registered_destinations[$dest_key]['error'] .= sprintf(
                                __(
                                    'Missing class "%s".',
                                    'backwpup'
                                ),
                                $class_need
                            ) . ' ';
                            self::$registered_destinations[$dest_key]['class'] = null;
                        }
                    }
                }
            }

            return self::$registered_destinations;
        }

        /**
         * Gets a array of instances from Job types.
         *
         * @return array BackWPup_JobTypes
         */
        public static function get_job_types()
        {
            if (!empty(self::$job_types)) {
                return self::$job_types;
            }

            self::$job_types['DBDUMP'] = new BackWPup_JobType_DBDump();
            self::$job_types['FILE'] = new BackWPup_JobType_File();
            self::$job_types['WPEXP'] = new BackWPup_JobType_WPEXP();
            self::$job_types['WPPLUGIN'] = new BackWPup_JobType_WPPlugin();
            self::$job_types['DBCHECK'] = new BackWPup_JobType_DBCheck();

            self::$job_types = apply_filters('backwpup_job_types', self::$job_types);

            //remove types can't load
            foreach (self::$job_types as $key => $job_type) {
                if (empty($job_type) || !is_object($job_type)) {
                    unset(self::$job_types[$key]);
                }
            }

            return self::$job_types;
        }

        /**
         * Gets a array of instances from Wizards.
         *
         * @return array BackWPup_Pro_Wizards
         */
        public static function get_wizards()
        {
            if (!empty(self::$wizards)) {
                return self::$wizards;
            }

            self::$wizards = apply_filters('backwpup_pro_wizards', self::$wizards);

            //remove wizards can't load
            foreach (self::$wizards as $key => $wizard) {
                if (empty($wizard) || !is_object($wizard)) {
                    unset(self::$wizards[$key]);
                }
            }

            return self::$wizards;
        }
    }

    require_once __DIR__ . '/inc/class-system-requirements.php';

    require_once __DIR__ . '/inc/class-system-tests.php';
    $system_requirements = new BackWPup_System_Requirements();
    $system_tests = new BackWPup_System_Tests($system_requirements);

	// Don't activate on anything less than PHP 7.4 or WordPress 4.9.
	if ( ! $system_tests->is_php_version_compatible() || ! $system_tests->is_wp_version_compatible() ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
        deactivate_plugins(__FILE__);

        exit(
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
    add_action('plugins_loaded', [\BackWPup::class, 'get_instance'], 11);
}
