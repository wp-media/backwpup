<?php

use Base32\Base32;

/**
 * Class for options.
 */
final class BackWPup_Option
{
    /**
     * add filter for Site option defaults.
	 */
	public static function default_site_options() {
		// global.
		add_site_option( 'backwpup_version', '0.0.0' );
		// job default.
		add_site_option( 'backwpup_jobs', [] );
		// general.
		add_site_option( 'backwpup_cfg_showadminbar', true );
		add_site_option( 'backwpup_cfg_showfoldersize', false );
		add_site_option( 'backwpup_cfg_protectfolders', true );
		add_site_option( 'backwpup_cfg_keepplugindata', false );
		// job.
		add_site_option( 'backwpup_cfg_jobmaxexecutiontime', 30 );
		add_site_option( 'backwpup_cfg_jobstepretry', 3 );
		add_site_option( 'backwpup_cfg_jobrunauthkey', BackWPup::get_generated_hash( 8 ) );
		add_site_option( 'backwpup_cfg_loglevel', 'normal_translated' );
		add_site_option( 'backwpup_cfg_jobwaittimems', 0 );
		add_site_option( 'backwpup_cfg_jobdooutput', 0 );
		add_site_option( 'backwpup_cfg_windows', 0 );
		// Logs.
		add_site_option( 'backwpup_cfg_maxlogs', 30 );
		add_site_option( 'backwpup_cfg_gzlogs', 0 );
		$upload_dir   = wp_upload_dir( null, false, true );
		$logs_dir     = trailingslashit(
			str_replace(
			'\\',
            '/',
			$upload_dir['basedir']
		)
			) . 'backwpup/' . BackWPup::get_plugin_data( 'hash' ) . '/logs/';
		$content_path = trailingslashit( str_replace( '\\', '/', (string) WP_CONTENT_DIR ) );
		$logs_dir     = str_replace( $content_path, '', $logs_dir );
		add_site_option( 'backwpup_cfg_logfolder', $logs_dir );
		// Network Auth.
		add_site_option( 'backwpup_cfg_httpauthuser', '' );
		add_site_option( 'backwpup_cfg_httpauthpassword', '' );
		// Network plugin activation time.
		add_site_option( 'backwpup_activation_time', time() );
	}

	/**
	 * Update a BackWPup option.
	 *
	 * @param int    $jobid  The job ID to update.
	 * @param string $option Option key.
	 * @param mixed  $value  The value to store.
	 *
	 * @return bool True if the option was successfully updated, false otherwise.
	 */
	public static function update( $jobid, $option, $value ) {
		$jobid  = (int) $jobid;
		$option = sanitize_key( trim( $option ) );

		if (empty($jobid) || empty($option)) {
			return false;
		}

		$jobs_options = get_site_option( 'backwpup_jobs', [] );

		$jobids    = array_column( $jobs_options, 'jobid' );
		$job_keys  = array_keys( $jobs_options );
		$job_index = array_search( $jobid, $jobids, true );

		if ( false !== $job_index ) {
			$job_key = $job_keys[ $job_index ];
		} else {
			// Prevent collision with existing keys.
			$job_key                  = empty( $job_keys ) ? 0 : max( $job_keys ) + 1;
			$jobs_options[ $job_key ] = [
				'jobid' => $jobid,
			];
		}

		$jobs_options[ $job_key ][ $option ] = $value;

		return self::update_jobs_options($jobs_options);
	}

	/**
	 * Update the job ID for a BackWPup job.
	 *
	 * @param int $old_id The existing job ID.
	 * @param int $new_id The new job ID.
	 *
	 * @return bool True if the update was successful, false otherwise.
	 */
	public static function update_job_id( $old_id, $new_id ) {
		$old_id = (int) $old_id;
		$new_id = (int) $new_id;

		if ( $old_id <= 0 || $new_id <= 0 || $old_id === $new_id ) {
			return false;
		}

		// Fetch existing jobs.
		$jobs_options = self::jobs_options( false );

		if ( ! isset( $jobs_options[ $old_id ] ) ) {
			return false;
		}

		if ( isset( $jobs_options[ $new_id ] ) ) {
			return false;
		}

		// Update job ID: Move old job options to new ID.
		$jobs_options[ $new_id ] = $jobs_options[ $old_id ];
		unset( $jobs_options[ $old_id ] );
		$jobs_options[ $new_id ]['jobid'] = $new_id;
		// Save updated jobs options.
		if ( self::update_jobs_options( $jobs_options ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Load BackWPup Options.
	 *
	 * @param bool $use_cache
	 *
	 * @return array of options
	 */
	private static function jobs_options($use_cache = true)
	{
		global $current_site;

		//remove from cache
		if (!$use_cache) {
			if (is_multisite()) {
				$network_id = $current_site->id;
				$cache_key = "{$network_id}:backwpup_jobs";
				wp_cache_delete($cache_key, 'site-options');
			} else {
				wp_cache_delete('backwpup_jobs', 'options');
				$alloptions = wp_cache_get('alloptions', 'options');
				if (isset($alloptions['backwpup_jobs'])) {
					unset($alloptions['backwpup_jobs']);
					wp_cache_set('alloptions', $alloptions, 'options');
				}
			}
		}

		return get_site_option('backwpup_jobs', []);
	}

	/**
	 * Update BackWPup Options.
	 *
	 * @param array $options The options array to save
	 *
	 * @return bool updated or not
	 */
	private static function update_jobs_options($options)
	{
		return update_site_option('backwpup_jobs', $options);
	}

	/**
	 * Get a BackWPup Option.
	 *
	 * @param int    $jobid     The job ID to retrieve the option from.
	 * @param string $option    The option key.
	 * @param mixed  $default   The default value to return if the option is not found.
	 * @param bool   $use_cache Whether to use the cache.
	 *
	 * @return bool|mixed False if nothing can be retrieved, else the option value.
	 */
	public static function get( $jobid, $option, $default = null, $use_cache = true ) {
		$jobid  = (int) $jobid;
		$option = sanitize_key( trim( $option ) );

		if (empty($jobid) || empty($option)) {
			return false;
		}

		$jobs_options = self::jobs_options($use_cache);

		$jobids    = array_column( $jobs_options, 'jobid' );
		$job_keys  = array_keys( $jobs_options );
		$job_index = array_search( $jobid, $jobids, true );

		if ( false === $job_index ) {
			return $default;
		}

		$job_key = $job_keys[ $job_index ];

		// Handle archive name normalization if needed.
		if ( isset( $jobs_options[ $job_key ]['archivename'] ) ) {
			$jobs_options[ $job_key ]['archivenamenohash'] = $jobs_options[ $job_key ]['archivename'];
		}

		// Return default if the option does not exist.
		if ( ! isset( $jobs_options[ $job_key ][ $option ] ) ) {
			if ( null !== $default ) {
				return $default;
			}

			if ( 'archivename' === $option ) {
				return self::normalize_archive_name( self::defaults_job( $option ), $jobid );
			}

			return self::defaults_job($option);
		}

		// Ensure archive name is formatted properly.
		if ( 'archivename' === $option ) {
			return self::normalize_archive_name( $jobs_options[ $job_key ][ $option ], $jobid, true );
		}

		if ( 'archivenamenohash' === $option ) {
			return self::normalize_archive_name( $jobs_options[ $job_key ]['archivename'], $jobid, false );
		}

		$option_value = $jobs_options[ $job_key ][ $option ];

		// Handle special cases for option values.
		switch ( $option ) {
			case 'archiveformat':
				if ($option_value === '.tar.bz2') {
					$option_value = '.tar.gz';
				}
				break;

			case 'pluginlistfilecompression':
			case 'wpexportfilecompression':
				if ($option_value === '.bz2') {
					$option_value = '.gz';
				}
				break;
		}

		return $option_value;
	}

	/**
	 * Get default option for BackWPup option.
	 *
	 * @param string $key Option key
	 *
	 * @internal param int $id The job id
	 *
	 * @return bool|mixed
	 */
	public static function defaults_job($key = '')
	{
		$key = sanitize_key(trim($key));

		// set defaults.
		$default                          = [];
		$default['type']                  = [ 'DBDUMP', 'FILE', 'WPPLUGIN' ];
		$default['destinations']          = [
			'FOLDER',
		];
		$default['name']                  = __( 'New Job', 'backwpup' );
		$default['activetype']            = 'wpcron';
		$default['logfile']               = '';
		$default['lastbackupdownloadurl'] = '';
		$default['cronselect']            = 'basic';
		$default['cron']                  = '0 0 1 * *';
		$default['frequency']             = 'monthly';
		$default['mailaddresslog']        = sanitize_email( get_bloginfo( 'admin_email' ) );
		$default['mailaddresssenderlog']  = 'BackWPup ' . get_bloginfo( 'name' ) . ' <' . sanitize_email( get_bloginfo( 'admin_email' ) ) . '>';
		$default['mailerroronly']         = true;
		$default['backuptype']            = 'archive';
		$default['archiveformat']         = '.tar';
		$default['archivename']           = '%Y-%m-%d_%H-%i-%s_%hash%';
		$default['archivenamenohash']     = '%Y-%m-%d_%H-%i-%s_%hash%';
		$default['legacy']                = false;
		$default['archiveencryption']     = false;
		$default['tempjob']               = false;
		$default['backup_now']            = false;
		// defaults vor destinations.
		foreach ( BackWPup::get_registered_destinations() as $dest_key => $dest ) {
			if ( ! empty( $dest['class'] ) ) {
				$dest_object = BackWPup::get_destination( $dest_key );
				$default     = array_merge( $default, $dest_object->option_defaults() );
			}
		}
		// defaults vor job types.
		foreach ( BackWPup::get_job_types() as $job_type ) {
			$default = array_merge( $default, $job_type->option_defaults() );
		}

		// return all.
		if ( empty( $key ) ) {
			return $default;
		}
		// return one default setting.
		if ( isset( $default[ $key ] ) ) {
			return $default[ $key ];
		}

		return false;
	}

	/**
	 * Get BackWPup Job Options.
	 *
	 * @param int  $id        The job ID.
	 * @param bool $use_cache Whether to use the cache.
	 *
	 * @return array|false Array of all job options if found, false otherwise.
	 */
	public static function get_job($id, $use_cache = true)
	{
		if (!is_numeric($id)) {
			return false;
		}

		$id = intval($id);
		$jobs_options = self::jobs_options($use_cache);

		// Find the correct job index based on "jobid".
		$job_index = array_search( $id, array_column( $jobs_options, 'jobid' ), true );

		if ( false === $job_index ) {
			return false; // Job ID not found.
		}

		$job_keys = array_keys( $jobs_options );
		$job_key  = $job_keys[ $job_index ];

		// Normalize archive name if it exists.
		if ( isset( $jobs_options[ $job_key ]['archivename'] ) ) {
			$jobs_options[ $job_key ]['archivename'] = self::normalize_archive_name(
				$jobs_options[ $job_key ]['archivename'],
				$id,
				true
			);
		}

		// Merge with default values.
		$options = wp_parse_args( $jobs_options[ $job_key ], self::defaults_job() );

		// Normalize compression format values.
		$compression_mappings = [
			'archiveformat'             => [ '.tar.bz2' => '.tar.gz' ],
			'pluginlistfilecompression' => [ '.bz2' => '.gz' ],
			'wpexportfilecompression'   => [ '.bz2' => '.gz' ],
		];

		foreach ( $compression_mappings as $key => $mapping ) {
			if ( isset( $options[ $key ] ) && array_key_exists( $options[ $key ], $mapping ) ) {
				$options[ $key ] = $mapping[ $options[ $key ] ];
			}
		}

		return $options;
	}

	/**
	 * Delete a BackWPup Option.
	 *
	 * @param int    $jobid  The job ID.
	 * @param string $option The option key to delete.
	 *
	 * @return bool True if deleted successfully, false otherwise.
	 */
	public static function delete($jobid, $option)
	{
		$jobid = (int) $jobid;
		$option = sanitize_key(trim($option));

		if (empty($jobid) || empty($option)) {
			return false;
		}

		// Get all jobs.
		$jobs_options = self::jobs_options( false );

		// Find the correct job index based on "jobid".
		$job_index = array_search( $jobid, array_column( $jobs_options, 'jobid' ), true );

		if ( false === $job_index ) {
			return false; // Job ID not found.
		}

		// If the option exists, delete it.
		if ( isset( $jobs_options[ $job_index ][ $option ] ) ) {
			unset( $jobs_options[ $job_index ][ $option ] );
			return self::update_jobs_options( $jobs_options );
		}

		return false; // Option did not exist.
	}

	/**
	 * Delete a BackWPup Job.
	 *
	 * @param int $id The job id
	 *
	 * @return bool deleted or not
	 */
	public static function delete_job($id)
	{
		if (!is_numeric($id)) {
			return false;
		}

		$id = intval($id);
		$jobs_options = self::jobs_options(false);

		// Filter out the job with the matching ID.
		$filtered_jobs = array_filter( $jobs_options, fn( $job ) => ( $job['jobid'] ?? null ) !== $id );

		// If nothing was removed, return false.
		if ( count( $filtered_jobs ) === count( $jobs_options ) ) {
			return false;
		}

		return self::update_jobs_options( $filtered_jobs );
	}

	/**
	 * Get job IDs optionally filtered by a specific option key and value.
	 *
	 * @param string|null $key   Option key to filter by, or null to get all job IDs.
	 * @param mixed       $value Expected value of the option.
	 *
	 * @return array List of job IDs.
	 */
	public static function get_job_ids( $key = null, $value = false ) {
		$key          = sanitize_key( trim( (string) $key ) );
		$jobs_options = self::jobs_options( false );

		if ( empty( $jobs_options ) ) {
			return [];
		}

		$job_ids = [];

		foreach ( $jobs_options as $job ) {
			if ( ! isset( $job['jobid'] ) ) {
				continue;
			}

			// No key filter? Return all job IDs.
			if ( empty( $key ) ) {
				$job_ids[] = (int) $job['jobid'];
				continue;
			}

			if ( isset( $job[ $key ] ) && $job[ $key ] == $value ) { // phpcs:ignore
				$job_ids[] = (int) $job['jobid'];
			}
		}

		sort( $job_ids );

		return $job_ids;
	}

	/**
	 * Gets the next available job id.
	 *
	 * @return int
	 */
	public static function next_job_id()
	{
		$ids = self::get_job_ids();
		sort($ids);

		return end($ids) + 1;
	}

	/**
	 * Normalizes the archive name.
	 *
	 * The archive name should include the hash to identify this site, and the job id to identify this job.
	 *
	 * This allows backup files belonging to this job to be tracked.
	 *
	 * @param string $archive_name
	 * @param int    $jobid
	 *
	 * @return string The normalized archive name
	 */
	public static function normalize_archive_name($archive_name, $jobid, $substitute_hash = true)
	{
		$hash = BackWPup::get_plugin_data('hash');
		$generated_hash = self::get_generated_hash($jobid);

		// Does the string contain %hash%?
		if (strpos($archive_name, '%hash%') !== false) {
			if ($substitute_hash == true) {
				return str_replace('%hash%', $generated_hash, $archive_name);
			}
			// Nothing needs to be done since we don't have to substitute it.
			return $archive_name;
		}
		// %hash% not included, so check for old style archive name pre-3.4.3
		// If name starts with 'backwpup', then we can try to parse
		if (substr($archive_name, 0, 8) == 'backwpup') {
			$parts = explode('_', $archive_name);

			// Decode hash part if hash not found (from 3.4.2)
			if (strpos($parts[1], $hash) === false) {
				$parts[1] = is_numeric($parts[1]) ? base_convert($parts[1], 36, 16) : $parts[1];
			}

			// Search again
			if (strpos($parts[1], $hash) !== false) {
				$parts[1] = '%hash%';
			} else {
				// Hash not included, so insert
				array_splice($parts, 1, 0, '%hash%');
			}
			$archive_name = implode('_', $parts);
			if ($substitute_hash == true) {
				return str_replace('%hash%', $generated_hash, $archive_name);
			}

			return $archive_name;
		}
		// But otherwise, just append the hash
		if ($substitute_hash == true) {
			return $archive_name . '_' . $generated_hash;
		}

		return $archive_name . '_%hash%';
	}

	/**
	 * Generate a hash including random bytes and job ID.
	 *
	 * @return string
	 */
	public static function get_generated_hash( $jobid ) {
		return Base32::encode(
			pack(
				'H*',
				sprintf(
					'%02x%06s%02x',
					random_int( 0, 255 ),
					BackWPup::get_plugin_data( 'hash' ),
					random_int( 0, 255 )
				)
			)
			) .
			sprintf( '%02d', $jobid );
	}

	/**
	 * Return the decoded hash and the job ID.
	 *
	 * If the hash is not found in the given code, then false is returned.
	 *
	 * @param string $code The string to decode
	 *
	 * @return array|bool An array with hash and job ID, or false otherwise
	 */
	public static function decode_hash($code)
	{
		$hash = BackWPup::get_plugin_data('hash');

		// Try base 32 first
		$decoded = bin2hex(Base32::decode(substr($code, 0, 8)));

		if (substr($decoded, 2, 6) == $hash) {
			return [substr($decoded, 2, 6), intval(substr($code, -2))];
		}

		// Try base 36
		$decoded = is_numeric($code) ? base_convert($code, 36, 16) : $code;
		if (substr($decoded, 2, 6) == $hash) {
			return [substr($decoded, 2, 6), intval(substr($decoded, -2))];
		}

		// Check style prior to 3.4.1
		if (substr($code, 0, 6) == $hash) {
			return [substr($code, 0, 6), intval(substr($code, -2))];
		}

		// Tried everything, now return failure
		return false;
	}

	/**
	 * Substitute date variables in archive name.
	 *
	 * @param string $archivename the name of the archive
	 *
	 * @return string the archive name with substituted variables
	 */
	public static function substitute_date_vars($archivename)
	{
		$current_time = current_time('timestamp');
		$datevars = [
			'%d',
			'%j',
			'%m',
			'%n',
			'%Y',
			'%y',
			'%a',
			'%A',
			'%B',
			'%g',
			'%G',
			'%h',
			'%H',
			'%i',
			'%s',
		];
		$datevalues = [
			date('d', $current_time),
			date('j', $current_time),
			date('m', $current_time),
			date('n', $current_time),
			date('Y', $current_time),
			date('y', $current_time),
			date('a', $current_time),
			date('A', $current_time),
			date('B', $current_time),
			date('g', $current_time),
			date('G', $current_time),
			date('h', $current_time),
			date('H', $current_time),
			date('i', $current_time),
			date('s', $current_time),
		];
		// Temporarily replace %hash% with [hash]
		$archivename = str_replace('%hash%', '[hash]', $archivename);
		$archivename = str_replace(
			$datevars,
			$datevalues,
			$archivename
		);
		$archivename = str_replace('[hash]', '%hash%', $archivename);

		return BackWPup_Job::sanitize_file_name($archivename);
	}

	/**
	 * Creates a default job with the specified name and type.
	 *
	 * This method initializes a new job using default settings, assigns it a unique job ID,
	 * and updates the job's properties with the provided name and type. The job is then
	 * saved using the `self::update` method.
	 *
	 * @param string $job_name The name of the job to be created.
	 * @param array  $job_type An array specifying the type(s) of the job.
	 *
	 * @return int The ID of the newly created job.
	 */
	public static function create_default_jobs( string $job_name, array $job_type ) {
		$job          = self::defaults_job();
		$next_jobid   = self::next_job_id();
		$job['jobid'] = $next_jobid;
		$job['type']  = $job_type;
		$job['name']  = $job_name;

		foreach ( $job as $key => $value ) {
			self::update( $next_jobid, $key, $value );
		}
		return $next_jobid;
	}

	/**
	 * Get structure of default jobs.
	 *
	 * @return array
	 */
	public static function get_default_jobs() {
		$job_file          = self::defaults_job();
		$job_file['jobid'] = (int) get_site_option( 'backwpup_backup_files_job_id', 1 );
		$job_file['type']  = BackWPup_JobTypes::$type_job_files;
		$job_file['name']  = BackWPup_JobTypes::$name_job_files;

		$job_db          = self::defaults_job();
		$job_db['jobid'] = (int) get_site_option( 'backwpup_backup_database_job_id', 2 );
		$job_db['type']  = BackWPup_JobTypes::$type_job_database;
		$job_db['name']  = BackWPup_JobTypes::$name_job_database;

		return [ $job_file, $job_db ];
	}
}
