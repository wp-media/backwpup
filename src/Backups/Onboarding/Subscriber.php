<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Backups\Onboarding;

use WPMedia\BackWPup\EventManagement\SubscriberInterface;

class Subscriber implements SubscriberInterface {

	/**
	 * Onboarding instance.
	 *
	 * @var Onboarding
	 */
	private Onboarding $onboarding;

	/**
	 * Constructor
	 *
	 * @param Onboarding $onboarding Onboarding instance.
	 */
	public function __construct( Onboarding $onboarding ) {
		$this->onboarding = $onboarding;
	}

	/**
	 * Returns an array of events that this subscriber wants to listen to.
	 *
	 * @return array
	 */
	public static function get_subscribed_events(): array {
		return [
			'backwpup_onboarding_save_option' => [ 'save_onboarding_option', 10, 2 ],
			'backwpup_onboarding_storage'     => [ 'save_onboarding_storage', 10, 2 ],
		];
	}

	/**
	 * Save Job during onboarding process.
	 *
	 * @param array $job_frequency Job frequency.
	 * @param array $data Default job values.
	 *
	 * @return void
	 */
	public function save_onboarding_option( array $job_frequency, array $data ): void {
		$this->onboarding->save_onboarding_job_options( $job_frequency, $data );
	}

	/**
	 * Save storage for onboarding jobs.
	 *
	 * @param string $job_id The onboarding job id.
	 * @param array  $storage_provider The storage providers selected during onboarding.
	 *
	 * @return void
	 */
	public function save_onboarding_storage( string $job_id, array $storage_provider ): void {
		$this->onboarding->save_onboarding_job_storage( $job_id, $storage_provider );
	}
}
