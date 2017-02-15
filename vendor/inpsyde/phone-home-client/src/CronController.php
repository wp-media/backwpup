<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Inpsyde phone-home-client package.
 *
 * (c) 2017 Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package phone-home-client
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License, version 3
 */
class Inpsyde_PhoneHome_CronController {

	const HOOK = 'inpsyde_phone-home_checkin';
	const RECURRENCE_KEY = 'twice_weekly';
	const RECURRENCE_INTERVAL = 1209600; // 3600 * 24 * 14

	/**
	 * @var Inpsyde_PhoneHome_HttpClient
	 */
	private $http_client;

	/**
	 * @var Inpsyde_PhoneHome_Consent
	 */
	private $consent;

	/**
	 * Unschedule send data hook
	 */
	public static function unschedule() {
		wp_clear_scheduled_hook( self::HOOK );
	}

	/**
	 * @param Inpsyde_PhoneHome_HttpClient $http_client
	 * @param Inpsyde_PhoneHome_Consent    $consent
	 */
	public function __construct( Inpsyde_PhoneHome_HttpClient $http_client, Inpsyde_PhoneHome_Consent $consent ) {
		$this->http_client = $http_client;
		$this->consent     = $consent;
	}

	/**
	 * Schedule send data hook
	 */
	public function schedule() {

		add_action( self::HOOK, array( $this, 'run' ) );
		add_filter( 'cron_schedules', array( $this, 'filter_schedules' ) );

		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time(), self::RECURRENCE_KEY, self::HOOK );
		}
	}

	/**
	 * Run the send data hook
	 *
	 * @return bool
	 */
	public function run() {
		return $this->http_client->send_data( $this->consent->consent_data() );
	}

	/**
	 * Add custom schedule
	 *
	 * @param array $schedules
	 *
	 * @return array
	 */
	public function filter_schedules( $schedules ) {

		is_array( $schedules ) or $schedules = array();
		$label = __( 'Every %d days', 'inpsyde-phone-home' );

		$interval = apply_filters( 'inpsyde-phone-home-cron-interval', self::RECURRENCE_INTERVAL );
		is_int( $interval ) or $interval = self::RECURRENCE_INTERVAL;

		$schedules[ self::RECURRENCE_KEY ] = array(
			'interval' => $interval,
			'display'  => sprintf( $label, ceil( $interval / DAY_IN_SECONDS ) )
		);

		return $schedules;
	}
}