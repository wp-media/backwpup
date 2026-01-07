<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Admin\Beacon;

use WPMedia\BackWPup\Common\AbstractRender;

class Beacon extends AbstractRender {

	/**
	 * Returns the link for corresponding section.
	 * TODO:: Add language option, we could have separate link for diff languages.
	 *
	 * @since  5.4
	 *
	 * @param string $doc_id Section identifier. Available values:
	 *                       - 'include_extra_files'
	 *                       - 'file_format'
	 *                       - 'user_account'
	 *                       - 'contact_support'.
	 * @param bool   $is_tracked Whether to add tracking data to the URL.
	 * @param array  $track_data Tracking data to append to the URL.
	 *                           Format structure:
	 *                           [
	 *                               'bwu_event' => string,        // Required. The event name to track
	 *                               'property1' => string|int,    // Optional. Custom event property
	 *                               'property2' => string|int,    // Optional. Additional event property
	 *                               // ... more properties
	 *                           ]
	 *
	 *                           Examples:
	 *                           - Simple tracking:
	 *                           ['bwu_event' => 'help_clicked']
	 *
	 *                           - With properties:
	 *                           [
	 *                               'bwu_event' => 'support_link_clicked',
	 *                               'page' => 'job_edit',
	 *                               'user_type' => 'pro',
	 *                               'job_id' => 123
	 *                           ]
	 *
	 *                           Note: All properties except 'bwu_event' will be
	 *                           automatically prefixed with 'bwu_event_property_'.
	 *
	 * @return string|array
	 */
	public function get_suggest( string $doc_id, bool $is_tracked = false, array $track_data = [] ): array {
		$suggest = [
			'include_extra_files'   => [
				'url'   => 'https://backwpup.com/backwpup-5-4/',
				'title' => __( 'Welcome to BackWPup 5.4!', 'backwpup' ),
			],
			'file_format'           => [
				'url'   => 'https://backwpup.com/backwpup-5-5/',
				'title' => __( 'BackWPup 5.5 is here!', 'backwpup' ),
			],
			'user_account'          => [
				'url'   => 'https://backwpup.com/my-account/',
				'title' => __( 'Manage your BackWPup license', 'backwpup' ),
			],
			'update-payment-method' => [
				'url'   => 'https://backwpup.com/my-account/add-payment-method/',
				'title' => __( 'Manage your BackWPup license', 'backwpup' ),
			],
			'contact_support'       => [
				'url'   => 'https://backwpup.com/contact/',
				'title' => __( 'Contact BackWPup support', 'backwpup' ),
			],
		];

		// change urls by license server.
		if ( '{{%LICENSE_URL%}}' === 'https://backwpup.de/' ) {
			$suggest['user_account']['url']          = 'https://backwpup.de/mein-account/';
			$suggest['update-payment-method']['url'] = 'https://backwpup.de/mein-account/add-payment-method/';
		}

		$language = get_user_locale();
		// update suggestions to german webseite urls.
		if ( 'de' === substr( $language, 0, 2 ) ) {
			$suggest['contact_support']['url'] = 'https://backwpup.de/kontakt/';
		}

		$selected_suggest = $suggest[ $doc_id ] ?? [];
		// Add tracking data if required using the redirect.
		if ( ! empty( $selected_suggest ) && $is_tracked ) {
			// Prefix all keys with 'bwu_event_property_' except 'bwu_event'.
			$prefixed_track_data = [];
			foreach ( $track_data as $key => $value ) {
				if ( 'bwu_event' === $key ) {
					$prefixed_track_data[ $key ] = $value;
				} else {
					$prefixed_track_data[ 'bwu_event_property_' . $key ] = $value;
				}
			}
			$selected_suggest['url'] = add_query_arg(
				$prefixed_track_data,
				'?bwu_redirect=' . $selected_suggest['url']
			);
			// Add nonce for security.
			$selected_suggest['url'] = wp_nonce_url( $selected_suggest['url'], 'backwpup_redirect_nonce' );
		}

		return $selected_suggest;
	}
}
