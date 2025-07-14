<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Backups;

use WPMedia\BackWPup\Dependencies\League\Container\ServiceProvider\AbstractServiceProvider;
use WPMedia\BackWPup\Backups\API\Subscriber as BackupsApiSubscriber;
use WPMedia\BackWPup\Backups\API\Rest as BackupsApiRest;
use WPMedia\BackWPup\Backups\History\Frontend\API\Subscriber as BackupsHistoryFrontendApiSubscriber;
use WPMedia\BackWPup\Backups\History\Frontend\API\Rest as BackupsHistoryFrontendApiRest;
use WPMedia\BackWPup\Backups\Onboarding\{
	Onboarding,
	Subscriber as OnboardingSubscriber
};

class ServiceProvider extends AbstractServiceProvider {
	/**
	 * An array of services provided by this service provider.
	 *
	 * This property defines the list of services that this provider offers.
	 * It is used to register and manage these services within the application.
	 *
	 * @var array
	 */
	protected $provides = [
		'backups_api_subscriber',
		'backups_api_rest',
		'backups_history_frontend_api_rest',
		'backups_history_frontend_api_subscriber',
		'backwpup_onboarding_subscriber',
		'backwpup_onboarding',
	];

	/**
	 * An array of subscribers for the service provider.
	 *
	 * This property holds a list of subscribers that are registered
	 * to listen for events or perform specific actions within the
	 * Backups service of the BackWPup plugin.
	 *
	 * @var array
	 */
	public $subscribers = [
		'backups_api_subscriber',
		'backups_history_frontend_api_subscriber',
		'backwpup_onboarding_subscriber',
	];

	/**
	 * Determines if the service provider can provide the specified service.
	 *
	 * @param string $id The identifier of the service to check.
	 * @return bool True if the service provider can provide the service, false otherwise.
	 */
	public function provides( string $id ): bool {
		return in_array( $id, $this->provides, true );
	}

	/**
	 * Registers services or bindings within the Backups service provider.
	 *
	 * This method is called to initialize and configure the necessary
	 * components or dependencies required by the Backups service.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->getContainer()->addShared( 'backups_api_rest', BackupsApiRest::class )
			->addArguments(
				[
					'backwpup_adapter',
					'option_adapter',
					'job_adapter',
					'file_adapter',
					'job_types_adapter',
				]
				);

		$this->getContainer()->addShared( 'backups_api_subscriber', BackupsApiSubscriber::class )
			->addArguments(
				[
					'backups_api_rest',
				]
				);

		$this->getContainer()->addShared( 'backups_history_frontend_api_rest', BackupsHistoryFrontendApiRest::class )
			->addArguments(
				[
					'backwpup_adapter',
					'option_adapter',
					'backwpup_helpers_adapter',
				]
				);
		$this->getContainer()->addShared( 'backups_history_frontend_api_subscriber', BackupsHistoryFrontendApiSubscriber::class )
			->addArguments(
				[
					'backups_history_frontend_api_rest',
				]
				);
		$this->getContainer()->addShared( 'backwpup_onboarding', Onboarding::class )
			->addArguments(
				[
					'job_types_adapter',
					'option_adapter',
					'job_adapter',
					'backwpup_adapter',
				]
			);

		$this->getContainer()->addShared( 'backwpup_onboarding_subscriber', OnboardingSubscriber::class )
			->addArguments(
				[
					'backwpup_onboarding',
				]
			);
	}

	/**
	 * Retrieves the list of subscribers for the service provider.
	 *
	 * @return array An array of subscribers associated with the service provider.
	 */
	public function get_subscribers() {
		return $this->subscribers;
	}
}
