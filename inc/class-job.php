<?php

use GuzzleHttp\Psr7\Utils;
use Inpsyde\BackWPup\Infrastructure\Security\EncryptionStream;
use ParagonIE\ConstantTime\Base64;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class in that the BackWPup job runs.
 */
class BackWPup_Job {

	public const ENCRYPTION_SYMMETRIC  = 'symmetric';
	public const ENCRYPTION_ASYMMETRIC = 'asymmetric';

	/**
	 * Job settings.
	 *
	 * @var array
	 */
	public $job = [];

	/**
	 * Job start timestamp.
	 *
	 * @var int
	 */
	public $start_time = 0;

	/**
	 * Log file path.
	 *
	 * @var string
	 */
	public $logfile = '';
	/**
	 * Temporary values.
	 *
	 * @var array
	 */
	public $temp = [];
	/**
	 * Backup folder path.
	 *
	 * @var string
	 */
	public $backup_folder = '';
	/**
	 * Backup archive file name.
	 *
	 * @var string
	 */
	public $backup_file = '';
	/**
	 * Backup archive file size.
	 *
	 * @var int
	 */
	public $backup_filesize = 0;
	/**
	 * Script process ID.
	 *
	 * @var int
	 */
	public $pid = 0;
	/**
	 * Timestamp of the last .running update.
	 *
	 * @var float
	 */
	public $timestamp_last_update = 0;
	/**
	 * Warning count.
	 *
	 * @var int
	 */
	public $warnings = 0;
	/**
	 * Error count.
	 *
	 * @var int
	 */
	public $errors = 0;
	/**
	 * Last log notice message.
	 *
	 * @var string
	 */
	public $lastmsg = '';
	/**
	 * Last log error/warning message.
	 *
	 * @var string
	 */
	public $lasterrormsg = '';
	/**
	 * Steps to do.
	 *
	 * @var array
	 */
	public $steps_todo = [ 'CREATE' ];
	/**
	 * Steps already done.
	 *
	 * @var array
	 */
	public $steps_done = [];
	/**
	 * Steps data.
	 *
	 * @var array
	 */
	public $steps_data = [];
	/**
	 * Current step.
	 *
	 * @var string
	 */
	public $step_working = 'CREATE';
	/**
	 * Number of substeps to do.
	 *
	 * @var int
	 */
	public $substeps_todo = 0;
	/**
	 * Number of substeps done.
	 *
	 * @var int
	 */
	public $substeps_done = 0;
	/**
	 * Percent of steps done.
	 *
	 * @var int
	 */
	public $step_percent = 1;
	/**
	 * Percent of substeps done.
	 *
	 * @var int
	 */
	public $substep_percent = 1;
	/**
	 * Additional files to backup.
	 *
	 * @var string[]
	 */
	public $additional_files_to_backup = [];
	/**
	 * Files/folders to exclude from backup.
	 *
	 * @var array
	 */
	public $exclude_from_backup = [];
	/**
	 * Count of affected files.
	 *
	 * @var int
	 */
	public $count_files = 0;
	/**
	 * Total size of affected files.
	 *
	 * @var int
	 */
	public $count_files_size = 0;
	/**
	 * Count of affected folders.
	 *
	 * @var int
	 */
	public $count_folder = 0;
	/**
	 * If job aborted from user.
	 *
	 * @var bool
	 */
	public $user_abort = false;
	/**
	 * A uniqid ID uniqid('', true); to identify process.
	 *
	 * @var string
	 */
	public $uniqid = '';
	/**
	 * Script start timestamp.
	 *
	 * @var float
	 */
	private $timestamp_script_start = 0;
	/**
	 * Stores data that will only used in a single run.
	 *
	 * @var array
	 */
	private $run = [];
	/**
	 * Logging level.
	 *
	 * @var string
	 */
	private $log_level = 'normal';

	/**
	 * Signal handler value.
	 *
	 * @var int
	 */
	private $signal = 0;

	/**
	 * Start the job.
	 *
	 * @param string $starttype Start type.
	 * @param int    $jobid Job ID.
	 *
	 * @return void
	 */
	public static function start_http( $starttype, $jobid = 0 ) {
		if ( 'restart' !== $starttype ) {
			// Check job ID exists.
			if ( (int) BackWPup_Option::get( $jobid, 'jobid' ) !== (int) $jobid ) {
				// translators: %s = job id.
				BackWPup_Admin::message( sprintf( __( 'Job with ID %s not exists', 'backwpup' ), $jobid ), true );
				return;
			}

			// Check folders.
			$log_folder          = get_site_option( 'backwpup_cfg_logfolder' );
			$folder_message_log  = BackWPup_File::check_folder( BackWPup_File::get_absolute_path( $log_folder ) );
			$folder_message_temp = BackWPup_File::check_folder( BackWPup::get_plugin_data( 'TEMP' ), true );
			if ( ! empty( $folder_message_log ) || ! empty( $folder_message_temp ) ) {
				BackWPup_Admin::message( $folder_message_log, true );
				BackWPup_Admin::message( $folder_message_temp, true );

				return;
			}

			$filesystem = backwpup_wpfilesystem();
			$written    = $filesystem->put_contents(
				trailingslashit( BackWPup::get_plugin_data( 'TEMP' ) ) . '.backwpup_job_started',
				$starttype . "\n"
			);
			if ( false === $written ) {
				BackWPup_Admin::message( __( 'Can`t write a file to temporary folder', 'backwpup' ), true );
				return;
			}
		}

		// Redirect.
		if ( 'runnowalt' === $starttype ) {
			ob_start();
			wp_safe_redirect( add_query_arg( [ 'page' => 'backwpupjobs' ], network_admin_url( 'admin.php' ) ) );
			echo ' ';
			flush();
			$level = ob_get_level();
			if ( $level ) {
				for ( $i = 0; $i < $level; ++$i ) {
					ob_end_clean();
				}
			}
		}

		// Prevent doubled running jobs on HTTP requests.
		$random = random_int( 10, 90 ) * 10000;
		usleep( $random );

		// Check running job.
		$backwpup_job_object = self::get_working_data();
		// Start class.
		$starttype_exists = in_array( $starttype, [ 'runnow', 'runnowalt', 'runext', 'cronrun' ], true );
		if ( ! $backwpup_job_object && $starttype_exists && $jobid ) {
			// Schedule restart event.
			wp_schedule_single_event( time() + 60, 'backwpup_cron', [ 'arg' => 'restart' ] );
			// Start job.
			$backwpup_job_object = new self();
			$backwpup_job_object->create( $starttype, $jobid );
		}
		if ( $backwpup_job_object ) {
			$backwpup_job_object->run();
		}
	}

	/**
	 * Get data off a working job.
	 *
	 * @return bool|object BackWPup_Job Object or Bool if file not exits
	 */
	public static function get_working_data() {
		clearstatcache( true, BackWPup::get_plugin_data( 'running_file' ) );

		if ( ! file_exists( BackWPup::get_plugin_data( 'running_file' ) ) ) {
			return false;
		}

		$filesystem = backwpup_wpfilesystem();
		$file_data  = $filesystem->get_contents( BackWPup::get_plugin_data( 'running_file' ) );
		if ( false === $file_data ) {
			return false;
		}
		$file_data = substr( $file_data, 8 );
		if ( empty( $file_data ) ) {
			return false;
		}

		$job_data = json_decode( $file_data, true );
		if ( ! empty( $job_data ) ) {
			return self::init( $job_data );
		}

		return false;
	}

	/**
	 * Get data for the current step.
	 *
	 * @param string $key Data key.
	 *
	 * @return string|int|null
	 */
	public function get_step_data( string $key ) {
		return $this->steps_data[ $this->step_working ][ $key ] ?? null;
	}

	/**
	 * Set data for the current step.
	 *
	 * @param string     $key Data key.
	 * @param int|string $value Data value.
	 */
	public function set_step_data( string $key, $value ): void {
		$this->steps_data[ $this->step_working ][ $key ] = $value;
	}

	/**
	 * This starts or restarts the job working.
	 *
	 * @param string    $start_type Start types are 'runnow', 'runnowalt', 'cronrun', 'runext', 'runcli'.
	 * @param array|int $job_id     The job ID to start.
	 */
	private function create( $start_type, $job_id = 0 ) {
		/**
		 * Database connection.
		 *
		 * @var wpdb $wpdb
		 */
		global $wpdb;

		// Check start type.
		if ( ! in_array( $start_type, [ 'runnow', 'runnowalt', 'cronrun', 'runext', 'runcli' ], true ) ) {
			return;
		}

		if ( $job_id ) {
			$this->job = BackWPup_Option::get_job( $job_id );
		} else {
			return;
		}

		/**
		 * Filter to avoid starting of the job.
		 *
		 * @param bool $start Start the job.
		 * @param array $job Job data.
		 * @param string $start_type Start type.
		 * @return bool Start the job.
		 */
		$start = wpm_apply_filters_typed( 'boolean', 'backwpup_can_job_start', true, $this->job, $start_type );
		if ( ! $start ) {
			// translators: %s = job name.
			$error_message = wpm_apply_filters_typed( 'string', 'backwpup_job_not_started_error_message', sprintf( __( 'Start of job "%s" forbidden!', 'backwpup' ), $this->job['name'] ), $this->job['jobid'] );
			if ( 'runcli' === $start_type && defined( 'WP_CLI' ) && WP_CLI ) {
				\WP_CLI::error( $error_message );
			}
			BackWPup_Admin::message( $error_message, true );
			return;
		}

		$this->start_time = time() + (int) ( (float) get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
		$this->lastmsg    = __( 'Starting job', 'backwpup' );
		// Set logfile.
		$log_folder    = get_site_option( 'backwpup_cfg_logfolder' );
		$log_folder    = BackWPup_File::get_absolute_path( $log_folder );
		$this->logfile = $log_folder . 'backwpup_log_' . BackWPup::get_generated_hash( 6 ) . '_' . wp_date(
			'Y-m-d_H-i-s',
			time()
		) . '.html';
		// Write settings to job.
		BackWPup_Option::update( $this->job['jobid'], 'lastrun', $this->start_time );
		BackWPup_Option::update( $this->job['jobid'], 'logfile', $this->logfile ); // Set current logfile.
		BackWPup_Option::update( $this->job['jobid'], 'lastbackupdownloadurl', '' );
		// Set needed job values.
		$this->timestamp_last_update = microtime( true );
		/**
		 * Filter to exclude files from backup.
		 *
		 * @param array $excluded_files List of excluded files.
		 */
		$this->exclude_from_backup = wpm_apply_filters_typed(
			'array',
			'backwpup_file_exclude',
			explode( ',', trim( (string) $this->job['fileexclude'] ) )
		);
		$this->exclude_from_backup = array_merge(
			$this->exclude_from_backup,
			[ '.tmp','.svn','.git','desktop.ini','.DS_Store','/node_modules/' ]
		);
		$this->exclude_from_backup = array_unique( $this->exclude_from_backup );
		// Setup job steps.
		$this->steps_data['CREATE']['CALLBACK'] = '';
		$this->steps_data['CREATE']['NAME']     = __( 'Job Start', 'backwpup' );
		$this->steps_data['CREATE']['STEP_TRY'] = 0;
		// Add job types file.
		$job_need_dest = false;
		$job_types     = BackWPup::get_job_types();
		if ( $job_types ) {
			/**
			 * Job type class.
			 *
			 * @var BackWPup_JobTypes $job_type_class
			 */
			foreach ( $job_types as $id => $job_type_class ) {
				if ( in_array( $id, $this->job['type'], true ) && $job_type_class->creates_file() ) {
					$this->steps_todo[]                                = 'JOB_' . $id;
					$this->steps_data[ 'JOB_' . $id ]['NAME']          = $job_type_class->info['description'];
					$this->steps_data[ 'JOB_' . $id ]['STEP_TRY']      = 0;
					$this->steps_data[ 'JOB_' . $id ]['SAVE_STEP_TRY'] = 0;
					$job_need_dest                                     = true;
				}
			}
		}
		// Add destinations and create archive if a job has files to backup.
		if ( $job_need_dest ) {
			// Create manifest file.
			$this->steps_todo[]                                   = 'CREATE_MANIFEST';
			$this->steps_data['CREATE_MANIFEST']['NAME']          = __( 'Creates manifest file', 'backwpup' );
			$this->steps_data['CREATE_MANIFEST']['STEP_TRY']      = 0;
			$this->steps_data['CREATE_MANIFEST']['SAVE_STEP_TRY'] = 0;
			// Add archive creation and backup filename for archive backups.
			if ( 'archive' === $this->job['backuptype'] ) {
				// Get backup folder if destination folder set.
				if ( in_array( 'FOLDER', $this->job['destinations'], true ) ) {
					$this->backup_folder = $this->job['backupdir'];
					// Check backup folder.
					if ( ! empty( $this->backup_folder ) ) {
						$this->backup_folder    = BackWPup_File::get_absolute_path( $this->backup_folder );
						$this->job['backupdir'] = $this->backup_folder;
					}
				}
				// Set temp folder to backup folder if not set because we need one.
				if ( empty( $this->backup_folder ) || '/' === $this->backup_folder ) {
					$this->backup_folder = BackWPup::get_plugin_data( 'TEMP' );
				}
				// Add job type to the filename.
				$archive_filename = $this->job['archivename'] . '_' . implode( '-', $this->job['type'] );

				$format = $this->job['archiveformat'];
				if ( empty( $format ) ) {
					$format = get_site_option( 'backwpup_archiveformat', '.tar' );
				}

				/**
				 * Filter the backup extension.
				 *
				 * @param string $format The initial extension name.
				 */
				$format = wpm_apply_filters_typed( 'string', 'backwpup_generate_archive_extension', $format );
				if ( ! in_array( $format, [ 'zip', 'tar', 'tar.gz', '.zip', '.tar', '.tar.gz' ], true ) ) {
					$format = 'tar';
				}
				// Create backup archive full file name.
				$this->backup_file = $this->generate_filename( $archive_filename, $format );
				// Add archive create.
				$this->steps_todo[]                                  = 'CREATE_ARCHIVE';
				$this->steps_data['CREATE_ARCHIVE']['NAME']          = __( 'Creates archive', 'backwpup' );
				$this->steps_data['CREATE_ARCHIVE']['STEP_TRY']      = 0;
				$this->steps_data['CREATE_ARCHIVE']['SAVE_STEP_TRY'] = 0;
				// Encrypt archive.
				if ( get_option( 'backwpup_archiveencryption' ) ) {
					$this->steps_todo[]                                   = 'ENCRYPT_ARCHIVE';
					$this->steps_data['ENCRYPT_ARCHIVE']['NAME']          = __( 'Encrypts the archive', 'backwpup' );
					$this->steps_data['ENCRYPT_ARCHIVE']['STEP_TRY']      = 0;
					$this->steps_data['ENCRYPT_ARCHIVE']['SAVE_STEP_TRY'] = 0;
				}
			}
			// Add destinations.
			foreach ( BackWPup::get_registered_destinations() as $id => $dest ) {
				if ( ! in_array( $id, $this->job['destinations'], true ) || empty( $dest['class'] ) ) {
					continue;
				}
				/**
				 * Destination class.
				 *
				 * @var BackWPup_Destinations $dest_class
				 */
				$dest_class = BackWPup::get_destination( $id );
				if ( $dest_class->can_run( $this->job ) ) {
					if ( 'sync' === $this->job['backuptype'] ) {
						if ( $dest['can_sync'] ) {
							$this->steps_todo[]                                      = 'DEST_SYNC_' . $id;
							$this->steps_data[ 'DEST_SYNC_' . $id ]['NAME']          = $dest['info']['description'];
							$this->steps_data[ 'DEST_SYNC_' . $id ]['STEP_TRY']      = 0;
							$this->steps_data[ 'DEST_SYNC_' . $id ]['SAVE_STEP_TRY'] = 0;
						}
					} else {
						$this->steps_todo[]                                 = 'DEST_' . $id;
						$this->steps_data[ 'DEST_' . $id ]['NAME']          = $dest['info']['description'];
						$this->steps_data[ 'DEST_' . $id ]['STEP_TRY']      = 0;
						$this->steps_data[ 'DEST_' . $id ]['SAVE_STEP_TRY'] = 0;
					}
				}
			}
		}
		// Add job types with no file output.
		$job_types = BackWPup::get_job_types();
		if ( $job_types ) {
			foreach ( $job_types as $id => $job_type_class ) {
				if ( in_array( $id, $this->job['type'], true ) && ! $job_type_class->creates_file() ) {
					$this->steps_todo[]                                = 'JOB_' . $id;
					$this->steps_data[ 'JOB_' . $id ]['NAME']          = $job_type_class->info['description'];
					$this->steps_data[ 'JOB_' . $id ]['STEP_TRY']      = 0;
					$this->steps_data[ 'JOB_' . $id ]['SAVE_STEP_TRY'] = 0;
				}
			}
		}
		$this->steps_todo[]                  = 'END';
		$this->steps_data['END']['NAME']     = __( 'End of Job', 'backwpup' );
		$this->steps_data['END']['STEP_TRY'] = 1;
		// Must write working data.
		$this->write_running_file();

		// Set log level.
		$this->log_level = get_site_option( 'backwpup_cfg_loglevel', 'normal_translated' );
		if ( ! in_array(
			$this->log_level,
			[
				'normal_translated',
				'normal',
				'debug_translated',
				'debug',
			],
			true
		) ) {
			$this->log_level = 'normal_translated';
		}
		// Create log file.
		$head  = '';
		$info  = '';
		$head .= '<!DOCTYPE html>' . PHP_EOL;
		$head .= '<html lang="' . str_replace( '_', '-', get_locale() ) . '">' . PHP_EOL;
		$head .= '<head>' . PHP_EOL;
		$head .= '<meta charset="' . get_bloginfo( 'charset' ) . '" />' . PHP_EOL;
		$head .= '<title>' . sprintf(
			// translators: %1$s = job name, %2$s = date, %3$s = time.
			__( 'BackWPup log for %1$s from %2$s at %3$s', 'backwpup' ),
			esc_attr( $this->job['name'] ),
			wp_date( get_option( 'date_format' ) ),
			wp_date( get_option( 'time_format' ) )
		) . '</title>' . PHP_EOL;
		$head .= '<meta name="robots" content="noindex, nofollow" />' . PHP_EOL;
		$head .= '<meta name="copyright" content="Copyright &copy; 2012 - ' . wp_date( 'Y', time() ) . ' WP Media, Inc." />' . PHP_EOL;
		$head .= '<meta name="author" content="WP Media" />' . PHP_EOL;
		$head .= '<meta name="generator" content="BackWPup ' . BackWPup::get_plugin_data( 'Version' ) . '" />' . PHP_EOL;
		$head .= '<meta http-equiv="cache-control" content="no-cache" />' . PHP_EOL;
		$head .= '<meta http-equiv="pragma" content="no-cache" />' . PHP_EOL;
		$head .= '<meta name="date" content="' . wp_date( 'c', time() ) . '" />' . PHP_EOL;
		$head .= str_pad( '<meta name="backwpup_errors" content="0" />', 100 ) . PHP_EOL;
		$head .= str_pad( '<meta name="backwpup_warnings" content="0" />', 100 ) . PHP_EOL;
		$head .= '<meta name="backwpup_jobid" content="' . $this->job['jobid'] . '" />' . PHP_EOL;
		$head .= '<meta name="backwpup_jobname" content="' . esc_attr( $this->job['name'] ) . '" />' . PHP_EOL;
		$head .= '<meta name="backwpup_jobtype" content="' . esc_attr( implode( '+', $this->job['type'] ) ) . '" />' . PHP_EOL;
		$head .= str_pad( '<meta name="backwpup_backupfilesize" content="0" />', 100 ) . PHP_EOL;
		$head .= str_pad( '<meta name="backwpup_jobruntime" content="0" />', 100 ) . PHP_EOL;
		$head .= '</head>' . PHP_EOL;
		$head .= '<body style="margin:0;padding:3px;font-family:monospace;font-size:12px;line-height:15px;background-color:black;color:#c0c0c0;white-space:nowrap;">' . PHP_EOL;
		$info .= sprintf(
				/* translators: %1$s: plugin name, %2$s: plugin version, %3$s: plugin URL. */
			_x(
				'[INFO] %1$s %2$s; A project of WP Media',
				'Plugin name; Plugin Version; plugin url',
				'backwpup'
			),
			BackWPup::get_plugin_data( 'name' ),
			BackWPup::get_plugin_data( 'Version' ),
			__( 'http://backwpup.com', 'backwpup' )
		) . '<br />' . PHP_EOL;
		$info .= sprintf(
			/* translators: 1: WordPress version, 2: site URL. */
			_x( '[INFO] WordPress %1$s on %2$s', 'WordPress Version; Blog url', 'backwpup' ),
			BackWPup::get_plugin_data( 'wp_version' ),
			esc_attr( site_url( '/' ) )
		) . '<br />' . PHP_EOL;
		$level      = __( 'Normal', 'backwpup' );
		$translated = '';
		if ( $this->is_debug() ) {
			$level = __( 'Debug', 'backwpup' );
		}
		if ( is_textdomain_loaded( 'backwpup' ) ) {
			$translated = __( '(translated)', 'backwpup' );
		}
		$info .= sprintf(
			/* translators: 1: log level, 2: translation flag. */
			__( '[INFO] Log Level: %1$s %2$s', 'backwpup' ),
			$level,
			$translated
		) . '<br />' . PHP_EOL;
		$job_name = esc_attr( $this->job['name'] );
		if ( $this->is_debug() ) {
			$job_name .= '; ' . implode( '+', $this->job['type'] );
		}
		$info .= sprintf(
			/* translators: %s: job name. */
			__( '[INFO] BackWPup job: %1$s', 'backwpup' ),
			$job_name
		) . '<br />' . PHP_EOL;
		if ( $this->is_debug() ) {
			$current_user = wp_get_current_user();
			$info        .= sprintf(
				/* translators: 1: user login, 2: user ID. */
				__( '[INFO] Runs with user: %1$s (%2$d) ', 'backwpup' ),
				$current_user->user_login,
				$current_user->ID
			) . '<br />' . PHP_EOL;
		}
		if ( 'wpcron' === $this->job['activetype'] ) {
			// Check next run.
			$cron_next = wp_next_scheduled( 'backwpup_cron', [ 'arg' => $this->job['jobid'] ] );
			if ( ! $cron_next || $cron_next < time() ) {
				wp_unschedule_event( $cron_next, 'backwpup_cron', [ 'arg' => $this->job['jobid'] ] );
				$cron_next = BackWPup_Cron::cron_next( $this->job['cron'] );
				wp_schedule_single_event( $cron_next, 'backwpup_cron', [ 'arg' => $this->job['jobid'] ] );
				$cron_next = wp_next_scheduled( 'backwpup_cron', [ 'arg' => $this->job['jobid'] ] );
			}
			// Output scheduling.
			if ( $this->is_debug() ) {
				if ( ! $cron_next ) {
					$cron_next = __( 'Not scheduled!', 'backwpup' );
				} else {
					$cron_next = wp_date(
						'D, j M Y @ H:i',
						$cron_next
					);
				}
				$info .= sprintf(
					// translators: 1: Cron schedule. 2: Next scheduled run.
					__( '[INFO] Cron: %1$s; Next: %2$s ', 'backwpup' ),
					$this->job['cron'],
					$cron_next
				) . '<br />' . PHP_EOL;
			}
		} elseif ( 'link' === $this->job['activetype'] && $this->is_debug() ) {
			$info .= __( '[INFO] BackWPup job start with link is active', 'backwpup' ) . '<br />' . PHP_EOL;
		} elseif ( $this->is_debug() ) {
			$info .= __( '[INFO] BackWPup no automatic job start configured', 'backwpup' ) . '<br />' . PHP_EOL;
		}
		if ( $this->is_debug() ) {
			if ( 'cronrun' === $start_type ) {
				$info .= __( '[INFO] BackWPup job started from wp-cron', 'backwpup' ) . '<br />' . PHP_EOL;
			} elseif ( 'runnow' === $start_type || 'runnowalt' === $start_type ) {
				$info .= __( '[INFO] BackWPup job started manually', 'backwpup' ) . '<br />' . PHP_EOL;
			} elseif ( 'runext' === $start_type ) {
				$info .= __( '[INFO] BackWPup job started from external url', 'backwpup' ) . '<br />' . PHP_EOL;
			} elseif ( 'runcli' === $start_type ) {
				$info .= __(
					'[INFO] BackWPup job started form commandline interface',
					'backwpup'
				) . '<br />' . PHP_EOL;
			}
			$bit = '';
			if ( 4 === PHP_INT_SIZE ) {
				$bit = ' (32bit)';
			}
			if ( 8 === PHP_INT_SIZE ) {
				$bit = ' (64bit)';
			}
			$info .= __(
				'[INFO] PHP ver.:',
				'backwpup'
			) . ' ' . PHP_VERSION . $bit . '; ' . PHP_SAPI . '; ' . PHP_OS . '<br />' . PHP_EOL;
			$info .= sprintf(
				/* translators: %d: time in seconds. */
				__( '[INFO] Maximum PHP script execution time is %1$d seconds', 'backwpup' ),
				ini_get( 'max_execution_time' )
			) . '<br />' . PHP_EOL;
			if ( 'cli' !== php_sapi_name() ) {
				$job_max_execution_time = get_site_option( 'backwpup_cfg_jobmaxexecutiontime' );
				if ( ! empty( $job_max_execution_time ) ) {
					$info .= sprintf(
						/* translators: %d: time in seconds. */
						__( '[INFO] Script restart time is configured to %1$d seconds', 'backwpup' ),
						$job_max_execution_time
					) . '<br />' . PHP_EOL;
				}
			}

			$cache_group   = 'backwpup_job';
			$cache_key     = 'mysql_version_' . DB_NAME;
			$mysql_version = wp_cache_get( $cache_key, $cache_group );
			if ( false === $mysql_version ) {
				$mysql_version = $wpdb->db_version();
				wp_cache_set( $cache_key, $mysql_version, $cache_group, MINUTE_IN_SECONDS );
			}
			$info .= sprintf(
				/* translators: %s: MySQL version. */
				__( '[INFO] MySQL ver.: %s', 'backwpup' ),
				$mysql_version
			) . '<br />' . PHP_EOL;
			if ( isset( $_SERVER['SERVER_SOFTWARE'] ) ) {
				$server_software = sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) );
				$info           .= sprintf(
					/* translators: %s: web server info. */
					__( '[INFO] Web Server: %s', 'backwpup' ),
					$server_software
				) . '<br />' . PHP_EOL;
			}
			if ( function_exists( 'curl_init' ) ) {
				$curlversion = curl_version();
				$info       .= sprintf(
					/* translators: 1: cURL version, 2: SSL version. */
					__( '[INFO] curl ver.: %1$s; %2$s', 'backwpup' ),
					$curlversion['version'],
					$curlversion['ssl_version']
				) . '<br />' . PHP_EOL;
			}
			$info .= sprintf(
				/* translators: %s: temporary folder path. */
				__( '[INFO] Temp folder is: %s', 'backwpup' ),
				BackWPup::get_plugin_data( 'TEMP' )
			) . '<br />' . PHP_EOL;
		}
		if ( $this->is_debug() ) {
			$logfile = $this->logfile;
		} else {
			$logfile = basename( $this->logfile );
		}
		$info .= sprintf(
			/* translators: %s: log file name. */
			__( '[INFO] Logfile is: %s', 'backwpup' ),
			$logfile
		) . '<br />' . PHP_EOL;
		if ( ! empty( $this->backup_file ) && 'archive' === $this->job['backuptype'] ) {
			if ( $this->is_debug() ) {
				$backupfile = $this->backup_folder . $this->backup_file;
			} else {
				$backupfile = $this->backup_file;
			}
			$info .= sprintf(
			/* translators: %s: backup file name. */
			__( '[INFO] Backup file is: %s', 'backwpup' ),
			$backupfile
			) . '<br />' . PHP_EOL;
		} else {
			$info .= sprintf(
				/* translators: %s: backup type. */
				__( '[INFO] Backup type is: %s', 'backwpup' ),
				esc_attr( $this->job['backuptype'] )
			) . '<br />' . PHP_EOL;
		}
		// Output info on CLI.
		if ( 'cli' === php_sapi_name() && defined( 'STDOUT' ) ) {
			fwrite( STDOUT, wp_strip_all_tags( $info ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Writing to STDOUT for CLI output is appropriate
		}
		$filesystem      = backwpup_wpfilesystem();
		$existing_log    = $filesystem->get_contents( $this->logfile );
		$existing_log    = false === $existing_log ? '' : $existing_log;
		$logfile_written = $filesystem->put_contents( $this->logfile, $existing_log . $head . $info );
		if ( false === $logfile_written ) {
			$this->logfile = '';
			$this->log( __( 'Could not write log file', 'backwpup' ), E_USER_ERROR );
		}
		// Test for destinations.
		if ( $job_need_dest ) {
			$desttest = false;

			foreach ( $this->steps_todo as $deststeptest ) {
				if ( 'DEST_' === substr( (string) $deststeptest, 0, 5 ) ) {
					$desttest = true;
					break;
				}
			}
			if ( ! $desttest ) {
				$this->log(
					__(
						'No destination correctly defined for backup! Please correct job settings.',
						'backwpup'
					),
					E_USER_ERROR
				);
				$this->steps_todo = [ 'END' ];
			}
		}
		// Test backup folder.
		if ( ! empty( $this->backup_folder ) ) {
			$folder_message = BackWPup_File::check_folder( $this->backup_folder, true );
			if ( ! empty( $folder_message ) ) {
				$this->log( $folder_message, E_USER_ERROR );
				$this->steps_todo = [ 'END' ];
			}
		}

		$backup_trigger = $this->get_backup_trigger( $start_type, $this->job );

		/**
		 * Fires on backup job creation
		 *
		 * @param array $job Job details.
		 * @param string $backup_file Backup file name.
		 * @param string $backup_trigger Backup trigger.
		 */
		do_action( 'backwpup_create_job', $this->job, $this->backup_file, $backup_trigger );

		// Set start as done.
		$this->steps_done[] = 'CREATE';
	}

	/**
	 * Get backup trigger.
	 *
	 * @param string $start_type Backup Start types.
	 * @param array  $job Backup job data.
	 *
	 * @return string
	 */
	private function get_backup_trigger( string $start_type, array $job ): string {
		$trigger = 'first_job';
		switch ( $start_type ) {
			case 'runcli':
				$trigger = 'wp-cli';
				break;
			case 'cronrun':
				$trigger = 'scheduled_job';
				break;
			case 'runext':
				$trigger = 'link';
				break;
			case 'runnow':
				$trigger = 'backup_now_job';
				if ( ! empty( $job['backup_now'] ) ) {
					$trigger = 'backup_now_global';
				} elseif ( $job['tempjob'] ) {
					$trigger = 'first_job';
				}
				break;
		}

		return $trigger;
	}

	/**
	 * Generate a filename for a backup file.
	 *
	 * @param string $name             Base file name.
	 * @param string $suffix           Optional file suffix.
	 * @param bool   $delete_temp_file Whether to delete an existing temp file.
	 *
	 * @return string
	 */
	public function generate_filename( $name, $suffix = '', $delete_temp_file = true ) {
		if ( $suffix ) {
			$suffix = '.' . trim( $suffix, '. ' );
		}
		$name  = BackWPup_Option::substitute_date_vars( $name );
		$name .= $suffix;
		if ( $delete_temp_file && wp_is_writable( BackWPup::get_plugin_data( 'TEMP' ) . $name ) && ! is_dir( BackWPup::get_plugin_data( 'TEMP' ) . $name ) && ! is_link( BackWPup::get_plugin_data( 'TEMP' ) . $name ) ) {
			@unlink( BackWPup::get_plugin_data( 'TEMP' ) . $name ); //phpcs:ignore
		}

		return $name;
	}

	/**
	 * Generate a filename for a database dump.
	 *
	 * @param string $name   The initial filename.
	 * @param string $suffix The suffix to append to the filename.
	 *
	 * @return string The generated filename.
	 */
	public function generate_db_dump_filename( $name, $suffix = '' ) {
		/**
		 * Filter the db dump filename.
		 *
		 * @param string $name The initial filename.
		 */
		$name = wpm_apply_filters_typed( 'string', 'backwpup_generate_dump_filename', $name );
		return $this->generate_filename( $name, $suffix );
	}

	/**
	 * Sanitizes a filename, replacing whitespace with underscores.
	 *
	 * @param string $filename Filename to sanitize.
	 *
	 * @return string
	 */
	public static function sanitize_file_name( $filename ) {
		$filename = trim( (string) $filename );

		$special_chars = [
			'?',
			'[',
			']',
			'/',
			'\\',
			'=',
			'<',
			'>',
			':',
			';',
			',',
			"'",
			'"',
			'&',
			'$',
			'#',
			'*',
			'(',
			')',
			'|',
			'~',
			'`',
			'!',
			'{',
			'}',
			chr( 0 ),
		];

		$filename = str_replace( $special_chars, '', $filename );

		$filename = str_replace( [ ' ', '%20', '+' ], '_', $filename );
		$filename = str_replace( [ "\n", "\t", "\r" ], '-', $filename );

		return trim( $filename, '.-_' );
	}

	/**
	 * Write the running job state to disk.
	 *
	 * @return void
	 */
	private function write_running_file() {
		$clone = clone $this;
		$data  = '<?php //' . wp_json_encode( get_object_vars( $clone ) );

		$wp_filesystem = backwpup_wpfilesystem();
		if ( ! is_object( $wp_filesystem ) ) {
			$this->log( __( 'Cannot initialize WP Filesystem. Job will be aborted.', 'backwpup' ), E_USER_ERROR );
			return;
		}

		$running_file     = BackWPup::get_plugin_data( 'running_file' );
		$write            = $wp_filesystem->put_contents( $running_file, $data, FS_CHMOD_FILE );
		$running_contents = $wp_filesystem->get_contents( $running_file );
		if ( ! $write || ! is_string( $running_contents ) || strlen( $running_contents ) < strlen( $data ) ) {
			if ( $wp_filesystem->exists( $running_file ) ) {
				$wp_filesystem->delete( $running_file );
			}
			$this->log( __( 'Cannot write progress to working file. Job will be aborted.', 'backwpup' ), E_USER_ERROR );
		}
	}

	/**
	 * Write messages to log file.
	 *
	 * @param string     $message The error message.
	 * @param int        $type    The error number (E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE, ...).
	 * @param string     $file    The full path of file with error (__FILE__).
	 * @param int        $line    The line in that is the error (__LINE__).
	 * @param array|null $context Optional context payload for error signals.
	 *
	 * @return bool True.
	 */
	public function log( $message, $type = E_USER_NOTICE, $file = '', $line = 0, $context = null ) {
		// If error has been suppressed with an @.
		$reporting_level = (int) ini_get( 'error_reporting' );
		if ( 0 === $reporting_level ) {
			return true;
		}

		// If the first is the type and second the message, switch it on user errors.
		if ( ! is_int( $type ) && is_int( $message ) && in_array(
			$message,
			[
				1,
				2,
				4,
				8,
				16,
				32,
				64,
				128,
				256,
				512,
				1024,
				2048,
				4096,
				8192,
				16384,
			],
			true
		)
		) {
			$temp    = $message;
			$message = $type;
			$type    = $temp;
		}

		// JSON message if array or object.
		if ( is_array( $message ) || is_object( $message ) ) {
			$message = wp_json_encode( $message );
		}

		// If not set, get line and file.
		if ( $this->is_debug() ) {
			if ( empty( $file ) || empty( $line ) ) {
				$debug_info = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 2 ); //phpcs:ignore
				$file       = isset( $debug_info[1]['file'] ) ? $debug_info[1]['file'] : '';
				$line       = isset( $debug_info[1]['line'] ) ? $debug_info[1]['line'] : 0;
			}
		}

		$error   = false;
		$warning = false;

		switch ( $type ) {
			case E_NOTICE:
			case E_USER_NOTICE:
				break;

			case E_WARNING:
			case E_CORE_WARNING:
			case E_COMPILE_WARNING:
			case E_USER_WARNING:
				++$this->warnings;
				$warning = true;
				$message = __( 'WARNING:', 'backwpup' ) . ' ' . $message;
				break;

			case E_ERROR:
			case E_PARSE:
			case E_CORE_ERROR:
			case E_COMPILE_ERROR:
			case E_USER_ERROR:
				++$this->errors;
				$error   = true;
				$message = __( 'ERROR:', 'backwpup' ) . ' ' . $message;
				break;

			case E_DEPRECATED:
			case E_USER_DEPRECATED:
				$message = __( 'DEPRECATED:', 'backwpup' ) . ' ' . $message;
				break;

			case E_STRICT:
				$message = __( 'STRICT NOTICE:', 'backwpup' ) . ' ' . $message;
				break;

			case E_RECOVERABLE_ERROR:
				++$this->errors;
				$error   = true;
				$message = __( 'RECOVERABLE ERROR:', 'backwpup' ) . ' ' . $message;
				break;

			default:
				$message = $type . ': ' . $message;
				break;
		}

		// Print message to CLI.
		if ( defined( \WP_CLI::class ) && WP_CLI ) {
			$output_message = str_replace( [ '&hellip;', '&#160;' ], [ '...', ' ' ], esc_html( $message ) );
			if ( ! call_user_func( [ '\cli\Shell', 'isPiped' ] ) ) {
				if ( $error ) {
					$output_message = '%r' . $output_message . '%n';
				}
				if ( $warning ) {
					$output_message = '%y' . $output_message . '%n';
				}
				$output_message = call_user_func( [ '\cli\Colors', 'colorize' ], $output_message, true );
			}
			WP_CLI::line( $output_message );
		} elseif ( 'cli' === php_sapi_name() && defined( 'STDOUT' ) ) {
			$output_message = str_replace(
				[ '&hellip;', '&#160;' ],
				[
					'...',
					' ',
				],
				esc_html( $message )
			) . PHP_EOL;
			fwrite( STDOUT, $output_message ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Writing to STDOUT for CLI output is appropriate
		}

		// Timestamp for log file.
		$debug_info = '';
		if ( $this->is_debug() ) {
			$debug_info = ' title="[Type: ' . $type . '|Line: ' . $line . '|File: ' . $this->get_destination_path_replacement( $file ) . '|Mem: ' . size_format(
				memory_get_usage( true ),
				2
			) . '|Mem Max: ' . size_format(
				memory_get_peak_usage( true ),
				2
			) . '|Mem Limit: ' . ini_get( 'memory_limit' ) . '|PID: ' . self::get_pid() . ' | UniqID: ' . $this->uniqid . '|Queries: ' . get_num_queries() . ']"';
		}
		$timestamp = '<span datetime="' . wp_date( 'c', time() ) . '" ' . $debug_info . '>[' . wp_date(
			'd-M-Y H:i:s',
			time()
		) . ']</span> ';

		// Set last message.
		if ( $error ) {
			$output_message     = '<span style="background-color:#ff6766;color:black;padding:0 2px;">' . esc_html( $message ) . '</span>';
			$this->lasterrormsg = $output_message;
		} elseif ( $warning ) {
			$output_message     = '<span style="background-color:#ffc766;color:black;padding:0 2px;">' . esc_html( $message ) . '</span>';
			$this->lasterrormsg = $output_message;
		} else {
			$output_message = esc_html( $message );
			$this->lastmsg  = $output_message;
		}
		// Write log file.
		if ( $this->logfile ) {
			$filesystem    = backwpup_wpfilesystem();
			$existing_log  = $filesystem->get_contents( $this->logfile );
			$existing_log  = false === $existing_log ? '' : $existing_log;
			$logfile_write = $filesystem->put_contents(
				$this->logfile,
				$existing_log . $timestamp . $output_message . '<br />' . PHP_EOL
			);
			if ( false === $logfile_write ) {
				$this->logfile = '';
				restore_error_handler();
				trigger_error( esc_html( $message ), $type );
			}

			// Write new log header.
			if ( ( $error || $warning ) && $this->logfile ) {
				$fd = fopen( $this->logfile, 'r+' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
				if ( $fd ) {
					$file_pos = ftell( $fd );

					while ( ! feof( $fd ) ) {
						$line = fgets( $fd );
						if ( $error && false !== stripos( $line, '<meta name="backwpup_errors" content="' ) ) {
							fseek( $fd, $file_pos );
							fwrite( // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
								$fd,
								str_pad(
									'<meta name="backwpup_errors" content="' . $this->errors . '" />',
									100
								) . PHP_EOL
							);
							break;
						}
						if ( $warning && false !== stripos( $line, '<meta name="backwpup_warnings" content="' ) ) {
							fseek( $fd, $file_pos );
							fwrite( // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
								$fd,
								str_pad(
									'<meta name="backwpup_warnings" content="' . $this->warnings . '" />',
									100
								) . PHP_EOL
							);
							break;
						}
						$file_pos = ftell( $fd );
					}
					fclose( $fd ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
				}
			}
		}

		// Write working data.
		$this->update_working_data( $error || $warning );

		// Fire "signal" for warnings/errors so other components can store/forward them.
		if ( $error || $warning ) {
			/**
			 * Fires when BackWPup logs a warning/error (signal).
			 *
			 * @param array $signal Signal data.
			 *
			 * @type string $level      'error'|'warning'.
			 * @type int    $type       PHP error type (E_USER_ERROR, etc).
			 * @type string $message    The (already prefixed) message string.
			 * @type int    $timestamp  current_time('timestamp').
			 * @type int    $job_id     Job ID (if available).
			 * @type string $job_name   Job name (if available).
			 * @type string $step       Current step identifier.
			 * @type string $logfile    Current logfile path (maybe empty).
			 * @type string $file       Source file (debug only might be filled).
			 * @type int    $line       Source line.
			 * @type array  $context    Optional context payload.
			 *   @type string $reason_code   Failure reason code.
			 *   @type string $destination   Destination identifier.
			 *   @type string $provider_code Provider-specific reason code.
			 *   @type int    $http_status   HTTP status code (if any).
			 *
			 * @param BackWPup_Job $job The job instance.
			 */
			$signal_context = $this->sanitize_signal_context( $context );
			if ( empty( $signal_context['reason_code'] ) ) {
				$inferred_reason = $this->infer_signal_reason_code_from_message( (string) $message );
				if ( '' !== $inferred_reason ) {
					$signal_context['reason_code'] = $inferred_reason;
				}
			}
			$signal = [
				'level'     => $error ? 'error' : 'warning',
				'type'      => (int) $type,
				'message'   => (string) $message,
				'timestamp' => time(),
				'job_id'    => isset( $this->job['jobid'] ) ? (int) $this->job['jobid'] : 0,
				'job_name'  => isset( $this->job['name'] ) ? (string) $this->job['name'] : '',
				'step'      => (string) $this->step_working,
				'logfile'   => (string) $this->logfile,
				'file'      => (string) $file,
				'line'      => (int) $line,
			];

			if ( ! empty( $signal_context ) ) {
				$signal['context'] = $signal_context;
			}

			do_action( 'backwpup_job_error_signal', $signal, $this );
		}

		// true for no more php error handling.
		return true;
	}

	/**
	 * Is debug log active.
	 *
	 * @return bool
	 */
	public function is_debug() {
		return strstr( $this->log_level, 'debug' ) ? true : false;
	}

	/**
	 * Sanitize optional signal context data.
	 *
	 * @param mixed $context Optional context payload.
	 * @return array
	 */
	private function sanitize_signal_context( $context ): array {
		if ( ! is_array( $context ) ) {
			return [];
		}

		$allowed = [
			'reason_code',
			'destination',
			'provider_code',
			'http_status',
		];

		$clean = [];
		foreach ( $allowed as $key ) {
			if ( ! array_key_exists( $key, $context ) ) {
				continue;
			}

			$value = $context[ $key ];
			if ( null === $value ) {
				continue;
			}

			if ( 'http_status' === $key ) {
				$clean[ $key ] = (int) $value;
				continue;
			}

			if ( is_scalar( $value ) ) {
				$clean[ $key ] = trim( (string) $value );
			}
		}

		if ( isset( $clean['reason_code'] ) ) {
			$clean['reason_code'] = strtolower( $clean['reason_code'] );
		}

		return $clean;
	}

	/**
	 * Infer a failure reason code from a log message.
	 *
	 * @param string $message Error message.
	 * @return string
	 */
	private function infer_signal_reason_code_from_message( string $message ): string {
		$normalized = strtolower( trim( $message ) );
		$normalized = preg_replace( '/^(error|warning|recoverable error|deprecated|strict notice):\s*/i', '', $normalized );
		$normalized = $normalized ? $normalized : strtolower( trim( $message ) );

		$storage_patterns = [
			'not enough space',
			'not enough storage',
			'no space left on device',
			'insufficient space',
			'insufficient storage',
			'insufficientstorage',
			'disk full',
			'storagequotaexceeded',
			'quotalimitreached',
			'quotaexceeded',
			'quota limit',
			'quota exceeded',
			'insufficient_space',
			'quotareached',
		];

		foreach ( $storage_patterns as $pattern ) {
			if ( false !== strpos( $normalized, $pattern ) ) {
				return 'not_enough_storage';
			}
		}

		return '';
	}

	/**
	 * Change the path for better storing in archives or sync destinations.
	 *
	 * @param string $path Path to normalize.
	 *
	 * @return string
	 */
	public function get_destination_path_replacement( $path ) {
		$abs_path = realpath( BackWPup_Path_Fixer::fix_path( ABSPATH ) );
		if ( $this->job['backupabsfolderup'] ) {
			$abs_path = dirname( $abs_path );
		}
		$abs_path = trailingslashit( str_replace( '\\', '/', $abs_path ) );

		$path = str_replace( [ '\\', $abs_path ], '/', (string) $path );

		// Replace the colon from Windows drive letters to avoid issues in archives or copying to directories.
		if ( 0 === stripos( PHP_OS, 'WIN' ) && 1 === strpos( $path, ':/' ) ) {
			$path = '/' . substr_replace( $path, '', 1, 1 );
		}

		return $path;
	}

	/**
	 * Get the Process id of working script.
	 *
	 * @return int
	 */
	private static function get_pid() {
		if ( function_exists( 'posix_getpid' ) ) {
			return posix_getpid();
		}
		if ( function_exists( 'getmypid' ) ) {
			return getmypid();
		}

		return -1;
	}

	/**
	 * Write working data so the process can be displayed or resumed.
	 *
	 * The write only happens once per second.
	 *
	 * @param bool $must Whether to force a write.
	 */
	public function update_working_data( $must = false ) {
		global $wpdb;

		// Reduce server load.
		$job_wait_time_ms = (int) get_site_option( 'backwpup_cfg_jobwaittimems' );
		if ( 0 < $job_wait_time_ms && 500000 >= $job_wait_time_ms ) {
			usleep( $job_wait_time_ms );
		}

		// Check free memory.
		$this->need_free_memory( '10M' );

		// only run every 1 sec.
		$time_to_update = microtime( true ) - $this->timestamp_last_update;
		if ( 1 > $time_to_update && ! $must ) {
			return;
		}

		// FCGI must have a permanent output so that it not broke.
		if ( get_site_option( 'backwpup_cfg_jobdooutput' ) && ! defined( 'STDOUT' ) ) {
			echo esc_html( str_repeat( ' ', 12 ) );
			flush();
		}

		// Check WPDB connection. WP will do it after a query that will cause messages.
		$wpdb->check_connection( false );

		// Reset execution time for 5 minutes.
		if ( function_exists( 'set_time_limit' ) ) {
			set_time_limit( 300 );
		}

		// Calculate substep percent.
		if ( 0 < $this->substeps_todo && 0 < $this->substeps_done ) {
			$this->substep_percent = min( round( $this->substeps_done / $this->substeps_todo * 100 ), 100 );
		} else {
			$this->substep_percent = 1;
		}

		// Check if job aborted.
		if ( ! file_exists( BackWPup::get_plugin_data( 'running_file' ) ) ) {
			if ( 'END' !== $this->step_working ) {
				$this->end();
			}
		} else {
			$this->timestamp_last_update = microtime( true ); // Last update of working file.
			$this->write_running_file();
		}

		if ( 0 !== $this->signal ) {
			$this->do_restart();
		}
	}

	/**
	 * Increase automatically the memory that is needed.
	 *
	 * @param int|string $memory_need Amount of memory needed.
	 */
	public function need_free_memory( $memory_need ) {
		$needed_memory = memory_get_usage( true ) + self::convert_hr_to_bytes( $memory_need );
		if ( wp_is_ini_value_changeable( 'memory_limit' ) && $needed_memory > self::convert_hr_to_bytes( ini_get( 'memory_limit' ) ) ) {
			$new_memory_size = round( $needed_memory / 1024 / 1024 ) + 1 . 'M';
			if ( 1073741824 <= $needed_memory ) {
				$new_memory_size = round( $needed_memory / 1024 / 1024 / 1024 ) . 'G';
			}
			ini_set( 'memory_limit', $new_memory_size ); // @phpcs:ignore
		}
	}

	/**
	 * Converts hr to bytes.
	 *
	 * @param int|string $size Human-readable size.
	 *
	 * @return int
	 */
	public static function convert_hr_to_bytes( $size ) {
		$size  = strtolower( (string) $size );
		$bytes = (int) $size;
		if ( false !== strpos( $size, 'k' ) ) {
			$bytes = intval( $size ) * 1024;
		} elseif ( false !== strpos( $size, 'm' ) ) {
			$bytes = intval( $size ) * 1024 * 1024;
		} elseif ( false !== strpos( $size, 'g' ) ) {
			$bytes = intval( $size ) * 1024 * 1024 * 1024;
		}

		return $bytes;
	}

	/**
	 * Called on job stop makes cleanup and terminates the script.
	 */
	private function end() {
		$this->step_working  = 'END';
		$this->substeps_todo = 1;

		if ( ! file_exists( BackWPup::get_plugin_data( 'running_file' ) ) ) {
			$this->log( __( 'Backup aborted!', 'backwpup' ), E_USER_ERROR );
		}

		// Delete old logs.
		if ( get_site_option( 'backwpup_cfg_maxlogs' ) ) {
			$log_file_list = [];
			$log_folder    = trailingslashit( dirname( $this->logfile ) );
			if ( is_readable( $log_folder ) ) { // Make file list.
				try {
					$dir = new BackWPup_Directory( $log_folder );

					foreach ( $dir as $file ) {
						if ( ! $file->isDot() && 0 === strpos(
							$file->getFilename(),
							'backwpup_log_'
						) && false !== strpos( $file->getFilename(), '.html' ) ) {
							$log_file_list[ $file->getMTime() ] = clone $file;
						}
					}
				} catch ( UnexpectedValueException $e ) {
					$this->log(
						sprintf(
							// translators: %s: path.
							__( 'Could not open path: %s', 'backwpup' ),
							$e->getMessage()
						),
						E_USER_WARNING
					);
				}
			}
			if ( 0 < count( $log_file_list ) ) {
				krsort( $log_file_list, SORT_NUMERIC );
				$num_delete_files = 0;
				$i                = -1;

				foreach ( $log_file_list as $log_file ) {
					++$i;
					if ( $i < get_site_option( 'backwpup_cfg_maxlogs' ) ) {
						continue;
					}
					@unlink( $log_file->getPathname() ); // phpcs:ignore
					++$num_delete_files;
				}
				if ( 0 < $num_delete_files ) {
					$this->log(
						sprintf(
						// translators: %d: number of log files.
							_n(
								'%d old log deleted',
								'%d old logs deleted',
								$num_delete_files,
								'backwpup'
							),
							$num_delete_files
						)
					);
				}
			}
		}

		// Display job working time.
		$current_timestamp = time() + (int) ( (float) get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
		if ( 0 < $this->errors ) {
			$this->log(
				sprintf(
					/* translators: %s: runtime in seconds. */
					__(
						'Job has ended with errors in %s seconds. You must resolve the errors for correct execution.',
						'backwpup'
					),
					$current_timestamp - $this->start_time
				),
				E_USER_ERROR
			);
		} elseif ( 0 < $this->warnings ) {
			$this->log(
				sprintf(
					/* translators: %s: runtime in seconds. */
					__(
						'Job finished with warnings in %s seconds. Please resolve them for correct execution.',
						'backwpup'
					),
					$current_timestamp - $this->start_time
				),
				E_USER_WARNING
			);
		} else {
			$this->log(
				sprintf(
				/* translators: %s: runtime in seconds. */
				__( 'Job done in %s seconds.', 'backwpup' ),
				$current_timestamp - $this->start_time
			)
				);
		}

		// Update job options.
		$this->job['lastruntime'] = $current_timestamp - $this->start_time;
		BackWPup_Option::update( $this->job['jobid'], 'lastruntime', $this->job['lastruntime'] );

		// Write header info.
		if ( ! empty( $this->logfile ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
			$fd = fopen( $this->logfile, 'r+' );
			if ( $fd ) {
				$filepos = ftell( $fd );
				$found   = 0;

				while ( ! feof( $fd ) ) {
					$line = fgets( $fd );
					if ( false !== stripos( $line, '<meta name="backwpup_jobruntime"' ) ) {
						fseek( $fd, $filepos );
						fwrite( // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
							$fd,
							str_pad(
								'<meta name="backwpup_jobruntime" content="' . $this->job['lastruntime'] . '" />',
								100
							) . PHP_EOL
						);
						++$found;
					}
					if ( false !== stripos( $line, '<meta name="backwpup_backupfilesize"' ) ) {
						fseek( $fd, $filepos );
						fwrite( // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
							$fd,
							str_pad(
								'<meta name="backwpup_backupfilesize" content="' . $this->backup_filesize . '" />',
								100
							) . PHP_EOL
						);
						++$found;
					}
					if ( 2 <= $found ) {
						break;
					}
					$filepos = ftell( $fd );
				}
				fclose( $fd ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			}

			// check for new settings and overwrite when it is not a legacy job.
			if ( empty( $this->job['legacy'] ) ) {
				$this->job['mailaddresslog']       = get_site_option( 'backwpup_cfg_mailaddresslog' );
				$this->job['mailerroronly']        = (bool) get_site_option( 'backwpup_cfg_mailerroronly' );
				$this->job['mailaddresssenderlog'] = get_site_option( 'backwpup_cfg_mailaddresssenderlog' );
			}

			// Send mail with log.
			$sendmail = false;
			if ( $this->job['mailaddresslog'] ) {
				$sendmail = true;
			}
			if ( 0 === $this->errors && $this->job['mailerroronly'] ) {
				$sendmail = false;
			}
			if ( $sendmail ) {
				// Special subject.
				$status = __( 'SUCCESSFUL', 'backwpup' );
				if ( 0 < $this->warnings ) {
					$status = __( 'WARNING', 'backwpup' );
				}
				if ( 0 < $this->errors ) {
					$status = __( 'ERROR', 'backwpup' );
				}

				$subject = sprintf(
					// translators: %1$s = Date, %2$s = job name, %3$s = job status.
					__( '[%3$s] BackWPup log %1$s: %2$s', 'backwpup' ),
					wp_date( 'd-M-Y H:i', $this->start_time ),
					esc_attr( $this->job['name'] ),
					$status
				);
				$headers   = [];
				$headers[] = 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' );

				if ( $this->job['mailaddresssenderlog'] ) {
					$from  = $this->job['mailaddresssenderlog'];
					$from  = html_entity_decode( $from );
					$email = '';
					if ( ! is_email( $from ) ) {
						$start_pos = strrpos( $from, '<' );
						if ( false !== $start_pos ) {
							$end_pos = strpos( $from, '>', $start_pos );
							if ( false !== $end_pos ) {
								$email = sanitize_email(
									substr( $from, $start_pos + 1, $end_pos - $start_pos + 1 )
								);
							}
							$from = trim( substr( $from, 0, $start_pos ) );
						}
					} else {
						$email = sanitize_email( $from );
						$from  = '';
					}

					$from = sanitize_text_field( trim( $from ) );
					if ( ! $from ) {
						$from = sprintf(
							// translators: 1$s = Plugin name, %2$s = WordPress blog name.
							'%1$s on %2$s',
							BackWPup::get_plugin_data( 'name' ),
							get_bloginfo( 'name' )
						);
					}

					if ( ! is_email( $email ) ) {
						$email = get_bloginfo( 'admin_email' );
					}

					$headers[] = 'From: ' . $from . ' <' . $email . '>';
				}
				$filesystem   = backwpup_wpfilesystem();
				$log_contents = $filesystem->get_contents( $this->logfile );
				$log_contents = false === $log_contents ? '' : $log_contents;
				wp_mail( $this->job['mailaddresslog'], $subject, $log_contents, $headers );
			}
		}

		// Set done.
		$this->substeps_done = 1;
		$this->steps_done[]  = 'END';

		do_action( 'backwpup_end_job', $this->job, $this->backup_file, $this );

		// Clean up temp.
		self::clean_temp_folder();

		// Remove shutdown action.
		remove_action( 'shutdown', [ $this, 'shutdown' ] );
		restore_exception_handler();
		restore_error_handler();

		// Logfile end.
		$filesystem   = backwpup_wpfilesystem();
		$log_contents = $filesystem->get_contents( $this->logfile );
		$log_contents = false === $log_contents ? '' : $log_contents;
		$filesystem->put_contents( $this->logfile, $log_contents . '</body>' . PHP_EOL . '</html>' );

		// Disable The job if it's a temp job.
		if ( true === $this->job['tempjob'] ) {
			self::disable_job( $this->job['jobid'] );
		}
		BackWPup_Cron::check_cleanup();
		exit();
	}

	/**
	 * Cleanup Temp Folder.
	 *
	 * @return void
	 */
	public static function clean_temp_folder() {
		$instance            = new self();
		$temp_dir            = BackWPup::get_plugin_data( 'TEMP' );
		$do_not_delete_files = [ '.htaccess', 'nginx.conf', 'index.php', '.', '..', '.donotbackup', '.backwpup_job_started' ];

		if ( is_writable( $temp_dir ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable
			try {
				$dir = new BackWPup_Directory( $temp_dir );

				foreach ( $dir as $file ) {
					if ( in_array(
						$file->getFilename(),
						$do_not_delete_files,
						true
					) || $file->isDir() || $file->isLink() ) {
						continue;
					}
					if ( $file->isWritable() ) {
						@unlink( $file->getPathname() ); // phpcs:ignore
					}
				}
			} catch ( UnexpectedValueException $e ) {
				$instance->log(
					sprintf(
					// translators: %s: path.
						__( 'Could not open path: %s', 'backwpup' ),
						$e->getMessage()
					),
					E_USER_WARNING
				);
			}
		}
	}

	/**
	 * Do a job restart.
	 *
	 * @param bool $must Whether restart must be done.
	 */
	public function do_restart( $must = false ) {
		// Restart when a signal is present.
		if ( 0 !== $this->signal ) {
			$must = true;
		}

		// No restart if in end step.
		if ( 'END' === $this->step_working || ( count( $this->steps_done ) + 1 ) >= count( $this->steps_todo ) ) {
			return;
		}

		// No restart on CLI usage.
		if ( 'cli' === php_sapi_name() ) {
			return;
		}

		// No restart if no restart time configured.
		$job_max_execution_time = get_site_option( 'backwpup_cfg_jobmaxexecutiontime' );
		if ( ! $must && empty( $job_max_execution_time ) ) {
			return;
		}

		// No restart when restart was 3 seconds before.
		$execution_time = microtime( true ) - $this->timestamp_script_start;
		if ( ! $must && 3 > $execution_time ) {
			return;
		}

		// No restart if no working job.
		if ( ! file_exists( BackWPup::get_plugin_data( 'running_file' ) ) ) {
			return;
		}

		// Print message.
		if ( $this->is_debug() ) {
			if ( 0 !== $execution_time ) {
				$this->log(
					sprintf(
					/* translators: %d: time in seconds. */
					__( 'Restart after %1$d seconds.', 'backwpup' ),
					ceil( $execution_time )
				)
					);
			} elseif ( 0 !== $this->signal ) {
				$this->log( __( 'Restart after getting signal.', 'backwpup' ) );
			}
		}

		// Do things for a clean restart.
		$this->pid    = 0;
		$this->uniqid = '';
		$this->write_running_file();
		remove_action( 'shutdown', [ $this, 'shutdown' ] );
		// Restart job.
		wp_clear_scheduled_hook( 'backwpup_cron', [ 'arg' => 'restart' ] );
		wp_schedule_single_event( time() + 5, 'backwpup_cron', [ 'arg' => 'restart' ] );
		self::get_jobrun_url( 'restart' );

		exit();
	}

	/**
	 * Get a url to run a job of BackWPup.
	 *
	 * @param string $starttype Start types are 'runnow', 'runnowlink', 'cronrun', 'runext', 'restart', 'restartalt', 'test'.
	 * @param int    $jobid     The job ID to start, or 0.
	 *
	 * @return array|object [url] is the job url [header] for auth header or object form wp_remote_get()
	 */
	public static function get_jobrun_url( $starttype, $jobid = 0 ) {
		$authentication = get_site_option(
			'backwpup_cfg_authentication',
			[
				'method'         => '',
				'basic_user'     => '',
				'basic_password' => '',
				'user_id'        => 0,
				'query_arg'      => '',
			]
		);
		$url            = site_url( 'wp-cron.php' );
		$header         = [ 'Cache-Control' => 'no-cache' ];
		$authurl        = '';
		$query_args     = [
			'_nonce'        => substr( wp_hash( wp_nonce_tick() . 'backwpup_job_run-' . $starttype, 'nonce' ), -12, 10 ),
			'doing_wp_cron' => sprintf( '%.22F', microtime( true ) ),
		];

		if ( in_array( $starttype, [ 'restart', 'runnow', 'cronrun', 'runext', 'test' ], true ) ) {
			$query_args['backwpup_run'] = $starttype;
		}

		if ( in_array( $starttype, [ 'runnowlink', 'runnow', 'cronrun', 'runext' ], true ) && ! empty( $jobid ) ) {
			$query_args['jobid'] = $jobid;
		}

		if ( ! empty( $authentication['basic_user'] ) && ! empty( $authentication['basic_password'] ) && 'basic' === $authentication['method'] ) {
			$header['Authorization'] = 'Basic ' . Base64::encode( $authentication['basic_user'] . ':' . BackWPup_Encryption::decrypt( $authentication['basic_password'] ) );
			$authurl                 = rawurlencode( (string) $authentication['basic_user'] ) . ':' . rawurlencode( BackWPup_Encryption::decrypt( $authentication['basic_password'] ) ) . '@';
		}

		if ( ! empty( $authentication['query_arg'] ) && 'query_arg' === $authentication['method'] ) {
			$url .= '?' . $authentication['query_arg'];
		}

		if ( 'runext' === $starttype ) {
			$query_args['_nonce']        = md5( get_site_option( 'backwpup_cfg_jobrunauthkey' ) . $jobid );
			$query_args['doing_wp_cron'] = null;
			if ( ! empty( $authurl ) ) {
				$url = str_replace( 'https://', 'https://' . $authurl, $url );
				$url = str_replace( 'http://', 'http://' . $authurl, $url );
			}
		}

		if ( 'runnowlink' === $starttype && ( ! defined( 'ALTERNATE_WP_CRON' ) || ! ALTERNATE_WP_CRON ) ) {
			$url                         = wp_nonce_url( network_admin_url( 'admin.php' ), 'backwpup_job_run-' . $starttype );
			$query_args['page']          = 'backwpupjobs';
			$query_args['action']        = 'runnow';
			$query_args['doing_wp_cron'] = null;
			unset( $query_args['_nonce'] );
		}

		if ( 'runnowlink' === $starttype && defined( 'ALTERNATE_WP_CRON' ) && ALTERNATE_WP_CRON ) {
			$query_args['backwpup_run']  = 'runnowalt';
			$query_args['_nonce']        = substr(
				wp_hash( wp_nonce_tick() . 'backwpup_job_run-runnowalt', 'nonce' ),
				-12,
				10
			);
			$query_args['doing_wp_cron'] = null;
		}

		if ( 'restartalt' === $starttype && defined( 'ALTERNATE_WP_CRON' ) && ALTERNATE_WP_CRON ) {
			$query_args['backwpup_run'] = 'restart';
			$query_args['_nonce']       = null;
		}

		if ( 'restart' === $starttype || 'test' === $starttype ) {
			$query_args['_nonce'] = null;
		}

		if ( ! empty( $authentication['user_id'] ) && 'user' === $authentication['method'] ) {
			// Cache cookies for auth.
			$cookies = get_site_transient( 'backwpup_cookies' );
			if ( empty( $cookies ) ) {
				$wp_admin_user = get_users(
					[
						'role'   => 'administrator',
						'number' => 1,
					]
					);
				if ( empty( $wp_admin_user ) ) {
					$wp_admin_user = get_users(
						[
							'role'   => 'backwpup_admin',
							'number' => 1,
						]
						);
				}
				if ( ! empty( $wp_admin_user[0]->ID ) ) {
					$expiration                  = time() + ( 2 * DAY_IN_SECONDS );
					$manager                     = WP_Session_Tokens::get_instance( $wp_admin_user[0]->ID );
					$token                       = $manager->create( $expiration );
					$cookies[ LOGGED_IN_COOKIE ] = wp_generate_auth_cookie(
						$wp_admin_user[0]->ID,
						$expiration,
						'logged_in',
						$token
					);
				}
				set_site_transient( 'backwpup_cookies', $cookies, 2 * DAY_IN_SECONDS );
			}
		} else {
			$cookies = '';
		}

		$cron_request = [
			'url'  => add_query_arg( $query_args, $url ),
			'key'  => $query_args['doing_wp_cron'],
			'args' => [
				'blocking'   => ! function_exists( 'curl_init' ),
				'sslverify'  => false,
				'timeout'    => 0.01,
				'headers'    => $header,
				'user-agent' => BackWPup::get_plugin_data( 'User-Agent' ),
			],
		];

		if ( ! empty( $cookies ) ) {
			foreach ( $cookies as $name => $value ) {
				$cron_request['args']['cookies'][] = new WP_Http_Cookie(
					[
						'name'  => $name,
						'value' => $value,
					]
					);
			}
		}

		$cron_request = wpm_apply_filters_typed(
			'array',
			'cron_request',
			$cron_request
		);

		if ( 'test' === $starttype ) {
			$cron_request['args']['timeout']  = 15;
			$cron_request['args']['blocking'] = true;
		}

		if ( ! in_array( $starttype, [ 'runnowlink', 'runext', 'restartalt' ], true ) ) {
			delete_transient( 'doing_cron' );

			return wp_remote_post( $cron_request['url'], $cron_request['args'] );
		}

		return $cron_request;
	}

	/**
	 * Run baby run.
	 */
	public function run() {
		/**
		 * Database connection.
		 *
		 * @var wpdb $wpdb
		 */
		global $wpdb;

		// Disable output buffering.
		ob_implicit_flush( false );
		$level = ob_get_level();
		if ( $level ) {
			for ( $i = 0; $i < $level; ++$i ) {
				ob_end_clean();
			}
		}

		// Job can't run it is not created.
		if ( empty( $this->steps_todo ) || empty( $this->logfile ) ) {
			$running_file = BackWPup::get_plugin_data( 'running_file' );
			if ( file_exists( $running_file ) ) {
				@unlink( $running_file ); //phpcs:ignore
			}

			BackWPup_Admin::message( __( 'Backup can\'t be started because it is not created!', 'backwpup' ), true );
			return;
		}

		// Check double running and inactivity.
		$last_update = microtime( true ) - $this->timestamp_last_update;
		if ( ! empty( $this->pid ) && 300 < $last_update ) {
			$this->log( __( 'Job restarts due to inactivity for more than 5 minutes.', 'backwpup' ), E_USER_WARNING );
		} elseif ( ! empty( $this->pid ) ) {
			BackWPup_Admin::message( __( 'Run aborted because no PID given!', 'backwpup' ), true );
			return;
		}
		// Set timestamp of script start.
		$this->timestamp_script_start = microtime( true );
		// Set PID.
		$this->pid    = self::get_pid();
		$this->uniqid = uniqid( '', true );
		// Early write new working file.
		$this->write_running_file();
		// Configure error handling.
		if ( $this->is_debug() ) {
			set_error_handler( [ $this, 'log' ] ); // @phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler
			// Change error_log in php ini so that it writes to our log file. Specially for error that can't be handled by the error handler.
			if ( wp_is_ini_value_changeable( 'error_log' ) ) {
				@ini_set( 'error_log', $this->logfile ); // @phpcs:ignore
			}
			if ( wp_is_ini_value_changeable( 'html_errors' ) ) {
				@ini_set( 'html_errors', '0' ); // @phpcs:ignore
			}
			if ( wp_is_ini_value_changeable( 'log_errors' ) ) {
				@ini_set( 'log_errors', '1' ); // @phpcs:ignore
			}
		} else {
			set_error_handler( [ $this, 'log' ], E_ALL & ~E_NOTICE ); // @phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler
		}
		set_exception_handler( [ $this, 'exception_handler' ] );
		// disable Mixpanel error logging.
		add_filter( 'wp_media_mixpanel_debug', '__return_false' );
		if ( ! headers_sent() && wp_is_ini_value_changeable( 'zlib.output_compression' ) ) {
			@ini_set( 'zlib.output_compression', '0' ); // @phpcs:ignore
		}
		// Set WP max memory limit.
		if ( wp_is_ini_value_changeable( 'memory_limit' ) ) {
		    @ini_set( // @phpcs:ignore
				'memory_limit',
				wpm_apply_filters_typed(
					'string',
					'admin_memory_limit',
					WP_MAX_MEMORY_LIMIT
				)
			);
		}

		// Write WordPress DB errors to log.
		$wpdb->suppress_errors( false );
		$wpdb->hide_errors();

		// Set environment variables.
		if ( function_exists( 'putenv' ) ) {
			putenv( 'TMPDIR=' . BackWPup::get_plugin_data( 'TEMP' ) ); // @phpcs:ignore
		}

		// Register a shutdown function.
		add_action( 'shutdown', [ $this, 'shutdown' ] );

		if ( function_exists( 'pcntl_signal' ) ) {
			$signals = [
				'SIGHUP', // Term.
				'SIGINT', // Term.
				'SIGQUIT', // Core.
				'SIGILL', // Core.
				// 'SIGTRAP', // Core.
				'SIGABRT', // Core.
				'SIGBUS', // Core.
				'SIGFPE', // Core.
				// 'SIGKILL', // Term.
				'SIGSEGV', // Core.
				// 'SIGPIPE', // Term.
				// 'SIGALRM', // Term.
				'SIGTERM', // Term.
				'SIGSTKFLT', // Term.
				'SIGUSR1', // Term.
				'SIGUSR2', // Term.
				// 'SIGCHLD', // Ign.
				// 'SIGCONT', // Cont.
				// 'SIGSTOP', // Stop.
				// 'SIGTSTP', // Stop.
				// 'SIGTTIN', // Stop.
				// 'SIGTTOU', // Stop.
				// 'SIGURG', // Ign.
				'SIGXCPU', // Core.
				'SIGXFSZ', // Core.
				// 'SIGVTALRM', // Term.
				// 'SIGPROF', // Term.
				// 'SIGWINCH', // Ign.
				// 'SIGIO', // Term.
				'SIGPWR', // Term.
				'SIGSYS', // Core.
			];
			$signals = wpm_apply_filters_typed( 'array', 'backwpup_job_signals_to_handel', $signals );

			declare(ticks=1);
			$this->signal = 0;

			foreach ( $signals as $signal ) {
				if ( defined( $signal ) ) {
					pcntl_signal( constant( $signal ), [ $this, 'signal_handler' ], false );
				}
			}
		}
		$job_types = BackWPup::get_job_types();
		// Go step by step.
		foreach ( $this->steps_todo as $this->step_working ) {
			// Check if step already done.
			if ( in_array( $this->step_working, $this->steps_done, true ) ) {
				continue;
			}
			// Calculate step percent.
			if ( 0 < count( $this->steps_done ) ) {
				$this->step_percent = min(
					round( count( $this->steps_done ) / count( $this->steps_todo ) * 100 ),
					100
				);
			} else {
				$this->step_percent = 1;
			}
			// Do step tries.
			while ( true ) {
				if ( $this->steps_data[ $this->step_working ]['STEP_TRY'] >= get_site_option( 'backwpup_cfg_jobstepretry' ) ) {
					$this->log( __( 'Step aborted: too many attempts!', 'backwpup' ), E_USER_ERROR );
					$this->temp          = [];
					$this->steps_done[]  = $this->step_working;
					$this->substeps_done = 0;
					$this->substeps_todo = 0;
					$this->do_restart();
					break;
				}

				++$this->steps_data[ $this->step_working ]['STEP_TRY'];
				$done = false;

				// Execute the methods of job process.
				if ( 'CREATE_ARCHIVE' === $this->step_working ) {
					$done = $this->create_archive();
				} elseif ( 'ENCRYPT_ARCHIVE' === $this->step_working ) {
					$done = $this->encrypt_archive();
				} elseif ( 'CREATE_MANIFEST' === $this->step_working ) {
					$done = $this->create_manifest();
				} elseif ( 'END' === $this->step_working ) {
					$this->end();
					break 2;
				} elseif ( strstr( (string) $this->step_working, 'JOB_' ) ) {
					$done = $job_types[ str_replace( 'JOB_', '', (string) $this->step_working ) ]->job_run( $this );
				} elseif ( strstr( (string) $this->step_working, 'DEST_SYNC_' ) ) {
					$done = BackWPup::get_destination( str_replace( 'DEST_SYNC_', '', (string) $this->step_working ) )
						->job_run_sync( $this );
				} elseif ( strstr( (string) $this->step_working, 'DEST_' ) ) {
					$done = BackWPup::get_destination( str_replace( 'DEST_', '', (string) $this->step_working ) )
						->job_run_archive( $this );
				} elseif ( ! empty( $this->steps_data[ $this->step_working ]['CALLBACK'] ) ) {
					$done = $this->steps_data[ $this->step_working ]['CALLBACK']( $this );
				}

				// Set step as done.
				if ( true === $done ) {
					$this->temp          = [];
					$this->steps_done[]  = $this->step_working;
					$this->substeps_done = 0;
					$this->substeps_todo = 0;
					$this->update_working_data( true );
				}
				if ( $done && strstr( (string) $this->step_working, 'DEST_' ) ) {

					// Retrieve the destintation ID.
					$destination = str_replace( 'DEST_', '', (string) $this->step_working );
					if ( strstr( (string) $this->step_working, 'DEST_SYNC_' ) ) {
						$destination = str_replace( 'DEST_SYNC_', '', (string) $this->step_working );
					}

					/**
					 * Action fires with backup success.
					 *
					 * @param array $job Job details.
					 * @param string $destination Destination ID.
					 * @param string $backup_file Backup file name.
					 */
					do_action( 'backwpup_job_success', $this->job, $destination, $this->backup_file );
				}
				if ( count( $this->steps_done ) < count( $this->steps_todo ) - 1 ) {
					$this->do_restart();
				}
				if ( true === $done ) {
					break;
				}
			}
		}
	}

	/**
	 * Creates the backup archive.
	 */
	private function create_archive() {
		// Load folders to back up.
		$folders_to_backup = $this->get_folders_to_backup();

		$this->substeps_todo = $this->count_folder + 1;

		// Initial settings for restarts in archiving.
		if ( ! isset( $this->steps_data[ $this->step_working ]['on_file'] ) ) {
			$this->steps_data[ $this->step_working ]['on_file'] = '';
		}
		if ( ! isset( $this->steps_data[ $this->step_working ]['on_folder'] ) ) {
			$this->steps_data[ $this->step_working ]['on_folder'] = '';
		}

		if ( '' === $this->steps_data[ $this->step_working ]['on_folder'] && '' === $this->steps_data[ $this->step_working ]['on_file'] && is_file( $this->backup_folder . $this->backup_file ) ) {
			@unlink( $this->backup_folder . $this->backup_file ); //phpcs:ignore
		}

		if ( $this->steps_data[ $this->step_working ]['SAVE_STEP_TRY'] !== $this->steps_data[ $this->step_working ]['STEP_TRY'] ) {
			$this->log(
				sprintf(
					/* translators: %d: attempt number. */
					__( '%d. Trying to create backup archive &hellip;', 'backwpup' ),
					$this->steps_data[ $this->step_working ]['STEP_TRY']
				),
				E_USER_NOTICE
			);
		}

		try {
			$backup_archive = new BackWPup_Create_Archive( $this->backup_folder . $this->backup_file );

			// Show method for creation.
			if ( 0 === $this->substeps_done ) {
				$this->log(
					sprintf(
					/* translators: %s: archive compression method. */
					_x(
						'Compressing files as %s. Please be patient, this may take a moment.',
						'Archive compression method',
						'backwpup'
					),
					$backup_archive->get_method()
				)
					);
			}

			// Add extra files.
			if ( 0 === $this->substeps_done ) {
				if ( ! empty( $this->additional_files_to_backup ) ) {
					if ( $this->is_debug() ) {
						$this->log( __( 'Adding Extra files to Archive', 'backwpup' ) );
					}

					foreach ( $this->additional_files_to_backup as $file ) {
						// Generate top-level filename.
						// Requires special handling in case of "use one folder above".
						$archive_filename = ltrim( $this->get_destination_path_replacement( ABSPATH . basename( $file ) ), '/' );
						if ( $backup_archive->add_file( $file, $archive_filename ) ) {
							++$this->count_files;
							$this->count_files_size = $this->count_files_size + filesize( $file );
							$this->update_working_data();
						} else {
							$backup_archive->close();
							$this->steps_data[ $this->step_working ]['on_file']   = '';
							$this->steps_data[ $this->step_working ]['on_folder'] = '';
							$this->log(
								__( 'Cannot create backup archive correctly. Aborting creation.', 'backwpup' ),
								E_USER_ERROR
							);

							return false;
						}
					}
				}
				++$this->substeps_done;
			}

			// Add normal files.
			$folder = array_shift( $folders_to_backup );
			while ( null !== $folder ) {
				// Jump over already done folders.
				if ( in_array( $this->steps_data[ $this->step_working ]['on_folder'], $folders_to_backup, true ) ) {
					$folder = array_shift( $folders_to_backup );
					continue;
				}
				if ( $this->is_debug() ) {
					$this->log(
						sprintf(
						/* translators: %s: folder path. */
						__( 'Archiving Folder: %s', 'backwpup' ),
						$folder
					)
						);
				}
				$this->steps_data[ $this->step_working ]['on_folder'] = $folder;
				$files_in_folder                                      = $this->get_files_in_folder( $folder );
				// Add empty folders.
				if ( empty( $files_in_folder ) ) {
					$folder_name_in_archive = trim( ltrim( $this->get_destination_path_replacement( $folder ), '/' ) );
					if ( ! empty( $folder_name_in_archive ) ) {
						$backup_archive->add_empty_folder( $folder, $folder_name_in_archive );
					}

					$folder = array_shift( $folders_to_backup );
					continue;
				}
				// Add files.
				$file = array_shift( $files_in_folder );
				while ( null !== $file ) {
					// Jump over already done files.
					if ( in_array( $this->steps_data[ $this->step_working ]['on_file'], $files_in_folder, true ) ) {
						$file = array_shift( $files_in_folder );
						continue;
					}
					if ( $this->maybe_sql_dump( $file ) ) {
						$file = array_shift( $files_in_folder );
						continue;
					}

					$this->steps_data[ $this->step_working ]['on_file'] = $file;
					// Restart if needed.
					$restart_time = $this->get_restart_time();
					if ( 0 >= $restart_time ) {
						unset( $backup_archive );
						$this->do_restart_time( true );

						return false;
					}
					// Generate filename in archive.
					$in_archive_filename = ltrim( $this->get_destination_path_replacement( $file ), '/' );
					// Add file to archive.
					if ( $backup_archive->add_file( $file, $in_archive_filename ) ) {
						++$this->count_files;
						$this->count_files_size = $this->count_files_size + filesize( $file );
						$this->update_working_data();
					} else {
						$backup_archive->close();
						unset( $backup_archive );
						$this->steps_data[ $this->step_working ]['on_file']   = '';
						$this->steps_data[ $this->step_working ]['on_folder'] = '';
						$this->substeps_done                                  = 0;
						$this->backup_filesize                                = filesize( $this->backup_folder . $this->backup_file );
						if ( false === $this->backup_filesize ) {
							$this->backup_filesize = PHP_INT_MAX;
						}
						$this->log(
							__( 'Cannot create backup archive correctly. Aborting creation.', 'backwpup' ),
							E_USER_ERROR
						);

						return false;
					}
					$file = array_shift( $files_in_folder );
				}
				$this->steps_data[ $this->step_working ]['on_file'] = '';
				++$this->substeps_done;
				$folder = array_shift( $folders_to_backup );
			}
			$backup_archive->close();
			unset( $backup_archive );
			$this->log( __( 'Backup archive created.', 'backwpup' ), E_USER_NOTICE );
		} catch ( Exception $e ) {
			$this->log( $e->getMessage(), E_USER_ERROR, $e->getFile(), $e->getLine() );

			return false;
		}

		$this->backup_filesize = filesize( $this->backup_folder . $this->backup_file );
		if ( false === $this->backup_filesize ) {
			$this->backup_filesize = PHP_INT_MAX;
		}

		if ( PHP_INT_MAX <= $this->backup_filesize ) {
			$this->log(
				__(
					'The Backup archive will be too large for file operations with this PHP Version. You might want to consider splitting the backup job in multiple jobs with less files each.',
					'backwpup'
				),
				E_USER_ERROR
			);
			$this->end();
		} else {
			$this->log(
				sprintf(
					/* translators: %s: archive size. */
					__( 'Archive size is %s.', 'backwpup' ),
					size_format( $this->backup_filesize, 2 )
				),
				E_USER_NOTICE
			);
		}

		$this->log(
			sprintf(
				/* translators: 1: number of files, 2: total size. */
				__( '%1$d Files with %2$s in Archive.', 'backwpup' ),
				$this->count_files,
				size_format( $this->count_files_size, 2 )
			),
			E_USER_NOTICE
		);

		return true;
	}

	/**
	 * Encrypt Archive.
	 *
	 * Encrypt the backup archive.
	 *
	 * @return bool True when done, false otherwise.
	 */
	private function encrypt_archive() {
		$encryption_type = get_site_option( 'backwpup_cfg_encryption' );
		// Substeps are number of 128 KB chunks.
		$block_size          = 128 * 1024;
		$this->substeps_todo = $this->backup_filesize;

		if ( ! isset( $this->steps_data[ $this->step_working ]['encrypted_filename'] ) ) {
			$this->steps_data[ $this->step_working ]['encrypted_filename'] = $this->backup_folder . $this->backup_file . '.encrypted';
			if ( is_file( $this->steps_data[ $this->step_working ]['encrypted_filename'] ) ) {
				@unlink( $this->steps_data[ $this->step_working ]['encrypted_filename'] ); //phpcs:ignore
			}
		}

		if ( ! isset( $this->steps_data[ $this->step_working ]['key'] ) ) {
			$this->steps_data[ $this->step_working ]['OutFilePos'] = 0;
			$this->steps_data[ $this->step_working ]['aesIv']      = \phpseclib3\Crypt\Random::string( 16 );
			switch ( $encryption_type ) {
				case self::ENCRYPTION_SYMMETRIC:
					$this->steps_data[ $this->step_working ]['key'] = pack( 'H*', get_site_option( 'backwpup_cfg_encryptionkey' ) );
					break;

				case self::ENCRYPTION_ASYMMETRIC:
					$this->steps_data[ $this->step_working ]['key'] = \phpseclib3\Crypt\Random::string( 32 );
					break;
			}

			if ( empty( $this->steps_data[ $this->step_working ]['key'] ) ) {
				$this->log( __( 'No encryption key was provided. Aborting encryption.', 'backwpup' ), E_USER_WARNING );

				return false;
			}
		}

		if ( $this->steps_data[ $this->step_working ]['SAVE_STEP_TRY'] !== $this->steps_data[ $this->step_working ]['STEP_TRY'] ) {
			// Show initial log message.
			$this->log(
				sprintf(
					/* translators: %d: attempt number. */
					__( '%d. Trying to encrypt archive &hellip;', 'backwpup' ),
					$this->steps_data[ $this->step_working ]['STEP_TRY']
				),
				E_USER_NOTICE
			);
		}

		try {
			$file_in = Utils::streamFor( Utils::tryFopen( $this->backup_folder . $this->backup_file, 'r' ) );
		} catch ( \RuntimeException $e ) {
			$this->log( __( 'Cannot open the archive for reading. Aborting encryption.', 'backwpup' ), E_USER_ERROR );

			return false;
		}

		try {
			$file_out = Utils::tryFopen( $this->steps_data[ $this->step_working ]['encrypted_filename'], 'a+' );
		} catch ( \RuntimeException $e ) {
			$this->log( __( 'Cannot write the encrypted archive. Aborting encryption.', 'backwpup' ), E_USER_ERROR );

			return false;
		}

		$encryptor = null;
		$key       = $this->steps_data[ $this->step_working ]['key'];
		$aes_iv    = $this->steps_data[ $this->step_working ]['aesIv'];

		switch ( $encryption_type ) {
			case self::ENCRYPTION_SYMMETRIC:
				$encryptor = new EncryptionStream( $aes_iv, $key, Utils::streamFor( $file_out ) );
				break;

			case self::ENCRYPTION_ASYMMETRIC:
				$rsa_pub_key = get_site_option( 'backwpup_cfg_publickey' );
				$encryptor   = new EncryptionStream( $aes_iv, $key, Utils::streamFor( $file_out ), $rsa_pub_key );
				break;
		}

		if ( null === $encryptor ) {
			$this->log( __( 'Could not initialize encryptor.', 'backwpup' ), E_USER_ERROR );

			return false;
		}

		$file_in->seek( $this->substeps_done );
		$encryptor->seek( $this->steps_data[ $this->step_working ]['OutFilePos'] );

		while ( ! $file_in->eof() ) {
			$data                 = $file_in->read( $block_size );
			$out_bytes            = $encryptor->write( $data );
			$this->substeps_done += $block_size;
			$this->steps_data[ $this->step_working ]['OutFilePos'] += $out_bytes;
			$this->update_working_data();
			// Should we restart?
			$restart_time = $this->get_restart_time();
			if ( 0 >= $restart_time ) {
				$file_in->close();
				$encryptor->close();
				$this->do_restart_time( true );
			}
		}
		$file_in->close();
		$encryptor->close();

		$this->log(
			sprintf(
				/* translators: %s: encrypted data size. */
				__( 'Encrypted %s of data.', 'backwpup' ),
				size_format( $this->substeps_done, 2 )
			),
			E_USER_NOTICE
		);

		// Remove the original file then rename the encrypted file.
		if ( ! @unlink( $this->backup_folder . $this->backup_file ) ) { //phpcs:ignore
			$this->log( __( 'Unable to delete unencrypted archive.', 'backwpup' ) );

			return false;
		}
		if ( ! rename( // phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
			$this->steps_data[ $this->step_working ]['encrypted_filename'],
			$this->backup_folder . $this->backup_file
		) ) {
			$this->log( __( 'Unable to rename encrypted archive.', 'backwpup' ) );

			return false;
		}

		$this->backup_filesize = filesize( $this->backup_folder . $this->backup_file );
		$this->log( __( 'Archive has been successfully encrypted.', 'backwpup' ) );

		return true;
	}

	/**
	 * Get list of Folders for backup.
	 *
	 * @return string[] Folder list.
	 */
	public function get_folders_to_backup() {
		$file = BackWPup::get_plugin_data( 'temp' ) . 'backwpup-' . BackWPup::get_plugin_data( 'hash' ) . '-folder.php';

		if ( ! file_exists( $file ) ) {
			return [];
		}

		$folders = [];

		$file_data = file( $file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );

		foreach ( $file_data as $folder ) {
			$folder = trim( str_replace( [ '<?php', '//' ], '', (string) $folder ) );
			if ( ! empty( $folder ) && is_dir( $folder ) ) {
				$folders[] = $folder;
			}
		}
		$folders = array_unique( $folders );
		sort( $folders );
		$this->count_folder = count( $folders );

		return $folders;
	}

	/**
	 * Get an array of files to back up in the selected folder.
	 *
	 * @param string $folder The folder to get the files from.
	 *
	 * @return string[] Files to back up.
	 */
	public function get_files_in_folder( $folder ) {
		$files  = [];
		$folder = trailingslashit( $folder );

		if ( ! is_dir( $folder ) ) {
			$this->log(
				sprintf(
					/* translators: %s: folder path. */
					_x( 'Folder %s does not exist', 'Folder name', 'backwpup' ),
					$folder
				),
				E_USER_WARNING
			);

			return $files;
		}

		if ( ! is_readable( $folder ) ) {
			$this->log(
				sprintf(
					/* translators: %s: folder path. */
					_x( 'Folder %s is not readable', 'Folder name', 'backwpup' ),
					$folder
				),
				E_USER_WARNING
			);

			return $files;
		}

		try {
			$dir = new BackWPup_Directory( $folder );

			foreach ( $dir as $file ) {
				if ( $file->isDot() || $file->isDir() ) {
					continue;
				}

				$path = BackWPup_Path_Fixer::slashify( $file->getPathname() );

				foreach ( $this->exclude_from_backup as $exclusion ) { // Exclude files.
					$exclusion = trim( (string) $exclusion );
					if ( false !== stripos( (string) $path, $exclusion ) && ! empty( $exclusion ) ) {
						continue 2;
					}
				}

				if ( $this->job['backupexcludethumbs'] && false !== strpos(
					$folder,
					BackWPup_File::get_upload_dir()
				) && preg_match(
					'/\\-[0-9]{1,4}x[0-9]{1,4}.+\\.(jpg|png|gif|webp)$/i',
					$file->getFilename()
				) ) {
					continue;
				}

				if ( $file->isLink() ) {
					$this->log(
						sprintf(
							/* translators: %s: file path. */
							__( 'Link "%s" not following.', 'backwpup' ),
							$file->getPathname()
						),
						E_USER_WARNING
					);
				} elseif ( ! $file->isReadable() ) {
					$this->log(
						sprintf(
							/* translators: %s: file path. */
							__( 'File "%s" is not readable!', 'backwpup' ),
							$file->getPathname()
						),
						E_USER_WARNING
					);
				} else {
					$file_size = $file->getSize();
					if ( ! is_int( $file_size ) || 0 > $file_size || 2147483647 < $file_size ) {
						$this->log(
							sprintf(
								/* translators: %s: file path and size. */
								__(
									'File size of “%s” cannot be retrieved. File might be too large and will not be added to queue.',
									'backwpup'
								),
								$file->getPathname() . ' ' . $file_size
							),
							E_USER_WARNING
						);

						continue;
					}

					$files[] = BackWPup_Path_Fixer::slashify( realpath( $path ) );
				}
			}
		} catch ( UnexpectedValueException $e ) {
			$this->log(
				sprintf(
					// translators: %s: path.
					__( 'Could not open path: %s', 'backwpup' ),
					$e->getMessage()
				),
				E_USER_WARNING
			);
		}

		return $files;
	}

	/**
	 * Get job restart time.
	 *
	 * @return int Remaining time.
	 */
	public function get_restart_time() {
		if ( 'cli' === php_sapi_name() ) {
			return 300;
		}

		$job_max_execution_time = get_site_option( 'backwpup_cfg_jobmaxexecutiontime' );

		if ( empty( $job_max_execution_time ) ) {
			return 300;
		}

		$execution_time = microtime( true ) - $this->timestamp_script_start;

		return $job_max_execution_time - $execution_time - 3;
	}

	/**
	 * Do a job restart.
	 *
	 * @param bool $do_restart_now Whether to restart immediately.
	 *
	 * @return int Remaining time.
	 */
	public function do_restart_time( $do_restart_now = false ) {
		if ( 'cli' === php_sapi_name() ) {
			return 300;
		}

		// Do restart after signal is sent.
		if ( 0 !== $this->signal ) {
			$this->steps_data[ $this->step_working ]['SAVE_STEP_TRY'] = $this->steps_data[ $this->step_working ]['STEP_TRY'];
			--$this->steps_data[ $this->step_working ]['STEP_TRY'];
			$this->do_restart( true );
		}

		$job_max_execution_time = get_site_option( 'backwpup_cfg_jobmaxexecutiontime' );

		if ( empty( $job_max_execution_time ) ) {
			return 300;
		}

		$execution_time = microtime( true ) - $this->timestamp_script_start;

		// Do restart 3 seconds before max execution time.
		if ( $do_restart_now || $execution_time >= ( $job_max_execution_time - 3 ) ) {
			$this->steps_data[ $this->step_working ]['SAVE_STEP_TRY'] = $this->steps_data[ $this->step_working ]['STEP_TRY'];
			--$this->steps_data[ $this->step_working ]['STEP_TRY'];
			$this->do_restart( true );
		}

		return $job_max_execution_time - $execution_time;
	}

	/**
	 * Create manifest file.
	 *
	 * @return bool
	 */
	public function create_manifest() {
		$this->substeps_todo = 3;

		$this->log(
			sprintf(
			/* translators: %d: attempt number. */
			__( '%d. Trying to generate a manifest file&#160;&hellip;', 'backwpup' ),
			$this->steps_data[ $this->step_working ]['STEP_TRY']
		)
			);

		// Build manifest.
		$manifest = [];
		// Add blog information.
		$manifest['blog_info']['url']                  = home_url();
		$manifest['blog_info']['wpurl']                = site_url();
		$manifest['blog_info']['prefix']               = $GLOBALS[ \wpdb::class ]->prefix;
		$manifest['blog_info']['description']          = get_option( 'blogdescription' );
		$manifest['blog_info']['stylesheet_directory'] = get_template_directory_uri();
		$manifest['blog_info']['activate_plugins']     = wp_get_active_and_valid_plugins();
		$manifest['blog_info']['activate_theme']       = wp_get_theme()->get( 'Name' );
		$manifest['blog_info']['admin_email']          = get_option( 'admin_email' );
		$manifest['blog_info']['charset']              = get_bloginfo( 'charset' );
		$manifest['blog_info']['version']              = BackWPup::get_plugin_data( 'wp_version' );
		$manifest['blog_info']['backwpup_version']     = BackWPup::get_plugin_data( 'version' );
		$manifest['blog_info']['language']             = get_bloginfo( 'language' );
		$manifest['blog_info']['name']                 = get_bloginfo( 'name' );
		$manifest['blog_info']['abspath']              = ABSPATH;
		$manifest['blog_info']['uploads']              = wp_upload_dir( null, false, true );
		$manifest['blog_info']['contents']['basedir']  = WP_CONTENT_DIR;
		$manifest['blog_info']['contents']['baseurl']  = WP_CONTENT_URL;
		$manifest['blog_info']['plugins']['basedir']   = WP_PLUGIN_DIR;
		$manifest['blog_info']['plugins']['baseurl']   = WP_PLUGIN_URL;
		$manifest['blog_info']['themes']['basedir']    = get_theme_root();
		$manifest['blog_info']['themes']['baseurl']    = get_theme_root_uri();

		// Add job settings.
		$manifest['job_settings'] = [
			'dbdumptype'            => $this->job['dbdumptype'],
			'dbdumpfile'            => $this->job['dbdumpfile'],
			'dbdumpfilecompression' => $this->job['dbdumpfilecompression'],
			'dbdumpdbcharset'       => ! empty( $this->job['dbdumpdbcharset'] ) ? $this->job['dbdumpdbcharset'] : '',
			'type'                  => $this->job['type'],
			'destinations'          => $this->job['destinations'],
			'backuptype'            => $this->job['backuptype'],
			'archiveformat'         => $this->job['archiveformat'],
			'dbdumpexclude'         => $this->job['dbdumpexclude'],
		];

		// Add archive info.
		foreach ( $this->additional_files_to_backup as $file ) {
			$manifest['archive']['extra_files'][] = basename( $file );
		}
		if ( isset( $this->steps_data['JOB_FILE'] ) ) {
			if ( $this->job['backuproot'] ) {
				$manifest['archive']['abspath'] = trailingslashit( $this->get_destination_path_replacement( ABSPATH ) );
			}
			if ( $this->job['backupuploads'] ) {
				$manifest['archive']['uploads'] = trailingslashit( $this->get_destination_path_replacement( BackWPup_File::get_upload_dir() ) );
			}
			if ( $this->job['backupcontent'] ) {
				$manifest['archive']['contents'] = trailingslashit( $this->get_destination_path_replacement( WP_CONTENT_DIR ) );
			}
			if ( $this->job['backupplugins'] ) {
				$manifest['archive']['plugins'] = trailingslashit( $this->get_destination_path_replacement( WP_PLUGIN_DIR ) );
			}
			if ( $this->job['backupthemes'] ) {
				$manifest['archive']['themes'] = trailingslashit( $this->get_destination_path_replacement( get_theme_root() ) );
			}
		}

		$filesystem    = backwpup_wpfilesystem();
		$manifest_json = wp_json_encode( $manifest );
		if ( false === $filesystem->put_contents( BackWPup::get_plugin_data( 'TEMP' ) . 'manifest.json', $manifest_json ) ) {
			return false;
		}
		$this->substeps_done = 1;

		// Create backwpup_readme.txt.
		$readme_text  = __( 'You may have noticed the manifest.json file in this archive.', 'backwpup' ) . PHP_EOL;
		$readme_text .= __(
			'manifest.json might be needed for later restoring a backup from this archive.',
			'backwpup'
		) . PHP_EOL;
		$readme_text .= __(
			'Please leave manifest.json untouched and in place. Otherwise it is safe to be ignored.',
			'backwpup'
		) . PHP_EOL;
		if ( false === $filesystem->put_contents( BackWPup::get_plugin_data( 'TEMP' ) . 'backwpup_readme.txt', $readme_text ) ) {
			return false;
		}
		$this->substeps_done = 2;

		// Add file to backup files.
		if ( is_readable( BackWPup::get_plugin_data( 'TEMP' ) . 'manifest.json' ) ) {
			$this->additional_files_to_backup[] = BackWPup::get_plugin_data( 'TEMP' ) . 'manifest.json';
			$this->additional_files_to_backup[] = BackWPup::get_plugin_data( 'TEMP' ) . 'backwpup_readme.txt';
			$this->log(
				sprintf(
				/* translators: %s: file size. */
				__( 'Added manifest.json file with %1$s to backup file list.', 'backwpup' ),
				size_format( filesize( BackWPup::get_plugin_data( 'TEMP' ) . 'manifest.json' ), 2 )
			)
				);
		}
		$this->substeps_done = 3;

		return true;
	}

	/**
	 * Start a job in CLI context.
	 *
	 * @param int $jobid Job ID.
	 */
	public static function start_cli( $jobid ) {
		if ( 'cli' !== php_sapi_name() ) {
			return;
		}

		$jobid = absint( $jobid );

		// Logs folder.
		$log_folder = get_site_option( 'backwpup_cfg_logfolder' );
		$log_folder = BackWPup_File::get_absolute_path( $log_folder );

		// Check job ID exists.
		$jobids = BackWPup_Option::get_job_ids();
		if ( ! in_array( $jobid, $jobids, true ) ) {
			exit( esc_html__( 'Wrong BackWPup JobID', 'backwpup' ) );
		}
		// Check folders.
		$log_folder_message = BackWPup_File::check_folder( $log_folder );
		if ( ! empty( $log_folder_message ) ) {
			exit( esc_html( $log_folder_message ) );
		}
		$log_folder_message = BackWPup_File::check_folder( BackWPup::get_plugin_data( 'TEMP' ), true );
		if ( ! empty( $log_folder_message ) ) {
			exit( esc_html( $log_folder_message ) );
		}
		// Check running job.
		if ( file_exists( BackWPup::get_plugin_data( 'running_file' ) ) ) {
			exit( esc_html__( 'A BackWPup job is already running', 'backwpup' ) );
		}

		// Start class.
		$backwpup_job_object = new self();
		$backwpup_job_object->create( 'runcli', (int) $jobid );
		$backwpup_job_object->run();
	}

	/**
	 * Disable caches.
	 */
	public static function disable_caches() {
		// Special settings.
		if ( function_exists( 'putenv' ) ) {
			putenv( 'nokeepalive=1' ); // @phpcs:ignore
		}
		if ( ! headers_sent() && wp_is_ini_value_changeable( 'zlib.output_compression' ) ) {
			@ini_set( 'zlib.output_compression', '0' ); // @phpcs:ignore
		}

		// Deactivate caches.
		if ( function_exists( 'wp_suspend_cache_addition' ) ) {
			wp_suspend_cache_addition( true );
		}
		if ( function_exists( 'wp_cache_disable' ) ) {
			wp_cache_disable();
		}
	}

	/**
	 * Reads a BackWPup logfile header and gives back a array of information.
	 *
	 * @param string $logfile Full logfile path.
	 *
	 * @return array|bool
	 */
	public static function read_logheader( $logfile ) {
		$usedmetas = [
			'date'                    => 'logtime',
			'backwpup_logtime'        => 'logtime', // Old value of date.
			'backwpup_errors'         => 'errors',
			'backwpup_warnings'       => 'warnings',
			'backwpup_jobid'          => 'jobid',
			'backwpup_jobname'        => 'name',
			'backwpup_jobtype'        => 'type',
			'backwpup_jobruntime'     => 'runtime',
			'backwpup_backupfilesize' => 'backupfilesize',
		];

		// Get metadata of logfile.
		$metas = [];
		if ( is_readable( $logfile ) ) {
			if ( '.gz' === substr( $logfile, -3 ) ) {
				$metas = (array) get_meta_tags( 'compress.zlib://' . $logfile );
			} else {
				$metas = (array) get_meta_tags( $logfile );
			}
		}

		// Only output needed data.
		foreach ( $usedmetas as $keyword => $field ) {
			if ( isset( $metas[ $keyword ] ) ) {
				$joddata[ $field ] = $metas[ $keyword ];
			} else {
				$joddata[ $field ] = '';
			}
		}

		// Convert date.
		if ( isset( $metas['date'] ) ) {
			$joddata['logtime'] = strtotime( (string) $metas['date'] ) + ( get_option( 'gmt_offset' ) * 3600 );
		}

		// use file create date if none.
		if ( empty( $joddata['logtime'] ) ) {
			if ( file_exists( $logfile ) ) {
				$joddata['logtime'] = filectime( $logfile );
			} else {
				$joddata['logtime'] = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
			}
		}

		return $joddata;
	}

	/**
	 * Mark the current running job as aborted.
	 *
	 * @return void
	 */
	public static function user_abort() {
		/**
		 * Running job object.
		 *
		 * @var BackWPup_Job $job_object
		 */
		$job_object = self::get_working_data();

		$running_file = BackWPup::get_plugin_data( 'running_file' );
		if ( file_exists( $running_file ) ) {
			wp_delete_file( $running_file );
		}

		if ( ! $job_object instanceof self ) {
			return;
		}

		// If job not working currently, abort it this way for message.
		$not_worked_time = microtime( true ) - $job_object->timestamp_last_update;
		$restart_time    = get_site_option( 'backwpup_cfg_jobmaxexecutiontime' );
		if ( empty( $restart_time ) ) {
			$restart_time = 60;
		}
		if ( empty( $job_object->pid ) || $restart_time < $not_worked_time ) {
			$job_object->user_abort = true;
			$job_object->update_working_data();
		}
	}

	/**
	 * Delete some data on cloned objects.
	 */
	public function __clone() {
		$this->temp = [];
		$this->run  = [];
	}

	/**
	 * Signal handler.
	 *
	 * @param int $signal_send Signal number.
	 */
	public function signal_handler( $signal_send ) {
		// Known signals.
		$signals = [
			'SIGHUP'    => [
				'description' => _x(
					'Hangup detected on controlling terminal or death of controlling process',
					'SIGHUP: Please see http://man7.org/linux/man-pages/man7/signal.7.html for details',
					'backwpup'
				),
				'error'       => E_USER_ERROR,
			],
			'SIGINT'    => [
				'description' => _x(
					'Interrupt from keyboard',
					'SIGINT: Please see http://man7.org/linux/man-pages/man7/signal.7.html for details',
					'backwpup'
				),
				'error'       => E_USER_ERROR,
			],
			'SIGQUIT'   => [
				'description' => _x(
					'Quit from keyboard',
					'SIGQUIT: Please see http://man7.org/linux/man-pages/man7/signal.7.html for details',
					'backwpup'
				),
				'error'       => E_USER_ERROR,
			],
			'SIGILL'    => [
				'description' => _x(
					'Illegal Instruction',
					'SIGILL: Please see http://man7.org/linux/man-pages/man7/signal.7.html for details',
					'backwpup'
				),
				'error'       => E_USER_ERROR,
			],
			'SIGABRT'   => [
				'description' => _x(
					'Abort signal from abort(3)',
					'SIGABRT: Please see http://man7.org/linux/man-pages/man7/signal.7.html for details',
					'backwpup'
				),
				'error'       => E_USER_NOTICE,
			],
			'SIGBUS'    => [
				'description' => _x(
					'Bus error (bad memory access)',
					'SIGBUS: Please see http://man7.org/linux/man-pages/man7/signal.7.html for details',
					'backwpup'
				),
				'error'       => E_USER_ERROR,
			],
			'SIGFPE'    => [
				'description' => _x(
					'Floating point exception',
					'SIGFPE: Please see http://man7.org/linux/man-pages/man7/signal.7.html for details',
					'backwpup'
				),
				'error'       => E_USER_ERROR,
			],
			'SIGSEGV'   => [
				'description' => _x(
					'Invalid memory reference',
					'SIGSEGV: Please see http://man7.org/linux/man-pages/man7/signal.7.html for details',
					'backwpup'
				),
				'error'       => E_USER_ERROR,
			],
			'SIGTERM'   => [
				'description' => _x(
					'Termination signal',
					'SIGTERM: Please see http://man7.org/linux/man-pages/man7/signal.7.html for details',
					'backwpup'
				),
				'error'       => E_USER_WARNING,
			],
			'SIGSTKFLT' => [
				'description' => _x(
					'Stack fault on coprocessor',
					'SIGSTKFLT: Please see http://man7.org/linux/man-pages/man7/signal.7.html for details',
					'backwpup'
				),
				'error'       => E_USER_ERROR,
			],
			'SIGUSR1'   => [
				'description' => _x(
					'User-defined signal 1',
					'SIGUSR1: Please see http://man7.org/linux/man-pages/man7/signal.7.html for details',
					'backwpup'
				),
				'error'       => E_USER_NOTICE,
			],
			'SIGUSR2'   => [
				'description' => _x(
					'User-defined signal 2',
					'SIGUSR2: Please see http://man7.org/linux/man-pages/man7/signal.7.html for details',
					'backwpup'
				),
				'error'       => E_USER_NOTICE,
			],
			'SIGURG'    => [
				'description' => _x(
					'Urgent condition on socket',
					'SIGURG: Please see http://man7.org/linux/man-pages/man7/signal.7.html for details',
					'backwpup'
				),
				'error'       => E_USER_NOTICE,
			],
			'SIGXCPU'   => [
				'description' => _x(
					'CPU time limit exceeded',
					'SIGXCPU: Please see http://man7.org/linux/man-pages/man7/signal.7.html for details',
					'backwpup'
				),
				'error'       => E_USER_ERROR,
			],
			'SIGXFSZ'   => [
				'description' => _x(
					'File size limit exceeded',
					'SIGXFSZ: Please see http://man7.org/linux/man-pages/man7/signal.7.html for details',
					'backwpup'
				),
				'error'       => E_USER_ERROR,
			],
			'SIGPWR'    => [
				'description' => _x(
					'Power failure',
					'SIGPWR: Please see http://man7.org/linux/man-pages/man7/signal.7.html for details',
					'backwpup'
				),
				'error'       => E_USER_ERROR,
			],
			'SIGSYS'    => [
				'description' => _x(
					'Bad argument to routine',
					'SIGSYS: Please see http://man7.org/linux/man-pages/man7/signal.7.html for details',
					'backwpup'
				),
				'error'       => E_USER_ERROR,
			],
		];

		foreach ( $signals as $signal => $config ) {
			if ( defined( $signal ) && constant( $signal ) === $signal_send ) {
				$this->log(
					sprintf(
						/* translators: 1: signal name, 2: signal description. */
						__( 'Signal "%1$s" (%2$s) is sent to script!', 'backwpup' ),
						$signal,
						$config['description']
					),
					$config['error']
				);
				$this->signal = $signal_send;
				break;
			}
		}
	}

	/**
	 * Shutdown function is called if script terminates, try to restart if needed.
	 *
	 * Prepare the job for start.
	 *
	 * @internal param int the signal that terminates the job
	 */
	public function shutdown() {
		// Put last error to log if any.
		$lasterror = error_get_last();
		if ( E_ERROR === $lasterror['type']
			|| E_PARSE === $lasterror['type']
			|| E_CORE_ERROR === $lasterror['type']
			|| E_CORE_WARNING === $lasterror['type']
			|| E_COMPILE_ERROR === $lasterror['type']
			|| E_COMPILE_WARNING === $lasterror['type'] ) {
			$this->log( $lasterror['type'], $lasterror['message'], $lasterror['file'], $lasterror['line'] );
		}

		$error = false;
		if ( function_exists( 'pcntl_get_last_error' ) ) {
			$error = pcntl_get_last_error();
			if ( ! empty( $error ) ) {
				$error_msg = pcntl_strerror( $error );
				if ( ! empty( $error_msg ) ) {
					$error = '(' . $error . ') ' . $error_msg;
				}
			}
			if ( ! empty( $error ) ) {
				$this->log(
					sprintf(
					/* translators: %s: system error. */
					__( 'System: %s', 'backwpup' ),
					$error
				),
					E_USER_ERROR
					);
			}
		}

		if ( function_exists( 'posix_get_last_error' ) && ! $error ) {
			$error = posix_get_last_error();
			if ( ! empty( $error ) ) {
				$error_msg = posix_strerror( $error );
				if ( ! empty( $error_msg ) ) {
					$error = '(' . $error . ') ' . $error_msg;
				}
			}
			if ( ! empty( $error ) ) {
				$this->log(
					sprintf(
					/* translators: %s: system error. */
					__( 'System: %s', 'backwpup' ),
					$error
				),
					E_USER_ERROR
					);
			}
		}

		$this->do_restart( true );
	}

	/**
	 * The uncouth exception handler.
	 *
	 * @param Throwable $exception The exception instance.
	 */
	public function exception_handler( $exception ) {
		$this->log(
			sprintf(
				/* translators: 1: exception class, 2: exception message. */
				__( 'Exception caught in %1$s: %2$s', 'backwpup' ),
				get_class( $exception ),
				$exception->getMessage()
			),
			E_USER_ERROR,
			$exception->getFile(),
			$exception->getLine()
		);
	}

	/**
	 * Callback for CURLOPT_READFUNCTION that submits the transferred bytes.
	 *
	 * @param resource $curl_handle Curl handle.
	 * @param resource $file_handle File handle.
	 * @param int      $read_count Bytes to read.
	 *
	 * @return string
	 *
	 * @internal param $out
	 */
	public function curl_read_callback( $curl_handle, $file_handle, $read_count ) {
		$data = null;
		if ( ! empty( $file_handle ) && is_numeric( $read_count ) ) {
			$data = fread( $file_handle, $read_count ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
		}

		if ( 'sync' === $this->job['backuptype'] ) {
			return $data;
		}

		$length              = ( is_numeric( $read_count ) ) ? $read_count : strlen( (string) $read_count );
		$this->substeps_done = $this->substeps_done + $length;
		$this->update_working_data();

		return $data;
	}

	/**
	 * Store and retrieve data from an extra temp file.
	 *
	 * @param string|null $storage The name of the storage.
	 * @param mixed       $data    Data to save in storage.
	 *
	 * @return array|mixed|null Data from storage.
	 */
	public function data_storage( $storage = null, $data = null ) {
		if ( empty( $storage ) ) {
			return $data;
		}

		$storage = strtolower( $storage );

		$file       = BackWPup::get_plugin_data( 'temp' ) . 'backwpup-' . BackWPup::get_plugin_data( 'hash' ) . '-' . $storage . '.json';
		$filesystem = backwpup_wpfilesystem();

		if ( ! empty( $data ) ) {
			$filesystem->put_contents( $file, wp_json_encode( $data ) );
		} elseif ( $filesystem->exists( $file ) ) {
			$json = $filesystem->get_contents( $file );
			$data = false === $json ? null : json_decode( $json, true );
		}

		return $data;
	}

	/**
	 * Add folders to the folder list that should be backed up.
	 *
	 * @param array|string $folders   Folders to add.
	 * @param bool         $overwrite Whether to overwrite the existing file.
	 */
	public function add_folders_to_backup( $folders = [], $overwrite = false ) {
		if ( ! is_array( $folders ) ) {
			$folders = (array) $folders;
		}

		$file       = BackWPup::get_plugin_data( 'temp' ) . 'backwpup-' . BackWPup::get_plugin_data( 'hash' ) . '-folder.php';
		$filesystem = backwpup_wpfilesystem();

		if ( ! $filesystem->exists( $file ) || $overwrite ) {
			$filesystem->put_contents( $file, '<?php' . PHP_EOL );
		}

		$content = '';

		foreach ( $folders as $folder ) {
			$content .= '//' . $folder . PHP_EOL;
		}

		if ( '' !== $content ) {
			$existing_content = $filesystem->get_contents( $file );
			$existing_content = false === $existing_content ? '' : $existing_content;
			$filesystem->put_contents( $file, $existing_content . $content );
		}
	}

	/**
	 * Check if file is a dbdump.
	 *
	 * @param string $file File path.
	 *
	 * @return bool
	 */
	private function maybe_sql_dump( $file ) {
		$file_name = BackWPup_Option::get( $this->job['jobid'], 'dbdumpfile' );

		$dump_files = [
			$file_name . '.sql',
			$file_name . '.sql.gz',
			$file_name . '.xml',
		];

		return in_array( basename( (string) $file ), $dump_files, true );
	}

	/**
	 * Check if a job is enabled.
	 *
	 * This function checks if a job with the given ID is enabled by verifying if its 'activetype' option is set to 'wpcron'.
	 *
	 * @param int $job_id The ID of the job to check.
	 * @return bool True if the job is enabled, false otherwise.
	 */
	public static function is_job_enabled( $job_id ): bool {
		return 'wpcron' === BackWPup_Option::get( $job_id, 'activetype' );
	}

	/**
	 * Enables a BackWPup job by updating its activation type to 'wpcron'.
	 *
	 * @param int $job_id The ID of the job to enable.
	 */
	public static function enable_job( $job_id ): void {
		BackWPup_Option::update( $job_id, 'activetype', 'wpcron' );
	}

	/**
	 * Disables a BackWPup job.
	 *
	 * This function updates the job's 'activetype' option to an empty string,
	 * effectively disabling the job. It also clears any scheduled cron hooks
	 * associated with the job.
	 *
	 * @param int $job_id The ID of the job to disable.
	 */
	public static function disable_job( $job_id ): void {
		BackWPup_Option::update( $job_id, 'activetype', '' );
		wp_clear_scheduled_hook( 'backwpup_cron', [ 'arg' => $job_id ] );
	}

	/**
	 * Schedules a single job event for the given job ID.
	 *
	 * This function schedules a single cron event for the specified job ID using the WordPress
	 * scheduling system. The event will be triggered at the next scheduled time for the job.
	 *
	 * @param int $job_id The ID of the job to schedule.
	 * @return int|false The Unix timestamp of the next scheduled event, or false if an error occurred.
	 */
	public static function schedule_job( $job_id ) {
		wp_clear_scheduled_hook( 'backwpup_cron', [ 'arg' => $job_id ] );
		$cron_next = BackWPup_Cron::cron_next( BackWPup_Option::get( $job_id, 'cron' ) );
		wp_schedule_single_event( $cron_next, 'backwpup_cron', [ 'arg' => $job_id ] );
		return $cron_next;
	}

	/**
	 * Renames a BackWPup job.
	 *
	 * This function updates the name of a BackWPup job with the given job ID.
	 *
	 * @param int    $job_id   The ID of the job to rename.
	 * @param string $new_name The new name for the job.
	 */
	public static function rename_job( $job_id, $new_name ): void {
		BackWPup_Option::update( $job_id, 'name', $new_name );
	}

	/**
	 * Duplicates a job based on the given job ID.
	 *
	 * @param int $old_job_id The ID of the job to duplicate.
	 * @return int|WP_Error The ID of the new duplicated job on success, or a WP_Error object on failure.
	 */
	public static function duplicate_job( $old_job_id ) {
		$newjobid = BackWPup_Option::get_job_ids();
		sort( $newjobid );
		$newjobid    = end( $newjobid ) + 1;
		$old_options = BackWPup_Option::get_job( $old_job_id );

		foreach ( $old_options as $key => $option ) {
			// Skip keys that should not be updated.
			if ( in_array( $key, [ 'logfile', 'lastbackupdownloadurl', 'lastruntime', 'lastrun' ], true ) ) {
				continue;
			}

			// Update option values based on key.
			switch ( $key ) {
				case 'jobid':
					$option = $newjobid;
					break;

				case 'name':
					$option = __( 'Copy of', 'backwpup' ) . ' ' . $option;
					break;

				case 'activetype':
					$option = '';
					break;

				case 'archivename':
					$option = str_replace( $old_job_id, $newjobid, (string) $option );
					break;
			}

			// Save the updated option.
			BackWPup_Option::update( $newjobid, $key, $option );
		}
		return $newjobid;
	}

	/**
	 * Init and instantiate job instance.
	 *
	 * @param array $args Job arguments.
	 * @return self
	 */
	public static function init( $args = [] ) {
		$instance = new self();
		foreach ( $args as $key => $value ) {
			if ( ! isset( $instance->$key ) ) {
				continue;
			}
			$instance->$key = $value;
		}
		return $instance;
	}


	/**
	 * Get the list of jobs from the backwpup_jobs site option.
	 *
	 * @return array The list of jobs.
	 */
	public static function get_jobs() {
		return BackWPup_Option::jobs_options();
	}

	/**
	 * Deletes a BackWPup job.
	 *
	 * This function removes the job with the given job ID from the options and clears any scheduled cron hooks
	 * associated with the job.
	 *
	 * @param int $job_id The ID of the job to delete.
	 */
	public static function delete_job( $job_id ): bool {
		if ( BackWPup_Option::delete_job( $job_id ) ) {
			// Clear any scheduled cron hooks for the job.
			wp_clear_scheduled_hook( 'backwpup_cron', [ 'arg' => $job_id ] );
			return true;
		}
		return false;
	}
}
