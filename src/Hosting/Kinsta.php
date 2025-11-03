<?php

namespace WPMedia\BackWPup\Hosting;

use WPMedia\BackWPup\Adapters\JobAdapter;
use WPMedia\BackWPup\Adapters\OptionAdapter;
use WPMedia\BackWPup\EventManagement\SubscriberInterface;

class Kinsta implements SubscriberInterface {


	/**
	 * JobAdapter instance
	 *
	 * @var JobAdapter $job_adapter
	 */
	private JobAdapter $job_adapter;

	/**
	 * OptionAdapter instance
	 *
	 * @var OptionAdapter $option_adapter
	 */
	private OptionAdapter $option_adapter;

	/**
	 * Constructor method for initializing the class with a JobAdapter instance.
	 *
	 * @param JobAdapter    $job_adapter The JobAdapter instance to be assigned.
	 * @param OptionAdapter $option_adapter The OptionAdapter instance to be assigned.
	 *
	 * @return void
	 */
	public function __construct( JobAdapter $job_adapter, OptionAdapter $option_adapter ) {
		$this->job_adapter    = $job_adapter;
		$this->option_adapter = $option_adapter;
	}

	/**
	 * Determines if the current server environment is hosting.
	 *
	 * @return bool True if the server environment is hosting, false otherwise.
	 */
	public function is_hosting() {
		return isset( $_SERVER['KINSTA_CACHE_ZONE'] );
	}

	/**
	 * Determines the backup type based on the provided job data.
	 *
	 * @param array $job_data An associative array containing job details, including a 'type' key with an array of backup types.
	 *
	 * @return string Returns 'db' if the backup type is only database, 'file' if it is only files, 'full' if it is both, or an empty string if none match.
	 */
	public function get_backup_type( array $job_data ) {
		if ( ! in_array( 'FILE', $job_data['type'], true ) && in_array( 'DBDUMP', $job_data['type'], true ) ) {
			return 'db';
		} elseif ( in_array( 'FILE', $job_data['type'], true ) && ! in_array( 'DBDUMP', $job_data['type'], true ) ) {
			return 'file';
		} elseif ( in_array( 'FILE', $job_data['type'], true ) && in_array( 'DBDUMP', $job_data['type'], true ) ) {
			return 'full';
		}
		return '';
	}

	/**
	 * Retrieves the last run time of jobs filtered by a specific type.
	 *
	 * @param string $type The type of backup or job to filter.
	 *
	 * @return int The timestamp of the last run time for the last matching job, or 0 if no matching found or on run errors.
	 */
	public function get_last_run_time_from_jobs_by_type( string $type ) {
		$last_run_time = 0;
		$jobs          = $this->job_adapter->get_jobs();
		foreach ( $jobs as $job ) {
			$log_header = \BackWPup_Job::read_logheader( $job['logfile'] );
			if ( ! $log_header || ! isset( $log_header['errors'] ) ) {
				$job['errors'] = 0;
			}
			if ( ! isset( $job['lastrun'] ) ) {
				$job['lastrun'] = 0;
			}
			if ( $type === $this->get_backup_type( $job ) &&
				$last_run_time < $job['lastrun'] &&
				empty( $log_header['errors'] )
			) {
				$last_run_time = $job['lastrun'];
			}
		}
		return $last_run_time;
	}

	/**
	 * Retrieves the avoid time duration based on the provided type.
	 *
	 * @param string $type The type used to determine the avoid time duration.
	 *                     Possible values are 'full', 'db', or 'file'.
	 *
	 * @return int The avoid time in seconds. Returns 30 days for 'full',
	 *             7 days for 'db' or 'file', and 0 for other types.
	 */
	public function get_avoid_time_by_type( string $type ) {

		if ( 'full' === $type ) {
			return HOUR_IN_SECONDS * 24 * 30; // 30 days
		}
		if ( 'db' === $type || 'file' === $type ) {
			return HOUR_IN_SECONDS * 24 * 7; // 7 days
		}

		return 0;
	}

	/**
	 * Determines if a job can start based on the specified job data and timing conditions.
	 *
	 * @param bool  $start The initial state indicating whether the job can start.
	 * @param array $job_data Array containing job-related metadata, including the last run time, log file, and job type.
	 *
	 * @return bool True if the job can start, false otherwise, based on various conditions such as last run time, log file errors, and job type.
	 */
	public function can_job_start( $start, $job_data ) {

		if ( ! $this->is_hosting() ) {
			return $start;
		}

		// phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
		$current_time  = current_time( 'timestamp' );
		$current_type  = $this->get_backup_type( $job_data );
		$last_run_time = $this->get_last_run_time_from_jobs_by_type( $current_type );
		$avoid_time    = $this->get_avoid_time_by_type( $current_type );

		if ( $last_run_time + $avoid_time > $current_time ) {
			return false;
		}

		return $start;
	}

	/**
	 * Modifies the modal info content based on the hosting environment, job ID, and backup type.
	 *
	 * @param array $info_content The initial info content configuration to be potentially modified.
	 * @param int   $job_id The ID of the job used to determine the backup type. If null, 'full' type is used by default.
	 *
	 * @return array The modified or unmodified info content configuration.
	 */
	public function modal_info_content( array $info_content, int $job_id ) {
		if ( ! $this->is_hosting() ) {
			return $info_content;
		}

		if ( ! $job_id ) {
			$current_type = 'full';
		} else {
			$job_data = $this->option_adapter->get_job( $job_id );
			if ( ! $job_data ) {
				return $info_content;
			}
			$current_type = $this->get_backup_type( $job_data );
		}

		// phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
		$current_time  = current_time( 'timestamp' );
		$last_run_time = $this->get_last_run_time_from_jobs_by_type( $current_type );
		$avoid_time    = $this->get_avoid_time_by_type( $current_type );

		if ( $last_run_time + $avoid_time > $current_time ) {
			// translators: %1$s: date, %2$s: time.
			$date_string                  = sprintf( __( '%1$s at %2$s', 'backwpup' ), wp_date( get_option( 'date_format' ), $last_run_time + $avoid_time, new \DateTimeZone( 'UTC' ) ), wp_date( get_option( 'time_format' ), $last_run_time + $avoid_time, new \DateTimeZone( 'UTC' ) ) );
			$info_content['args']['type'] = 'alert';
			if ( 'full' === $current_type ) {
				// translators: %s: date.
				$info_content['args']['content'] = sprintf( __( 'Kinsta limit reached: one full site backup every 30 days. Next available: %s', 'backwpup' ), $date_string );
			} elseif ( 'db' === $current_type ) {
				// translators: %s: date.
				$info_content['args']['content'] = sprintf( __( 'Kinsta limit reached: only one database backup every 7 days. Next available: %s', 'backwpup' ), $date_string );
			} elseif ( 'file' === $current_type ) {
				// translators: %s: date.
				$info_content['args']['content'] = sprintf( __( 'Kinsta limit reached: only one file backup every 7 days. Next available: %s', 'backwpup' ), $date_string );
			}
		}

		return $info_content;
	}

	/**
	 * Modifies the modal button based on the hosting environment, job ID, and backup type.
	 *
	 * @param array $button The initial button configuration to be potentially modified.
	 * @param int   $job_id The ID of the job used to determine the backup type. If null, 'full' type is used by default.
	 *
	 * @return array The modified or unmodified button configuration.
	 */
	public function modal_button( array $button, int $job_id ) {
		if ( ! $this->is_hosting() ) {
			return $button;
		}

		if ( ! $job_id ) {
			$current_type = 'full';
		} else {
			$job_data = $this->option_adapter->get_job( $job_id );
			if ( ! $job_data ) {
				return $button;
			}
			$current_type = $this->get_backup_type( $job_data );
		}

		// phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
		$current_time  = current_time( 'timestamp' );
		$last_run_time = $this->get_last_run_time_from_jobs_by_type( $current_type );
		$avoid_time    = $this->get_avoid_time_by_type( $current_type );

		if ( $last_run_time + $avoid_time > $current_time ) {
			$button = [];
		}

		return $button;
	}

	/**
	 * Adjusts the frequency options based on the hosting environment and the backup type of a given job.
	 *
	 * @param array $options The initial frequency options available for the job.
	 * @param int   $job_id The ID of the job used to determine the applicable backup type.
	 *
	 * @return array The modified set of frequency options based on the backup type.
	 */
	public function frequency_options( array $options, int $job_id ) {
		if ( ! $this->is_hosting() ) {
			return $options;
		}

		$job_data = $this->option_adapter->get_job( $job_id );
		if ( $job_data ) {
			$current_type = $this->get_backup_type( $job_data );
		} else {
			// fallback to the lowest possible type.
			$current_type = 'file';
		}

		if ( 'full' === $current_type ) {
			unset( $options['weekly'], $options['daily'], $options['hourly'] );
		}
		if ( 'db' === $current_type || 'file' === $current_type ) {
			unset( $options['daily'], $options['hourly'] );
		}

		return $options;
	}

	/**
	 * Retrieves the list of subscribed events and their corresponding callbacks.
	 *
	 * @return array An associative array where the keys are event names and the values are arrays containing callback information.
	 */
	public static function get_subscribed_events() {
		return [
			'backwpup_can_job_start'                   => [ 'can_job_start', 10, 2 ],
			'backwpup_backup_now_modal_info_content'   => [ 'modal_info_content', 10, 2 ],
			'backwpup_backup_now_modal_button'         => [ 'modal_button', 10, 2 ],
			'backwpup_backup_select_frequency_options' => [ 'frequency_options', 10, 2 ],
		];
	}
}
