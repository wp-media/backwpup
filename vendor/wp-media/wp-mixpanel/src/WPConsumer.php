<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Dependencies\WPMedia\Mixpanel;

use BackWPup_WPMedia_ConsumerStrategies_AbstractConsumer as WPMedia_ConsumerStrategies_AbstractConsumer;

/**
 * Consumes messages and sends them to a host/endpoint using WordPress's HTTP API
 */
class WPConsumer extends WPMedia_ConsumerStrategies_AbstractConsumer {

	/**
	 * Number of seconds to allow the request to execute.
	 *
	 * Not configurable: analytics must never delay a response. Also the lowest value
	 * WordPress honours, as it clamps the cURL timeout to a minimum of 1 second.
	 *
	 * @var int
	 */
	const REQUEST_TIMEOUT = 1;

	/**
	 * The host to connect to (e.g. api.mixpanel.com)
	 *
	 * @var string
	 */
	protected $host;

	/**
	 * The host-relative endpoint to write to (e.g. /engage)
	 *
	 * @var string
	 */
	protected $endpoint;

	/**
	 * The protocol to use for the cURL connection
	 *
	 * @var string
	 */
	protected $protocol;

	/**
	 * Creates a new WPConsumer and assigns properties from the $options array
	 *
	 * The request timeout and blocking behaviour are fixed, and cannot be set here.
	 *
	 * @param array{host: string, endpoint: string, use_ssl?: bool} $options Options for the consumer.
	 */
	public function __construct( $options ) {
		parent::__construct( $options );

		$this->host     = $options['host'];
		$this->endpoint = $options['endpoint'];
		$this->protocol = isset( $options['use_ssl'] ) && ( true === $options['use_ssl'] ) ? 'https' : 'http';
	}

	/**
	 * Send post request to the given host/endpoint using WordPress's HTTP API
	 *
	 * @param mixed[] $batch Batch of data to send to mixpanel.
	 *
	 * @return bool
	 */
	public function persist( $batch ) {
		if ( count( $batch ) <= 0 ) {
			return true;
		}

		$url  = $this->protocol . '://' . $this->host . $this->endpoint;
		$data = 'data=' . $this->_encode( $batch );

		// Non-blocking still waits up to the timeout, it only discards the response:
		// the timeout is what bounds the cost of a degraded endpoint.
		$response = wp_remote_post(
			$url,
			[
				'timeout'  => self::REQUEST_TIMEOUT,
				'blocking' => false,
				'body'     => $data,
			]
		);

		if ( is_wp_error( $response ) ) {
			$this->_handleError( $response->get_error_code(), $response->get_error_message() );
		}

		/*
		 * Report the batch as consumed even on failure. Returning false would make the
		 * producer re-queue it and retry the flush up to 10 times at shutdown, once per
		 * producer, multiplying the cost of an unreachable endpoint.
		 *
		 * @see \BackWPup_WPMedia_Producers_MixpanelBaseProducer::__destruct()
		 */
		return true;
	}
}
