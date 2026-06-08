<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Jobs;

use WPMedia\BackWPup\Abilities\Jobs\CancelJob;
use WPMedia\BackWPup\Abilities\Jobs\GetBackupHistory;
use WPMedia\BackWPup\Abilities\Jobs\GetBackupLogs;
use WPMedia\BackWPup\Abilities\Jobs\GetJobsList;
use WPMedia\BackWPup\Abilities\Jobs\RunJob;
use WPMedia\BackWPup\Dependencies\League\Container\ServiceProvider\AbstractServiceProvider;
use WPMedia\BackWPup\Jobs\API\Subscriber as JobApiSubscriber;
use WPMedia\BackWPup\Jobs\API\Rest as JobApiRest;
use WPMedia\BackWPup\Jobs\Frontend\API\Subscriber as JobApiFrontendSubscriber;
use WPMedia\BackWPup\Jobs\Frontend\API\Rest as JobApiFrontendRest;
use WPMedia\BackWPup\Jobs\Abilities\Subscriber as JobsAbilitiesSubscriber;

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
		'get_jobs_list_ability',
		'run_job_ability',
		'cancel_job_ability',
		'get_backup_history_ability',
		'get_backup_logs_ability',
		'jobs_abilities_subscriber',
	];

	/**
	 * Subscribers provided by this provider
	 *
	 * @var array
	 */
	public $subscribers = [
		'job_api_subscriber',
		'job_api_frontend_subscriber',
		'jobs_abilities_subscriber',
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

		$this->getContainer()->addShared( 'get_jobs_list_ability', GetJobsList::class )
			->addArguments(
				[
					'backwpup_adapter',
					'option_adapter',
				]
			);

		$this->getContainer()->addShared( 'run_job_ability', RunJob::class )
			->addArguments(
				[
					'backwpup_adapter',
					'option_adapter',
					'job_adapter',
					'file_adapter',
					'job_types_adapter',
				]
			);

		$this->getContainer()->addShared( 'cancel_job_ability', CancelJob::class )
			->addArguments(
				[
					'job_adapter',
				]
			);

		$this->getContainer()->addShared( 'get_backup_history_ability', GetBackupHistory::class )
			->addArguments(
				[
					'backwpup_adapter',
					'option_adapter',
					'backwpup_helpers_adapter',
					'job_adapter',
					'backups_query',
				]
			);

		$this->getContainer()->addShared( 'get_backup_logs_ability', GetBackupLogs::class );

		$this->getContainer()->addShared( 'jobs_abilities_subscriber', JobsAbilitiesSubscriber::class )
			->addArguments(
				[
					'get_jobs_list_ability',
					'run_job_ability',
					'cancel_job_ability',
					'get_backup_history_ability',
					'get_backup_logs_ability',
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
