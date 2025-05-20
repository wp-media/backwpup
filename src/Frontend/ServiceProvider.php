<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Frontend;

use WPMedia\BackWPup\Dependencies\League\Container\ServiceProvider\AbstractServiceProvider;
use WPMedia\BackWPup\Frontend\API\Rest as FrontendApiRest;
use WPMedia\BackWPup\Frontend\API\Subscriber as FrontendApiSubscriber;
use WPMedia\BackWPup\Adapters\BackWPupHelpersAdapter;

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
		'frontend_api_subscriber',
		'frontend_api_rest',
	];

	/**
	 * Subscribers provided by this provider
	 *
	 * @var array
	 */
	public $subscribers = [
		'frontend_api_subscriber',
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
		$this->getContainer()->addShared( 'frontend_api_rest', FrontendApiRest::class )
			->addArguments(
				[
					'backwpup_helpers_adapter' => $this->getContainer()->get( 'backwpup_helpers_adapter' ),
				]
				);

		$this->getContainer()->addShared( 'frontend_api_subscriber', FrontendApiSubscriber::class )
			->addArgument( 'frontend_api_rest' );
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
