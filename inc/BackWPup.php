<?php

final class BackWPup {
	/**
	 * Plugin data
	 *
	 * @var array
	 */
	private static $plugin_data = [];

	/**
	 * BackWPup_Destinations
	 *
	 * @var array
	 */
	private static $destinations = [];

	/**
	 * Registered BackWPup_Destinations
	 *
	 * @var array
	 */
	private static $registered_destinations = [];

	/**
	 * BackWPup_JobTypes
	 *
	 * @var array
	 */
	private static $job_types = [];

	/**
	 * Is Pro
	 *
	 * @var bool
	 */
	private static $is_pro = false;

	/**
	 * Sets is pro
	 *
	 * @param bool $value True if pro, false if not.
	 *
	 * @return void
	 */
	public static function set_is_pro( $value ) {
		self::$is_pro = $value;
	}

	/**
	 * Check if the plugin is pro
	 *
	 * @return bool
	 */
	public static function is_pro() {
		return self::$is_pro;
	}

	/**
	 * Get information about the Plugin.
	 *
	 * @param string $name Name of info to get or NULL to get all.
	 *
	 * @return string|array
	 */
	public static function get_plugin_data( $name = null ) {
		if ( $name ) {
			$name = strtolower( trim( $name ) );
		}

		if ( empty( self::$plugin_data ) ) {
			self::$plugin_data         = get_file_data(
				BACKWPUP_PLUGIN_FILE,
				[
					'name'    => 'Plugin Name',
					'version' => 'Version',
				],
				'plugin'
			);
			self::$plugin_data['name'] = trim( self::$plugin_data['name'] );
			// set some extra vars.
			self::$plugin_data['basename']          = plugin_basename( BACKWPUP_PLUGIN_FILE );
			self::$plugin_data['mainfile']          = BACKWPUP_PLUGIN_FILE;
			self::$plugin_data['plugindir']         = untrailingslashit( dirname( BACKWPUP_PLUGIN_FILE ) );
			self::$plugin_data['pluginincdir']      = untrailingslashit( self::$plugin_data['plugindir'] . '/inc' );
			self::$plugin_data['plugin3rdpartydir'] = untrailingslashit( self::$plugin_data['pluginincdir'] . '/ThirdParty' );
			self::$plugin_data['hash']              = get_site_option( 'backwpup_cfg_hash' );
			if ( empty( self::$plugin_data['hash'] ) || strlen( self::$plugin_data['hash'] ) < 6
				|| strlen(
					self::$plugin_data['hash']
				) > 12 ) {
				self::$plugin_data['hash'] = self::get_generated_hash( 6 );
				update_site_option( 'backwpup_cfg_hash', self::$plugin_data['hash'] );
			}
			if ( defined( 'WP_TEMP_DIR' ) && is_dir( WP_TEMP_DIR ) ) {
				self::$plugin_data['temp'] = str_replace(
					'\\',
					'/',
					get_temp_dir()
				) . 'backwpup/' . self::$plugin_data['hash'] . '/';
			} else {
				$upload_dir                = wp_upload_dir();
				self::$plugin_data['temp'] = str_replace(
					'\\',
					'/',
					$upload_dir['basedir']
				) . '/backwpup/' . self::$plugin_data['hash'] . '/temp/';
			}
			self::$plugin_data['running_file'] = self::$plugin_data['temp'] . 'backwpup-working.php';
			self::$plugin_data['url']          = plugins_url( '', BACKWPUP_PLUGIN_FILE );
			self::$plugin_data['cacert']       = wpm_apply_filters_typed(
				'string',
				'backwpup_cacert_bundle',
				ABSPATH . WPINC . '/certificates/ca-bundle.crt'
			);
			// get unmodified WP Versions.
			include ABSPATH . WPINC . '/version.php';

			self::$plugin_data['wp_version'] = $wp_version;
			// Build User Agent.
			self::$plugin_data['user-agent'] = self::$plugin_data['name'] . '/' . self::$plugin_data['version'] . '; WordPress/' . self::$plugin_data['wp_version'] . '; ' . home_url();

			$activation_time = get_site_option( 'backwpup_activation_time' );
			if ( ! $activation_time ) {
				update_site_option( 'backwpup_activation_time', time() );
			}
			self::$plugin_data['activation_time'] = $activation_time;
		}

		if ( ! empty( $name ) ) {
			return self::$plugin_data[ $name ];
		}

		return self::$plugin_data;
	}

	/**
	 * Generates a random hash.
	 *
	 * @param int $length
	 *
	 * @return string
	 */
	public static function get_generated_hash( $length = 6 ) {
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

		$hash = '';

		for ( $i = 0; $i < 254; ++$i ) {
			$hash .= $chars[ random_int( 0, 61 ) ];
		}

		return substr( md5( $hash ), random_int( 0, 31 - $length ), $length );
	}

	/**
	 * Get a array of instances for Backup Destination's.
	 *
	 * @param string $key Key of Destination where get class instance from.
	 *
	 * @return array BackWPup_Destinations
	 */
	public static function get_destination( $key ) {
		$key = strtoupper( $key );

		if ( isset( self::$destinations[ $key ] ) && is_object( self::$destinations[ $key ] ) ) {
			return self::$destinations[ $key ];
		}

		$reg_dests = self::get_registered_destinations();
		if ( ! empty( $reg_dests[ $key ]['class'] ) ) {
			self::$destinations[ $key ] = new $reg_dests[ $key ]['class']();
		} else {
			return null;
		}

		return self::$destinations[ $key ];
	}

	/**
	 * Get a array of registered Destination's for Backups.
	 *
	 * @return array BackWPup_Destinations
	 */
	public static function get_registered_destinations() {
		// only run it one time.
		if ( ! empty( self::$registered_destinations ) ) {
			return self::$registered_destinations;
		}

		// add BackWPup Destinations.
		// to folder.
		self::$registered_destinations['FOLDER'] = [
			'class'    => \BackWPup_Destination_Folder::class,
			'info'     => [
				'ID'          => 'FOLDER',
				'name'        => __( 'Folder', 'backwpup' ),
				'description' => __( 'Backup to Folder', 'backwpup' ),
			],
			'can_sync' => false,
			'needed'   => [
				'php_version' => '',
				'functions'   => [],
				'classes'     => [],
			],
		];
		// backup with mail.
		self::$registered_destinations['EMAIL'] = [
			'class'    => \BackWPup_Destination_Email::class,
			'info'     => [
				'ID'          => 'EMAIL',
				'name'        => __( 'Email', 'backwpup' ),
				'description' => __( 'Backup sent via email', 'backwpup' ),
			],
			'can_sync' => false,
			'needed'   => [
				'php_version' => '',
				'functions'   => [],
				'classes'     => [],
			],
		];
		// backup to ftp.
		self::$registered_destinations['FTP'] = [
			'class'    => \BackWPup_Destination_Ftp::class,
			'info'     => [
				'ID'          => 'FTP',
				'name'        => __( 'FTP', 'backwpup' ),
				'description' => __( 'Backup to FTP', 'backwpup' ),
			],
			'can_sync' => false,
			'needed'   => [
				'php_version' => '',
				'functions'   => [ 'ftp_nb_fput' ],
				'classes'     => [],
			],
		];
		// backup to dropbox.
		self::$registered_destinations['DROPBOX'] = [
			'class'    => \BackWPup_Destination_Dropbox::class,
			'info'     => [
				'ID'          => 'DROPBOX',
				'name'        => __( 'Dropbox', 'backwpup' ),
				'description' => __( 'Backup to Dropbox', 'backwpup' ),
			],
			'can_sync' => false,
			'needed'   => [
				'php_version' => '',
				'functions'   => [ 'curl_exec' ],
				'classes'     => [],
			],
		];
		// Backup to S3.
		self::$registered_destinations['S3'] = [
			'class'    => \BackWPup_Destination_S3::class,
			'info'     => [
				'ID'          => 'S3',
				'name'        => __( 'S3 Service', 'backwpup' ),
				'description' => __( 'Backup to an S3 Service', 'backwpup' ),
			],
			'can_sync' => false,
			'needed'   => [
				'php_version' => '',
				'functions'   => [ 'curl_exec' ],
				'classes'     => [ \XMLWriter::class ],
			],
		];
		// backup to MS Azure.
		self::$registered_destinations['MSAZURE'] = [
			'class'    => \BackWPup_Destination_MSAzure::class,
			'info'     => [
				'ID'          => 'MSAZURE',
				'name'        => __( 'MS Azure', 'backwpup' ),
				'description' => __( 'Backup to Microsoft Azure (Blob)', 'backwpup' ),
			],
			'can_sync' => false,
			'needed'   => [
				'php_version' => '5.6.0',
				'functions'   => [],
				'classes'     => [],
			],
		];
		// backup to Rackspace Cloud.
		self::$registered_destinations['RSC'] = [
			'class'    => \BackWPup_Destination_RSC::class,
			'info'     => [
				'ID'          => 'RSC',
				'name'        => __( 'RSC', 'backwpup' ),
				'description' => __( 'Backup to Rackspace Cloud Files', 'backwpup' ),
			],
			'can_sync' => false,
			'needed'   => [
				'php_version' => '',
				'functions'   => [ 'curl_exec' ],
				'classes'     => [],
			],
		];
		// backup to Sugarsync.
		self::$registered_destinations['SUGARSYNC'] = [
			'class'    => \BackWPup_Destination_SugarSync::class,
			'info'     => [
				'ID'          => 'SUGARSYNC',
				'name'        => __( 'SugarSync', 'backwpup' ),
				'description' => __( 'Backup to SugarSync', 'backwpup' ),
			],
			'can_sync' => false,
			'needed'   => [
				'php_version' => '',
				'functions'   => [ 'curl_exec' ],
				'classes'     => [],
			],
		];

		// Hook for adding Destinations like above.
		self::$registered_destinations = wpm_apply_filters_typed(
			'array',
			'backwpup_register_destination',
			self::$registered_destinations
		);

		// check BackWPup Destinations.
		foreach ( self::$registered_destinations as $dest_key => $dest ) {
			self::$registered_destinations[ $dest_key ]['error'] = '';
			// check PHP Version.
			if ( ! empty( $dest['needed']['php_version'] )
					&& version_compare(
						PHP_VERSION,
						$dest['needed']['php_version'],
						'<'
					) ) {
				self::$registered_destinations[ $dest_key ]['error'] .= sprintf(
					// translators: %1$s = current PHP version, %2$s = needed PHP version.
					__(
						'PHP Version %1$s is to low, you need Version %2$s or above.',
						'backwpup'
					),
					PHP_VERSION,
					$dest['needed']['php_version']
				) . ' ';
				self::$registered_destinations[ $dest_key ]['class'] = null;
			}
			// check functions exists.
			if ( ! empty( $dest['needed']['functions'] ) ) {
				foreach ( $dest['needed']['functions'] as $function_need ) {
					if ( ! function_exists( $function_need ) ) {
						self::$registered_destinations[ $dest_key ]['error'] .= sprintf(
							// translators: %s = function name.
							__(
								'Missing function "%s".',
								'backwpup'
							),
							$function_need
						) . ' ';
						self::$registered_destinations[ $dest_key ]['class'] = null;
					}
				}
			}
			// check classes exists.
			if ( ! empty( $dest['needed']['classes'] ) ) {
				foreach ( $dest['needed']['classes'] as $class_need ) {
					if ( ! class_exists( $class_need ) ) {
						self::$registered_destinations[ $dest_key ]['error'] .= sprintf(
							// translators: %s = class name.
							__(
								'Missing class "%s".',
								'backwpup'
							),
							$class_need
						) . ' ';
						self::$registered_destinations[ $dest_key ]['class'] = null;
					}
				}
			}
		}

		return self::$registered_destinations;
	}

	/**
	 * Gets a array of instances from Job types.
	 *
	 * @return array BackWPup_JobTypes
	 */
	public static function get_job_types() {
		if ( ! empty( self::$job_types ) ) {
			return self::$job_types;
		}

		self::$job_types['DBDUMP']   = new BackWPup_JobType_DBDump();
		self::$job_types['FILE']     = new BackWPup_JobType_File();
		self::$job_types['WPEXP']    = new BackWPup_JobType_WPEXP();
		self::$job_types['WPPLUGIN'] = new BackWPup_JobType_WPPlugin();
		self::$job_types['DBCHECK']  = new BackWPup_JobType_DBCheck();

		self::$job_types = wpm_apply_filters_typed( 'array', 'backwpup_job_types', self::$job_types );

		// remove types can't load.
		foreach ( self::$job_types as $key => $job_type ) {
			if ( empty( $job_type ) || ! is_object( $job_type ) ) {
				unset( self::$job_types[ $key ] );
			}
		}

		return self::$job_types;
	}
}
