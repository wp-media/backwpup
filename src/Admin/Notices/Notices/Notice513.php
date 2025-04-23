<?php

namespace WPMedia\BackWPup\Admin\Notices\Notices;

use Inpsyde\BackWPup\Notice\NoticeView;
use Inpsyde\BackWPup\Notice\NoticeMessage;
use Inpsyde\BackWPup\Notice\DismissibleNoticeOption;
use WPMedia\BackWPup\Adapters\BackWPupAdapter;

/**
 * Notice for legacy disabled tasks after update to 5.1.3.
 */
class Notice513 extends AbstractNotice {

	/**
	 * The unique ID for this notice.
	 */
	public const ID = 'legacy_disabled_tasks';

	/**
	 * Adapter for BackWPup plugin data.
	 *
	 * @var BackWPupAdapter
	 */
	private BackWPupAdapter $backwpup;

	/**
	 * Constructor.
	 *
	 * @param NoticeView      $view The view renderer for the notice.
	 * @param BackWPupAdapter $backwpup Adapter for plugin data.
	 */
	public function __construct( NoticeView $view, BackWPupAdapter $backwpup ) {
		parent::__construct( $view, true );
		$this->backwpup = $backwpup;
	}


	/**
	 * Determine if the notice should be displayed.
	 *
	 * @return bool
	 */
	protected function should_display(): bool {
		$old_version = get_site_option( 'backwpup_previous_version', '0.0.0' );
		if (
			version_compare( $old_version, '5.1.0', '>=' ) &&
			parent::should_display()
		) {
			$jobs = get_option( 'backwpup_jobs', [] );
			if ( empty( $jobs ) ) {
				return false;
			}
			$legacy_disabled_jobs = array_filter(
				$jobs,
				fn( $job ) => isset( $job['legacy'] ) && true === $job['legacy'] && ( ! isset( $job['activetype'] ) || empty( $job['activetype'] ) )
			);

			return ! empty( $legacy_disabled_jobs );
		}

		return false;
	}

	/**
	 * Render the notice using the view.
	 *
	 * @param NoticeMessage $message
	 */
	protected function render( NoticeMessage $message ): void {
		$this->view->info( $message, null, 'error' );
	}

	/**
	 * Get the dismiss action URL for the notice.
	 *
	 * @return string|null
	 */
	protected function get_dismiss_action_url(): ?string {
		if ( $this->dismissible ) {
			return DismissibleNoticeOption::dismiss_action_url(
				self::ID,
				DismissibleNoticeOption::FOR_USER_FOR_GOOD_ACTION
			);
		}
		return null;
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
	protected function isScreenAllowed(): bool {
		$screen = get_current_screen();

		// Check if the current screen is a BackWPup page.
		return isset( $screen->id ) && str_contains( $screen->id, 'backwpup' );
	}
}
