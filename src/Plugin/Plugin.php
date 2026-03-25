<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Plugin;

use BackWPup;
use BackWPup_Admin;
use BackWPup_Adminbar;
use BackWPup_Cron;
use BackWPup_Install;
use BackWPup_Job;
use BackWPup_Page_Settings;
use BackWPup_Pro;
use BackWPup_ThirdParties;
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
use WPMedia\BackWPup\Admin\Options\Options;
use WPMedia\BackWPup\Dependencies\League\Container\Argument\Literal\StringArgument;
use WPMedia\BackWPup\Dependencies\League\Container\Container;
use WPMedia\BackWPup\Dependencies\League\Container\ServiceProvider\ServiceProviderInterface;
use WPMedia\BackWPup\EventManagement\EventManager;
use WPMedia\BackWPup\EventManagement\SubscriberInterface;
use WPMedia\BackWPup\License\Infrastructure\WpOptionsLicenseRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Plugin {
	/**
	 * Container instance.
	 *
	 * @var Container
	 */
	private $container;

	/**
	 * Is the plugin loaded
	 *
	 * @var boolean
	 */
	private $loaded = false;

	/**
	 * Plugin file path.
	 *
	 * @var string
	 */
	private $plugin_path;

	/**
	 * Job IDs.
	 *
	 * These constants are used to identify the wp_options storing the different types of backup jobs.
	 */
	public const FILES_JOB_ID    = 'backwpup_backup_files_job_id';
	public const DATABASE_JOB_ID = 'backwpup_backup_database_job_id';
	public const FIRST_JOB_ID    = 'backwpup_first_backup_job_id';

	/**
	 * Instantiate the class.
	 *
	 * @param Container $container Instance of the container.
	 * @param string    $plugin_path Path to the plugin file.
	 */
	public function __construct( Container $container, string $plugin_path ) {
		$this->container   = $container;
		$this->plugin_path = $plugin_path;

		add_filter( 'backwpup_container', [ $this, 'get_container' ] );
	}

	/**
	 * Returns the container instance.
	 *
	 * @return Container
	 */
	public function get_container() {
		return $this->container;
	}

	/**
	 * Checks if the plugin is loaded
	 *
	 * @return boolean
	 */
	private function is_loaded(): bool {
		return $this->loaded;
	}

	/**
	 * Plugin init.
	 *
	 * @return void
	 */
	public function init(): void {
		if ( $this->is_loaded() ) {
			return;
		}

		// Nothing else matters if we're not on the main site.
		if ( ! is_main_network() && ! is_main_site() ) {
			return;
		}

		BackWPup::set_is_pro( file_exists( dirname( BACKWPUP_PLUGIN_FILE ) . '/inc/Pro/class-pro.php' ) );

		// Start upgrade if needed.
		if (
			get_site_option( 'backwpup_version' ) !== BackWPup::get_plugin_data( 'Version' )
			||
			! wp_next_scheduled( 'backwpup_check_cleanup' )
		) {
			add_action( 'init', [ BackWPup_Install::class, 'activate' ], 0 );
		}

		$plugin_data = [
			'version'    => BackWPup::get_plugin_data( 'version' ),
			'pluginName' => 'backwpup-pro/backwpup.php',
			'slug'       => 'backwpup',
		];

		// Register the third party services.
		BackWPup_ThirdParties::register();

		$this->container->addShared(
			'event_manager',
			function () {
				return new EventManager();
			}
		);

		$plugin_path = dirname( BACKWPUP_PLUGIN_FILE ) . '/views';
		$this->container->add( 'template_path', new StringArgument( $plugin_path ) );

		$this->container->add( 'options_api', Options::class )
			->addArgument( new StringArgument( 'backwpup_' ) );

		// Load service providers.
		$providers = require dirname( BACKWPUP_PLUGIN_FILE ) . '/config/providers.php';

		foreach ( $providers as $service_provider ) {
			$provider_instance = new $service_provider();
			$this->container->addServiceProvider( $provider_instance );

			// Load each service provider's subscribers if found.
			$this->load_subscribers( $provider_instance );
		}

		// Load pro features.
		if ( BackWPup::is_pro() ) {
			/**
			 * WpOptionsLicenseRepository instance.
			 *
			 * @var WpOptionsLicenseRepository $license_repository
			 */
			$license_repository = $this->container->get( 'license_repository' );
			$instance_key       = $license_repository->get_instance_key();
			$instance_key       = '' !== $instance_key ? $instance_key : wp_generate_password( 12, false );

			$license = new License(
				$license_repository->get_product_id(),
				$license_repository->get_api_key(),
				$instance_key,
				$license_repository->get_status()
			);

			$plugin_update      = new PluginUpdate( $license, $plugin_data );
			$plugin_information = new PluginInformation( $license, $plugin_data );

			$pro = new BackWPup_Pro( $plugin_update, $plugin_information );
			$pro->init();
		}

		// Only in backend.
		$this->load_admin_backend( $plugin_data );

		// WP-Cron.
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			if ( ! empty( $_GET['backwpup_run'] ) && class_exists( BackWPup_Job::class ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				// Early disable caches.
				BackWPup_Job::disable_caches();
				// Add action for running jobs in wp-cron.php.
				add_action( 'wp_loaded', [ BackWPup_Cron::class, 'cron_active' ], PHP_INT_MAX );
			} else {
				// Add cron actions.
				add_action( 'backwpup_cron', [ BackWPup_Cron::class, 'run' ] );
				add_action( 'backwpup_check_cleanup', [ BackWPup_Cron::class, 'check_cleanup' ] );
			}

			// If in cron the rest is not needed.
			return;
		}

		$this->loaded = true;
	}

	/**
	 * Load admin related
	 *
	 * @param array $plugin_data Plugin data.
	 *
	 * @return void
	 */
	private function load_admin_backend( array $plugin_data ): void {
		// Bail early.
		if ( ! is_admin() && class_exists( BackWPup_Admin::class ) ) {
			return;
		}

		$settings_views     = [];
		$settings_updaters  = [];
		$license_repository = null;

		if ( BackWPup::is_pro() ) {
			$activate        = new LicenseActivation( $plugin_data );
			$deactivate      = new LicenseDeactivation( $plugin_data );
			$status          = new LicenseStatusRequest();
			$license_manager = $this->container->get( 'license_manager' );

			$settings_views     = array_merge(
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
			$settings_updaters  = array_merge(
				$settings_updaters,
				[
					new EncryptionSettingUpdater(),
					new LicenseSettingUpdater(
						$activate,
						$deactivate,
						$status,
						$license_manager
					),
				]
			);
			$license_repository = $this->container->get( 'license_repository' );
		}

		$settings = new BackWPup_Page_Settings(
			$settings_views,
			$settings_updaters,
			$license_repository
		);

		$admin = new BackWPup_Admin( $settings );
		$admin->init();

		/**
		 * Filter whether BackWPup will show the plugins in the admin bar or not.
		 *
		 * @param bool $is_in_admin_bar Whether the admin link will be shown in the admin bar or not.
		 */
		$is_in_admin_bar = wpm_apply_filters_typed( 'boolean', 'backwpup_is_in_admin_bar', (bool) get_site_option( 'backwpup_cfg_showadminbar' ) );

		if ( true === $is_in_admin_bar ) {
			$admin_bar = new BackWPup_Adminbar( $admin );
			add_action( 'init', [ $admin_bar, 'init' ] );
		}
	}

	/**
	 * Load list of event subscribers from service provider.
	 *
	 * @param ServiceProviderInterface $service_provider Instance of service provider.
	 *
	 * @return void
	 */
	private function load_subscribers( ServiceProviderInterface $service_provider ): void {
		if ( empty( $service_provider->get_subscribers() ) ) {
			return;
		}

		foreach ( $service_provider->get_subscribers() as $subscriber ) {
			$subscriber_object = $this->container->get( $subscriber );

			if ( $subscriber_object instanceof SubscriberInterface ) {
				$this->container->get( 'event_manager' )->add_subscriber( $subscriber_object );
			}
		}
	}
}
