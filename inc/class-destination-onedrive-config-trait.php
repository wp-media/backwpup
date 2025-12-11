<?php

use Krizalys\Onedrive\ClientState;

trait BackWPup_Pro_OneDrive_ConfigTrait {

	/**
	 * Returns [client_id, client_secret] already resolved.
	 *
	 * @return array{0:string|null,1:string|null} [client_id, client_secret]
	 */
	protected function one_drive_credentials(): array {
		$client_id     = get_site_option( BackWPup_Pro_Settings_APIKeys::OPTION_ONEDRIVE_CLIENT_ID );
		$client_secret = BackWPup_Encryption::decrypt(
			get_site_option( BackWPup_Pro_Settings_APIKeys::OPTION_ONEDRIVE_CLIENT_SECRET )
		);

		return [ $client_id, $client_secret ];
	}

	/**
	 * Default scopes (single source of truth).
	 *
	 * @return array
	 */
	protected function one_drive_scopes(): array {
		return [
			'files.read',
			'files.read.all',
			'files.readwrite',
			'files.readwrite.all',
			'offline_access',
		];
	}

	/**
	 * Builds the configuration array for Onedrive::client(), optionally injecting the state.
	 *
	 * @param null $state
	 * @return array
	 */
	protected function one_drive_client_config( $state = null ): array {
		$config = [
			'client_secret' => null, // filled by the caller (has the secret at hand).
			'redirect_uri'  => home_url( 'wp-load.php' ),
			'scopes'        => $this->one_drive_scopes(),
		];

		if ( null !== $state ) {
			$config['state'] = $state;
		}

		return $config;
	}

	/**
	 * Extracts obtained/expires_in supporting array|object|ClientState|null.
	 * Returns [obtained:int, expires:int]. Falls back to (0, 0) to force renewal if missing.
	 *
	 * @param mixed $client_state
	 * @return array{0:int,1:int} [obtained, expires_in]
	 */
	protected function extract_token_times( $client_state ): array {
		$token = null;

		if ( $client_state instanceof ClientState ) {
			$token = $client_state->token ?? null;
		} elseif ( is_object( $client_state ) ) {
			$token = $client_state->token ?? null;
		} elseif ( is_array( $client_state ) ) {
			$token = $client_state['token'] ?? null;
		}

		if ( is_array( $token ) ) {
			$obtained = (int) ( $token['obtained'] ?? 0 );
			$data     = $token['data'] ?? [];
			$expires  = is_array( $data ) ? (int) ( $data['expires_in'] ?? 0 )
				: ( is_object( $data ) ? (int) ( $data->expires_in ?? 0 ) : 0 );
			return [ $obtained, $expires ];
		}

		if ( is_object( $token ) ) {
			$obtained = (int) ( $token->obtained ?? 0 );
			$data     = $token->data ?? null;
			$expires  = is_array( $data ) ? (int) ( $data['expires_in'] ?? 0 )
				: ( is_object( $data ) ? (int) ( $data->expires_in ?? 0 ) : 0 );
			return [ $obtained, $expires ];
		}

		return [ 0, 0 ];
	}
}
