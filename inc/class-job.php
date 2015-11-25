<?php
/**
 * Class in that the BackWPup job runs
 */
final class BackWPup_Job {

	/**
	 * @var array of the job settings
	 */
	public $job = array();

	/**
	 * @var int The timestamp when the job starts
	 */
	public $start_time = 0;

	/**
	 * @var string the logfile
	 */
	public $logfile = '';
	/**
	 * @var array for temp values
	 */
	public $temp = array();
	/**
	 * @var string Folder where is Backup files in
	 */
	public $backup_folder = '';
	/**
	 * @var string the name of the Backup archive file
	 */
	public $backup_file = '';
	/**
	 * @var int The size of the Backup archive file
	 */
	public $backup_filesize = 0;
	/**
	 * @var int PID of script
	 */
	public $pid = 0;
	/**
	 * @var float Timestamp of last update off .running file
	 */
	public $timestamp_last_update = 0;
	/**
	 * @var float Timestamp of script start
	 */
	private $timestamp_script_start = 0;
	/**
	 * @var int Number of warnings
	 */
	public $warnings = 0;
	/**
	 * @var int Number of errors
	 */
	public $errors = 0;
	/**
	 * @var string the last log notice message
	 */
	public $lastmsg = '';
	/**
	 * @var string the last log error/waring message
	 */
	public $lasterrormsg = '';
	/**
	 * @var array of steps to do
	 */
	public $steps_todo = array( 'CREATE' );
	/**
	 * @var array of done steps
	 */
	public $steps_done = array();
	/**
	 * @var array  of steps data
	 */
	public $steps_data = array();
	/**
	 * @var string working on step
	 */
	public $step_working = 'CREATE';
	/**
	 * @var int Number of sub steps must do in step
	 */
	public $substeps_todo = 0;
	/**
	 * @var int Number of sub steps done in step
	 */
	public $substeps_done = 0;
	/**
	 * @var int Percent of steps done
	 */
	public $step_percent = 1;
	/**
	 * @var int Percent of sub steps done
	 */
	public $substep_percent = 1;
	/**
	 * @var array of files to additional to backup
	 */
	public $additional_files_to_backup = array();
	/**
	 * @var array of files/folder to exclude from backup
	 */
	public $exclude_from_backup = array();
	/**
	 * @var int count of affected files
	 */
	public $count_files = 0;
	/**
	 * @var int count of affected file sizes
	 */
	public $count_files_size = 0;
	/**
	 * @var int count of affected folders
	 */
	public $count_folder = 0;

	/**
	 * If job aborted from user
	 * @var bool
	 */
	public $user_abort = FALSE;

	/**
	 * Stores data that will only used in a single run
	 * @var array
	 */
	private $run = array();

	/**
	 * A uniqid ID uniqid('', true); to identify process
	 * @var string
	 */
	public $uniqid = '';

	/**
	 * @var string logging level (normal|normal_untranslated|debug|debug_untranslated)
	 */
	private $log_level = 'normal';


	/**
	 * Delete some data on cloned objects
	 */
	public function __clone( ) {

		$this->temp = array();
		$this->run = array();
	}

	/**
	 *
	 * This starts or restarts the job working
	 *
	 * @param string $start_type Start types are 'runnow', 'runnowalt', 'cronrun', 'runext', 'runcli'
	 * @param array|int $job_settings The id of job or the settings of a job to start
	 */
	private function create( $start_type, $job_settings = 0 ) {
		global $wpdb;
		/* @var wpdb $wpdb */

		//check startype
		if ( ! in_array( $start_type, array( 'runnow', 'runnowalt', 'cronrun', 'runext', 'runcli' ) ) ) {
			return;
		}

		if ( is_int( $job_settings ) ) {
			$this->job = BackWPup_Option::get_job( $job_settings );
		} elseif( is_array( $job_settings ) ) {
				$this->job = $job_settings;
		} else {
			return;
		}

		$this->start_time  =  current_time( 'timestamp' );
		$this->lastmsg = __( 'Starting job', 'backwpup' );
		//set Logfile
		$log_folder = get_site_option( 'backwpup_cfg_logfolder' );
		$log_folder = BackWPup_File::get_absolute_path( $log_folder );
		$this->logfile = $log_folder . 'backwpup_log_' . BackWPup::get_plugin_data( 'hash' ) . '_' . date( 'Y-m-d_H-i-s', current_time( 'timestamp' ) ) . '.html';
		//write settings to job
		if ( ! empty( $this->job[ 'jobid' ] ) ) {
			BackWPup_Option::update( $this->job[ 'jobid' ], 'lastrun', $this->start_time );
			BackWPup_Option::update( $this->job[ 'jobid' ], 'logfile', $this->logfile ); //Set current logfile
			BackWPup_Option::update( $this->job[ 'jobid' ], 'lastbackupdownloadurl', '' );
		}
		//Set needed job values
		$this->timestamp_last_update = microtime( TRUE );
		$this->exclude_from_backup 	= explode( ',', trim( $this->job[ 'fileexclude' ] ) );
		$this->exclude_from_backup 	= array_unique( $this->exclude_from_backup );
		//setup job steps
		$this->steps_data[ 'CREATE' ][ 'CALLBACK' ] = '';
		$this->steps_data[ 'CREATE' ][ 'NAME' ]     = __( 'Job Start', 'backwpup' );
		$this->steps_data[ 'CREATE' ][ 'STEP_TRY' ] = 0;
		//ADD Job types file
		/* @var $job_type_class BackWPup_JobTypes */
		$job_need_dest = FALSE;
		if ( $job_types = BackWPup::get_job_types() ) {
			foreach ( $job_types as $id => $job_type_class ) {
				if ( in_array( $id, $this->job[ 'type' ] ) && $job_type_class->creates_file( ) ) {
					$this->steps_todo[ ]                            = 'JOB_' . $id;
					$this->steps_data[ 'JOB_' . $id ][ 'NAME' ]     = $job_type_class->info[ 'description' ];
					$this->steps_data[ 'JOB_' . $id ][ 'STEP_TRY' ] = 0;
					$this->steps_data[ 'JOB_' . $id ][ 'SAVE_STEP_TRY' ] = 0;
					$job_need_dest                                  = TRUE;
				}
			}
		}
		//add destinations and create archive if a job where files to backup
		if ( $job_need_dest ) {
			//Create manifest file
			$this->steps_todo[ ]                                	  = 'CREATE_MANIFEST';
			$this->steps_data[ 'CREATE_MANIFEST' ][ 'NAME' ]     	  = __( 'Creates manifest file', 'backwpup' );
			$this->steps_data[ 'CREATE_MANIFEST' ][ 'STEP_TRY' ] 	  = 0;
			$this->steps_data[ 'CREATE_MANIFEST' ][ 'SAVE_STEP_TRY' ] = 0;
			//Add archive creation and backup filename on backup type archive
			if ( $this->job[ 'backuptype' ] == 'archive' ) {
				//get Backup folder if destination folder set
				if ( in_array( 'FOLDER', $this->job[ 'destinations' ] ) ) {
					$this->backup_folder = $this->job[ 'backupdir' ];
					//check backup folder
					if ( ! empty( $this->backup_folder ) ) {
						$this->backup_folder = BackWPup_File::get_absolute_path( $this->backup_folder );
						$this->job[ 'backupdir' ] = $this->backup_folder;
					}
				}
				//set temp folder to backup folder if not set because we need one
				if ( ! $this->backup_folder || $this->backup_folder == '/' ) {
					$this->backup_folder = BackWPup::get_plugin_data( 'TEMP' );
				}
				//Create backup archive full file name
				$this->backup_file = $this->generate_filename( $this->job[ 'archivename' ], $this->job[ 'archiveformat' ] );
				//add archive create
				$this->steps_todo[ ]                                = 'CREATE_ARCHIVE';
				$this->steps_data[ 'CREATE_ARCHIVE' ][ 'NAME' ]     = __( 'Creates archive', 'backwpup' );
				$this->steps_data[ 'CREATE_ARCHIVE' ][ 'STEP_TRY' ] = 0;
				$this->steps_data[ 'CREATE_ARCHIVE' ][ 'SAVE_STEP_TRY' ] = 0;
			}
			//ADD Destinations
			/* @var BackWPup_Destinations $dest_class */
			foreach ( BackWPup::get_registered_destinations() as $id => $dest ) {
				if ( ! in_array( $id, $this->job[ 'destinations' ] ) || empty( $dest[ 'class' ] ) )
					continue;
				$dest_class = BackWPup::get_destination( $id );
				if ( $dest_class->can_run( $this->job ) ) {
					if ( $this->job[ 'backuptype' ] == 'sync' ) {
						if ( $dest[ 'can_sync' ] ) {
							$this->steps_todo[]                                   = 'DEST_SYNC_' . $id;
							$this->steps_data[ 'DEST_SYNC_' . $id ][ 'NAME' ]     = $dest[ 'info' ][ 'description' ];
							$this->steps_data[ 'DEST_SYNC_' . $id ][ 'STEP_TRY' ] = 0;
							$this->steps_data[ 'DEST_SYNC_' . $id ][ 'SAVE_STEP_TRY' ] = 0;
						}
					} else {
						$this->steps_todo[]                              = 'DEST_' . $id;
						$this->steps_data[ 'DEST_' . $id ][ 'NAME' ]     = $dest[ 'info' ][ 'description' ];
						$this->steps_data[ 'DEST_' . $id ][ 'STEP_TRY' ] = 0;
						$this->steps_data[ 'DEST_' . $id ][ 'SAVE_STEP_TRY' ] = 0;
					}
				}
			}
		}
		//ADD Job type no file
		if ( $job_types = BackWPup::get_job_types() ) {
			foreach ( $job_types as $id => $job_type_class ) {
				if ( in_array( $id, $this->job[ 'type' ] ) && ! $job_type_class->creates_file() ) {
					$this->steps_todo[ ]                            = 'JOB_' . $id;
					$this->steps_data[ 'JOB_' . $id ][ 'NAME' ]     = $job_type_class->info[ 'description' ];
					$this->steps_data[ 'JOB_' . $id ][ 'STEP_TRY' ] = 0;
					$this->steps_data[ 'JOB_' . $id ][ 'SAVE_STEP_TRY' ] = 0;
				}
			}
		}
		$this->steps_todo[]                      = 'END';
		$this->steps_data[ 'END' ][ 'NAME' ]     = __( 'End of Job', 'backwpup' );
		$this->steps_data[ 'END' ][ 'STEP_TRY' ] = 1;
		//must write working data
		$this->write_running_file();

		//load text domain if needed
		$this->log_level = get_site_option( 'backwpup_cfg_loglevel' );
		if ( ! in_array( $this->log_level, array( 'normal_translated', 'normal', 'debug_translated', 'debug' ) ) ) {
			$this->log_level = 'normal_translated';
		}
		//create log file
		$head = '';
		$info = '';
		$head .= "<!DOCTYPE html>" . PHP_EOL;
		$head .= "<html lang=\"" . str_replace( '_', '-', get_locale() ) . "\">" . PHP_EOL;
		$head .= "<head>" . PHP_EOL;
		$head .= "<meta charset=\"" . get_bloginfo( 'charset' ) . "\" />" . PHP_EOL;
		$head .= "<title>" . sprintf( __( 'BackWPup log for %1$s from %2$s at %3$s', 'backwpup' ), $this->job[ 'name' ], date_i18n( get_option( 'date_format' ) ), date_i18n( get_option( 'time_format' ) ) ) . "</title>" . PHP_EOL;
		$head .= "<meta name=\"robots\" content=\"noindex, nofollow\" />" . PHP_EOL;
		$head .= "<meta name=\"copyright\" content=\"Copyright &copy; 2012 - " . date( 'Y' ) . " Inpsyde GmbH\" />" . PHP_EOL;
		$head .= "<meta name=\"author\" content=\"Inpsyde GmbH\" />" . PHP_EOL;
		$head .= "<meta name=\"generator\" content=\"BackWPup " . BackWPup::get_plugin_data( 'Version' ) . "\" />" . PHP_EOL;
		$head .= "<meta http-equiv=\"cache-control\" content=\"no-cache\" />" . PHP_EOL;
		$head .= "<meta http-equiv=\"pragma\" content=\"no-cache\" />" . PHP_EOL;
		$head .= "<meta name=\"date\" content=\"" . date( 'c' ) . "\" />" . PHP_EOL;
		$head .= str_pad( '<meta name="backwpup_errors" content="0" />', 100 ) . PHP_EOL;
		$head .= str_pad( '<meta name="backwpup_warnings" content="0" />', 100 ) . PHP_EOL;
		if ( ! empty( $this->job[ 'jobid' ] ) ) {
			$head .= "<meta name=\"backwpup_jobid\" content=\"" . $this->job[ 'jobid' ] . "\" />" . PHP_EOL;
		}
		$head .= "<meta name=\"backwpup_jobname\" content=\"" . esc_attr( $this->job[ 'name' ] ) . "\" />" . PHP_EOL;
		$head .= "<meta name=\"backwpup_jobtype\" content=\"" . implode( '+', $this->job[ 'type' ] ) . "\" />" . PHP_EOL;
		$head .= str_pad( '<meta name="backwpup_backupfilesize" content="0" />', 100 ) . PHP_EOL;
		$head .= str_pad( '<meta name="backwpup_jobruntime" content="0" />', 100 ) . PHP_EOL;
		$head .= "</head>" . PHP_EOL;
		$head .= "<body style=\"margin:0;padding:3px;font-family:monospace;font-size:12px;line-height:15px;background-color:#000;color:#fff;white-space:nowrap;\">" . PHP_EOL;
		$info .= sprintf( _x( '[INFO] %1$s %2$s; A project of Inpsyde GmbH', 'Plugin name; Plugin Version; plugin url','backwpup' ), BackWPup::get_plugin_data( 'name' ), BackWPup::get_plugin_data( 'Version' ), BackWPup::get_plugin_data( 'pluginuri' ) ) . '<br />' . PHP_EOL;
		if ( $this->is_debug() ) {
			$info .= sprintf( _x( '[INFO] WordPress %1$s on %2$s', 'WordPress Version; Blog url', 'backwpup' ), BackWPup::get_plugin_data( 'wp_version' ), esc_attr( site_url( '/' ) ) ). '<br />' . PHP_EOL;
		}
		$job_name = esc_attr( $this->job[ 'name' ] );
		if ( $this->is_debug() ) {
			$job_name .= '; ' . implode( '+', $this->job[ 'type' ] );
		}
		$info .= sprintf( __( '[INFO] BackWPup job: %1$s', 'backwpup' ), $job_name ) . '<br />' . PHP_EOL;
		if ( $this->is_debug() ) {
			$current_user = wp_get_current_user();
			$info .= sprintf( __( '[INFO] Runs with user: %1$s (%2$d) ', 'backwpup' ), $current_user->user_login, $current_user->ID ) . '<br />' . PHP_EOL;
		}
		if ( $this->job[ 'activetype' ] == 'wpcron' ) {
			//check next run
			$cron_next = wp_next_scheduled( 'backwpup_cron', array( 'id' => $this->job[ 'jobid' ] ) );
			if ( ! $cron_next || $cron_next < time() ) {
				wp_unschedule_event( $cron_next, 'backwpup_cron', array( 'id' => $this->job[ 'jobid' ] ) );
				$cron_next = BackWPup_Cron::cron_next( $this->job[ 'cron' ] );
				wp_schedule_single_event( $cron_next, 'backwpup_cron', array( 'id' => $this->job[ 'jobid' ] ) );
				$cron_next = wp_next_scheduled( 'backwpup_cron', array( 'id' => $this->job[ 'jobid' ] ) );
			}
			//output scheduling
			if ( $this->is_debug() ) {
				if ( ! $cron_next ) {
					$cron_next = __( 'Not scheduled!', 'backwpup' );
				} else {
					$cron_next = date_i18n( 'D, j M Y @ H:i', $cron_next + ( get_option( 'gmt_offset' ) * 3600 ), TRUE );
				}
				$info .= sprintf( __( '[INFO] Cron: %s; Next: %s ', 'backwpup' ), $this->job[ 'cron' ] , $cron_next ) . '<br />' . PHP_EOL;
			}
		}
		elseif( $this->job[ 'activetype' ] == 'link' && $this->is_debug() ) {
			$info .= __( '[INFO] BackWPup job start with link is active', 'backwpup' ) . '<br />' . PHP_EOL;
		}
		elseif( $this->job[ 'activetype' ] == 'easycron' && $this->is_debug() ) {
			$info .= __( '[INFO] BackWPup job start with EasyCron.com', 'backwpup' ) . '<br />' . PHP_EOL;
			//output scheduling
			if ( $this->is_debug() ) {
				$cron_next = BackWPup_Cron::cron_next( $this->job[ 'cron' ] );
				$cron_next = date_i18n( 'D, j M Y @ H:i', $cron_next + ( get_option( 'gmt_offset' ) * 3600 ), TRUE );
				$info .= sprintf( __( '[INFO] Cron: %s; Next: %s ', 'backwpup' ), $this->job[ 'cron' ] , $cron_next ) . '<br />' . PHP_EOL;
			}
		}
		elseif( $this->is_debug() ) {
			$info .= __( '[INFO] BackWPup no automatic job start configured', 'backwpup' ) . '<br />' . PHP_EOL;
		}
		if ( $this->is_debug() ) {
			if ( $start_type == 'cronrun' ) {
				$info .= __( '[INFO] BackWPup job started from wp-cron', 'backwpup' ) . '<br />' . PHP_EOL;
			} elseif ( $start_type == 'runnow' || $start_type == 'runnowalt' ) {
				$info .= __( '[INFO] BackWPup job started manually', 'backwpup' ) . '<br />' . PHP_EOL;
			} elseif ( $start_type == 'runext' ) {
				$info .= __( '[INFO] BackWPup job started from external url', 'backwpup' ) . '<br />' . PHP_EOL;
			} elseif ( $start_type == 'runcli' ) {
				$info .= __( '[INFO] BackWPup job started form commandline interface', 'backwpup' ) . '<br />' . PHP_EOL;
			}
			$bit = '';
			if ( PHP_INT_SIZE === 4 ) {
				$bit = ' (32bit)';
			}
			if ( PHP_INT_SIZE === 8 ) {
				$bit = ' (64bit)';
			}
			$info .= __( '[INFO] PHP ver.:', 'backwpup' ) . ' ' . PHP_VERSION . $bit .'; ' . PHP_SAPI . '; ' . PHP_OS . '<br />' . PHP_EOL;
			$info .= sprintf( __( '[INFO] Maximum PHP script execution time is %1$d seconds', 'backwpup' ), ini_get( 'max_execution_time' ) ) . '<br />' . PHP_EOL;
			if ( php_sapi_name() != 'cli' ) {
				$job_max_execution_time = get_site_option( 'backwpup_cfg_jobmaxexecutiontime' );
				if ( ! empty( $job_max_execution_time ) ) {
					$info .= sprintf( __( '[INFO] Script restart time is configured to %1$d seconds', 'backwpup' ), $job_max_execution_time ) . '<br />' . PHP_EOL;
				}
			}
			$info .= sprintf( __( '[INFO] MySQL ver.: %s', 'backwpup' ), $wpdb->get_var( "SELECT VERSION() AS version" ) ) . '<br />' . PHP_EOL;
			if ( isset( $_SERVER[ 'SERVER_SOFTWARE' ] ) )
				$info .= sprintf( __( '[INFO] Web Server: %s', 'backwpup' ), $_SERVER[ 'SERVER_SOFTWARE' ] ) . '<br />' . PHP_EOL;
			if ( function_exists( 'curl_init' ) ) {
				$curlversion = curl_version();
				$info .= sprintf( __( '[INFO] curl ver.: %1$s; %2$s', 'backwpup' ), $curlversion[ 'version' ], $curlversion[ 'ssl_version' ] ) . '<br />' . PHP_EOL;
			}
			$info .= sprintf( __( '[INFO] Temp folder is: %s', 'backwpup' ), BackWPup::get_plugin_data( 'TEMP' ) ) . '<br />' . PHP_EOL;
		}
		if ( $this->is_debug() ) {
			$logfile = $this->logfile;
		} else {
			$logfile = basename( $this->logfile );
		}
		$info .= sprintf( __( '[INFO] Logfile is: %s', 'backwpup' ), $logfile ) . '<br />' . PHP_EOL;
		if ( ! empty( $this->backup_file ) && $this->job[ 'backuptype' ] == 'archive' ) {
			if ( $this->is_debug() ) {
				$backupfile = $this->backup_folder . $this->backup_file;
			} else {
				$backupfile = $this->backup_file;
			}
			$info .= sprintf( __( '[INFO] Backup file is: %s', 'backwpup' ), $backupfile ) . '<br />' . PHP_EOL;
		} else {
			$info .= sprintf( __( '[INFO] Backup type is: %s', 'backwpup' ), $this->job[ 'backuptype' ] ) . '<br />' . PHP_EOL;
		}
		//output info on cli
		if ( php_sapi_name() == 'cli' && defined( 'STDOUT' ) ) {
			fwrite( STDOUT, strip_tags( $info ) ) ;
		}
		if ( ! file_put_contents( $this->logfile, $head . $info, FILE_APPEND ) ) {
			$this->logfile = '';
			$this->log( __( 'Could not write log file', 'backwpup' ), E_USER_ERROR );
		}
		//test for destinations
		if ( $job_need_dest ) {
			$desttest = FALSE;
			foreach ( $this->steps_todo as $deststeptest ) {
				if ( substr( $deststeptest, 0, 5 ) == 'DEST_' ) {
					$desttest = TRUE;
					break;
				}
			}
			if ( ! $desttest ) {
				$this->log( __( 'No destination correctly defined for backup! Please correct job settings.', 'backwpup' ), E_USER_ERROR );
				$this->steps_todo = array( 'END' );
			}
		}
		//test backup folder
		if ( ! empty( $this->backup_folder ) ) {
			$folder_message = BackWPup_File::check_folder( $this->backup_folder, TRUE );
			if ( ! empty( $folder_message ) ) {
				$this->log( $folder_message, E_USER_ERROR );
				$this->steps_todo = array( 'END' );
			}
		}

		//Set start as done
		$this->steps_done[] = 'CREATE';
	}


	/**
	 *
	 * Get a url to run a job of BackWPup
	 *
	 * @param string     $starttype Start types are 'runnow', 'runnowlink', 'cronrun', 'runext', 'restart', 'restartalt', 'test'
	 * @param int        $jobid     The id of job to start else 0
	 * @return array|object [url] is the job url [header] for auth header or object form wp_remote_get()
	 */
	public static function get_jobrun_url( $starttype, $jobid = 0 ) {

		$authentication     = get_site_option( 'backwpup_cfg_authentication', array( 'method' => '', 'basic_user' => '', 'basic_password' => '', 'user_id' => 0, 'query_arg' => '' ) );
		$url        		= site_url( 'wp-cron.php' );
		$header				= array();
		$authurl    		= '';
		$query_args 		= array( '_nonce' => substr( wp_hash( wp_nonce_tick() . 'backwpup_job_run-' . $starttype, 'nonce' ), - 12, 10 ), 'doing_wp_cron' => sprintf( '%.22F', microtime( true ) ) );

		if ( in_array( $starttype, array( 'restart', 'runnow', 'cronrun', 'runext', 'test' ) ) ) {
			$query_args[ 'backwpup_run' ] = $starttype;
		}

		if ( in_array( $starttype, array( 'runnowlink', 'runnow', 'cronrun', 'runext' ) ) && ! empty( $jobid ) ) {
			$query_args[ 'jobid' ] = $jobid;
		}

		if ( ! empty( $authentication[ 'basic_user' ] ) && ! empty( $authentication[ 'basic_password' ] ) && $authentication[ 'method' ] == 'basic' ) {
			$header[ 'Authorization' ] = 'Basic ' . base64_encode( $authentication[ 'basic_user' ] . ':' . BackWPup_Encryption::decrypt( $authentication[ 'basic_password' ] ) );
			$authurl = urlencode( $authentication[ 'basic_user' ] ) . ':' . urlencode( BackWPup_Encryption::decrypt( $authentication[ 'basic_password' ] ) ) . '@';
		}

		if ( ! empty( $authentication[ 'query_arg' ] ) && $authentication[ 'method' ] == 'query_arg' ) {
			$url .= '?' . $authentication[ 'query_arg' ];
		}

		if ( $starttype == 'runext' ) {
			$query_args[ '_nonce' ] = get_site_option( 'backwpup_cfg_jobrunauthkey' );
			$query_args[ 'doing_wp_cron' ] = NULL;
			if ( ! empty( $authurl ) ) {
				$url = str_replace( 'https://', 'https://' . $authurl, $url );
				$url = str_replace( 'http://', 'http://' . $authurl, $url );
			}
		}

		if ( $starttype == 'runnowlink' && ( ! defined( 'ALTERNATE_WP_CRON' ) || ! ALTERNATE_WP_CRON ) ) {
			$url                       		= wp_nonce_url( network_admin_url( 'admin.php' ), 'backwpup_job_run-' . $starttype );
			$query_args[ 'page' ]      		= 'backwpupjobs';
			$query_args[ 'action' ] 		= 'runnow';
			$query_args[ 'doing_wp_cron' ]  = NULL;
			unset( $query_args[ '_nonce' ] );
		}

		if ( $starttype == 'runnowlink' && defined( 'ALTERNATE_WP_CRON' ) && ALTERNATE_WP_CRON ) {
			$query_args[ 'backwpup_run' ] = 'runnowalt';
			$query_args[ '_nonce' ]    = substr( wp_hash( wp_nonce_tick() . 'backwpup_job_run-runnowalt', 'nonce' ), - 12, 10 );
			$query_args[ 'doing_wp_cron' ] = NULL;
		}

		if ( $starttype == 'restartalt' && defined( 'ALTERNATE_WP_CRON' ) && ALTERNATE_WP_CRON ) {
			$query_args[ 'backwpup_run' ] = 'restart';
			$query_args[ '_nonce' ]    = substr( wp_hash( wp_nonce_tick() . 'backwpup_job_run-restart', 'nonce' ), - 12, 10 );
		}

		if ( ! empty( $authentication[ 'user_id' ] ) && $authentication[ 'method' ] == 'user'  ) {
			//cache cookies for auth some
			$cookies = get_site_transient( 'backwpup_cookies' );
			if ( empty( $cookies ) ) {
				$wp_admin_user = get_users( array( 'role' => 'administrator', 'number' => 1 ) );
				if ( empty( $wp_admin_user ) ) {
					$wp_admin_user 	= get_users( array( 'role' => 'backwpup_admin', 'number' => 1 ) );
				}
				if ( ! empty( $wp_admin_user[ 0 ]->ID ) ) {
					$expiration = time() + ( 356 * DAY_IN_SECONDS );
					$manager = WP_Session_Tokens::get_instance( $wp_admin_user[ 0 ]->ID );
					$token   = $manager->create( $expiration );
					$cookies[ LOGGED_IN_COOKIE ] =  wp_generate_auth_cookie( $wp_admin_user[ 0 ]->ID, $expiration, 'logged_in', $token );
				}
				set_site_transient( 'backwpup_cookies', $cookies, 3600 - 30 );
			}
		} else {
			$cookies = '';
		}

		$cron_request = array(
			'url' => add_query_arg( $query_args, $url ),
			'key' => $query_args[ 'doing_wp_cron' ],
			'args' => array(
				'blocking'   	=> FALSE,
				'sslverify'		=> apply_filters( 'https_local_ssl_verify', true ),
				'timeout' 		=> 0.01,
				'headers'    	=> $header,
			    'user-agent' 	=> BackWpup::get_plugin_data( 'User-Agent' )
			)
		);

		if ( ! empty( $cookies ) ) {
			foreach ( $cookies as $name => $value ) {
				$cron_request[ 'args' ][ 'cookies' ][] = new WP_Http_Cookie( array( 'name' => $name, 'value' => $value ) );
			}
		}

		$cron_request = apply_filters( 'cron_request', $cron_request );

		if ( $starttype == 'test' ) {
			$cron_request[ 'args' ][ 'timeout' ] = 15;
			$cron_request[ 'args' ][ 'blocking' ] = TRUE;
		}

		if ( ! in_array( $starttype, array( 'runnowlink', 'runext', 'restartalt' ) ) ) {
			delete_transient( 'doing_cron' );
			return wp_remote_post( $cron_request[ 'url' ], $cron_request[ 'args' ] );
		}

		return $cron_request;
	}


	/**
	 *
	 */
	public static function start_http( $starttype ) {

		//load text domain
		$log_level = get_site_option( 'backwpup_cfg_loglevel' );
		if ( strstr( $log_level, 'translated' ) ) {
			BackWPup::load_text_domain();
		}

		if ( $starttype != 'restart' ) {

			//check get vars
			if ( isset( $_GET[ 'jobid' ] ) )
				$jobid = (int)$_GET[ 'jobid' ];
			else
				$jobid = 0;

			//check job id exists
			if ( $jobid != BackWPup_Option::get( $jobid, 'jobid' ) ) {
				die( '-1' );
			}

			//check folders
			$log_folder = get_site_option( 'backwpup_cfg_logfolder' );
			$folder_message_log = BackWPup_File::check_folder( BackWPup_File::get_absolute_path( $log_folder ) );
			$folder_message_temp = BackWPup_File::check_folder( BackWPup::get_plugin_data( 'TEMP' ), TRUE );
			if ( ! empty( $folder_message_log ) || ! empty( $folder_message_temp ) ) {
				BackWPup_Admin::message( $folder_message_log, TRUE );
				BackWPup_Admin::message( $folder_message_temp, TRUE );
				die( '-2' );
			}
		}

		// redirect
		if ( $starttype == 'runnowalt' ) {
			ob_start();
			wp_redirect( add_query_arg( array( 'page' => 'backwpupjobs' ), network_admin_url( 'admin.php' ) ) );
			echo ' ';
			while ( @ob_end_flush() );
			flush();
		}

		// Should be preventing doubled running job's on http requests
		$random = rand( 1, 9 ) * 100000;
		usleep( $random );

		//check running job
		$backwpup_job_object = self::get_working_data();
		//start class
		if ( ! $backwpup_job_object && in_array( $starttype, array( 'runnow', 'runnowalt', 'runext' ) ) && ! empty( $jobid ) ) {
			//schedule restart event
			wp_schedule_single_event( time() + 60, 'backwpup_cron', array( 'id' => 'restart' ) );
			//start job
			$backwpup_job_object = new self();
			$backwpup_job_object->create( $starttype, (int)$jobid );
		}
		if( is_object( $backwpup_job_object ) && $backwpup_job_object instanceof BackWPup_Job )
			$backwpup_job_object->run();
	}

	/**
	 * @param $jobid
	 */
	public static function start_cli( $jobid ) {

		if ( php_sapi_name() != 'cli' ) {
			return;
		}

		//define DOING_CRON to prevent caching
		if( ! defined( 'DOING_CRON' ) ) {
			define( 'DOING_CRON', TRUE );
		}

		//load text domain
		$log_level = get_site_option( 'backwpup_cfg_loglevel' );
		if ( strstr( $log_level, 'translated' ) ) {
			BackWPup::load_text_domain();
		}

		//Logs Folder
		$log_folder = get_site_option( 'backwpup_cfg_logfolder' );
		$log_folder = BackWPup_File::get_absolute_path( $log_folder );

		//check job id exists
		$jobids = BackWPup_Option::get_job_ids();
		if ( ! in_array( $jobid, $jobids ) ) {
			die( __( 'Wrong BackWPup JobID', 'backwpup' ) );
		}
		//check folders
		$log_folder_message = BackWPup_File::check_folder( $log_folder );
		if ( ! empty( $log_folder_message ) ) {
			die( $log_folder_message );
		}
		$log_folder_message = BackWPup_File::check_folder( BackWPup::get_plugin_data( 'TEMP' ), TRUE );
		if ( ! empty( $log_folder_message ) ) {
			die( $log_folder_message );
		}
		//check running job
		if ( file_exists( BackWPup::get_plugin_data( 'running_file' ) ) ) {
			die( __( 'A BackWPup job is already running', 'backwpup' ) );
		}

		//start class
		$backwpup_job_object = new self();
		$backwpup_job_object->create( 'runcli', (int)$jobid );
		$backwpup_job_object->run();
	}

	/**
	 * @param int $jobid
	 */
	public static function start_wp_cron( $jobid = 0 ) {

		if ( ! defined( 'DOING_CRON' ) || ! DOING_CRON ) {
			return;
		}

		//load text domain
		$log_level = get_site_option( 'backwpup_cfg_loglevel' );
		if ( strstr( $log_level, 'translated' ) ) {
			BackWPup::load_text_domain();
		}

		if ( ! empty( $jobid ) ) {
			//check folders
			$log_folder = get_site_option( 'backwpup_cfg_logfolder' );
			$folder_message_log = BackWPup_File::check_folder( BackWPup_File::get_absolute_path( $log_folder ) );
			$folder_message_temp = BackWPup_File::check_folder( BackWPup::get_plugin_data( 'TEMP' ), TRUE );
			if ( ! empty( $folder_message_log ) || ! empty( $folder_message_temp ) ) {
				BackWPup_Admin::message( $folder_message_log, TRUE );
				BackWPup_Admin::message( $folder_message_temp, TRUE );
				return;
			}
		}

		// Should be preventing doubled running job's on http requests
		$random = rand( 1, 9 ) * 100000;
		usleep( $random );

		//get running job
		$backwpup_job_object = self::get_working_data();
		//start/restart class
		if ( empty( $backwpup_job_object ) && ! empty( $jobid ) ) {
			//schedule restart event
			wp_schedule_single_event( time() + 60, 'backwpup_cron', array( 'id' => 'restart' ) );
			//start job
			$backwpup_job_object = new self();
			$backwpup_job_object->create( 'cronrun', (int)$jobid );
		}
		if( is_object( $backwpup_job_object ) && $backwpup_job_object instanceof BackWPup_Job )
			$backwpup_job_object->run();
	}

	/**
	 * disable caches
	 */
	public static function disable_caches() {

		//Special settings
		@putenv( 'nokeepalive=1' );
		@ini_set( 'zlib.output_compression', 'Off' );

		// deactivate caches
		if ( ! defined( 'DONOTCACHEDB' ) ) {
			define( 'DONOTCACHEDB', TRUE );
		}
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', TRUE );
		}
	}


	/**
	 * Run baby run
	 */
	public function run() {
		global $wpdb;
		/* @var wpdb $wpdb */

		// Job can't run it is not created
		if ( empty( $this->steps_todo ) || empty( $this->logfile ) ) {
			$running_file = BackWPup::get_plugin_data( 'running_file' );
			if ( file_exists( $running_file ) ) {
				unlink( $running_file );
			}
			return;
		}

		//Check double running and inactivity
		$last_update = microtime( TRUE ) - $this->timestamp_last_update;
		if ( ! empty( $this->pid ) && $last_update > 300 ) {
			$this->log( __( 'Job restarts due to inactivity for more than 5 minutes.', 'backwpup' ), E_USER_WARNING );
		}
		elseif ( ! empty( $this->pid ) ) {
			return;
		}
		// set timestamp of script start
		$this->timestamp_script_start = microtime( TRUE );
		//set Pid
		$this->pid = self::get_pid();
		$this->uniqid = uniqid( '', TRUE );
		//Early write new working file
		$this->write_running_file();
		//set function for PHP user defined error handling
		$this->run[ 'PHP' ][ 'INI' ][ 'ERROR_LOG' ]      = ini_get( 'error_log' );
		$this->run[ 'PHP' ][ 'INI' ][ 'ERROR_REPORTING' ]= ini_get( 'error_reporting' );
		$this->run[ 'PHP' ][ 'INI' ][ 'LOG_ERRORS' ]     = ini_get( 'log_errors' );
		$this->run[ 'PHP' ][ 'INI' ][ 'DISPLAY_ERRORS' ] = ini_get( 'display_errors' );
		$this->run[ 'PHP' ][ 'INI' ][ 'HTML_ERRORS' ] 	 = ini_get( 'html_errors' );
		$this->run[ 'PHP' ][ 'INI' ][ 'REPORT_MEMLEAKS' ]= ini_get( 'report_memleaks' );
		$this->run[ 'PHP' ][ 'INI' ][ 'ZLIB_OUTPUT_COMPRESSION' ] = ini_get( 'zlib.output_compression' );
		$this->run[ 'PHP' ][ 'INI' ][ 'IMPLICIT_FLUSH' ] = ini_get( 'implicit_flush' );
		if ( $this->is_debug() ) {
			@ini_set( 'error_log', $this->logfile );
			error_reporting( -1 );
		}
		@ini_set( 'display_errors', '0' );
		@ini_set( 'log_errors', '1' );
		@ini_set( 'html_errors', '0' );
		@ini_set( 'report_memleaks', '1' );
		@ini_set( 'zlib.output_compression', '0' );
		@ini_set( 'implicit_flush', '0' );
		//increase MySQL timeout
		@ini_set( 'mysql.connect_timeout', '360' );
		//set temp folder
		$can_set_temp_env = TRUE;
		$protected_env_vars = explode( ',', ini_get( 'safe_mode_protected_env_vars' ) ); //removed in php 5.4.0
		foreach( $protected_env_vars as $protected_env ) {
			if ( strtoupper( trim( $protected_env ) ) == 'TMPDIR' ) {
				$can_set_temp_env = FALSE;
			}
		}
		if ( $can_set_temp_env ) {
			$this->run[ 'PHP' ][ 'ENV' ][ 'TEMPDIR' ] = getenv( 'TMPDIR' );
			@putenv( 'TMPDIR='.BackWPup::get_plugin_data( 'TEMP') );
		}
		//Write Wordpress DB errors to log
		$wpdb->suppress_errors( FALSE );
		$wpdb->hide_errors();
		//set wp max memory limit
		@ini_set( 'memory_limit', apply_filters( 'admin_memory_limit', WP_MAX_MEMORY_LIMIT ) );
		//set error handler
		if ( ! empty( $this->logfile ) ) {
			if ( $this->is_debug() ) {
				set_error_handler( array( $this, 'log' ) );
			} else {
				set_error_handler( array( $this, 'log' ), E_ALL ^ E_NOTICE );
			}
		}
		set_exception_handler( array( $this, 'exception_handler' ) );
		//not loading Textdomains and unload loaded
		if ( ! strstr( $this->log_level, 'translated' ) ) {
			add_filter( 'override_load_textdomain', create_function( '','return TRUE;' ) );
			$GLOBALS[ 'l10n' ] = array();
		}
		// execute function on job shutdown  register_shutdown_function( array( $this, 'shutdown' ) );
		add_action( 'shutdown', array( $this, 'shutdown' ) );
		//remove_action('shutdown', array( $this, 'shutdown' ));
		if ( function_exists( 'pcntl_signal' ) ) {
			$signals = array(
				'SIGHUP',
				'SIGINT',
				'SIGQUIT',
				'SIGILL',
				'SIGTRAP',
				'SIGABRT',
				'SIGBUS',
				'SIGFPE',
				//'SIGKILL',
				'SIGSEGV',
				//'SIGPIPE',
				//'SIGALRM',
				'SIGTERM',
				'SIGSTKFLT',
				'SIGUSR1',
				'SIGUSR2',
				//'SIGCHLD',
				//'SIGCONT',
				//'SIGSTOP',
				'SIGTSTP',
				'SIGTTIN',
				'SIGTTOU',
				'SIGURG',
				'SIGXCPU',
				'SIGXFSZ',
				//'SIGVTALRM',
				'SIGPROF',
				'SIGWINCH',
				//'SIGIO',
				'SIGPWR',
				'SIGSYS',
			);
			$signals = apply_filters( 'backwpup_job_signals_to_handel', $signals );
			declare( ticks = 1 ) ;
			foreach( $signals as $signal ) {
				if ( defined( $signal ) ) {
					pcntl_signal( constant( $signal ), array( $this, 'shutdown' ), FALSE );
				}
			}
		}
		//clear output buffer
		ob_start();
		while( @ob_end_clean() );
		@flush();
		$job_types = BackWPup::get_job_types();
		//go step by step
		foreach ( $this->steps_todo as $this->step_working ) {
			//Check if step already done
			if ( in_array( $this->step_working, $this->steps_done ) )
				continue;
			//calc step percent
			if ( count( $this->steps_done ) > 0 )
				$this->step_percent = round( count( $this->steps_done ) / count( $this->steps_todo ) * 100 );
			else
				$this->step_percent = 1;
			// do step tries
			while ( TRUE ) {
				if ( $this->steps_data[ $this->step_working ][ 'STEP_TRY' ] >= get_site_option( 'backwpup_cfg_jobstepretry' ) ) {
					$this->log( __( 'Step aborted: too many attempts!', 'backwpup' ), E_USER_ERROR );
					$this->temp = array();
					$this->steps_done[ ] = $this->step_working;
					$this->substeps_done = 0;
					$this->substeps_todo = 0;
					$this->do_restart();
					break;
				}

				$this->steps_data[ $this->step_working ][ 'STEP_TRY' ] ++;
				$done = FALSE;

				//executes the methods of job process
				if ( $this->step_working == 'CREATE_ARCHIVE' ) {
					$done = $this->create_archive();
				}
				elseif ( $this->step_working == 'CREATE_MANIFEST' ) {
					$done = $this->create_manifest();
				}
				elseif ( $this->step_working == 'END' ) {
					$this->end();
					break 2;
				}
				elseif ( strstr( $this->step_working, 'JOB_' ) ) {
					$done = $job_types[ str_replace( 'JOB_', '', $this->step_working ) ]->job_run( $this );
				}
				elseif ( strstr( $this->step_working, 'DEST_SYNC_' ) ) {
					$done = BackWPup::get_destination( str_replace( 'DEST_SYNC_', '', $this->step_working ) )->job_run_sync( $this );
				}
				elseif ( strstr( $this->step_working, 'DEST_' ) ) {
					$done = BackWPup::get_destination( str_replace( 'DEST_', '', $this->step_working ) )->job_run_archive( $this );
				}
				elseif ( ! empty( $this->steps_data[ $this->step_working ][ 'CALLBACK' ] ) ) {
					$done = $this->steps_data[ $this->step_working ][ 'CALLBACK' ]( $this );
				}

				// set step as done
				if ( $done === TRUE ) {
					$this->temp 		 = array();
					$this->steps_done[]  = $this->step_working;
					$this->substeps_done = 0;
					$this->substeps_todo = 0;
					$this->write_running_file();
				}
				if ( count( $this->steps_done ) < count( $this->steps_todo ) -1 ) {
					$this->do_restart();
				}
				if ( $done === TRUE ) {
					break;
				}
			}
		}
	}

	/**
	 * Do a job restart
	 *
	 * @param bool $must Restart must done
	 * @param bool $msg Log restart message
	 */
	public function do_restart( $must = FALSE ) {

		//no restart if in end step
		if ( $this->step_working == 'END' || ( count( $this->steps_done ) + 1 ) >= count( $this->steps_todo ) ) {
			return;
		}

		//no restart on cli usage
		if ( php_sapi_name() == 'cli' ) {
			return;
		}

		//no restart if no restart time configured
		$job_max_execution_time = get_site_option( 'backwpup_cfg_jobmaxexecutiontime' );
		if ( ! $must && empty( $job_max_execution_time ) ) {
			return;
		}

		//no restart when restart was 3 Seconds before
		$execution_time = microtime( TRUE ) - $this->timestamp_script_start;
		if ( ! $must  && $execution_time < 3 ) {
			return;
		}

		//no restart if no working job
		if ( ! file_exists( BackWPup::get_plugin_data( 'running_file' ) ) ) {
			return;
		}

		//print message
		if ( $this->is_debug() ) {
			$this->log( sprintf( __( 'Restart after %1$d seconds.', 'backwpup' ), ceil( $execution_time ) ) );
		}

		//do things for a clean restart
		$this->pid = 0;
		$this->uniqid = '';
		$this->write_running_file();
		remove_action( 'shutdown', array( $this, 'shutdown' ) );
		//do restart
		wp_clear_scheduled_hook( 'backwpup_cron', array( 'id' => 'restart' ) );
		wp_schedule_single_event( time() + 5, 'backwpup_cron', array( 'id' => 'restart' ) );
		self::get_jobrun_url( 'restart' );

		exit();
	}

	/**
	 * Do a job restart
	 *
	 * @param bool $do_restart_now should time restart now be done
	 * @return int remaining time
	 */
	public function do_restart_time( $do_restart_now = FALSE ) {

		$job_max_execution_time = get_site_option( 'backwpup_cfg_jobmaxexecutiontime' );

		if ( empty( $job_max_execution_time ) ) {
			return 300;
		}

		$execution_time = microtime( TRUE ) - $this->timestamp_script_start;

		// do restart 3 sec. before max. execution time
		if ( $do_restart_now || $execution_time >= ( $job_max_execution_time - 3 ) ) {
			$this->steps_data[ $this->step_working ][ 'SAVE_STEP_TRY' ] = $this->steps_data[ $this->step_working ][ 'STEP_TRY' ];
			$this->steps_data[ $this->step_working ][ 'STEP_TRY' ] -= 1;
			$this->do_restart( TRUE );
		}

		return $job_max_execution_time - $execution_time;

	}

	/**
	 * Get job restart time
	 *
	 * @return int remaining time
	 */
	public function get_restart_time() {

		$job_max_execution_time = get_site_option( 'backwpup_cfg_jobmaxexecutiontime' );

		if ( empty( $job_max_execution_time ) ) {
			return 300;
		}

		$execution_time = microtime( TRUE ) - $this->timestamp_script_start;
		return $job_max_execution_time - $execution_time - 3;
	}

	/**
	 *
	 * Get data off a working job
	 *
	 * @return bool|object BackWPup_Job Object or Bool if file not exits
	 */
	public static function get_working_data() {

		if ( version_compare( PHP_VERSION, '5.3', '>=' ) ) {
			clearstatcache( TRUE, BackWPup::get_plugin_data( 'running_file' ) );
		} else {
			clearstatcache();
		}

		if ( ! file_exists( BackWPup::get_plugin_data( 'running_file' ) ) ) {
			return FALSE;
		}

		$file_data = file_get_contents( BackWPup::get_plugin_data( 'running_file' ), FALSE, NULL, 8 );
		if ( empty( $file_data ) ) {
			return FALSE;
		}

		if ( $job_object = unserialize( $file_data ) ) {
			if ( $job_object instanceof BackWPup_Job )
				return $job_object;
		}

		return FALSE;

	}

	/**
	 *
	 * Reads a BackWPup logfile header and gives back a array of information
	 *
	 * @param string $logfile full logfile path
	 *
	 * @return array|bool
	 */
	public static function read_logheader( $logfile ) {

		$usedmetas = array(
			"date"                    => "logtime",
			"backwpup_logtime"        => "logtime", //old value of date
			"backwpup_errors"         => "errors",
			"backwpup_warnings"       => "warnings",
			"backwpup_jobid"          => "jobid",
			"backwpup_jobname"        => "name",
			"backwpup_jobtype"        => "type",
			"backwpup_jobruntime"     => "runtime",
			"backwpup_backupfilesize" => "backupfilesize"
		);

		//get metadata of logfile
		$metas = array();
		if ( is_readable( $logfile ) ) {
			if (  '.gz' == substr( $logfile, -3 ) )
				$metas = (array)get_meta_tags( 'compress.zlib://' . $logfile );
			else
				$metas = (array)get_meta_tags( $logfile );
		}

		//only output needed data
		foreach ( $usedmetas as $keyword => $field ) {
			if ( isset( $metas[ $keyword ] ) ) {
				$joddata[ $field ] = $metas[ $keyword ];
			} else {
				$joddata[ $field ] = '';
			}
		}

		//convert date
		if ( isset( $metas[ 'date' ] ) )
			$joddata[ 'logtime' ] = strtotime( $metas[ 'date' ] ) + ( get_option( 'gmt_offset' ) * 3600 );

		//use file create date if none
		if ( empty( $joddata[ 'logtime' ] ) )
			$joddata[ 'logtime' ] = filectime( $logfile );

		return $joddata;
	}


	/**
	 *
	 * Shutdown function is call if script terminates try to make a restart if needed
	 *
	 * Prepare the job for start
	 *
	 * @internal param int the signal that terminates the job
	 */
	public function shutdown() {

		$args = func_get_args();

		//Put last error to log if one
		$lasterror = error_get_last();
		if ( $lasterror[ 'type' ] == E_ERROR or $lasterror[ 'type' ] == E_PARSE or $lasterror[ 'type' ] == E_CORE_ERROR or $lasterror[ 'type' ] == E_CORE_WARNING or $lasterror[ 'type' ] == E_COMPILE_ERROR or $lasterror[ 'type' ] == E_COMPILE_WARNING ) {
			$this->log( $lasterror[ 'type' ], $lasterror[ 'message' ], $lasterror[ 'file' ], $lasterror[ 'line' ] );
		}

		//Put signals to log
		if ( ! empty( $args[ 0 ] ) ) {
			$signals = array(
				'SIGHUP',
				'SIGINT',
				'SIGQUIT',
				'SIGILL',
				'SIGTRAP',
				'SIGABRT',
				'SIGBUS',
				'SIGFPE',
				'SIGKILL',
				'SIGSEGV',
				'SIGPIPE',
				'SIGALRM',
				'SIGTERM',
				'SIGSTKFLT',
				'SIGUSR1',
				'SIGUSR2',
				'SIGCHLD',
				'SIGCONT',
				'SIGSTOP',
				'SIGTSTP',
				'SIGTTIN',
				'SIGTTOU',
				'SIGURG',
				'SIGXCPU',
				'SIGXFSZ',
				'SIGVTALRM',
				'SIGPROF',
				'SIGWINCH',
				'SIGIO',
				'SIGPWR',
				'SIGSYS'
			);
			foreach ( $signals as $signal ) {
				if ( defined( $signal ) && $args[ 0 ] === constant( $signal ) ) {
					$this->log( sprintf( __( 'Signal "%s" is sent to script!', 'backwpup' ), $signal ), E_USER_ERROR );
					break;
				}
			}
		}

		if ( function_exists( 'pcntl_get_last_error' ) ) {
			$error = pcntl_get_last_error();
			if ( ! empty( $error ) ) {
				$error_msg = pcntl_strerror( $error );
				if ( ! empty( $error_msg ) ) {
					$error = '(' . $error . ') ' . $error_msg;
				}
			}
			if ( ! empty( $error ) ) {
				$this->log( sprintf( __( 'System: %s', 'backwpup' ), $error ), E_USER_ERROR );
			}
		}

		if ( function_exists( 'posix_get_last_error' ) && empty( $error ) ) {
			$error = posix_get_last_error();
			if ( ! empty( $error ) ) {
				$error_msg = posix_strerror( $error );
				if ( ! empty( $error_msg ) ) {
					$error = '(' . $error . ') ' . $error_msg;
				}
			}
			if ( ! empty( $error ) ) {
				$this->log( sprintf( __( 'System: %s', 'backwpup' ), $error ), E_USER_ERROR );
			}
		}

		$this->do_restart( TRUE );
	}

	/**
	 *
	 * The uncouth exception handler
	 *
	 * @param object $exception
	 */
	public function exception_handler( $exception ) {

		$this->log( sprintf( __( 'Exception caught in %1$s: %2$s', 'backwpup' ), get_class( $exception ), $exception->getMessage() ), E_USER_ERROR,  $exception->getFile(), $exception->getLine() );
	}

	/**
	 * Write messages to log file
	 *
	 * @param string  $message  the error message
	 * @param int     $type the error number (E_USER_ERROR,E_USER_WARNING,E_USER_NOTICE, ...)
	 * @param string  $file the full path of file with error (__FILE__)
	 * @param int     $line the line in that is the error (__LINE__)
	 *
	 * @return bool true
	 */
	public function log( $message, $type = E_USER_NOTICE, $file = '', $line = 0 ) {

		// if error has been suppressed with an @
		if ( error_reporting() == 0 ) {
			return TRUE;
		}

		//if first the type an second the message switch it on user errors
		if ( ! is_int( $type ) && is_int( $message ) && in_array( $message, array( 1, 2, 4, 8, 16, 32, 64, 128, 256, 512, 1024, 2048, 4096, 8192, 16384 ) ) ) {
			$temp 		= $message;
			$message 	= $type;
			$type 	    = $temp;
		}

		//json message if array or object
		if ( is_array( $message ) || is_object( $message ) ) {
			$message = json_encode( $message );
		}

		//if not set line and file get it
		if ( $this->is_debug() ) {
			if ( empty( $file ) || empty( $line ) ) {
				$debug_info = debug_backtrace();
				$file       = $debug_info[ 0 ][ 'file' ];
				$line       = $debug_info[ 0 ][ 'line' ];
			}
		}

		$error_or_warning = FALSE;

		switch ( $type ) {
			case E_NOTICE:
			case E_USER_NOTICE:
				break;
			case E_WARNING:
			case E_CORE_WARNING:
			case E_COMPILE_WARNING:
			case E_USER_WARNING:
				$this->warnings ++;
				$error_or_warning = TRUE;
				$message = '%y' . __( 'WARNING:', 'backwpup' ) . ' ' . $message . '%n';
				break;
			case E_ERROR:
			case E_PARSE:
			case E_CORE_ERROR:
			case E_COMPILE_ERROR:
			case E_USER_ERROR:
				$this->errors ++;
				$error_or_warning = TRUE;
				$message = '%r' . __( 'ERROR:', 'backwpup' ) . ' ' . $message . '%n';
				break;
			case 8192: //E_DEPRECATED      comes with php 5.3
			case 16384: //E_USER_DEPRECATED comes with php 5.3
				$message = __( 'DEPRECATED:', 'backwpup' ) . ' ' . $message;
				break;
			case E_STRICT:
				$message = __( 'STRICT NOTICE:', 'backwpup' ) . ' ' . $message;
				break;
			case E_RECOVERABLE_ERROR:
				$this->errors ++;
				$error_or_warning = TRUE;
				$message = '%r' . __( 'RECOVERABLE ERROR:', 'backwpup' ) . ' ' . $message . '%n';
				break;
			default:
				$message = $type . ': ' . $message;
				break;
		}

		$in_file = $this->get_destination_path_replacement( $file );

		//print message to cli
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			$output_message = str_replace( array( '&hellip;', '&#160;' ), array( '...', ' ' ), strip_tags( $message ) );
			if ( !call_user_func( array( '\cli\Shell', 'isPiped' ) ) ) {
				$output_message = call_user_func( array( '\cli\Colors', 'colorize' ), $output_message, true );
			} else {
				$output_message = str_replace( array( '%y', '%r', '%n' ), '', $output_message );
			}
			WP_CLI::line( $output_message );
		} elseif ( php_sapi_name() == 'cli' && defined( 'STDOUT' ) ) {
			$output_message = str_replace( array( '&hellip;', '&#160;' ), array( '...', ' ' ), strip_tags( $message ) ) . PHP_EOL;
			$output_message = str_replace( array( '%y', '%r', '%n' ), '', $output_message );
			fwrite( STDOUT, $output_message ) ;
		}

		//timestamp for log file
		$debug_info = '';
		if ( $this->is_debug() ) {
			$debug_info = ' title="[Type: ' . $type . '|Line: ' . $line . '|File: ' . $in_file . '|Mem: ' . size_format( @memory_get_usage( TRUE ), 2 ) . '|Mem Max: ' . size_format( @memory_get_peak_usage( TRUE ), 2 ) . '|Mem Limit: ' . ini_get( 'memory_limit' ) . '|PID: ' . self::get_pid() . ' | UniqID: ' . $this->uniqid . '|Query\'s: ' . get_num_queries() . ']"';
		}
		$timestamp = '<span datetime="' . date( 'c' ) . '" ' . $debug_info . '>[' . date( 'd-M-Y H:i:s', current_time( 'timestamp' ) ) . ']</span> ';

		//set last Message
		$output_message = esc_attr( $message );
		$output_message = str_replace( array( '%y', '%r', '%n' ), array( '<span style="background-color:#ffc000;color:#fff">', '<span style="background-color:red;color:#fff">', '</span>' ), $output_message );
		if ( $error_or_warning ) {
			$this->lasterrormsg = $output_message;
		} else {
			$this->lastmsg = $output_message;
		}
		//write log file
		if ( ! empty( $this->logfile ) ) {
			if ( ! file_put_contents( $this->logfile, $timestamp . $output_message . '<br />' . PHP_EOL, FILE_APPEND ) ) {
				$this->logfile = '';
				restore_error_handler();
				trigger_error( str_replace( array( '%y', '%r', '%n' ), '', $message ), $type );
			}

			//write new log header
			if ( $error_or_warning && ! empty( $this->logfile ) ) {
				if ( $fd = fopen( $this->logfile, 'r+' ) ) {
					$found = 0;
					$file_pos = ftell( $fd );
					while ( ! feof( $fd ) ) {
						$line = fgets( $fd );
						if ( stripos( $line, '<meta name="backwpup_errors" content="' ) !== FALSE ) {
							fseek( $fd, $file_pos );
							fwrite( $fd, str_pad( '<meta name="backwpup_errors" content="' . $this->errors . '" />', 100 ) . PHP_EOL );
							$found ++;
						}
						if ( stripos( $line, '<meta name="backwpup_warnings" content="' ) !== FALSE ) {
							fseek( $fd, $file_pos );
							fwrite( $fd, str_pad( '<meta name="backwpup_warnings" content="' . $this->warnings . '" />', 100 ) . PHP_EOL );
							$found ++;
						}
						if ( $found >= 2 ) {
							break;
						}
						$file_pos = ftell( $fd );
					}
					fclose( $fd );
				}
			}

		} else {
			trigger_error( str_replace( array( '%y', '%r', '%n' ), '', $message ), $type );
		}

		//write working data
		$this->update_working_data( $error_or_warning );

		//true for no more php error handling.
		return TRUE;
	}

	/**
	 *
	 * Write the Working data to display the process or that i can executes again
	 * The write will only done every second
	 *
	 * @global wpdb $wpdb
	 */
	public function update_working_data() {
		global $wpdb;
		/* @var wpdb $wpdb */

		//to reduce server load
		if ( get_site_option( 'backwpup_cfg_jobwaittimems' ) > 0 && get_site_option( 'backwpup_cfg_jobwaittimems') <= 500000 ) {
			usleep( get_site_option( 'backwpup_cfg_jobwaittimems' ) );
		}

		//check free memory
		$this->need_free_memory( '10M' );

		//only run every 1 sec.
		$time_to_update = microtime( TRUE ) - $this->timestamp_last_update;
		if ( $time_to_update < 1 ) {
			return;
		}

		//FCGI must have a permanent output so that it not broke
		if ( get_site_option( 'backwpup_cfg_jobdooutput' ) && ! defined( 'STDOUT' ) ) {
			echo str_repeat( ' ', 12 );
			flush();
		}

		//set execution time again for 5 min
		@set_time_limit( 300 );

		//check MySQL connection to WordPress Database and reconnect if needed
		$res = $wpdb->query( 'SELECT ' . time() );
		if ( $res === FALSE ) {
			$wpdb->db_connect();
		}

		//calc sub step percent
		if ( $this->substeps_todo > 0 && $this->substeps_done > 0 ) {
			$this->substep_percent = round( $this->substeps_done / $this->substeps_todo * 100 );
		} else {
			$this->substep_percent = 1;
		}

		//check if job aborted
		if ( ! file_exists( BackWPup::get_plugin_data( 'running_file' ) ) ) {
			if ( $this->step_working != 'END' ) {
				$this->end();
			}
		} else {
			$this->timestamp_last_update = microtime( TRUE ); //last update of working file
			$this->write_running_file();
		}
	}

	public function write_running_file() {

		$clone = clone $this;
		$data = '<?php //' . serialize( $clone );

		$write = file_put_contents( BackWPup::get_plugin_data( 'running_file' ), $data );
		if ( !$write || $write < strlen( $data ) ) {
			unlink( BackWPup::get_plugin_data( 'running_file' ) );
			$this->log( __( 'Cannot write progress to working file. Job will be aborted.', 'backwpup' ), E_USER_ERROR );
		}
	}

	/**
	 *
	 * Called on job stop makes cleanup and terminates the script
	 *
	 */
	private function end() {

		$this->step_working = 'END';
		$this->substeps_todo = 1;
		$abort = FALSE;

		if ( ! file_exists( BackWPup::get_plugin_data( 'running_file' ) ) ) {
			if ( ! $this->user_abort )
				$abort = TRUE;
			$this->log( __( 'Aborted by user!', 'backwpup' ), E_USER_ERROR );
		}

		//delete old logs
		if ( get_site_option( 'backwpup_cfg_maxlogs' ) ) {
			$log_file_list = array();
			$log_folder = trailingslashit( dirname( $this->logfile ) );
			if ( is_readable( $log_folder ) && $dir = opendir( $log_folder ) ) { //make file list
				while ( ( $file = readdir( $dir ) ) !== FALSE ) {
					if ( strpos( $file, 'backwpup_log_' ) == 0 && FALSE !== strpos( $file, '.html' ) )
						$log_file_list[ filemtime( $log_folder . $file ) ] = $file;
				}
				closedir( $dir );
			}
			if ( sizeof( $log_file_list ) > 0 ) {
				krsort( $log_file_list, SORT_NUMERIC );
				$num_delete_files = 0;
				$i = -1;
				foreach ( $log_file_list AS $log_file ) {
					$i ++;
					if ( $i < get_site_option( 'backwpup_cfg_maxlogs' ) ) {
						continue;
					}
					unlink( $log_folder . $log_file );
					$num_delete_files ++;
				}
				if ( $num_delete_files > 0 )
					$this->log( sprintf( _n( 'One old log deleted', '%d old logs deleted', $num_delete_files, 'backwpup' ), $num_delete_files ) );
			}
		}

		//Display job working time
		if ( $this->errors > 0 )
			$this->log( sprintf( __( 'Job has ended with errors in %s seconds. You must resolve the errors for correct execution.', 'backwpup' ), current_time( 'timestamp' ) - $this->start_time ), E_USER_ERROR );
		elseif ( $this->warnings > 0 )
			$this->log( sprintf( __( 'Job finished with warnings in %s seconds. Please resolve them for correct execution.', 'backwpup' ), current_time( 'timestamp' ) - $this->start_time ), E_USER_WARNING );
		else
			$this->log( sprintf( __( 'Job done in %s seconds.', 'backwpup' ), current_time( 'timestamp' ) - $this->start_time, E_USER_NOTICE ) );

		//Update job options
		if ( ! empty( $this->job[ 'jobid' ] ) ) {
			$this->job[ 'lastruntime' ] = current_time( 'timestamp' ) - $this->start_time;
			BackWPup_Option::update( $this->job[ 'jobid' ], 'lastruntime', $this->job[ 'lastruntime' ] );
		}

		//write header info
		if ( ! empty( $this->logfile ) ) {

			if ( $fd = fopen( $this->logfile, 'r+' ) ) {
				$filepos = ftell( $fd );
				$found = 0;
				while ( ! feof( $fd ) ) {
					$line = fgets( $fd );
					if ( stripos( $line, '<meta name="backwpup_jobruntime"' ) !== FALSE ) {
						fseek( $fd, $filepos );
						fwrite( $fd, str_pad( '<meta name="backwpup_jobruntime" content="' . $this->job[ 'lastruntime' ] . '" />', 100 ) . PHP_EOL );
						$found ++;
					}
					if ( stripos( $line, '<meta name="backwpup_backupfilesize"' ) !== FALSE ) {
						fseek( $fd, $filepos );
						fwrite( $fd, str_pad( '<meta name="backwpup_backupfilesize" content="' . $this->backup_filesize . '" />', 100 ) . PHP_EOL );
						$found ++;
					}
					if ( $found >= 2 ) {
						break;
					}
					$filepos = ftell( $fd );
				}
				fclose( $fd );
			}

			//logfile end
			file_put_contents( $this->logfile, "</body>" . PHP_EOL . "</html>", FILE_APPEND );

			//Send mail with log
			$sendmail = FALSE;
			if ( $this->errors > 0 && ! empty( $this->job[ 'mailerroronly' ] ) && ! empty( $this->job[ 'mailaddresslog' ] ) )
				$sendmail = TRUE;
			if ( empty( $this->job[ 'mailerroronly' ] ) && ! empty( $this->job[ 'mailaddresslog' ] ) )
				$sendmail = TRUE;
			if ( $sendmail ) {
				//special subject
				$status   = __( 'SUCCESSFUL', 'backwpup' );
				$priority = 3; //Normal
				if ( $this->warnings > 0 ) {
					$status   = __( 'WARNING', 'backwpup' );
					$priority = 2; //High
				}
				if ( $this->errors > 0 ) {
					$status   = __( 'ERROR', 'backwpup' );
					$priority = 1; //Highest
				}

				$subject = sprintf( __( '[%3$s] BackWPup log %1$s: %2$s', 'backwpup' ), date_i18n( 'd-M-Y H:i', $this->start_time, TRUE ), esc_attr( $this->job[ 'name' ] ), $status );
				$headers = array();
				$headers[] = 'Content-Type: text/html; charset='. get_bloginfo( 'charset' );
				/* $headers[] = 'X-Priority: ' . $priority; */ // Priority not working with header setting
				if ( ! empty( $this->job[ 'mailaddresssenderlog' ] ) ) {
					if ( FALSE === $start_mail = strpos( $this->job[ 'mailaddresssenderlog' ], '<' ) ) {
						if ( FALSE === strpos( $this->job[ 'mailaddresssenderlog' ], '@' ) ) {
							$this->job[ 'mailaddresssenderlog' ] = '"' . str_replace( array( '<','>','@' ), '', $this->job[ 'mailaddresssenderlog' ] ) . '" <' . get_bloginfo( 'admin_email' ). '>';
						}
					}
					elseif ( FALSE === strpos( $this->job[ 'mailaddresssenderlog' ], '>', $start_mail ) ) {
						$this->job[ 'mailaddresssenderlog' ] = '"' . str_replace( array( '<','>','@' ), '', substr( $this->job[ 'mailaddresssenderlog' ], 0, $start_mail ) ) . '" <' . get_bloginfo( 'admin_email' ). '>';
					}

					$headers[] = 'From: ' . $this->job[ 'mailaddresssenderlog' ];
				}

				wp_mail( $this->job[ 'mailaddresslog' ], $subject, file_get_contents( $this->logfile ), $headers );
			}
		}

		//set done
		$this->substeps_done = 1;
		$this->steps_done[ ] = 'END';

		//clean up temp
		self::clean_temp_folder();

		//remove shutdown action
		remove_action( 'shutdown', array( $this, 'shutdown' ) );
		restore_exception_handler();
		restore_error_handler();
		if ( ! empty( $this->run[ 'PHP' ] ) ) {
			@ini_set( 'log_errors', $this->run[ 'PHP' ][ 'INI' ][ 'LOG_ERRORS' ] );
			@ini_set( 'error_log', $this->run[ 'PHP' ][ 'INI' ][ 'ERROR_LOG' ] );
			@ini_set( 'display_errors', $this->run[ 'PHP' ][ 'INI' ][ 'DISPLAY_ERRORS' ] );
			@ini_set( 'html_errors', $this->run[ 'PHP' ][ 'INI' ][ 'HTML_ERRORS' ] );
			@ini_set( 'zlib.output_compression', $this->run[ 'PHP' ][ 'INI' ][ 'ZLIB_OUTPUT_COMPRESSION' ] );
			@ini_set( 'implicit_flush', $this->run[ 'PHP' ][ 'INI' ][ 'IMPLICIT_FLUSH' ] );
			@ini_set( 'error_reporting', $this->run[ 'PHP' ][ 'INI' ][ 'ERROR_REPORTING' ] );
			@ini_set( 'report_memleaks', $this->run[ 'PHP' ][ 'INI' ][ 'REPORT_MEMLEAKS' ] );
			if ( !empty( $this->run[ 'PHP' ][ 'ENV' ][ 'TEMPDIR' ] ) ) {
				@putenv('TMPDIR=' . $this->run[ 'PHP' ][ 'ENV' ][ 'TEMPDIR' ] );
			}
		}

		BackWPup_Cron::check_cleanup();

		if ( $abort )
			exit();
	}


	public static function user_abort() {

		/* @var $job_object BackWPup_Job */
		$job_object = BackWPup_Job::get_working_data();

		unlink( BackWPup::get_plugin_data( 'running_file' ) );

		//if job not working currently abort it this way for message
		$not_worked_time = microtime( TRUE ) - $job_object->timestamp_last_update;
		$restart_time = get_site_option( 'backwpup_cfg_jobmaxexecutiontime' );
		if ( empty( $restart_time ) )
			$restart_time = 60;
		if ( empty( $job_object->pid ) || $not_worked_time > $restart_time ) {
			$job_object->user_abort = TRUE;
			$job_object->update_working_data();
		}

	}

	/**
	 *
	 * Increase automatically the memory that is needed
	 *
	 * @param int|string $memneed of the needed memory
	 */
	public function need_free_memory( $memneed ) {

		//need memory
		$needmemory = @memory_get_usage( TRUE ) + self::convert_hr_to_bytes( $memneed );
		// increase Memory
		if ( $needmemory > self::convert_hr_to_bytes( ini_get( 'memory_limit' ) ) ) {
			$newmemory = round( $needmemory / 1024 / 1024 ) + 1 . 'M';
			if ( $needmemory >= 1073741824 )
				$newmemory = round( $needmemory / 1024 / 1024 / 1024 ) . 'G';
			@ini_set( 'memory_limit', $newmemory );
		}
	}


	/**
	 *
	 * Converts hr to bytes
	 *
	 * @param $size
	 * @return int
	 */
	public static function convert_hr_to_bytes( $size ) {
		$size  = strtolower( $size );
		$bytes = (int) $size;
		if ( strpos( $size, 'k' ) !== FALSE )
			$bytes = intval( $size ) * 1024;
		elseif ( strpos( $size, 'm' ) !== FALSE )
			$bytes = intval($size) * 1024 * 1024;
		elseif ( strpos( $size, 'g' ) !== FALSE )
			$bytes = intval( $size ) * 1024 * 1024 * 1024;
		return $bytes;
	}

	/**
	 *
	 * Callback for the CURLOPT_READFUNCTION that submit the transferred bytes
	 * to build the process bar
	 *
	 * @param $curl_handle
	 * @param $file_handle
	 * @param $read_count
	 * @return string
	 * @internal param $out
	 */
	public function curl_read_callback( $curl_handle, $file_handle, $read_count ) {

		$data = NULL;
		if ( ! empty( $file_handle ) && is_numeric( $read_count ) )
			$data = fread( $file_handle, $read_count );

		if (  $this->job[ 'backuptype' ] == 'sync'  )
			return $data;

		$length = ( is_numeric( $read_count ) ) ? $read_count : strlen( $read_count );
		$this->substeps_done = $this->substeps_done + $length;
		$this->update_working_data();

		return $data;
	}


	/**
	 *
	 * Get the mime type of a file
	 *
	 * @param string $file The full file name
	 *
	 * @return bool|string the mime type or false
	 */
	public static function get_mime_type( $file ) {

		if ( is_dir( $file ) || is_link( $file ) ) {
			return 'application/octet-stream';
		}

		$mime_types = array(
			'zip'     => 'application/zip',
			'gz'      => 'application/gzip',
			'bz2'     => 'application/x-bzip',
			'tar'     => 'application/x-tar',
			'3gp'     => 'video/3gpp',
			'ai'      => 'application/postscript',
			'aif'     => 'audio/x-aiff',
			'aifc'    => 'audio/x-aiff',
			'aiff'    => 'audio/x-aiff',
			'asc'     => 'text/plain',
			'atom'    => 'application/atom+xml',
			'au'      => 'audio/basic',
			'avi'     => 'video/x-msvideo',
			'bcpio'   => 'application/x-bcpio',
			'bin'     => 'application/octet-stream',
			'bmp'     => 'image/bmp',
			'cdf'     => 'application/x-netcdf',
			'cgm'     => 'image/cgm',
			'class'   => 'application/octet-stream',
			'cpio'    => 'application/x-cpio',
			'cpt'     => 'application/mac-compactpro',
			'csh'     => 'application/x-csh',
			'css'     => 'text/css',
			'dcr'     => 'application/x-director',
			'dif'     => 'video/x-dv',
			'dir'     => 'application/x-director',
			'djv'     => 'image/vnd.djvu',
			'djvu'    => 'image/vnd.djvu',
			'dll'     => 'application/octet-stream',
			'dmg'     => 'application/octet-stream',
			'dms'     => 'application/octet-stream',
			'doc'     => 'application/msword',
			'dtd'     => 'application/xml-dtd',
			'dv'      => 'video/x-dv',
			'dvi'     => 'application/x-dvi',
			'dxr'     => 'application/x-director',
			'eps'     => 'application/postscript',
			'etx'     => 'text/x-setext',
			'exe'     => 'application/octet-stream',
			'ez'      => 'application/andrew-inset',
			'flv'     => 'video/x-flv',
			'gif'     => 'image/gif',
			'gram'    => 'application/srgs',
			'grxml'   => 'application/srgs+xml',
			'gtar'    => 'application/x-gtar',
			'hdf'     => 'application/x-hdf',
			'hqx'     => 'application/mac-binhex40',
			'htm'     => 'text/html',
			'html'    => 'text/html',
			'ice'     => 'x-conference/x-cooltalk',
			'ico'     => 'image/x-icon',
			'ics'     => 'text/calendar',
			'ief'     => 'image/ief',
			'ifb'     => 'text/calendar',
			'iges'    => 'model/iges',
			'igs'     => 'model/iges',
			'jnlp'    => 'application/x-java-jnlp-file',
			'jp2'     => 'image/jp2',
			'jpe'     => 'image/jpeg',
			'jpeg'    => 'image/jpeg',
			'jpg'     => 'image/jpeg',
			'js'      => 'application/x-javascript',
			'kar'     => 'audio/midi',
			'latex'   => 'application/x-latex',
			'lha'     => 'application/octet-stream',
			'lzh'     => 'application/octet-stream',
			'm3u'     => 'audio/x-mpegurl',
			'm4a'     => 'audio/mp4a-latm',
			'm4p'     => 'audio/mp4a-latm',
			'm4u'     => 'video/vnd.mpegurl',
			'm4v'     => 'video/x-m4v',
			'mac'     => 'image/x-macpaint',
			'man'     => 'application/x-troff-man',
			'mathml'  => 'application/mathml+xml',
			'me'      => 'application/x-troff-me',
			'mesh'    => 'model/mesh',
			'mid'     => 'audio/midi',
			'midi'    => 'audio/midi',
			'mif'     => 'application/vnd.mif',
			'mov'     => 'video/quicktime',
			'movie'   => 'video/x-sgi-movie',
			'mp2'     => 'audio/mpeg',
			'mp3'     => 'audio/mpeg',
			'mp4'     => 'video/mp4',
			'mpe'     => 'video/mpeg',
			'mpeg'    => 'video/mpeg',
			'mpg'     => 'video/mpeg',
			'mpga'    => 'audio/mpeg',
			'ms'      => 'application/x-troff-ms',
			'msh'     => 'model/mesh',
			'mxu'     => 'video/vnd.mpegurl',
			'nc'      => 'application/x-netcdf',
			'oda'     => 'application/oda',
			'ogg'     => 'application/ogg',
			'ogv'     => 'video/ogv',
			'pbm'     => 'image/x-portable-bitmap',
			'pct'     => 'image/pict',
			'pdb'     => 'chemical/x-pdb',
			'pdf'     => 'application/pdf',
			'pgm'     => 'image/x-portable-graymap',
			'pgn'     => 'application/x-chess-pgn',
			'pic'     => 'image/pict',
			'pict'    => 'image/pict',
			'png'     => 'image/png',
			'pnm'     => 'image/x-portable-anymap',
			'pnt'     => 'image/x-macpaint',
			'pntg'    => 'image/x-macpaint',
			'ppm'     => 'image/x-portable-pixmap',
			'ppt'     => 'application/vnd.ms-powerpoint',
			'ps'      => 'application/postscript',
			'qt'      => 'video/quicktime',
			'qti'     => 'image/x-quicktime',
			'qtif'    => 'image/x-quicktime',
			'ra'      => 'audio/x-pn-realaudio',
			'ram'     => 'audio/x-pn-realaudio',
			'ras'     => 'image/x-cmu-raster',
			'rdf'     => 'application/rdf+xml',
			'rgb'     => 'image/x-rgb',
			'rm'      => 'application/vnd.rn-realmedia',
			'roff'    => 'application/x-troff',
			'rtf'     => 'text/rtf',
			'rtx'     => 'text/richtext',
			'sgm'     => 'text/sgml',
			'sgml'    => 'text/sgml',
			'sh'      => 'application/x-sh',
			'shar'    => 'application/x-shar',
			'silo'    => 'model/mesh',
			'sit'     => 'application/x-stuffit',
			'skd'     => 'application/x-koan',
			'skm'     => 'application/x-koan',
			'skp'     => 'application/x-koan',
			'skt'     => 'application/x-koan',
			'smi'     => 'application/smil',
			'smil'    => 'application/smil',
			'snd'     => 'audio/basic',
			'so'      => 'application/octet-stream',
			'spl'     => 'application/x-futuresplash',
			'src'     => 'application/x-wais-source',
			'sv4cpio' => 'application/x-sv4cpio',
			'sv4crc'  => 'application/x-sv4crc',
			'svg'     => 'image/svg+xml',
			'swf'     => 'application/x-shockwave-flash',
			't'       => 'application/x-troff',
			'tcl'     => 'application/x-tcl',
			'tex'     => 'application/x-tex',
			'texi'    => 'application/x-texinfo',
			'texinfo' => 'application/x-texinfo',
			'tif'     => 'image/tiff',
			'tiff'    => 'image/tiff',
			'tr'      => 'application/x-troff',
			'tsv'     => 'text/tab-separated-values',
			'txt'     => 'text/plain',
			'ustar'   => 'application/x-ustar',
			'vcd'     => 'application/x-cdlink',
			'vrml'    => 'model/vrml',
			'vxml'    => 'application/voicexml+xml',
			'wav'     => 'audio/x-wav',
			'wbmp'    => 'image/vnd.wap.wbmp',
			'wbxml'   => 'application/vnd.wap.wbxml',
			'webm'    => 'video/webm',
			'wml'     => 'text/vnd.wap.wml',
			'wmlc'    => 'application/vnd.wap.wmlc',
			'wmls'    => 'text/vnd.wap.wmlscript',
			'wmlsc'   => 'application/vnd.wap.wmlscriptc',
			'wmv'     => 'video/x-ms-wmv',
			'wrl'     => 'model/vrml',
			'xbm'     => 'image/x-xbitmap',
			'xht'     => 'application/xhtml+xml',
			'xhtml'   => 'application/xhtml+xml',
			'xls'     => 'application/vnd.ms-excel',
			'xml'     => 'application/xml',
			'xpm'     => 'image/x-xpixmap',
			'xsl'     => 'application/xml',
			'xslt'    => 'application/xslt+xml',
			'xul'     => 'application/vnd.mozilla.xul+xml',
			'xwd'     => 'image/x-xwindowdump',
			'xyz'     => 'chemical/x-xyz',
		);

		$filesuffix = pathinfo( $file, PATHINFO_EXTENSION );
		$suffix = strtolower( $filesuffix );
		if ( isset( $mime_types[ $suffix ] ) ) {
			return $mime_types[ $suffix ];
		}

		if ( ! is_readable( $file ) ) {
			return 'application/octet-stream';
		}

		if ( function_exists( 'fileinfo' ) ) {
			$finfo = finfo_open( FILEINFO_MIME_TYPE );
			$mime = finfo_file( $finfo, $file );
		}

		if ( empty( $mime ) && function_exists( 'mime_content_type' ) ) {
			$mime = mime_content_type( $file );
		}

		if ( ! empty( $mime ) ) {
			return $mime;
		}

		return 'application/octet-stream';
	}


	/**
	 *
	 * Gifs back a array of files to backup in the selected folder
	 *
	 * @param string $folder the folder to get the files from
	 *
	 * @return array files to backup
	 */
	public function get_files_in_folder( $folder ) {

		$files = array();
		$folder = trailingslashit( $folder );

		if ( ! is_dir( $folder ) ) {
			$this->log( sprintf( _x( 'Folder %s not exists', 'Folder name', 'backwpup' ), $folder ), E_USER_WARNING );
			return $files;
		}

		if ( ! is_readable( $folder ) ) {
			$this->log( sprintf( _x( 'Folder %s not readable', 'Folder name', 'backwpup' ), $folder ), E_USER_WARNING );
			return $files;
		}

		if ( $dir = opendir( $folder ) ) {
			while ( FALSE !== ( $file = readdir( $dir ) ) ) {
				if ( in_array( $file, array( '.', '..' ) ) || is_dir( $folder . $file ) ) {
					continue;
				}
				foreach ( $this->exclude_from_backup as $exclusion ) { //exclude files
					$exclusion = trim( $exclusion );
					if ( FALSE !== stripos( $folder . $file, trim( $exclusion ) ) && ! empty( $exclusion ) ) {
						continue 2;
					}
				}
				if ( $this->job[ 'backupexcludethumbs' ] && strpos( $folder, BackWPup_File::get_upload_dir() ) !== FALSE && preg_match( "/\-[0-9]{1,4}x[0-9]{1,4}.+\.(jpg|png|gif)$/i", $file ) ) {
					continue;
				}
				if ( is_link( $folder . $file ) ) {
					$this->log( sprintf( __( 'Link "%s" not following.', 'backwpup' ), $folder . $file ), E_USER_WARNING );
				} elseif ( ! is_readable( $folder . $file ) ) {
					$this->log( sprintf( __( 'File "%s" is not readable!', 'backwpup' ), $folder . $file ), E_USER_WARNING );
				} else {
					$file_size = filesize( $folder . $file );
					if ( ! is_int( $file_size ) || $file_size < 0 || $file_size > 2147483647 ) {
						$this->log( sprintf( __( 'File size of “%s” cannot be retrieved. File might be too large and will not be added to queue.', 'backwpup' ), $folder . $file . ' ' . $file_size ), E_USER_WARNING );
						continue;
					}
					$files[] = $folder . $file;
				}
			}
			closedir( $dir );
		}

		return $files;
	}

	/**
	 * create manifest file
	 * @return bool
	 */
	public function create_manifest( ) {

		$this->substeps_todo = 3;

		$this->log( sprintf( __( '%d. Trying to generate a manifest file&#160;&hellip;', 'backwpup' ), $this->steps_data[ $this->step_working ][ 'STEP_TRY' ] ) );

		//build manifest
		$manifest = array();
		// add blog information
		$manifest[ 'blog_info' ][ 'url' ] = home_url();
		$manifest[ 'blog_info' ][ 'wpurl' ] = site_url();
		$manifest[ 'blog_info' ][ 'prefix' ] = $GLOBALS[ 'wpdb' ]->prefix;
		$manifest[ 'blog_info' ][ 'description' ] = get_option('blogdescription');
		$manifest[ 'blog_info' ][ 'stylesheet_directory' ] =  get_template_directory_uri();
		$manifest[ 'blog_info' ][ 'activate_plugins' ] = wp_get_active_and_valid_plugins();
		$manifest[ 'blog_info' ][ 'activate_theme' ] = wp_get_theme()->get('Name');
		$manifest[ 'blog_info' ][ 'admin_email' ] = get_option('admin_email');
		$manifest[ 'blog_info' ][ 'charset' ] = get_bloginfo( 'charset' );
		$manifest[ 'blog_info' ][ 'version' ] = BackWPup::get_plugin_data( 'wp_version' );
		$manifest[ 'blog_info' ][ 'backwpup_version' ] = BackWPup::get_plugin_data( 'version' );
		$manifest[ 'blog_info' ][ 'language' ] = get_bloginfo( 'language' );
		$manifest[ 'blog_info' ][ 'name' ] = get_bloginfo( 'name' );
		$manifest[ 'blog_info' ][ 'abspath' ] = ABSPATH;
		$manifest[ 'blog_info' ][ 'uploads' ] = wp_upload_dir();
		$manifest[ 'blog_info' ][ 'contents' ][ 'basedir' ] = WP_CONTENT_DIR;
		$manifest[ 'blog_info' ][ 'contents' ][ 'baseurl' ] = WP_CONTENT_URL;
		$manifest[ 'blog_info' ][ 'plugins' ][ 'basedir' ] = WP_PLUGIN_DIR;
		$manifest[ 'blog_info' ][ 'plugins' ][ 'baseurl' ] = WP_PLUGIN_URL;
		$manifest[ 'blog_info' ][ 'themes' ][ 'basedir' ] = get_theme_root();
		$manifest[ 'blog_info' ][ 'themes' ][ 'baseurl' ] = get_theme_root_uri();
		// add job settings
		$manifest[ 'job_settings' ] = $this->job;
		// add archive info
		foreach( $this->additional_files_to_backup as $file ) {
			$manifest[ 'archive' ][ 'extra_files' ][] = basename( $file );
		}
		if ( isset( $this->steps_data[ 'JOB_FILE' ] ) ) {
			if ( $this->job[ 'backuproot'] )
				$manifest[ 'archive' ][ 'abspath' ] = trailingslashit( $this->get_destination_path_replacement( ABSPATH ) );
			if ( $this->job[ 'backupuploads'] )
				$manifest[ 'archive' ][ 'uploads' ] = trailingslashit( $this->get_destination_path_replacement( BackWPup_File::get_upload_dir() ) );
			if ( $this->job[ 'backupcontent'] )
				$manifest[ 'archive' ][ 'contents' ] = trailingslashit( $this->get_destination_path_replacement( WP_CONTENT_DIR ) );
			if ( $this->job[ 'backupplugins'])
				$manifest[ 'archive' ][ 'plugins' ] = trailingslashit( $this->get_destination_path_replacement( WP_PLUGIN_DIR ) );
			if ( $this->job[ 'backupthemes'] )
				$manifest[ 'archive' ][ 'themes' ] = trailingslashit( $this->get_destination_path_replacement( get_theme_root() ) );
		}

		if ( ! file_put_contents( BackWPup::get_plugin_data( 'TEMP' ) . 'manifest.json', json_encode( $manifest ) ) )
			return FALSE;
		$this->substeps_done = 1;

		//Create backwpup_readme.txt
		$readme_text  = __( 'You may have noticed the manifest.json file in this archive.', 'backwpup' ) . PHP_EOL;
		$readme_text .= __( 'manifest.json might be needed for later restoring a backup from this archive.', 'backwpup' ) . PHP_EOL;
		$readme_text .= __( 'Please leave manifest.json untouched and in place. Otherwise it is safe to be ignored.', 'backwpup' ) . PHP_EOL;
		if ( ! file_put_contents( BackWPup::get_plugin_data( 'TEMP' ) . 'backwpup_readme.txt', $readme_text ) ) {
			return FALSE;
		}
		$this->substeps_done = 2;

		//add file to backup files
		if ( is_readable( BackWPup::get_plugin_data( 'TEMP' ) . 'manifest.json' ) ) {
			$this->additional_files_to_backup[ ] = BackWPup::get_plugin_data( 'TEMP' ) . 'manifest.json';
			$this->additional_files_to_backup[ ] = BackWPup::get_plugin_data( 'TEMP' ) . 'backwpup_readme.txt';
			$this->log( sprintf( __( 'Added manifest.json file with %1$s to backup file list.', 'backwpup' ), size_format( filesize( BackWPup::get_plugin_data( 'TEMP' ) . 'manifest.json' ), 2 ) ) );
		}
		$this->substeps_done = 3;

		return TRUE;
	}

	/**
	 * Creates the backup archive
	 */
	private function create_archive() {

		//load folders to backup
		$folders_to_backup = $this->get_folders_to_backup();

		$this->substeps_todo = $this->count_folder  + 1;

		//initial settings for restarts in archiving
		if ( ! isset( $this->steps_data[ $this->step_working ]['on_file'] ) ) {
			$this->steps_data[ $this->step_working ]['on_file'] = '';
		}
		if ( ! isset( $this->steps_data[ $this->step_working ]['on_folder'] ) ) {
			$this->steps_data[ $this->step_working ]['on_folder'] = '';
		}

		if ( $this->steps_data[ $this->step_working ][ 'on_folder' ] == '' && $this->steps_data[ $this->step_working ][ 'on_file' ] == '' && is_file( $this->backup_folder . $this->backup_file ) ) {
			unlink( $this->backup_folder . $this->backup_file );
		}

		if ( $this->steps_data[ $this->step_working ]['SAVE_STEP_TRY'] != $this->steps_data[ $this->step_working ][ 'STEP_TRY' ] )
			$this->log( sprintf( __( '%d. Trying to create backup archive &hellip;', 'backwpup' ), $this->steps_data[ $this->step_working ][ 'STEP_TRY' ] ), E_USER_NOTICE );

		try {
			$backup_archive = new BackWPup_Create_Archive( $this->backup_folder . $this->backup_file );

			//show method for creation
			if ( $this->substeps_done == 0 ) {
				$this->log( sprintf( _x( 'Compressing files as %s. Please be patient, this may take a moment.', 'Archive compression method', 'backwpup'), $backup_archive->get_method() ) );
			}

			//add extra files
			if ( $this->substeps_done == 0 ) {
				if ( ! empty( $this->additional_files_to_backup ) && $this->substeps_done == 0 ) {
					if ( $this->is_debug() ) {
						$this->log( __( 'Adding Extra files to Archive', 'backwpup' ) );
					}
					foreach ( $this->additional_files_to_backup as $file ) {
						if ( $backup_archive->add_file( $file, basename( $file ) ) ) {;
							$this->count_files ++;
							$this->count_files_size = $this->count_files_size + filesize( $file );
							$this->update_working_data();
						} else {
							$backup_archive->close();
							$this->steps_data[ $this->step_working ][ 'on_file' ] = '';
							$this->steps_data[ $this->step_working ][ 'on_folder' ] = '';
							$this->log( __( 'Cannot create backup archive correctly. Aborting creation.', 'backwpup' ), E_USER_ERROR );
							return FALSE;
						}
					}
				}
				$this->substeps_done ++;
			}

			//add normal files
			while ( $folder = array_shift( $folders_to_backup ) ) {
				//jump over already done folders
				if ( in_array( $this->steps_data[ $this->step_working ]['on_folder'], $folders_to_backup ) ) {
					continue;
				}
				if ( $this->is_debug() ) {
					$this->log( sprintf( __( 'Archiving Folder: %s', 'backwpup' ), $folder ) );
				}
				$this->steps_data[ $this->step_working ]['on_folder'] = $folder;
				$files_in_folder = $this->get_files_in_folder( $folder );
				//add empty folders
				if ( empty( $files_in_folder ) ) {
					$folder_name_in_archive = trim( ltrim( $this->get_destination_path_replacement( $folder ), '/' ) );
					if ( ! empty ( $folder_name_in_archive ) ) {
						$backup_archive->add_empty_folder( $folder, $folder_name_in_archive );
					}
					continue;
				}
				//add files
				while ( $file = array_shift( $files_in_folder ) ) {
					//jump over already done files
					if ( in_array( $this->steps_data[ $this->step_working ]['on_file'], $files_in_folder ) ) {
						continue;
					}
					$this->steps_data[ $this->step_working ]['on_file'] = $file;
					//restart if needed
					$restart_time = $this->get_restart_time();
					if ( $restart_time <= 0 ) {
						unset( $backup_archive );
						$this->do_restart_time( TRUE );
						return FALSE;
					}
					//generate filename in archive
					$in_archive_filename = ltrim( $this->get_destination_path_replacement( $file ), '/' );
					//add file to archive
					if ( $backup_archive->add_file( $file, $in_archive_filename ) ) {
						$this->count_files ++;
						$this->count_files_size = $this->count_files_size + filesize( $file );
						$this->update_working_data();
					} else {
						$backup_archive->close();
						unset( $backup_archive );
						$this->steps_data[ $this->step_working ][ 'on_file' ] = '';
						$this->steps_data[ $this->step_working ][ 'on_folder' ] = '';
						$this->substeps_done = 0;
						$this->backup_filesize = filesize( $this->backup_folder . $this->backup_file );
						if ( $this->backup_filesize === FALSE ) {
							$this->backup_filesize = PHP_INT_MAX;
						}
						$this->log( __( 'Cannot create backup archive correctly. Aborting creation.', 'backwpup' ), E_USER_ERROR );
						return FALSE;
					}
				}
				$this->steps_data[ $this->step_working ]['on_file'] = '';
				$this->substeps_done ++;
			}
			$backup_archive->close();
			unset( $backup_archive );
			$this->log( __( 'Backup archive created.', 'backwpup' ), E_USER_NOTICE );
		} catch ( Exception $e ) {
			$this->log( $e->getMessage(), E_USER_ERROR, $e->getFile(), $e->getLine() );
			unset( $backup_archive );
			return FALSE;
		}

		$this->backup_filesize = filesize( $this->backup_folder . $this->backup_file );
		if ( $this->backup_filesize === FALSE ) {
			$this->backup_filesize = PHP_INT_MAX;
		}

		if ( $this->backup_filesize >= PHP_INT_MAX ) {
			$this->log( __( 'The Backup archive will be too large for file operations with this PHP Version. You might want to consider splitting the backup job in multiple jobs with less files each.', 'backwpup' ), E_USER_ERROR );
			$this->end();
		}
		else {
			$this->log( sprintf( __( 'Archive size is %s.', 'backwpup' ), size_format( $this->backup_filesize, 2 ) ), E_USER_NOTICE );
		}

		$this->log( sprintf( __( '%1$d Files with %2$s in Archive.', 'backwpup' ), $this->count_files, size_format( $this->count_files_size, 2 ) ), E_USER_NOTICE );

		return TRUE;
	}

	/**
	 * @param        $name
	 * @param string $suffix
	 * @param bool   $delete_temp_file
	 * @return string
	 */
	public function generate_filename( $name, $suffix = '', $delete_temp_file = TRUE ) {

		$local_time = current_time( 'timestamp' );

		$datevars   = array( '%d', '%j', '%m', '%n', '%Y', '%y', '%a', '%A', '%B', '%g', '%G', '%h', '%H', '%i', '%s' );
		$datevalues = array( date( 'd', $local_time ), date( 'j', $local_time ), date( 'm', $local_time ), date( 'n', $local_time ), date( 'Y', $local_time ), date( 'y', $local_time ), date( 'a', $local_time ), date( 'A', $local_time ), date( 'B', $local_time ), date( 'g', $local_time ), date( 'G', $local_time ), date( 'h', $local_time ), date( 'H', $local_time ), date( 'i', $local_time ), date( 's', $local_time ) );

		if ( ! empty( $suffix ) && substr( $suffix, 0, 1 ) != '.' ) {
			$suffix = '.' . $suffix;
		}

		$name = str_replace( $datevars, $datevalues, self::sanitize_file_name( $name ) );
		$name .= $suffix;
		if ( $delete_temp_file && is_writeable( BackWPup::get_plugin_data( 'TEMP' ) . $name ) && !is_dir( BackWPup::get_plugin_data( 'TEMP' ) . $name ) && !is_link( BackWPup::get_plugin_data( 'TEMP' ) . $name ) ) {
			unlink( BackWPup::get_plugin_data( 'TEMP' ) . $name );
		}

		return $name;
	}

	/**
	 * @param $filename
	 * @return bool
	 */
	public function is_backup_archive( $filename ) {

		$filename = basename( $filename );

		if ( ! substr( $filename, -3 ) == '.gz' ||  ! substr( $filename, -4 ) == '.bz2' ||  ! substr( $filename, -4 ) == '.tar' ||  ! substr( $filename, -4 ) == '.zip' )
			return FALSE;

		$filename = str_replace( array( '.gz', '.bz2', '.tar', '.zip' ), '', $filename );

		$datevars  = array( '%d', '%j', '%m', '%n', '%Y', '%y', '%a', '%A', '%B', '%g', '%G', '%h', '%H', '%i', '%s' );
		$dateregex = array( '(0[1-9]|[12][0-9]|3[01])', '([1-9]|[12][0-9]|3[01])', '(0[1-9]|1[012])', '([1-9]|1[012])', '((19|20|21)[0-9]{2})', '([0-9]{2})', '(am|pm)', '(AM|PM)', '([0-9]{3})', '([1-9]|1[012])', '([0-9]|1[0-9]|2[0-3])', '(0[1-9]|1[012])', '(0[0-9]|1[0-9]|2[0-3])', '([0-5][0-9])', '([0-5][0-9])' );

		$regex = "/^" . str_replace( $datevars, $dateregex, self::sanitize_file_name( $this->job[ 'archivename' ] ) ) . "$/";

		preg_match( $regex, $filename, $matches );
		if ( ! empty( $matches[ 0 ] ) && $matches[ 0 ] == $filename )
			return TRUE;

		return FALSE;
	}

	/**
	 * Sanitizes a filename, replacing whitespace with underscores.
	 *
	 * @param $filename
	 *
	 * @return mixed
	 */
	public static function sanitize_file_name( $filename ) {

		$filename = trim( $filename );

		$special_chars = array( "?", "[", "]", "/", "\\", "=", "<", ">", ":", ";", ",", "'", "\"", "&", "$", "#", "*", "(", ")", "|", "~", "`", "!", "{", "}", chr(0) );

		$filename = str_replace( $special_chars, '', $filename );

		$filename = str_replace( array( ' ', '%20', '+' ), '_', $filename );
		$filename = str_replace( array( "\n", "\t", "\r" ), '-', $filename );
		$filename = trim( $filename, '.-_' );

		return $filename;
	}

	/**
	 * Get the Process id of working script
	 *
	 * @return int
	 */
	private static function get_pid( ) {

		if  ( function_exists( 'posix_getpid' ) ) {

			return posix_getpid();
		} elseif ( function_exists( 'getmypid' ) ) {

			return getmypid();
		}

		return -1;
	}

	/**
	 * For storing and getting data in/from a extra temp file
	 *
	 * @param 	string $storage The name of the storage
	 * @param  	array  $data data to save in storage
	 * @return 	array|mixed|null data from storage
	 */
	public function data_storage( $storage = NULL, $data = NULL ) {

		if ( empty( $storage ) )
			return $data;

		$storage = strtolower( $storage );

		$file = BackWPup::get_plugin_data( 'temp' ) . 'backwpup-' . BackWPup::get_plugin_data( 'hash' ) . '-' . $storage . '.json';

		if ( ! empty( $data ) ) {
			file_put_contents( $file, json_encode( $data ) );
		}
		elseif ( is_readable( $file ) ) {
			$json = file_get_contents( $file );
			$data = json_decode( $json, TRUE );
		}

		return $data;
	}

	/**
	 * Get list of Folder for backup
	 *
	 * @return array folder list
	 */
	public function get_folders_to_backup( ) {

		$file = BackWPup::get_plugin_data( 'temp' ) . 'backwpup-' . BackWPup::get_plugin_data( 'hash' ) . '-folder.php';

		if ( ! file_exists( $file ) ) {
			return array();
		}

		$folders = array();

		$file_data = file( $file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );

		foreach( $file_data as $folder ) {
			$folder = trim( str_replace( array( '<?php', '//' ), '', $folder ) );
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
	 * Add a Folders to Folder list that should be backup
	 *
	 * @param array $folders folder to add
	 * @param bool  $new overwrite existing file
	 */
	public function add_folders_to_backup( $folders = array(), $new = FALSE ) {

		if ( ! is_array( $folders ) )
			$folders = (array) $folders;

		$file = BackWPup::get_plugin_data( 'temp' ) . 'backwpup-' . BackWPup::get_plugin_data( 'hash' ) . '-folder.php';

		if ( ! file_exists( $file ) || $new ) {
			file_put_contents( $file, '<?php' . PHP_EOL );
		}

		$content = '';
		foreach ( $folders AS $folder ) {
			$content .= '//' . $folder . PHP_EOL;
		}

		if ( ! empty( $content ) )
			file_put_contents( $file, $content, FILE_APPEND );
	}

	/**
	 * Check whether exec has been disabled.
	 *
	 * @access public
	 * @static
	 * @return bool
	 */
	public static function is_exec() {

		// Is function avail
		if ( ! function_exists( 'exec' ) ) {
			return FALSE;
		}

		// Is shell_exec disabled?
		if ( in_array( 'exec', array_map( 'trim', explode( ',', @ini_get( 'disable_functions' ) ) ) ) ) {
			return FALSE;
		}

		// Can we issue a simple echo command?
		$output = exec( 'echo backwpupechotest' );
		if ( $output != 'backwpupechotest' ) {
			return FALSE;
		}

		return TRUE;

	}

	/**
	 * Cleanup Temp Folder
	 */
	public static function clean_temp_folder() {

		$temp_dir = BackWPup::get_plugin_data( 'TEMP' );
		$do_not_delete_files = array( '.htaccess', 'index.php', '.', '..', '.donotbackup' );

		if ( is_writable( $temp_dir ) && $dir = opendir( $temp_dir ) ) {
			while ( FALSE !== ( $file = readdir( $dir ) ) ) {
				if ( in_array( $file, $do_not_delete_files ) || is_dir( $temp_dir . $file ) || is_link( $temp_dir . $file ) ) {
					continue;
				}
				if ( is_writeable( $temp_dir . $file ) ) {
					unlink( $temp_dir . $file );
				}
			}
			closedir( $dir );
		}
	}

	/**
	 * Is debug log active
	 *
	 * @return bool
	 */
	public function is_debug() {

		return strstr( $this->log_level, 'debug' ) ? TRUE : FALSE;
	}

	/**
	 * Change path of a given path
	 * for better storing in archives or on sync destinations
	 *
	 * @param $path string path to change to wp default path
	 *
	 * @return string
	 */
	public function get_destination_path_replacement( $path ) {

		$path = str_replace( '\\', '/', $path );

		$abs_path = realpath( ABSPATH );
		if ( $this->job[ 'backupabsfolderup' ] ) {
			$abs_path = dirname( $abs_path );
		}

		$abs_path = trailingslashit( str_replace( '\\', '/', $abs_path ) );

		$path = str_replace( $abs_path, '/', $path );

		return $path;
	}

}
