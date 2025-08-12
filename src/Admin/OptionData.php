<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Admin;

class OptionData {
	/**
	 * Option data
	 *
	 * @var array Array of data inside the option
	 */
	private $options;

	/**
	 * Constructor
	 *
	 * @param array $options Array of data coming from an option.
	 */
	public function __construct( array $options ) {
		$this->options = $options;
	}

	/**
	 * Checks if the provided key exists in the option data array.
	 *
	 * @param string $key key name.
	 * @return boolean true if it exists, false otherwise
	 */
	public function has( string $key ): bool {
		return isset( $this->options[ $key ] );
	}

	/**
	 * Sets the value associated with a specific key.
	 *
	 * @param string $key key name.
	 * @param mixed  $value The value to set.
	 *
	 * @return  void
	 */
	public function set( string $key, $value ) {
		$this->options[ $key ] = $value;
	}

	/**
	 * Sets multiple values.
	 *
	 * @param array $options An array of key/value pairs to set.
	 * @return void
	 */
	public function set_values( array $options ): void {
		foreach ( $options as $key => $value ) {
			$this->set( $key, $value );
		}
	}

	/**
	 * Gets all available option array.
	 *
	 * @return array
	 */
	public function get_options(): array {
		return $this->options;
	}

	/**
	 * Gets the value associated with a specific key.
	 *
	 * @param string $key key name.
	 * @param mixed  $default default value to return if key doesn't exist.
	 * @return mixed
	 */
	public function get( string $key, $default = '' ) {
		if ( ! $this->has( $key ) ) {
			return $default;
		}

		return $this->options[ $key ];
	}
}
