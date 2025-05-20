<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Jobs;

use WPMedia\BackWPup\Dependencies\League\Container\ServiceProvider\AbstractServiceProvider;
use WPMedia\BackWPup\Adapters\JobAdapter;
use WPMedia\BackWPup\Adapters\OptionAdapter;
use WPMedia\BackWPup\Adapters\CronAdapter;
use WPMedia\BackWPup\Adapters\JobTypesAdapter;
use WPMedia\BackWPup\Adapters\BackWPupHelpersAdapter;
use WPMedia\BackWPup\Adapters\BackWPupAdapter;
use WPMedia\BackWPup\Jobs\API\Subscriber as JobApiSubscriber;
use WPMedia\BackWPup\Jobs\API\Rest as JobApiRest;
use WPMedia\BackWPup\Jobs\Frontend\API\Subscriber as JobApiFrontendSubscriber;
use WPMedia\BackWPup\Jobs\Frontend\API\Rest as JobApiFrontendRest;

class ServiceProvider extends AbstractServiceProvider {

	/**
	 * Services provided by this provider.
	 *
	 * @var array
	 */
	protected $provides = [
		'job_api_subscriber',
		'job_api_rest',
		'job_api_frontend_subscriber',
		'job_api_frontend_rest',
		// 'backwpup_helpers_adapter',
		// 'backwpup_adapter',
		// 'job_types_adapter',
		// 'job_adapter',
		// 'option_adapter',
		// 'cron_adapter',
	];

	/**
	 * Subscribers provided by this provider
	 *
	 * @var array
	 */
	public $subscribers = [
		'job_api_subscriber',
		'job_api_frontend_subscriber',
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
	 * Registers items with the container.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->getContainer()->addShared( 'job_api_rest', JobApiRest::class )
			->addArguments(
				[
					'job_adapter',
					'option_adapter',
					'cron_adapter',
					'job_types_adapter',
					'backwpup_adapter',
					'encryption_adapter',
				]
				);

		$this->getContainer()->addShared( 'job_api_subscriber', JobApiSubscriber::class )
			->addArguments(
				[
					'job_api_rest',
				]
				);

		$this->getContainer()->addShared( 'job_api_frontend_rest', JobApiFrontendRest::class )
			->addArguments(
				[
					'backwpup_helpers_adapter',
					'job_adapter',
				]
				);
		$this->getContainer()->addShared( 'job_api_frontend_subscriber', JobApiFrontendSubscriber::class )
			->addArguments(
				[
					'job_api_frontend_rest',
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
