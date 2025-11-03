<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Hosting;

use WPMedia\BackWPup\Dependencies\League\Container\ServiceProvider\AbstractServiceProvider;

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
		'hosting_kinsta',
	];

	/**
	 * Subscribers provided by this provider
	 *
	 * @var array
	 */
	public $subscribers = [
		'hosting_kinsta',
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
		$this->getContainer()->addShared( 'hosting_kinsta', Kinsta::class )
			->addArgument( 'job_adapter' )
			->addArgument( 'option_adapter' );
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
