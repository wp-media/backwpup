<?php

use WP_CLI\Formatter;
use GuzzleHttp\Psr7\Utils;

/**
 * Class for WP-CLI commands.
 */
class BackWPup_WP_CLI extends WP_CLI_Command
{
    /**
     * Start a BackWPup job.
     *
     * # EXAMPLES
     *
     *   backwpup start 13
     *   backwpup start --jobid=13 (deprecated)
     *
     * @param $args
     * @param $assoc_args
     */
    public function start($args, $assoc_args)
    {
        $jobid = 0;

        if (file_exists(BackWPup::get_plugin_data('running_file'))) {
            WP_CLI::error(__('A job is already running.', 'backwpup'));
        }

        if (isset($assoc_args['jobid'])) {
            $jobid = (int) $assoc_args['jobid'];
        }

        if (!empty($args[0])) {
            $jobid = (int) $args[0];
        }

        if (empty($jobid)) {
            WP_CLI::error(__('No job ID specified!', 'backwpup'));
        }

        $jobids = BackWPup_Option::get_job_ids();
        if (!in_array($jobid, $jobids, true)) {
            WP_CLI::error(__('Job ID does not exist!', 'backwpup'));
        }

        BackWPup_Job::start_cli($jobid);
    }

    /**
     *  Abort a working BackWPup Job.
     */
    public function abort($args, $assoc_args)
    {
        if (!file_exists(BackWPup::get_plugin_data('running_file'))) {
            WP_CLI::error(__('Nothing to abort!', 'backwpup'));
        }

        //abort
        BackWPup_Job::user_abort();
        WP_CLI::success(__('Job will be terminated.', 'backwpup'));
    }

    /**
     * Display a List of Jobs.
     */
    public function jobs($args, $assoc_args)
    {
        $formatter_args = [
            'format' => 'table',
            'fields' => [
                'Job ID',
                'Name',
            ],
            'field' => null,
        ];

        $items = [];

        $formatter = new Formatter($formatter_args);

        $jobids = BackWPup_Option::get_job_ids();

        foreach ($jobids as $jobid) {
            $items[] = [
                'Job ID' => $jobid,
                'Name' => BackWPup_Option::get($jobid, 'name'),
            ];
        }

        $formatter->display_items($items);
    }

    /**
     * See Status of a working job.
     *
     * @param $args
     * @param $assoc_args
     */
    public function working($args, $assoc_args)
    {
        $job_object = BackWPup_Job::get_working_data();

        if (!is_object($job_object)) {
            WP_CLI::error(__('No job running', 'backwpup'));
        }

        $formatter_args = [
            'format' => 'table',
            'fields' => [
                'JobID',
                'Name',
                'Warnings',
                'Errors',
                'On Step',
                'Done',
            ],
            'field' => null,
        ];

        $formatter = new Formatter($formatter_args);

        $items = [];
        $items[] = [
            'JobID' => $job_object->job['jobid'],
            'Name' => $job_object->job['name'],
            'Warnings' => $job_object->warnings,
            'Errors' => $job_object->errors,
            'On Step' => $job_object->steps_data[$job_object->step_working]['NAME'],
            'Done' => $job_object->step_percent . ' / ' . $job_object->substep_percent,
            'Last message' => str_replace('&hellip;', '...', strip_tags((string) $job_object->lastmsg)),
        ];

        $formatter->display_items($items);

        WP_CLI::log('Last Message: ' . str_replace('&hellip;', '...', strip_tags((string) $job_object->lastmsg)));
    }

	/**
	 * Decrypt an BackWPup archive.
	 *
     * # EXAMPLES
	 *
	 *   backwpup decrypt archiv.zip
	 *   backwpup decrypt achriv.tar.gz --key="ABCDEFGHIJKLMNOPQRSTUVWXYZ123456"
	 *   backwpup decrypt archiv.zip --key="./id_rsa_backwpup.pri"
	 *
	 * @param $args
	 * @param $assoc_args
	 */
	public function decrypt( $args, $assoc_args ) {
		$key = '';
		if ( isset( $assoc_args['key'] ) ) {
			if ( is_file( $assoc_args['key'] ) ) {
				$key = file_get_contents( $assoc_args['key'], false ); // phpcs:ignore
			} else {
				$key = $assoc_args['key'];
			}
		} else {
			$decryptionType = get_site_option( 'backwpup_cfg_encryption' );
			if ( $decryptionType === 'symmetric' ) {
				$key = get_site_option( 'backwpup_cfg_encryptionkey' );
			}
		}

		if ( ! $key ) {
			WP_CLI::error( __( 'No Key provided or stored in settings for decryption!', 'backwpup' ) );
		}

		if ( $args[0] && is_file( $args[0] ) ) {
			$archive_file = $args[0];
		} else {
			WP_CLI::error( __( 'Archive file that should be decrypted can\'t be found!', 'backwpup' ) );
		}

		/** @var \Inpsyde\Restore\Api\Module\Decryption\Decrypter $decrypter */
		$decrypter = Inpsyde\BackWPup\Infrastructure\Restore\restore_container( 'decrypter' );
		if ( ! $decrypter->isEncrypted( $archive_file ) ) {
			WP_CLI::error( __( 'Archive not needs decryption.', 'backwpup' ) );
		}

		try {
			$decrypter->decrypt( $key, $archive_file );
		} catch ( \Exception $e ) {
			WP_CLI::error( sprintf( __( 'Cannot decrypt: %s', 'backwpup' ), $e->getMessage() ) );
		}

		WP_CLI::success( __( 'Archive has been successfully decrypted.', 'backwpup' ) );
	}

	/**
	 * Encrypt an BackWPup archive.
	 *
     * # EXAMPLES
	 *
	 *   backwpup encrypt achriv.tar.gz
	 *   backwpup encrypt achriv.tar.gz -key="ABCDEFGHIJKLMNOPQRSTUVWXYZ123456"
	 *   backwpup encrypt archiv.zip -keyfile="./id_rsa_backwpup.pub"
	 *
	 * @param $args
	 * @param $assoc_args
	 */
	public function encrypt( $args, $assoc_args ) {
		$aes_iv      = \phpseclib3\Crypt\Random::string( 16 );
		$rsa_pub_key = '';
		$type        = Inpsyde\BackWPup\Infrastructure\Security\EncryptionStream::TYPE_SYMMETRIC;
		if ( isset( $assoc_args['key'] ) ) {
			if ( is_file( $assoc_args['key'] ) ) {
				$key         = \phpseclib3\Crypt\Random::string( 32 );
				$rsa_pub_key = file_get_contents( $assoc_args['key'], false ); // phpcs:ignore
				$type        = Inpsyde\BackWPup\Infrastructure\Security\EncryptionStream::TYPE_ASYMMETRIC;
			} else {
				$key = pack( 'H*', $assoc_args['key'] );
			}
		} else {
			$encryption_type = get_site_option( 'backwpup_cfg_encryption' );
			if ( 'symmetric' !== $encryption_type ) {
				$key         = \phpseclib3\Crypt\Random::string( 32 );
				$rsa_pub_key = get_site_option( 'backwpup_cfg_publickey' );
				$type        = Inpsyde\BackWPup\Infrastructure\Security\EncryptionStream::TYPE_ASYMMETRIC;
			} else {
				$key = pack( 'H*', get_site_option( 'backwpup_cfg_encryptionkey' ) );
			}
		}

		if ( ! $key ) {
			WP_CLI::error( __( 'No Key provided or stored in settings for encryption!', 'backwpup' ) );
		}

		if ( $args[0] && is_file( $args[0] ) ) {
			$archive_file = $args[0];
		} else {
			WP_CLI::error( __( 'Archive file that should be encrypted can\'t be found!', 'backwpup' ) );
		}

		if ( $type === Inpsyde\BackWPup\Infrastructure\Security\EncryptionStream::TYPE_SYMMETRIC ) {
			WP_CLI::log( __( 'Symmetric encryption will be used for the archive.', 'backwpup' ) );
		} else {
			WP_CLI::log( __( 'Asymmetric encryption will be used for the archive.', 'backwpup' ) );
		}

		try {
			$file_in = Utils::streamFor( Utils::tryFopen( $archive_file, 'r' ) );
		} catch ( \RuntimeException $e ) {
			WP_CLI::error( __( 'Cannot open the archive for reading. Aborting encryption.', 'backwpup' ) );
		}

		try {
			$file_out = Utils::tryFopen( $archive_file . '.encrypted', 'a+' );
		} catch ( \RuntimeException $e ) {
			WP_CLI::error( __( 'Cannot write the encrypted archive. Aborting encryption.', 'backwpup' ) );
		}

		$encryptor = new Inpsyde\BackWPup\Infrastructure\Security\EncryptionStream(
			$aes_iv,
			$key,
			Utils::streamFor( $file_out ),
			$rsa_pub_key
		);

		if ( ! $encryptor ) {
			WP_CLI::error( __( 'Could not initialize encryptor.', 'backwpup' ) );
		}

		$block_size = 128 * 1024;
		while ( ! $file_in->eof() ) {
			$data = $file_in->read( $block_size );
			$encryptor->write( $data );
		}
		$file_in->close();
		$encryptor->close();

		if ( ! unlink( $archive_file ) ) { // phpcs:ignore
			WP_CLI::error( __( 'Unable to delete unencrypted archive.', 'backwpup' ) );
		}
		if ( ! rename( $archive_file . '.encrypted', $archive_file ) ) { // phpcs:ignore
			WP_CLI::error( __( 'Unable to rename encrypted archive.', 'backwpup' ) );
		}
		WP_CLI::success( __( 'Archive has been successfully encrypted.', 'backwpup' ) );
	}

	/**
	 * Activate Legacy Jobs; Filter by all or selected job IDs
	 *
	 * @subcommand activate-legacy-job
	 *
	 * @param array $args        Positional arguments passed to the command.
	 * @param array $assoc_args  Associative arguments passed to the command (e.g --type, --jobIds).
	 * @return void
	 */
	public function activate_legacy_job( array $args, array $assoc_args ): void {
		// Check if mandatory flag exist.
		if ( ! isset( $assoc_args['type'] ) ) {
			WP_CLI::error( __( 'The --type flag is mandatory and must be specified.', 'backwpup' ) );
		}

		// Check if provided type is valid (must be either wpcron or link).
		if ( ! in_array( $assoc_args['type'], [ 'wpcron', 'link' ], true ) ) {
			WP_CLI::error( __( 'Invalid value for --type flag.', 'backwpup' ) );
		}

		$job_ids = isset( $assoc_args['jobIds'] ) ? explode( ',', $assoc_args['jobIds'] ) : [];

		// Check that all job ids are numeric.
		if ( ! empty( $job_ids ) && count( $job_ids ) !== count( array_filter( $job_ids, 'is_numeric' ) ) ) {
			WP_CLI::error( __( 'Invalid value for --jobIds flag provided.', 'backwpup' ) );
		}

		// Convert string ids to proper integers.
		$job_ids = array_map( 'intval', $job_ids );

		// Sanitize type value.
		$type = sanitize_text_field( wp_unslash( $assoc_args['type'] ) );

		WP_CLI::log( __( 'Activating legacy jobs.', 'backwpup' ) );

		// Get all jobs.
		$jobs = get_site_option( 'backwpup_jobs', [] );

		[ $filtered_jobs, $total_filtered_jobs ] = $this->filter_jobs_to_be_activated( $jobs, $job_ids, $type );

		// Bail out early if number of jobs to be updated is still 0.
		if ( 0 === $total_filtered_jobs ) {
			WP_CLI::warning( 'No job was updated.', 'backwpup' );
			return;
		}

		// Update jobs.
		update_site_option( 'backwpup_jobs', $filtered_jobs );
		WP_CLI::success(
			sprintf(
				// translators: %1$d = Number of jobs, %2$s = Success message.
				_n( '%1$d job %2$s', '%1$d jobs %2$s', $total_filtered_jobs, 'backwpup' ),
				$total_filtered_jobs,
				esc_html__( 'successfully activated', 'backwpup' )
			)
			);
	}

	/**
	 * Filters jobs to be activated based on job IDs and activation type.
	 *
	 * @param array  $jobs     Array of jobs to filter.
	 * @param array  $job_ids  Array of job IDs to activate. If empty, all legacy jobs will be activated.
	 * @param string $type     The activation type ('wpcron' or 'link').
	 *
	 * @return array An array containing the filtered jobs and the count of updated jobs.
	 */
	private function filter_jobs_to_be_activated( array $jobs, array $job_ids, string $type ) {
		$jobs_updated = 0;

		// Go over jobs and update only those with empty activeType and legacy set to 1.
		$jobs = array_map(
			function ( $job ) use ( $job_ids, $type, &$jobs_updated ) {
				// Filter out jobs that already have activetype set and not empty either legacy or not.
				if ( isset( $job['activetype'] ) && '' !== $job['activetype'] ) {
					return $job;
				}

				// Filter out non-legacy jobs.
				if ( ! isset( $job['legacy'] ) || ! $job['legacy'] ) {
						return $job;
				}

				// Activate legacy jobs with specified job ids.
				if ( ! empty( $job_ids ) && in_array( $job['jobid'], $job_ids, true ) ) {
					$job['activetype'] = $type;

					// Schedule next run for type of wpcron.
					if ( 'wpcron' === $type ) {
						wp_schedule_single_event( BackWPup_Cron::cron_next( $job['cron'] ), 'backwpup_cron', [ 'arg' => $job['jobid'] ] );
					}

					$jobs_updated++;
					return $job;
				}

				// Activate all legacy jobs.
				if ( empty( $job_ids ) ) {
					$job['activetype'] = $type;

					// Schedule next run for type of wpcron.
					if ( 'wpcron' === $type ) {
						wp_schedule_single_event( BackWPup_Cron::cron_next( $job['cron'] ), 'backwpup_cron', [ 'arg' => $job['jobid'] ] );
					}

					$jobs_updated++;
					return $job;
				}

				return $job;
			},
		$jobs
		);

		return [
			$jobs,
			$jobs_updated,
		];
	}
}
