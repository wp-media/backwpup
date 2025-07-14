<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\StorageProviders\OneDrive;

use WPMedia\BackWPup\EventManagement\SubscriberInterface;
use WPMedia\BackWPup\Plugin\Plugin;
use InvalidArgumentException;
use BackWPup_Pro_Settings_APIKeys;
use Krizalys\Onedrive\Onedrive;

class Subscriber implements SubscriberInterface {

	/**
	 * Returns an array of events that this subscriber wants to listen to.
	 *
	 * @return mixed
	 */
	public static function get_subscribed_events() {
		return [
			'backwpup_onedrive_login_url' => [ 'get_login_url' ],
		];
	}

	/**
	 * Returns the login URL for OneDrive.
	 *
	 * @param string|null $url The URL to redirect to after login.
	 *
	 * @return string|null The login URL for OneDrive or null if not available.
	 * @throws InvalidArgumentException If the job ID is not provided.
	 */
	public function get_login_url( ?string $url = null ): ?string {

		$client_id = get_site_option( BackWPup_Pro_Settings_APIKeys::OPTION_ONEDRIVE_CLIENT_ID );
		$client    = Onedrive::client( $client_id );

		$job_id = get_site_option( Plugin::FIRST_JOB_ID );
		if ( ! $job_id ) {
			throw new InvalidArgumentException( esc_html__( 'Job ID was not provided', 'backwpup' ) );
		}

		$url = $client->getLogInUrl(
			[
				'files.read',
				'files.read.all',
				'files.readwrite',
				'files.readwrite.all',
				'offline_access',
			],
			home_url( 'wp-load.php' ),
			'backwpup_dest_onedrive'
		);

		$job_url = add_query_arg(
			[
				'page'     => 'backwpupeditjob',
				'jobid'    => $job_id,
				'tab'      => 'dest-onedrive',
				'_wpnonce' => wp_create_nonce( 'edit-job' ),
			],
			network_admin_url( 'admin.php' )
		);

		set_site_transient(
			'backwpup_onedrive_state',
			[
				'state'   => $client->getState(),
				'job_url' => $job_url,
				'job_id'  => $job_id,
			],
			HOUR_IN_SECONDS
		);

		if ( ! empty( $url ) ) {
			return $url;
		}

		return null;
	}
}
