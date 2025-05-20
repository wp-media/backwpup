<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Adapters;

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

		'backwpup_adapter',
		'option_adapter',
		'backwpup_helpers_adapter',
		'job_adapter',
		'cron_adapter',
		'file_adapter',
		'job_types_adapter',
		'encryption_adapter',
	];

	/**
	 * Subscribers provided by this provider
	 *
	 * @var array
	 */
	public $subscribers = [];

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
		$this->getContainer()->addShared( 'option_adapter', OptionAdapter::class );
		$this->getContainer()->addShared( 'backwpup_helpers_adapter', BackWPupHelpersAdapter::class );
		$this->getContainer()->addShared( 'backwpup_adapter', BackWPupAdapter::class );
		$this->getContainer()->addShared( 'job_adapter', JobAdapter::class );
		$this->getContainer()->addShared( 'cron_adapter', CronAdapter::class );
		$this->getContainer()->addShared( 'file_adapter', FileAdapter::class );
		$this->getContainer()->addShared( 'job_types_adapter', JobTypesAdapter::class );
		$this->getContainer()->addShared( 'encryption_adapter', EncryptionAdapter::class );
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
