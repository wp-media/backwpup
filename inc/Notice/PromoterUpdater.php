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

		$api_response = wp_remote_get( self::URL, array( 'timeout' => 3 ) );
		if ( is_wp_error( $api_response ) ) {
			return array();
		}
		if ( 200 !== $api_response['response']['code'] ) {
			return array();
		}

		$json = $this->clean_json( wp_remote_retrieve_body( $api_response ) );
		$messages = json_decode( $json, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return array();
		}

		$data = array();
		foreach ( $messages as $language => $remote_data ) {
			$data[ $language ] = wp_parse_args(
				$remote_data,
				PromoterMessage::defaults()
			);
		}

		$expiration_time = DAY_IN_SECONDS / 2;

		is_multisite() ?
			set_site_transient(
				Promoter::OPTION_NAME,
				$data,
				$expiration_time
			) :
			set_transient(
				Promoter::OPTION_NAME,
				$data,
				$expiration_time
			);

		return $data;
	}

	/**
	 * @param $json
	 *
	 * @return mixed
	 */
	private function clean_json( $json ) {

		$json = str_replace(
			array( "\n", "\t", "\r" ),
			'',
			$json
		);

		return str_replace( '},}', '}}', $json );
	}
}
