<?php

use WP_CLI\Formatter;

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
	 * @param $assocArgs
	 */
	public function decrypt( $args, $assocArgs ) {
		$key = '';
		if ( isset( $assocArgs['key'] ) ) {
			if ( is_file( $assocArgs['key'] ) ) {
				$key = file_get_contents( $assocArgs['key'], false );
			} else {
				$key = $assocArgs['key'];
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
			$archiveFile = $args[0];
		} else {
			WP_CLI::error( __( 'Archive file that should be decrypted can\'t be found!', 'backwpup' ) );
		}

		/** @var \Inpsyde\Restore\Api\Module\Decryption\Decrypter $decrypter */
		$decrypter = Inpsyde\BackWPup\Infrastructure\Restore\restore_container( 'decrypter' );
		if ( ! $decrypter->isEncrypted( $archiveFile ) ) {
			WP_CLI::error( __( 'Archive not needs decryption.', 'backwpup' ) );
		}

		try {
			$decrypter->decrypt( $key, $archiveFile );
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
	 * @param $assocArgs
	 */
	public function encrypt( $args, $assocArgs ) {
		$aesIv     = \phpseclib3\Crypt\Random::string( 16 );
		$rsaPubKey = '';
		$type      = Inpsyde\BackWPup\Infrastructure\Security\EncryptionStream::TYPE_SYMMETRIC;
		if ( isset( $assocArgs['key'] ) ) {
			if ( is_file( $assocArgs['key'] ) ) {
				$key       = \phpseclib3\Crypt\Random::string( 32 );
				$rsaPubKey = file_get_contents( $assocArgs['key'], false );
				$type      = Inpsyde\BackWPup\Infrastructure\Security\EncryptionStream::TYPE_ASYMMETRIC;
			} else {
				$key = pack( 'H*', $assocArgs['key'] );
			}
		} else {
			$encryptionType = get_site_option( 'backwpup_cfg_encryption' );
			if ( $encryptionType !== 'symmetric' ) {
				$key       = \phpseclib3\Crypt\Random::string( 32 );
				$rsaPubKey = get_site_option( 'backwpup_cfg_publickey' );
				$type      = Inpsyde\BackWPup\Infrastructure\Security\EncryptionStream::TYPE_ASYMMETRIC;
			} else {
				$key = pack( 'H*', get_site_option( 'backwpup_cfg_encryptionkey' ) );
			}
		}

		if ( ! $key ) {
			WP_CLI::error( __( 'No Key provided or stored in settings for encryption!', 'backwpup' ) );
		}

		if ( $args[0] && is_file( $args[0] ) ) {
			$archiveFile = $args[0];
		} else {
			WP_CLI::error( __( 'Archive file that should be encrypted can\'t be found!', 'backwpup' ) );
		}

		if ( $type === Inpsyde\BackWPup\Infrastructure\Security\EncryptionStream::TYPE_SYMMETRIC ) {
			WP_CLI::log( __( 'Symmetric encryption will be used for the archive.', 'backwpup' ) );
		} else {
			WP_CLI::log( __( 'Asymmetric encryption will be used for the archive.', 'backwpup' ) );
		}

		try {
			$fileIn = GuzzleHttp\Psr7\Utils::streamFor( GuzzleHttp\Psr7\Utils::tryFopen( $archiveFile, 'r' ) );
		} catch ( \RuntimeException $e ) {
			WP_CLI::error( __( 'Cannot open the archive for reading. Aborting encryption.', 'backwpup' ) );
		}

		try {
			$fileOut = GuzzleHttp\Psr7\Utils::tryFopen( $archiveFile . '.encrypted', 'a+' );
		} catch ( \RuntimeException $e ) {
			WP_CLI::error( __( 'Cannot write the encrypted archive. Aborting encryption.', 'backwpup' ) );
		}

		$encryptor = new Inpsyde\BackWPup\Infrastructure\Security\EncryptionStream(
			$aesIv,
			$key,
			GuzzleHttp\Psr7\Utils::streamFor( $fileOut ),
			$rsaPubKey
		);

		if ( ! $encryptor ) {
			WP_CLI::error( __( 'Could not initialize encryptor.', 'backwpup' ) );
		}

		$blockSize = 128 * 1024;
		while ( ! $fileIn->eof() ) {
			$data = $fileIn->read( $blockSize );
			$encryptor->write( $data );
		}
		$fileIn->close();
		$encryptor->close();

		if ( ! unlink( $archiveFile ) ) {
			WP_CLI::error( __( 'Unable to delete unencrypted archive.', 'backwpup' ) );
		}
		if ( ! rename( $archiveFile . '.encrypted', $archiveFile ) ) {
			WP_CLI::error( __( 'Unable to rename encrypted archive.', 'backwpup' ) );
		}
		WP_CLI::success( __( 'Archive has been successfully encrypted.', 'backwpup' ) );
	}
}
