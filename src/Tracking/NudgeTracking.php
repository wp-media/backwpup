<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Tracking;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tracking methods for upgrade nudge banners.
 */
class NudgeTracking extends BaseTracking {

	/**
	 * Track a nudge impression event when locked premium features are displayed to a free user.
	 *
	 * @param string $location The screen or context where the nudge appeared.
	 *
	 * @return void
	 */
	public function track_nudge_impression( string $location ): void {
		if ( ! $this->can_track() ) {
			return;
		}

		$user = wp_get_current_user();
		$this->identify_user( $user );

		$properties                   = $this->get_default_event_properties();
		$properties['nudge_location'] = $location;

		$this->mixpanel->track( 'Upgrade nudge banner shown', $properties );
	}

	/**
	 * Track a nudge CTA click event when a free user clicks the "Upgrade to Pro" button.
	 *
	 * @param string $storage_slug The storage slug the user tried to upgrade from.
	 *
	 * @return void
	 */
	public function track_nudge_click( string $storage_slug ): void {
		if ( ! $this->can_track() ) {
			return;
		}

		$user = wp_get_current_user();
		$this->identify_user( $user );

		$properties            = $this->get_default_event_properties();
		$properties['storage'] = $storage_slug;

		$this->mixpanel->track( 'Upgrade nudge banner clicked', $properties );
	}

	/**
	 * Track a locked option click event when a free user attempts to select a PRO-only storage.
	 *
	 * @param string $storage_slug The storage slug the user attempted to select.
	 *
	 * @return void
	 */
	public function track_locked_option_click( string $storage_slug ): void {
		if ( ! $this->can_track() ) {
			return;
		}

		$user = wp_get_current_user();
		$this->identify_user( $user );

		$properties            = $this->get_default_event_properties();
		$properties['storage'] = $storage_slug;

		$this->mixpanel->track( 'Locked storage option clicked', $properties );
	}
}
