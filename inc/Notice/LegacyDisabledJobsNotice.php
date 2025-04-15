<?php
declare(strict_types=1);

namespace Inpsyde\BackWPup\Notice; // phpcs:ignore

use BackWPup_Job;

class LegacyDisabledJobsNotice extends Notice {
	/**
	 * The notice ID.
	 *
	 * @var string
	 */
	public const ID = 'disabled_legacy_jobs';

	/**
	 * {@inheritdoc}
	 *
	 * @param NoticeMessage $message The message to render.
	 */
	protected function render( NoticeMessage $message ): void {
		$this->view->info( $message, null, 'error' );
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
		$site_wide = is_multisite() ? true : false;
		$option    = new DismissibleNoticeOption( $site_wide );

		if ( parent::shouldDisplay() ) {
			$jobs        = BackWPup_Job::get_jobs();
			$legacy_jobs = [];
			foreach ( $jobs as $job ) {
				// Skip temp jobs.
				if ( isset( $job['legacy'] ) && true === $job['legacy'] && '' === $job['activetype'] ) {
					$legacy_jobs[] = $job;
				}
			}

			return (bool) count( $legacy_jobs ) && ! $option->is_dismissed( 'disabled_legacy_job' );
		}

		return false;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return NoticeMessage
	 */
	protected function message(): NoticeMessage {
		$message             = new NoticeMessage( 'disabled_legacy_jobs' );
		$message->dismissurl = DismissibleNoticeOption::dismiss_action_url(
			static::ID,
			DismissibleNoticeOption::FOR_GOOD_ACTION
		);

		return $message;
	}
}
