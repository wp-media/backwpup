<?php # -*- coding: utf-8 -*-

namespace Inpsyde\BackWPup\Notice;

/**
 * Class PromoterUpdater
 */
class PromoterUpdater {

	const URL = 'https://backwpup.com/wp-json/inpsyde-messages/v1/message/';

	/**
	 * @return array
	 */
	public function update() {

		$api_response = wp_remote_get( self::URL );
		if ( is_wp_error( $api_response ) ) {
			return array();
		}
		if ( 200 !== $api_response['response']['code'] ) {
			return array();
		}

		$messages = json_decode( wp_remote_retrieve_body( $api_response ), true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return array();
		}

		foreach ( $messages as $language => $remote_data ) {
			$data[ $language ] = wp_parse_args(
				$remote_data,
				PromoterMessage::defaults()
			);
		}

		is_multisite() ?
			set_site_transient(
				Promoter::OPTION_NAME,
				$data,
				DAY_IN_SECONDS
			) :
			set_transient(
				Promoter::OPTION_NAME,
				$data,
				DAY_IN_SECONDS
			);
	}
}
