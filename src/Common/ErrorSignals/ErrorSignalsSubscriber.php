<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Common\ErrorSignals;

use BackWPup_Job;
use WPMedia\BackWPup\EventManagement\SubscriberInterface;

class ErrorSignalsSubscriber implements SubscriberInterface {

	/**
	 * ErrorSignalsStore instance.
	 *
	 * @var ErrorSignalsStore
	 */
	private ErrorSignalsStore $store;

	/**
	 * Constructor.
	 *
	 * @param ErrorSignalsStore $store
	 */
	public function __construct( ErrorSignalsStore $store ) {
		$this->store = $store;
	}

	/**
	 * Subscribed events.
	 *
	 * @return array[]
	 */
	public static function get_subscribed_events(): array {
		return [
			'backwpup_job_error_signal' => [ [ 'on_error_signal', 10, 2 ] ],
		];
	}

	/**
	 * Store errors.
	 *
	 * @param array             $signal Errors data.
	 * @param BackWPup_Job|null $job The Job instance.
	 * @return void
	 */
	public function on_error_signal( array $signal, BackWPup_Job $job = null ): void {
		$this->store->store( $signal );
	}
}
