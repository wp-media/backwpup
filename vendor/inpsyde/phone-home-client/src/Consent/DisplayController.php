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
class Inpsyde_PhoneHome_Consent_DisplayController {

	const OPTION_NAME = 'inpsyde_phchide_';

	/**
	 * @var bool
	 */
	private static $should_show;

	/**
	 * @var Inpsyde_PhoneHome_Consent
	 */
	private $consent;

	/**
	 * @param $plugin_name
	 */
	public function __construct( $plugin_name ) {
		$this->consent = new Inpsyde_PhoneHome_Consent( $plugin_name );
	}

	/**
	 * @param WP_User $user
	 *
	 * @return bool
	 */
	public function should_show( WP_User $user ) {

		if ( is_bool( self::$should_show ) ) {
			return self::$should_show;
		}

		// If we already have a consent, no need to ask for consent again
		if ( $this->consent->agreed() ) {
			self::$should_show = false;

			return self::$should_show;
		}

		$option_name = $this->option_name();

		// If the user decided to hide question for good, let's respect their decision
		if ( get_user_option( $option_name, $user->ID ) ) {
			self::$should_show = false;

			return self::$should_show;
		}

		// If the user decided to hide question for some time, let's respect their decision
		if ( get_site_transient( $option_name . $user->ID ) ) {
			self::$should_show = false;

			return self::$should_show;
		}

		self::$should_show = true;

		return self::$should_show;
	}

	/**
	 * @param WP_User $user
	 *
	 * @return bool
	 */
	public function hide_for_good( WP_User $user ) {
		return (bool) update_user_option( $user->ID, $this->option_name(), 1 );
	}

	/**
	 * @param WP_User $user
	 *
	 * @return bool
	 */
	public function hide_for_now( WP_User $user ) {
		return set_site_transient( $this->option_name() . $user->ID, 1, DAY_IN_SECONDS );
	}

	/**
	 * Cut option name to 32 characters so that 8 are left to be used for user ID in site transient name
	 *
	 * @return string
	 */
	private function option_name() {

		$plugin = $this->consent->plugin();

		return self::OPTION_NAME . substr( $plugin, 0, 6 ) . substr( md5( $plugin ), 0, 16 );
	}
}