<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\StorageProviders;

use WPMedia\BackWPup\Dependencies\League\Container\ServiceProvider\AbstractServiceProvider;

use WPMedia\BackWPup\StorageProviders\Subscriber as StorageProviderSubscriber;
use WPMedia\BackWPup\StorageProviders\Frontend\API\Subscriber as StorageProviderFrontendApiSubscriber;
use WPMedia\BackWPup\StorageProviders\Frontend\API\Rest as StorageProviderFrontendApiRest;
use WPMedia\BackWPup\StorageProviders\API\Rest as StorageProviderApiRest;
use WPMedia\BackWPup\StorageProviders\API\Subscriber as StorageProviderApiSubscriber;
use WPMedia\BackWPup\StorageProviders\Dropbox\DropboxProvider;
use WPMedia\BackWPup\StorageProviders\GDrive\GDriveProvider;
use WPMedia\BackWPup\StorageProviders\OneDrive\OneDriveProvider;
use WPMedia\BackWPup\StorageProviders\SugarSync\SugarSyncProvider;
use WPMedia\BackWPup\StorageProviders\CloudProviderManager;

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
		StorageProviderSubscriber::class,
		'storage_providers_frontend_api_subscriber',
		'storage_providers_frontend_api_rest',
		'storage_providers_api_rest',
		'storage_providers_api_subscriber',
		'dropbox_provider',
		'gdrive_provider',
		'onedrive_provider',
		'sugarsync_provider',
		'cloud_provider_manager',
	];

	/**
	 * Subscribers provided by this provider
	 *
	 * @var array
	 */
	public $subscribers = [
		StorageProviderSubscriber::class,
		'storage_providers_frontend_api_subscriber',
		'storage_providers_api_subscriber',
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
		$this->getContainer()->addShared( Subscriber::class );

		$this->getContainer()->addShared( 'dropbox_provider', DropboxProvider::class )
			->addArguments(
				[
					$this->getContainer()->get( 'option_adapter' ),
					$this->getContainer()->get( 'backwpup_helpers_adapter' ),
				]
				);
		$this->getContainer()->addShared( 'gdrive_provider', GDriveProvider::class )
			->addArguments(
				[
					$this->getContainer()->get( 'option_adapter' ),
					$this->getContainer()->get( 'backwpup_helpers_adapter' ),
					$this->getContainer()->get( 'encryption_adapter' ),
				]
				);
		$this->getContainer()->addShared( 'onedrive_provider', OneDriveProvider::class )
			->addArguments(
				[
					$this->getContainer()->get( 'option_adapter' ),
					$this->getContainer()->get( 'backwpup_helpers_adapter' ),
				]
				);
		$this->getContainer()->addShared( 'sugarsync_provider', SugarSyncProvider::class )
			->addArguments(
				[
					$this->getContainer()->get( 'option_adapter' ),
					$this->getContainer()->get( 'backwpup_helpers_adapter' ),
				]
				);

		$providers = [
			$this->getContainer()->get( 'dropbox_provider' ),
			$this->getContainer()->get( 'gdrive_provider' ),
			$this->getContainer()->get( 'onedrive_provider' ),
			$this->getContainer()->get( 'sugarsync_provider' ),
		];

		$this->getContainer()->addShared( 'cloud_provider_manager', CloudProviderManager::class )
			->addArguments( [ $providers ] );

		$this->getContainer()->addShared( 'storage_providers_frontend_api_rest', StorageProviderFrontendApiRest::class )
			->addArguments(
				[
					$this->getContainer()->get( 'backwpup_helpers_adapter' ),
					$this->getContainer()->get( 'option_adapter' ),
				]
				);
		$this->getContainer()->addShared( 'storage_providers_frontend_api_subscriber', StorageProviderFrontendApiSubscriber::class )
			->addArguments(
				[
					$this->getContainer()->get( 'storage_providers_frontend_api_rest' ),
				]
				);

		$this->getContainer()->add( 'storage_providers_api_rest', StorageProviderApiRest::class )
			->addArguments(
				[
					$this->getContainer()->get( 'backwpup_helpers_adapter' ),
					$this->getContainer()->get( 'backwpup_adapter' ),
					$this->getContainer()->get( 'option_adapter' ),
					$this->getContainer()->get( 'cloud_provider_manager' ),
				]
				);
		$this->getContainer()->addShared( 'storage_providers_api_subscriber', StorageProviderApiSubscriber::class )
			->addArguments(
				[
					$this->getContainer()->get( 'storage_providers_api_rest' ),
				]
				);
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
