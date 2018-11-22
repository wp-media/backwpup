<?php # -*- coding: utf-8 -*-

namespace Inpsyde\BackWPup\Notice;

/**
 * Class PromoterMessage
 *
 * @method content()
 * @method button_label()
 * @method cta_url()
 */
class PromoterMessage {

	/**
	 * @var string
	 */
	private $content;

	/**
	 * @var string
	 */
	private $button_label;

	/**
	 * @var string
	 */
	private $cta_url;

	/**
	 * @return array
	 */
	public static function defaults() {

		return array(
			'content' => '',
			'button-text' => '',
			'url' => '',
		);
	}

	/**
	 * PromoterMessage constructor
	 *
	 * @see defaults()
	 * @param array $data
	 */
	public function __construct( array $data ) {

		$data = wp_parse_args( $data, self::defaults() );

		$this->content = $data['content'];
		$this->button_label = $data['button-text'];
		$this->cta_url = $data['url'];
	}

	/**
	 * @param $name
	 * @param $args
	 *
	 * @return string
	 */
	public function __call( $name, $args ) {

		if ( ! property_exists( $this, $name ) ) {
			return '';
		}

		return $this->$name;
	}
}
