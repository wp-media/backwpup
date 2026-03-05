<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Admin\Rating;

class RatingInstallStateInitializer {

	/**
	 * Ensure install state options exist and derive defaults from activation time when available.
	 *
	 * @return void
	 */
	public function maybe_initialize(): void {
		$install_type  = (string) get_option( RatingNoticeDecider::OPT_INSTALL_TYPE, '' );
		$install_ts    = (int) get_option( RatingNoticeDecider::OPT_INSTALL_TIME, 0 );
		$activation_ts = (int) get_site_option( 'backwpup_activation_time', 0 );

		if ( '' === $install_type ) {
			$previous_version = (string) get_site_option( 'backwpup_previous_version', '' );
			$current_version  = (string) get_site_option( 'backwpup_version', '' );

			if ( '' !== $previous_version && '' !== $current_version ) {
				// Version values come from the upgrade/activation flow. If they match, this is a fresh install.
				$is_fresh_install = '0.0.0' === $previous_version
					|| version_compare( $previous_version, $current_version, '=' );
				$install_type     = $is_fresh_install ? 'new' : 'update';
			} else {
				// Fallback for legacy environments where version options are missing.
				$install_type = $activation_ts > 0 ? 'update' : 'new';
			}
			add_option( RatingNoticeDecider::OPT_INSTALL_TYPE, $install_type, '', false );
		}

		if ( 0 >= $install_ts ) {
			$install_ts = $activation_ts > 0 ? $activation_ts : time();
			add_option( RatingNoticeDecider::OPT_INSTALL_TIME, $install_ts, '', false );
		}
	}
}
