<?php

namespace WPMedia\BackWPup\Admin\Notices;

use WPMedia\BackWPup\Admin\Beacon\Beacon;
use WPMedia\BackWPup\License\LicenseState;

/**
 * Builds the admin notice title and message for the current license state.
 *
 * This class contains only presentation logic (text + links),
 * and does not access storage directly.
 */
class LicenseNoticeFactory {

	/**
	 * Beacon instance.
	 *
	 * @var Beacon
	 */
	private Beacon $beacon;

	/**
	 * Constructor.
	 *
	 * @param Beacon $beacon Beacon instance used to generate help/upgrade links.
	 */
	public function __construct( Beacon $beacon ) {
		$this->beacon = $beacon;
	}

	/**
	 * Builds the notice data for the given license state.
	 *
	 * @param LicenseState $state The normalized license state.
	 * @param bool         $is_legacy_payment_method Whether the license uses the legacy payment flow.
	 * @param bool         $is_backwpup_main_screen Whether the notice is rendered on the main BackWPup screen.
	 *
	 * @return array{title:string, message:string} Title and message to be rendered.
	 */
	public function build(
		LicenseState $state,
		bool $is_legacy_payment_method,
		bool $is_backwpup_main_screen
	): array {

		if ( $is_legacy_payment_method ) {
			$link = $this->beacon->get_suggest(
				'update-payment-method',
				true,
				[ 'bwu_event' => 'legacy_update_payment_method' ]
			);

			return [
				'title'   => sprintf(
					// translators: 1: <strong> opening tag, 2: link opening tag, 3: link closing tag, 4: </strong> closing tag.
				__( '⚠️ %1$sAction Required – %2$sUpdate Your Payment Method%3$s%4$s', 'backwpup' ),
					'<strong>',
					'<a href="' . esc_url( $link['url'] ) . '" title="' . esc_attr( $link['title'] ) . '" target="_blank" class="text-primary-darker border-b border-primary-darker">',
					'</a>',
					'</strong>'
				),
				'message' => __( 'Your payment method is outdated. Update it to restore your Pro features.', 'backwpup' ),
			];
		}

		switch ( $state->state() ) {
			case LicenseState::STATE_NOT_ACTIVATED:
				$message = $is_backwpup_main_screen
					? sprintf(
						// translators: 1: line break tag, 2: opening <button> tag for "Advanced settings", 3: closing </button> tag.
						__(
							'To unlock Pro features and receive updates, you need to activate your license.%1$s%1$sGo to %2$sAdvanced settings%3$s → License, then enter your API Key and Product ID and click Activate.%1$sOnce the details are valid, your license will be activated automatically.',
							'backwpup'
						),
						'<br>',
						'<button type="button" class="text-base gap-4 inline-flex items-center justify-center leading-5 text-primary-darker border-b border-primary-darker font-title enabled:hover:text-primary-lighter enabled:hover:border-primary-lighter js-backwpup-open-sidebar" data-content="advanced-settings">',
						'</button>'
					)
					: sprintf(
						// translators: 1: line break tag, 2: <strong> opening tag, 3: </strong> closing tag.
						__(
							'To unlock Pro features and receive updates, you need to activate your license.%1$s%1$sGo to %2$sBackWPup Pro → Advanced settings → License%3$s, then enter your API Key and Product ID and click Activate.%1$sOnce the details are valid, your license will be activated automatically.',
							'backwpup'
						),
						'<br>',
						'<strong>',
						'</strong>'
					);

				return [
					'title'   => sprintf(
						// translators: 1: <strong> opening tag, 2: </strong> closing tag.
					__( '⚠️ %1$sYour BackWPup Pro license is not activated%2$s', 'backwpup' ),
						'<strong>',
						'</strong>'
					),
					'message' => $message,
				];

			case LicenseState::STATE_LIMIT_REACHED:
				$link = $this->beacon->get_suggest(
					'user_account',
					true,
					[
						'bwu_event' => 'license_activation_limit_reached',
					]
				);

				return [
					'title'   => sprintf(
						// translators: 1: <strong> opening tag, 2: </strong> closing tag.
					__( '⚠️ %1$sAll allowed WordPress installations are in use%2$s', 'backwpup' ),
						'<strong>',
						'</strong>'
					),
					'message' => sprintf(
						// translators: 1: line break tag, 2: <a> opening tag (Manage licenses / upgrade button), 3: </a> closing tag.
					__(
						'You’ve reached the maximum number of websites allowed for your current plan.%1$sTo activate this site, please deactivate a license on another site or upgrade your plan.%1$s%1$s%2$sManage licenses or upgrade plan%3$s',
						'backwpup'
					),
						'<br>',
						'<a href="' . esc_url( $link['url'] ) . '" title="' . esc_attr( $link['title'] ) . '" target="_blank" rel="noopener noreferrer" class="button button-primary">',
						'</a>'
					),
				];

			case LicenseState::STATE_EXPIRED:
			case LicenseState::STATE_INVALID:
			default:
				$link = $this->beacon->get_suggest(
					'update-payment-method',
					true,
					[ 'bwu_event' => 'expired_license_update_payment_method' ]
				);

				return [
					'title'   => sprintf(
						// translators: 1: <strong> opening tag, 2: </strong> closing tag.
					__( '⚠️ %1$sYour BackWPup Pro Plan Has Expired%2$s', 'backwpup' ),
						'<strong>',
						'</strong>'
					),
					'message' => sprintf(
						// translators: 1: <a> opening tag, 2: </a> closing tag.
						__( '%1$sRenew your subscription%2$s to continue receiving updates and Pro features.', 'backwpup' ),
						'<a href="' . esc_url( $link['url'] ) . '" title="' . esc_attr( $link['title'] ) . '" target="_blank" class="text-primary-darker border-b border-primary-darker">',
						'</a>'
					),
				];
		}
	}
}
