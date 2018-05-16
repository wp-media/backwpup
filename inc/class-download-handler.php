<?php

/**
 * Class DownloadLogHandler
 */
class BackWpup_Download_Handler {

	/**
	 * Nonce
	 *
	 * @var string The nonce to verify
	 */
	private $nonce_action;

	/**
	 * Capability
	 *
	 * @var string The capability needed to download the file
	 */
	private $capability;

	/**
	 * Action
	 *
	 * @var string The action to perform
	 */
	private $action;

	/**
	 * Downloader
	 *
	 * @var \BackWPup_Download_File_Interface The instance used to download the file
	 */
	private $downloader;

	/**
	 * DownloadLogHandler constructor
	 *
	 * @param \BackWPup_Download_File_Interface $downloader   The instance used to download the file.
	 * @param  string                           $nonce_action The nonce to verify.
	 * @param  string                           $capability   The capability needed to download the file.
	 * @param  string                           $action       The action to perform.
	 */
	public function __construct( \BackWPup_Download_File_Interface $downloader, $nonce_action, $capability, $action ) {

		$this->downloader   = $downloader;
		$this->nonce_action = $nonce_action;
		$this->capability   = $capability;
		$this->action       = $action;
	}

	/**
	 * Handle the Request
	 *
	 * @return void
	 */
	public function handle() {

		if ( ! $this->verify_request() ) {
			return;
		}

		$this->downloader->download();
	}

	/**
	 * Verify Request
	 *
	 * @return bool True if verified, false otherwise. Die if nonce is not valid
	 */
	private function verify_request() {

		// phpcs:ignore
		if ( ! isset( $_GET['action'] ) || $this->action !== filter_var( $_GET['action'], FILTER_SANITIZE_STRING ) ) {
			return false;
		}

		check_admin_referer( $this->nonce_action );

		if ( ! current_user_can( $this->capability ) ) {
			wp_die( 'Cheating Uh?' );
		}

		return true;
	}
}
