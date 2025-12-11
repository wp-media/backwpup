<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Cli;

use BackWPup_File;
use WPMedia\BackWPup\Cli\Commands\Backup;
use WPMedia\BackWPup\Cli\Commands\BackupDelete;
use WPMedia\BackWPup\Cli\Commands\BackupDownload;
use WPMedia\BackWPup\Cli\Commands\JobActivate;
use WPMedia\BackWPup\Cli\Commands\Kill;
use WPMedia\BackWPup\Cli\Commands\Job;
use WPMedia\BackWPup\Cli\Commands\Log;
use WPMedia\BackWPup\Cli\Commands\LogDelete;
use WPMedia\BackWPup\Cli\Commands\LogShow;
use WPMedia\BackWPup\Cli\Commands\Run;
use WPMedia\BackWPup\Cli\Commands\Status;
use WPMedia\BackWPup\Cli\Commands\Version;
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
		Subscriber::class,
	];

	/**
	 * Subscribers provided by this provider
	 *
	 * @var array
	 */
	public $subscribers = [
		Subscriber::class,
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

		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			return;
		}

		$this->getContainer()->add( Version::class )
							->addArgument( $this->getContainer()->get( 'backwpup_adapter' )->get_plugin_data() );
		$this->getContainer()->add( Run::class )
							->addArguments(
								[
									$this->getContainer()->get( 'job_adapter' ),
									$this->getContainer()->get( 'option_adapter' ),
									$this->getContainer()->get( 'backwpup_adapter' ),
									$this->getContainer()->get( 'job_types_adapter' ),
								]
							);
		$this->getContainer()->add( Kill::class )
			->addArguments(
				[
					$this->getContainer()->get( 'job_adapter' ),
					$this->getContainer()->get( 'backwpup_adapter' ),
				]
				);
		$this->getContainer()->add( Job::class )
			->addArgument( $this->getContainer()->get( 'job_adapter' ) );
		$this->getContainer()->add( Status::class )
			->addArguments(
			[
				$this->getContainer()->get( 'backwpup_adapter' ),
				$this->getContainer()->get( 'job_adapter' ),
			]
		);
		$this->getContainer()->add( JobActivate::class )
			->addArguments(
				[
					$this->getContainer()->get( 'job_adapter' ),
					$this->getContainer()->get( 'option_adapter' ),
					$this->getContainer()->get( 'cron_adapter' ),
				]
			);
		$this->getContainer()->add( Backup::class )
			->addArguments(
				[
					$this->getContainer()->get( 'job_adapter' ),
					$this->getContainer()->get( 'backwpup_adapter' ),
				]
			);

		$this->getContainer()->add( \BackWPup_Destination_Downloader_Factory::class );
		$this->getContainer()->add( BackupDownload::class )
			->addArguments(
				[
					$this->getContainer()->get( 'job_adapter' ),
					$this->getContainer()->get( 'backwpup_adapter' ),
					$this->getContainer()->get( \BackWPup_Destination_Downloader_Factory::class ),
				]
			);
		$this->getContainer()->add( BackupDelete::class )
			->addArguments(
				[
					$this->getContainer()->get( 'job_adapter' ),
					$this->getContainer()->get( 'backwpup_adapter' ),
				]
			);

		$this->getContainer()->add( BackWPup_File::class );
		$this->getContainer()->add( Log::class )->addArguments( [ $this->getContainer()->get( BackWPup_File::class ) ] );
		$this->getContainer()->add( LogShow::class )->addArguments( [ $this->getContainer()->get( BackWPup_File::class ) ] );
		$this->getContainer()->add( LogDelete::class )->addArguments( [ $this->getContainer()->get( BackWPup_File::class ) ] );

		$this->getContainer()->add( Subscriber::class )
			->addArgument(
				[
					$this->getContainer()->get( Version::class ),
					$this->getContainer()->get( Run::class ),
					$this->getContainer()->get( Kill::class ),
					$this->getContainer()->get( Job::class ),
					$this->getContainer()->get( JobActivate::class ),
					$this->getContainer()->get( Status::class ),
					$this->getContainer()->get( Log::class ),
					$this->getContainer()->get( LogShow::class ),
					$this->getContainer()->get( LogDelete::class ),
					$this->getContainer()->get( Backup::class ),
					$this->getContainer()->get( BackupDownload::class ),
					$this->getContainer()->get( BackupDelete::class ),
				]
			);
	}

	/**
	 * Returns the subscribers array
	 *
	 * @return array
	 */
	public function get_subscribers() {
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			return [];
		}
		return $this->subscribers;
	}
}
