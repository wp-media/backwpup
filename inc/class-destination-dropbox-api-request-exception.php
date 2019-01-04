<?php
/**
 * Exception thrown when there is an error in the Dropbox request.
 */
class BackWPup_Destination_Dropbox_API_Request_Exception extends BackWPup_Destination_Dropbox_API_Exception {

	protected $error;

	public function __construct( $message, $code = 0, $previous = null, $error = null ) {

		$this->error = $error;
		parent::__construct( $message, $code, $previous );
	}

	public function getError() {

		return $this->error;
	}
}
