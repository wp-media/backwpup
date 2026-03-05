<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Admin\Rating;

/**
 * Decides whether the rating notice should be displayed for a user.
 */
class RatingNoticeDecider {

	public const OPT_INSTALL_TYPE             = 'backwpup_install_type';     // 'new' | 'update' (set via RatingInstallStateInitializer).
	public const OPT_INSTALL_TIME             = 'backwpup_first_install_ts'; // int timestamp.
	public const META_DISMISSED               = 'backwpup_rating_notice_dismissed'; // bool.
	public const META_DISMISSED_UNTIL         = 'backwpup_rating_notice_dismissed_until'; // int timestamp.
	public const META_SHOWN                   = 'backwpup_rating_notice_shown';     // bool.
	public const META_REMIND_AT               = 'backwpup_rating_notice_remind_at'; // int timestamp.
	public const STATE_DISMISSED              = 'dismissed';
	public const STATE_HAS_REMIND_AT          = 'has_remind_at';
	public const STATE_REMIND_DUE             = 'remind_due';
	public const STATE_DISMISSED_UNTIL_ACTIVE = 'dismissed_until_active';
	public const STATE_DISMISSED_UNTIL_DUE    = 'dismissed_until_due';

	/**
	 * Gets rating notice state for a user.
	 *
	 * @param int $user_id User ID.
	 * @param int $now     Current timestamp.
	 * @return array<string, bool>
	 */
	public function get_user_notice_state( int $user_id, int $now ): array {
		$dismissed           = (bool) get_user_meta( $user_id, self::META_DISMISSED, true );
		$remind_at           = (int) get_user_meta( $user_id, self::META_REMIND_AT, true );
		$dismissed_until     = (int) get_user_meta( $user_id, self::META_DISMISSED_UNTIL, true );
		$has_remind_at       = 0 < $remind_at;
		$has_dismissed_until = 0 < $dismissed_until;

		return [
			self::STATE_DISMISSED              => $dismissed,
			self::STATE_HAS_REMIND_AT          => $has_remind_at,
			self::STATE_REMIND_DUE             => $has_remind_at && $remind_at <= $now,
			self::STATE_DISMISSED_UNTIL_ACTIVE => $has_dismissed_until && $now < $dismissed_until,
			self::STATE_DISMISSED_UNTIL_DUE    => $has_dismissed_until && $dismissed_until <= $now,
		];
	}

	/**
	 * Whether the notice should be shown for the user.
	 *
	 * @param int $user_id User ID.
	 * @param int $now     Current timestamp.
	 */
	public function should_show_for_user( int $user_id, int $now ): bool {
		// Only new installations.
		if ( 'new' !== (string) get_option( self::OPT_INSTALL_TYPE, '' ) ) {
			return false;
		}

		$install_ts = (int) get_option( self::OPT_INSTALL_TIME, 0 );
		if ( 0 >= $install_ts ) {
			return false;
		}

		$state = $this->get_user_notice_state( $user_id, $now );

		if ( $state[ self::STATE_DISMISSED ] ) {
			return false;
		}

		if ( $state[ self::STATE_DISMISSED_UNTIL_ACTIVE ] ) {
			return false;
		}

		if ( $state[ self::STATE_HAS_REMIND_AT ] ) {
			return $state[ self::STATE_REMIND_DUE ];
		}

		$shown = (bool) get_user_meta( $user_id, self::META_SHOWN, true );
		if ( $shown ) {
			return false;
		}

		return true;
	}

	/**
	 * Marks notice as shown.
	 *
	 * @param int $user_id User ID.
	 */
	public function mark_shown( int $user_id ): void {
		update_user_meta( $user_id, self::META_SHOWN, 1 );

		// Clear remind flag once the notice has been shown again.
		delete_user_meta( $user_id, self::META_REMIND_AT );
	}

	/**
	 * Dismisses notice permanently.
	 *
	 * @param int $user_id User ID.
	 */
	public function dismiss_forever( int $user_id ): void {
		update_user_meta( $user_id, self::META_DISMISSED, 1 );
		update_user_meta( $user_id, self::META_DISMISSED_UNTIL, PHP_INT_MAX );
		delete_user_meta( $user_id, self::META_REMIND_AT );
	}

	/**
	 * Dismisses notice until a given timestamp.
	 *
	 * @param int $user_id        User ID.
	 * @param int $dismiss_until  Timestamp until which the notice is dismissed.
	 */
	public function dismiss_until( int $user_id, int $dismiss_until ): void {
		if ( 0 >= $dismiss_until ) {
			return;
		}

		update_user_meta( $user_id, self::META_DISMISSED_UNTIL, $dismiss_until );

		// Clear remind + shown to allow the notice to reappear after the dismiss window.
		delete_user_meta( $user_id, self::META_REMIND_AT );
		delete_user_meta( $user_id, self::META_SHOWN );
	}

	/**
	 * Sets remind timestamp.
	 *
	 * @param int $user_id User ID.
	 * @param int $remind_at Timestamp for the next reminder.
	 */
	public function remind_later( int $user_id, int $remind_at ): void {
		if ( 0 >= $remind_at ) {
			return;
		}

		update_user_meta( $user_id, self::META_REMIND_AT, $remind_at );
	}
}
