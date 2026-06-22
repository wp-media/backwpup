<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\MCP;

use WPMedia\BackWPup\Abilities\Jobs\CancelJob;
use WPMedia\BackWPup\Abilities\Jobs\GetBackupHistory;
use WPMedia\BackWPup\Abilities\Jobs\GetBackupLogs;
use WPMedia\BackWPup\Abilities\Jobs\GetJobsList;
use WPMedia\BackWPup\Abilities\Jobs\RunJob;
use WPMedia\BackWPup\Abilities\MCP\DocsOverview;
use WPMedia\BackWPup\Dependencies\League\Container\ServiceProvider\AbstractServiceProvider;
use WPMedia\BackWPup\Jobs\Abilities\Subscriber as JobsAbilitiesSubscriber;

class ServiceProvider extends AbstractServiceProvider {

	/**
	 * Services provided by this provider.
	 *
	 * @var array
	 */
	protected $provides = [
		'mcp_config_subscriber',
		'mcp_docs_overview_ability',
		'mcp_abilities_subscriber',
		'get_jobs_list_ability',
		'run_job_ability',
		'cancel_job_ability',
		'get_backup_history_ability',
		'get_backup_logs_ability',
		'jobs_abilities_subscriber',
	];

	/**
	 * Register services
	 *
	 * @return void
	 */
	public function register(): void {
		$this->getContainer()->addShared( 'mcp_config_subscriber', ConfigSubscriber::class );

		$this->getContainer()->addShared( 'mcp_docs_overview_ability', DocsOverview::class );

		$this->getContainer()->addShared( 'mcp_abilities_subscriber', AbilitiesSubscriber::class )
			->addArguments(
				[
					'mcp_docs_overview_ability',
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
	public function get_subscribers(): array {
		return [
			'mcp_config_subscriber',
			'mcp_abilities_subscriber',
			'jobs_abilities_subscriber',
		];
	}

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
}
