<?php

/**
 * Class BackWPup_System_Tests_Runner
 */
class BackWPup_System_Tests_Runner {
	/**
	 * Errors
	 *
	 * @var string[] A list of errors
	 */
	private $errors = array();

	/**
	 * Warning
	 *
	 * @var string[] A list of warnings
	 */
	private $warnings = array();

	/**
	 * System Requirements
	 *
	 * @var BackWPup_System_Requirements
	 */
	private $requirements;

	/**
	 * System Tests Instance
	 *
	 * @var BackWPup_System_Tests
	 */
	private $system_tests;

	/**
	 * Suppress Success Message
	 *
	 * @var bool To suppress or not the success message
	 */
	private $suppress_success_message;

	/**
	 * BackWPup_System_Tests_Runner constructor
	 *
	 * @param BackWPup_System_Requirements $requirements             The instance.
	 * @param BackWPup_System_Tests        $system_tests             The instance.
	 * @param bool                         $suppress_success_message To suppress or not the success message.
	 */
	public function __construct(
		BackWPup_System_Requirements $requirements,
		BackWPup_System_Tests $system_tests,
		$suppress_success_message
	) {

		$this->requirements             = $requirements;
		$this->system_tests             = $system_tests;
		$this->suppress_success_message = $suppress_success_message;
	}

	/**
	 * Run Tests
	 *
	 * @return void
	 */
	public function run() {

		$extension_rec = _x(
			'We recommend to install the %1$s extension to generate %2$s archives.',
			'%1 = extension name, %2 = file suffix',
			'backwpup'
		);
		$raw_response  = BackWPup_Job::get_jobrun_url( 'test' );

		// WP Version check.
		if ( ! $this->system_tests->is_wp_version_compatible() ) {

			$this->errors[] = $this->message( sprintf( __(
				'You must run WordPress version %1$s or higher to use this plugin. You are using version %2$s now.',
				'backwpup'
			),
				$this->requirements->wp_minimum_version(),
				BackWPup::get_plugin_data( 'wp_version' )
			), 'error' );
		}

		// PHP Version check.
		if ( ! $this->system_tests->is_php_version_compatible() ) {
			$this->errors[] = $this->message( sprintf( __(
				'We recommend to run a PHP version above %1$s to get the full plugin functionality. You are using version %2$s now.',
				'backwpup'
			),
				$this->requirements->php_minimum_version(),
				PHP_VERSION
			), 'error' );
		}

		$db_version = backwpup_wpdb()->db_version();
		// Mysql Version check.
		if ( ! $this->system_tests->is_database_compatible() ) {
			$this->errors[] = $this->message( sprintf( __(
				'You must have the MySQLi extension installed and a MySQL server version of %1$s or higher to use this plugin. You are using version %2$s now.',
				'backwpup'
			),
				$this->requirements->mysql_minimum_version(),
				$db_version
			), 'error' );
		}

		// Curl check.
		if ( ! $this->system_tests->test_curl_init() ) {
			$this->errors[] = $this->message(
				__( 'PHP cURL extension must be installed to use the full plugin functionality.', 'backwpup' ),
				'error'
			);
		}

		// ZIPArchive.
		if ( ! $this->system_tests->test_zip_archive() ) {
			$this->warnings[] = $this->message( sprintf( $extension_rec, 'PHP ZIP', '.zip' ), 'warning' );
		}

		// GZ.
		if ( ! $this->system_tests->support_gzip() ) {
			$this->warnings[] = $this->message( sprintf( $extension_rec, 'PHP GZ', '.tar.gz' ), 'warning' );
		}

		// Safe mode.
		if ( $this->system_tests->is_save_mode_activated() ) {
			$this->errors[] = $this->message(
				str_replace( '\"', '"', sprintf(
					_x( 'Please disable the deprecated <a href="%s">PHP safe mode</a>.', 'Link to PHP manual', 'backwpup' ),
					'http://php.net/manual/en/features.safe-mode.php'
				) ),
				'error'
			);
		}

		// FTP.
		if ( ! $this->system_tests->is_ftp_supported() ) {
			$this->warnings[] = $this->message(
				esc_html__( 'We recommend to install the PHP FTP extension to use the FTP backup destination.', 'backwpup' ),
				'warning'
			);
		}

		// Temp dir.
		$temp_dir_state = $this->system_tests->temp_dir_state();
		if ( '' !== $temp_dir_state ) {
			$this->errors[] = $this->message( esc_html( $temp_dir_state ), 'error' );
		}

		// Log dir.
		$log_folder_message = $this->system_tests->log_folder_state();
		if ( ! empty( $log_folder_message ) ) {
			$this->errors[] = $this->message( esc_html( $log_folder_message ), 'error' );
		}

		if ( is_wp_error( $raw_response ) ) {
			$this->warnings[] = $this->message( esc_html( sprintf( __(
				'The HTTP response test result is an error: "%s".',
				'backwpup'
			),
				$raw_response->get_error_message()
			) ), 'warning' );
		}

		if ( 200 != wp_remote_retrieve_response_code( $raw_response )
		     && 204 != wp_remote_retrieve_response_code( $raw_response )
		) {
			$this->warnings[] = $this->message( sprintf( __(
				'The HTTP response test result is a wrong HTTP status: %s. It should be status 200.',
				'backwpup'
			), wp_remote_retrieve_response_code( $raw_response )
			), 'warning' );
		}

		// Cron test.
		$schedule_cron = $this->try_schedule_cron();
		if ( $schedule_cron ) {
			$this->errors[] = $this->message( $schedule_cron, 'error' );
		}

		$this->maybe_show_errors( $this->errors );
		$this->maybe_show_warnings( $this->warnings );

		if ( ! $this->errors && ! $this->warnings && ! $this->suppress_success_message ) {
			$this->alert(
				esc_html__( 'Yeah!', 'backwpup' ),
				esc_html__( 'All tests passed without errors.', 'backwpup' ),
				'success'
			);
		}
	}

	/**
	 * Schedule Cron
	 *
	 * @return string The message in case the cron cannot be scheduled
	 */
	private function try_schedule_cron() {

		$next_run = wp_next_scheduled( 'wp_update_plugins' );

		if ( ! $next_run ) {
			$next_run = wp_next_scheduled( 'wp_version_check' );
		}
		if ( ! $next_run ) {
			$next_run = wp_next_scheduled( 'wp_update_themes' );
		}
		if ( ! $next_run ) {
			$next_run = wp_next_scheduled( 'wp_scheduled_delete' );
		}

		if ( $next_run && $next_run < ( time() - 3600 * 12 ) ) {
			return $this->message( esc_html__(
				'WP-Cron seems to be broken. But it is needed to run scheduled jobs.',
				'backwpup'
			), 'error' );
		}
	}

	/**
	 * Show Error Messages
	 *
	 * @param string[] $errors An array of error messages.
	 *
	 * @return void
	 */
	private function maybe_show_errors( $errors ) {

		if ( $errors ) {
			$this->alert(
				'',
				esc_html__( 'There are errors. Please correct them, or BackWPup cannot work.', 'backwpup' ),
				'error',
				$this->show_messages_list( $errors )
			);

			// Clean the list.
			$this->errors = array();
		}
	}

	/**
	 * Show Warning Messages
	 *
	 * @param string[] $warnings A list of warning messages.
	 *
	 * @return void
	 */
	private function maybe_show_warnings( $warnings ) {

		if ( $warnings ) {
			$this->alert(
				'',
				esc_html__( 'There are some warnings. BackWPup will work, but with limitations.', 'backwpup' ),
				'warning',
				$this->show_messages_list( $warnings )
			);

			//$this->show_messages_list( $warnings );
			// Clean the list.
			$this->warnings = array();
		}
	}

	/**
	 * Build Message
	 *
	 * @param string $message The message string.
	 * @param string $type    The type of the message. E.g. 'error', 'warning', 'success'.
	 *
	 * @return string The markup
	 */
	private function message( $message, $type ) {

		return '<p class="' . sanitize_key( $type ) . '">' . $message . '</p>';
	}

	/**
	 * Returns Messages List
	 *
	 * @param string[] $list A list of messages to output.
	 *
	 * @return string
	 */
	private function show_messages_list( array $list ) {

		$output = '<ul>';
		foreach ( $list as $message ) {
			$output .= '<li>' . wp_kses_post( $message ) . '</li>';
		}
		$output .= '</ul>';

		return $output;
	}

	/**
	 * WordPress Alert
	 *
	 * @param string $title   The title for the alert.
	 * @param string $message The alert message.
	 * @param string $type    The type of the alert.
	 * @param string $more_info More information.
	 */
	private function alert( $title, $message, $type, $more_info = '' ) {

		if ( ! $message ) {
			return;
		}

		echo '<div class="notice notice-' . sanitize_key( $type ) . '">';
		if ( $title ) {
			echo '<p><strong>' . esc_html( $title ) . '</strong></p>';
		}
		echo '<p>' . wp_kses_post( $message ) . '</p>';
		echo wp_kses_post( $more_info );
		echo '</div>';
	}
}
