<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\License;

use WPMedia\BackWPup\Dependencies\League\Container\ServiceProvider\AbstractServiceProvider;
use WPMedia\BackWPup\License\API\Rest as LicenseApiRest;
use WPMedia\BackWPup\License\API\Subscriber as LicenseApiSubscriber;
use WPMedia\BackWPup\Adapters\BackWPupAdapter as BackWPupAdapterInterface;
use Inpsyde\BackWPup\Pro\License\Api\LicenseActivation;
use Inpsyde\BackWPup\Pro\License\Api\LicenseDeactivation;
use Inpsyde\BackWPup\Pro\License\Api\LicenseStatusRequest;
use Inpsyde\BackWPup\Pro\License\LicenseSettingUpdater;

/**
 * Service provider for Storage providers
 */
class ServiceProvider extends AbstractServiceProvider {
	/**
	 * Service provided by this provider
	 *
	 * @var array
	 */
	protected $provides = [
		'license_api_subscriber',
		'license_api_rest',
		'license_activation',
		'license_deactivation',
		'license_status_request',
		'license_setting_updater',
	];

	/**
	 * Subscribers provided by this provider
	 *
	 * @var array
	 */
	public $subscribers = [
		'license_api_subscriber',
	];

	/**
	 * Check if the service provider provides a specific service.
	 *
	 * @param string $id The id of the service.
	 *
	 * @return bool
	 */
	public function provides( string $id ): bool {
		return in_array( $id, $this->provides, true );
	}

	/**
	 * Registers items with the container
	 *
	 * @return void
	 */
	public function register(): void {
		$this->getContainer()->addShared( 'license_activation', LicenseActivation::class )
			->addArgument( $this->getContainer()->get( 'backwpup_adapter' )->get_plugin_data() );
		$this->getContainer()->addShared( 'license_deactivation', LicenseDeactivation::class )
			->addArgument( $this->getContainer()->get( 'backwpup_adapter' )->get_plugin_data() );
		$this->getContainer()->addShared( 'license_status_request', LicenseStatusRequest::class );
		$this->getContainer()->addShared( 'license_setting_updater', LicenseSettingUpdater::class )
			->addArguments(
				[
					'license_activation',
					'license_deactivation',
					'license_status_request',
				]
				);

		$this->getContainer()->addShared( 'license_api_rest', LicenseApiRest::class )
			->addArguments(
				[
					'backwpup_adapter',
					'license_setting_updater',
				]
				);

		$this->getContainer()->addShared( 'license_api_subscriber', LicenseApiSubscriber::class )
			->addArgument( 'license_api_rest' );
	}

	/**
	 * Returns the subscribers array
	 *
	 * @return array
	 */
	public function get_subscribers() {
		return $this->subscribers;
	}
}
