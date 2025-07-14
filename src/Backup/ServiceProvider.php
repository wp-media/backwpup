<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Backup;

use WPMedia\BackWPup\Backup\Database;
use WPMedia\BackWPup\Backup\Database\Queries\Backup as BackupQuery;
use WPMedia\BackWPup\Backup\Database\Tables\Backup as BackupTable;
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
		$this->getContainer()->add( 'backwpup_database', Database::class )
			->addArguments(
				[
					'backups_query',
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
