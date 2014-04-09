<?php
/**
 * Class for options
 */
final class BackWPup_Option {

	/**
	 *
	 * add filter for Site option defaults
	 *
	 */
	public static function default_site_options() {

		//global
		add_site_option( 'backwpup_version', '0.0.0' );
		//job default
		add_site_option( 'backwpup_jobs', array() );
		//general
		add_site_option( 'backwpup_cfg_showadminbar', 1 );
		add_site_option( 'backwpup_cfg_showfoldersize', 0 );
		add_site_option( 'backwpup_cfg_protectfolders', 1 );
		//job
		$max_execution_time = 0;
		if ( strstr( PHP_SAPI, 'cgi' ) ) {
			$max_execution_time = (int) ini_get( 'max_execution_time' );
		}
		add_site_option( 'backwpup_cfg_jobmaxexecutiontime', $max_execution_time );
		add_site_option( 'backwpup_cfg_jobziparchivemethod','' );
		add_site_option( 'backwpup_cfg_jobstepretry', 3 );
		add_site_option( 'backwpup_cfg_jobrunauthkey', substr( md5( BackWPup::get_plugin_data( 'hash' ) ), 11, 8 ) );
		add_site_option( 'backwpup_cfg_jobnotranslate', 0 );
		add_site_option( 'backwpup_cfg_jobwaittimems', 0 );
		//Logs
		add_site_option( 'backwpup_cfg_maxlogs', 30  );
		add_site_option( 'backwpup_cfg_gzlogs', 0 );
		$upload_dir = wp_upload_dir();
		$logs_dir = trailingslashit( str_replace( '\\', '/',$upload_dir[ 'basedir' ] ) ) . 'backwpup-' . BackWPup::get_plugin_data( 'hash' ) . '-logs/';
		add_site_option( 'backwpup_cfg_logfolder', $logs_dir );
		//Network Auth
		add_site_option( 'backwpup_cfg_httpauthuser','' );
		add_site_option( 'backwpup_cfg_httpauthpassword', '' );

	}


	/**
	 *
	 * Load BackWPup Options
	 *
	 * @param bool $use_cache
	 * @return array of options
	 */
	private static function jobs_options( $use_cache = TRUE ) {

		if ( $use_cache )
			return get_site_option( 'backwpup_jobs' );

		if ( is_multisite() )
			return get_site_option( 'backwpup_jobs', array(), FALSE );

		//remove from options cache
		wp_cache_delete( 'backwpup_jobs', 'options' );
		//remove from all options
		$alloptions = wp_cache_get( 'alloptions', 'options' );
		if ( isset( $alloptions[ 'backwpup_jobs' ] ) ) {
			unset( $alloptions[ 'backwpup_jobs' ] );
			wp_cache_set( 'alloptions', $alloptions, 'options' );
		}

		return get_option( 'backwpup_jobs', array() );
	}

	/**
	 *
	 * Update BackWPup Options
	 *
	 * @param array $options The options array to save
	 * @return bool updated or not
	 */
	private static function update_jobs_options( $options ) {

		return update_site_option( 'backwpup_jobs', $options );
	}


	/**
	 *
	 * Get default option for BackWPup option
	 *
	 * @param string $key  Option key
	 * @internal param int $id The job id
	 *
	 * @return bool|mixed
	 */
	public static function defaults_job( $key = '' ) {

		$key = sanitize_key( trim( $key ) );

		//set defaults
		$default[ 'type' ]           = array( 'DBDUMP', 'FILE', 'WPPLUGIN' );
		$default[ 'destinations' ]   = array();
		$default[ 'name' ]           = __( 'New Job', 'backwpup' );
		$default[ 'activetype' ]     = '';
		$default[ 'logfile' ]        = '';
		$default[ 'lastbackupdownloadurl' ] = '';
		$default[ 'cronselect' ]     = 'basic';
		$default[ 'cron' ]           = '0 3 * * *';
		$default[ 'mailaddresslog' ] = sanitize_email( get_bloginfo( 'admin_email' ) );
		$default[ 'mailaddresssenderlog' ] = 'BackWPup ' . get_bloginfo( 'name' ) . ' <' . sanitize_email( get_bloginfo( 'admin_email' ) ).'>';
		$default[ 'mailerroronly' ]  = TRUE;
		$default[ 'backuptype' ]     = 'archive';
		$default[ 'archiveformat' ] = '.tar.gz';
		$default[ 'archivename' ]    = 'backwpup_' . BackWPup::get_plugin_data( 'hash' ) . '_%Y-%m-%d_%H-%i-%s';
		//defaults vor destinations
		foreach ( BackWPup::get_registered_destinations() as $dest_key => $dest ) {
			if ( ! empty( $dest[ 'class' ] ) ) {
				$dest_object = BackWPup::get_destination( $dest_key );
				$default = array_merge( $default, $dest_object->option_defaults() );
			}
		}
		//defaults vor job types
		foreach ( BackWPup::get_job_types() as $job_type ) {
			$default = array_merge( $default, $job_type->option_defaults() );
		}

		//return all
		if ( empty( $key ) )
			return $default;
		//return one default setting
		if ( isset( $default[ $key ] ) )
			return $default[ $key ];
		else
			return FALSE;
	}

	/**
	 *
	 * Update a BackWPup option
	 *
	 * @param int 		 $jobid      the job id
	 * @param string     $option     Option key
	 * @param mixed      $value      the value to store
	 *
	 * @return bool if option save or not
	 */
	public static function update( $jobid, $option, $value ) {

		$jobid  = (int) $jobid;
		$option = sanitize_key( trim( $option ) );

		if ( empty( $jobid ) || empty( $option ) )
			return FALSE;

		//Update option
		$jobs_options = self::jobs_options( FALSE );
		$jobs_options[ $jobid ][ $option ] = $value;
		return self::update_jobs_options( $jobs_options );
	}


	/**
	 *
	 * Get a BackWPup Option
	 *
	 * @param int    $jobid   Option the job id
	 * @param string $option  Option key
	 * @param mixed  $default returned if no value, if null the the default BackWPup option will get
	 * @param bool   $use_cache USe the cache
	 * @return bool|mixed        false if nothing can get else the option value
	 */
	public static function get( $jobid, $option, $default = NULL, $use_cache = TRUE ) {

		$jobid  = (int) $jobid;
		$option = sanitize_key( trim( $option ) );

		if ( empty( $jobid ) || empty( $option ) )
			return FALSE;

		$jobs_options = self::jobs_options( $use_cache );
		if ( ! isset( $jobs_options[ $jobid ][ $option ] ) && isset( $default ) )
			return $default;
		elseif ( ! isset( $jobs_options[ $jobid ][ $option ] ) )
			return self::defaults_job( $option );
		else
			return $jobs_options[ $jobid ][ $option ];
	}

	/**
	 *
	 * BackWPup Job Options
	 *
	 * @param int  $id The job id
	 * @param bool $use_cache
	 *
	 * @return array  of all job options
	 */
	public static function get_job( $id, $use_cache = TRUE ) {

		if ( ! is_numeric( $id ) )
			return FALSE;

		$id      	  = intval( $id );
		$jobs_options = self::jobs_options( $use_cache );

		return wp_parse_args( $jobs_options[ $id ], self::defaults_job( ) );
	}


	/**
	 *
	 * Delete a BackWPup Option
	 *
	 * @param int 		 $jobid      the job id
	 * @param string     $option     Option key
	 *
	 * @return bool deleted or not
	 */
	public static function delete( $jobid, $option ) {

		$jobid  = (int) $jobid;
		$option = sanitize_key( trim( $option ) );

		if ( empty( $jobid ) || empty( $option ) )
			return FALSE;

		//delete option
		$jobs_options = self::jobs_options( FALSE );
		unset( $jobs_options[ $jobid ][ $option ] );
		return self::update_jobs_options( $jobs_options );


	}

	/**
	 *
	 * Delete a BackWPup Job
	 *
	 * @param int $id The job id
	 *
	 * @return bool   deleted or not
	 */
	public static function delete_job( $id ) {

		if ( ! is_numeric( $id ) )
			return FALSE;

		$id      	  = intval( $id );
		$jobs_options = self::jobs_options( FALSE );
		unset( $jobs_options[ $id ] );

		return self::update_jobs_options( $jobs_options );
	}

	/**
	 *
	 * get the id's of jobs
	 *
	 * @param string|null $key    Option key or null for getting all id's
	 * @param bool        $value  Value that the option must have to get the id
	 *
	 * @return array job id's
	 */
	public static function get_job_ids( $key = NULL, $value = FALSE ) {

		$key     	  = sanitize_key( trim( $key ) );
		$jobs_options = self::jobs_options( FALSE );

		if ( empty( $jobs_options ) )
			return array();

		//get option job ids
		if ( empty( $key ) )
			return array_keys( $jobs_options );

		//get option ids for option with the defined value
		$new_option_job_ids = array();
		foreach ( $jobs_options as $id => $option ) {
			if ( isset( $option[ $key ] ) && $value == $option[ $key ] )
				$new_option_job_ids[ ] = $id;
		}
		sort( $new_option_job_ids );

		return $new_option_job_ids;
	}
}
