<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Backup;

use WPMedia\BackWPup\Backup\Database;
use WPMedia\BackWPup\Backup\Database\Queries\Backup as BackupQuery;
use WPMedia\BackWPup\Backup\Database\Tables\Backup as BackupTable;
use WPMedia\BackWPup\Backup\FailureContext\Dropbox\DropboxFailureDisplayDetailsProvider;
use WPMedia\BackWPup\Backup\FailureContext\FailureDisplayDetailsResolver;
use WPMedia\BackWPup\Backup\FailureContext\GenericFailureDisplayDetailsProvider;
use WPMedia\BackWPup\Backup\FailureContext\Ftp\FtpFailureDisplayDetailsProvider;
use WPMedia\BackWPup\Backup\FailureContext\MSAzure\MSAzureFailureDisplayDetailsProvider;
use WPMedia\BackWPup\Backup\FailureContext\Rsc\RscFailureDisplayDetailsProvider;
use WPMedia\BackWPup\Backup\FailureContext\S3\S3FailureDisplayDetailsProvider;
use WPMedia\BackWPup\Backup\FailureContext\Sftp\SftpFailureDisplayDetailsProvider;
use WPMedia\BackWPup\Backup\FailureContext\SugarSync\SugarSyncFailureDisplayDetailsProvider;
use WPMedia\BackWPup\Backup\FailureReasonResolver;
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
		'backups_query',
		'backups_table',
		'failure_display_details_resolver',
		'failure_reason_resolver',
		'backwpup_database',
		'backups_subscriber',
	];

	/**
	 * Subscribers provided by this provider
	 *
	 * @var array
	 */
	public $subscribers = [
		'backups_subscriber',
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
		$this->getContainer()->addShared( 'backups_table', BackupTable::class );
		$this->getContainer()->get( 'backups_table' );
		$this->getContainer()->add( 'backups_query', BackupQuery::class );
		$this->getContainer()->addShared( 'ftp_failure_display_details_provider', FtpFailureDisplayDetailsProvider::class );
		$this->getContainer()->addShared( 'sftp_failure_display_details_provider', SftpFailureDisplayDetailsProvider::class );
		$this->getContainer()->addShared( 'dropbox_failure_display_details_provider', DropboxFailureDisplayDetailsProvider::class );
		$this->getContainer()->addShared( 's3_failure_display_details_provider', S3FailureDisplayDetailsProvider::class );
		$this->getContainer()->addShared( 'msazure_failure_display_details_provider', MSAzureFailureDisplayDetailsProvider::class );
		$this->getContainer()->addShared( 'rsc_failure_display_details_provider', RscFailureDisplayDetailsProvider::class );
		$this->getContainer()->addShared( 'sugarsync_failure_display_details_provider', SugarSyncFailureDisplayDetailsProvider::class );
		$this->getContainer()->addShared( 'generic_failure_display_details_provider', GenericFailureDisplayDetailsProvider::class );
		$this->getContainer()->addShared( 'failure_display_details_resolver', FailureDisplayDetailsResolver::class )
			->addArgument( 'ftp_failure_display_details_provider' )
			->addArgument( 'sftp_failure_display_details_provider' )
			->addArgument( 'dropbox_failure_display_details_provider' )
			->addArgument( 's3_failure_display_details_provider' )
			->addArgument( 'msazure_failure_display_details_provider' )
			->addArgument( 'rsc_failure_display_details_provider' )
			->addArgument( 'sugarsync_failure_display_details_provider' )
			->addArgument( 'generic_failure_display_details_provider' );
		$this->getContainer()->addShared( 'failure_reason_resolver', FailureReasonResolver::class )
			->addArgument( 'error_signals_context_store' );
		$this->getContainer()->add( 'backwpup_database', Database::class )
			->addArguments(
				[
					'backups_query',
					'error_signals_store',
					'failure_reason_resolver',
				]
			);
		$this->getContainer()->addShared( 'backups_subscriber', Subscriber::class )
			->addArgument( 'backwpup_database' );
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
