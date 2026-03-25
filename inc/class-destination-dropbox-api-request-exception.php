<?php
/**
 * Exception thrown when there is an error in the Dropbox request.
 */
class BackWPup_Destination_Dropbox_API_Request_Exception extends BackWPup_Destination_Dropbox_API_Exception {

	/**
	 * Error details returned by the Dropbox API.
	 *
	 * @var string[]|null
	 */
	protected $error;

	/**
	 * Creates a Dropbox API request exception.
	 *
	 * @param string         $message  Exception message.
	 * @param int            $code     Exception code.
	 * @param Exception|null $previous Previous exception.
	 * @param string[]|null  $error    Error details returned by the API.
	 */
	public function __construct( string $message, int $code = 0, ?Exception $previous = null, ?array $error = null ) {
		$this->error = $error;
		parent::__construct( $message, $code, $previous );
	}

	/**
	 * Returns the API error details.
	 *
	 * @return string[]|null Error details returned by the API.
	 */
	public function getError(): ?array {
		return $this->error;
	}
}
