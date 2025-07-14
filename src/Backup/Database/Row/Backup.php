<?php

namespace WPMedia\BackWPup\Backup\Database\Row;

use WPMedia\BackWPup\Dependencies\BerlinDB\Database\Row;

/**
 * Backup Row.
 */
class Backup extends Row {
	/**
	 * Row ID
	 *
	 * @var int
	 */
	public $id;

	/**
	 * Destination
	 *
	 * @var string
	 */
	public $destination;

	/**
	 * Filename
	 *
	 * @var string
	 */
	public $filename;

	/**
	 * Status
	 *
	 * @var string
	 */
	public $status;

	/**
	 * Error code
	 *
	 * @var string
	 */
	public $error_code;

	/**
	 * Error message
	 *
	 * @var string
	 */
	public $error_message;

	/**
	 * Number of retries
	 *
	 * @var int
	 */
	public $retries;

	/**
	 * Last modified time
	 *
	 * @var int
	 */
	public $modified;

	/**
	 * Submitted date
	 *
	 * @var int
	 */
	public $submitted_at;

	/**
	 * UsedCSS constructor.
	 *
	 * @param mixed $item Object Row.
	 */
	public function __construct( $item ) {
		parent::__construct( $item );

		// Set the type of each column, and prepare.
		$this->id            = (int) $this->id;
		$this->destination   = (string) $this->destination;
		$this->filename      = (string) $this->filename;
		$this->status        = (string) $this->status;
		$this->error_code    = (string) $this->error_code;
		$this->error_message = (string) $this->error_message;
		$this->modified      = empty( $this->modified ) ? 0 : strtotime( $this->modified );
		$this->submitted_at  = empty( $this->submitted_at ) ? 0 : strtotime( $this->submitted_at );
	}
}
