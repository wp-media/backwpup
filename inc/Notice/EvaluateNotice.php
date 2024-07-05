<?php

namespace Inpsyde\BackWPup\Notice; // phpcs:ignore

use BackWPup_Option;
use BackWPup;

class EvaluateNotice extends Notice {

	/**
	 * The number of days before the notice is displayed.
	 *
	 * @var int
	 */
	public const DAYS_BEFORE_EVALUATE = 3;
	/**
	 * The number of days before the notice is displayed again.
	 *
	 * @var int
	 */
	public const DAYS_BEFORE_REAPPEAR = 90;
	/**
	 * The option name.
	 *
	 * @var string
	 */
	public const OPTION_NAME = 'backwpup_notice_evaluate';
	/**
	 * The notice ID.
	 *
	 * @var string
	 */
	public const ID = self::OPTION_NAME;

	/**
	 * {@inheritdoc}
	 *
	 * @param NoticeMessage $message The message to render.
	 */
	protected function render( NoticeMessage $message ): void {
		$this->view->info( $message, null );
	}

	/**
	 * {@inheritdoc}
	 */
	protected function isScreenAllowed(): bool {
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function shouldDisplay(): bool {
		// Check if the notice has been dismissed.
		$site_wide = is_multisite() ? true : false;
		$option    = new DismissibleNoticeOption( $site_wide );
		if ( $option->is_dismissed( static::ID ) ) {
			return false;
		}

		// Calculate the time since the plugin was activated in days.
		$current_time          = time();
		$time_difference       = $current_time - BackWPup::get_plugin_data( 'activation_time' );
		$days_since_activation = round( $time_difference / ( 60 * 60 * 24 ) );
		if ( $days_since_activation < self::DAYS_BEFORE_EVALUATE ) {
			return false;
		}

		$jobs                = BackWPup_Option::get_job_ids();
		$one_job_already_run = false;
		// For each jobid check if it has a lastrun timestamp.
		foreach ( $jobs as $job_id ) {
			$lastrun = BackWPup_Option::get( $job_id, 'lastrun' );
			if ( false !== $lastrun ) {
				$one_job_already_run = true;
				break;
			}
		}
		return $one_job_already_run;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return NoticeMessage
	 */
	protected function message(): NoticeMessage {

		$message                  = new NoticeMessage( 'evaluate' );
		$message->tempdissmissurl = $this->getNoticeDismissActionUrl(
			DismissibleNoticeOption::FOR_NOW_ACTION,
			self::DAYS_BEFORE_REAPPEAR * 24
		);
		$message->dismissurl      = $this->getNoticeDismissActionUrl(
			DismissibleNoticeOption::FOR_GOOD_ACTION
		);
		return $message;
	}

	/**
	 * Gets the dismissible action URL from DismissibleNoticeOption.
	 *
	 * @param string   $action
	 * @param int|null $expiration
	 *
	 * @return string|null
	 */
	protected function getNoticeDismissActionUrl( string $action, ?int $expiration = null ): ?string {
		$option = new DismissibleNoticeOption( false );
		return $option::dismiss_action_url(
			static::ID,
			$action,
			$expiration
		);
	}
}
