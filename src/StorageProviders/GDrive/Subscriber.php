<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\StorageProviders\GDrive;

use WPMedia\BackWPup\EventManagement\SubscriberInterface;
use WPMedia\BackWPup\Plugin\Plugin;
use WPMedia\BackWPup\Adapters\OptionAdapter;
use BackWPup_Admin;
use BackWPup_Option;
use BackWPup_Encryption;
use BackWPup;
use Exception;
use WPMedia\BackWPup\Adapters\BackWPupAdapter;

class Subscriber implements SubscriberInterface {

	/**
	 * Adapter for BackWPup plugin data.
	 *
	 * @var BackWPupAdapter
	 */
	private BackWPupAdapter $backwpup;

	/**
	 * Instance of OptionAdapter.
	 *
	 * @var OptionAdapter
	 */
	private $option_adapter;

	/**
	 * Gdrive subsriber constructor.
	 *
	 * Initializes this subscriber with the given option adapter.
	 *
	 * @param OptionAdapter   $option_adapter   Adapter for handling option settings.
	 * @param BackWPupAdapter $backwpup Adapter for BackWPup plugin data.
	 */
	public function __construct(
		OptionAdapter $option_adapter,
		BackWPupAdapter $backwpup
	) {
		$this->option_adapter = $option_adapter;
		$this->backwpup       = $backwpup;
	}
	/**
	 * Returns an array of events that this subscriber wants to listen to.
	 *
	 * @return mixed
	 */
	public static function get_subscribed_events() {
		return [
			'backwpup_gdrive_save_tokens' => [ 'save_tokens', 10, 2 ],
		];
	}

	/**
	 * Save the tokens received from Google Drive authentication.
	 *
	 * @param string         $code The authorization code received from Google.
	 * @param \Google\Client $client The Google Client instance.
	 *
	 * @return void
	 */
	public function save_tokens( $code, $client ): void {
		// on edit job.
		$first_job_id = get_site_option( Plugin::FIRST_JOB_ID, false );
		$dest_gdrive  = $this->backwpup->get_destination( 'GDRIVE' );

		if ( false !== $first_job_id ) {
			try {
				$first_job_id = (int) $first_job_id;
				$access_token = $client->fetchAccessTokenWithAuthCode( $code ); //phpcs:ignore
				if ( ! empty( $access_token['refresh_token'] ) ) {
					$dest_gdrive->update_refresh_token( $first_job_id, $access_token['refresh_token'] );
					BackWPup_Admin::message( __( 'GDrive: Authenticated.', 'backwpup' ) );
				} else {
					if ( isset( $access_token['access_token'] ) ) {
						$client->revokeToken( $access_token['access_token'] );
					}

					$dest_gdrive->delete_refresh_token( $first_job_id );
					BackWPup_Admin::message(
							__( 'GDrive: No refresh token received. Try to Authenticate again!', 'backwpup' ),
							true
					);
				}
			} catch ( Exception $e ) {
					// translators: %s: error message.
					BackWPup_Admin::message( sprintf( __( 'GDrive API: %s', 'backwpup' ), $e->getMessage() ), true );
					$dest_gdrive->delete_refresh_token( $first_job_id );
			}
			// We show the cloud auth endpoint.
			$this->show_auth_endpoint();

		}
	}

	/**
	 * Show the authentication endpoint for Google Drive.
	 *
	 * This method includes the cloud authentication endpoint file and exits the script.
	 *
	 * @return void
	 */
	protected function show_auth_endpoint() {
		include $this->backwpup->get_plugin_data( 'plugindir' ) . '/cloud-auth-endpoint.php';
		exit();
	}
}
