<?php
/**
 * Class for options
 */
final class BackWPup_Option {

	/**
	 *
	 * Load BackWPup Options
	 *
	 * @param bool $use_cache
	 * @return array of options
	 */
	private static function jobs_options( $use_cache = TRUE ) {

		//disable cache for non multisite
		if ( ! is_multisite() && ! $use_cache ) {
			//remove from options cache
			wp_cache_delete( 'backwpup_jobs' , 'options' );
			//remove from all options
			$alloptions = wp_cache_get( 'alloptions', 'options' );
			if ( isset( $alloptions[ 'backwpup_jobs' ] )) {
				unset( $alloptions[ 'backwpup_jobs' ] );
				wp_cache_set('alloptions', $alloptions, 'options');
			}
		}

		return get_site_option( 'backwpup_jobs', array( ), $use_cache );
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
	 * @param string $group Option group
	 * @param string $key   Option key
	 *
	 * @return bool|mixed
	 */
	public static function defaults( $group, $key ) {

		$group = sanitize_key( trim( $group ) );
		$key   = sanitize_key( trim( $key ) );

		$upload_dir = wp_upload_dir();
		$default[ 'cfg' ] = array();
		//set defaults
		if ( $group == 'cfg' ) { //for settings
			//generel
			$default[ 'cfg' ][ 'showadminbar' ]      = TRUE;
			$default[ 'cfg' ][ 'showfoldersize' ]    = FALSE;
			$default[ 'cfg' ][ 'protectfolders' ]    = TRUE;
			//job
			$default[ 'cfg' ][ 'jobmaxexecutiontime' ] = 0;
			$default[ 'cfg' ][ 'jobziparchivemethod' ] = '';
			$default[ 'cfg' ][ 'jobstepretry' ]      = 3;
			$default[ 'cfg' ][ 'jobsteprestart' ]    = FALSE;
			$default[ 'cfg' ][ 'jobrunauthkey' ]     = substr( md5( md5( SECURE_AUTH_KEY ) ), 11, 8 );
			$default[ 'cfg' ][ 'jobnotranslate' ] 	 = FALSE;
			$default[ 'cfg' ][ 'jobwaittimems' ] 	 = 0;
			//Logs
			$default[ 'cfg' ][ 'maxlogs' ]           = 30;
			$default[ 'cfg' ][ 'gzlogs' ]            = FALSE;
			$default[ 'cfg' ][ 'logfolder' ]         = trailingslashit( str_replace( '\\', '/',$upload_dir[ 'basedir' ] ) ) . 'backwpup-' . substr( md5( md5( SECURE_AUTH_KEY ) ), 9, 5 ) . '-logs/';
			//Network Auth
			$default[ 'cfg' ][ 'httpauthuser' ]      = '';
			$default[ 'cfg' ][ 'httpauthpassword' ]  = '';
			//API Keys
			$default[ 'cfg' ][ 'dropboxappkey' ]     = base64_decode( 'dHZkcjk1MnRhZnM1NmZ2' );
			$default[ 'cfg' ][ 'dropboxappsecret' ]  = base64_decode( 'OWV2bDR5MHJvZ2RlYmx1' );
			$default[ 'cfg' ][ 'dropboxsandboxappkey' ]     = base64_decode( 'cHVrZmp1a3JoZHR5OTFk' );
			$default[ 'cfg' ][ 'dropboxsandboxappsecret' ]  = base64_decode( 'eGNoYzhxdTk5eHE0eWdq' );
			$default[ 'cfg' ][ 'sugarsynckey' ]     = base64_decode( 'TlRBek1EY3lOakV6TkRrMk1URXhNemM0TWpJ' );
			$default[ 'cfg' ][ 'sugarsyncsecret' ]  = base64_decode( 'TkRFd01UazRNVEpqTW1Ga05EaG1NR0k1TVRFNFpqa3lPR1V6WlRVMk1tTQ==' );
			$default[ 'cfg' ][ 'sugarsyncappid' ]   = '/sc/5030726/449_18207099';
		}
		//return defaults of main
		if ( empty( $key ) )
			return $default[ $group ];
		//return one default setting
		if ( isset( $default[ $group ][ $key ] ) )
			return $default[ $group ][ $key ];
		else
			return FALSE;
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
		$default[ 'archivename' ]    = 'backwpup_' . substr( md5( md5( SECURE_AUTH_KEY ) ), 15, 6 ). '_%Y-%m-%d_%H-%i-%s';
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
	 * @param string|int $group      Option group or the job id
	 * @param string     $key        Option key
	 * @param mixed      $value      the value to store
	 *
	 * @return bool if option save or not
	 */
	public static function update( $group, $key, $value ) {

		$group = sanitize_key( trim( $group ) );
		$key   = sanitize_key( trim( $key ) );

		if ( empty( $group ) || empty( $key ) || $group == 'jobs' )
			return FALSE;

		//Update option
		if ( is_numeric( $group ) ) { //update job option
			$jobs_options = self::jobs_options( FALSE );
			$group  = intval( $group );
			$jobs_options[ $group ][ $key ] = $value;
			return self::update_jobs_options( $jobs_options );
		}
		else {
			return update_site_option( 'backwpup_' . $group . '_' . $key , $value );
		}


	}


	/**
	 *
	 * Get a BackWPup Option
	 *
	 * @param string|int $group      Option group or the job id
	 * @param string     $key        Option key
	 * @param mixed      $default    returned if no value, if null the the default BackWPup option will get
	 * @param bool       $use_cache
	 *
	 * @return bool|mixed        false if nothing can get else the option value
	 */
	public static function get( $group, $key, $default = NULL, $use_cache = TRUE ) {

		$group = sanitize_key( trim( $group ) );
		$key   = sanitize_key( trim( $key ) );

		if ( empty( $group ) || empty( $key ) || $group == 'jobs' )
			return FALSE;

		if ( is_numeric( $group ) ) { //get job option
			$jobs_options = self::jobs_options( $use_cache );
			$group 		  = intval( $group );
			if ( ! isset( $jobs_options[ $group ][ $key ] ) && isset( $default ) )
				return $default;
			elseif ( ! isset($jobs_options[ $group ][ $key ] ) )
				return self::defaults_job( $key );
			else
				return $jobs_options[ $group ][ $key ];
		}
		else {
			return get_site_option( 'backwpup_' . $group . '_' . $key , self::defaults( $group, $key ) , $use_cache );
		}

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
	 * @param string|int $group      Option group or the job id
	 * @param string     $key        Option key
	 *
	 * @return bool deleted or not
	 */
	public static function delete( $group, $key ) {

		$group = sanitize_key( trim( $group ) );
		$key   = sanitize_key( trim( $key ) );

		if ( empty( $group ) || empty( $key ) || $group == 'jobs' )
			return FALSE;

		//delete option
		if ( is_numeric( $group ) ) { //update job option
			$jobs_options = self::jobs_options( FALSE );
			$group = intval( $group );
			unset( $jobs_options[ $group ][ $key ] );
			return self::update_jobs_options( $jobs_options );
		}
		else {
			return delete_site_option( 'backwpup_' . $group . '_' . $key );
		}


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
