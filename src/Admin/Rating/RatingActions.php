<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Admin\Rating;

/**
 * Handles admin-post actions for the rating notice.
 */
class RatingActions {

	/**
	 * Dismiss duration for the close icon.
	 */
	private const DISMISS_DAYS = 30;

	/**
	 * RatingNoticeDecider instance.
	 *
	 * @var RatingNoticeDecider
	 */
	private RatingNoticeDecider $decider;

	/**
	 * RatingEvents instance.
	 *
	 * @var RatingEvents
	 */
	private RatingEvents $events;

	/**
	 * RatingNoticeDecider instance.
	 *
	 * @param RatingNoticeDecider $decider RatingNoticeDecider instance.
	 * @param RatingEvents        $events RatingEvents instance.
	 */
	public function __construct( RatingNoticeDecider $decider, RatingEvents $events ) {
		$this->decider = $decider;
		$this->events  = $events;
	}

	/**
	 * Dismiss notice for a period of time.
	 */
	public function dismiss(): void {
		check_admin_referer( 'backwpup_rating_notice_dismiss' );

		$this->decider->dismiss_until( get_current_user_id(), time() + ( self::DISMISS_DAYS * DAY_IN_SECONDS ) );
		$this->events->do_tracking( RatingEvents::TRIGGER_DISMISSED );

		wp_safe_redirect( wp_get_referer() ?: admin_url() );
		exit;
	}

	/**
	 * Remind notice for a period of time (30 days).
	 */
	public function remind(): void {
		check_admin_referer( 'backwpup_rating_notice_remind' );

		$this->decider->remind_later( (int) get_current_user_id(), time() + ( self::DISMISS_DAYS * DAY_IN_SECONDS ) );
		$this->events->do_tracking( RatingEvents::TRIGGER_REMIND_LATER );

		wp_safe_redirect( wp_get_referer() ?: admin_url() );
		exit;
	}
}
