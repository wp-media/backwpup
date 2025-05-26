<?php

/**
 * Class for BackWPup cron methods.
 */
class BackWPup_Cron
{
    /**
     * @param string $arg
     */
    public static function run($arg = 'restart')
    {
        if (!is_main_site(get_current_blog_id())) {
            return;
        }

        if ($arg === 'restart') {
            //reschedule restart
            wp_schedule_single_event(time() + 60, 'backwpup_cron', ['arg' => 'restart']);
            //restart job if not working or a restart imitated
            self::cron_active(['run' => 'restart']);

            return;
        }

        $arg = is_numeric($arg) ? abs((int) $arg) : 0;
        if (!$arg) {
            return;
        }

        //check that job exits
        $jobids = BackWPup_Option::get_job_ids('activetype', 'wpcron');
        if (!in_array($arg, $jobids, true)) {
            return;
        }

        //delay other job start for 5 minutes if already one is running
        $job_object = BackWPup_Job::get_working_data();
        if ($job_object) {
            wp_schedule_single_event(time() + 300, 'backwpup_cron', ['arg' => $arg]);

            return;
        }

        //reschedule next job run
        $cron_next = self::cron_next(BackWPup_Option::get($arg, 'cron'));
        wp_schedule_single_event($cron_next, 'backwpup_cron', ['arg' => $arg]);

        //start job
        self::cron_active([
            'run' => 'cronrun',
            'jobid' => $arg,
        ]);
    }

    /**
     * Check Jobs worked and Cleanup logs and so on.
     */
    public static function check_cleanup()
    {
        $job_object = BackWPup_Job::get_working_data();
        $log_folder = get_site_option('backwpup_cfg_logfolder');
        $log_folder = BackWPup_File::get_absolute_path($log_folder);

        // check aborted jobs for longer than a tow hours, abort them courtly and send mail
        if (is_object($job_object) && !empty($job_object->logfile)) {
            $not_worked_time = microtime(true) - $job_object->timestamp_last_update;
            if ($not_worked_time > 3600) {
                $job_object->log(
                    E_USER_ERROR,
                    __('Aborted, because no progress for one hour!', 'backwpup'),
                    __FILE__,
                    __LINE__
                );
                unlink(BackWPup::get_plugin_data('running_file'));
                $job_object->update_working_data();
            }
        }

		/**
		 * Filter whether BackWPup will compress logs or not.
		 *
		 * @param bool $log_compress Whether the logs will be compressed or not.
		 */
		$log_compress = wpm_apply_filters_typed(
			'boolean',
			'backwpup_gz_logs',
			(bool) get_site_option( 'backwpup_cfg_gzlogs' )
		);
		// Compress not compressed logs.
		if (
			is_readable( $log_folder ) &&
			function_exists( 'gzopen' ) &&
			$log_compress &&
			! is_object( $job_object )
		) {
			// Compress old not compressed logs.
			try {
                $dir = new BackWPup_Directory($log_folder);

                $jobids = BackWPup_Option::get_job_ids();

                foreach ($dir as $file) {
                    if ($file->isWritable() && '.html' == substr($file->getFilename(), -5)) {
                        $compress = new BackWPup_Create_Archive($file->getPathname() . '.gz');
                        if ($compress->add_file($file->getPathname())) {
                            unlink($file->getPathname());
                            //change last logfile in jobs
                            foreach ($jobids as $jobid) {
                                $job_logfile = BackWPup_Option::get($jobid, 'logfile');
                                if (!empty($job_logfile) && $job_logfile === $file->getPathname()) {
                                    BackWPup_Option::update($jobid, 'logfile', $file->getPathname() . '.gz');
                                }
                            }
                        }
                        $compress->close();
                        unset($compress);
                    }
                }
            } catch (UnexpectedValueException $e) {
                $job_object->log(
                    sprintf(__('Could not open path: %s', 'backwpup'), $e->getMessage()),
                    E_USER_WARNING
                );
            }
        }

        //Jobs cleanings
        if (!$job_object) {
            //remove restart cron
            wp_clear_scheduled_hook('backwpup_cron', ['arg' => 'restart']);
            //temp cleanup
            BackWPup_Job::clean_temp_folder();
        }

        //check scheduling jobs that not found will removed because there are single scheduled
        $activejobs = BackWPup_Option::get_job_ids('activetype', 'wpcron');

        foreach ($activejobs as $jobid) {
            $cron_next = wp_next_scheduled('backwpup_cron', ['arg' => $jobid]);
            if (!$cron_next || $cron_next < time()) {
                wp_unschedule_event($cron_next, 'backwpup_cron', ['arg' => $jobid]);
                $cron_next = BackWPup_Cron::cron_next(BackWPup_Option::get($jobid, 'cron'));
                wp_schedule_single_event($cron_next, 'backwpup_cron', ['arg' => $jobid]);
            }
        }
    }

    /**
     * Start job if in cron and run query args are set.
     */
    public static function cron_active($args = [])
    {
        //only if cron active
        if (!defined('DOING_CRON') || !DOING_CRON) {
            return;
        }

        if (!is_array($args)) {
            $args = [];
        }

        if (isset($_GET['backwpup_run'])) {
            $args['run'] = sanitize_text_field($_GET['backwpup_run']);
        }

        if (isset($_GET['_nonce'])) {
            $args['nonce'] = sanitize_text_field($_GET['_nonce']);
        }

        if (isset($_GET['jobid'])) {
            $args['jobid'] = absint($_GET['jobid']);
        }

        $args = array_merge(
            [
                'run' => '',
                'nonce' => '',
                'jobid' => 0,
            ],
            $args
        );

        if (!in_array(
            $args['run'],
            ['test', 'restart', 'runnow', 'runnowalt', 'runext', 'cronrun'],
            true
        )) {
            return;
        }

        //special header
        @session_write_close();
        @header('Content-Type: text/html; charset=' . get_bloginfo('charset'), true);
        @header('X-Robots-Tag: noindex, nofollow', true);
        nocache_headers();

        //on test die for fast feedback
        if ($args['run'] === 'test') {
            exit('BackWPup test request');
        }

        if ($args['run'] === 'restart') {
            $job_object = BackWPup_Job::get_working_data();
            // Restart if cannot find job
            if (!$job_object) {
                BackWPup_Job::start_http('restart');

                return;
            }
            //restart job if not working or a restart wished
            $not_worked_time = microtime(true) - $job_object->timestamp_last_update;
            if (!$job_object->pid || $not_worked_time > 300) {
                BackWPup_Job::start_http('restart');

                return;
            }
        }

        // generate normal nonce
        $nonce = substr(wp_hash(wp_nonce_tick() . 'backwpup_job_run-' . $args['run'], 'nonce'), -12, 10);
        //special nonce on external start
        if ($args['run'] === 'runext') {
            $nonce = get_site_option('backwpup_cfg_jobrunauthkey');
        }
        if ($args['run'] === 'cronrun') {
            $nonce = '';
        }
        // check nonce
        if ($nonce !== $args['nonce']) {
            return;
        }

		// check runext is allowed.
		if ( 'runext' === $args['run'] ) {
			$should_continue = in_array( BackWPup_Option::get( $args['jobid'], 'activetype', '' ), [ 'link', 'easycron' ], true );
			/**
			 * Filter whether BackWPup will allow to start a job with links or not.
			 *
			 * @param bool $enable Enable starting job with external link for type "link", default is true if the activetype is link or easycron.
			 * @param array $args Job args array.
			 */
			$should_continue = wpm_apply_filters_typed(
				'boolean',
				'backwpup_allow_job_start_with_links',
				$should_continue,
				$args
			);
			// If we should not continue, return early.
			if ( ! $should_continue ) {
				return;
            }
        }

        //run BackWPup job
        BackWPup_Job::start_http($args['run'], $args['jobid']);
    }

    /**
     * Get the local time timestamp of the next cron execution.
     *
     * @param string $cronstring cron (* * * * *)
     *
     * @return int Timestamp
     */
    public static function cron_next($cronstring)
    {
        $cronstr = [];
        $cron = [];
        $cronarray = [];
        //Cron string
        [$cronstr['minutes'], $cronstr['hours'], $cronstr['mday'], $cronstr['mon'], $cronstr['wday']] = explode(
            ' ',
            trim($cronstring),
            5
        );

        //make arrays form string
        foreach ($cronstr as $key => $value) {
            if (strstr($value, ',')) {
                $cronarray[$key] = explode(',', $value);
            } else {
                $cronarray[$key] = [0 => $value];
            }
        }

        //make arrays complete with ranges and steps
        foreach ($cronarray as $cronarraykey => $cronarrayvalue) {
            $cron[$cronarraykey] = [];

            foreach ($cronarrayvalue as $value) {
                //steps
                $step = 1;
                if (strstr($value, '/')) {
                    [$value, $step] = explode('/', $value, 2);
                }
                //replace weekday 7 with 0 for sundays
                if ($cronarraykey === 'wday') {
                    $value = str_replace('7', '0', $value);
                }
                //ranges
                if (strstr($value, '-')) {
                    [$first, $last] = explode('-', $value, 2);
                    if (!is_numeric($first) || !is_numeric($last) || $last > 60 || $first > 60) { //check
                        return PHP_INT_MAX;
                    }
                    if ($cronarraykey === 'minutes' && $step < 5) { //set step minimum to 5 min.
                        $step = 5;
                    }
                    $range = [];

                    for ($i = $first; $i <= $last; $i = $i + $step) {
                        $range[] = $i;
                    }
                    $cron[$cronarraykey] = array_merge($cron[$cronarraykey], $range);
                } elseif ($value === '*') {
                    $range = [];
                    if ($cronarraykey === 'minutes') {
                        if ($step < 10) { //set step minimum to 5 min.
                            $step = 10;
                        }

                        for ($i = 0; $i <= 59; $i = $i + $step) {
                            $range[] = $i;
                        }
                    }
                    if ($cronarraykey === 'hours') {
                        for ($i = 0; $i <= 23; $i = $i + $step) {
                            $range[] = $i;
                        }
                    }
                    if ($cronarraykey === 'mday') {
                        for ($i = $step; $i <= 31; $i = $i + $step) {
                            $range[] = $i;
                        }
                    }
                    if ($cronarraykey === 'mon') {
                        for ($i = $step; $i <= 12; $i = $i + $step) {
                            $range[] = $i;
                        }
                    }
                    if ($cronarraykey === 'wday') {
                        for ($i = 0; $i <= 6; $i = $i + $step) {
                            $range[] = $i;
                        }
                    }
                    $cron[$cronarraykey] = array_merge($cron[$cronarraykey], $range);
                } else {
                    if (!is_numeric($value) || (int) $value > 60) {
                        return PHP_INT_MAX;
                    }
                    $cron[$cronarraykey] = array_merge($cron[$cronarraykey], [0 => absint($value)]);
                }
            }
        }

        //generate years
        $year = (int) gmdate('Y');

        for ($i = $year; $i < $year + 100; ++$i) {
            $cron['year'][] = $i;
        }

		// Calc next timestamp.
		$current_time_object = current_datetime();
		$current_time        = $current_time_object->getTimestamp();

		foreach ( $cron['year'] as $year ) {
			foreach ( $cron['mon'] as $mon ) {
				foreach ( $cron['mday'] as $mday ) {
					// Get total days in a month.
					$days_in_month = cal_days_in_month( CAL_GREGORIAN, $mon, $year );
					/**
					 * Check if cron month day is greater than total days for that month
					 * and set month day to the total days for the new month.
					 */
					if ( $mday > $days_in_month ) {
						$mday           = $days_in_month;
						$cron['mday'][] = $mday;
					}

					if ( ! checkdate( $mon, $mday, $year ) ) {
						continue;
                    }

					foreach ( $cron['hours'] as $hours ) {
						foreach ( $cron['minutes'] as $minutes ) {
							$time      = sprintf(
								'%04d-%02d-%02d %02d:%02d:00',
								$year,
								$mon,
								$mday,
								$hours,
								$minutes
							);
							$date      = new DateTimeImmutable( $time, wp_timezone() );
							$timestamp = $date->getTimestamp();

							if ( $timestamp && in_array(
								(int) $date->format( 'j' ),
								$cron['mday'],
                                true
							) && in_array(
								(int) $date->format( 'w' ),
								$cron['wday'],
								true
							) && $timestamp > $current_time ) {
								/**
								 * Filters the next cron timestamp
								 *
								 * @param int $timestamp The next cron timestamp.
								 */
								return wpm_apply_filters_typed( 'integer', 'backwpup_cron_next', $timestamp );
							}
                        }
                    }
                }
            }
        }

        return PHP_INT_MAX;
    }

	/**
	 * Get the basic cron expression.
	 *
	 * @param string     $basic_expression Basic expression.
	 * @param int|string $hours Hours of the cron.
	 * @param int        $minutes Minutes of the cron.
	 * @param int        $day_of_week Day of the week default = 0.
	 * @param string     $day_of_month Day of the month.
	 *
	 * @return string Cron expression
	 * @throws InvalidArgumentException If the cron expression is unsupported.
	 */
	public static function get_basic_cron_expression( string $basic_expression, $hours = 0, int $minutes = 0, int $day_of_week = 0, string $day_of_month = '1' ): string {
		$cron = '';
		switch ( $basic_expression ) {
			case 'monthly':
				$cron = implode( ' ', [ $minutes, $hours, $day_of_month, '*', '*' ] );
				break;
			case 'weekly':
				$cron = implode( ' ', [ $minutes, $hours, '*', '*', $day_of_week ] );
				break;
			case 'daily':
				$cron = implode( ' ', [ $minutes, $hours, '*', '*', '*' ] );
				break;
			case 'hourly':
				$cron = implode( ' ', [ $minutes, '*', '*', '*', '*' ] );
				break;
		}
		return $cron;
	}

	/**
	 * Parse the cron expression to get the frequency and start time.
	 *
	 * @param string $cron_expression The cron expression.
	 *
	 * @return array An array containing the frequency and start time.
	 * @throws InvalidArgumentException If the cron expression is invalid or unsupported.
	 */
	public static function parse_cron_expression( string $cron_expression ): array {
		$parts = explode( ' ', $cron_expression );
		if ( count( $parts ) !== 5 ) {
			throw new InvalidArgumentException( 'Invalid cron expression' );
		}

		list($minutes, $hours, $day_of_month, $month, $day_of_week) = $parts;

		$montly_expr = [
			'1'   => [ '*' => 'first-day' ],
			'1-7' => [
				'1' => 'first-monday',
				'0' => 'first-sunday',
			],
		];

		$frequency         = '';
		$weekly_start_day  = '';
		$monthly_start_day = $day_of_month;
		$hourly_start_time = 0;

		if ( '*' !== $day_of_month && 0 < $day_of_month && '*' === $month && '*' === $day_of_week ) {
			$frequency = 'monthly';
		} elseif ( in_array( $day_of_month, array_keys( $montly_expr ) ) && '*' === $month && in_array( $day_of_week, array_keys( $montly_expr[ $day_of_month ] ) ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
			$frequency         = 'monthly';
			$monthly_start_day = $montly_expr[ $day_of_month ][ $day_of_week ];
		} elseif ( '*' === $day_of_month && '*' === $month && '*' !== $day_of_week ) {
			$frequency        = 'weekly';
			$weekly_start_day = (int) $day_of_week;
		} elseif ( '*' === $day_of_month && '*' === $month && '*' === $day_of_week && '*' !== $hours ) {
			$frequency = 'daily';
		} elseif ( '*' === $day_of_month && '*' === $month && '*' === $day_of_week && '*' === $hours ) {
			$frequency         = 'hourly';
			$hourly_start_time = $minutes;
		} else {
			throw new InvalidArgumentException( 'Unsupported cron expression' );
		}

		$start_time = sprintf( '%02d:%02d', $hours, $minutes );

		return [
			'frequency'         => $frequency,
			'start_time'        => $start_time,
			'hourly_start_time' => $hourly_start_time,
			'monthly_start_day' => $monthly_start_day,
			'weekly_start_day'  => $weekly_start_day,
		];
	}

	/**
	 * Re-evaluates and reschedules the default BackWPup cron jobs for file and database backups.
	 *
	 * This function performs the following steps:
	 * 1. Retrieves the default job IDs for file and database backups.
	 * 2. Disables the current scheduled cron for the file backup job.
	 * 3. If the file backup job is active and uses 'wpcron', it reschedules the job.
	 * 4. Disables the current scheduled cron for the database backup job.
	 * 5. If the database backup job is active and uses 'wpcron', it reschedules the job.
	 *
	 * @return int|false The timestamp of the next cron job, or false if the job is not active.
	 */
	public static function re_evaluate_cron_jobs() {
		// Retrieve the default job IDs for verification and scheduling.
		$default_file_job_id     = get_site_option( 'backwpup_backup_files_job_id', false );
		$default_database_job_id = $default_file_job_id + 1;

		// Disable the default file backup cron.
		wp_clear_scheduled_hook( 'backwpup_cron', [ 'arg' => $default_file_job_id ] );

		// If the job is active, reschedule it.
		if ( 'wpcron' === BackWPup_Option::get( $default_file_job_id, 'activetype', '' ) ) {
			$cron_next = self::cron_next( BackWPup_Option::get( $default_file_job_id, 'cron' ) );
			wp_schedule_single_event( $cron_next, 'backwpup_cron', [ 'arg' => $default_file_job_id ] );
		}

		// Disable the default database backup cron.
		wp_clear_scheduled_hook( 'backwpup_cron', [ 'arg' => $default_database_job_id ] );

		// If the job is active, reschedule it.
		if ( 'wpcron' === BackWPup_Option::get( $default_database_job_id, 'activetype', '' ) ) {
			$cron_next = self::cron_next( BackWPup_Option::get( $default_database_job_id, 'cron' ) );
			wp_schedule_single_event( $cron_next, 'backwpup_cron', [ 'arg' => $default_database_job_id ] );
		}

		return $cron_next ?? false;
	}
}
