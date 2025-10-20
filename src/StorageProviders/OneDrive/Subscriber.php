<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\StorageProviders\OneDrive;

use BackWPup_Encryption;
use BackWPup_Pro_OneDrive_ConfigTrait;
use WPMedia\BackWPup\EventManagement\SubscriberInterface;
use WPMedia\BackWPup\Plugin\Plugin;
use InvalidArgumentException;
use BackWPup_Pro_Settings_APIKeys;
use Krizalys\Onedrive\Onedrive;

class Subscriber implements SubscriberInterface {

	use BackWPup_Pro_OneDrive_ConfigTrait;

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

		[$client_id, $client_secret] = $this->one_drive_credentials();

		$config                  = $this->one_drive_client_config();
		$config['client_secret'] = $client_secret;

		$client = Onedrive::client( $client_id, $config );

		$job_id = get_site_option( Plugin::FIRST_JOB_ID );
		if ( ! $job_id ) {
			throw new InvalidArgumentException( esc_html__( 'Job ID was not provided', 'backwpup' ) );
		}

		$url = $client->getLogInUrl(
			$this->one_drive_scopes(),
			$config['redirect_uri'],
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

		return $url ?: null;
	}
}
