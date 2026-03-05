<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Admin\Rating;

/**
 * Provides notice copy based on context.
 */
class RatingNoticeMessageProvider {

	public const CTX_AFTER_ONBOARDING             = 'after_onboarding';
	public const CTX_AFTER_10_DAYS                = 'after_10_days';
	public const CTX_AFTER_FIRST_SCHEDULED_BACKUP = 'after_first_scheduled_backup';

	/**
	 * Returns notice content for context.
	 *
	 * @param string $context Context identifier.
	 *
	 * @return array{title:string,message:string}
	 */
	public function get_message( string $context ): array {
		switch ( $context ) {
			case self::CTX_AFTER_ONBOARDING:
				return [
					'title'   => sprintf(
						// translators: %1$s: opening a tag, %2$s: closing a tag.
						esc_html__( '%1$sAll set!%2$s Automatic backups are now saved and scheduled for your website. ✅', 'backwpup' ),
						'<span>',
						'</span>'
					),
					'message' => __( "💡 Tips: You can create additional backups and store them in different locations for extra safety.\n\nIf BackWPup is helping you protect your website, we'd really appreciate a quick review on WordPress.org", 'backwpup' ),
				];

			case self::CTX_AFTER_FIRST_SCHEDULED_BACKUP:
				return [
					'title'   => __( 'Congratulations!', 'backwpup' ),
					'message' => __( "We have saved your first backup automatically as scheduled ✅\n\nWe hope BackWPup has been a valuable tool in managing your WordPress backups. If you've found our plugin helpful, please take a moment to leave a review. Your feedback helps us improve and serve you better!", 'backwpup' ),
				];

			case self::CTX_AFTER_10_DAYS:
			default:
				return [
					'title'   => __( 'Thank You for Using BackWPup!', 'backwpup' ),
					'message' => __( "We hope BackWPup has been a valuable tool in managing your WordPress backups. If you've found our plugin helpful, please take a moment to leave a review. Your feedback helps us improve and serve you better!", 'backwpup' ),
				];
		}
	}
}
