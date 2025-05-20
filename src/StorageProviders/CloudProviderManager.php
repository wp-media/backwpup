<?php
namespace WPMedia\BackWPup\StorageProviders;

use WPMedia\BackWPup\StorageProviders\ProviderInterface;

class CloudProviderManager {

	/**
	 * List of registered cloud storage providers.
	 *
	 * @var ProviderInterface[]
	 */
	private $providers = [];

	/**
	 * CloudProviderManager constructor.
	 *
	 * Initializes the CloudProviderManager with an array of storage providers.
	 * Each provider must implement the ProviderInterface and will be stored
	 * in the $providers property, keyed by the provider's name.
	 *
	 * @param ProviderInterface[] $providers Array of storage provider instances.
	 */
	public function __construct( array $providers ) {
		foreach ( $providers as $provider ) {
			/**
			 * Cloud storage provider instance.
			 *
			 * @var ProviderInterface $provider
			 */
			$this->providers[ $provider->get_name() ] = $provider;
		}
	}

	/**
	 * Retrieves a cloud storage provider by its name.
	 *
	 * @param string $cloud_name The name of the cloud provider to retrieve.
	 * @return ProviderInterface The repository interface for the specified cloud provider.
	 * @throws \Exception If the specified provider does not exist.
	 */
	public function get_provider( string $cloud_name ): ProviderInterface {
		if ( ! isset( $this->providers[ $cloud_name ] ) ) {
			throw new \Exception( "Unknown provider: $cloud_name" ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}
		return $this->providers[ $cloud_name ];
	}

	/**
	 * Retrieves all registered cloud storage providers.
	 *
	 * @return array List of all cloud storage provider instances.
	 */
	public function get_all_providers(): array {
		return $this->providers;
	}
}
