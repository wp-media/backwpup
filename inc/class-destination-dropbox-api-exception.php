<?php
/**
 * BackWPup_Destination_Dropbox_API_Exception.
 */
class BackWPup_Destination_Dropbox_API_Exception extends Exception {
	/**
	 * Context data.
	 *
	 * @var array
	 */
	private $context = [];

	/**
	 * Constructor.
	 *
	 * @param string         $message  The exception message.
	 * @param int            $code     The exception code.
	 * @param Throwable|null $previous The previous exception.
	 * @param array          $context  The context payload.
	 */
	public function __construct( $message = '', $code = 0, Throwable $previous = null, array $context = [] ) {
		parent::__construct( $message, $code, $previous );
		$this->context = $context;
	}

	/**
	 * Get context.
	 *
	 * @return array
	 */
	public function getContext(): array {
		return $this->context;
	}
}
