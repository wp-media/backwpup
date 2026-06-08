<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Jobs\Abilities;

use WPMedia\BackWPup\Abilities\Jobs\CancelJob;
use WPMedia\BackWPup\Abilities\Jobs\GetBackupHistory;
use WPMedia\BackWPup\Abilities\Jobs\GetBackupLogs;
use WPMedia\BackWPup\Abilities\Jobs\GetJobsList;
use WPMedia\BackWPup\Abilities\Jobs\RunJob;
use WPMedia\BackWPup\EventManagement\SubscriberInterface;

class Subscriber implements SubscriberInterface {

	/**
	 * GetJobsList Ability Instance
	 *
	 * @var GetJobsList
	 */
	private GetJobsList $get_jobs_list_ability;

	/**
	 * RunJob ability instance
	 *
	 * @var RunJob
	 */
	private RunJob $run_job;

	/**
	 * GetBackupHistory ability instance
	 *
	 * @var GetBackupHistory
	 */
	private GetBackupHistory $get_backup_history;

	/**
	 * GetBackupLogs ability instance
	 *
	 * @var GetBackupLogs
	 */
	private GetBackupLogs $get_backup_logs;

	/**
	 * CancelJob ability instance
	 *
	 * @var CancelJob
	 */
	private CancelJob $cancel_job;

	/**
	 * Constructor
	 *
	 * @param GetJobsList      $get_jobs_list_ability GetJobsList Ability Instance.
	 * @param RunJob           $run_job               RunJob ability instance.
	 * @param CancelJob        $cancel_job            CancelJob ability instance.
	 * @param GetBackupHistory $get_backup_history    GetBackupHistory ability instance.
	 * @param GetBackupLogs    $get_backup_logs       GetBackupLogs ability instance.
	 */
	public function __construct( GetJobsList $get_jobs_list_ability, RunJob $run_job, CancelJob $cancel_job, GetBackupHistory $get_backup_history, GetBackupLogs $get_backup_logs ) {
		$this->get_jobs_list_ability = $get_jobs_list_ability;
		$this->run_job               = $run_job;
		$this->cancel_job            = $cancel_job;
		$this->get_backup_history    = $get_backup_history;
		$this->get_backup_logs       = $get_backup_logs;
	}

	/**
	 * Get the events to subscribe to.
	 *
	 * @return array
	 */
	public static function get_subscribed_events(): array {
		return [
			'wp_abilities_api_categories_init' => 'register_jobs_abilities_categories',
			'wp_abilities_api_init'            => 'register_abilities',
		];
	}

	/**
	 * Register jobs abilities category.
	 *
	 * @return void
	 */
	public function register_jobs_abilities_categories(): void {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		wp_register_ability_category(
			'backwpup-jobs',
			[
				'label'       => __( 'BackWPup Jobs', 'backwpup' ),
				'description' => __( 'Backup management tools for WordPress. Before performing any risky action (plugin updates, theme changes, core updates, database operations), use these tools to check backup status and offer the user a backup.', 'backwpup' ),
			]
		);
	}

	/**
	 * Register abilities
	 *
	 * @return void
	 */
	public function register_abilities(): void {
		$this->get_jobs_list_ability->register();
		$this->run_job->register();
		$this->cancel_job->register();
		$this->get_backup_history->register();
		$this->get_backup_logs->register();
	}
}
