<?php

/**
 * Class BackWPup_S3_Destination
 */
class BackWPup_S3_Destination {

	public $label;
	public $base_url;
	public $region;
	public $allows_multipart;

	public function __construct( $label, $base_url, $region, $allows_multipart = true ) {
		$this->label            = $label;
		$this->base_url         = $base_url;
		$this->region           = $region;
		$this->allows_multipart = $allows_multipart;
	}
}
