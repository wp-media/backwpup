<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\StorageProviders\Rackspace;

use WPMedia\BackWPup\Adapters\OptionAdapter;
use WPMedia\BackWPup\EventManagement\SubscriberInterface;

class Subscriber implements SubscriberInterface {
	/**
	 * Rackspace class instance.
	 *
	 * @var RackspaceProvider
	 */
	private $rackspace;

	/**
	 * Instance of Option adapter
	 *
	 * @var OptionAdapter
	 */
	private OptionAdapter $option_adapter;

	/**
	 * Constructor
	 *
	 * @param OptionAdapter $option_adapter Adapter for managing options.
	 */
	public function __construct( OptionAdapter $option_adapter ) {
		$this->option_adapter = $option_adapter;
	}

	/**
	 * Returns an array of events that this subscriber wants to listen to.
	 *
	 * @return array
	 */
	public static function get_subscribed_events(): array {
		return [
			'backwpup_rsc_delete_segment_files' => [ 'delete_segments_file', 10, 2 ],
		];
	}

	/**
	 * Delete segments file
	 *
	 * @param string $filename The prefix filename that needs to be deleted.
	 * @param string $job_id The job id.
	 *
	 * @return void
	 */
	public function delete_segments_file( string $filename, string $job_id ): void {
		$this->initialise_rackspace( (int) $job_id );

		$this->rackspace->delete_segments_file( $filename );
	}

	/**
	 * Initialise rackspace
	 *
	 * @param int $job_id Job id.
	 *
	 * @return void
	 */
	public function initialise_rackspace( int $job_id ) {
		$rcs_region = $this->option_adapter->get( $job_id, 'rscregion' );
		$username   = $this->option_adapter->get( $job_id, 'rscusername' );
		$api_key    = $this->option_adapter->get( $job_id, 'rscapikey' );
		$container  = $this->option_adapter->get( $job_id, 'rsccontainer' );

		$rackspace_client = new RackspaceProvider(
			[
				'username'       => $username,
				'api_key'        => \BackWPup_Encryption::decrypt( $api_key ),
				'container_name' => $container,
				'region'         => $rcs_region,
			],
		);

		$rackspace_client->initialise();

		$this->rackspace = $rackspace_client;
	}
}
