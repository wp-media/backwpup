<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Tracking;

use WPMedia\Mixpanel\Optin;
use WPMedia\Mixpanel\TrackingPlugin as MixpanelTracking;

class Notices {
	/**
	 * Optin instance.
	 *
	 * @var Optin
	 */
	private $optin;


	/**
	 * Tracking constructor.
	 *
	 * @param Optin $optin Optin instance.
	 */
	public function __construct( Optin $optin ) {
		$this->optin = $optin;
	}

	/**
	 * Determine if the notice should be displayed.
	 *
	 * @return bool
	 */
	private function should_display_notice() {
		$screen = get_current_screen();
		if ( ! isset( $screen->id ) || ! preg_match( '/^backwpup(-pro)?_page_backwpuponboarding(-network)?$/', $screen->id ) ) {
			return false;
		}

		/**
		 * Filter whether the tracking notice should be displayed.
		 *
		 * @param bool $enable Enable starting job with external link for type "link", default is true if the activetype is link or easycron.
		 * @param array $args Job args array.
		 */
		return wpm_apply_filters_typed(
			'boolean',
			'backwpup_notice_optin_should_display',
			true
		);
	}

	/**
	 * Display tracking notice.
	 *
	 * @return void.
	 */
	public function display_tracking_notices() {
		if ( ! $this->should_display_notice() ) {
			return;
		}

		$inline_script = sprintf(
			'<script>var bwuAnalyticsOptin = { "_ajax_nonce": "%s" };</script>',
			esc_js( wp_create_nonce( 'backwpup_analytics_optin' ) )
		);

		$message = sprintf(
			'<p>%1$s</p>
	 <ul>
	     <li>%2$s</li>
	     <li>%3$s</li>
	 </ul>
	 <p>
	     <a href="#" class="bwu-onboarding-optin" data-optin="yes">%4$s</a><br />
	     <a href="#" class="bwu-onboarding-optin" data-optin="no">%5$s</a>
	 </p>
	 <p>%6$s</p> %7$s',
			esc_html__( 'Can we collect anonymous data to make BackWPup better?', 'backwpup' ),
			esc_html__( 'What we track: Only features usage, onboarding, errors, & environment info.', 'backwpup' ),
			esc_html__( 'Why: To understand what works, fix bugs faster, and prioritize new features.', 'backwpup' ),
			esc_html__( 'Yes, help improve BackWPup!', 'backwpup' ),
			esc_html__( 'No, thanks.', 'backwpup' ),
			esc_html__( 'You can change this setting at any time in the plugin settings.', 'backwpup' ),
			$inline_script
		);

		backwpup_notice_html(
			[
				'status'               => 'info',
				'dismissible'          => '',
				'title'                => sprintf(
				// translators: %1$s = strong opening tag, %2$s = strong closing tag.
					__( '📈 %1$sHelp Us Improve BackWPup! %2$s', 'backwpup' ),
					'<strong>',
					'</strong>',
				),
				'message'              => $message,
				'dismiss_button'       => 'backwpup_optin_notice',
				'dismiss_button_class' => 'bwpup-ajax-close',
				'id'                   => 'backwpup_optin_notice',
			]
		);
	}
}
