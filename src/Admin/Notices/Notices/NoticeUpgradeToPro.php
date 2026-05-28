<?php

namespace WPMedia\BackWPup\Admin\Notices\Notices;

use BackWPup;
use Inpsyde\BackWPup\Notice\NoticeMessage;
use Inpsyde\BackWPup\Notice\DismissibleNoticeOption;
use Inpsyde\BackWPup\Notice\NoticeView;

/**
 * Notice to encourage users to upgrade to Pro after a successful backup scheduled job run. This notice is dismissible and will be shown a maximum of 5 times per user, with a limit of one impression per day.
 */
class NoticeUpgradeToPro extends AbstractNotice {

	public const SESSIONS_SHOWN_META_KEY       = 'backwpup_upgrade_to_pro_sessions_shown';
	public const LAST_SESSION_META_KEY         = 'backwpup_upgrade_to_pro_last_session';
	public const NUMBER_OF_SESSIONS_BEFORE_CAP = 5;

	/**
	 * The unique ID for this notice.
	 */
	public const ID = 'notice_upgrade_to_pro';

	/**
	 * The site-option key used to record a permanent sitewide dismissal of this notice
	 * (DismissibleNoticeOption::OPTION_PREFIX . self::ID).
	 */
	public const DISMISSED_SITE_OPTION_KEY = 'backwpup_dinotopt_notice_upgrade_to_pro';


	/**
	 * Constructor.
	 *
	 * @param NoticeView $view The view renderer for the notice.
	 */
	public function __construct( NoticeView $view ) {
		parent::__construct( $view, true );
	}

	/**
	 * Determine if the notice should be displayed.
	 *
	 * @return bool
	 */
	protected function should_display(): bool {
		// Never show if the user is in onboarding, is already Pro, or if the parent conditions for display are not met.
		$onboarding_to_do = get_site_option( 'backwpup_onboarding', false );
		if ( $onboarding_to_do || BackWPup::is_pro() || ! parent::should_display() ) {
			return false;
		}

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}

		// Limit to 5 impressions per user.
		$sessions_shown = (int) get_user_meta( $user_id, self::SESSIONS_SHOWN_META_KEY, true );
		if ( $sessions_shown >= self::NUMBER_OF_SESSIONS_BEFORE_CAP ) {
			return false;
		}

		// Show once per session (1 session = 1 day).
		$last_session    = get_user_meta( $user_id, self::LAST_SESSION_META_KEY, true );
		$current_session = gmdate( 'Y-m-d' ); // 1 session = 1 day.
		if ( $last_session === $current_session ) {
			return false;
		}

		// Record the impression for the user.
		update_user_meta( $user_id, self::SESSIONS_SHOWN_META_KEY, $sessions_shown + 1 );
		update_user_meta( $user_id, self::LAST_SESSION_META_KEY, $current_session );

		return true;
	}

	/**
	 * Render the notice using the view.
	 *
	 * @param NoticeMessage $message
	 */
	protected function render( NoticeMessage $message ): void {
		$this->view->banner( $message, null );
	}

	/**
	 * Get the dismiss action URL for the notice.
	 *
	 * @return string|null
	 */
	protected function get_dismiss_action_url(): ?string {
		if ( ! $this->dismissible ) {
			return null;
		}

		return DismissibleNoticeOption::dismiss_action_url(
			self::ID,
			DismissibleNoticeOption::FOR_USER_FOR_GOOD_ACTION
		);
	}

	/**
	 * Build the message for the notice.
	 *
	 * @return NoticeMessage
	 */
	protected function message(): NoticeMessage {
		$notice_message             = new NoticeMessage( self::ID );
		$notice_message->dismissurl = $this->get_dismiss_action_url();
		return $notice_message;
	}

	/**
	 * Check if the current screen is allowed for this notice.
	 *
	 * @return bool
	 */
	protected function is_screen_allowed(): bool {
		$screen = get_current_screen();
		if ( ! $screen instanceof \WP_Screen || ! isset( $screen->id ) ) {
			return false;
		}
		// Do not show on the first backup progress page.
		if ( 'admin_page_backwpupfirstbackup' === $screen->id ) {
			return false;
		}
		// Check if the current screen is a BackWPup page.
		return isset( $screen->id ) && strpos( $screen->id, 'backwpup' ) !== false;
	}
}
