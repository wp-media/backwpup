<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Backups\Onboarding;

use Exception;
use WPMedia\BackWPup\Adapters\{
	BackWPupAdapter,
	JobAdapter,
	JobTypesAdapter,
	OptionAdapter
};

class Onboarding {
	/**
	 * JobTypesAdapter instance.
	 *
	 * @var JobTypesAdapter
	 */
	private JobTypesAdapter $job_types_adapter;

	/**
	 * OptionAdapter instance.
	 *
	 * @var OptionAdapter
	 */
	private OptionAdapter $option_adapter;

	/**
	 * JobAdapter instance.
	 *
	 * @var JobAdapter
	 */
	private JobAdapter $job_adapter;

	/**
	 * Instance of BackWPupAdapter.
	 *
	 * @var BackWPupAdapter
	 */
	private BackWPupAdapter $backwpup_adapter;

	/**
	 * Constructor.
	 *
	 * @param JobTypesAdapter $job_types_adapter Job type adapter instance.
	 * @param OptionAdapter   $option_adapter Option adapter instance.
	 * @param JobAdapter      $job_adapter Job adapter instance.
	 * @param BackWPupAdapter $backwpup_adapter Backwpup adapter instance.
	 */
	public function __construct(
		JobTypesAdapter $job_types_adapter,
		OptionAdapter $option_adapter,
		JobAdapter $job_adapter,
		BackWPupAdapter $backwpup_adapter
	) {
		$this->job_types_adapter = $job_types_adapter;
		$this->option_adapter    = $option_adapter;
		$this->job_adapter       = $job_adapter;
		$this->backwpup_adapter  = $backwpup_adapter;
	}

	/**
	 * Update job values.
	 *
	 * @param string $key Key.
	 * @param string $job_id Job id.
	 * @param array  $type Type of job.
	 * @param array  $default The default job values.
	 *
	 * @return void
	 */
	private function update_job_options_values( string $key, $job_id, array $type, array $default ): void {
		$name   = $this->job_types_adapter->$key;
		$job_id = (int) $job_id;

		$options = [
			'name'                 => $name,
			'jobid'                => $job_id,
			'backuptype'           => $default['backuptype'],
			'type'                 => $type,
			'mailaddresslog'       => sanitize_email( $default['mailaddresslog'] ),
			'mailaddresssenderlog' => $default['mailaddresssenderlog'],
			'mailerroronly'        => $default['mailerroronly'],
			'archiveencryption'    => $default['archiveencryption'],
			'archivename'          => $this->job_adapter->sanitize_file_name(
				$this->option_adapter->normalize_archive_name(
					$default['archivename'],
					$job_id,
					false
				)
			),
		];

		foreach ( $options as $option_key => $option_value ) {
			$this->option_adapter->update( $job_id, $option_key, $option_value );
		}
	}

	/**
	 * Update default job options during onboarding process
	 *
	 * @param array $job_frequency Job frequency.
	 * @param array $default_values Default values.
	 *
	 * @return void
	 */
	public function save_onboarding_job_options( array $job_frequency, array $default_values ): void {
		foreach ( $job_frequency as $key => $value ) {
			$job_id = $value['job_id'];
			update_site_option( "backwpup_backup_{$key}_job_id", $job_id );
			$this->update_job_options_values( $key, $job_id, $value['type'], $default_values );
		}
	}

	/**
	 * Update onboarding job storage provider.
	 *
	 * @param string $job_id Job id.
	 * @param array  $storage_providers Storage provider.
	 *
	 * @throws Exception Throw exception when no storage is found.
	 *
	 * @return void
	 */
	public function save_onboarding_job_storage( string $job_id, array $storage_providers ): void {
		foreach ( $storage_providers as $storage ) {
			$cloud = $this->backwpup_adapter->get_destination( strtolower( $storage ) );

			if ( null === $cloud ) {
				throw new Exception( esc_html__( 'Cloud not found', 'backwpup' ) );
			}
		}

		$this->option_adapter->update( (int) $job_id, 'destinations', $storage_providers );
	}
}
